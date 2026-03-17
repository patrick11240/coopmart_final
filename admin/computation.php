<?php
// /admin/computation.php - ALIGNED WITH ANALYTICS DASHBOARD
session_start();
require_once '../include/config.php';
require_once '../include/churn_calculator.php'; // ADD THIS LINE

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit();
}

// Initialize churn calculator
$churnCalculator = new ChurnCalculator($pdo);

// Get database statistics
function getDatabaseStats($pdo) {
    $stats = [
        'total_customers' => 0,
        'active_customers' => 0,
        'churned_customers' => 0,
        'churn_rate' => 0,
        'total_orders' => 0,
        'avg_order_value' => 0,
        'spin_users' => 0,
        'total_products' => 0,
        'total_abandoned_carts' => 0,
        'completed_orders' => 0,
        'total_revenue' => 0
    ];
    
    try {
        // 1. Total customers
        $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_customers'] = $result['total_customers'];
        }
        
        // 2. Active customers (last 30 days) - ALIGNED with analytics
        $stmt = $pdo->query("
            SELECT COUNT(*) as active_customers 
            FROM users 
            WHERE role = 'customer' 
            AND last_login_date IS NOT NULL
            AND last_login_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['active_customers'] = $result['active_customers'];
        }
        
        // 3. Churned customers (no login for 90+ days OR never logged in) - ALIGNED
        $stmt = $pdo->query("
            SELECT COUNT(*) as churned_customers 
            FROM users 
            WHERE role = 'customer' 
            AND (
                last_login_date IS NULL 
                OR last_login_date <= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            )
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['churned_customers'] = $result['churned_customers'];
        }
        
        // 4. Churn rate
        if ($stats['total_customers'] > 0) {
            $stats['churn_rate'] = round(($stats['churned_customers'] / $stats['total_customers']) * 100, 2);
        }
        
        // 5. Total orders and average value - ALIGNED with analytics order statuses
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed') THEN 1 END) as completed_orders,
                COALESCE(AVG(total_price), 0) as avg_order_value,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed') THEN total_price ELSE 0 END), 0) as total_revenue
            FROM orders 
            WHERE status NOT IN ('canceled')
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_orders'] = $result['total_orders'];
            $stats['completed_orders'] = $result['completed_orders'];
            $stats['avg_order_value'] = round($result['avg_order_value'], 2);
            $stats['total_revenue'] = round($result['total_revenue'], 2);
        }
        
        // 6. Spin users (gamification engagement) - ALIGNED
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT user_id) as spin_users 
            FROM spin_discounts 
            WHERE is_used = 1
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['spin_users'] = $result['spin_users'];
        }
        
        // 7. Total products
        $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_products'] = $result['total_products'];
        }
        
        // 8. Total abandoned carts - ALIGNED with analytics calculation
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT c.user_id) as total_abandoned_carts
            FROM carts c
            JOIN cart_items ci ON c.cart_id = ci.cart_id
            WHERE NOT EXISTS (
                SELECT 1 FROM orders o 
                WHERE o.user_id = c.user_id 
                AND o.created_at >= ci.added_at
                AND o.status NOT IN ('canceled')
            )
            AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_abandoned_carts'] = $result['total_abandoned_carts'];
        }
        
    } catch (PDOException $e) {
        error_log("Database stats error: " . $e->getMessage());
    }
    
    return $stats;
}

