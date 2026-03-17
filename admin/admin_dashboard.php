<?php
session_start();
require_once '../include/config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Get comprehensive dashboard statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    
    // Users by membership type
    $stmt = $pdo->query("SELECT membership_type, COUNT(*) as count FROM users GROUP BY membership_type");
    $membership_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total spin discounts
    $stmt = $pdo->query("SELECT COUNT(*) as total_discounts FROM spin_discounts");
    $total_discounts = $stmt->fetch(PDO::FETCH_ASSOC)['total_discounts'];
    
    // Used vs unused discounts
    $stmt = $pdo->query("SELECT is_used, COUNT(*) as count FROM spin_discounts GROUP BY is_used");
    $discount_usage = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent users (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as recent_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_users = $stmt->fetch(PDO::FETCH_ASSOC)['recent_users'];
    
    // Active users (logged in within last 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) as active_users FROM users WHERE last_login_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];

    // NEW STATISTICS - Fixed queries based on your actual database schema
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
    
    // Total revenue (only completed orders)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total_revenue FROM orders WHERE status = 'completed'");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
    
    // Recent orders (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as recent_orders FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_orders = $stmt->fetch(PDO::FETCH_ASSOC)['recent_orders'];
    
    // Orders by status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $order_status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top products (by order count) - Fixed query
    $stmt = $pdo->query("
        SELECT p.name, COUNT(oi.order_item_id) as order_count 
        FROM products p 
        LEFT JOIN order_items oi ON p.product_id = oi.product_id 
        GROUP BY p.product_id, p.name
        ORDER BY order_count DESC 
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly revenue (last 6 months) - Fixed query
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COALESCE(SUM(total_price), 0) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        AND status = 'completed'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $monthly_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User growth (last 30 days) - Fixed query
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as new_users
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $user_growth = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error fetching dashboard data: " . $e->getMessage();
}

// Get recent users for table - Fixed and safer query
try {
    $stmt = $pdo->query("
        SELECT 
            u.user_id, 
            u.full_name, 
            u.email, 
            u.membership_type, 
            u.role, 
            u.created_at, 
            u.last_login_date, 
            u.points, 
            u.login_streak,
            COUNT(DISTINCT o.order_id) as total_orders,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_price ELSE 0 END), 0) as total_spent,
            COALESCE(COUNT(sd.id), 0) as total_discounts,
            COALESCE(SUM(CASE WHEN sd.is_used = 0 THEN 1 ELSE 0 END), 0) as unused_discounts
        FROM users u
        LEFT JOIN orders o ON u.user_id = o.user_id
        LEFT JOIN spin_discounts sd ON u.user_id = sd.user_id
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $recent_users_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_users_data = [];
    $error_message = "Error fetching users: " . $e->getMessage();
    error_log("Dashboard User Query Error: " . $e->getMessage());
}

