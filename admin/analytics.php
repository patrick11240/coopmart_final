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

// Retention Offers Analytics (FIXED - removed converted_at reference)
$retention_offers_query = "SELECT 
    COUNT(*) as total_offers,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_offers,
    SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as opened_offers,
    SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as converted_offers,
    ROUND((SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 1) as conversion_rate,
    offer_type,
    COUNT(*) as type_count
FROM retention_offers 
GROUP BY offer_type
ORDER BY type_count DESC";
$retention_offers = $pdo->query($retention_offers_query)->fetchAll(PDO::FETCH_ASSOC);

// Recent retention offers (FIXED - removed converted_at)
$recent_offers_query = "SELECT 
    ro.offer_id,
    u.full_name,
    u.email,
    ro.offer_type,
    ro.discount_percentage,
    ro.points_bonus,
    ro.status,
    ro.sent_at
FROM retention_offers ro
JOIN users u ON ro.user_id = u.user_id
ORDER BY ro.sent_at DESC
LIMIT 10";
$recent_offers = $pdo->query($recent_offers_query)->fetchAll(PDO::FETCH_ASSOC);

// Analytics queries
try {
    // 1. Total counts
    $total_users_query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
    $total_products_query = "SELECT COUNT(*) as total FROM products";
    $total_orders_query = "SELECT COUNT(*) as total FROM orders";
    $total_categories_query = "SELECT COUNT(*) as total FROM categories";
    
    $total_users = $pdo->query($total_users_query)->fetch()['total'];
    $total_products = $pdo->query($total_products_query)->fetch()['total'];
    $total_orders = $pdo->query($total_orders_query)->fetch()['total'];
    $total_categories = $pdo->query($total_categories_query)->fetch()['total'];

    // 2. Revenue analytics
    $total_revenue_query = "SELECT SUM(total_price) as revenue FROM orders WHERE status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')";
    $total_revenue = $pdo->query($total_revenue_query)->fetch()['revenue'] ?? 0;
    
    $monthly_revenue_query = "SELECT SUM(total_price) as revenue FROM orders 
                              WHERE status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                              AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    $monthly_revenue = $pdo->query($monthly_revenue_query)->fetch()['revenue'] ?? 0;

    // 3. CHURN PROBABILITY ANALYTICS - UPDATED WITH MEDIUM RISK
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

    // High-risk customers details
    $high_risk_customers_query = "
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
            u.points,
            u.daily_spins,
            u.last_spin_date,
            (SELECT COUNT(*) FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id 
             WHERE c.user_id = u.user_id 
             AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
             AND NOT EXISTS (SELECT 1 FROM order_items oi 
                            JOIN orders ord ON oi.order_id = ord.order_id 
                            WHERE oi.product_id = ci.product_id 
                            AND ord.user_id = u.user_id 
                            AND ord.created_at > ci.added_at 
                            AND ord.status NOT IN ('canceled'))) as abandoned_carts_count

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
        LIMIT 20
    ";
    $high_risk_customers = $pdo->query($high_risk_customers_query)->fetchAll(PDO::FETCH_ASSOC);

    // Medium-risk customers details (NEW QUERY)
    $medium_risk_customers_query = "
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
            u.points,
            u.daily_spins,
            u.last_spin_date,
            (SELECT COUNT(*) FROM carts c JOIN cart_items ci ON c.cart_id = ci.cart_id 
             WHERE c.user_id = u.user_id 
             AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
             AND NOT EXISTS (SELECT 1 FROM order_items oi 
                            JOIN orders ord ON oi.order_id = ord.order_id 
                            WHERE oi.product_id = ci.product_id 
                            AND ord.user_id = u.user_id 
                            AND ord.created_at > ci.added_at 
                            AND ord.status NOT IN ('canceled'))) as abandoned_carts_count

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
        HAVING churn_probability BETWEEN 0.4 AND 0.7
        ORDER BY churn_probability DESC
        LIMIT 20
    ";
    $medium_risk_customers = $pdo->query($medium_risk_customers_query)->fetchAll(PDO::FETCH_ASSOC);

    // Churn factors analysis
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

    // 4. Order status distribution
    $order_status_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $order_status_result = $pdo->query($order_status_query)->fetchAll(PDO::FETCH_ASSOC);

    // Top Selling Products (based on actual order data)
    $top_products_query = "SELECT 
                            p.product_id,
                            p.name,
                            p.price,
                            COALESCE(c.name, 'Uncategorized') as category_name,
                            COALESCE(SUM(oi.quantity), 0) as total_sold,
                            COUNT(DISTINCT o.order_id) as order_count,
                            COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue
                           FROM products p
                           LEFT JOIN categories c ON p.category_id = c.category_id
                           LEFT JOIN order_items oi ON p.product_id = oi.product_id
                           LEFT JOIN orders o ON oi.order_id = o.order_id
                               AND o.status IN ('paid', 'processing_purchased_product', 
                                              'ready_to_pick_the_purchased_product', 'completed')
                           GROUP BY p.product_id, p.name, p.price, c.name
                           ORDER BY total_sold DESC 
                           LIMIT 10";
    $top_products = $pdo->query($top_products_query)->fetchAll(PDO::FETCH_ASSOC);

    // Least Selling Products
    $least_products_query = "SELECT 
                              p.product_id,
                              p.name,
                              p.price,
                              COALESCE(c.name, 'Uncategorized') as category_name,
                              COALESCE(SUM(oi.quantity), 0) as total_sold,
                              COUNT(DISTINCT o.order_id) as order_count,
                              COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue,
                              p.created_at
                             FROM products p
                             LEFT JOIN categories c ON p.category_id = c.category_id
                             LEFT JOIN order_items oi ON p.product_id = oi.product_id
                             LEFT JOIN orders o ON oi.order_id = o.order_id 
                             AND o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                             GROUP BY p.product_id
                             ORDER BY total_sold ASC, p.created_at DESC
                             LIMIT 10";
    $least_products = $pdo->query($least_products_query)->fetchAll(PDO::FETCH_ASSOC);

    // Age Group Analysis
    $age_group_query = "SELECT 
                          CASE 
                            WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) < 18 THEN 'Under 18'
                            WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                            WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                            WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                            WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                            WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) >= 55 THEN '55+'
                            ELSE 'Unknown'
                          END as age_group,
                          COUNT(DISTINCT u.user_id) as customer_count,
                          COUNT(DISTINCT o.order_id) as total_orders,
                          COALESCE(SUM(o.total_price), 0) as total_spent,
                          COALESCE(AVG(o.total_price), 0) as avg_order_value
                        FROM users u
                        LEFT JOIN orders o ON u.user_id = o.user_id 
                        AND o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                        WHERE u.role = 'customer'
                        GROUP BY age_group
                        ORDER BY 
                          CASE age_group
                            WHEN 'Under 18' THEN 1
                            WHEN '18-24' THEN 2
                            WHEN '25-34' THEN 3
                            WHEN '35-44' THEN 4
                            WHEN '45-54' THEN 5
                            WHEN '55+' THEN 6
                            ELSE 7
                          END";
    $age_groups = $pdo->query($age_group_query)->fetchAll(PDO::FETCH_ASSOC);

    // Top Products by Age Group
    $age_product_trends_query = "SELECT 
                                   CASE 
                                     WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) < 18 THEN 'Under 18'
                                     WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                                     WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                                     WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                                     WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                                     WHEN TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) >= 55 THEN '55+'
                                     ELSE 'Unknown'
                                   END as age_group,
                                   p.name as product_name,
                                   c.name as category_name,
                                   SUM(oi.quantity) as total_quantity,
                                   SUM(oi.price * oi.quantity) as total_revenue
                                 FROM users u
                                 JOIN orders o ON u.user_id = o.user_id
                                 JOIN order_items oi ON o.order_id = oi.order_id
                                 JOIN products p ON oi.product_id = p.product_id
                                 LEFT JOIN categories c ON p.category_id = c.category_id
                                 WHERE u.role = 'customer'
                                 AND o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                                 AND u.birth_date IS NOT NULL
                                 GROUP BY age_group, p.product_id
                                 ORDER BY age_group, total_quantity DESC";
    $age_product_trends = $pdo->query($age_product_trends_query)->fetchAll(PDO::FETCH_ASSOC);

    // Category performance
    $category_performance_query = "SELECT 
                                     c.name, 
                                     COUNT(p.product_id) as product_count,
                                     COALESCE(SUM(sales.total_sold), 0) as total_sold,
                                     AVG(p.price) as avg_price
                                   FROM categories c 
                                   LEFT JOIN products p ON c.category_id = p.category_id
                                   LEFT JOIN (
                                     SELECT 
                                       p.product_id,
                                       SUM(oi.quantity) as total_sold
                                     FROM products p
                                     JOIN order_items oi ON p.product_id = oi.product_id
                                     JOIN orders o ON oi.order_id = o.order_id
                                     WHERE o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                                     GROUP BY p.product_id
                                   ) sales ON p.product_id = sales.product_id
                                   GROUP BY c.category_id, c.name
                                   ORDER BY total_sold DESC";
    $category_performance = $pdo->query($category_performance_query)->fetchAll(PDO::FETCH_ASSOC);

    // User membership distribution
    $membership_query = "SELECT membership_type, COUNT(*) as count FROM users WHERE role = 'customer' GROUP BY membership_type";
    $membership_data = $pdo->query($membership_query)->fetchAll(PDO::FETCH_ASSOC);

    // Recent orders
    $recent_orders_query = "SELECT o.order_id, u.full_name, o.total_price, o.status, o.created_at 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.user_id 
                            ORDER BY o.created_at DESC LIMIT 10";
    $recent_orders = $pdo->query($recent_orders_query)->fetchAll(PDO::FETCH_ASSOC);

    // Daily sales trend (last 7 days)
    $daily_sales_query = "SELECT DATE(created_at) as sale_date, 
                          COUNT(*) as orders_count, 
                          SUM(total_price) as daily_revenue
                          FROM orders 
                          WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                          AND status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                          GROUP BY DATE(created_at)
                          ORDER BY sale_date DESC";
    $daily_sales = $pdo->query($daily_sales_query)->fetchAll(PDO::FETCH_ASSOC);

    // Average order value
    $avg_order_query = "SELECT AVG(total_price) as avg_order FROM orders 
                        WHERE status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')";
    $avg_order_value = $pdo->query($avg_order_query)->fetch()['avg_order'] ?? 0;

    // Monthly Sales Trends (Last 12 Months)
    $monthly_sales_trend_query = "SELECT 
                                    YEAR(created_at) as year, 
                                    MONTH(created_at) as month, 
                                    MONTHNAME(created_at) as month_name,
                                    COUNT(*) as orders_count, 
                                    SUM(total_price) as monthly_revenue,
                                    AVG(total_price) as avg_order_value,
                                    COUNT(DISTINCT user_id) as unique_customers
                                  FROM orders 
                                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                                  AND status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                                  GROUP BY YEAR(created_at), MONTH(created_at), MONTHNAME(created_at)
                                  ORDER BY year ASC, month ASC";
    $monthly_trends = $pdo->query($monthly_sales_trend_query)->fetchAll(PDO::FETCH_ASSOC);

    // Monthly Product Performance
    $monthly_product_trends_query = "SELECT 
                                        p.name as product_name,
                                        c.name as category_name,
                                        YEAR(o.created_at) as year, 
                                        MONTH(o.created_at) as month,
                                        MONTHNAME(o.created_at) as month_name,
                                        COUNT(oi.quantity) as units_sold,
                                        SUM(oi.price * oi.quantity) as product_revenue
                                     FROM orders o
                                     JOIN order_items oi ON o.order_id = oi.order_id
                                     JOIN products p ON oi.product_id = p.product_id
                                     LEFT JOIN categories c ON p.category_id = c.category_id
                                     WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                     AND o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                                     GROUP BY p.product_id, YEAR(o.created_at), MONTH(o.created_at)
                                     ORDER BY year DESC, month DESC, product_revenue DESC";
    $monthly_product_trends = $pdo->query($monthly_product_trends_query)->fetchAll(PDO::FETCH_ASSOC);

    // Monthly Category Trends
    $monthly_category_trends_query = "SELECT 
                                        c.name as category_name,
                                        YEAR(o.created_at) as year, 
                                        MONTH(o.created_at) as month,
                                        MONTHNAME(o.created_at) as month_name,
                                        COUNT(oi.quantity) as units_sold,
                                        SUM(oi.price * oi.quantity) as category_revenue
                                      FROM orders o
                                      JOIN order_items oi ON o.order_id = oi.order_id
                                      JOIN products p ON oi.product_id = p.product_id
                                      LEFT JOIN categories c ON p.category_id = c.category_id
                                      WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                      AND o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                                      GROUP BY c.category_id, YEAR(o.created_at), MONTH(o.created_at)
                                      ORDER BY year DESC, month DESC, category_revenue DESC";
    $monthly_category_trends = $pdo->query($monthly_category_trends_query)->fetchAll(PDO::FETCH_ASSOC);

    $customer_growth_query = "SELECT 
                                DATE_FORMAT(created_at, '%Y-%m') as month, 
                                MONTHNAME(created_at) as month_name,
                                YEAR(created_at) as year,
                                COUNT(*) as new_customers
                              FROM users 
                              WHERE role = 'customer' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                              GROUP BY DATE_FORMAT(created_at, '%Y-%m'), MONTHNAME(created_at), YEAR(created_at)
                              ORDER BY year ASC, MONTH(created_at) ASC";
    $customer_growth = $pdo->query($customer_growth_query)->fetchAll(PDO::FETCH_ASSOC);

    // Calculate growth percentages for monthly trends
    $monthly_trends_with_growth = [];
    for ($i = 0; $i < count($monthly_trends); $i++) {
        $current = $monthly_trends[$i];
        $previous = isset($monthly_trends[$i + 1]) ? $monthly_trends[$i + 1] : null;
        
        $revenue_growth = 0;
        $orders_growth = 0;
        
        if ($previous && $previous['monthly_revenue'] > 0) {
            $revenue_growth = (($current['monthly_revenue'] - $previous['monthly_revenue']) / $previous['monthly_revenue']) * 100;
        }
        
        if ($previous && $previous['orders_count'] > 0) {
            $orders_growth = (($current['orders_count'] - $previous['orders_count']) / $previous['orders_count']) * 100;
        }
        
        $current['revenue_growth'] = $revenue_growth;
        $current['orders_growth'] = $orders_growth;
        $monthly_trends_with_growth[] = $current;
    }

    // Gender distribution query
    $gender_distribution_query = "SELECT 
                                    gender,
                                    COUNT(*) as customer_count,
                                    SUM(points) as total_points
                                  FROM users 
                                  WHERE role = 'customer'
                                  GROUP BY gender
                                  ORDER BY customer_count DESC";
    $gender_distribution = $pdo->query($gender_distribution_query)->fetchAll(PDO::FETCH_ASSOC);

    // Male purchasing trends
    $male_trends_query = "SELECT 
                            p.name as product_name,
                            c.name as category_name,
                            COUNT(oi.quantity) as purchase_count,
                            SUM(oi.quantity) as total_quantity,
                            SUM(oi.price * oi.quantity) as total_revenue,
                            AVG(oi.price) as avg_price
                          FROM orders o
                          JOIN users u ON o.user_id = u.user_id
                          JOIN order_items oi ON o.order_id = oi.order_id
                          JOIN products p ON oi.product_id = p.product_id
                          LEFT JOIN categories c ON p.category_id = c.category_id
                          WHERE u.gender = 'male' 
                          AND u.role = 'customer'
                          AND o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                          GROUP BY p.product_id
                          ORDER BY total_quantity DESC
                          LIMIT 10";
    $male_trends = $pdo->query($male_trends_query)->fetchAll(PDO::FETCH_ASSOC);

    // Female purchasing trends
    $female_trends_query = "SELECT 
                              p.name as product_name,
                              c.name as category_name,
                              COUNT(oi.quantity) as purchase_count,
                              SUM(oi.quantity) as total_quantity,
                              SUM(oi.price * oi.quantity) as total_revenue,
                              AVG(oi.price) as avg_price
                            FROM orders o
                            JOIN users u ON o.user_id = u.user_id
                            JOIN order_items oi ON o.order_id = oi.order_id
                            JOIN products p ON oi.product_id = p.product_id
                            LEFT JOIN categories c ON p.category_id = c.category_id
                            WHERE u.gender = 'female' 
                            AND u.role = 'customer'
                            AND o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                            GROUP BY p.product_id
                            ORDER BY total_quantity DESC
                            LIMIT 10";
    $female_trends = $pdo->query($female_trends_query)->fetchAll(PDO::FETCH_ASSOC);

    // Gender spending analysis
    $gender_spending_query = "SELECT 
                                u.gender,
                                COUNT(DISTINCT o.order_id) as total_orders,
                                SUM(o.total_price) as total_spent,
                                AVG(o.total_price) as avg_order_value,
                                COUNT(DISTINCT u.user_id) as customer_count
                              FROM orders o
                              JOIN users u ON o.user_id = u.user_id
                              WHERE u.role = 'customer'
                              AND o.status IN ('paid', 'processing_purchased_product', 'ready_to_pick_the_purchased_product', 'completed')
                              GROUP BY u.gender
                              ORDER BY total_spent DESC";
    $gender_spending = $pdo->query($gender_spending_query)->fetchAll(PDO::FETCH_ASSOC);

    // DISCOUNT ANALYTICS QUERIES
    $discount_overview_query = "SELECT 
                                COUNT(*) as total_discounts,
                                SUM(is_used) as used_discounts,
                                COUNT(*) - SUM(is_used) as active_discounts,
                                SUM(discount_amount) as total_discount_value,
                                AVG(discount_amount) as avg_discount_amount
                               FROM user_discounts";
    $discount_overview = $pdo->query($discount_overview_query)->fetch(PDO::FETCH_ASSOC);

    $discount_type_analysis_query = "SELECT 
                                    discount_type,
                                    COUNT(*) as total_issued,
                                    SUM(is_used) as used_count,
                                    (SUM(is_used) * 100.0 / COUNT(*)) as usage_rate,
                                    SUM(discount_amount) as total_discount_value,
                                    AVG(discount_amount) as avg_amount
                                   FROM user_discounts 
                                   GROUP BY discount_type 
                                   ORDER BY total_issued DESC";
    $discount_types = $pdo->query($discount_type_analysis_query)->fetchAll(PDO::FETCH_ASSOC);

    $discount_status_query = "SELECT 
                                CASE 
                                    WHEN expires_at < NOW() AND is_used = 0 THEN 'Expired'
                                    WHEN expires_at >= NOW() AND is_used = 0 THEN 'Active'
                                    WHEN is_used = 1 THEN 'Used'
                                    ELSE 'Other'
                                END as status,
                                COUNT(*) as count,
                                SUM(discount_amount) as total_value
                              FROM user_discounts 
                              GROUP BY status";
    $discount_status = $pdo->query($discount_status_query)->fetchAll(PDO::FETCH_ASSOC);

    $monthly_discount_trends_query = "SELECT 
                                        DATE_FORMAT(applied_at, '%Y-%m') as month,
                                        MONTHNAME(applied_at) as month_name,
                                        discount_type,
                                        COUNT(*) as discounts_issued,
                                        SUM(is_used) as discounts_used,
                                        SUM(discount_amount) as total_discount_value
                                      FROM user_discounts 
                                      WHERE applied_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                      GROUP BY DATE_FORMAT(applied_at, '%Y-%m'), MONTHNAME(applied_at), discount_type
                                      ORDER BY month DESC";
    $monthly_discount_trends = $pdo->query($monthly_discount_trends_query)->fetchAll(PDO::FETCH_ASSOC);

    $top_discounted_products_query = "SELECT 
                                        p.name as product_name,
                                        c.name as category_name,
                                        COUNT(ud.product_id) as discount_count,
                                        SUM(ud.discount_amount) as total_discount_value,
                                        AVG(ud.discount_amount) as avg_discount
                                      FROM user_discounts ud
                                      JOIN products p ON ud.product_id = p.product_id
                                      LEFT JOIN categories c ON p.category_id = c.category_id
                                      GROUP BY ud.product_id, p.name, c.name
                                      ORDER BY discount_count DESC
                                      LIMIT 10";
    $top_discounted_products = $pdo->query($top_discounted_products_query)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error fetching analytics data: " . $e->getMessage();
}

