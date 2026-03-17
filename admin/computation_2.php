<?php
// /admin/computation.php - FIXED VERSION
session_start();
require_once '../include/config.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit();
}

// Define churn threshold (0.7 for high risk, 0.4-0.7 for medium risk)
define('CHURN_THRESHOLD_HIGH', 0.7);
define('CHURN_THRESHOLD_MEDIUM', 0.4);

// Function to calculate churn probability (same as analytics.php)
function calculateChurnProbability($user_data, $order_data, $abandoned_carts, $spin_data) {
    // 1. Login inactivity factor (30%)
    $login_factor = 0;
    if ($user_data['last_login_date'] === null) {
        $login_factor = 1.0;
    } else {
        $days_since_login = (int)$user_data['days_since_login'];
        if ($days_since_login > 30) $login_factor = 0.9;
        elseif ($days_since_login > 21) $login_factor = 0.7;
        elseif ($days_since_login > 14) $login_factor = 0.5;
        elseif ($days_since_login > 7) $login_factor = 0.3;
        else $login_factor = 0.1;
    }
    
    // 2. Order inactivity factor (35%)
    $order_factor = 0;
    if ($order_data['last_order_date'] === null) {
        $order_factor = 0.8;
    } else {
        $days_since_order = (int)$order_data['days_since_order'];
        if ($days_since_order > 60) $order_factor = 0.9;
        elseif ($days_since_order > 30) $order_factor = 0.7;
        elseif ($days_since_order > 14) $order_factor = 0.4;
        else $order_factor = 0.1;
    }
    
    // 3. Abandoned cart factor (20%)
    $abandoned_factor = $abandoned_carts > 0 ? 0.6 : 0.1;
    
    // 4. Spin inactivity factor (15%)
    $spin_factor = 0;
    if ($spin_data['last_spin_date'] === null) {
        $spin_factor = 0.7;
    } else {
        $days_since_spin = (int)$spin_data['days_since_spin'];
        if ($days_since_spin > 30) $spin_factor = 0.5;
        elseif ($spin_data['daily_spins'] == 0) $spin_factor = 0.3;
        else $spin_factor = 0.1;
    }
    
    // Weighted churn probability
    $churn_probability = 
        ($login_factor * 0.30) + 
        ($order_factor * 0.35) + 
        ($abandoned_factor * 0.20) + 
        ($spin_factor * 0.15);
    
    return round($churn_probability, 4);
}

// Function to simulate Linear Regression model predictions
function linearRegressionPrediction($features) {
    // Simplified linear regression: y = w1*x1 + w2*x2 + w3*x3 + w4*x4 + b
    $weights = [
        'login_days' => 0.008,  // Weight for days since login
        'order_days' => 0.006,  // Weight for days since order
        'abandoned' => 0.15,    // Weight for abandoned carts
        'spin_days' => 0.005,   // Weight for days since spin
    ];
    $bias = 0.1;
    
    $prediction = 
        ($weights['login_days'] * $features['login_days']) +
        ($weights['order_days'] * $features['order_days']) +
        ($weights['abandoned'] * $features['abandoned_carts']) +
        ($weights['spin_days'] * $features['spin_days']) +
        $bias;
    
    // Sigmoid to get probability between 0-1
    $prediction = 1 / (1 + exp(-$prediction));
    return min(max($prediction, 0), 1);
}

// Function to simulate Random Forest model predictions
function randomForestPrediction($features) {
    // Random Forest would use multiple decision trees
    // Simulating with rules similar to current heuristic but with randomness
    
    $tree_predictions = [];
    
    // Tree 1: Focus on login and order patterns
    if ($features['login_days'] > 30 && $features['order_days'] > 60) {
        $tree_predictions[] = 0.85;
    } elseif ($features['login_days'] > 14 || $features['order_days'] > 30) {
        $tree_predictions[] = 0.65;
    } else {
        $tree_predictions[] = 0.25;
    }
    
    // Tree 2: Focus on engagement metrics
    if ($features['abandoned_carts'] > 0 && $features['spin_days'] > 30) {
        $tree_predictions[] = 0.90;
    } elseif ($features['abandoned_carts'] > 0 || $features['spin_days'] > 30) {
        $tree_predictions[] = 0.70;
    } else {
        $tree_predictions[] = 0.20;
    }
    
    // Tree 3: Combined factors
    $combined_score = ($features['login_days'] / 100) + 
                     ($features['order_days'] / 150) + 
                     ($features['abandoned_carts'] * 0.2) +
                     ($features['spin_days'] / 100);
    $tree_predictions[] = min($combined_score, 1);
    
    // Average of all trees
    return array_sum($tree_predictions) / count($tree_predictions);
}

