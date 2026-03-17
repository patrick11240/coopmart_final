<?php
// /admin/export_dataset.php
session_start();
require_once '../include/config.php';
require_once '../include/churn_calculator.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit();
}

// Initialize churn calculator
$churnCalculator = new ChurnCalculator($pdo);

// Get all churn data
$churn_data = $churnCalculator->calculateChurnForAllCustomers();

if (empty($churn_data)) {
    $_SESSION['error_message'] = "No dataset available for export.";
    header("Location: computation.php");
    exit();
}

// Prepare headers for Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="churn_dataset_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Churn Prediction Dataset</title>
    <style>
        .excel-table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .excel-table th {
            background-color: #1a4d2e;
            color: white;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            position: sticky;
            top: 0;
        }
        .excel-table td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        .excel-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .churned-yes {
            background-color: #ffebee;
            color: #c62828;
            font-weight: bold;
        }
        .churned-no {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .risk-high { background-color: #ffebee; }
        .risk-medium { background-color: #fff3e0; }
        .risk-low { background-color: #e8f5e9; }
        .header-section {
            background-color: #1a4d2e;
            color: white;
            padding: 15px;
            margin-bottom: 10px;
        }
        .summary-box {
            background-color: #e3f2fd;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #bbdefb;
        }
    </style>
</head>
<body>
    <div class="header-section">
        <h1>Churn Prediction Dataset</h1>
        <h3>CoopMart Customer Analytics</h3>
        <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
        <p>Total Records: <?php echo count($churn_data); ?> customers</p>
    </div>
    
    <div class="summary-box">
        <h4>Dataset Summary</h4>
        <p>This dataset contains customer behavior features used for churn prediction.</p>
        <p>Features include: login activity, order history, points, cart behavior, and churn scores.</p>
    </div>
    
    <table class="excel-table">
        <thead>
            <tr>
                <th>Customer ID</th>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Join Date</th>
                <th>Last Login</th>
                <th>Days Since Login</th>
                <th>Last Order</th>
                <th>Days Since Order</th>
                <th>Total Orders</th>
                <th>Total Spent</th>
                <th>Avg Order Value</th>
                <th>Points</th>
                <th>Login Streak</th>
                <th>Abandoned Carts</th>
                <th>Churn Score</th>
                <th>Risk Level</th>
                <th>Churned (Actual)</th>
                <th>Churned (Predicted)</th>
                <th>Probability</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_spent = 0;
            $total_orders = 0;
            $churned_count = 0;
            
            foreach ($churn_data as $customer): 
                $total_spent += $customer['total_spent'] ?? 0;
                $total_orders += $customer['total_orders'] ?? 0;
                if (($customer['is_churned_actual'] ?? 0) == 1) {
                    $churned_count++;
                }
                
                // Calculate probabilities based on your existing logic
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
                
                // XGBoost prediction (as in your code)
                $xgb_score = (
                    ($features['churn_score'] * 0.45) +
                    ($features['days_since_login'] > 90 ? 0.25 : 0) +
                    ($features['total_orders'] == 0 ? 0.15 : -0.01 * $features['total_orders']) +
                    ($features['points'] < 50 ? 0.1 : 0) +
                    ($features['total_spent'] < 500 ? 0.05 : 0)
                );
                
                $probability = round($xgb_score * 100, 2);
                $predicted_churn = $xgb_score > 0.6 ? 1 : 0;
                
                // Determine risk level
                if ($customer['churn_score'] >= 70) {
                    $risk_level = 'High';
                    $risk_class = 'risk-high';
                } elseif ($customer['churn_score'] >= 40) {
                    $risk_level = 'Medium';
                    $risk_class = 'risk-medium';
                } else {
                    $risk_level = 'Low';
                    $risk_class = 'risk-low';
                }
            ?>
            <tr>
                <td><?php echo $customer['user_id']; ?></td>
                <td><?php echo htmlspecialchars($customer['full_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></td>
                <td><?php echo $customer['created_at'] ?? 'N/A'; ?></td>
                <td><?php echo $customer['last_login_date'] ?? 'Never'; ?></td>
                <td><?php echo $features['days_since_login']; ?></td>
                <td><?php echo $customer['last_order_date'] ?? 'Never'; ?></td>
                <td><?php echo $features['days_since_order']; ?></td>
                <td><?php echo $customer['total_orders'] ?? 0; ?></td>
                <td>₱<?php echo number_format($customer['total_spent'] ?? 0, 2); ?></td>
                <td>₱<?php echo number_format(($customer['total_orders'] > 0 ? ($customer['total_spent'] ?? 0) / $customer['total_orders'] : 0), 2); ?></td>
                <td><?php echo $customer['points'] ?? 0; ?></td>
                <td><?php echo $customer['login_streak'] ?? 0; ?></td>
                <td><?php echo $customer['abandoned_carts_count'] ?? 0; ?></td>
                <td><?php echo $customer['churn_score']; ?>%</td>
                <td class="<?php echo $risk_class; ?>"><?php echo $risk_level; ?></td>
                <td class="<?php echo ($customer['is_churned_actual'] ?? 0) == 1 ? 'churned-yes' : 'churned-no'; ?>">
                    <?php echo ($customer['is_churned_actual'] ?? 0) == 1 ? 'Yes' : 'No'; ?>
                </td>
                <td class="<?php echo $predicted_churn == 1 ? 'churned-yes' : 'churned-no'; ?>">
                    <?php echo $predicted_churn == 1 ? 'Yes' : 'No'; ?>
                </td>
                <td><?php echo $probability; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; padding: 15px; background-color: #f5f5f5;">
        <h4>Dataset Statistics</h4>
        <table style="width: 100%;">
            <tr>
                <td><strong>Total Customers:</strong></td>
                <td><?php echo count($churn_data); ?></td>
                <td><strong>Total Revenue:</strong></td>
                <td>₱<?php echo number_format($total_spent, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Churned Customers:</strong></td>
                <td><?php echo $churned_count; ?> (<?php echo round(($churned_count / count($churn_data)) * 100, 2); ?>%)</td>
                <td><strong>Average Orders per Customer:</strong></td>
                <td><?php echo count($churn_data) > 0 ? round($total_orders / count($churn_data), 2) : 0; ?></td>
            </tr>
            <tr>
                <td><strong>Average Churn Score:</strong></td>
                <td><?php 
                    $avg_score = array_sum(array_column($churn_data, 'churn_score')) / count($churn_data);
                    echo round($avg_score, 2); ?>%
                </td>
                <td><strong>Average Spent per Customer:</strong></td>
                <td>₱<?php echo count($churn_data) > 0 ? number_format($total_spent / count($churn_data), 2) : 0; ?></td>
            </tr>
        </table>
    </div>
    
    <div style="margin-top: 20px; font-size: 11px; color: #666; text-align: center;">
        <p>Dataset Fields Description:</p>
        <p>1. Days Since Login: Days since last login (365 max)</p>
        <p>2. Days Since Order: Days since last order (365 max)</p>
        <p>3. Churn Score: 0-100% probability based on behavior</p>
        <p>4. Churned (Actual): Based on 90+ days inactivity</p>
        <p>5. Probability: XGBoost model prediction score</p>
        <p>Generated by CoopMart Analytics System | Confidential</p>
    </div>
</body>
</html>