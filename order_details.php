<?php
session_start();
require_once 'include/config.php';

// Check if a user is logged in, otherwise redirect to login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_details = null;
$order_items = [];
$user = [];

// Fetch user details for the header.
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Check if an order_id is provided in the URL.
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = $_GET['order_id'];

    // Fetch the specific order details with enhanced discount information
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            u.full_name,
            u.membership_type,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE o.user_id = ? AND o.order_id = ?
    ");
    $stmt->execute([$user_id, $order_id]);
    $order_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order_details) {
        // Fetch all order items with discount information
        $items_stmt = $pdo->prepare("
            SELECT
                oi.quantity,
                oi.price as final_price,
                oi.was_discounted,
                p.name,
                p.price as original_price,
                p.image_path,
                (oi.price * oi.quantity) as subtotal,
                ((p.price * oi.quantity) - (oi.price * oi.quantity)) as discount_savings
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total savings
        $total_savings = 0;
        foreach ($order_items as $item) {
            $total_savings += $item['discount_savings'];
        }
        // Add other discounts
        $total_savings += $order_details['discount_amount'] + $order_details['retention_discount_amount'] + $order_details['points_discount'];
    }
} else {
    // If no order ID is specified, show a list of all orders.
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get cart count for the header.
$cart_count = 0;
if (isset($user['user_id'])) {
    $cart_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0) as total_items
        FROM carts c
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        WHERE c.user_id = ?
    ");
    $cart_stmt->execute([$user['user_id']]);
    $cart_result = $cart_stmt->fetch();
    $cart_count = $cart_result['total_items'] ?? 0;
}

// Get notifications for current user
$discount_notifications = getDiscountNotifications($pdo, $_SESSION['user_id']);
$notification_count = getNotificationCount($pdo, $_SESSION['user_id']);

// Function to get notification count for active discounts
function getNotificationCount($pdo, $user_id) {
    $current_time = date('Y-m-d H:i:s');
    
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as notification_count
        FROM user_discounts 
        WHERE user_id = ? 
        AND is_used = 0 
        AND (expires_at IS NULL OR expires_at > ?)
    ");
    
    $count_stmt->execute([$user_id, $current_time]);
    $result = $count_stmt->fetch();
    
    return $result['notification_count'] ?? 0;
}

