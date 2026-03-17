<?php
session_start();
require_once 'include/config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check for eligible discounts on page load - MODIFIED FOR 1 DAY
$eligible_discounts = [];
$eligible_check = $pdo->prepare("
    SELECT 
        ci.cart_item_id,
        ci.added_at,
        ci.product_id,
        p.price,
        TIMESTAMPDIFF(HOUR, ci.added_at, NOW()) as hours_in_cart,
        ud.user_discount_id
    FROM cart_items ci
    JOIN carts c ON ci.cart_id = c.cart_id
    JOIN products p ON ci.product_id = p.product_id
    LEFT JOIN user_discounts ud ON (
        ci.cart_item_id = ud.cart_item_id 
        AND ud.user_id = ? 
        AND ud.is_used = 0 
        AND (ud.expires_at IS NULL OR ud.expires_at > NOW())
    )
    WHERE c.user_id = ?
    AND ud.user_discount_id IS NULL
    AND TIMESTAMPDIFF(HOUR, ci.added_at, NOW()) >= 24  -- CHANGED TO 24 HOURS
");
$eligible_check->execute([$user_id, $user_id]);
$eligible_items = $eligible_check->fetchAll();

// Auto-apply discounts for eligible items
foreach ($eligible_items as $item) {
    $save_stmt = $pdo->prepare("
        INSERT INTO user_discounts 
        (user_id, cart_item_id, product_id, original_price, discount_amount, discount_type, expires_at) 
        VALUES (?, ?, ?, ?, ?, 'inactivity', DATE_ADD(NOW(), INTERVAL 24 HOUR))
    ");
    $save_stmt->execute([
        $user_id, 
        $item['cart_item_id'], 
        $item['product_id'],
        $item['price'],
        5.00
    ]);
    
    $eligible_discounts[] = $item['cart_item_id'];
}

// Load all active discounts from database
$discount_stmt = $pdo->prepare("
    SELECT cart_item_id, discount_amount
    FROM user_discounts 
    WHERE user_id = ? AND is_used = 0 
    AND (expires_at IS NULL OR expires_at > NOW())
");
$discount_stmt->execute([$user_id]);
$active_discounts = $discount_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Initialize session with database discounts
if (!isset($_SESSION['discounted_items'])) {
    $_SESSION['discounted_items'] = array_keys($active_discounts);
} else {
    // Merge with database discounts
    $_SESSION['discounted_items'] = array_unique(array_merge(
        $_SESSION['discounted_items'],
        array_keys($active_discounts)
    ));
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $cart_item_id = $_POST['cart_item_id'] ?? null;
        
        switch ($action) {
            case 'update_quantity':
                $quantity = max(1, (int)$_POST['quantity']);
                $update_stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                $update_stmt->execute([$quantity, $cart_item_id]);
                break;
                
            case 'remove_item':
                $remove_stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
                $remove_stmt->execute([$cart_item_id]);
                
                // Remove from user_discounts table if exists
                $remove_discount_stmt = $pdo->prepare("DELETE FROM user_discounts WHERE cart_item_id = ? AND user_id = ?");
                $remove_discount_stmt->execute([$cart_item_id, $user_id]);
                
                // Remove from session
                if (($key = array_search($cart_item_id, $_SESSION['discounted_items'])) !== false) {
                    unset($_SESSION['discounted_items'][$key]);
                    $_SESSION['discounted_items'] = array_values($_SESSION['discounted_items']);
                }
                break;
                
            case 'clear_cart':
                $clear_stmt = $pdo->prepare("
                    DELETE ci FROM cart_items ci 
                    JOIN carts c ON ci.cart_id = c.cart_id 
                    WHERE c.user_id = ?
                ");
                $clear_stmt->execute([$user_id]);
                
                // Clear user discounts for this user
                $clear_discounts_stmt = $pdo->prepare("DELETE FROM user_discounts WHERE user_id = ? AND is_used = 0");
                $clear_discounts_stmt->execute([$user_id]);
                
                // Clear session
                $_SESSION['discounted_items'] = [];
                break;
                
            case 'apply_discount':
                $item_id = $_POST['item_id'] ?? null;
                if ($item_id) {
                    // Check if discount already exists
                    $check_stmt = $pdo->prepare("
                        SELECT user_discount_id 
                        FROM user_discounts 
                        WHERE user_id = ? AND cart_item_id = ? AND is_used = 0
                    ");
                    $check_stmt->execute([$user_id, $item_id]);
                    
                    if (!$check_stmt->fetch()) {
                        // Get product info
                        $product_stmt = $pdo->prepare("
                            SELECT ci.product_id, p.price 
                            FROM cart_items ci 
                            JOIN products p ON ci.product_id = p.product_id 
                            WHERE ci.cart_item_id = ?
                        ");
                        $product_stmt->execute([$item_id]);
                        $product = $product_stmt->fetch();
                        
                        if ($product) {
                            // Save to database
                            $save_stmt = $pdo->prepare("
                                INSERT INTO user_discounts 
                                (user_id, cart_item_id, product_id, original_price, discount_amount, discount_type, expires_at) 
                                VALUES (?, ?, ?, ?, ?, 'inactivity', DATE_ADD(NOW(), INTERVAL 24 HOUR))
                            ");
                            $save_stmt->execute([
                                $user_id, 
                                $item_id, 
                                $product['product_id'],
                                $product['price'],
                                5.00
                            ]);
                            
                            $_SESSION['discounted_items'][] = $item_id;
                        }
                    }
                }
                echo json_encode(['success' => true]);
                exit();
                
            case 'check_eligible_discounts':
                // Check for new eligible discounts - MODIFIED FOR 1 DAY
                $new_eligible = [];
                $check_stmt = $pdo->prepare("
                    SELECT 
                        ci.cart_item_id,
                        TIMESTAMPDIFF(HOUR, ci.added_at, NOW()) as hours_in_cart
                    FROM cart_items ci
                    JOIN carts c ON ci.cart_id = c.cart_id
                    LEFT JOIN user_discounts ud ON (
                        ci.cart_item_id = ud.cart_item_id 
                        AND ud.user_id = ? 
                        AND ud.is_used = 0
                    )
                    WHERE c.user_id = ?
                    AND ud.user_discount_id IS NULL
                    AND TIMESTAMPDIFF(HOUR, ci.added_at, NOW()) >= 24  -- CHANGED TO 24 HOURS
                ");
                $check_stmt->execute([$user_id, $user_id]);
                $new_eligible = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo json_encode([
                    'success' => true,
                    'eligible_items' => $new_eligible
                ]);
                exit();
        }
        
        // Redirect for cart operations (update, remove, clear)
        header("Location: cart.php");
        exit();
    }
}

// Get cart items with discount information - MODIFIED FOR 1 DAY
$cart_stmt = $pdo->prepare("
    SELECT DISTINCT
        ci.cart_item_id,
        ci.quantity,
        ci.added_at,
        p.product_id,
        p.name,
        p.price,
        p.image_path,
        c.name as category_name,
        (p.price * ci.quantity) as subtotal,
        ud.discount_amount,
        ud.applied_at as discount_applied,
        ud.expires_at as discount_expires,
        TIMESTAMPDIFF(HOUR, ci.added_at, NOW()) as hours_in_cart  -- CHANGED TO HOURS
    FROM cart_items ci
    JOIN carts cart ON ci.cart_id = cart.cart_id
    JOIN products p ON ci.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN user_discounts ud ON (
        ci.cart_item_id = ud.cart_item_id 
        AND ud.user_id = ? 
        AND ud.is_used = 0 
        AND (ud.expires_at IS NULL OR ud.expires_at > NOW())
    )
    WHERE cart.user_id = ?
    GROUP BY ci.cart_item_id
    ORDER BY ci.added_at DESC
");
$cart_stmt->execute([$user_id, $user_id]);
$cart_items = $cart_stmt->fetchAll();

// Calculate totals
$total_items = 0;
$total_amount = 0;
$total_discount = 0;

foreach ($cart_items as $item) {
    $total_items += $item['quantity'];
    $item_subtotal = $item['subtotal'];
    
    // Apply discount if exists
    if ($item['discount_amount'] > 0) {
        $item_discount = $item['discount_amount'];
        $item_subtotal -= $item_discount;
        $total_discount += $item_discount;
    }
    
    $total_amount += $item_subtotal;
}

// Initialize cart count
$cart_count = 0; 
if (isset($_SESSION['user_id'])) {
    $cart_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0) as total_items
        FROM carts c
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        WHERE c.user_id = ?
    ");
    $cart_stmt->execute([$_SESSION['user_id']]);
    $cart_result = $cart_stmt->fetch();
    $cart_count = $cart_result['total_items'] ?? 0;
}

// Cleanup expired discounts
if (rand(1, 10) === 1) {
    $cleanup_stmt = $pdo->prepare("
        DELETE FROM user_discounts 
        WHERE expires_at < NOW() AND is_used = 0
    ");
    $cleanup_stmt->execute();
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
    <title>Shopping Cart - Coopamart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/cart.css">
    <style>
        /* Your existing CSS styles remain the same */
        :root {
            --primary-color: #28a745;
            --primary-dark: #218838;
            --primary-light: #d4edda;
            --secondary-color: #6c757d;
            --secondary-dark: #5a6268;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-color: #343a40;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-strong: rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
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

        /* Your existing CSS styles continue... */
        /* ... (all your existing CSS styles remain exactly the same) ... */
        
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

        /* Mobile Menu Toggle Button */
        .mobile-menu-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .mobile-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .mobile-menu-close {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 10px;
            cursor: pointer;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .icon-label {
            display: none;
        }

        /* Mobile Responsive - Modified Section */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
            }

            .logo {
                order: 1;
            }

            .mobile-menu-toggle {
                display: block;
                order: 3;
            }

            .mobile-menu-close {
                display: block;
            }
            
            .header-actions {
                position: fixed !important;
                top: 0;
                right: -100%;
                width: 280px;
                height: 100vh;
                background: linear-gradient(135deg, #28a745 0%, #177c38 100%);
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0 !important;
                padding: 60px 20px 20px !important;
                box-shadow: -5px 0 15px rgba(0,0,0,0.3);
                transition: right 0.3s ease;
                z-index: 1000;
                overflow-y: auto;
                order: 2;
            }

            .header-actions.show {
                right: 0;
            }

            .header-icon-link {
                width: 100%;
                padding: 15px !important;
                border-radius: 8px !important;
                margin-bottom: 8px;
                justify-content: flex-start !important;
                gap: 15px;
            }

            .icon-label {
                display: inline;
                font-size: 1rem;
                font-weight: 500;
            }

            .user-info {
                width: 100%;
                padding: 15px !important;
                margin-top: 10px;
                margin-bottom: 0 !important;
                border-top: 1px solid rgba(255, 255, 255, 0.2);
                justify-content: flex-start !important;
            }
            
            .search-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<!-- Responsive Header -->
<header class="header">
    <div class="header-content">
        <a href="index.php" class="logo" style="text-decoration: none;">
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

    <!-- Discount Notification Modal -->
    <div class="discount-overlay" id="discountOverlay"></div>
    <div class="discount-notification" id="discountNotification">
        <div class="discount-icon">
            <i class="fas fa-gift"></i>
        </div>
   <div class="discount-title">🎉 Welcome Back!</div>
<div class="discount-message">
    We noticed you’ve been away for a few days and still have items waiting in your cart.
    As a little thank you for returning, here’s a special discount just for you!
</div>
<div class="discount-amount">₱5 OFF</div>
<div style="color: #666; font-size: 0.9rem; margin-bottom: 1.5rem;">
    Enjoy this exclusive offer — valid for the next 24 hours!
</div>

        <button class="discount-btn" onclick="closeDiscountModal()">Awesome!</button>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="cart-header">
            <h1 class="cart-title">
                <i class="fas fa-shopping-cart"></i> Shopping Cart
                <?php if (!empty($cart_items)): ?>
                    <span style="font-size: 1rem; color: #666; font-weight: normal;">
                        (<?= $total_items ?> item<?= $total_items !== 1 ? 's' : '' ?>)
                    </span>
                <?php endif; ?>
            </h1>
            
            <?php if (!empty($cart_items)): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="clear-cart-btn" onclick="return confirm('Are you sure you want to clear your cart?')">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="index.php" class="checkout-btn" style="display: inline-block; text-decoration: none; max-width: 300px;">
                    <i class="fas fa-store"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="cart-items">
                    <div class="select-all-container">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" checked>
                        <label for="selectAll">Select All Items</label>
                    </div>
                    
                    <?php foreach ($cart_items as $item): 
                        $has_discount = $item['discount_amount'] > 0;
                        $item_subtotal = $item['subtotal'];
                        if ($has_discount) {
                            $item_subtotal -= $item['discount_amount'];
                        }
                    ?>
                        <div class="cart-item selected" 
                             data-item-id="<?= $item['cart_item_id'] ?>" 
                             data-price="<?= $item['price'] ?>"
                             data-quantity="<?= $item['quantity'] ?>"
                             data-subtotal="<?= $item_subtotal ?>"
                             data-has-discount="<?= $has_discount ? 'true' : 'false' ?>"
                             data-discount-amount="<?= $has_discount ? $item['discount_amount'] : 0 ?>"
                             data-hours-in-cart="<?= $item['hours_in_cart'] ?>">  <!-- CHANGED TO hours_in_cart -->
                            <div class="item-checkbox">
                                <input type="checkbox" 
                                       data-cart-item-id="<?= $item['cart_item_id'] ?>" 
                                       class="item-select"
                                       onchange="updateSummary()" checked>
                            </div>
                            
                            <div class="item-image">
                                <?php if ($item['image_path']): ?>
                                    <img src="<?= htmlspecialchars($item['image_path']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         onerror="this.src='https://via.placeholder.com/100x100?text=No+Image'">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/100x100?text=No+Image" alt="No image">
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-details">
                                <div class="item-name">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <?php if ($has_discount): ?>
                                        <span class="discount-badge">₱<?= number_format($item['discount_amount'], 2) ?> OFF</span>
                                        <small style="color: #666; font-size: 0.8rem; display: block;">
                                            Expires: <?= date('M j, g:i A', strtotime($item['discount_expires'])) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($item['category_name']): ?>
                                    <div class="item-category"><?= htmlspecialchars($item['category_name']) ?></div>
                                <?php endif; ?>
                                <div class="item-price">
                                    <?php if ($has_discount): ?>
                                        <span style="text-decoration: line-through; color: #999; font-size: 0.9rem;">
                                            ₱<?= number_format($item['price'], 2) ?>
                                        </span>
                                        <span style="color: #dc3545; font-weight: bold;">
                                            ₱<?= number_format($item['price'] - $item['discount_amount'], 2) ?>
                                        </span>
                                        each
                                    <?php else: ?>
                                        ₱<?= number_format($item['price'], 2) ?> each
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 5px; font-weight: 600; color: #333;">
                                    Subtotal: <span class="item-subtotal">₱<?= number_format($item_subtotal, 2) ?></span>
                                    <?php if ($has_discount): ?>
                                        <span style="text-decoration: line-through; color: #999; font-size: 0.9rem;">
                                            ₱<?= number_format($item['subtotal'], 2) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="item-actions">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <input type="hidden" name="action" value="update_quantity">
                                    <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                    
                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn" onclick="changeQuantity(this, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                               min="1" class="quantity-input" onchange="updateItemSubtotal(this)">
                                        <button type="button" class="quantity-btn" onclick="changeQuantity(this, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="button" class="remove-btn" onclick="removeItem(<?= $item['cart_item_id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-title">
                        <i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i>
                        Order Summary
                    </div>

                    <div class="alert alert-warning" style="background:#fff3cd; border:1px solid #ffeeba; padding:10px; margin:10px 0; border-radius:6px;">
                        <strong>⚠️ Important:</strong> Select items to checkout and review carefully!
                    </div>
                    
                    <div class="summary-row">
                        <span>Selected Items:</span>
                        <span id="selectedCount"><?= $total_items ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="selectedSubtotal">₱<?= number_format($total_amount + $total_discount, 2) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Discount:</span>
                        <span id="totalDiscount" style="color: #dc3545;">-₱<?= number_format($total_discount, 2) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Total:</span>
                        <span id="selectedTotal">₱<?= number_format($total_amount, 2) ?></span>
                    </div>
                    
                    <button type="button" class="checkout-btn" id="checkoutBtn" onclick="proceedToCheckout()">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </button>
                    
                    <a href="dashboard.php" class="continue-shopping">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #f0f0f0;">
                        <div style="display: flex; align-items: center; gap: 10px; color: #28a745; font-size: 0.9rem;">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure checkout guaranteed</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
    // Track applied discounts and shown popups
    const appliedDiscounts = new Set(<?= json_encode(array_map('strval', $_SESSION['discounted_items'] ?? [])) ?>);
    const shownPopups = new Set();
    const DISCOUNT_AMOUNT = 5;
    const INACTIVITY_DURATION = 24 * 60 * 60 * 1000; // 24 HOURS in milliseconds
    const CHECK_INTERVAL = 30 * 60 * 1000; // Check every 30 MINUTES instead of 2 seconds

    // Show discount popup on page load for eligible items
    const eligibleOnLoad = <?= json_encode($eligible_discounts) ?>;
    if (eligibleOnLoad.length > 0 && !sessionStorage.getItem('discount_shown_this_session')) {
        setTimeout(() => {
            showDiscountNotification(eligibleOnLoad[0]);
            sessionStorage.setItem('discount_shown_this_session', 'true');
        }, 500);
    }

    // Periodically check for new eligible discounts
    let discountCheckInterval = setInterval(() => {
        checkForEligibleDiscounts();
    }, CHECK_INTERVAL);

    function checkForEligibleDiscounts() {
        // Check items that have been in cart for 24+ HOURS
        document.querySelectorAll('.cart-item').forEach(item => {
            const itemId = item.getAttribute('data-item-id');
            const hasDiscount = item.getAttribute('data-has-discount') === 'true';
            const hoursInCart = parseInt(item.getAttribute('data-hours-in-cart') || 0);  // CHANGED TO hours_in_cart
            
            // If item doesn't have discount, has been in cart 24+ hours, and we haven't shown popup yet
            if (!hasDiscount && hoursInCart >= 24 && !shownPopups.has(itemId) && !appliedDiscounts.has(itemId)) {
                shownPopups.add(itemId);
                showDiscountNotification(itemId);
                return; // Show only one popup at a time
            }
        });
    }

    function showDiscountNotification(itemId) {
        // Apply discount via AJAX
        const formData = new FormData();
        formData.append('action', 'apply_discount');
        formData.append('item_id', itemId);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add to applied discounts
                appliedDiscounts.add(itemId);
                
                // Update the item in the DOM
                const itemDiv = document.querySelector(`[data-item-id="${itemId}"]`);
                if (itemDiv) {
                    itemDiv.setAttribute('data-has-discount', 'true');
                    itemDiv.setAttribute('data-discount-amount', DISCOUNT_AMOUNT);
                    
                    // Update prices and subtotal
                    updateItemDiscountDisplay(itemDiv);
                }
                
                // Show modal
                const overlay = document.getElementById('discountOverlay');
                const notification = document.getElementById('discountNotification');
                
                overlay.classList.add('show');
                notification.classList.add('show');
                
                // Auto close after 5 seconds
                setTimeout(() => {
                    closeDiscountModal();
                }, 5000);
                
                // Update summary
                updateSummary();
            }
        })
        .catch(error => {
            console.error('Error applying discount:', error);
        });
    }

    function updateItemDiscountDisplay(itemDiv) {
        const price = parseFloat(itemDiv.dataset.price);
        const quantity = parseInt(itemDiv.dataset.quantity);
        const discountAmount = DISCOUNT_AMOUNT;
        const originalSubtotal = price * quantity;
        const newSubtotal = originalSubtotal - discountAmount;
        
        // Update subtotal
        const subtotalElement = itemDiv.querySelector('.item-subtotal');
        if (subtotalElement) {
            subtotalElement.textContent = '₱' + newSubtotal.toFixed(2);
        }
        
        // Update dataset
        itemDiv.dataset.subtotal = newSubtotal;
    }

    function closeDiscountModal() {
        const overlay = document.getElementById('discountOverlay');
        const notification = document.getElementById('discountNotification');
        
        overlay.classList.remove('show');
        notification.classList.remove('show');
    }

    // MODIFIED: Updated proceedToCheckout function
    function proceedToCheckout() {
        const checkboxes = document.querySelectorAll('.item-select:checked');
        
        if (checkboxes.length === 0) {
            showNotification('Please select at least one item to checkout', 'error');
            return;
        }

        const selectedItems = Array.from(checkboxes).map(cb => cb.dataset.cartItemId);
        
        // Create form to submit selected items to order.php
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'order.php';
        
        // Add selected items as hidden fields
        selectedItems.forEach(itemId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_items[]';
            input.value = itemId;
            form.appendChild(input);
        });

        // Add discounted items if any
        selectedItems.forEach(itemId => {
            if (appliedDiscounts.has(itemId)) {
                const discountInput = document.createElement('input');
                discountInput.type = 'hidden';
                discountInput.name = 'discounted_items[]';
                discountInput.value = itemId;
                form.appendChild(discountInput);
            }
        });
        
        // Show loading state
        const checkoutBtn = document.getElementById('checkoutBtn');
        const originalText = checkoutBtn.innerHTML;
        checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        checkoutBtn.disabled = true;
        
        // Add form to document and submit
        document.body.appendChild(form);
        
        setTimeout(() => {
            form.submit();
        }, 500);
    }

    function toggleSelectAll(checkbox) {
        const itemCheckboxes = document.querySelectorAll('.item-select');
        itemCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
            const cartItem = cb.closest('.cart-item');
            if (checkbox.checked) {
                cartItem.classList.add('selected');
            } else {
                cartItem.classList.remove('selected');
            }
        });
        updateSummary();
    }

    function updateSummary() {
        const checkboxes = document.querySelectorAll('.item-select:checked');
        const checkoutBtn = document.getElementById('checkoutBtn');
        
        let count = 0;
        let subtotal = 0;
        let totalDiscount = 0;
        
        checkboxes.forEach(checkbox => {
            const itemDiv = checkbox.closest('.cart-item');
            const price = parseFloat(itemDiv.dataset.price);
            const quantity = parseInt(itemDiv.dataset.quantity);
            const itemSubtotal = price * quantity;
            
            count++;
            subtotal += itemSubtotal;
            
            const itemId = itemDiv.getAttribute('data-item-id');
            const discountAmount = parseFloat(itemDiv.getAttribute('data-discount-amount') || 0);
            if (appliedDiscounts.has(itemId) && discountAmount > 0) {
                totalDiscount += discountAmount;
            }
        });
        
        const total = subtotal - totalDiscount;
        
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('selectedSubtotal').textContent = '₱' + subtotal.toFixed(2);
        document.getElementById('totalDiscount').textContent = '-₱' + totalDiscount.toFixed(2);
        document.getElementById('selectedTotal').textContent = '₱' + total.toFixed(2);
        
        checkoutBtn.disabled = count === 0;
        
        const selectAllCheckbox = document.getElementById('selectAll');
        const totalCheckboxes = document.querySelectorAll('.item-select').length;
        selectAllCheckbox.checked = count === totalCheckboxes && count > 0;
    }

    function changeQuantity(button, change) {
        const input = button.parentNode.querySelector('.quantity-input');
        const currentValue = parseInt(input.value);
        const newValue = Math.max(1, currentValue + change);
        input.value = newValue;
        updateItemSubtotal(input);
    }

    function updateItemSubtotal(input) {
        const itemDiv = input.closest('.cart-item');
        const price = parseFloat(itemDiv.dataset.price);
        const quantity = parseInt(input.value);
        const discountAmount = parseFloat(itemDiv.getAttribute('data-discount-amount') || 0);
        const subtotal = (price * quantity) - discountAmount;
        
        itemDiv.querySelector('.item-subtotal').textContent = '₱' + subtotal.toFixed(2);
        itemDiv.dataset.quantity = quantity;
        itemDiv.dataset.subtotal = subtotal;
        
        updateSummary();
        
        const cartItemId = itemDiv.getAttribute('data-item-id');
        updateQuantityInDB(cartItemId, quantity);
    }

    function updateQuantityInDB(cartItemId, quantity) {
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('cart_item_id', cartItemId);
        formData.append('quantity', quantity);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        });
    }

    function removeItem(cartItemId) {
        if (!confirm('Remove this item from cart?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'remove_item');
        formData.append('cart_item_id', cartItemId);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            window.location.reload();
        });
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            ${type === 'success' ? 'background: #28a745;' : 
              type === 'error' ? 'background: #dc3545;' : 'background: #17a2b8;'}
        `;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    document.getElementById('discountOverlay').addEventListener('click', closeDiscountModal);

    document.addEventListener('DOMContentLoaded', () => {
        updateSummary();
        
        document.querySelectorAll('.item-select').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const cartItem = this.closest('.cart-item');
                if (this.checked) {
                    cartItem.classList.add('selected');
                } else {
                    cartItem.classList.remove('selected');
                }
                updateSummary();
            });
        });
    });

    // Clean up interval when leaving page
    window.addEventListener('beforeunload', () => {
        if (discountCheckInterval) {
            clearInterval(discountCheckInterval);
        }
    });

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

    // Auto-update notification count every 30 seconds
    function updateNotificationCount() {
        fetch('get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                const notificationCountElements = document.querySelectorAll('.notification-count');
                const sidebarCountElements = document.querySelectorAll('.sidebar-nav .notification-count');
                
                if (data.count > 0) {
                    // Update header notification count
                    notificationCountElements.forEach(element => {
                        element.textContent = data.count;
                        element.style.display = 'flex';
                    });
                    
                    // Update sidebar notification count
                    sidebarCountElements.forEach(element => {
                        element.textContent = data.count;
                        element.style.display = 'inline';
                    });
                } else {
                    // Hide notification count if zero
                    notificationCountElements.forEach(element => {
                        element.style.display = 'none';
                    });
                    sidebarCountElements.forEach(element => {
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