// Get recent orders for table - Fixed query
try {
    $stmt = $pdo->query("
        SELECT 
            o.order_id,
            o.total_price,
            o.status,
            o.created_at,
            u.full_name,
            u.email,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $recent_orders_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_orders_data = [];
    $error_message = "Error fetching orders: " . $e->getMessage();
}

// Handle user actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_user':
                try {
                    $user_id = $_POST['user_id'];
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Delete related data in correct order to handle foreign key constraints
                    $stmt = $pdo->prepare("DELETE FROM spin_discounts WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete order items first
                    $stmt = $pdo->prepare("DELETE oi FROM order_items oi INNER JOIN orders o ON oi.order_id = o.order_id WHERE o.user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete orders
                    $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete cart items
                    $stmt = $pdo->prepare("DELETE ci FROM cart_items ci INNER JOIN carts c ON ci.cart_id = c.cart_id WHERE c.user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete cart
                    $stmt = $pdo->prepare("DELETE FROM carts WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete user discounts
                    $stmt = $pdo->prepare("DELETE FROM user_discounts WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete retention offers
                    $stmt = $pdo->prepare("DELETE FROM retention_offers WHERE user_id = ? OR created_by = ?");
                    $stmt->execute([$user_id, $user_id]);
                    
                    // Finally delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    $pdo->commit();
                    $success_message = "User and all related data deleted successfully!";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error_message = "Error deleting user: " . $e->getMessage();
                }
                break;
                
            case 'change_role':
                try {
                    $user_id = $_POST['user_id'];
                    $new_role = $_POST['new_role'];
                    
                    // Prevent admin from removing their own admin privileges
                    if ($user_id == $_SESSION['user_id'] && $new_role !== 'admin') {
                        $error_message = "You cannot remove your own admin privileges!";
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                        $stmt->execute([$new_role, $user_id]);
                        $success_message = "User role updated successfully!";
                    }
                } catch (PDOException $e) {
                    $error_message = "Error updating user role: " . $e->getMessage();
                }
                break;
                
            case 'reset_points':
                try {
                    $user_id = $_POST['user_id'];
                    
                    $stmt = $pdo->prepare("UPDATE users SET points = 0, login_streak = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    $success_message = "User points and streak reset successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error resetting user data: " . $e->getMessage();
                }
                break;
                
            case 'update_order_status':
                try {
                    $order_id = $_POST['order_id'];
                    $new_status = $_POST['new_status'];
                    
                    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                    $stmt->execute([$new_status, $order_id]);
                    
                    $success_message = "Order status updated successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error updating order status: " . $e->getMessage();
                }
                break;
        }
        
        // Refresh page to show updated data
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Coopamart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --dark-green: #1a4d2e;
            --medium-green: #3a754f;
            --light-green: #4caf50;
            --accent-green: #66bb6a;
            --light-bg: #f7f9fc;
            --white: #ffffff;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #e0e0e0;
            --shadow: rgba(0, 0, 0, 0.1);
            --delete-red: #d9534f;
            --edit-blue: #5cb85c;
            --warning-orange: #f39c12;
            --info-blue: #17a2b8;
            --purple: #6f42c1;
            --teal: #20c997;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            line-height: 1.6;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--medium-green) 100%);
            color: var(--white);
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            z-index: 1000;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            color: var(--white);
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .sidebar a {
            color: var(--white);
            text-decoration: none;
            padding: 15px 20px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .sidebar a:hover {
            background-color: rgba(255,255,255,0.15);
            transform: translateX(5px);
            opacity: 1;
        }

        .sidebar a.active {
            background-color: rgba(255,255,255,0.2);
            border-left: 4px solid var(--accent-green);
            opacity: 1;
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            margin-left: 280px;
            width: calc(100% - 280px);
            transition: all 0.3s ease;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--dark-green), var(--medium-green));
            color: var(--white);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(60px, -60px);
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dashboard-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-left: 4px solid var(--light-green);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: rgba(102, 187, 106, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-card h3 {
            color: var(--text-light);
            margin: 0 0 15px 0;
            font-size: 0.95rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            color: var(--dark-green);
            font-size: 2.8rem;
            font-weight: 800;
            margin: 0;
            line-height: 1;
        }

        .stat-card .description {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-card .trend {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: auto;
        }

        .trend-up { background: #d4edda; color: #155724; }
        .trend-down { background: #f8d7da; color: #721c24; }

        .content-section {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .content-section:hover {
            transform: translateY(-2px);
        }

        .section-header {
            background: linear-gradient(135deg, var(--dark-green), var(--medium-green));
            color: var(--white);
            padding: 20px 25px;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .section-header i {
            font-size: 1.1rem;
        }

        .section-content {
            padding: 25px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            min-width: 800px;
        }

        th, td {
            text-align: left;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--light-bg);
            color: var(--text-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
        }

        td {
            color: var(--text-dark);
        }

        tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .badge-regular { background: #e3f2fd; color: #1976d2; }
        .badge-sidc_member { background: #e8f5e8; color: var(--light-green); }
        .badge-non_member { background: #fff3e0; color: var(--warning-orange); }
        .badge-admin { background: #ffebee; color: var(--delete-red); }
        .badge-customer { background: #f3e5f5; color: #7b1fa2; }

        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending_payment { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1ecf1; color: #0c5460; }
        .status-processing_purchased_product { background: #f8d7da; color: #721c24; }
        .status-ready_to_pick_the_purchased_product { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-canceled { background: #f8d7da; color: #721c24; }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-danger { background: linear-gradient(135deg, var(--delete-red), #c9302c); color: var(--white); }
        .btn-warning { background: linear-gradient(135deg, var(--warning-orange), #e68900); color: var(--white); }
        .btn-info { background: linear-gradient(135deg, var(--info-blue), #138496); color: var(--white); }
        .btn-success { background: linear-gradient(135deg, var(--light-green), #45a049); color: var(--white); }
        .btn-primary { background: linear-gradient(135deg, #007bff, #0056b3); color: var(--white); }
        .btn-purple { background: linear-gradient(135deg, var(--purple), #5a3596); color: var(--white); }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4ffd4, #a8e6a8);
            color: #155724;
            border-left: 4px solid var(--light-green);
        }

        .alert-error {
            background: linear-gradient(135deg, #ffd4d4, #e6a8a8);
            color: #721c24;
            border-left: 4px solid var(--delete-red);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            color: var(--text-dark);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chart-placeholder {
            height: 250px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-style: italic;
        }

        .membership-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .membership-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            transition: transform 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .membership-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .membership-item .count {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-green);
            margin-bottom: 8px;
        }

        .membership-item .label {
            font-size: 0.9rem;
            color: var(--text-light);
            text-transform: capitalize;
            font-weight: 600;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .quick-action-btn {
            background: linear-gradient(135deg, var(--light-green), var(--accent-green));
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .quick-action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .quick-action-btn i {
            font-size: 2rem;
        }

        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            background: linear-gradient(135deg, var(--dark-green), var(--medium-green));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 18px;
            cursor: pointer;
            z-index: 1001;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .mobile-toggle:hover {
            transform: scale(1.05);
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            backdrop-filter: blur(5px);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 70px 15px 15px;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .overlay.active {
                display: block;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .dashboard-header {
                padding: 20px;
                margin-top: 10px;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .section-content {
                padding: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn {
                padding: 10px 12px;
                font-size: 0.75rem;
                justify-content: center;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 12px 10px;
            }
            
            .stat-card .number {
                font-size: 2.2rem;
            }
            
            .membership-breakdown {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 15px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 250px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-card .number {
                font-size: 2rem;
            }
            
            .membership-breakdown {
                grid-template-columns: 1fr;
            }
            
            .membership-item {
                padding: 15px;
            }
            
            .membership-item .count {
                font-size: 1.8rem;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px 8px;
            }
            
            .badge, .status-badge {
                font-size: 0.7rem;
                padding: 4px 8px;
            }
            
            .chart-container {
                padding: 15px;
            }
            
            .chart-placeholder {
                height: 200px;
            }
        }

        /* Animation for stat cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card, .content-section {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Print Styles */
        @media print {
            .sidebar, .mobile-toggle, .action-buttons {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .stat-card, .content-section {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Status Update Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--dark-green), var(--medium-green));
            color: var(--white);
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }

        .close {
            color: var(--white);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--accent-green);
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="overlay" id="overlay"></div>
    
    <div class="sidebar" id="sidebar">
        <h2><i class="fas fa-cogs"></i> Admin Panel</h2>
        <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
        <a href="add_product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
        <a href="add_category.php"><i class="fas fa-tags"></i> Add Category</a>
        <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="../auth/admin_logout.php" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <p>Welcome back! Here's an overview of your platform's performance and activity.</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Main Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo number_format($total_users); ?></div>
                <div class="description">
                    <i class="fas fa-users"></i> Registered users
                    <span class="trend trend-up">+<?php echo $recent_users; ?> new</span>
                </div>
            </div>

            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="number">₱<?php echo number_format($total_revenue, 2); ?></div>
                <div class="description">
                    <i class="fas fa-money-bill-wave"></i> Total sales
                </div>
            </div>

            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo number_format($total_orders); ?></div>
                <div class="description">
                    <i class="fas fa-shopping-cart"></i> All-time orders
                    <span class="trend trend-up">+<?php echo $recent_orders; ?> recent</span>
                </div>
            </div>

            <div class="stat-card">
                <h3>Active Users</h3>
                <div class="number"><?php echo number_format($active_users); ?></div>
                <div class="description">
                    <i class="fas fa-user-check"></i> Last 30 days
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Membership Distribution</h3>
                </div>
                <div style="height: 300px;">
                    <canvas id="membershipChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Revenue Trend (6 Months)</h3>
                </div>
                <div style="height: 300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Membership Breakdown -->
        <div class="content-section">
            <div class="section-header">
                <span><i class="fas fa-users"></i> Membership Breakdown</span>
            </div>
            <div class="section-content">
                <div class="membership-breakdown">
                    <?php foreach ($membership_stats as $stat): ?>
                        <div class="membership-item">
                            <div class="count"><?php echo number_format($stat['count']); ?></div>
                            <div class="label"><?php echo str_replace('_', ' ', $stat['membership_type']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Discount Usage Statistics -->
        <div class="content-section">
            <div class="section-header">
                <span><i class="fas fa-tags"></i> Discount Usage Statistics</span>
            </div>
            <div class="section-content">
                <div class="membership-breakdown">
                    <?php 
                    $used = 0; $unused = 0;
                    foreach ($discount_usage as $usage) {
                        if ($usage['is_used'] == 1) $used = $usage['count'];
                        else $unused = $usage['count'];
                    }
                    $usage_rate = $total_discounts > 0 ? round(($used / $total_discounts) * 100, 1) : 0;
                    ?>
                    <div class="membership-item">
                        <div class="count"><?php echo number_format($used); ?></div>
                        <div class="label">Used Discounts</div>
                    </div>
                    <div class="membership-item">
                        <div class="count"><?php echo number_format($unused); ?></div>
                        <div class="label">Unused Discounts</div>
                    </div>
                    <div class="membership-item">
                        <div class="count"><?php echo $usage_rate; ?>%</div>
                        <div class="label">Usage Rate</div>
                    </div>
                    <div class="membership-item">
                        <div class="count"><?php echo number_format($total_discounts); ?></div>
                        <div class="label">Total Discounts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Status Overview -->
        <div class="content-section">
            <div class="section-header">
                <span><i class="fas fa-shopping-bag"></i> Order Status Overview</span>
            </div>
            <div class="section-content">
                <div class="membership-breakdown">
                    <?php 
                    $status_counts = [];
                    foreach ($order_status_stats as $stat) {
                        $status_counts[$stat['status']] = $stat['count'];
                    }
                    
                    $statuses = [
                        'pending_payment' => 'Pending Payment',
                        'paid' => 'Paid',
                        'processing_purchased_product' => 'Processing',
                        'ready_to_pick_the_purchased_product' => 'Ready to Pick',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled'
                    ];
                    
                    foreach ($statuses as $status => $label): 
                        $count = $status_counts[$status] ?? 0;
                        $percentage = $total_orders > 0 ? round(($count / $total_orders) * 100, 1) : 0;
                    ?>
                        <div class="membership-item">
                            <div class="count"><?php echo number_format($count); ?></div>
                            <div class="label"><?php echo $label; ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-light); margin-top: 5px;">
                                <?php echo $percentage; ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

      <!-- Recent Users Table -->
<div class="content-section">
    <div class="section-header">
        <span><i class="fas fa-user-clock"></i> Recent Users</span>
        <a href="user_management.php" class="btn btn-primary" style="font-size: 0.8rem;">
            <i class="fas fa-external-link-alt"></i> View All
        </a>
    </div>
    <div class="section-content">
        <div class="table-container">
            <?php if (!empty($recent_users_data)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Membership</th>
                            <th>Role</th>
                            <th>Orders</th>
                            <th>Spent</th>
                            <th>Points</th>
                            <th>Streak</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users_data as $user): ?>
                            <tr>
                                <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['membership_type'] ?? 'regular'; ?>">
                                        <?php echo str_replace('_', ' ', $user['membership_type'] ?? 'regular'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] ?? 'customer'; ?>">
                                        <?php echo $user['role'] ?? 'customer'; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($user['total_orders'] ?? 0); ?></td>
                                <td>₱<?php echo number_format($user['total_spent'] ?? 0, 2); ?></td>
                                <td><?php echo number_format($user['points'] ?? 0); ?></td>
                                <td><?php echo $user['login_streak'] ?? 0; ?> days</td>
                                <td><?php echo isset($user['last_login_date']) && $user['last_login_date'] ? date('M j, Y', strtotime($user['last_login_date'])) : 'Never'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to change this user\\'s role?');">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="new_role" value="<?php echo ($user['role'] ?? 'customer') === 'admin' ? 'customer' : 'admin'; ?>">
                                            <button type="submit" class="btn btn-warning" title="Toggle Role">
                                                <i class="fas fa-user-cog"></i>
                                                <?php echo ($user['role'] ?? 'customer') === 'admin' ? 'Customer' : 'Admin'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to reset this user\\'s points and streak?');">
                                            <input type="hidden" name="action" value="reset_points">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="btn btn-info" title="Reset Points & Streak">
                                                <i class="fas fa-redo"></i> Reset
                                            </button>
                                        </form>
                                        
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone!');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-danger" title="Delete User">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data" style="text-align: center; padding: 40px; color: var(--text-light);">
                    <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                    <p>No users found or there was an error loading user data.</p>
                    <?php if (!empty($error_message)): ?>
                        <p style="font-size: 0.9rem; margin-top: 10px; color: var(--delete-red);">
                            Error: <?php echo htmlspecialchars($error_message); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

        <!-- Recent Orders Table -->
        <div class="content-section">
            <div class="section-header">
                <span><i class="fas fa-shopping-cart"></i> Recent Orders</span>
                <a href="user_management.php?tab=orders" class="btn btn-primary" style="font-size: 0.8rem;">
                    <i class="fas fa-external-link-alt"></i> View All
                </a>
            </div>
            <div class="section-content">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders_data as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['full_name']); ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--text-light);">
                                                <?php echo htmlspecialchars($order['email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $order['item_count']; ?> items</td>
                                    <td><strong>₱<?php echo number_format($order['total_price'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $order['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="openOrderStatusModal(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')" 
                                                    class="btn btn-warning">
                                                <i class="fas fa-edit"></i> Status
                                            </button>
                                            <a href="user_management.php?view_order=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

            <!-- Top Products -->
        <div class="content-section">
            <div class="section-header">
                <span><i class="fas fa-star"></i> Top Products</span>
            </div>
            <div class="section-content">
                <?php if (!empty($top_products)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Orders Count</th>
                                    <th>Popularity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Calculate max order count safely
                                $order_counts = array_column($top_products, 'order_count');
                                $max_order_count = !empty($order_counts) ? max($order_counts) : 1;
                                
                                foreach ($top_products as $product): 
                                    $order_count = $product['order_count'] ?? 0;
                                    $percentage = $max_order_count > 0 ? min(($order_count / $max_order_count) * 100, 100) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name'] ?? 'Unknown Product'); ?></td>
                                        <td><?php echo number_format($order_count); ?> orders</td>
                                        <td>
                                            <div style="background: #e9ecef; border-radius: 10px; height: 8px; width: 100%; overflow: hidden;">
                                                <div style="background: linear-gradient(135deg, var(--light-green), var(--accent-green)); 
                                                            height: 100%; width: <?php echo $percentage; ?>%; 
                                                            border-radius: 10px;">
                                                </div>
                                            </div>
                                            <small style="color: var(--text-light); font-size: 0.8rem;">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: var(--text-light); padding: 40px;">
                        <i class="fas fa-box-open" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                        <p>No product data available yet.</p>
                        <?php if (!empty($error_message)): ?>
                            <p style="font-size: 0.9rem; margin-top: 10px; color: var(--delete-red);">
                                Error: <?php echo htmlspecialchars($error_message); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- Order Status Modal -->
    <div id="orderStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Update Order Status</h3>
                <span class="close" onclick="closeModal('orderStatusModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="post" id="orderStatusForm">
                    <input type="hidden" name="action" value="update_order_status">
                    <input type="hidden" name="order_id" id="modalOrderId">
                    
                    <div class="form-group">
                        <label>Current Status:</label>
                        <div id="currentOrderStatus" style="padding: 10px; background: #f8f9fa; border-radius: 6px; font-weight: 600;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalOrderNewStatus">Select New Status:</label>
                        <select name="new_status" id="modalOrderNewStatus" required>
                            <option value="pending_payment">Pending Payment</option>
                            <option value="paid">Paid</option>
                            <option value="processing_purchased_product">Processing Purchased Product</option>
                            <option value="ready_to_pick_the_purchased_product">Ready to Pick</option>
                            <option value="completed">Completed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 25px;">
                        <button type="submit" class="btn btn-success" style="flex: 1;">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                        <button type="button" class="btn btn-primary" onclick="closeModal('orderStatusModal')" style="flex: 1;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('overlay');
            
            // Toggle sidebar on button click
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            });
            
            // Close sidebar when clicking on overlay
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Close sidebar when clicking on a link (on mobile)
            if (window.innerWidth <= 768) {
                const sidebarLinks = document.querySelectorAll('.sidebar a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            // Animate stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add loading states to forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<div class="loading"></div> Processing...';
                        submitBtn.disabled = true;
                        
                        // Revert after 5 seconds if still processing (fallback)
                        setTimeout(() => {
                            if (submitBtn.disabled) {
                                submitBtn.innerHTML = originalText;
                                submitBtn.disabled = false;
                            }
                        }, 5000);
                    }
                });
            });

            // Initialize Charts
            initializeCharts();

            // Auto-refresh data every 2 minutes
            setInterval(() => {
                // You could implement a more sophisticated refresh here
                console.log('Auto-refresh triggered');
            }, 120000);
        });

        function initializeCharts() {
            // Membership Distribution Pie Chart
            const membershipCtx = document.getElementById('membershipChart').getContext('2d');
            const membershipData = {
                labels: [
                    <?php foreach ($membership_stats as $stat): ?>
                        '<?php echo ucfirst(str_replace('_', ' ', $stat['membership_type'])); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($membership_stats as $stat): ?>
                            <?php echo $stat['count']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#4caf50', '#2196f3', '#ff9800', '#9c27b0', '#f44336'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };

            new Chart(membershipCtx, {
                type: 'pie',
                data: membershipData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Revenue Trend Line Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueData = {
                labels: [
                    <?php 
                    $revenueLabels = [];
                    $revenueValues = [];
                    foreach (array_reverse($monthly_revenue) as $revenue): 
                        $revenueLabels[] = date('M Y', strtotime($revenue['month'] . '-01'));
                        $revenueValues[] = $revenue['revenue'];
                    endforeach; 
                    foreach ($revenueLabels as $label): ?>
                        '<?php echo $label; ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Monthly Revenue (₱)',
                    data: [
                        <?php foreach ($revenueValues as $value): ?>
                            <?php echo $value; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4caf50',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            };

            new Chart(revenueCtx, {
                type: 'line',
                data: revenueData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Revenue: ₱${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    }
                }
            });
        }

        function openOrderStatusModal(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            const statusText = currentStatus.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            document.getElementById('currentOrderStatus').innerHTML = 
                '<span class="status-badge status-' + currentStatus.replace(/_/g, '-') + '">' + statusText + '</span>';
            document.getElementById('modalOrderNewStatus').value = currentStatus;
            document.getElementById('orderStatusModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        }

        // Handle keyboard events
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.getElementsByClassName('modal');
                for (let modal of modals) {
                    modal.style.display = 'none';
                }
                
                // Close sidebar on mobile
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('overlay').classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });

        // Enhanced confirmation for critical actions
        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('input[name="action"][value="delete_user"]')) {
                form.addEventListener('submit', function(e) {
                    if (!confirm('⚠️ WARNING: This will permanently delete the user and all their data including orders, discounts, and cart items. This action cannot be undone!\n\nAre you absolutely sure?')) {
                        e.preventDefault();
                    }
                });
            }
        });

        // Add some visual feedback
        document.addEventListener('click', function(e) {
            if (e.target.matches('.btn') || e.target.closest('.btn')) {
                const btn = e.target.matches('.btn') ? e.target : e.target.closest('.btn');
                btn.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    btn.style.transform = '';
                }, 150);
            }
        });

        // Print functionality
        function printDashboard() {
            window.print();
        }

        // Export functionality (placeholder)
        function exportData(type) {
            alert(`Export ${type} functionality would be implemented here.`);
        }
    </script>
</body>
</html>