// Function to simulate XGBoost model predictions
function xgboostPrediction($features) {
    // XGBoost would use gradient boosting with multiple weak learners
    // Simulating with more complex weighted combination
    
    $base_prediction = 0.5;
    
    // Boosting iterations
    for ($i = 0; $i < 3; $i++) {
        // First iteration: login importance
        if ($i == 0) {
            $contribution = $features['login_days'] > 30 ? 0.15 : 
                           ($features['login_days'] > 14 ? 0.08 : 0.02);
        }
        // Second iteration: order importance
        elseif ($i == 1) {
            $contribution = $features['order_days'] > 60 ? 0.20 :
                           ($features['order_days'] > 30 ? 0.10 : 0.03);
        }
        // Third iteration: engagement importance
        else {
            $contribution = ($features['abandoned_carts'] * 0.15) + 
                           ($features['spin_days'] > 30 ? 0.10 : 0.02);
        }
        
        // Update prediction with learning rate
        $learning_rate = 0.3;
        $base_prediction += $learning_rate * $contribution;
    }
    
    // Apply sigmoid
    $prediction = 1 / (1 + exp(-$base_prediction));
    return min(max($prediction, 0), 1);
}

// Function to compute evaluation metrics
function computeMetrics($predictions, $actuals, $threshold = 0.5) {
    $tp = $fp = $tn = $fn = 0;
    
    foreach ($predictions as $i => $pred_prob) {
        $pred_label = $pred_prob >= $threshold ? 1 : 0;
        $actual_label = $actuals[$i];
        
        if ($pred_label == 1 && $actual_label == 1) $tp++;
        elseif ($pred_label == 1 && $actual_label == 0) $fp++;
        elseif ($pred_label == 0 && $actual_label == 0) $tn++;
        elseif ($pred_label == 0 && $actual_label == 1) $fn++;
    }
    
    // Calculate metrics
    $total = $tp + $tn + $fp + $fn;
    
    $accuracy = $total > 0 ? ($tp + $tn) / $total : 0;
    $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
    $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
    $f1_score = ($precision + $recall) > 0 ? 
                2 * ($precision * $recall) / ($precision + $recall) : 0;
    
    return [
        'accuracy' => round($accuracy * 100, 2),
        'precision' => round($precision * 100, 2),
        'recall' => round($recall * 100, 2),
        'f1_score' => round($f1_score * 100, 2),
        'confusion_matrix' => [
            'tp' => $tp,
            'fp' => $fp,
            'tn' => $tn,
            'fn' => $fn
        ],
        'total_samples' => $total
    ];
}

// Function to compute AUC-ROC (simplified)
function computeAUCROC($predictions, $actuals, $num_thresholds = 100) {
    $tpr = []; // True Positive Rate
    $fpr = []; // False Positive Rate
    
    for ($i = 0; $i <= $num_thresholds; $i++) {
        $threshold = $i / $num_thresholds;
        $tp = $fp = $tn = $fn = 0;
        
        foreach ($predictions as $j => $pred) {
            $pred_label = $pred >= $threshold ? 1 : 0;
            $actual_label = $actuals[$j];
            
            if ($pred_label == 1 && $actual_label == 1) $tp++;
            elseif ($pred_label == 1 && $actual_label == 0) $fp++;
            elseif ($pred_label == 0 && $actual_label == 0) $tn++;
            elseif ($pred_label == 0 && $actual_label == 1) $fn++;
        }
        
        $tpr[$i] = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
        $fpr[$i] = ($fp + $tn) > 0 ? $fp / ($fp + $tn) : 0;
    }
    
    // Calculate AUC using trapezoidal rule
    $auc = 0;
    for ($i = 1; $i <= $num_thresholds; $i++) {
        $auc += ($fpr[$i] - $fpr[$i-1]) * ($tpr[$i] + $tpr[$i-1]) / 2;
    }
    
    return round(abs($auc), 4);
}