// Function to get all active discount notifications
function getDiscountNotifications($pdo, $user_id) {
    $current_time = date('Y-m-d H:i:s');
    
    $notification_stmt = $pdo->prepare("
        SELECT ud.*, p.name as product_name
        FROM user_discounts ud
        LEFT JOIN products p ON ud.product_id = p.product_id
        WHERE ud.user_id = ? 
        AND ud.is_used = 0 
        AND (ud.expires_at IS NULL OR ud.expires_at > ?)
        ORDER BY ud.applied_at DESC
    ");
    
    $notification_stmt->execute([$user_id, $current_time]);
    $notifications = $notification_stmt->fetchAll();
    
    return $notifications;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Coopamart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        /* Your existing CSS styles here - preserved as requested */
        :root {
            --primary-color: #28a745;
            --primary-dark: #218838;
            --primary-light: #d4edda;
            --secondary-color: #6c757d;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-color: #343a40;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-strong: rgba(0, 0, 0, 0.15);
            --info-color: #172b4d;
            --error-color: #ef4444;
            --border-color: #e2e8f0;
            --text-dark: #1a202c;
            --text-light: #4a5568;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --card-bg: linear-gradient(135deg, white 0%, #f8fafc 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Include all your existing CSS styles here */
        /* ... rest of your CSS ... */
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 5px rgba(220, 53, 69, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }

        .header-icon-link {
            position: relative;
            background: rgba(255, 255, 255, 0.15);
            padding: 12px;
            border-radius: 50%;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px var(--shadow-light);
        }
        /* Main Content Styles */
.main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    min-height: calc(100vh - 80px);
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 2rem;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: var(--card-background);
    box-shadow: var(--shadow-sm);
}

.back-link:hover {
    background: var(--primary-color);
    color: white;
    transform: translateX(-5px);
}

/* Card Styles */
.card {
    background: var(--card-background);
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-bottom: 1px solid var(--border-color);
}

.card-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.order-meta {
    display: flex;
    gap: 1.5rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.order-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Order Timeline */
.order-timeline {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.order-timeline h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-dark);
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.timeline-item.active {
    background: var(--primary-light);
    border-left: 4px solid var(--primary-color);
}

.timeline-item.inactive {
    background: #f8f9fa;
    color: var(--text-light);
}

.timeline-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--secondary-color);
    margin-top: 5px;
    flex-shrink: 0;
}

.timeline-item.active .timeline-dot {
    background: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
}

.timeline-date {
    font-size: 0.85rem;
    color: var(--text-light);
    margin-top: 2px;
}

/* Status Badges */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.pending_payment {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-badge.processing {
    background: #cce7ff;
    color: #004085;
    border: 1px solid #b3d7ff;
}

.status-badge.completed {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-badge.cancelled {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Order Summary Grid */
.order-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.summary-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.summary-item strong {
    color: var(--text-dark);
    margin-right: auto;
}

/* Membership Badges */
.membership-badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.membership-premium {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #856404;
    border: 1px solid #ffd700;
}

.membership-regular {
    background: #e9ecef;
    color: #495057;
    border: 1px solid #dee2e6;
}

/* Discount Breakdown */
.discount-breakdown {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(135deg, #f8fff8, #f0fff0);
}

.discount-breakdown h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-dark);
}

.discount-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.discount-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.discount-type {
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.discount-type.cart-discount {
    background: #e3f2fd;
    color: #1565c0;
}

.discount-type.member-discount {
    background: #fff3e0;
    color: #ef6c00;
}

.discount-type.retention-discount {
    background: #f3e5f5;
    color: #7b1fa2;
}

.discount-type.points-discount {
    background: #e8f5e8;
    color: #2e7d32;
}

.discount-amount {
    font-weight: 600;
    color: var(--primary-color);
}

.savings-badge {
    margin-top: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-radius: 8px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
}

/* Order Items List */
.order-items-list {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.order-items-list h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-dark);
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.order-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.item-discount-badge {
    font-size: 0.7rem;
    padding: 2px 8px;
    background: var(--primary-light);
    color: var(--primary-dark);
    border-radius: 10px;
    font-weight: 600;
}

.item-price {
    font-weight: 600;
    color: var(--text-dark);
}

.original-price {
    text-decoration: line-through;
    color: var(--text-light);
    margin-right: 8px;
    font-size: 0.9rem;
}

.item-savings {
    margin-top: 0.25rem;
}

.item-quantity {
    text-align: right;
    font-weight: 500;
}

.item-total {
    font-weight: 600;
    color: var(--text-dark);
    margin-top: 0.25rem;
}

/* Order Final Summary */
.order-final-summary {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 0 0 12px 12px;
}

.order-final-summary h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-dark);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.summary-row.text-muted {
    color: var(--text-light);
}

.summary-row.text-success {
    color: var(--primary-color);
    font-weight: 500;
}

.summary-row.text-primary {
    color: #007bff;
    font-weight: 500;
}

.summary-row.text-warning {
    color: #ffc107;
    font-weight: 500;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0;
    font-size: 1.2rem;
    font-weight: 700;
    border-top: 2px solid var(--border-color);
    margin-top: 0.5rem;
}

.total-amount {
    color: var(--primary-color);
}

/* Receipt Section */
.receipt-section {
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.receipt-section h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-dark);
}

.receipt-image {
    max-width: 300px;
    max-height: 400px;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.3s ease;
    box-shadow: var(--shadow-md);
}

.receipt-image:hover {
    transform: scale(1.02);
}

.receipt-note {
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-light);
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
}

.modal-content img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 8px;
}

.close {
    position: absolute;
    top: -40px;
    right: 0;
    color: white;
    font-size: 2rem;
    cursor: pointer;
}

/* Orders List Container */
.orders-list-container {
    display: grid;
    gap: 1.5rem;
}

.order-card {
    background: var(--card-background);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.order-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.order-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    flex: 1;
}