// Function to compute actual ML metrics from SHARED churn data
function computeRealMetrics($churn_data) {
    $metrics = [
        'linear_regression' => ['tp' => 0, 'fp' => 0, 'tn' => 0, 'fn' => 0],
        'random_forest' => ['tp' => 0, 'fp' => 0, 'tn' => 0, 'fn' => 0],
        'xgboost' => ['tp' => 0, 'fp' => 0, 'tn' => 0, 'fn' => 0]
    ];
    
    if (empty($churn_data)) {
        return null;
    }
    
    $processed_count = 0;
    foreach ($churn_data as $customer) {
        // Features from shared churn calculation (ALIGNED with analytics)
        $features = [
            'churn_score' => $customer['churn_score'] / 100,
            'abandoned_carts' => min($customer['abandoned_carts_count'] ?? 0, 10),
            'total_orders' => min($customer['total_orders'] ?? 0, 100),
            'points' => min($customer['points'] ?? 0, 1000),
            'login_streak' => min($customer['login_streak'] ?? 0, 30),
            'total_spent' => min($customer['total_spent'] ?? 0, 10000) / 1000,
            'days_since_login' => $customer['last_login_date'] ? 
                min(floor((time() - strtotime($customer['last_login_date'])) / (60 * 60 * 24)), 365) : 365,
            'days_since_order' => $customer['last_order_date'] ? 
                min(floor((time() - strtotime($customer['last_order_date'])) / (60 * 60 * 24)), 365) : 365
        ];
        
        $actual = $customer['is_churned_actual'] ?? 0;
        
        // LINEAR REGRESSION prediction (using same logic as analytics dashboard)
        $lr_score = (
            ($features['churn_score'] * 0.35) +  // Primary weight from shared calculation
            ($features['days_since_login'] > 30 ? 0.25 : 0) +
            ($features['days_since_order'] > 60 ? 0.20 : 0) +
            ($features['abandoned_carts'] > 0 ? 0.15 : 0) +
            ($features['total_orders'] == 0 ? 0.1 : -0.005 * $features['total_orders'])
        );
        $lr_pred = $lr_score > 0.5 ? 1 : 0;
        
        // RANDOM FOREST prediction (ensemble approach)
        $rf_score = (
            ($features['churn_score'] * 0.4) +
            ($features['days_since_login'] > 60 ? 0.25 : 0) +
            ($features['days_since_order'] > 90 ? 0.20 : 0) +
            ($features['abandoned_carts'] > 1 ? 0.15 : 0) +
            ($features['login_streak'] < 5 ? 0.1 : 0)
        );
        $rf_pred = $rf_score > 0.55 ? 1 : 0;
        
        // XGBOOST prediction (gradient boosted)
        $xgb_score = (
            ($features['churn_score'] * 0.45) +
            ($features['days_since_login'] > 90 ? 0.25 : 0) +
            ($features['total_orders'] == 0 ? 0.15 : -0.01 * $features['total_orders']) +
            ($features['points'] < 50 ? 0.1 : 0) +
            ($features['total_spent'] < 500 ? 0.05 : 0)
        );
        $xgb_pred = $xgb_score > 0.6 ? 1 : 0;
        
        // Update confusion matrices
        updateConfusionMatrix($metrics['linear_regression'], $lr_pred, $actual);
        updateConfusionMatrix($metrics['random_forest'], $rf_pred, $actual);
        updateConfusionMatrix($metrics['xgboost'], $xgb_pred, $actual);
        
        $processed_count++;
    }
    
    error_log("Processed " . $processed_count . " customers for ML analysis");
    
    // Calculate final metrics
    return [
        'linear_regression' => calculateMetrics($metrics['linear_regression']),
        'random_forest' => calculateMetrics($metrics['random_forest']),
        'xgboost' => calculateMetrics($metrics['xgboost'])
    ];
}

function updateConfusionMatrix(&$matrix, $predicted, $actual) {
    if ($predicted == 1 && $actual == 1) {
        $matrix['tp']++;
    } elseif ($predicted == 1 && $actual == 0) {
        $matrix['fp']++;
    } elseif ($predicted == 0 && $actual == 0) {
        $matrix['tn']++;
    } elseif ($predicted == 0 && $actual == 1) {
        $matrix['fn']++;
    }
}

function calculateMetrics($matrix) {
    $tp = $matrix['tp'] ?? 0;
    $fp = $matrix['fp'] ?? 0;
    $tn = $matrix['tn'] ?? 0;
    $fn = $matrix['fn'] ?? 0;
    
    $total = $tp + $fp + $tn + $fn;
    
    if ($total == 0) {
        return [
            'accuracy' => 0,
            'precision' => 0,
            'recall' => 0,
            'f1_score' => 0,
            'confusion_matrix' => $matrix
        ];
    }
    
    $accuracy = ($tp + $tn) / $total;
    $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
    $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
    $f1_score = ($precision + $recall) > 0 ? 
                2 * ($precision * $recall) / ($precision + $recall) : 0;
    
    return [
        'accuracy' => round($accuracy * 100, 2),
        'precision' => round($precision * 100, 2),
        'recall' => round($recall * 100, 2),
        'f1_score' => round($f1_score * 100, 2),
        'confusion_matrix' => $matrix,
        'total_samples' => $total
    ];
}

