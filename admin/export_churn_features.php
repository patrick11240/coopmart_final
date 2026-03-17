<?php
// /admin/export_churn_features.php
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
    $_SESSION['error_message'] = "No customer data available for export.";
    header("Location: computation.php");
    exit();
}

// Prepare headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="churn_features_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Churn Feature Analysis</title>
    <style>
        /* Excel-friendly styling */
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }
        .excel-table {
            border-collapse: collapse;
            width: 100%;
            border: 1px solid #ccc;
        }
        .excel-table th {
            background-color: #2E7D32;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border: 1px solid #ccc;
        }
        .excel-table td {
            padding: 6px;
            border: 1px solid #ccc;
            vertical-align: middle;
        }
        .excel-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .excel-table tr:hover {
            background-color: #f5f5f5;
        }
        .header {
            background-color: #1a4d2e;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .feature-high {
            background-color: #ffebee;
            color: #c62828;
            font-weight: bold;
        }
        .feature-medium {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        .feature-low {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .summary {
            background-color: #e3f2fd;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #2196F3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Customer Churn Feature Analysis</h1>
        <h3>Export Date: <?php echo date('F j, Y, g:i a'); ?></h3>
        <p>Total Customers: <?php echo count($churn_data); ?></p>
    </div>
    
    <div class="summary">
        <h4>Feature Description:</h4>
        <p><strong>last_login_days:</strong> Days since last login (higher = higher churn risk)</p>
        <p><strong>order_recency:</strong> Days since last order (higher = higher churn risk)</p>
        <p><strong>engagement:</strong> Based on gamification activity (1-100 score, lower = better)</p>
        <p><strong>abandoned_carts:</strong> Count of abandoned shopping carts</p>
    </div>
    
    <table class="excel-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>last_login_days</th>
                <th>order_recency</th>
                <th>engagement</th>
                <th>abandoned_carts</th>
                <th>Churn Score</th>
                <th>Risk Level</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $index = 1;
            foreach ($churn_data as $customer): 
                // Calculate the specific features you want
                
                // 1. last_login_days
                $last_login_days = 0;
                if ($customer['last_login_date']) {
                    $last_login_days = floor((time() - strtotime($customer['last_login_date'])) / (60 * 60 * 24));
                } else {
                    $last_login_days = floor((time() - strtotime($customer['created_at'])) / (60 * 60 * 24));
                }
                
                // 2. order_recency
                $order_recency = 0;
                if ($customer['last_order_date']) {
                    $order_recency = floor((time() - strtotime($customer['last_order_date'])) / (60 * 60 * 24));
                } else {
                    $order_recency = floor((time() - strtotime($customer['created_at'])) / (60 * 60 * 24));
                }
                
                // 3. engagement (based on your churn calculator logic)
                $engagement = 100; // Default (worst)
                if ($customer['last_spin_date']) {
                    $days_since_spin = floor((time() - strtotime($customer['last_spin_date'])) / (60 * 60 * 24));
                    if ($days_since_spin > 30) {
                        $engagement = 50;
                    } elseif ($customer['daily_spins'] > 0) {
                        $engagement = 10; // Good engagement
                    } else {
                        $engagement = 30;
                    }
                }
                
                // 4. abandoned_carts
                $abandoned_carts = $customer['abandoned_carts_count'] ?? 0;
                
                // Determine feature risk level for styling
                $login_class = '';
                if ($last_login_days > 60) $login_class = 'feature-high';
                elseif ($last_login_days > 30) $login_class = 'feature-medium';
                else $login_class = 'feature-low';
                
                $order_class = '';
                if ($order_recency > 90) $order_class = 'feature-high';
                elseif ($order_recency > 60) $order_class = 'feature-medium';
                else $order_class = 'feature-low';
                
                $engagement_class = '';
                if ($engagement > 70) $engagement_class = 'feature-high';
                elseif ($engagement > 40) $engagement_class = 'feature-medium';
                else $engagement_class = 'feature-low';
                
                $cart_class = '';
                if ($abandoned_carts > 2) $cart_class = 'feature-high';
                elseif ($abandoned_carts > 0) $cart_class = 'feature-medium';
                else $cart_class = 'feature-low';
                
                $risk_class = '';
                $risk_level = $customer['risk_category'];
                if ($risk_level == 'high') $risk_class = 'feature-high';
                elseif ($risk_level == 'medium') $risk_class = 'feature-medium';
                else $risk_class = 'feature-low';
            ?>
            <tr>
                <td><?php echo $index++; ?></td>
                <td><?php echo $customer['user_id']; ?></td>
                <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                <td class="<?php echo $login_class; ?>"><?php echo $last_login_days; ?></td>
                <td class="<?php echo $order_class; ?>"><?php echo $order_recency; ?></td>
                <td class="<?php echo $engagement_class; ?>"><?php echo $engagement; ?></td>
                <td class="<?php echo $cart_class; ?>"><?php echo $abandoned_carts; ?></td>
                <td><?php echo $customer['churn_score']; ?>%</td>
                <td class="<?php echo $risk_class; ?>" style="text-transform: uppercase;">
                    <?php echo $risk_level; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-top: 2px solid #ccc;">
        <h3>Feature Statistics</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <th style="text-align: left; padding: 5px;">Feature</th>
                <th style="text-align: center; padding: 5px;">Average</th>
                <th style="text-align: center; padding: 5px;">Minimum</th>
                <th style="text-align: center; padding: 5px;">Maximum</th>
                <th style="text-align: center; padding: 5px;">Median</th>
            </tr>
            <?php
            // Calculate statistics
            $login_days = [];
            $order_recencies = [];
            $engagements = [];
            $carts = [];
            
            foreach ($churn_data as $customer) {
                // Calculate values
                $last_login_days = 0;
                if ($customer['last_login_date']) {
                    $last_login_days = floor((time() - strtotime($customer['last_login_date'])) / (60 * 60 * 24));
                }
                $login_days[] = $last_login_days;
                
                $order_recency = 0;
                if ($customer['last_order_date']) {
                    $order_recency = floor((time() - strtotime($customer['last_order_date'])) / (60 * 60 * 24));
                }
                $order_recencies[] = $order_recency;
                
                $engagement = 100;
                if ($customer['last_spin_date']) {
                    $days_since_spin = floor((time() - strtotime($customer['last_spin_date'])) / (60 * 60 * 24));
                    if ($days_since_spin > 30) {
                        $engagement = 50;
                    } elseif ($customer['daily_spins'] > 0) {
                        $engagement = 10;
                    } else {
                        $engagement = 30;
                    }
                }
                $engagements[] = $engagement;
                
                $carts[] = $customer['abandoned_carts_count'] ?? 0;
            }
            
            function calculateStats($array) {
                if (empty($array)) return ['avg' => 0, 'min' => 0, 'max' => 0, 'median' => 0];
                
                sort($array);
                $count = count($array);
                $avg = array_sum($array) / $count;
                $min = min($array);
                $max = max($array);
                $middle = floor($count / 2);
                $median = $count % 2 == 0 ? 
                    ($array[$middle - 1] + $array[$middle]) / 2 : 
                    $array[$middle];
                
                return [
                    'avg' => round($avg, 1),
                    'min' => $min,
                    'max' => $max,
                    'median' => round($median, 1)
                ];
            }
            
            $login_stats = calculateStats($login_days);
            $order_stats = calculateStats($order_recencies);
            $engagement_stats = calculateStats($engagements);
            $cart_stats = calculateStats($carts);
            ?>
            <tr>
                <td style="padding: 5px;"><strong>last_login_days</strong></td>
                <td style="text-align: center; padding: 5px;"><?php echo $login_stats['avg']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $login_stats['min']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $login_stats['max']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $login_stats['median']; ?></td>
            </tr>
            <tr>
                <td style="padding: 5px;"><strong>order_recency</strong></td>
                <td style="text-align: center; padding: 5px;"><?php echo $order_stats['avg']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $order_stats['min']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $order_stats['max']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $order_stats['median']; ?></td>
            </tr>
            <tr>
                <td style="padding: 5px;"><strong>engagement</strong></td>
                <td style="text-align: center; padding: 5px;"><?php echo $engagement_stats['avg']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $engagement_stats['min']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $engagement_stats['max']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $engagement_stats['median']; ?></td>
            </tr>
            <tr>
                <td style="padding: 5px;"><strong>abandoned_carts</strong></td>
                <td style="text-align: center; padding: 5px;"><?php echo $cart_stats['avg']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $cart_stats['min']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $cart_stats['max']; ?></td>
                <td style="text-align: center; padding: 5px;"><?php echo $cart_stats['median']; ?></td>
            </tr>
        </table>
    </div>
    
    <div style="margin-top: 20px; text-align: center; font-size: 10px; color: #666;">
        <p>Generated by CoopMart Churn Analysis System | <?php echo date('Y'); ?></p>
        <p>Note: Higher values for login_days, order_recency, and engagement indicate higher churn risk</p>
    </div>
</body>
</html>