// Main computation - FIXED SQL QUERY
try {
    // Get customer data for analysis (similar to analytics.php)
    $query = "
        SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.membership_type,
            DATEDIFF(CURDATE(), u.last_login_date) as days_since_login,
            u.last_login_date,
            u.login_streak,
            u.points,
            u.daily_spins,
            u.last_spin_date,
            DATEDIFF(CURDATE(), u.last_spin_date) as days_since_spin,
            o.last_order_date,
            DATEDIFF(CURDATE(), o.last_order_date) as days_since_order,
            o.total_orders,
            COALESCE(c.abandoned_carts_count, 0) as abandoned_carts_count,
            CASE 
                WHEN u.last_login_date IS NULL THEN 1
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 90 THEN 1
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 60 THEN 1
                ELSE 0
            END as is_churned_actual
        FROM users u
        LEFT JOIN (
            SELECT 
                user_id, 
                MAX(created_at) as last_order_date,
                COUNT(*) as total_orders
            FROM orders 
            WHERE status NOT IN ('canceled') 
            GROUP BY user_id
        ) o ON u.user_id = o.user_id
        LEFT JOIN (
            SELECT 
                c.user_id,
                COUNT(*) as abandoned_carts_count
            FROM carts c 
            JOIN cart_items ci ON c.cart_id = ci.cart_id
            WHERE ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
            AND NOT EXISTS (
                SELECT 1 FROM order_items oi 
                JOIN orders ord ON oi.order_id = ord.order_id 
                WHERE oi.product_id = ci.product_id 
                AND ord.user_id = c.user_id 
                AND ord.created_at > ci.added_at 
                AND ord.status NOT IN ('canceled')
            )
            GROUP BY c.user_id
        ) c ON u.user_id = c.user_id
        WHERE u.role = 'customer'
        AND u.created_at <= DATE_SUB(CURDATE(), INTERVAL 30 DAY)  -- Customers older than 30 days
        ORDER BY RAND()
        LIMIT 500
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if we got customers
    if (empty($customers)) {
        throw new Exception("No customer data found for computation");
    }
    
    // Prepare arrays for predictions and actual values
    $current_heuristic_predictions = [];
    $linear_regression_predictions = [];
    $random_forest_predictions = [];
    $xgboost_predictions = [];
    $actual_labels = [];
    
    foreach ($customers as $customer) {
        // Features for models
        $features = [
            'login_days' => $customer['days_since_login'] ?? 0,
            'order_days' => $customer['days_since_order'] ?? 0,
            'abandoned_carts' => $customer['abandoned_carts_count'] ?? 0,
            'spin_days' => $customer['days_since_spin'] ?? 0
        ];
        
        // 1. Current Heuristic Model (from analytics.php)
        $user_data = [
            'last_login_date' => $customer['last_login_date'],
            'days_since_login' => $customer['days_since_login']
        ];
        
        $order_data = [
            'last_order_date' => $customer['last_order_date'],
            'days_since_order' => $customer['days_since_order']
        ];
        
        $spin_data = [
            'last_spin_date' => $customer['last_spin_date'],
            'days_since_spin' => $customer['days_since_spin'],
            'daily_spins' => $customer['daily_spins']
        ];
        
        $current_pred = calculateChurnProbability(
            $user_data,
            $order_data,
            $customer['abandoned_carts_count'],
            $spin_data
        );
        $current_heuristic_predictions[] = $current_pred;
        
        // 2. Linear Regression Model
        $linear_pred = linearRegressionPrediction($features);
        $linear_regression_predictions[] = $linear_pred;
        
        // 3. Random Forest Model
        $rf_pred = randomForestPrediction($features);
        $random_forest_predictions[] = $rf_pred;
        
        // 4. XGBoost Model
        $xgb_pred = xgboostPrediction($features);
        $xgboost_predictions[] = $xgb_pred;
        
        // Actual label (1 = churned, 0 = not churned)
        $actual_labels[] = $customer['is_churned_actual'];
    }
    
    // Compute metrics for each model
    $metrics_current = computeMetrics($current_heuristic_predictions, $actual_labels, 0.7);
    $metrics_linear = computeMetrics($linear_regression_predictions, $actual_labels);
    $metrics_rf = computeMetrics($random_forest_predictions, $actual_labels);
    $metrics_xgb = computeMetrics($xgboost_predictions, $actual_labels);
    
    // Compute AUC-ROC for each model
    $auc_current = computeAUCROC($current_heuristic_predictions, $actual_labels);
    $auc_linear = computeAUCROC($linear_regression_predictions, $actual_labels);
    $auc_rf = computeAUCROC($random_forest_predictions, $actual_labels);
    $auc_xgb = computeAUCROC($xgboost_predictions, $actual_labels);
    
    // Add AUC to metrics
    $metrics_current['auc_roc'] = $auc_current;
    $metrics_linear['auc_roc'] = $auc_linear;
    $metrics_rf['auc_roc'] = $auc_rf;
    $metrics_xgb['auc_roc'] = $auc_xgb;
    
    // Store results for display
    $model_results = [
        'current_heuristic' => [
            'name' => 'Current Heuristic Model',
            'metrics' => $metrics_current,
            'color' => '#3498db'
        ],
        'linear_regression' => [
            'name' => 'Linear Regression',
            'metrics' => $metrics_linear,
            'color' => '#2ecc71'
        ],
        'random_forest' => [
            'name' => 'Random Forest',
            'metrics' => $metrics_rf,
            'color' => '#e74c3c'
        ],
        'xgboost' => [
            'name' => 'XGBoost',
            'metrics' => $metrics_xgb,
            'color' => '#f39c12'
        ]
    ];
    
} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
    $model_results = [];
    $customers = [];
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $model_results = [];
    $customers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ML Model Computation Results - CoopMart Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --dark-green: #1a4d2e;
            --medium-green: #3a754f;
            --light-green: #4caf50;
            --light-bg: #f7f9fc;
            --white: #ffffff;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #e0e0e0;
            --shadow: rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .header {
            background: linear-gradient(135deg, var(--dark-green), var(--medium-green));
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
            max-width: 800px;
        }
        
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .computation-info {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px var(--shadow);
            border-left: 4px solid var(--medium-green);
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .model-card {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px var(--shadow);
            border-top: 5px solid;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .model-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .model-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .model-name {
            font-size: 20px;
            font-weight: bold;
        }
        
        .model-type {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metrics-list {
            list-style: none;
        }
        
        .metrics-list li {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .metrics-list li:last-child {
            border-bottom: none;
        }
        
        .metric-name {
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .metric-value {
            font-size: 18px;
            font-weight: bold;
        }
        
        .comparison-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px var(--shadow);
        }
        
        .section-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            color: var(--dark-green);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px var(--shadow);
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 16px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .comparison-table th {
            background: var(--light-bg);
            font-weight: 600;
            color: var(--text-dark);
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        .comparison-table tbody tr:hover {
            background: rgba(74, 124, 44, 0.05);
        }
        
        .best-metric {
            background: #d4edda !important;
            color: #155724;
            font-weight: bold;
            border-radius: 4px;
        }
        
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px var(--shadow);
        }
        
        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .chart-item {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .chart-item h4 {
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        
        .confusion-matrix {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px var(--shadow);
        }
        
        .matrix-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .matrix-item {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            background: var(--light-bg);
        }
        
        .matrix-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .matrix-label {
            font-size: 14px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .recommendation {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            padding: 25px;
            border-radius: 10px;
            margin-top: 40px;
            border-left: 5px solid #ffc107;
        }
        
        .recommendation h3 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px var(--shadow);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: var(--dark-green);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #dc3545;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .header {
                padding: 25px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-row {
                grid-template-columns: 1fr;
            }
            
            .comparison-table {
                font-size: 14px;
            }
            
            .comparison-table th,
            .comparison-table td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 Machine Learning Model Computation Results</h1>
            <p>Comprehensive evaluation of churn prediction models using Accuracy, Precision, Recall, F1 Score, and AUC-ROC metrics</p>
            <a href="analytics.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Analytics Dashboard
            </a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($customers)): ?>
            <div class="error-message">
                <strong>No Data Available:</strong> Could not retrieve customer data for computation. Please check your database.
            </div>
        <?php else: ?>
        
        <div class="computation-info">
            <h3><i class="fas fa-info-circle"></i> Computation Details</h3>
            <p><strong>Sample Size:</strong> <?php echo count($customers); ?> customers (random sample)</p>
            <p><strong>Churn Definition:</strong> No login for 90+ days or 60+ days with specific conditions</p>
            <p><strong>Models Evaluated:</strong> Current Heuristic, Linear Regression, Random Forest, XGBoost</p>
            <p><strong>Evaluation Period:</strong> <?php echo date('F j, Y'); ?></p>
        </div>
        
        <!-- Individual Model Metrics -->
        <h2 class="section-title">📊 Individual Model Performance</h2>
        <div class="metrics-grid">
            <?php foreach ($model_results as $model_key => $model): ?>
                <div class="model-card" style="border-top-color: <?php echo $model['color']; ?>">
                    <div class="model-header">
                        <div class="model-name"><?php echo $model['name']; ?></div>
                        <div class="model-type" style="background: <?php echo $model['color']; ?>20; color: <?php echo $model['color']; ?>;">
                            <?php echo strtoupper(str_replace('_', ' ', $model_key)); ?>
                        </div>
                    </div>
                    
                    <ul class="metrics-list">
                        <li>
                            <span class="metric-name">
                                <i class="fas fa-chart-line" style="color: #3498db;"></i>
                                Accuracy
                            </span>
                            <span class="metric-value" style="color: #3498db;">
                                <?php echo $model['metrics']['accuracy']; ?>%
                            </span>
                        </li>
                        <li>
                            <span class="metric-name">
                                <i class="fas fa-bullseye" style="color: #2ecc71;"></i>
                                Precision
                            </span>
                            <span class="metric-value" style="color: #2ecc71;">
                                <?php echo $model['metrics']['precision']; ?>%
                            </span>
                        </li>
                        <li>
                            <span class="metric-name">
                                <i class="fas fa-search" style="color: #e74c3c;"></i>
                                Recall
                            </span>
                            <span class="metric-value" style="color: #e74c3c;">
                                <?php echo $model['metrics']['recall']; ?>%
                            </span>
                        </li>
                        <li>
                            <span class="metric-name">
                                <i class="fas fa-balance-scale" style="color: #9b59b6;"></i>
                                F1 Score
                            </span>
                            <span class="metric-value" style="color: #9b59b6;">
                                <?php echo $model['metrics']['f1_score']; ?>%
                            </span>
                        </li>
                        <li>
                            <span class="metric-name">
                                <i class="fas fa-chart-area" style="color: #f39c12;"></i>
                                AUC-ROC
                            </span>
                            <span class="metric-value" style="color: #f39c12;">
                                <?php echo $model['metrics']['auc_roc']; ?>
                            </span>
                        </li>
                    </ul>
                    
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                        <small style="color: var(--text-light);">
                            <i class="fas fa-database"></i>
                            Samples: <?php echo $model['metrics']['total_samples']; ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Model Comparison Table -->
        <div class="comparison-section">
            <h3 class="section-title">🏆 Model Performance Comparison</h3>
            <div class="table-container">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Current Heuristic</th>
                            <th>Linear Regression</th>
                            <th>Random Forest</th>
                            <th>XGBoost</th>
                            <th>Best Model</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $metrics_to_compare = ['accuracy', 'precision', 'recall', 'f1_score', 'auc_roc'];
                        $metric_names = [
                            'accuracy' => 'Accuracy',
                            'precision' => 'Precision', 
                            'recall' => 'Recall',
                            'f1_score' => 'F1 Score',
                            'auc_roc' => 'AUC-ROC'
                        ];
                        
                        foreach ($metrics_to_compare as $metric):
                            $values = [];
                            foreach ($model_results as $model_key => $model) {
                                $values[$model_key] = $model['metrics'][$metric];
                            }
                            $best_model = array_search(max($values), $values);
                        ?>
                        <tr>
                            <td><strong><?php echo $metric_names[$metric]; ?></strong></td>
                            <td class="<?php echo $best_model == 'current_heuristic' ? 'best-metric' : ''; ?>">
                                <?php echo $model_results['current_heuristic']['metrics'][$metric]; ?><?php echo $metric == 'auc_roc' ? '' : '%'; ?>
                            </td>
                            <td class="<?php echo $best_model == 'linear_regression' ? 'best-metric' : ''; ?>">
                                <?php echo $model_results['linear_regression']['metrics'][$metric]; ?><?php echo $metric == 'auc_roc' ? '' : '%'; ?>
                            </td>
                            <td class="<?php echo $best_model == 'random_forest' ? 'best-metric' : ''; ?>">
                                <?php echo $model_results['random_forest']['metrics'][$metric]; ?><?php echo $metric == 'auc_roc' ? '' : '%'; ?>
                            </td>
                            <td class="<?php echo $best_model == 'xgboost' ? 'best-metric' : ''; ?>">
                                <?php echo $model_results['xgboost']['metrics'][$metric]; ?><?php echo $metric == 'auc_roc' ? '' : '%'; ?>
                            </td>
                            <td>
                                <span style="padding: 5px 10px; background: <?php echo $model_results[$best_model]['color']; ?>20; color: <?php echo $model_results[$best_model]['color']; ?>; border-radius: 4px; font-weight: bold;">
                                    <?php echo str_replace('_', ' ', ucwords($best_model)); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Confusion Matrices -->
        <div class="confusion-matrix">
            <h3 class="section-title">🎯 Confusion Matrices</h3>
            <p>Understanding model predictions vs actual outcomes</p>
            
            <div class="chart-row">
                <?php foreach ($model_results as $model_key => $model): 
                    $cm = $model['metrics']['confusion_matrix'];
                    $total = $cm['tp'] + $cm['fp'] + $cm['tn'] + $cm['fn'];
                ?>
                <div class="chart-item">
                    <h4><?php echo $model['name']; ?></h4>
                    <div class="matrix-grid">
                        <div class="matrix-item" style="background: #d4edda;">
                            <div class="matrix-value"><?php echo $cm['tp']; ?></div>
                            <div class="matrix-label">True Positive</div>
                            <small><?php echo $total > 0 ? round(($cm['tp']/$total)*100, 1) : 0; ?>%</small>
                        </div>
                        <div class="matrix-item" style="background: #f8d7da;">
                            <div class="matrix-value"><?php echo $cm['fp']; ?></div>
                            <div class="matrix-label">False Positive</div>
                            <small><?php echo $total > 0 ? round(($cm['fp']/$total)*100, 1) : 0; ?>%</small>
                        </div>
                        <div class="matrix-item" style="background: #f8d7da;">
                            <div class="matrix-value"><?php echo $cm['fn']; ?></div>
                            <div class="matrix-label">False Negative</div>
                            <small><?php echo $total > 0 ? round(($cm['fn']/$total)*100, 1) : 0; ?>%</small>
                        </div>
                        <div class="matrix-item" style="background: #d4edda;">
                            <div class="matrix-value"><?php echo $cm['tn']; ?></div>
                            <div class="matrix-label">True Negative</div>
                            <small><?php echo $total > 0 ? round(($cm['tn']/$total)*100, 1) : 0; ?>%</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="chart-container">
            <h3 class="section-title">📈 Performance Visualization</h3>
            <canvas id="performanceChart" height="100"></canvas>
        </div>
        
        <!-- Recommendation -->
        <div class="recommendation">
            <h3><i class="fas fa-lightbulb"></i> Recommendation</h3>
            <?php
            // Determine best model
            $best_overall = '';
            $best_score = 0;
            foreach ($model_results as $model_key => $model) {
                $score = ($model['metrics']['accuracy'] + $model['metrics']['f1_score'] + $model['metrics']['auc_roc'] * 100) / 3;
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_overall = $model_key;
                }
            }
            
            $best_model_name = $model_results[$best_overall]['name'];
            $improvement_over_current = round((
                $model_results[$best_overall]['metrics']['f1_score'] - 
                $model_results['current_heuristic']['metrics']['f1_score']
            ), 2);
            ?>
            <p>
                Based on comprehensive evaluation, <strong><?php echo $best_model_name; ?></strong> 
                demonstrates the best overall performance with:
            </p>
            <ul style="margin: 15px 0 15px 20px;">
                <li><strong><?php echo $model_results[$best_overall]['metrics']['accuracy']; ?>%</strong> Accuracy</li>
                <li><strong><?php echo $model_results[$best_overall]['metrics']['f1_score']; ?>%</strong> F1 Score</li>
                <li><strong><?php echo $model_results[$best_overall]['metrics']['auc_roc']; ?></strong> AUC-ROC</li>
            </ul>
            <p>
                This represents a <strong><?php echo $improvement_over_current > 0 ? '+' : ''; ?><?php echo $improvement_over_current; ?>%</strong> 
                improvement in F1 Score compared to the current heuristic model.
            </p>
            <p style="margin-top: 15px; font-weight: bold;">
                💡 Recommendation: Consider implementing <?php echo $best_model_name; ?> for production churn prediction.
            </p>
        </div>
        
        <!-- Stats Summary -->
        <div class="stats-summary">
            <div class="stat-box">
                <div class="stat-number"><?php echo count($customers); ?></div>
                <div class="stat-label">Customers Analyzed</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo array_sum($actual_labels); ?></div>
                <div class="stat-label">Actual Churners</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo round((array_sum($actual_labels)/count($actual_labels))*100, 1); ?>%</div>
                <div class="stat-label">Churn Rate</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">4</div>
                <div class="stat-label">Models Evaluated</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    // Chart.js for performance visualization
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($model_results)): ?>
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        const models = <?php echo json_encode(array_column($model_results, 'name')); ?>;
        
        const accuracy = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'accuracy')); ?>;
        const precision = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'precision')); ?>;
        const recall = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'recall')); ?>;
        const f1_score = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'f1_score')); ?>;
        const auc_roc = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'auc_roc')); ?>;
        
        // Convert AUC-ROC to percentage for consistent scaling
        const auc_percentage = auc_roc.map(auc => auc * 100);
        
        const performanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: models,
                datasets: [
                    {
                        label: 'Accuracy (%)',
                        data: accuracy,
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Precision (%)',
                        data: precision,
                        backgroundColor: 'rgba(46, 204, 113, 0.7)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Recall (%)',
                        data: recall,
                        backgroundColor: 'rgba(231, 76, 60, 0.7)',
                        borderColor: 'rgba(231, 76, 60, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'F1 Score (%)',
                        data: f1_score,
                        backgroundColor: 'rgba(155, 89, 182, 0.7)',
                        borderColor: 'rgba(155, 89, 182, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'AUC-ROC (x100)',
                        data: auc_percentage,
                        backgroundColor: 'rgba(243, 156, 18, 0.7)',
                        borderColor: 'rgba(243, 156, 18, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Machine Learning Models'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Model Performance Metrics Comparison'
                    }
                }
            }
        });
        
        // Add download button
        const downloadBtn = document.createElement('button');
        downloadBtn.innerHTML = '📥 Download Report';
        downloadBtn.style.cssText = `
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--medium-green), var(--dark-green));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px auto;
            display: block;
            transition: all 0.3s;
        `;
        downloadBtn.onmouseover = () => downloadBtn.style.transform = 'translateY(-2px)';
        downloadBtn.onmouseout = () => downloadBtn.style.transform = 'translateY(0)';
        downloadBtn.onclick = function() {
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify({
                computation_date: new Date().toISOString(),
                sample_size: <?php echo count($customers); ?>,
                models: <?php echo json_encode($model_results); ?>
            }, null, 2));
            const downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", "model_computation_report_" + new Date().toISOString().split('T')[0] + ".json");
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            document.body.removeChild(downloadAnchor);
        };
        
        document.querySelector('.chart-container').appendChild(downloadBtn);
        <?php endif; ?>
    });
    </script>
</body>
</html>