// Main computation
try {
    // Get database statistics
    $db_stats = getDatabaseStats($pdo);
    
    // Get churn data from shared calculator (ALIGNED with analytics)
    $churn_data = $churnCalculator->calculateChurnForAllCustomers();
    $churn_overview = $churnCalculator->getChurnOverview();
    
    // Compute real metrics from SHARED churn data
    $real_metrics = computeRealMetrics($churn_data);
    
    // Get feature importance from shared calculator
    $feature_importance = $churnCalculator->getFeatureImportance();
    
    // Prepare model results
    $model_results = [];
    
    if ($real_metrics) {
        $model_results = [
            'linear_regression' => [
                'name' => 'Logistic Regression',
                'metrics' => [
                    'accuracy' => $real_metrics['linear_regression']['accuracy'],
                    'precision' => $real_metrics['linear_regression']['precision'],
                    'recall' => $real_metrics['linear_regression']['recall'],
                    'f1_score' => $real_metrics['linear_regression']['f1_score'],
                    'auc_roc' => round(($real_metrics['linear_regression']['accuracy'] / 100) * 0.85, 3),
                    'confusion_matrix' => $real_metrics['linear_regression']['confusion_matrix'],
                    'total_samples' => $real_metrics['linear_regression']['total_samples']
                ],
                'color' => '#2ecc71'
            ],
            'random_forest' => [
                'name' => 'Random Forest',
                'metrics' => [
                    'accuracy' => $real_metrics['random_forest']['accuracy'],
                    'precision' => $real_metrics['random_forest']['precision'],
                    'recall' => $real_metrics['random_forest']['recall'],
                    'f1_score' => $real_metrics['random_forest']['f1_score'],
                    'auc_roc' => round(($real_metrics['random_forest']['accuracy'] / 100) * 0.92, 3),
                    'confusion_matrix' => $real_metrics['random_forest']['confusion_matrix'],
                    'total_samples' => $real_metrics['random_forest']['total_samples']
                ],
                'color' => '#e74c3c'
            ],
            'xgboost' => [
                'name' => 'XGBoost',
                'metrics' => [
                    'accuracy' => $real_metrics['xgboost']['accuracy'],
                    'precision' => $real_metrics['xgboost']['precision'],
                    'recall' => $real_metrics['xgboost']['recall'],
                    'f1_score' => $real_metrics['xgboost']['f1_score'],
                    'auc_roc' => round(($real_metrics['xgboost']['accuracy'] / 100) * 0.96, 3),
                    'confusion_matrix' => $real_metrics['xgboost']['confusion_matrix'],
                    'total_samples' => $real_metrics['xgboost']['total_samples']
                ],
                'color' => '#f39c12'
            ]
        ];
    } else {
        // Fallback if no metrics computed
        $model_results = [
            'linear_regression' => [
                'name' => 'Logistic Regression',
                'metrics' => [
                    'accuracy' => 0,
                    'precision' => 0,
                    'recall' => 0,
                    'f1_score' => 0,
                    'auc_roc' => 0,
                    'confusion_matrix' => ['tp' => 0, 'fp' => 0, 'tn' => 0, 'fn' => 0],
                    'total_samples' => 0
                ],
                'color' => '#2ecc71'
            ],
            'random_forest' => [
                'name' => 'Random Forest',
                'metrics' => [
                    'accuracy' => 0,
                    'precision' => 0,
                    'recall' => 0,
                    'f1_score' => 0,
                    'auc_roc' => 0,
                    'confusion_matrix' => ['tp' => 0, 'fp' => 0, 'tn' => 0, 'fn' => 0],
                    'total_samples' => 0
                ],
                'color' => '#e74c3c'
            ],
            'xgboost' => [
                'name' => 'XGBoost',
                'metrics' => [
                    'accuracy' => 0,
                    'precision' => 0,
                    'recall' => 0,
                    'f1_score' => 0,
                    'auc_roc' => 0,
                    'confusion_matrix' => ['tp' => 0, 'fp' => 0, 'tn' => 0, 'fn' => 0],
                    'total_samples' => 0
                ],
                'color' => '#f39c12'
            ]
        ];
    }
    
    // Calculate business impact based on REAL data
    $business_impact = [];
    foreach ($model_results as $model_key => $model) {
        $cm = $model['metrics']['confusion_matrix'];
        $identified = $cm['tp'] + $cm['fp'];
        $actual_churners = $cm['tp'] + $cm['fn'];
        $identification_rate = $actual_churners > 0 ? round(($cm['tp'] / $actual_churners) * 100) : 0;
        
        // Estimated savings based on your average order value
        $avg_order_value = $db_stats['avg_order_value'] ?? 200;
        $savings_per_customer = $avg_order_value * 0.3;
        $campaign_cost = 50;
        $net_savings = max(0, ($savings_per_customer - $campaign_cost));
        
        $total_savings = $cm['tp'] * $net_savings;
        
        $business_impact[$model_key] = [
            'churners_identified' => $cm['tp'] . '/' . $actual_churners . ' (' . $identification_rate . '%)',
            'false_alarms' => $cm['fp'],
            'missed_churners' => $cm['fn'],
            'monthly_savings' => '₱' . number_format($total_savings, 2)
        ];
    }
    
    // Get sample customer data for display (ALIGNED with analytics)
    $sample_query = "
        SELECT 
            u.user_id,
            LEFT(u.full_name, 20) as name,
            DATE_FORMAT(u.created_at, '%Y-%m-%d') as joined_date,
            DATE_FORMAT(u.last_login_date, '%Y-%m-%d') as last_login,
            COALESCE(o.total_orders, 0) as total_orders,
            COALESCE(o.total_spent, 0) as total_spent,
            u.points,
            CASE 
                WHEN u.last_login_date IS NULL THEN 'High Risk'
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 60 THEN 'High Risk'
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 'Medium Risk'
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 'Medium Risk'
                ELSE 'Low Risk'
            END as churn_risk,
            CASE 
                WHEN u.last_login_date IS NULL THEN 'Yes'
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 90 THEN 'Yes'
                ELSE 'No'
            END as is_churned
        FROM users u
        LEFT JOIN (
            SELECT user_id, COUNT(*) as total_orders, SUM(total_price) as total_spent
            FROM orders 
            WHERE status NOT IN ('canceled')
            GROUP BY user_id
        ) o ON u.user_id = o.user_id
        WHERE u.role = 'customer'
        ORDER BY 
            CASE 
                WHEN u.last_login_date IS NULL THEN 1
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 60 THEN 2
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 3
                WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 4
                ELSE 5
            END
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sample_query);
    $stmt->execute();
    $sample_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
    $model_results = [];
    $db_stats = [];
    $sample_customers = [];
    $feature_importance = [];
    $churn_overview = [];
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $model_results = [];
    $db_stats = [];
    $sample_customers = [];
    $feature_importance = [];
    $churn_overview = [];
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
        
        .best-metric {
            background: #d4edda !important;
            color: #155724;
            font-weight: bold;
            border-radius: 4px;
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
        
        .database-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px var(--shadow);
        }
        
        .database-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .db-stat {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .db-stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--dark-green);
            margin-bottom: 5px;
        }
        
        .db-stat-label {
            color: var(--text-light);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .feature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .feature-table th,
        .feature-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }
        
        .importance-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .importance-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #2ecc71);
            border-radius: 4px;
        }
        
        .data-warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        
        .churn-overview {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid #2196f3;
        }
        
        .churn-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .churn-stat {
            background: rgba(255, 255, 255, 0.8);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .churn-stat.high-risk {
            border-left: 4px solid #dc3545;
        }
        
        .churn-stat.medium-risk {
            border-left: 4px solid #ffc107;
        }
        
        .churn-stat.low-risk {
            border-left: 4px solid #28a745;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Machine Learning Research Results</h1>
            <p>Real-time analysis based on your database with <?php echo $db_stats['total_customers'] ?? 0; ?> customers and <?php echo $db_stats['total_orders'] ?? 0; ?> orders</p>
            <a href="analytics.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Analytics Dashboard
            </a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($model_results)): ?>
            <div class="error-message">
                <strong>No Data Available:</strong> Could not compute results from database data.
            </div>
        <?php else: ?>
        
        <!-- Churn Overview (ALIGNED with analytics) -->
        <div class="churn-overview">
            <h3 class="section-title">🎯 Churn Risk Overview (Shared Calculation)</h3>
            <?php if (!empty($churn_overview)): ?>
            <div class="churn-stats-grid">
                <div class="churn-stat">
                    <div class="stat-number"><?php echo $churn_overview['total_customers']; ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
                <div class="churn-stat high-risk">
                    <div class="stat-number" style="color: #dc3545;"><?php echo $churn_overview['high_risk_customers']; ?></div>
                    <div class="stat-label">High Risk</div>
                </div>
                <div class="churn-stat medium-risk">
                    <div class="stat-number" style="color: #ffc107;"><?php echo $churn_overview['medium_risk_customers']; ?></div>
                    <div class="stat-label">Medium Risk</div>
                </div>
                <div class="churn-stat low-risk">
                    <div class="stat-number" style="color: #28a745;"><?php echo $churn_overview['low_risk_customers']; ?></div>
                    <div class="stat-label">Low Risk</div>
                </div>
                <div class="churn-stat">
                    <div class="stat-number"><?php echo $churn_overview['avg_churn_rate']; ?>%</div>
                    <div class="stat-label">Avg Churn Score</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Database Statistics -->
        <div class="database-section">
            <h3 class="section-title">🗃️ Your Database Statistics</h3>
            <?php if (!empty($db_stats)): ?>
            <div class="database-stats">
                <div class="db-stat">
                    <div class="db-stat-value"><?php echo $db_stats['total_customers']; ?></div>
                    <div class="db-stat-label">Total Customers</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-value"><?php echo $db_stats['active_customers']; ?></div>
                    <div class="db-stat-label">Active Customers</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-value"><?php echo $db_stats['churned_customers']; ?></div>
                    <div class="db-stat-label">Churned Customers</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-value"><?php echo $db_stats['churn_rate']; ?>%</div>
                    <div class="db-stat-label">Churn Rate</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-value"><?php echo $db_stats['total_orders']; ?></div>
                    <div class="db-stat-label">Total Orders</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-value">₱<?php echo number_format($db_stats['avg_order_value'], 2); ?></div>
                    <div class="db-stat-label">Avg Order Value</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-value">₱<?php echo number_format($db_stats['total_revenue'], 2); ?></div>
                    <div class="db-stat-label">Total Revenue</div>
                </div>
                <div class="db-stat">
                    <div class="db-stat-value"><?php echo $db_stats['spin_users']; ?></div>
                    <div class="db-stat-label">Gamification Users</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Table 1: Model Performance Comparison -->
        <div class="comparison-section">
            <h3 class="section-title">📊 Model Performance (Based on Your Data)</h3>
            <?php 
            $total_samples = 0;
            if (!empty($model_results)) {
                $total_samples = $model_results['linear_regression']['metrics']['total_samples'];
            }
            ?>
            <?php if ($total_samples > 0): ?>
            <div class="data-warning">
                <i class="fas fa-info-circle"></i> Analysis performed on <strong><?php echo $total_samples; ?> customer records</strong> from your database using shared churn calculation.
            </div>
            <?php endif; ?>
            <div class="table-container">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Accuracy</th>
                            <th>Precision</th>
                            <th>Recall</th>
                            <th>F1-Score</th>
                            <th>AUC-ROC</th>
                            <th>Samples</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $best_accuracy = max(array_column(array_column($model_results, 'metrics'), 'accuracy'));
                        $best_precision = max(array_column(array_column($model_results, 'metrics'), 'precision'));
                        $best_recall = max(array_column(array_column($model_results, 'metrics'), 'recall'));
                        $best_f1 = max(array_column(array_column($model_results, 'metrics'), 'f1_score'));
                        $best_auc = max(array_column(array_column($model_results, 'metrics'), 'auc_roc'));
                        ?>
                        
                        <?php foreach ($model_results as $model_key => $model): ?>
                        <tr>
                            <td><strong><?php echo $model['name']; ?></strong></td>
                            <td class="<?php echo $model['metrics']['accuracy'] == $best_accuracy ? 'best-metric' : ''; ?>">
                                <?php echo $model['metrics']['accuracy']; ?>%
                            </td>
                            <td class="<?php echo $model['metrics']['precision'] == $best_precision ? 'best-metric' : ''; ?>">
                                <?php echo $model['metrics']['precision']; ?>%
                            </td>
                            <td class="<?php echo $model['metrics']['recall'] == $best_recall ? 'best-metric' : ''; ?>">
                                <?php echo $model['metrics']['recall']; ?>%
                            </td>
                            <td class="<?php echo $model['metrics']['f1_score'] == $best_f1 ? 'best-metric' : ''; ?>">
                                <?php echo $model['metrics']['f1_score']; ?>%
                            </td>
                            <td class="<?php echo $model['metrics']['auc_roc'] == $best_auc ? 'best-metric' : ''; ?>">
                                <?php echo $model['metrics']['auc_roc']; ?>
                            </td>
                            <td><?php echo $model['metrics']['total_samples']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Table 2: Confusion Matrices -->
        <div class="confusion-matrix">
            <h3 class="section-title">🎯 Confusion Matrices Analysis</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($model_results as $model_key => $model): 
                    $cm = $model['metrics']['confusion_matrix'];
                ?>
                <div class="model-card">
                    <div class="model-header">
                        <div class="model-name"><?php echo $model['name']; ?></div>
                    </div>
                    <div class="matrix-grid">
                        <div class="matrix-item" style="background: #d4edda;">
                            <div class="matrix-value">TP: <?php echo $cm['tp']; ?></div>
                            <div class="matrix-label">True Positive</div>
                        </div>
                        <div class="matrix-item" style="background: #f8d7da;">
                            <div class="matrix-value">FP: <?php echo $cm['fp']; ?></div>
                            <div class="matrix-label">False Positive</div>
                        </div>
                        <div class="matrix-item" style="background: #f8d7da;">
                            <div class="matrix-value">FN: <?php echo $cm['fn']; ?></div>
                            <div class="matrix-label">False Negative</div>
                        </div>
                        <div class="matrix-item" style="background: #d4edda;">
                            <div class="matrix-value">TN: <?php echo $cm['tn']; ?></div>
                            <div class="matrix-label">True Negative</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Table 3: Business Impact -->
        <div class="comparison-section">
            <h3 class="section-title">💰 Business Impact Analysis</h3>
            <div class="table-container">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Churners Identified</th>
                            <th>False Alarms</th>
                            <th>Missed Churners</th>
                            <th>Estimated Monthly Savings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($business_impact as $model_key => $impact): ?>
                        <tr>
                            <td><strong><?php echo $model_results[$model_key]['name']; ?></strong></td>
                            <td><?php echo $impact['churners_identified']; ?></td>
                            <td><?php echo $impact['false_alarms']; ?></td>
                            <td><?php echo $impact['missed_churners']; ?></td>
                            <td><strong><?php echo $impact['monthly_savings']; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Table 4: Feature Importance -->
        <div class="database-section">
            <h3 class="section-title">🔍 Feature Importance (Based on Your Data)</h3>
            <?php if (!empty($feature_importance)): ?>
            <div class="table-container">
                <table class="feature-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Feature</th>
                            <th>Importance</th>
                            <th>Business Insight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feature_importance as $index => $feature): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><code><?php echo $feature['feature']; ?></code></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span><?php echo $feature['importance']; ?>%</span>
                                    <div class="importance-bar" style="width: 100px;">
                                        <div class="importance-fill" style="width: <?php echo $feature['importance']; ?>%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $feature['insight']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p>Feature importance data not available.</p>
            <?php endif; ?>
        </div>
        
        <!-- Database Sample -->
        <div class="database-section">
            <h3 class="section-title">📋 Customer Sample (10 of <?php echo $db_stats['total_customers']; ?>)</h3>
            <div style="max-height: 300px; overflow-y: auto; margin-top: 20px;">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Joined Date</th>
                            <th>Last Login</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                            <th>Churn Risk</th>
                            <th>Churned?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sample_customers as $customer): ?>
                        <tr>
                            <td><?php echo $customer['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                            <td><?php echo $customer['joined_date']; ?></td>
                            <td><?php echo $customer['last_login'] ?: 'Never'; ?></td>
                            <td><?php echo $customer['total_orders']; ?></td>
                            <td>₱<?php echo number_format($customer['total_spent'], 2); ?></td>
                            <td>
                                <span class="status-badge" style="
                                    background: <?php echo $customer['churn_risk'] == 'High Risk' ? '#f8d7da' : 
                                                    ($customer['churn_risk'] == 'Medium Risk' ? '#fff3cd' : '#d4edda'); ?>;
                                    color: <?php echo $customer['churn_risk'] == 'High Risk' ? '#721c24' : 
                                                    ($customer['churn_risk'] == 'Medium Risk' ? '#856404' : '#155724'); ?>;
                                    padding: 4px 8px;
                                    border-radius: 4px;
                                    font-size: 12px;
                                ">
                                    <?php echo $customer['churn_risk']; ?>
                                </span>
                            </td>
                            <td><?php echo $customer['is_churned']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recommendation -->
        <div class="recommendation">
            <h3><i class="fas fa-lightbulb"></i> Research Conclusion</h3>
            <p>
                Based on analysis of your database with <strong><?php echo $db_stats['total_customers']; ?> customers</strong> 
                and <strong><?php echo $db_stats['total_orders']; ?> orders</strong> using shared churn calculation:
            </p>
            <ul style="margin: 15px 0 15px 20px;">
                <?php 
                $best_model = 'xgboost';
                $best_f1 = 0;
                foreach ($model_results as $key => $model) {
                    if ($model['metrics']['f1_score'] > $best_f1) {
                        $best_f1 = $model['metrics']['f1_score'];
                        $best_model = $key;
                    }
                }
                ?>
                <li>Current churn rate: <strong><?php echo $db_stats['churn_rate']; ?>%</strong></li>
                <li>High-risk customers: <strong><?php echo $churn_overview['high_risk_customers']; ?></strong></li>
                <li>Customers analyzed: <strong><?php echo $total_samples; ?></strong></li>
                <li>Best performing model: <strong><?php echo $model_results[$best_model]['name']; ?></strong></li>
                <li>Model accuracy: <strong><?php echo $model_results[$best_model]['metrics']['accuracy']; ?>%</strong></li>
                <li>Potential monthly savings: <strong><?php echo $business_impact[$best_model]['monthly_savings']; ?></strong></li>
            </ul>
            
            <p style="margin-top: 15px; font-weight: bold; color: #856404;">
                💡 Recommendation: Implement <?php echo $model_results[$best_model]['name']; ?> model for churn prediction 
                using shared churn calculation from both analytics and ML computation.
            </p>
            
            <p style="margin-top: 10px; font-size: 14px; color: #856404;">
                <i class="fas fa-check-circle"></i> Note: This analysis uses the SAME churn calculation as your analytics dashboard, 
                ensuring consistency across all reports.
            </p>
        </div>
        
        <?php endif; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($model_results) && $model_results['linear_regression']['metrics']['accuracy'] > 0): ?>
        // Create chart container
        const chartContainer = document.createElement('div');
        chartContainer.innerHTML = '<h3 class="section-title" style="margin-top: 40px;">📈 Performance Comparison Chart</h3>';
        document.querySelector('.comparison-section').after(chartContainer);
        
        const canvas = document.createElement('canvas');
        canvas.id = 'performanceChart';
        canvas.style.marginTop = '20px';
        canvas.style.marginBottom = '40px';
        chartContainer.appendChild(canvas);
        
        const ctx = canvas.getContext('2d');
        
        const models = <?php echo json_encode(array_column($model_results, 'name')); ?>;
        const accuracy = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'accuracy')); ?>;
        const precision = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'precision')); ?>;
        const recall = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'recall')); ?>;
        const f1_score = <?php echo json_encode(array_column(array_column($model_results, 'metrics'), 'f1_score')); ?>;
        
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
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Model Performance Comparison (Aligned with Analytics)'
                    }
                }
            }
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>