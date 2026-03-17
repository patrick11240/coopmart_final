<?php
session_start();
require_once '../include/config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit();
}

$page_title = "Churn Probability Overview";
$success_message = '';
$error_message = '';

try {
    // Get churn overview statistics
    $churn_overview_query = "
        SELECT 
            COUNT(*) as total_customers,
            SUM(CASE WHEN risk_category = 'high' THEN 1 ELSE 0 END) as high_risk_customers,
            SUM(CASE WHEN risk_category = 'medium' THEN 1 ELSE 0 END) as medium_risk_customers,
            SUM(CASE WHEN risk_category = 'low' THEN 1 ELSE 0 END) as low_risk_customers,
            ROUND(AVG(churn_probability) * 100, 1) as avg_churn_rate
        FROM (
            SELECT 
                u.user_id,
                ROUND((
                    (CASE WHEN u.last_login_date IS NULL THEN 1.0 
                          WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 0.9 
                          WHEN DATEDIFF(CURDATE(), u.last_login_date) > 21 THEN 0.7 
                          WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 0.5 
                          WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 0.3 
                          ELSE 0.1 END * 0.30) +
                    (CASE WHEN o.last_order_date IS NULL THEN 0.8 
                          WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 0.9 
                          WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 0.7 
                          WHEN DATEDIFF(CURDATE(), o.last_order_date) > 14 THEN 0.4 
                          ELSE 0.1 END * 0.35) +
                    (CASE WHEN EXISTS (SELECT 1 FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id 
                                      WHERE c.user_id = u.user_id 
                                      AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
                                      AND NOT EXISTS (SELECT 1 FROM order_items oi 
                                                     JOIN orders ord ON oi.order_id = ord.order_id 
                                                     WHERE oi.product_id = ci.product_id 
                                                     AND ord.user_id = u.user_id 
                                                     AND ord.created_at > ci.added_at 
                                                     AND ord.status NOT IN ('canceled'))) 
                          THEN 0.6 ELSE 0.1 END * 0.20) +
                    (CASE WHEN u.last_spin_date IS NULL THEN 0.7 
                          WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 0.5 
                          WHEN u.daily_spins = 0 THEN 0.3 
                          ELSE 0.1 END * 0.15)
                ), 4) as churn_probability,
                
                CASE 
                    WHEN (
                        (CASE WHEN u.last_login_date IS NULL THEN 1.0 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 0.9 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 21 THEN 0.7 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 0.5 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 0.3 ELSE 0.1 END * 0.30) +
                        (CASE WHEN o.last_order_date IS NULL THEN 0.8 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 0.9 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 0.7 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 14 THEN 0.4 ELSE 0.1 END * 0.35) +
                        (CASE WHEN EXISTS (SELECT 1 FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id WHERE c.user_id = u.user_id AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOT EXISTS (SELECT 1 FROM order_items oi JOIN orders ord ON oi.order_id = ord.order_id WHERE oi.product_id = ci.product_id AND ord.user_id = u.user_id AND ord.created_at > ci.added_at AND ord.status NOT IN ('canceled'))) THEN 0.6 ELSE 0.1 END * 0.20) +
                        (CASE WHEN u.last_spin_date IS NULL THEN 0.7 WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 0.5 WHEN u.daily_spins = 0 THEN 0.3 ELSE 0.1 END * 0.15)
                    ) > 0.7 THEN 'high'
                    WHEN (
                        (CASE WHEN u.last_login_date IS NULL THEN 1.0 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 0.9 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 21 THEN 0.7 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 0.5 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 0.3 ELSE 0.1 END * 0.30) +
                        (CASE WHEN o.last_order_date IS NULL THEN 0.8 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 0.9 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 0.7 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 14 THEN 0.4 ELSE 0.1 END * 0.35) +
                        (CASE WHEN EXISTS (SELECT 1 FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id WHERE c.user_id = u.user_id AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOT EXISTS (SELECT 1 FROM order_items oi JOIN orders ord ON oi.order_id = ord.order_id WHERE oi.product_id = ci.product_id AND ord.user_id = u.user_id AND ord.created_at > ci.added_at AND ord.status NOT IN ('canceled'))) THEN 0.6 ELSE 0.1 END * 0.20) +
                        (CASE WHEN u.last_spin_date IS NULL THEN 0.7 WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 0.5 WHEN u.daily_spins = 0 THEN 0.3 ELSE 0.1 END * 0.15)
                    ) > 0.4 THEN 'medium'
                    ELSE 'low'
                END as risk_category

            FROM users u
            LEFT JOIN (
                SELECT user_id, MAX(created_at) as last_order_date 
                FROM orders 
                WHERE status NOT IN ('canceled') 
                GROUP BY user_id
            ) o ON u.user_id = o.user_id
            WHERE u.role = 'customer'
        ) as churn_data
    ";
    
    $churn_overview = $pdo->query($churn_overview_query)->fetch(PDO::FETCH_ASSOC);
    
    // Get churn factors analysis
    $churn_factors_query = "
        SELECT 
            risk_category,
            COUNT(*) as customer_count,
            ROUND(AVG(DATEDIFF(CURDATE(), COALESCE(last_login_date, created_at))), 1) as avg_days_since_login,
            ROUND(AVG(DATEDIFF(CURDATE(), COALESCE(last_order_date, created_at))), 1) as avg_days_since_order,
            ROUND(AVG(login_streak), 1) as avg_login_streak,
            ROUND(AVG(points), 1) as avg_points,
            ROUND(AVG(abandoned_carts_count), 1) as avg_abandoned_carts
        FROM (
            SELECT 
                u.user_id,
                u.last_login_date,
                u.created_at,
                u.login_streak,
                u.points,
                o.last_order_date,
                (SELECT COUNT(*) FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id 
                 WHERE c.user_id = u.user_id 
                 AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
                 AND NOT EXISTS (SELECT 1 FROM order_items oi 
                                JOIN orders ord ON oi.order_id = ord.order_id 
                                WHERE oi.product_id = ci.product_id 
                                AND ord.user_id = u.user_id 
                                AND ord.created_at > ci.added_at 
                                AND ord.status NOT IN ('canceled'))) as abandoned_carts_count,
                
                CASE 
                    WHEN (
                        (CASE WHEN u.last_login_date IS NULL THEN 1.0 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 0.9 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 21 THEN 0.7 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 0.5 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 0.3 ELSE 0.1 END * 0.30) +
                        (CASE WHEN o.last_order_date IS NULL THEN 0.8 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 0.9 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 0.7 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 14 THEN 0.4 ELSE 0.1 END * 0.35) +
                        (CASE WHEN EXISTS (SELECT 1 FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id WHERE c.user_id = u.user_id AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOT EXISTS (SELECT 1 FROM order_items oi JOIN orders ord ON oi.order_id = ord.order_id WHERE oi.product_id = ci.product_id AND ord.user_id = u.user_id AND ord.created_at > ci.added_at AND ord.status NOT IN ('canceled'))) THEN 0.6 ELSE 0.1 END * 0.20) +
                        (CASE WHEN u.last_spin_date IS NULL THEN 0.7 WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 0.5 WHEN u.daily_spins = 0 THEN 0.3 ELSE 0.1 END * 0.15)
                    ) > 0.7 THEN 'high'
                    WHEN (
                        (CASE WHEN u.last_login_date IS NULL THEN 1.0 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 0.9 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 21 THEN 0.7 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 0.5 WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 0.3 ELSE 0.1 END * 0.30) +
                        (CASE WHEN o.last_order_date IS NULL THEN 0.8 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 0.9 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 0.7 WHEN DATEDIFF(CURDATE(), o.last_order_date) > 14 THEN 0.4 ELSE 0.1 END * 0.35) +
                        (CASE WHEN EXISTS (SELECT 1 FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id WHERE c.user_id = u.user_id AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOT EXISTS (SELECT 1 FROM order_items oi JOIN orders ord ON oi.order_id = ord.order_id WHERE oi.product_id = ci.product_id AND ord.user_id = u.user_id AND ord.created_at > ci.added_at AND ord.status NOT IN ('canceled'))) THEN 0.6 ELSE 0.1 END * 0.20) +
                        (CASE WHEN u.last_spin_date IS NULL THEN 0.7 WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 0.5 WHEN u.daily_spins = 0 THEN 0.3 ELSE 0.1 END * 0.15)
                    ) > 0.4 THEN 'medium'
                    ELSE 'low'
                END as risk_category

            FROM users u
            LEFT JOIN (
                SELECT user_id, MAX(created_at) as last_order_date 
                FROM orders 
                WHERE status NOT IN ('canceled') 
                GROUP BY user_id
            ) o ON u.user_id = o.user_id
            WHERE u.role = 'customer'
        ) as churn_data
        GROUP BY risk_category
        ORDER BY 
            CASE risk_category 
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END
    ";
    
    $churn_factors = $pdo->query($churn_factors_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Get monthly churn trend
    $monthly_churn_query = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            CONCAT(MONTHNAME(created_at), ' ', YEAR(created_at)) as month_name,
            COUNT(*) as total_customers,
            SUM(CASE WHEN last_login_date IS NULL OR DATEDIFF(created_at, last_login_date) > 30 THEN 1 ELSE 0 END) as inactive_customers,
            ROUND((SUM(CASE WHEN last_login_date IS NULL OR DATEDIFF(created_at, last_login_date) > 30 THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 1) as churn_rate
        FROM users 
        WHERE role = 'customer' 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), MONTHNAME(created_at), YEAR(created_at)
        ORDER BY DATE_FORMAT(created_at, '%Y-%m') DESC
        LIMIT 6
    ";
    
    $monthly_churn = $pdo->query($monthly_churn_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top 10 high risk customers
    $high_risk_query = "
        SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.membership_type,
            ROUND((
                (CASE WHEN u.last_login_date IS NULL THEN 1.0 
                      WHEN DATEDIFF(CURDATE(), u.last_login_date) > 30 THEN 0.9 
                      WHEN DATEDIFF(CURDATE(), u.last_login_date) > 21 THEN 0.7 
                      WHEN DATEDIFF(CURDATE(), u.last_login_date) > 14 THEN 0.5 
                      WHEN DATEDIFF(CURDATE(), u.last_login_date) > 7 THEN 0.3 
                      ELSE 0.1 END * 0.30) +
                (CASE WHEN o.last_order_date IS NULL THEN 0.8 
                      WHEN DATEDIFF(CURDATE(), o.last_order_date) > 60 THEN 0.9 
                      WHEN DATEDIFF(CURDATE(), o.last_order_date) > 30 THEN 0.7 
                      WHEN DATEDIFF(CURDATE(), o.last_order_date) > 14 THEN 0.4 
                      ELSE 0.1 END * 0.35) +
                (CASE WHEN EXISTS (SELECT 1 FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id 
                                  WHERE c.user_id = u.user_id 
                                  AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
                                  AND NOT EXISTS (SELECT 1 FROM order_items oi 
                                                 JOIN orders ord ON oi.order_id = ord.order_id 
                                                 WHERE oi.product_id = ci.product_id 
                                                 AND ord.user_id = u.user_id 
                                                 AND ord.created_at > ci.added_at 
                                                 AND ord.status NOT IN ('canceled'))) 
                      THEN 0.6 ELSE 0.1 END * 0.20) +
                (CASE WHEN u.last_spin_date IS NULL THEN 0.7 
                      WHEN DATEDIFF(CURDATE(), u.last_spin_date) > 30 THEN 0.5 
                      WHEN u.daily_spins = 0 THEN 0.3 
                      ELSE 0.1 END * 0.15)
            ), 4) as churn_probability,
            
            u.last_login_date,
            o.last_order_date,
            o.total_orders,
            u.login_streak,
            u.points
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
        WHERE u.role = 'customer'
        HAVING churn_probability > 0.7
        ORDER BY churn_probability DESC
        LIMIT 10
    ";
    
    $high_risk_customers = $pdo->query($high_risk_query)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching churn data: " . $e->getMessage();
    $churn_overview = [];
    $churn_factors = [];
    $monthly_churn = [];
    $high_risk_customers = [];
}

// Calculate percentages
$total_customers = $churn_overview['total_customers'] ?? 1;
$high_risk_percentage = ($churn_overview['high_risk_customers'] / $total_customers) * 100;
$medium_risk_percentage = ($churn_overview['medium_risk_customers'] / $total_customers) * 100;
$low_risk_percentage = ($churn_overview['low_risk_customers'] / $total_customers) * 100;

// Determine churn health
if ($high_risk_percentage > 20) {
    $churn_health = 'critical';
    $health_color = '#dc3545';
    $health_icon = '🔴';
    $health_message = 'Immediate action required - High churn risk detected';
} elseif ($high_risk_percentage > 10) {
    $churn_health = 'warning';
    $health_color = '#ffc107';
    $health_icon = '🟡';
    $health_message = 'Monitor closely - Elevated churn risk';
} else {
    $churn_health = 'healthy';
    $health_color = '#28a745';
    $health_icon = '🟢';
    $health_message = 'Good standing - Normal churn levels';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CoopMart Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --light-green: #e8f5e9;
            --medium-green: #4a7c2c;
            --dark-green: #2d5016;
            --text-dark: #2d3748;
            --text-light: #718096;
            --light-bg: #f8f9fa;
            --border-color: #e2e8f0;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;
            --info: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border-left: 5px solid var(--medium-green);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-content h1 {
            color: var(--dark-green);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-content h1 i {
            color: var(--medium-green);
            font-size: 32px;
        }
        
        .header-content p {
            color: var(--text-light);
            font-size: 15px;
        }
        
        .health-status {
            background: <?php echo $health_color; ?>15;
            border: 1px solid <?php echo $health_color; ?>30;
            padding: 15px 25px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .health-icon {
            font-size: 32px;
        }
        
        .health-text h3 {
            color: <?php echo $health_color; ?>;
            margin-bottom: 5px;
        }
        
        .health-text p {
            color: var(--text-light);
            font-size: 14px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-trend {
            font-size: 12px;
            margin-top: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .trend-up {
            background: #d4edda;
            color: #155724;
        }
        
        .trend-down {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            color: var(--dark-green);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        /* Tables */
        .tables-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .table-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .table-title {
            font-size: 18px;
            color: var(--dark-green);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: var(--light-green);
        }
        
        .data-table th {
            padding: 15px 20px;
            text-align: left;
            color: var(--dark-green);
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
        }
        
        .data-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background 0.3s;
        }
        
        .data-table tbody tr:hover {
            background: var(--light-bg);
        }
        
        .data-table td {
            padding: 15px 20px;
            color: var(--text-dark);
        }
        
        .risk-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .risk-high {
            background: #f8d7da;
            color: var(--danger);
        }
        
        .risk-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .risk-low {
            background: #d4edda;
            color: var(--success);
        }
        
        .churn-score {
            font-weight: bold;
            font-size: 16px;
        }
        
        .churn-score.high {
            color: var(--danger);
        }
        
        .churn-score.medium {
            color: #856404;
        }
        
        .churn-score.low {
            color: var(--success);
        }
        
        /* Progress Bars */
        .progress-container {
            width: 100%;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            height: 10px;
        }
        
        .progress-bar {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-high {
            background: var(--danger);
        }
        
        .progress-medium {
            background: var(--warning);
        }
        
        .progress-low {
            background: var(--success);
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--medium-green);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--dark-green);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        /* Actions Bar */
        .actions-bar {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 20px;
            }
            
            .health-status {
                width: 100%;
            }
            
            .charts-container,
            .tables-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card,
            .table-card {
                padding: 15px;
            }
            
            .actions-bar {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .data-table th,
            .data-table td {
                padding: 12px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1>
                    <i class="fas fa-chart-line"></i>
                    Customer Churn Probability Dashboard
                </h1>
                <p>Monitor customer retention risks with comprehensive analytics</p>
            </div>
            
            <div class="health-status">
                <div class="health-icon">
                    <?php echo $health_icon; ?>
                </div>
                <div class="health-text">
                    <h3><?php echo ucfirst($churn_health); ?> Status</h3>
                    <p><?php echo $health_message; ?></p>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Actions Bar -->
        <div class="actions-bar">
            <div>
                <a href="analytics.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Analytics
                </a>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="churn_probability_table.php" class="btn btn-primary">
                    <i class="fas fa-table"></i> View Detailed Table
                </a>
                <button class="btn btn-danger" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> Export Report
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--info);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo number_format($churn_overview['total_customers'] ?? 0); ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($churn_overview['high_risk_customers'] ?? 0); ?></div>
                <div class="stat-label">High Risk Customers</div>
                <div class="stat-trend trend-up">
                    <?php echo number_format($high_risk_percentage, 1); ?>% of total
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--warning);">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($churn_overview['medium_risk_customers'] ?? 0); ?></div>
                <div class="stat-label">Medium Risk Customers</div>
                <div class="stat-trend">
                    <?php echo number_format($medium_risk_percentage, 1); ?>% of total
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($churn_overview['low_risk_customers'] ?? 0); ?></div>
                <div class="stat-label">Low Risk Customers</div>
                <div class="stat-trend trend-down">
                    <?php echo number_format($low_risk_percentage, 1); ?>% of total
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--dark-green);">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-number"><?php echo number_format($churn_overview['avg_churn_rate'] ?? 0, 1); ?>%</div>
                <div class="stat-label">Average Churn Rate</div>
                <div class="stat-trend">
                    <?php echo $churn_health == 'critical' ? 'High Risk' : ($churn_health == 'warning' ? 'Elevated' : 'Normal'); ?>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-container">
            <!-- Pie Chart: Risk Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Customer Risk Distribution
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="riskDistributionChart"></canvas>
                </div>
            </div>
            
            <!-- Line Chart: Monthly Churn Trend -->
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Monthly Churn Trend (Last 6 Months)
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyChurnChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tables Section -->
        <div class="tables-container">
            <!-- Table 1: Risk Factors Analysis -->
            <div class="table-card">
                <div class="table-title">
                    <i class="fas fa-chart-bar"></i>
                    Risk Category Analysis
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Risk Category</th>
                                <th>Customers</th>
                                <th>Percentage</th>
                                <th>Avg Days Since Login</th>
                                <th>Avg Days Since Order</th>
                                <th>Avg Login Streak</th>
                                <th>Avg Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($churn_factors as $factor): 
                                $percentage = ($factor['customer_count'] / $total_customers) * 100;
                            ?>
                            <tr>
                                <td>
                                    <span class="risk-badge risk-<?php echo $factor['risk_category']; ?>">
                                        <?php echo ucfirst($factor['risk_category']); ?> Risk
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($factor['customer_count']); ?></strong></td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-<?php echo $factor['risk_category']; ?>" 
                                             style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <div style="font-size: 12px; margin-top: 5px;">
                                        <?php echo number_format($percentage, 1); ?>%
                                    </div>
                                </td>
                                <td><?php echo $factor['avg_days_since_login']; ?> days</td>
                                <td><?php echo $factor['avg_days_since_order']; ?> days</td>
                                <td><?php echo $factor['avg_login_streak']; ?></td>
                                <td><?php echo number_format($factor['avg_points'], 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Table 2: Top High-Risk Customers -->
            <div class="table-card">
                <div class="table-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Top 10 High-Risk Customers (Requiring Immediate Attention)
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Churn Probability</th>
                                <th>Last Login</th>
                                <th>Last Order</th>
                                <th>Total Orders</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($high_risk_customers)): ?>
                                <?php foreach ($high_risk_customers as $customer): 
                                    $churn_percentage = $customer['churn_probability'] * 100;
                                    $days_since_login = $customer['last_login_date'] ? 
                                        floor((time() - strtotime($customer['last_login_date'])) / (60 * 60 * 24)) : 'Never';
                                    $days_since_order = $customer['last_order_date'] ? 
                                        floor((time() - strtotime($customer['last_order_date'])) / (60 * 60 * 24)) : 'No orders';
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                                        <div style="font-size: 13px; color: var(--text-light);"><?php echo htmlspecialchars($customer['email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="churn-score high">
                                            <?php echo number_format($churn_percentage, 1); ?>%
                                        </div>
                                        <div class="progress-container" style="margin-top: 8px;">
                                            <div class="progress-bar progress-high" 
                                                 style="width: <?php echo $churn_percentage; ?>%;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_login_date']): ?>
                                            <?php echo date('M j', strtotime($customer['last_login_date'])); ?>
                                            <div style="font-size: 12px; color: var(--danger);">
                                                (<?php echo $days_since_login; ?> days ago)
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--danger);">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_order_date']): ?>
                                            <?php echo date('M j', strtotime($customer['last_order_date'])); ?>
                                            <div style="font-size: 12px; color: var(--warning);">
                                                (<?php echo $days_since_order; ?> days ago)
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--danger);">No orders</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $customer['total_orders'] ?? 0; ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-small" 
                                                onclick="sendRetentionOffer(<?php echo $customer['user_id']; ?>)"
                                                style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fas fa-bullhorn"></i> Send Offer
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-light);">
                                        <i class="fas fa-check-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                                        <div>No high-risk customers found!</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Risk Distribution Pie Chart
    const riskCtx = document.getElementById('riskDistributionChart').getContext('2d');
    const riskDistributionChart = new Chart(riskCtx, {
        type: 'doughnut',
        data: {
            labels: ['High Risk', 'Medium Risk', 'Low Risk'],
            datasets: [{
                data: [
                    <?php echo $churn_overview['high_risk_customers'] ?? 0; ?>,
                    <?php echo $churn_overview['medium_risk_customers'] ?? 0; ?>,
                    <?php echo $churn_overview['low_risk_customers'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#dc3545',
                    '#ffc107',
                    '#28a745'
                ],
                borderColor: [
                    '#c82333',
                    '#e0a800',
                    '#218838'
                ],
                borderWidth: 2,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            const total = <?php echo $total_customers; ?>;
                            const value = context.raw;
                            const percentage = Math.round((value / total) * 100);
                            label += value + ' customers (' + percentage + '%)';
                            return label;
                        }
                    }
                }
            }
        }
    });

    // Monthly Churn Line Chart
    const monthlyCtx = document.getElementById('monthlyChurnChart').getContext('2d');
    const monthlyChurnChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: [
                <?php foreach (array_reverse($monthly_churn) as $month): ?>
                    "<?php echo $month['month_name']; ?>",
                <?php endforeach; ?>
            ],
            datasets: [
                {
                    label: 'Total Customers',
                    data: [
                        <?php foreach (array_reverse($monthly_churn) as $month): ?>
                            <?php echo $month['total_customers']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: '#4a7c2c',
                    backgroundColor: 'rgba(74, 124, 44, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Churn Rate (%)',
                    data: [
                        <?php foreach (array_reverse($monthly_churn) as $month): ?>
                            <?php echo $month['churn_rate']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            stacked: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Total Customers'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Churn Rate (%)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    min: 0,
                    max: 100
                }
            }
        }
    });

    // Send Retention Offer
    function sendRetentionOffer(userId) {
        if (confirm('Send an urgent retention offer to this high-risk customer?')) {
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            fetch('send_retention_offer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    user_id: userId,
                    risk_level: 'high' 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ Retention offer sent successfully!');
                    button.innerHTML = '<i class="fas fa-check"></i> Sent';
                    button.style.background = '#28a745';
                    button.disabled = true;
                    
                    // Refresh page after 2 seconds to update data
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('✗ Error: ' + data.message);
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                alert('✗ Network error: ' + error);
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    }

    // Export to PDF
    function exportToPDF() {
        // You can implement PDF export using jsPDF or link to a server-side PDF generator
        alert('PDF export would be implemented here. For now, use browser print (Ctrl+P)');
        window.print();
    }

    // Auto-refresh dashboard every 5 minutes
    setTimeout(() => {
        if (confirm('Refresh dashboard to get latest data?')) {
            window.location.reload();
        }
    }, 300000); // 5 minutes

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // R to refresh
        if (e.key === 'r' && e.ctrlKey) {
            e.preventDefault();
            window.location.reload();
        }
        // P to print/export
        if (e.key === 'p' && e.ctrlKey) {
            e.preventDefault();
            exportToPDF();
        }
    });

    // Add tooltips
    document.querySelectorAll('.stat-card').forEach(card => {
        card.title = 'Click to view details';
        card.style.cursor = 'pointer';
        card.addEventListener('click', function() {
            window.location.href = 'churn_probability_table.php';
        });
    });
    </script>
</body>
</html>