// Calculate key insights
$revenue_per_customer = $total_users > 0 ? $total_revenue / $total_users : 0;
$orders_per_customer = $total_users > 0 ? $total_orders / $total_users : 0;
$products_per_order = $total_orders > 0 ? array_sum(array_column($top_products, 'total_sold')) / $total_orders : 0;

// Determine business health
$recent_month = !empty($monthly_trends_with_growth) ? $monthly_trends_with_growth[0] : null;
$revenue_trend = $recent_month['revenue_growth'] ?? 0;
$orders_trend = $recent_month['orders_growth'] ?? 0;

$business_health = 'stable';
if ($revenue_trend > 10 && $orders_trend > 10) {
    $business_health = 'excellent';
} elseif ($revenue_trend > 0 && $orders_trend > 0) {
    $business_health = 'good';
} elseif ($revenue_trend < -10 || $orders_trend < -10) {
    $business_health = 'needs_attention';
}

// Churn health assessment
$churn_health = 'healthy';
$high_risk_percentage = $churn_overview['total_customers'] > 0 ? 
    ($churn_overview['high_risk_customers'] / $churn_overview['total_customers']) * 100 : 0;

if ($high_risk_percentage > 20) {
    $churn_health = 'critical';
} elseif ($high_risk_percentage > 10) {
    $churn_health = 'warning';
} elseif ($high_risk_percentage > 5) {
    $churn_health = 'monitor';
}

// FIX: Calculate the missing customer growth variables
$total_new_customers = 0;
$avg_new_customers = 0;

if (!empty($customer_growth)) {
    $total_new_customers = array_sum(array_column($customer_growth, 'new_customers'));
    $avg_new_customers = $total_new_customers / count($customer_growth);
}

function getTrendDescription($revenue_growth, $orders_growth, $current_revenue, $previous_revenue) {
    if ($revenue_growth > 20) {
        return "🚀 <strong>Exceptional Growth:</strong> Revenue surged by " . number_format($revenue_growth, 1) . "%, indicating strong market demand and effective sales strategies.";
    } elseif ($revenue_growth > 10) {
        return "📈 <strong>Strong Performance:</strong> Revenue increased by " . number_format($revenue_growth, 1) . "%, showing healthy business expansion.";
    } elseif ($revenue_growth > 0) {
        return "✅ <strong>Positive Trend:</strong> Revenue grew by " . number_format($revenue_growth, 1) . "%. Continue current strategies while exploring growth opportunities.";
    } elseif ($revenue_growth == 0) {
        return "➡️ <strong>Stable Period:</strong> Revenue remained flat. Consider promotional campaigns to stimulate growth.";
    } else {
        return "⚠️ <strong>Declining Revenue:</strong> Revenue decreased by " . number_format(abs($revenue_growth), 1) . "%. Immediate action needed to reverse this trend.";
    }
}