.order-actions .btn-primary {
    background: var(--primary-color);
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.order-actions .btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

/* Alert Styles */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-link {
    color: inherit;
    text-decoration: underline;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        padding: 15px;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .order-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .order-summary-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-details {
        text-align: center;
    }
    
    .item-quantity {
        text-align: center;
    }
    
    .order-card {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .order-info {
        grid-template-columns: 1fr;
    }
    
    .receipt-image {
        max-width: 100%;
    }
    
    .modal-content {
        max-width: 95%;
        max-height: 95%;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 10px;
    }
    
    .card-header {
        padding: 1rem;
    }
    
    .card-header h2 {
        font-size: 1.25rem;
    }
    
    .order-timeline,
    .order-summary-grid,
    .discount-breakdown,
    .order-items-list,
    .order-final-summary,
    .receipt-section {
        padding: 1rem;
    }
    
    .timeline-item {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .discount-item {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}
    </style>
</head>
<body>
<!-- Responsive Header -->
<header class="header">
    <div class="header-content">
        <a href="order_details.php" class="logo" style="text-decoration: none;">
            <img src="logo.png" alt="Coopamart Logo" style="height: 66px; width: 78px;">
        </a>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="header-actions" id="headerActions">
            <!-- Close button for mobile -->
            <button class="mobile-menu-close" onclick="toggleMobileMenu()">
                <i class="fas fa-times"></i>
            </button>

            <a href="account.php" class="header-icon-link" aria-label="My Account" title="My Account">
                <i class="fas fa-user-circle"></i>
                <span class="icon-label">Account</span>
            </a>
            
            <a href="order_details.php" class="header-icon-link" aria-label="My Orders" title="My Orders">
                <i class="fas fa-box"></i>
                <span class="icon-label">Orders</span>
            </a>

            <!-- 🔔 Notification Icon with Count -->
            <a href="notification.php" class="header-icon-link" aria-label="Notifications" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="icon-label">Notifications</span>
                <?php if ($notification_count > 0): ?>
                    <span class="notification-count"><?= $notification_count ?></span>
                <?php endif; ?>
            </a>

            <a href="cart.php" class="header-icon-link" aria-label="Cart" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="icon-label">Cart</span>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>

            <a href="auth/log_out.php" class="header-icon-link" aria-label="Log Out" title="Log Out">
                <i class="fas fa-sign-out-alt"></i>
                <span class="icon-label">Log Out</span>
            </a>

            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></div>
                    <div style="font-size: 0.8rem; opacity: 0.8;">Welcome back!</div>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="main-content">
    <?php if (isset($order_details) && $order_details): ?>
        <!-- CHANGED: Back link now goes to index.php instead of order_details.php -->
        <a href="index.php" id="back-link" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Store
        </a>

        <div class="card">
            <div class="card-header">
                <h2>Order #<?= htmlspecialchars($order_details['order_id']) ?></h2>
                <div class="order-meta">
                    <span class="order-date">
                        <i class="fas fa-calendar"></i>
                        <?= date('F j, Y g:i A', strtotime($order_details['created_at'])) ?>
                    </span>
                    <span class="item-count">
                        <i class="fas fa-shopping-bag"></i>
                        <?= $order_details['item_count'] ?> item<?= $order_details['item_count'] != 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <!-- Order Status and Timeline -->
            <div class="order-timeline">
                <h3><i class="fas fa-history"></i> Order Status</h3>
                <div class="timeline-item <?= $order_details['status'] == 'pending_payment' ? 'active' : 'inactive' ?>">
                    <div class="timeline-dot"></div>
                    <div>
                        <strong>Pending Payment</strong>
                        <div class="timeline-date">Order placed, waiting for payment</div>
                    </div>
                </div>
                <div class="timeline-item <?= $order_details['status'] == 'processing' ? 'active' : 'inactive' ?>">
                    <div class="timeline-dot"></div>
                    <div>
                        <strong>Processing</strong>
                        <div class="timeline-date">Payment confirmed, preparing order</div>
                    </div>
                </div>
                <div class="timeline-item <?= $order_details['status'] == 'completed' ? 'active' : 'inactive' ?>">
                    <div class="timeline-dot"></div>
                    <div>
                        <strong>Completed</strong>
                        <div class="timeline-date">Order delivered and completed</div>
                    </div>
                </div>
            </div>

            <div class="order-summary-grid">
                <div class="summary-item">
                    <strong>Order Status:</strong>
                    <span class="status-badge <?= strtolower(str_replace(' ', '-', $order_details['status'])) ?>">
                        <?= htmlspecialchars($order_details['status']) ?>
                    </span>
                </div>
                <div class="summary-item">
                    <strong>Total Amount:</strong>
                    <span>₱<?= number_format($order_details['total_price'], 2) ?></span>
                </div>
                <div class="summary-item">
                    <strong>Customer:</strong>
                    <span><?= htmlspecialchars($order_details['full_name']) ?></span>
                </div>
                <div class="summary-item">
                    <strong>Membership:</strong>
                    <span class="membership-badge <?= $order_details['membership_type'] === 'sidc_member' ? 'membership-premium' : 'membership-regular' ?>">
                        <?= $order_details['membership_type'] === 'sidc_member' ? 'SIDC Member' : 'Regular Customer' ?>
                    </span>
                </div>
            </div>

            <!-- Discount Breakdown -->
            <?php if ($order_details['discount_amount'] > 0 || $order_details['retention_discount_amount'] > 0 || $order_details['points_discount'] > 0 || $order_details['cart_discount_amount'] > 0): ?>
                <div class="discount-breakdown">
                    <h3><i class="fas fa-tag"></i> Discounts & Savings</h3>
                    
                    <?php if ($order_details['cart_discount_amount'] > 0): ?>
                        <div class="discount-item">
                            <span class="discount-label">
                                <i class="fas fa-shopping-cart"></i> Cart Item Discounts
                                <span class="discount-type cart-discount">ITEM DISCOUNTS</span>
                            </span>
                            <span class="discount-amount">-₱<?= number_format($order_details['cart_discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($order_details['discount_amount'] > 0): ?>
                        <div class="discount-item">
                            <span class="discount-label">
                                <i class="fas fa-crown"></i> Member Discount (10%)
                                <span class="discount-type member-discount">MEMBER</span>
                            </span>
                            <span class="discount-amount">-₱<?= number_format($order_details['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($order_details['retention_discount_amount'] > 0): ?>
                        <div class="discount-item">
                            <span class="discount-label">
                                <i class="fas fa-gift"></i> Special Offer Discount
                                <span class="discount-type retention-discount">SPECIAL OFFER</span>
                            </span>
                            <span class="discount-amount">-₱<?= number_format($order_details['retention_discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($order_details['points_discount'] > 0): ?>
                        <div class="discount-item">
                            <span class="discount-label">
                                <i class="fas fa-star"></i> Points Redemption
                                <span class="discount-type points-discount">POINTS</span>
                            </span>
                            <span class="discount-amount">-₱<?= number_format($order_details['points_discount'], 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($total_savings) && $total_savings > 0): ?>
                        <div class="savings-badge">
                            <i class="fas fa-piggy-bank"></i>
                            Total Savings: ₱<?= number_format($total_savings, 2) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Order Items with Enhanced Display -->
            <div class="order-items-list">
                <h3><i class="fas fa-list"></i> Order Items (<?= count($order_items) ?>)</h3>
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <img src="<?= htmlspecialchars($item['image_path'] ?? 'https://via.placeholder.com/80x80?text=No+Image') ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                        </div>
                        <div class="item-details">
                            <div class="item-name">
                                <?= htmlspecialchars($item['name']) ?>
                                <?php if ($item['was_discounted']): ?>
                                    <span class="item-discount-badge">
                                        <i class="fas fa-tag"></i> DISCOUNTED
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="item-price">
                                <?php if ($item['was_discounted'] && $item['original_price'] > $item['final_price']): ?>
                                    <span class="original-price">₱<?= number_format($item['original_price'], 2) ?></span>
                                <?php endif; ?>
                                ₱<?= number_format($item['final_price'], 2) ?>
                            </div>
                            <?php if ($item['was_discounted'] && $item['discount_savings'] > 0): ?>
                                <div class="item-savings" style="color: #28a745; font-size: 0.85rem;">
                                    <i class="fas fa-piggy-bank"></i>
                                    Saved ₱<?= number_format($item['discount_savings'], 2) ?> on this item
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="item-quantity">
                            <div>Qty: <?= $item['quantity'] ?></div>
                            <div class="item-total">₱<?= number_format($item['subtotal'], 2) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Final Order Summary -->
            <div class="order-final-summary">
                <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                <div class="summary-row">
                    <span>Items Subtotal:</span>
                    <span>₱<?= number_format($order_details['total_price'] + $order_details['discount_amount'] + $order_details['retention_discount_amount'] + $order_details['points_discount'] - $order_details['packing_fee'], 2) ?></span>
                </div>
                <?php if ($order_details['cart_discount_amount'] > 0): ?>
                    <div class="summary-row text-muted">
                        <span>Cart Discounts Applied:</span>
                        <span>-₱<?= number_format($order_details['cart_discount_amount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order_details['discount_amount'] > 0): ?>
                    <div class="summary-row text-success">
                        <span>Member Discount:</span>
                        <span>-₱<?= number_format($order_details['discount_amount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order_details['retention_discount_amount'] > 0): ?>
                    <div class="summary-row text-primary">
                        <span>Special Offer Discount:</span>
                        <span>-₱<?= number_format($order_details['retention_discount_amount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($order_details['points_discount'] > 0): ?>
                    <div class="summary-row text-warning">
                        <span>Points Discount:</span>
                        <span>-₱<?= number_format($order_details['points_discount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-row">
                    <span>Packing Fee:</span>
                    <span>₱<?= number_format($order_details['packing_fee'], 2) ?></span>
                </div>
                <div class="summary-total">
                    <span>Total Amount:</span>
                    <span class="total-amount">₱<?= number_format($order_details['total_price'], 2) ?></span>
                </div>
            </div>

            <?php if (!empty($order_details['receipt_image'])): ?>
                <div class="receipt-section">
                    <h3><i class="fas fa-file-invoice"></i> Payment Receipt</h3>
                    <img src="<?= htmlspecialchars($order_details['receipt_image']) ?>" 
                         alt="Payment Receipt" 
                         class="receipt-image"
                         onclick="openReceiptModal(this.src)">
                    <p class="receipt-note">
                        <i class="fas fa-info-circle"></i>
                        Click on the receipt to view larger version
                    </p>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No payment receipt has been uploaded for this order.
                </div>
            <?php endif; ?>

        </div>

        <!-- Receipt Modal -->
        <div id="receiptModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="closeReceiptModal()">&times;</span>
                <img id="modalReceipt" src="" alt="Payment Receipt">
            </div>
        </div>

    <?php elseif (isset($user_orders)): ?>
        <h1 class="page-title"><i class="fas fa-history"></i> My Order History</h1>
        <div class="orders-list-container">
            <?php if (!empty($user_orders)): ?>
                <?php foreach ($user_orders as $order): ?>
                    <div class="order-card">
                        <div class="order-info">
                            <div class="order-id"><strong>Order ID:</strong> #<?= htmlspecialchars($order['order_id']) ?></div>
                            <div class="order-date"><strong>Date:</strong> <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></div>
                            <div class="order-total"><strong>Total:</strong> ₱<?= number_format($order['total_price'], 2) ?></div>
                            <div class="order-items"><strong>Items:</strong> <?= $order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></div>
                            <div class="order-status">
                                <strong>Status:</strong>
                                <span class="status-badge <?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="order-actions">
                            <a href="order_details.php?order_id=<?= htmlspecialchars($order['order_id']) ?>" class="btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-shopping-cart"></i>
                    You have not placed any orders yet.
                    <a href="index.php" class="alert-link">Start shopping now!</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            Order not found or you do not have permission to view it.
        </div>
    <?php endif; ?>
</main>

<script>
    // CHANGED: Back link now goes to index.php instead of using history back
    const backLink = document.getElementById('back-link');
    if (backLink) {
        backLink.addEventListener('click', function(event) {
            // Go to index.php instead of history back
            event.preventDefault();
            window.location.href = 'index.php';
        });
    }

    // Mobile header menu toggle
    function toggleMobileMenu() {
        const headerActions = document.getElementById('headerActions');
        headerActions.classList.toggle('show');
        
        // Prevent body scroll when menu is open
        if (headerActions.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    // Close mobile header menu when clicking on a link
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.header-icon-link').forEach(link => {
            link.addEventListener('click', function() {
                const headerActions = document.getElementById('headerActions');
                headerActions.classList.remove('show');
                document.body.style.overflow = '';
            });
        });
    }

    // Receipt modal functions
    function openReceiptModal(src) {
        document.getElementById('modalReceipt').src = src;
        document.getElementById('receiptModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('receiptModal');
        if (event.target === modal) {
            closeReceiptModal();
        }
    }

    // Auto-update notification count every 30 seconds
    function updateNotificationCount() {
        fetch('get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                const notificationCountElements = document.querySelectorAll('.notification-count');
                
                if (data.count > 0) {
                    // Update header notification count
                    notificationCountElements.forEach(element => {
                        element.textContent = data.count;
                        element.style.display = 'flex';
                    });
                } else {
                    // Hide notification count if zero
                    notificationCountElements.forEach(element => {
                        element.style.display = 'none';
                    });
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
    }

    // Update every 30 seconds
    setInterval(updateNotificationCount, 30000);

    // Also update when page becomes visible
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateNotificationCount();
        }
    });
</script>
</body>
</html>