// Fix the undefined variable error
// Add this at line ~559
$total_age_customers = !empty($age_groups) ? array_sum(array_column($age_groups, 'customer_count')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Analytics Dashboard - CoopMart Admin</title>
    <style>
  /* Base Styles */
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
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--light-bg);
}

/* Sidebar Styles */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 260px;
    background: linear-gradient(180deg, var(--dark-green), var(--medium-green));
    color: white;
    padding: 30px 20px;
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebar h2 {
    font-size: 24px;
    margin-bottom: 30px;
    text-align: center;
    padding-bottom: 20px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 12px 20px;
    margin: 8px 0;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 15px;
}

.sidebar a:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
}

.sidebar a.active {
    background: rgba(255, 255, 255, 0.2);
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: var(--dark-green);
    color: white;
    border: none;
    padding: 12px 16px;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    font-size: 20px;
}

.mobile-menu-toggle:hover {
    background: var(--medium-green);
}

/* Main Content */
.main-content {
    margin-left: 260px;
    padding: 30px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

/* Analytics Header */
.analytics-header {
    background: linear-gradient(135deg, var(--dark-green), var(--medium-green));
    color: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.analytics-header h1 {
    font-size: 32px;
    margin-bottom: 10px;
}

.analytics-header p {
    font-size: 16px;
    opacity: 0.9;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.stat-card {
    background: var(--white);
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 3px 10px var(--shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-top: 4px solid var(--medium-green);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: var(--dark-green);
    margin-bottom: 8px;
}

.stat-label {
    color: var(--text-light);
    font-size: 14px;
    font-weight: 500;
}

/* Analytics Navigation Grid - FIXED */
.analytics-nav-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    margin: 30px 0;
    padding: 10px;
}

.analytics-nav-card {
    background: var(--white);
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 3px 15px var(--shadow);
    border-left: 5px solid var(--medium-green);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    min-height: 220px;
    position: relative;
    overflow: hidden;
}

.analytics-nav-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, transparent 0%, rgba(74, 124, 44, 0.03) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.analytics-nav-card:hover::before {
    opacity: 1;
}

.analytics-nav-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    border-left-color: var(--dark-green);
}

.card-icon {
    font-size: 48px;
    margin-bottom: 15px;
    line-height: 1;
}

.analytics-nav-card h3 {
    margin: 0 0 12px 0;
    color: var(--text-dark);
    font-size: 19px;
    font-weight: 600;
    line-height: 1.3;
}

.analytics-nav-card p {
    margin: 0;
    color: var(--text-light);
    font-size: 14px;
    line-height: 1.6;
    flex-grow: 1;
}

.card-stats {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 2px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    gap: 15px;
    font-size: 12px;
    color: var(--text-light);
    font-weight: 500;
}

.card-stats span {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    overflow-y: auto;
    backdrop-filter: blur(3px);
}

.modal-content {
    background-color: var(--white);
    margin: 2% auto;
    padding: 0;
    border-radius: 15px;
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideIn {
    from { 
        transform: translateY(-50px) scale(0.95); 
        opacity: 0; 
    }
    to { 
        transform: translateY(0) scale(1); 
        opacity: 1; 
    }
}

.modal-header {
    background: linear-gradient(135deg, var(--dark-green), var(--medium-green));
    color: white;
    padding: 25px 35px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 26px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.close-modal {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.close-modal:hover {
    background-color: rgba(255, 255, 255, 0.25);
    transform: rotate(90deg);
}

.modal-body {
    padding: 35px;
    max-height: calc(90vh - 100px);
    overflow-y: auto;
}

/* Status badges */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    display: inline-block;
}

.risk-high { background: #dc3545; color: white; }
.risk-medium { background: #ffc107; color: black; }
.risk-low { background: #28a745; color: white; }

/* Churn Health Status Colors */
.churn-critical { 
    background: linear-gradient(135deg, #f8d7da, #f5c6cb); 
    border-left: 4px solid #dc3545; 
}

.churn-warning { 
    background: linear-gradient(135deg, #fff3cd, #ffeaa7); 
    border-left: 4px solid #ffc107; 
}

.churn-monitor { 
    background: linear-gradient(135deg, #d1ecf1, #bee5eb); 
    border-left: 4px solid #17a2b8; 
}

.churn-healthy { 
    background: linear-gradient(135deg, #d4edda, #c3e6cb); 
    border-left: 4px solid #28a745; 
}

.churn-health-status {
    font-size: 18px;
    font-weight: bold;
    padding: 8px 16px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.9);
}

.membership-badge {
    background: #6c757d;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
}

/* Tables */
.table-container {
    overflow-x: auto;
    margin: 20px 0;
    border-radius: 10px;
    box-shadow: 0 2px 8px var(--shadow);
}

.analytics-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--white);
}

.analytics-table th,
.analytics-table td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.analytics-table th {
    background: var(--light-bg);
    font-weight: 600;
    color: var(--text-dark);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.analytics-table tbody tr:hover {
    background: rgba(74, 124, 44, 0.05);
}

/* Trend Charts - COMPLETE STYLES */
.trends-container {
    background: var(--white);
    border-radius: 12px;
    padding: 30px;
    margin: 20px 0;
    box-shadow: 0 3px 15px var(--shadow);
}

.trends-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 20px;
}

.trends-title {
    font-size: 24px;
    font-weight: bold;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.trend-chart {
    display: flex;
    align-items: flex-end;
    gap: 15px;
    padding: 30px 20px;
    overflow-x: auto;
    min-height: 320px;
    background: var(--white);
    border-radius: 10px;
}

.trend-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 85px;
    position: relative;
}

.trend-value {
    font-size: 13px;
    font-weight: bold;
    color: var(--text-dark);
    margin-bottom: 8px;
    text-align: center;
}

.trend-bar-visual {
    width: 45px;
    background: linear-gradient(to top, var(--light-green), var(--dark-green));
    border-radius: 5px 5px 0 0;
    position: relative;
    transition: all 0.3s ease;
    margin-bottom: 10px;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.trend-bar-visual:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    filter: brightness(1.1);
}

.trend-label {
    font-size: 11px;
    color: var(--text-light);
    margin-top: 5px;
    text-align: center;
    font-weight: 500;
}

.growth-indicator {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 12px;
    margin-top: 5px;
    font-weight: 600;
}

.growth-positive { 
    background: #d4edda; 
    color: #155724; 
}

.growth-negative { 
    background: #f8d7da; 
    color: #721c24; 
}

.growth-neutral {
    background: #e2e8f0;
    color: #4a5568;
}

/* Chart Container - For Daily Sales */
.chart-container {
    display: flex;
    align-items: flex-end;
    gap: 15px;
    padding: 30px 20px;
    overflow-x: auto;
    min-height: 300px;
    background: var(--white);
    border-radius: 10px;
}

.bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 80px;
    position: relative;
}

.bar-value {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 5px;
    color: var(--text-dark);
}

.bar-visual {
    width: 40px;
    background: linear-gradient(to top, var(--light-green), var(--dark-green));
    border-radius: 3px 3px 0 0;
    transition: all 0.3s;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.bar-visual:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.bar-label {
    font-size: 11px;
    color: var(--text-light);
    margin-top: 10px;
}

/* Status Bars - Progress Bars */
.status-bars {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 20px 0;
}

.status-bar {
    display: flex;
    align-items: center;
    gap: 15px;
}

.status-label {
    min-width: 120px;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
}

.status-progress {
    flex: 1;
    height: 24px;
    background: #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress-fill {
    height: 100%;
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 10px;
    color: white;
    font-size: 11px;
    font-weight: bold;
}

.status-count {
    min-width: 100px;
    text-align: right;
    font-weight: 600;
    color: var(--text-dark);
}

/* Monthly Summary Cards */
.monthly-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.summary-card {
    background: var(--light-bg);
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid var(--medium-green);
    transition: all 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.summary-title {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 8px;
}

.summary-value {
    font-size: 22px;
    font-weight: bold;
    color: var(--text-dark);
}

/* Details Grid - For Product/Category Trends */
.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.detail-section {
    background: var(--light-bg);
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px var(--shadow);
}

.detail-title {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.detail-list {
    list-style: none;
    padding: 0;
}

.detail-list li {
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
}

.detail-list li:last-child {
    border-bottom: none;
}

/* Analytics Row - 2 Column Layout */
.analytics-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

/* Membership Chart */
.membership-chart {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.membership-item {
    background: var(--light-bg);
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.membership-item:hover {
    border-color: var(--medium-green);
    transform: translateY(-3px);
}

.membership-count {
    font-size: 28px;
    font-weight: bold;
    color: var(--dark-green);
    margin-bottom: 5px;
}

.membership-type {
    color: var(--text-light);
    text-transform: capitalize;
    font-size: 14px;
}

/* Gender Stats Grid */
.gender-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.gender-stat-card {
    background: var(--light-bg);
    padding: 20px;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.gender-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Insights Grid */
.insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Alerts */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px 0;
    font-size: 14px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Buttons */
.btn-small {
    background: var(--medium-green);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-small:hover {
    background: var(--dark-green);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

/* Insight Card */
.insight-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.insight-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
}

/* Analytics Card */
.analytics-card {
    background: var(--white);
    border-radius: 12px;
    padding: 30px;
    margin: 20px 0;
    box-shadow: 0 3px 12px var(--shadow);
}

.card-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Performance Score */
.performance-score {
    display: inline-block;
    padding: 8px 16px;
    background: linear-gradient(135deg, var(--light-green), var(--dark-green));
    color: white;
    border-radius: 20px;
    font-weight: bold;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .mobile-menu-toggle {
        display: block;
    }

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
        padding: 80px 20px 30px;
    }

    .analytics-header {
        padding: 25px;
    }

    .analytics-header h1 {
        font-size: 24px;
    }

    .analytics-header p {
        font-size: 14px;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .stat-card {
        padding: 20px;
    }

    .stat-number {
        font-size: 24px;
    }

    .analytics-nav-grid {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 5px;
    }

    .analytics-nav-card {
        padding: 25px;
        min-height: auto;
    }

    .card-icon {
        font-size: 40px;
    }

    .analytics-nav-card h3 {
        font-size: 17px;
    }

    .card-stats {
        flex-direction: column;
        gap: 8px;
    }

    .analytics-row {
        grid-template-columns: 1fr;
    }

    .modal-content {
        width: 98%;
        margin: 5% auto;
        border-radius: 10px;
    }

    .modal-header {
        padding: 20px;
    }

    .modal-header h2 {
        font-size: 20px;
    }

    .modal-body {
        padding: 20px;
    }

    .analytics-table {
        font-size: 13px;
    }

    .analytics-table th,
    .analytics-table td {
        padding: 10px 8px;
    }

    .trend-chart,
    .chart-container {
        padding: 20px 10px;
        min-height: 250px;
    }

    .trend-bar,
    .bar {
        min-width: 60px;
    }

    .trend-bar-visual,
    .bar-visual {
        width: 35px;
    }

    .details-grid,
    .insights-grid,
    .membership-chart,
    .gender-stats-grid {
        grid-template-columns: 1fr;
    }

    .monthly-summary {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 70px 15px 20px;
    }

    .analytics-header {
        padding: 20px;
        border-radius: 10px;
    }

    .analytics-header h1 {
        font-size: 20px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .modal-header h2 {
        font-size: 18px;
        gap: 8px;
    }

    .close-modal {
        width: 36px;
        height: 36px;
        font-size: 24px;
    }

    .status-bar {
        flex-direction: column;
        align-items: flex-start;
    }

    .status-label,
    .status-count {
        width: 100%;
    }
}

/* Scrollbar Styling */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--light-bg);
}

::-webkit-scrollbar-thumb {
    background: var(--medium-green);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--dark-green);
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.loading {
    animation: pulse 1.5s ease-in-out infinite;
}
    </style>

</head>
<body>
 <button class="mobile-menu-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="sidebar">
        <h2><i class="fas fa-cogs"></i> Admin Panel</h2>
        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
        <a href="add_product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
        <a href="add_category.php"><i class="fas fa-tags"></i> Add Category</a>
        <a href="analytics.php" class="active"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="../auth/admin_logout.php" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <!-- Main Dashboard Content -->
        <div class="analytics-header">
            <h1>CoopMart Analytics Dashboard</h1>
            <p>Comprehensive business insights - Click on any analytic card to view detailed reports</p>
        </div>

        <!-- Quick Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_products); ?></div>
                <div class="stat-label">Products Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₱<?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₱<?php echo number_format($monthly_revenue, 2); ?></div>
                <div class="stat-label">This Month Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₱<?php echo number_format($avg_order_value, 2); ?></div>
                <div class="stat-label">Average Order Value</div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Analytics Navigation Cards -->
        <div class="analytics-nav-grid">
            <!-- Card 1: Churn Probability Analytics -->
            <div class="analytics-nav-card" onclick="openModal('churn-analytics')">
                <div class="card-icon">🚨</div>
                <h3>Churn Probability Analytics</h3>
                <p>Monitor customer retention risks and identify at-risk customers with detailed churn probability scoring and prevention strategies.</p>
                <div class="card-stats">
                    <span>High Risk: <?php echo number_format($churn_overview['high_risk_customers'] ?? 0); ?></span>
                    <span>Medium Risk: <?php echo number_format($churn_overview['medium_risk_customers'] ?? 0); ?></span>
                </div>
            </div>

            <!-- Card 2: Discount Performance Analytics -->
            <div class="analytics-nav-card" onclick="openModal('discount-analytics')">
                <div class="card-icon">🎁</div>
                <h3>Discount Performance Analytics</h3>
                <p>Track discount usage, effectiveness, and ROI across different discount types and customer segments.</p>
                <div class="card-stats">
                    <span>Used: <?php echo number_format($discount_overview['used_discounts'] ?? 0); ?></span>
                    <span>Active: <?php echo number_format($discount_overview['active_discounts'] ?? 0); ?></span>
                </div>
            </div>

            <!-- Card 3: Sales Trends Analysis -->
            <div class="analytics-nav-card" onclick="openModal('sales-trends')">
                <div class="card-icon">📊</div>
                <h3>Sales Trends Analysis</h3>
                <p>Analyze monthly revenue patterns, order volume trends, and growth metrics with comparative analysis.</p>
                <div class="card-stats">
                    <span>Growth: <?php echo ($recent_month['revenue_growth'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($recent_month['revenue_growth'] ?? 0, 1); ?>%</span>
                    <span>Trend: <?php echo $business_health; ?></span>
                </div>
            </div>

            <!-- Card 4: Customer Demographics -->
            <div class="analytics-nav-card" onclick="openModal('customer-demographics')">
                <div class="card-icon">👥</div>
                <h3>Customer Demographics</h3>
                <p>Understand your customer base through age group analysis, gender distribution, and membership types.</p>
                <div class="card-stats">
                    <span>Age Groups: <?php echo count($age_groups); ?></span>
                    <span>Membership: <?php echo count($membership_data); ?> types</span>
                </div>
            </div>

            <!-- Card 5: Product Performance -->
            <div class="analytics-nav-card" onclick="openModal('product-performance')">
                <div class="card-icon">🏆</div>
                <h3>Product Performance</h3>
                <p>Identify top-selling products, underperformers, and revenue contribution across your product catalog.</p>
                <div class="card-stats">
                    <span>Top Seller: <?php echo !empty($top_products) ? (strlen($top_products[0]['name']) > 15 ? substr($top_products[0]['name'], 0, 15).'...' : $top_products[0]['name']) : 'N/A'; ?></span>
                    <span>Low Sales: <?php echo count(array_filter($least_products, function($p) { return $p['total_sold'] == 0; })); ?></span>
                </div>
            </div>

            <!-- Card 6: Category Performance -->
            <div class="analytics-nav-card" onclick="openModal('category-performance')">
                <div class="card-icon">📂</div>
                <h3>Category Performance</h3>
                <p>Analyze sales distribution, product counts, and revenue performance across product categories.</p>
                <div class="card-stats">
                    <span>Categories: <?php echo count($category_performance); ?></span>
                    <span>Top: <?php echo !empty($category_performance) ? (strlen($category_performance[0]['name']) > 15 ? substr($category_performance[0]['name'], 0, 15).'...' : $category_performance[0]['name']) : 'N/A'; ?></span>
                </div>
            </div>

            <!-- Card 7: Customer Growth Analytics -->
            <div class="analytics-nav-card" onclick="openModal('customer-growth')">
                <div class="card-icon">📈</div>
                <h3>Customer Growth Analytics</h3>
                <p>Track new customer acquisition, monthly growth rates, and customer base expansion over time.</p>
                <div class="card-stats">
                    <span>New (12mo): <?php echo number_format($total_new_customers); ?></span>
                    <span>Avg/Month: <?php echo number_format($avg_new_customers); ?></span>
                </div>
            </div>

            <!-- Card 8: Order Status Distribution -->
            <div class="analytics-nav-card" onclick="openModal('order-status')">
                <div class="card-icon">📦</div>
                <h3>Order Status Distribution</h3>
                <p>Monitor order lifecycle, status percentages, and fulfillment pipeline efficiency.</p>
                <div class="card-stats">
                    <span>Statuses: <?php echo count($order_status_result); ?></span>
                    <span>Total: <?php echo number_format($total_orders); ?></span>
                </div>
            </div>

            <!-- Card 9: Retention Offers Analytics -->
            <div class="analytics-nav-card" onclick="openModal('retention-offers')">
                <div class="card-icon">🎯</div>
                <h3>Retention Offers Analytics</h3>
                <p>Track performance of customer retention campaigns, conversion rates, and offer effectiveness.</p>
                <div class="card-stats">
                    <span>Sent: <?php echo !empty($retention_offers) ? array_sum(array_column($retention_offers, 'sent_offers')) : 0; ?></span>
                    <span>Converted: <?php echo !empty($retention_offers) ? array_sum(array_column($retention_offers, 'converted_offers')) : 0; ?></span>
                </div>
            </div>

            <!-- Card 10: Gender-Based Analytics -->
            <div class="analytics-nav-card" onclick="openModal('gender-analytics')">
                <div class="card-icon">👨‍👩‍👧‍👦</div>
                <h3>Gender-Based Analytics</h3>
                <p>Analyze purchasing patterns, spending behavior, and product preferences by gender.</p>
                <div class="card-stats">
                    <span>Segments: <?php echo count($gender_distribution); ?></span>
                    <span>Top Spender: <?php echo !empty($gender_spending) ? ucfirst($gender_spending[0]['gender']) : 'N/A'; ?></span>
                </div>
            </div>

            <!-- Card 11: Monthly Product Trends -->
            <div class="analytics-nav-card" onclick="openModal('monthly-product-trends')">
                <div class="card-icon">📅</div>
                <h3>Monthly Product Trends</h3>
                <p>Track product performance over time with monthly sales data and seasonal patterns.</p>
                <div class="card-stats">
                    <span>Months: 6</span>
                    <span>Products: <?php echo count($monthly_product_trends); ?> records</span>
                </div>
            </div>

            <!-- Card 12: Category Trends -->
            <div class="analytics-nav-card" onclick="openModal('category-trends')">
                <div class="card-icon">📊</div>
                <h3>Category Trends</h3>
                <p>Monitor category performance evolution and revenue trends over the last 6 months.</p>
                <div class="card-stats">
                    <span>Months: 6</span>
                    <span>Categories: <?php echo count($monthly_category_trends); ?> records</span>
                </div>
            </div>

            <!-- Card 13: Basket Size Analysis -->
            <div class="analytics-nav-card" onclick="openModal('basket-analysis')">
                <div class="card-icon">🛒</div>
                <h3>Basket Size Analysis</h3>
                <p>Analyze cross-selling effectiveness and average products per order metrics.</p>
                <div class="card-stats">
                    <span>Avg Basket: <?php echo number_format($products_per_order, 1); ?> items</span>
                    <span>Orders/Customer: <?php echo number_format($orders_per_customer, 2); ?></span>
                </div>
            </div>

            <!-- Card 14: Customer Engagement Metrics -->
            <div class="analytics-nav-card" onclick="openModal('engagement-metrics')">
                <div class="card-icon">💫</div>
                <h3>Customer Engagement Metrics</h3>
                <p>Track login frequency, points usage, and loyalty program engagement patterns.</p>
                <div class="card-stats">
                    <span>Login Streaks: Active</span>
                    <span>Points Issued: <?php echo !empty($gender_distribution) ? number_format(array_sum(array_column($gender_distribution, 'total_points'))) : '0'; ?></span>
                </div>
            </div>

            <!-- Card 15: Business Health Assessment -->
            <div class="analytics-nav-card" onclick="openModal('business-health')">
                <div class="card-icon">❤️</div>
                <h3>Business Health Assessment</h3>
                <p>Comprehensive overview of overall business performance and key health indicators.</p>
                <div class="card-stats">
                    <span>Status: <?php echo ucfirst(str_replace('_', ' ', $business_health)); ?></span>
                    <span>Revenue/Customer: ₱<?php echo number_format($revenue_per_customer, 2); ?></span>
                </div>
            </div>

            <!-- Card 16: Daily Sales Analytics -->
            <div class="analytics-nav-card" onclick="openModal('daily-sales')">
                <div class="card-icon">📆</div>
                <h3>Daily Sales Analytics</h3>
                <p>Monitor daily sales patterns, revenue trends, and order volume for the last 7 days.</p>
                <div class="card-stats">
                    <span>Days: 7</span>
                    <span>Avg Daily: ₱<?php echo !empty($daily_sales) ? number_format(array_sum(array_column($daily_sales, 'daily_revenue')) / count($daily_sales), 2) : '0.00'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 1: Churn Probability Analytics -->
    <div id="churn-analytics" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🚨 Customer Churn Probability Analytics</h2>
                <button class="close-modal" onclick="closeModal('churn-analytics')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="<?php echo 'churn-' . $churn_health; ?>" style="padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0;">Customer Churn Overview</h3>
                        <div class="churn-health-status">
                            <?php
                            $status_icons = [
                                'critical' => '🔴',
                                'warning' => '🟡', 
                                'monitor' => '🔵',
                                'healthy' => '🟢'
                            ];
                            $status_messages = [
                                'critical' => 'Immediate Action Required',
                                'warning' => 'Needs Attention',
                                'monitor' => 'Monitor Closely', 
                                'healthy' => 'Good Standing'
                            ];
                            echo $status_icons[$churn_health] . ' ' . $status_messages[$churn_health];
                            ?>
                        </div>
                    </div>

                    <div class="stats-grid" style="margin-bottom: 30px;">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($churn_overview['total_customers'] ?? 0); ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: #dc3545;"><?php echo number_format($churn_overview['high_risk_customers'] ?? 0); ?></div>
                            <div class="stat-label">High Risk Customers</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: #ffc107;"><?php echo number_format($churn_overview['medium_risk_customers'] ?? 0); ?></div>
                            <div class="stat-label">Medium Risk</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: #28a745;"><?php echo number_format($churn_overview['low_risk_customers'] ?? 0); ?></div>
                            <div class="stat-label">Low Risk</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($churn_overview['avg_churn_rate'] ?? 0, 1); ?>%</div>
                            <div class="stat-label">Avg Churn Rate</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: <?php echo $high_risk_percentage > 20 ? '#dc3545' : ($high_risk_percentage > 10 ? '#ffc107' : '#28a745'); ?>">
                                <?php echo number_format($high_risk_percentage, 1); ?>%
                            </div>
                            <div class="stat-label">High Risk %</div>
                        </div>
                    </div>

                    <div class="analytics-card" style="margin-bottom: 20px;">
                        <div class="card-title">📊 Customer Risk Distribution</div>
                        <div class="status-bars">
                            <?php 
                            $total_customers = $churn_overview['total_customers'] ?? 1;
                            $risk_categories = [
                                ['count' => $churn_overview['high_risk_customers'] ?? 0, 'label' => 'High Risk', 'color' => '#dc3545'],
                                ['count' => $churn_overview['medium_risk_customers'] ?? 0, 'label' => 'Medium Risk', 'color' => '#ffc107'],
                                ['count' => $churn_overview['low_risk_customers'] ?? 0, 'label' => 'Low Risk', 'color' => '#28a745']
                            ];
                            
                            foreach ($risk_categories as $risk): 
                                $percentage = ($risk['count'] / $total_customers) * 100;
                            ?>
                            <div class="status-bar">
                                <div class="status-label"><?php echo $risk['label']; ?></div>
                                <div class="status-progress">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $risk['color']; ?>;"></div>
                                </div>
                                <div class="status-count">
                                    <?php echo $risk['count']; ?> (<?php echo number_format($percentage, 1); ?>%)
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="analytics-card" style="margin-bottom: 20px;">
                        <div class="card-title">🔍 Churn Risk Factors Analysis</div>
                        <div class="table-container">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>Risk Category</th>
                                        <th>Customers</th>
                                        <th>Avg Days Since Login</th>
                                        <th>Avg Days Since Order</th>
                                        <th>Avg Login Streak</th>
                                        <th>Avg Points</th>
                                        <th>Avg Abandoned Carts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($churn_factors as $factor): ?>
                                    <tr>
                                        <td>
                                            <span class="status-badge risk-<?php echo $factor['risk_category']; ?>">
                                                <?php echo ucfirst($factor['risk_category']); ?> Risk
                                            </span>
                                        </td>
                                        <td><strong><?php echo $factor['customer_count']; ?></strong></td>
                                        <td><?php echo number_format($factor['avg_days_since_login'], 1); ?> days</td>
                                        <td><?php echo number_format($factor['avg_days_since_order'], 1); ?> days</td>
                                        <td><?php echo number_format($factor['avg_login_streak'], 1); ?></td>
                                        <td><?php echo number_format($factor['avg_points'], 1); ?></td>
                                        <td><?php echo number_format($factor['avg_abandoned_carts'], 1); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="analytics-card" style="margin-bottom: 20px;">
                        <div class="card-title">🔴 High-Risk Customers Requiring Immediate Attention</div>
                        <div class="table-container">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Churn Probability</th>
                                        <th>Last Login</th>
                                        <th>Last Order</th>
                                        <th>Total Orders</th>
                                        <th>Login Streak</th>
                                        <th>Abandoned Carts</th>
                                        <th>Points</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($high_risk_customers)): ?>
                                        <?php foreach ($high_risk_customers as $customer): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($customer['email']); ?></small><br>
                                                <small class="membership-badge"><?php echo $customer['membership_type']; ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge risk-high">
                                                    <?php echo number_format($customer['churn_probability'] * 100, 1); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $customer['last_login_date'] ? date('M j, Y', strtotime($customer['last_login_date'])) : 'Never'; ?>
                                                <?php if ($customer['last_login_date']): ?>
                                                    <br><small>(<?php echo floor((time() - strtotime($customer['last_login_date'])) / (60 * 60 * 24)); ?> days ago)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $customer['last_order_date'] ? date('M j, Y', strtotime($customer['last_order_date'])) : 'No orders'; ?>
                                                <?php if ($customer['last_order_date']): ?>
                                                    <br><small>(<?php echo floor((time() - strtotime($customer['last_order_date'])) / (60 * 60 * 24)); ?> days ago)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $customer['total_orders'] ?? 0; ?></td>
                                            <td><?php echo $customer['login_streak']; ?></td>
                                            <td>
                                                <?php if ($customer['abandoned_carts_count'] > 0): ?>
                                                    <span style="color: #dc3545;"><?php echo $customer['abandoned_carts_count']; ?></span>
                                                <?php else: ?>
                                                    <span style="color: #28a745;">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $customer['points']; ?></td>
                                            <td>
                                               <button class="btn-small" onclick="sendRetentionOffer(<?php echo $customer['user_id']; ?>, 'high')">
                                                    Send Offer
                                                </button>

                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center; padding: 20px;">
                                                🎉 No high-risk customers found! Your retention strategies are working well.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- NEW: Medium Risk Customers Section -->
                    <div class="analytics-card">
                        <div class="card-title">🟡 Medium-Risk Customers - Send Targeted Offers</div>
                        <div class="table-container">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Churn Probability</th>
                                        <th>Last Login</th>
                                        <th>Last Order</th>
                                        <th>Total Orders</th>
                                        <th>Login Streak</th>
                                        <th>Abandoned Carts</th>
                                        <th>Points</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($medium_risk_customers)): ?>
                                        <?php foreach ($medium_risk_customers as $customer): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($customer['email']); ?></small><br>
                                                <small class="membership-badge"><?php echo $customer['membership_type']; ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge risk-medium">
                                                    <?php echo number_format($customer['churn_probability'] * 100, 1); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $customer['last_login_date'] ? date('M j, Y', strtotime($customer['last_login_date'])) : 'Never'; ?>
                                                <?php if ($customer['last_login_date']): ?>
                                                    <br><small>(<?php echo floor((time() - strtotime($customer['last_login_date'])) / (60 * 60 * 24)); ?> days ago)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $customer['last_order_date'] ? date('M j, Y', strtotime($customer['last_order_date'])) : 'No orders'; ?>
                                                <?php if ($customer['last_order_date']): ?>
                                                    <br><small>(<?php echo floor((time() - strtotime($customer['last_order_date'])) / (60 * 60 * 24)); ?> days ago)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $customer['total_orders'] ?? 0; ?></td>
                                            <td><?php echo $customer['login_streak']; ?></td>
                                            <td>
                                                <?php if ($customer['abandoned_carts_count'] > 0): ?>
                                                    <span style="color: #ffc107;"><?php echo $customer['abandoned_carts_count']; ?></span>
                                                <?php else: ?>
                                                    <span style="color: #28a745;">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $customer['points']; ?></td>
                                            <td>
                                                <button class="btn-small btn-warning" onclick="sendRetentionOffer(<?php echo $customer['user_id']; ?>, 'medium')">
                                                    Send Offer
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center; padding: 20px;">
                                                ✅ No medium-risk customers found at the moment.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2: Discount Performance Analytics -->
    <div id="discount-analytics" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🎁 Discount Performance Analytics</h2>
                <button class="close-modal" onclick="closeModal('discount-analytics')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($discount_overview['total_discounts'] ?? 0); ?></div>
                        <div class="stat-label">Total Discounts Issued</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($discount_overview['used_discounts'] ?? 0); ?></div>
                        <div class="stat-label">Discounts Used</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($discount_overview['active_discounts'] ?? 0); ?></div>
                        <div class="stat-label">Active Discounts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">₱<?php echo number_format($discount_overview['total_discount_value'] ?? 0, 2); ?></div>
                        <div class="stat-label">Total Discount Value</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">₱<?php echo number_format($discount_overview['avg_discount_amount'] ?? 0, 2); ?></div>
                        <div class="stat-label">Avg Discount Amount</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                            $usage_rate = $discount_overview['total_discounts'] > 0 ? 
                                ($discount_overview['used_discounts'] / $discount_overview['total_discounts']) * 100 : 0;
                            echo number_format($usage_rate, 1); ?>%
                        </div>
                        <div class="stat-label">Overall Usage Rate</div>
                    </div>
                </div>

                <div class="analytics-card" style="margin-bottom: 30px;">
                    <div class="card-title">📊 Discount Type Performance</div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Discount Type</th>
                                    <th>Issued</th>
                                    <th>Used</th>
                                    <th>Usage Rate</th>
                                    <th>Total Value</th>
                                    <th>Avg. Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($discount_types as $type): ?>
                                <tr>
                                    <td>
                                        <span class="status-badge">
                                            <?php echo ucfirst($type['discount_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $type['total_issued']; ?></td>
                                    <td><?php echo $type['used_count']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $type['usage_rate'] > 50 ? '#28a745' : ($type['usage_rate'] > 25 ? '#ffc107' : '#dc3545'); ?>">
                                            <?php echo number_format($type['usage_rate'], 1); ?>%
                                        </span>
                                    </td>
                                    <td>₱<?php echo number_format($type['total_discount_value'], 2); ?></td>
                                    <td>₱<?php echo number_format($type['avg_amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analytics-card" style="margin-bottom: 30px;">
                    <div class="card-title">📈 Discount Status Overview</div>
                    <div class="status-bars">
                        <?php 
                        $total_discounts = $discount_overview['total_discounts'] ?? 1;
                        foreach ($discount_status as $status): 
                            $percentage = ($status['count'] / $total_discounts) * 100;
                            $color = '';
                            switch($status['status']) {
                                case 'Used': $color = '#28a745'; break;
                                case 'Active': $color = '#17a2b8'; break;
                                case 'Expired': $color = '#dc3545'; break;
                                default: $color = '#6c757d';
                            }
                        ?>
                        <div class="status-bar">
                            <div class="status-label"><?php echo $status['status']; ?> Discounts</div>
                            <div class="status-progress">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>;"></div>
                            </div>
                            <div class="status-count"><?php echo $status['count']; ?> (<?php echo number_format($percentage, 1); ?>%)</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-title">🏆 Top Discounted Products</div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Discounts Count</th>
                                    <th>Total Discount Value</th>
                                    <th>Avg Discount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_discounted_products)): ?>
                                    <?php foreach ($top_discounted_products as $product): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></small></td>
                                        <td><?php echo $product['discount_count']; ?></td>
                                        <td>₱<?php echo number_format($product['total_discount_value'], 2); ?></td>
                                        <td>₱<?php echo number_format($product['avg_discount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align: center;">No discount data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 3: Sales Trends Analysis -->
    <div id="sales-trends" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📊 Sales Trends Analysis</h2>
                <button class="close-modal" onclick="closeModal('sales-trends')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="trends-container">
                    <div class="trends-header">
                        <div class="trends-title">
                            📊 Monthly Sales Trends (Last 12 Months)
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #4a7c2c;">
                        <p style="margin: 0; line-height: 1.6; color: #4a5568;">
                            <?php 
                            if (!empty($monthly_trends_with_growth)) {
                                echo getTrendDescription(
                                    $monthly_trends_with_growth[0]['revenue_growth'], 
                                    $monthly_trends_with_growth[0]['orders_growth'],
                                    $monthly_trends_with_growth[0]['monthly_revenue'],
                                    isset($monthly_trends_with_growth[1]) ? $monthly_trends_with_growth[1]['monthly_revenue'] : 0
                                );
                            }
                            ?>
                        </p>
                    </div>

                    <div class="trend-chart">
                        <?php 
                        $max_revenue = !empty($monthly_trends_with_growth) ? max(array_column($monthly_trends_with_growth, 'monthly_revenue')) : 1;
                        foreach (array_reverse($monthly_trends_with_growth) as $trend): 
                            $height = ($trend['monthly_revenue'] / $max_revenue) * 250;
                            $growth_class = $trend['revenue_growth'] > 0 ? 'growth-positive' : ($trend['revenue_growth'] < 0 ? 'growth-negative' : 'growth-neutral');
                        ?>
                        <div class="trend-bar">
                            <div class="trend-value">₱<?php echo number_format($trend['monthly_revenue']/1000, 1); ?>k</div>
                            <div class="trend-bar-visual" style="height: <?php echo $height; ?>px;" 
                                 title="<?php echo $trend['month_name'] . ' ' . $trend['year']; ?>: ₱<?php echo number_format($trend['monthly_revenue'], 2); ?>"></div>
                            <div class="trend-label"><?php echo substr($trend['month_name'], 0, 3) . ' ' . $trend['year']; ?></div>
                            <?php if ($trend['revenue_growth'] != 0): ?>
                            <div class="growth-indicator <?php echo $growth_class; ?>">
                                <?php echo ($trend['revenue_growth'] > 0 ? '+' : '') . number_format($trend['revenue_growth'], 1); ?>%
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="monthly-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                        <?php 
                        $recent_month = !empty($monthly_trends_with_growth) ? $monthly_trends_with_growth[0] : null;
                        $total_year_revenue = array_sum(array_column($monthly_trends_with_growth, 'monthly_revenue'));
                        $avg_monthly_revenue = count($monthly_trends_with_growth) > 0 ? $total_year_revenue / count($monthly_trends_with_growth) : 0;
                        ?>
                        <div class="summary-card" style="background: var(--light-bg); padding: 15px; border-radius: 8px; border-left: 4px solid var(--medium-green);">
                            <div class="summary-title" style="font-size: 14px; color: var(--text-light); margin-bottom: 5px;">Current Month Revenue</div>
                            <div class="summary-value" style="font-size: 20px; font-weight: bold; color: var(--text-dark);">₱<?php echo number_format($recent_month['monthly_revenue'] ?? 0, 2); ?></div>
                        </div>
                        <div class="summary-card" style="background: var(--light-bg); padding: 15px; border-radius: 8px; border-left: 4px solid var(--medium-green);">
                            <div class="summary-title" style="font-size: 14px; color: var(--text-light); margin-bottom: 5px;">Average Monthly Revenue</div>
                            <div class="summary-value" style="font-size: 20px; font-weight: bold; color: var(--text-dark);">₱<?php echo number_format($avg_monthly_revenue, 2); ?></div>
                        </div>
                        <div class="summary-card" style="background: var(--light-bg); padding: 15px; border-radius: 8px; border-left: 4px solid var(--medium-green);">
                            <div class="summary-title" style="font-size: 14px; color: var(--text-light); margin-bottom: 5px;">Total Year Revenue</div>
                            <div class="summary-value" style="font-size: 20px; font-weight: bold; color: var(--text-dark);">₱<?php echo number_format($total_year_revenue, 2); ?></div>
                        </div>
                        <div class="summary-card" style="background: var(--light-bg); padding: 15px; border-radius: 8px; border-left: 4px solid var(--medium-green);">
                            <div class="summary-title" style="font-size: 14px; color: var(--text-light); margin-bottom: 5px;">Revenue Growth</div>
                            <div class="summary-value" style="font-size: 20px; font-weight: bold; color: <?php echo ($recent_month['revenue_growth'] ?? 0) >= 0 ? '#28a745' : '#dc3545'; ?>">
                                <?php echo ($recent_month['revenue_growth'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($recent_month['revenue_growth'] ?? 0, 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 4: Customer Demographics -->
    <div id="customer-demographics" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👥 Customer Demographics</h2>
                <button class="close-modal" onclick="closeModal('customer-demographics')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-card" style="margin-bottom: 30px;">
                    <div class="card-title">📊 Customer Age Group Distribution & Spending</div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Age Group</th>
                                    <th>Customers</th>
                                    <th>Total Orders</th>
                                    <th>Total Spent</th>
                                    <th>Avg Order Value</th>
                                    <th>Spending per Customer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
// Add this at line ~559
$total_age_customers = !empty($age_groups) ? array_sum(array_column($age_groups, 'customer_count')) : 0;
                                foreach ($age_groups as $age): 
                                    $percentage = $total_age_customers > 0 ? ($age['customer_count'] / $total_age_customers) * 100 : 0;
                                    $spending_per_customer = $age['customer_count'] > 0 ? $age['total_spent'] / $age['customer_count'] : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($age['age_group']); ?></strong></td>
                                    <td>
                                        <?php echo number_format($age['customer_count']); ?>
                                        <small style="color: var(--text-light);"> (<?php echo number_format($percentage, 1); ?>%)</small>
                                    </td>
                                    <td><?php echo number_format($age['total_orders']); ?></td>
                                    <td>₱<?php echo number_format($age['total_spent'], 2); ?></td>
                                    <td>₱<?php echo number_format($age['avg_order_value'], 2); ?></td>
                                    <td>₱<?php echo number_format($spending_per_customer, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analytics-card" style="margin-bottom: 30px;">
                    <div class="card-title">🎯 Top Products by Age Group</div>
                    <div class="details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <?php 
                        $products_by_age = [];
                        foreach ($age_product_trends as $trend) {
                            $products_by_age[$trend['age_group']][] = $trend;
                        }
                        
                        foreach ($products_by_age as $age_group => $products): 
                            $top_5 = array_slice($products, 0, 5);
                        ?>
                        <div class="detail-section" style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <div class="detail-title" style="font-size: 16px; font-weight: bold; margin-bottom: 15px; color: var(--text-dark);">
                                <?php 
                                $age_icon = '';
                                switch($age_group) {
                                    case 'Under 18': $age_icon = '👶'; break;
                                    case '18-24': $age_icon = '🧑'; break;
                                    case '25-34': $age_icon = '👨‍💼'; break;
                                    case '35-44': $age_icon = '👨‍🦰'; break;
                                    case '45-54': $age_icon = '👨‍🦳'; break;
                                    case '55+': $age_icon = '👴'; break;
                                    default: $age_icon = '❓';
                                }
                                echo $age_icon . ' ' . htmlspecialchars($age_group);
                                ?>
                            </div>
                            <ul class="detail-list" style="list-style: none; padding: 0;">
                                <?php foreach ($top_5 as $product): ?>
                                <li style="padding: 8px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                                    <span>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></small>
                                    </span>
                                    <span>
                                        <?php echo $product['total_quantity']; ?> units<br>
                                        <small>₱<?php echo number_format($product['total_revenue'], 2); ?></small>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-title">👥 Customer Membership Distribution</div>
                    <div class="membership-chart" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                        <?php foreach ($membership_data as $membership): ?>
                        <div class="membership-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px; text-align: center;">
                            <div class="membership-count" style="font-size: 24px; font-weight: bold; color: var(--dark-green);"><?php echo $membership['count']; ?></div>
                            <div class="membership-type" style="color: var(--text-light); text-transform: capitalize;"><?php echo str_replace('_', ' ', $membership['membership_type']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 5: Product Performance -->
    <div id="product-performance" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🏆 Product Performance</h2>
                <button class="close-modal" onclick="closeModal('product-performance')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-row">
                    <div class="analytics-card">
                        <div class="card-title">🏆 Top 10 Selling Products</div>
                        <div class="table-container">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Qty Sold</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($top_products)): ?>
                                        <?php $rank = 1; foreach ($top_products as $product): ?>
                                        <tr>
                                            <td>
                                                <span style="display: inline-block; background: linear-gradient(135deg, #2d5016, #4a7c2c); color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold;">
                                                    #<?php echo $rank++; ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                            <td><small><?php echo htmlspecialchars($product['category_name']); ?></small></td>
                                            <td><strong style="color: #4a7c2c;"><?php echo number_format($product['total_sold']); ?></strong></td>
                                            <td><?php echo number_format($product['order_count']); ?></td>
                                            <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" style="text-align: center;">No sales data available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="analytics-card">
                        <div class="card-title">📉 Least 10 Selling Products</div>
                        <div class="table-container">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Qty Sold</th>
                                        <th>Orders</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($least_products)): ?>
                                        <?php foreach ($least_products as $product): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                            <td><small><?php echo htmlspecialchars($product['category_name']); ?></small></td>
                                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                            <td>
                                                <strong style="color: <?php echo $product['total_sold'] == 0 ? '#dc3545' : '#ffc107'; ?>;">
                                                    <?php echo number_format($product['total_sold']); ?>
                                                </strong>
                                            </td>
                                            <td><?php echo number_format($product['order_count']); ?></td>
                                            <td>
                                                <span class="status-badge" style="background: <?php echo $product['total_sold'] == 0 ? '#f8d7da' : '#fff3cd'; ?>; color: <?php echo $product['total_sold'] == 0 ? '#721c24' : '#856404'; ?>;">
                                                    <?php echo $product['total_sold'] == 0 ? 'No Sales' : 'Low Sales'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" style="text-align: center;">No product data available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                            <strong>💡 Recommendation:</strong> Consider promotional campaigns or inventory adjustments for low-performing products.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Continue with remaining modals 6-16 following the same pattern -->
    <!-- Due to character limits, I'll show the structure for one more modal -->

    <!-- Modal 6: Category Performance -->
    <div id="category-performance" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📂 Category Performance</h2>
                <button class="close-modal" onclick="closeModal('category-performance')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-card">
                    <div class="card-title">Category Performance Analysis</div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Products Count</th>
                                    <th>Total Sold</th>
                                    <th>Average Price</th>
                                    <th>Performance Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category_performance as $category): 
                                    $performance_score = ($category['total_sold'] ?? 0) * ($category['avg_price'] ?? 0) / 100;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                    <td><?php echo $category['product_count']; ?></td>
                                    <td><?php echo $category['total_sold'] ?? 0; ?></td>
                                    <td>₱<?php echo number_format($category['avg_price'] ?? 0, 2); ?></td>
                                    <td>
                                        <div class="performance-score">
                                            <?php echo number_format($performance_score, 1); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 7: Customer Growth Analytics -->
    <div id="customer-growth" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📈 Customer Growth Analytics</h2>
                <button class="close-modal" onclick="closeModal('customer-growth')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="trends-container">
                    <div class="trends-header">
                        <div class="trends-title">📈 Customer Growth Trends (Last 12 Months)</div>
                    </div>
                    <div class="trend-chart">
                        <?php 
                        $max_customers = !empty($customer_growth) ? max(array_column($customer_growth, 'new_customers')) : 1;
                        foreach (array_reverse($customer_growth) as $growth): 
                            $height = ($growth['new_customers'] / $max_customers) * 250;
                        ?>
                        <div class="trend-bar">
                            <div class="trend-value"><?php echo $growth['new_customers']; ?></div>
                            <div class="trend-bar-visual" style="height: <?php echo $height; ?>px; background: linear-gradient(to top, #17a2b8, #138496);" 
                                 title="<?php echo $growth['month_name'] . ' ' . $growth['year']; ?>: <?php echo $growth['new_customers']; ?> new customers"></div>
                            <div class="trend-label"><?php echo substr($growth['month_name'], 0, 3) . ' ' . $growth['year']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 8: Order Status Distribution -->
    <div id="order-status" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📦 Order Status Distribution</h2>
                <button class="close-modal" onclick="closeModal('order-status')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-card">
                    <div class="card-title">Order Status Distribution</div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_orders_for_percent = array_sum(array_column($order_status_result, 'count'));
                                foreach ($order_status_result as $status): 
                                    $percentage = ($status['count'] / $total_orders_for_percent) * 100;
                                    $status_class = 'status-' . str_replace(['_', ' '], ['-', '-'], strtolower($status['status']));
                                ?>
                                <tr>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo str_replace('_', ' ', $status['status']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $status['count']; ?></strong></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 9: Retention Offers Analytics -->
    <div id="retention-offers" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🎯 Retention Offers Analytics</h2>
                <button class="close-modal" onclick="closeModal('retention-offers')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-card">
                    <div class="card-title">Retention Offers Performance</div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Offer Type</th>
                                    <th>Total Offers</th>
                                    <th>Sent</th>
                                    <th>Opened</th>
                                    <th>Converted</th>
                                    <th>Conversion Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($retention_offers as $offer): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($offer['offer_type']); ?></strong></td>
                                    <td><?php echo $offer['total_offers']; ?></td>
                                    <td><?php echo $offer['sent_offers']; ?></td>
                                    <td><?php echo $offer['opened_offers']; ?></td>
                                    <td><?php echo $offer['converted_offers']; ?></td>
                                    <td><?php echo $offer['conversion_rate']; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 10: Gender-Based Analytics -->
    <div id="gender-analytics" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👨‍👩‍👧‍👦 Gender-Based Analytics</h2>
                <button class="close-modal" onclick="closeModal('gender-analytics')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-card" style="margin-bottom: 30px;">
                    <div class="card-title">👥 Customer Gender Distribution</div>
                    <div class="gender-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                        <?php foreach ($gender_distribution as $gender_data): 
                            $gender_icon = '';
                            $gender_color = '';
                            switch($gender_data['gender']) {
                                case 'male':
                                    $gender_icon = '👨';
                                    $gender_color = '#3498db';
                                    break;
                                case 'female':
                                    $gender_icon = '👩';
                                    $gender_color = '#e91e63';
                                    break;
                                case 'other':
                                    $gender_icon = '🧑';
                                    $gender_color = '#9c27b0';
                                    break;
                                default:
                                    $gender_icon = '❓';
                                    $gender_color = '#95a5a6';
                            }
                        ?>
                        <div class="gender-stat-card" style="background: var(--light-bg); padding: 20px; border-radius: 8px; border-left: 4px solid <?php echo $gender_color; ?>;">
                            <div style="font-size: 30px; margin-bottom: 10px;"><?php echo $gender_icon; ?></div>
                            <div style="font-size: 14px; color: var(--text-light); text-transform: capitalize;">
                                <?php echo $gender_data['gender'] ?? 'Not Specified'; ?>
                            </div>
                            <div style="font-size: 24px; font-weight: bold; color: var(--text-dark); margin: 10px 0;">
                                <?php echo number_format($gender_data['customer_count']); ?>
                            </div>
                            <div style="font-size: 12px; color: var(--text-light);">
                                <?php 
                                $total_customers = array_sum(array_column($gender_distribution, 'customer_count'));
                                $percentage = ($gender_data['customer_count'] / $total_customers) * 100;
                                echo number_format($percentage, 1) . '% of customers';
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 11: Monthly Product Trends -->
    <div id="monthly-product-trends" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📅 Monthly Product Trends</h2>
                <button class="close-modal" onclick="closeModal('monthly-product-trends')">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (!empty($monthly_product_trends)): ?>
                <div class="trends-container">
                    <div class="trends-header">
                        <div class="trends-title">
                            🏆 Top Products Monthly Performance (Last 6 Months)
                        </div>
                    </div>
                    
                    <div class="details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <?php 
                        $products_by_month = [];
                        foreach ($monthly_product_trends as $product) {
                            $month_key = $product['month_name'] . ' ' . $product['year'];
                            $products_by_month[$month_key][] = $product;
                        }
                        
                        $month_count = 0;
                        foreach ($products_by_month as $month => $products): 
                            if ($month_count >= 3) break;
                        ?>
                        <div class="detail-section" style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <div class="detail-title" style="font-size: 16px; font-weight: bold; margin-bottom: 15px; color: var(--text-dark);"><?php echo $month; ?></div>
                            <ul class="detail-list" style="list-style: none; padding: 0;">
                                <?php 
                                $top_products_month = array_slice($products, 0, 5);
                                foreach ($top_products_month as $product): 
                                ?>
                                <li style="padding: 8px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                                    <span>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($product['category_name']); ?></small>
                                    </span>
                                    <span>
                                        <?php echo $product['units_sold']; ?> units<br>
                                        <small>₱<?php echo number_format($product['product_revenue'], 2); ?></small>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php 
                        $month_count++;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal 12: Category Trends -->
    <div id="category-trends" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📊 Category Trends</h2>
                <button class="close-modal" onclick="closeModal('category-trends')">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (!empty($monthly_category_trends)): ?>
                <div class="trends-container">
                    <div class="trends-header">
                        <div class="trends-title">
                            📂 Category Performance Trends (Last 6 Months)
                        </div>
                    </div>
                    
                    <div class="details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <?php 
                        $categories_by_month = [];
                        foreach ($monthly_category_trends as $category) {
                            $month_key = $category['month_name'] . ' ' . $category['year'];
                            $categories_by_month[$month_key][] = $category;
                        }
                        
                        $month_count = 0;
                        foreach ($categories_by_month as $month => $categories): 
                            if ($month_count >= 3) break;
                        ?>
                        <div class="detail-section" style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <div class="detail-title" style="font-size: 16px; font-weight: bold; margin-bottom: 15px; color: var(--text-dark);"><?php echo $month; ?></div>
                            <ul class="detail-list" style="list-style: none; padding: 0;">
                                <?php foreach ($categories as $category): ?>
                                <li style="padding: 8px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                                    <span>
                                        <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                    </span>
                                    <span>
                                        <?php echo $category['units_sold']; ?> units<br>
                                        <small>₱<?php echo number_format($category['category_revenue'], 2); ?></small>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php 
                        $month_count++;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal 13: Basket Size Analysis -->
    <div id="basket-analysis" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🛒 Basket Size Analysis</h2>
                <button class="close-modal" onclick="closeModal('basket-analysis')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($products_per_order, 1); ?></div>
                        <div class="stat-label">Avg Products per Order</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($orders_per_customer, 2); ?></div>
                        <div class="stat-label">Orders per Customer</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">₱<?php echo number_format($avg_order_value, 2); ?></div>
                        <div class="stat-label">Average Order Value</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">₱<?php echo number_format($revenue_per_customer, 2); ?></div>
                        <div class="stat-label">Revenue per Customer</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 14: Customer Engagement Metrics -->
    <div id="engagement-metrics" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>💫 Customer Engagement Metrics</h2>
                <button class="close-modal" onclick="closeModal('engagement-metrics')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-card">
                    <div class="card-title">Customer Engagement Overview</div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Value</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Average Login Streak</strong></td>
                                    <td><?php echo !empty($churn_factors) ? number_format($churn_factors[0]['avg_login_streak'] ?? 0, 1) : '0'; ?> days</td>
                                    <td>Average consecutive days customers log in</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Points Issued</strong></td>
                                    <td><?php echo !empty($gender_distribution) ? number_format(array_sum(array_column($gender_distribution, 'total_points'))) : '0'; ?></td>
                                    <td>Total loyalty points distributed to customers</td>
                                </tr>
                                <tr>
                                    <td><strong>Orders per Customer</strong></td>
                                    <td><?php echo number_format($orders_per_customer, 2); ?></td>
                                    <td>Average number of orders per customer</td>
                                </tr>
                                <tr>
                                    <td><strong>Products per Order</strong></td>
                                    <td><?php echo number_format($products_per_order, 1); ?></td>
                                    <td>Average number of items in each order</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 15: Business Health Assessment -->
    <div id="business-health" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>❤️ Business Health Assessment</h2>
                <button class="close-modal" onclick="closeModal('business-health')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="insight-card" style="background: <?php 
                    echo $business_health == 'excellent' ? 'linear-gradient(135deg, #d4edda, #c3e6cb)' : 
                        ($business_health == 'good' ? 'linear-gradient(135deg, #d1ecf1, #bee5eb)' : 
                        ($business_health == 'needs_attention' ? 'linear-gradient(135deg, #fff3cd, #ffeaa7)' : 
                        'linear-gradient(135deg, #f8f9fa, #e9ecef)')); 
                ?>; padding: 25px; border-radius: 12px; margin-bottom: 20px; border-left: 5px solid <?php 
                    echo $business_health == 'excellent' ? '#28a745' : 
                        ($business_health == 'good' ? '#17a2b8' : 
                        ($business_health == 'needs_attention' ? '#ffc107' : '#6c757d')); 
                ?>;">
                    <h3 style="margin: 0 0 15px 0; color: #2d3748; display: flex; align-items: center;">
                        <span style="font-size: 28px; margin-right: 10px;">
                            <?php 
                            echo $business_health == 'excellent' ? '🎯' : 
                                ($business_health == 'good' ? '✅' : 
                                ($business_health == 'needs_attention' ? '⚠️' : '📊')); 
                            ?>
                        </span>
                        Overall Business Health: 
                        <span style="margin-left: 10px; text-transform: capitalize; color: <?php 
                            echo $business_health == 'excellent' ? '#28a745' : 
                                ($business_health == 'good' ? '#17a2b8' : 
                                                                ($business_health == 'needs_attention' ? '#856404' : '#6c757d')); 
                        ?>;">
                            <?php echo str_replace('_', ' ', ucwords($business_health)); ?>
                        </span>
                    </h3>
                    <p style="margin: 0; line-height: 1.6; color: #4a5568;">
                        <?php if ($business_health == 'excellent'): ?>
                            Your business is performing exceptionally well! Revenue is up <?php echo number_format(abs($revenue_trend), 1); ?>% and orders increased by <?php echo number_format(abs($orders_trend), 1); ?>% compared to last month. Keep up the great work and consider expanding your successful strategies.
                        <?php elseif ($business_health == 'good'): ?>
                            Your business shows positive growth with revenue <?php echo $revenue_trend >= 0 ? 'increasing' : 'stable'; ?> and consistent order flow. Focus on maintaining this momentum while exploring opportunities to accelerate growth.
                        <?php elseif ($business_health == 'needs_attention'): ?>
                            Your business metrics indicate areas that need attention. Revenue trend: <?php echo number_format($revenue_trend, 1); ?>%, Orders trend: <?php echo number_format($orders_trend, 1); ?>%. Review the detailed analytics below to identify improvement opportunities.
                        <?php else: ?>
                            Your business is maintaining stable performance. Monitor key metrics closely and implement strategies to drive growth in revenue and customer acquisition.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="insights-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="insight-card" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <h4 style="color: #2d5016; margin: 0 0 10px 0; display: flex; align-items: center;">
                            <span style="font-size: 24px; margin-right: 8px;">💰</span>
                            Revenue Efficiency
                        </h4>
                        <div style="font-size: 28px; font-weight: bold; color: #2d5016; margin: 10px 0;">
                            ₱<?php echo number_format($revenue_per_customer, 2); ?>
                        </div>
                        <p style="color: #718096; font-size: 14px; margin: 0;">Revenue per Customer</p>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #4a5568;">
                                <?php if ($revenue_per_customer > $avg_order_value * 2): ?>
                                    Excellent customer lifetime value! Your customers are highly engaged and making multiple purchases.
                                <?php elseif ($revenue_per_customer > $avg_order_value): ?>
                                    Good customer retention. Consider loyalty programs to increase repeat purchases.
                                <?php else: ?>
                                    Focus on customer retention strategies to increase lifetime value.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="insight-card" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <h4 style="color: #2d5016; margin: 0 0 10px 0; display: flex; align-items: center;">
                            <span style="font-size: 24px; margin-right: 8px;">🛒</span>
                            Customer Engagement
                        </h4>
                        <div style="font-size: 28px; font-weight: bold; color: #2d5016; margin: 10px 0;">
                            <?php echo number_format($orders_per_customer, 2); ?>
                        </div>
                        <p style="color: #718096; font-size: 14px; margin: 0;">Orders per Customer</p>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #4a5568;">
                                <?php if ($orders_per_customer >= 3): ?>
                                    Outstanding! High repeat purchase rate indicates strong customer loyalty.
                                <?php elseif ($orders_per_customer >= 1.5): ?>
                                    Moderate engagement. Implement email campaigns to encourage repeat orders.
                                <?php else: ?>
                                    Most customers are one-time buyers. Focus on post-purchase engagement.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="insight-card" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <h4 style="color: #2d5016; margin: 0 0 10px 0; display: flex; align-items: center;">
                            <span style="font-size: 24px; margin-right: 8px;">📦</span>
                            Basket Size
                        </h4>
                        <div style="font-size: 28px; font-weight: bold; color: #2d5016; margin: 10px 0;">
                            <?php echo number_format($products_per_order, 1); ?>
                        </div>
                        <p style="color: #718096; font-size: 14px; margin: 0;">Products per Order</p>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #4a5568;">
                                <?php if ($products_per_order >= 3): ?>
                                    Great cross-selling performance! Customers are buying multiple items.
                                <?php elseif ($products_per_order >= 2): ?>
                                    Decent basket size. Test product bundling to increase items per order.
                                <?php else: ?>
                                    Low basket size. Implement "Frequently Bought Together" recommendations.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-title">📊 Key Performance Indicators</div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Current Value</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Monthly Revenue Growth</strong></td>
                                    <td><?php echo number_format($recent_month['revenue_growth'] ?? 0, 1); ?>%</td>
                                    <td>> 10%</td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo ($recent_month['revenue_growth'] ?? 0) >= 10 ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo ($recent_month['revenue_growth'] ?? 0) >= 10 ? '#155724' : '#721c24'; ?>;">
                                            <?php echo ($recent_month['revenue_growth'] ?? 0) >= 10 ? 'On Target' : 'Needs Improvement'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="growth-indicator <?php echo ($recent_month['revenue_growth'] ?? 0) >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                            <?php echo ($recent_month['revenue_growth'] ?? 0) >= 0 ? '📈' : '📉'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Customer Churn Rate</strong></td>
                                    <td><?php echo number_format($churn_overview['avg_churn_rate'] ?? 0, 1); ?>%</td>
                                    <td>< 15%</td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo ($churn_overview['avg_churn_rate'] ?? 0) < 15 ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo ($churn_overview['avg_churn_rate'] ?? 0) < 15 ? '#155724' : '#721c24'; ?>;">
                                            <?php echo ($churn_overview['avg_churn_rate'] ?? 0) < 15 ? 'Healthy' : 'High'; ?>
                                        </span>
                                    </td>
                                    <td>📊</td>
                                </tr>
                                <tr>
                                    <td><strong>Average Order Value</strong></td>
                                    <td>₱<?php echo number_format($avg_order_value, 2); ?></td>
                                    <td>> ₱500</td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo $avg_order_value > 500 ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo $avg_order_value > 500 ? '#155724' : '#856404'; ?>;">
                                            <?php echo $avg_order_value > 500 ? 'Excellent' : 'Good'; ?>
                                        </span>
                                    </td>
                                    <td>💰</td>
                                </tr>
                                <tr>
                                    <td><strong>Customer Satisfaction</strong></td>
                                    <td><?php echo number_format($orders_per_customer * 20, 0); ?>%</td>
                                    <td>> 80%</td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo ($orders_per_customer * 20) >= 80 ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo ($orders_per_customer * 20) >= 80 ? '#155724' : '#856404'; ?>;">
                                            <?php echo ($orders_per_customer * 20) >= 80 ? 'High' : 'Moderate'; ?>
                                        </span>
                                    </td>
                                    <td>😊</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 16: Daily Sales Analytics -->
    <div id="daily-sales" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📆 Daily Sales Analytics</h2>
                <button class="close-modal" onclick="closeModal('daily-sales')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-card">
                    <div class="card-title">Daily Sales Trend (Last 7 Days)</div>
                    <?php if (!empty($daily_sales)): ?>
                    <div class="chart-container" style="display: flex; align-items: flex-end; gap: 15px; padding: 20px 0; overflow-x: auto; min-height: 300px;">
                        <?php 
                        $max_revenue = max(array_column($daily_sales, 'daily_revenue'));
                        foreach (array_reverse($daily_sales) as $day): 
                            $height = ($day['daily_revenue'] / $max_revenue) * 250;
                        ?>
                        <div class="bar" style="display: flex; flex-direction: column; align-items: center; min-width: 80px;">
                            <div class="bar-value" style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">₱<?php echo number_format($day['daily_revenue']); ?></div>
                            <div class="bar-visual" style="width: 40px; background: linear-gradient(to top, var(--light-green), var(--dark-green)); border-radius: 3px 3px 0 0; height: <?php echo $height; ?>px; transition: all 0.3s;"></div>
                            <div class="bar-label" style="font-size: 11px; color: var(--text-light); margin-top: 10px;"><?php echo date('M j', strtotime($day['sale_date'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="stats-grid" style="margin-top: 30px;">
                        <div class="stat-card">
                            <div class="stat-number">₱<?php echo number_format(array_sum(array_column($daily_sales, 'daily_revenue')), 2); ?></div>
                            <div class="stat-label">Total Weekly Revenue</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format(array_sum(array_column($daily_sales, 'orders_count'))); ?></div>
                            <div class="stat-label">Total Weekly Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">₱<?php echo number_format(array_sum(array_column($daily_sales, 'daily_revenue')) / count($daily_sales), 2); ?></div>
                            <div class="stat-label">Average Daily Revenue</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format(array_sum(array_column($daily_sales, 'orders_count')) / count($daily_sales), 1); ?></div>
                            <div class="stat-label">Average Daily Orders</div>
                        </div>
                    </div>

                    <div class="analytics-card" style="margin-top: 20px;">
                        <div class="card-title">📈 Daily Sales Breakdown</div>
                        <div class="table-container">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                        <th>Avg Order Value</th>
                                        <th>Day of Week</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_reverse($daily_sales) as $day): ?>
                                    <tr>
                                        <td><strong><?php echo date('M j, Y', strtotime($day['sale_date'])); ?></strong></td>
                                        <td><?php echo $day['orders_count']; ?></td>
                                        <td>₱<?php echo number_format($day['daily_revenue'], 2); ?></td>
                                        <td>₱<?php echo number_format($day['daily_revenue'] / $day['orders_count'], 2); ?></td>
                                        <td><?php echo date('l', strtotime($day['sale_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 40px;">No sales data available for the last 7 days.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Create mobile menu toggle button if it doesn't exist
    if (!document.querySelector('.mobile-menu-toggle')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-menu-toggle';
        toggleBtn.innerHTML = '☰';
        toggleBtn.setAttribute('aria-label', 'Toggle Menu');
        document.body.insertBefore(toggleBtn, document.body.firstChild);
    }

    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    // Toggle sidebar on mobile
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        this.innerHTML = sidebar.classList.contains('active') ? '✕' : '☰';
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = menuToggle.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            menuToggle.innerHTML = '☰';
        }
    });

    // Close sidebar when clicking on a link (mobile)
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                menuToggle.innerHTML = '☰';
            }
        });
    });

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                menuToggle.innerHTML = '☰';
            }
        }, 250);
    });
});

// Modal Management Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Add animation class
        setTimeout(() => {
            modal.querySelector('.modal-content').style.opacity = '1';
        }, 10);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.querySelector('.modal-content').style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        const modalContent = event.target.querySelector('.modal-content');
        modalContent.style.opacity = '0';
        setTimeout(() => {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.style.display === 'block') {
                const modalContent = modal.querySelector('.modal-content');
                modalContent.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }, 300);
            }
        });
    }
});

// Send Retention Offer Function - FIXED VERSION
function sendRetentionOffer(userId, riskLevel) {
    const riskText = riskLevel === 'high' ? 'high-risk' : 'medium-risk';
    
    // More reliable button selection
    const buttons = document.querySelectorAll('button.btn-small');
    let targetButton = null;
    
    buttons.forEach(button => {
        if (button.textContent.includes('Send Offer') || button.getAttribute('onclick')?.includes(`sendRetentionOffer(${userId}`)) {
            targetButton = button;
        }
    });
    
    if (!targetButton) {
        alert('Error: Could not find the send button');
        return;
    }
    
    if (confirm(`Send a personalized retention offer to this ${riskText} customer?`)) {
        // Show loading state
        const originalText = targetButton.innerHTML;
        targetButton.innerHTML = '⏳ Sending...';
        targetButton.disabled = true;
        
        fetch('send_retention_offer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                user_id: userId,
                risk_level: riskLevel 
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('✓ Retention offer sent successfully to ' + (data.user_name || 'the customer') + '!');
                targetButton.innerHTML = '✓ Sent';
                targetButton.style.background = riskLevel === 'high' ? '#28a745' : '#ffc107';
                targetButton.style.color = '#fff';
                targetButton.disabled = true;
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            alert('✗ Error sending offer: ' + error.message);
            targetButton.innerHTML = originalText;
            targetButton.disabled = false;
        });
    }
}

// Alternative Send Retention Offer Function using event delegation (more reliable)
document.addEventListener('click', function(e) {
    // Check if the clicked element or its parent is a send offer button
    let button = e.target;
    if (!button.classList.contains('btn-small')) {
        button = e.target.closest('.btn-small');
    }
    
    if (button && (button.textContent.includes('Send Offer') || button.getAttribute('onclick')?.includes('sendRetentionOffer'))) {
        e.preventDefault();
        e.stopPropagation();
        
        const onclickAttr = button.getAttribute('onclick');
        if (onclickAttr && onclickAttr.includes('sendRetentionOffer')) {
            // Extract parameters from onclick attribute
            const match = onclickAttr.match(/sendRetentionOffer\((\d+),\s*'(\w+)'\)/);
            if (match) {
                const userId = parseInt(match[1]);
                const riskLevel = match[2];
                
                const riskText = riskLevel === 'high' ? 'high-risk' : 'medium-risk';
                if (confirm(`Send a personalized retention offer to this ${riskText} customer?`)) {
                    // Show loading state
                    button.innerHTML = '⏳ Sending...';
                    button.disabled = true;
                    
                    fetch('send_retention_offer.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            user_id: userId,
                            risk_level: riskLevel 
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✓ Retention offer sent successfully!');
                            button.innerHTML = '✓ Sent';
                            button.style.background = riskLevel === 'high' ? '#28a745' : '#ffc107';
                            button.style.color = '#fff';
                        } else {
                            alert('✗ Error sending offer: ' + data.message);
                            button.innerHTML = 'Send Offer';
                            button.disabled = false;
                        }
                    })
                    .catch(error => {
                        alert('✗ Error sending offer: ' + error);
                        button.innerHTML = 'Send Offer';
                        button.disabled = false;
                    });
                }
            }
        }
    }
});

// Interactive hover effects for cards
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && document.querySelector(href)) {
                e.preventDefault();
                document.querySelector(href).scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add intersection observer for fade-in animation
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe analytics cards
    document.querySelectorAll('.analytics-nav-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `all 0.5s ease ${index * 0.05}s`;
        observer.observe(card);
    });

    // Add click ripple effect
    document.querySelectorAll('.analytics-nav-card, .btn-small').forEach(element => {
        element.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.5);
                left: ${x}px;
                top: ${y}px;
                pointer-events: none;
                animation: ripple 0.6s ease-out;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Add CSS animation for ripple
    if (!document.querySelector('#ripple-style')) {
        const style = document.createElement('style');
        style.id = 'ripple-style';
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
});

// Tooltip functionality for truncated text
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.analytics-nav-card h3, .analytics-nav-card p').forEach(element => {
        if (element.scrollWidth > element.clientWidth) {
            element.title = element.textContent;
        }
    });
});
</script>
</body>
</html>