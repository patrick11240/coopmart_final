<?php
session_start();
require_once 'include/config.php';

// Handle auto-apply retention offer
$auto_applied_offer = null;
$apply_offer_success = false;

// Handle auto-apply retention offer from notification
if (isset($_GET['apply_offer'])) {
    $offer_id = filter_var($_GET['apply_offer'], FILTER_VALIDATE_INT);
    
    if ($offer_id && $offer_id > 0) {
        $offer_validation = validateRetentionOffer($pdo, $offer_id, $_SESSION['user_id']);
        
        if ($offer_validation['valid']) {
            $auto_applied_offer = $offer_validation['offer'];
            
            // Store in session for order processing
            $_SESSION['auto_applied_offer'] = $auto_applied_offer;
            
            // Show success message
            $_SESSION['order_success_message'] = "Special offer applied! You're saving " . 
                $auto_applied_offer['discount_percentage'] . "% on your order.";
        } else {
            $_SESSION['order_error_message'] = $offer_validation['message'];
        }
    }
}

// Check for success message from session
if (isset($_SESSION['order_success_message'])) {
    $order_success_message = $_SESSION['order_success_message'];
    unset($_SESSION['order_success_message']);
}

if (isset($_SESSION['order_error_message'])) {
    $order_error_message = $_SESSION['order_error_message'];
    unset($_SESSION['order_error_message']);
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_member = ($user['membership_type'] === 'sidc_member');
$user_points = $user['points'] ?? 0;
$points_to_peso_rate = 5 / 20; // 20 points = ₱5
$error_message = null;

// Check if selected items are passed from cart
$selected_items = $_POST['selected_items'] ?? [];
$discounted_items = $_POST['discounted_items'] ?? [];

if (empty($selected_items)) {
    // Redirect back to cart if no items selected
    header("Location: cart.php");
    exit();
}

// Function to validate retention offer
function validateRetentionOffer($pdo, $offer_id, $user_id) {
    $current_time = date('Y-m-d H:i:s');
    
    $offer_stmt = $pdo->prepare("
        SELECT 
            ro.offer_id,
            ro.user_id,
            ro.offer_type,
            ro.discount_percentage,
            ro.points_bonus,
            ro.message,
            ro.status,
            ro.expires_at
        FROM retention_offers ro
        WHERE ro.offer_id = ? 
        AND ro.user_id = ?
        AND ro.status IN ('sent', 'opened')
        AND (ro.expires_at IS NULL OR ro.expires_at > ?)
    ");
    
    $offer_stmt->execute([$offer_id, $user_id, $current_time]);
    $offer = $offer_stmt->fetch();
    
    if (!$offer) {
        return ['valid' => false, 'message' => 'Offer not found, expired, or already used'];
    }
    
    return ['valid' => true, 'offer' => $offer];
}

// Function to get active retention offers
function getActiveRetentionOffers($pdo, $user_id) {
    $current_time = date('Y-m-d H:i:s');
    
    $retention_stmt = $pdo->prepare("
        SELECT ro.* 
        FROM retention_offers ro
        WHERE ro.user_id = ? 
        AND ro.status IN ('sent', 'opened')
        AND (ro.expires_at IS NULL OR ro.expires_at > ?)
        ORDER BY ro.sent_at DESC
    ");
    
    $retention_stmt->execute([$user_id, $current_time]);
    return $retention_stmt->fetchAll();
}

// Handle order placement and receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get SELECTED cart items with discount information
        $placeholders = str_repeat('?,', count($selected_items) - 1) . '?';
        
        $cart_stmt = $pdo->prepare("
            SELECT
                ci.cart_item_id,
                ci.quantity,
                p.product_id,
                p.name,
                p.price,
                (p.price * ci.quantity) as original_subtotal,
                ud.user_discount_id,
                ud.discount_amount,
                ud.discount_type
            FROM cart_items ci
            JOIN carts cart ON ci.cart_id = cart.cart_id
            JOIN products p ON ci.product_id = p.product_id
            LEFT JOIN user_discounts ud ON ci.cart_item_id = ud.cart_item_id 
                AND ud.user_id = ? 
                AND ud.is_used = 0
                AND (ud.expires_at IS NULL OR ud.expires_at > NOW())
            WHERE cart.user_id = ?
            AND ci.cart_item_id IN ($placeholders)
        ");
        
        $params = array_merge([$user_id, $user_id], $selected_items);
        $cart_stmt->execute($params);
        $cart_items = $cart_stmt->fetchAll();

        if (empty($cart_items)) {
            throw new Exception("Selected items not found in cart.");
        }

        // Calculate subtotal with discounts already applied to item prices
        $subtotal = 0;
        $discounted_items_count = 0;
        $cart_discount_amount = 0;
        $discount_ids_to_mark = [];
        
        foreach ($cart_items as $item) {
            $effective_price = $item['price'];
            
            if ($item['user_discount_id']) {
                $effective_price = $item['price'] - $item['discount_amount'];
                $discounted_items_count++;
                $cart_discount_amount += $item['discount_amount'];
                $discount_ids_to_mark[] = $item['user_discount_id'];
            }
            
            $item_subtotal = $effective_price * $item['quantity'];
            $subtotal += $item_subtotal;
        }

        // Set packing fee and member discount
        $packing_fee = 20.00;
        $member_discount_amount = 0;
        if ($is_member) {
            $discount_rate = 0.10;
            $member_discount_amount = $subtotal * $discount_rate;
        }

        // Apply retention offers if selected
        $retention_discount_amount = 0;
        $bonus_points_to_add = 0;
        $retention_offers_used = [];
        
        // Handle form-based retention offer selection
        if (isset($_POST['retention_offer_id']) && !empty($_POST['retention_offer_id'])) {
            $offer_id = filter_var($_POST['retention_offer_id'], FILTER_VALIDATE_INT);
            if ($offer_id && $offer_id > 0) {
                $offer_stmt = $pdo->prepare("SELECT * FROM retention_offers WHERE offer_id = ? AND user_id = ? AND status IN ('sent', 'opened')");
                $offer_stmt->execute([$offer_id, $user_id]);
                $offer = $offer_stmt->fetch();
                
                if ($offer) {
                    if ($offer['discount_percentage'] > 0) {
                        $offer_discount = $subtotal * ($offer['discount_percentage'] / 100);
                        $retention_discount_amount += $offer_discount;
                    }
                    
                    if ($offer['points_bonus'] > 0) {
                        $bonus_points_to_add += $offer['points_bonus'];
                    }
                    
                    $retention_offers_used[] = $offer_id;
                }
            }
        }
        
        // Handle auto-applied retention offer from session
        if (isset($_SESSION['auto_applied_offer'])) {
            $auto_offer = $_SESSION['auto_applied_offer'];
            
            if ($auto_offer['discount_percentage'] > 0) {
                $offer_discount = $subtotal * ($auto_offer['discount_percentage'] / 100);
                $retention_discount_amount += $offer_discount;
            }
            
            if ($auto_offer['points_bonus'] > 0) {
                $bonus_points_to_add += $auto_offer['points_bonus'];
            }
            
            $retention_offers_used[] = $auto_offer['offer_id'];
        }

        // Apply points discount if the checkbox is checked AND the user has points
        $points_discount_amount = 0;
        if (isset($_POST['use_points']) && $user_points > 0) {
            $points_discount_amount = min($user_points * $points_to_peso_rate, $subtotal - $member_discount_amount - $retention_discount_amount);
        }

        $final_total = $subtotal + $packing_fee - $member_discount_amount - $retention_discount_amount - $points_discount_amount;
        
        // Handle file upload (now optional)
        $receipt_path = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/receipts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('receipt_', true) . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_path)) {
                $receipt_path = $upload_path;
            }
        }

        // Create order
        $order_stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total_price, discount_amount, cart_discount_amount, retention_discount_amount, points_discount, packing_fee, status, receipt_image, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_payment', ?, NOW())
        ");
        $order_stmt->execute([
            $user_id, 
            $final_total, 
            $member_discount_amount, 
            $cart_discount_amount, 
            $retention_discount_amount, 
            $points_discount_amount, 
            $packing_fee, 
            $receipt_path
        ]);
        $order_id = $pdo->lastInsertId();

        // Update user points
        $new_points = $user_points;
        if ($points_discount_amount > 0) {
            $new_points = 0;
        }
        $new_points += $bonus_points_to_add;
        
        $update_points_stmt = $pdo->prepare("UPDATE users SET points = ? WHERE user_id = ?");
        $update_points_stmt->execute([$new_points, $user_id]);

        // Mark retention offers as converted
        if (!empty($retention_offers_used)) {
            foreach ($retention_offers_used as $offer_id) {
                $mark_retention_used_stmt = $pdo->prepare("
                    UPDATE retention_offers 
                    SET status = 'converted', converted_at = NOW() 
                    WHERE offer_id = ? AND user_id = ?
                ");
                $mark_retention_used_stmt->execute([$offer_id, $user_id]);
            }
        }

        // Mark auto-applied retention offer as used
        if (isset($_SESSION['auto_applied_offer'])) {
            $auto_offer = $_SESSION['auto_applied_offer'];
            
            $mark_retention_used_stmt = $pdo->prepare("
                UPDATE retention_offers 
                SET status = 'converted', converted_at = NOW() 
                WHERE offer_id = ? AND user_id = ?
            ");
            $mark_retention_used_stmt->execute([$auto_offer['offer_id'], $user_id]);
            
            unset($_SESSION['auto_applied_offer']);
        }

        // Mark discounts as used
        if (!empty($discount_ids_to_mark)) {
            $placeholders = str_repeat('?,', count($discount_ids_to_mark) - 1) . '?';
            $mark_used_stmt = $pdo->prepare("
                UPDATE user_discounts 
                SET is_used = 1, used_at = NOW() 
                WHERE user_discount_id IN ($placeholders)
            ");
            $mark_used_stmt->execute($discount_ids_to_mark);
        }

        // Insert order items and update monthly_sold
        $order_item_stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, was_discounted)
            VALUES (?, ?, ?, ?, ?)
        ");

        $update_monthly_sold_stmt = $pdo->prepare("
            UPDATE products 
            SET monthly_sold = monthly_sold + ? 
            WHERE product_id = ?
        ");

        foreach ($cart_items as $item) {
            $was_discounted = $item['user_discount_id'] ? 1 : 0;
            
            $effective_price = $item['price'];
            if ($was_discounted) {
                $effective_price = $item['price'] - $item['discount_amount'];
            }
            
            $order_item_stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $effective_price,
                $was_discounted
            ]);

            $update_monthly_sold_stmt->execute([
                $item['quantity'],
                $item['product_id']
            ]);
        }

        // Remove selected items from cart
        $placeholders = str_repeat('?,', count($selected_items) - 1) . '?';
        $clear_cart_stmt = $pdo->prepare("
            DELETE ci FROM cart_items ci
            JOIN carts c ON ci.cart_id = c.cart_id
            WHERE c.user_id = ?
            AND ci.cart_item_id IN ($placeholders)
        ");
        $clear_params = array_merge([$user_id], $selected_items);
        $clear_cart_stmt->execute($clear_params);

        // Clear auto-applied offer from session
        if (isset($_SESSION['auto_applied_offer'])) {
            unset($_SESSION['auto_applied_offer']);
        }

        // Commit transaction
        $pdo->commit();

        // Redirect to order details on success
        $_SESSION['order_success'] = "Order placed successfully! Order ID: #" . $order_id;
        if ($bonus_points_to_add > 0) {
            $_SESSION['order_success'] .= " You earned " . $bonus_points_to_add . " bonus points!";
        }
        header("Location: order_details.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error placing order: " . $e->getMessage();
    }
}

// Get SELECTED cart items for display
$placeholders = str_repeat('?,', count($selected_items) - 1) . '?';

$cart_stmt = $pdo->prepare("
    SELECT
        ci.cart_item_id,
        ci.quantity,
        p.product_id,
        p.name,
        p.price,
        p.image_path,
        c.name as category_name,
        (p.price * ci.quantity) as original_subtotal,
        ud.user_discount_id,
        ud.discount_amount,
        ud.discount_type,
        ud.expires_at
    FROM cart_items ci
    JOIN carts cart ON ci.cart_id = cart.cart_id
    JOIN products p ON ci.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN user_discounts ud ON ci.cart_item_id = ud.cart_item_id 
        AND ud.user_id = ? 
        AND ud.is_used = 0
        AND (ud.expires_at IS NULL OR ud.expires_at > NOW())
    WHERE cart.user_id = ?
    AND ci.cart_item_id IN ($placeholders)
    ORDER BY ci.added_at DESC
");

$params = array_merge([$user_id, $user_id], $selected_items);
$cart_stmt->execute($params);
$cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active retention offers
$retention_offers = getActiveRetentionOffers($pdo, $user_id);

// Calculate totals
$total_items = 0;
$subtotal = 0;
$cart_discount_amount = 0;
$discounted_items_count = 0;

foreach ($cart_items as $item) {
    $total_items += $item['quantity'];
    
    $effective_price = $item['price'];
    if ($item['user_discount_id']) {
        $effective_price = $item['price'] - $item['discount_amount'];
        $discounted_items_count++;
        $cart_discount_amount += $item['discount_amount'];
    }
    
    $item_subtotal = $effective_price * $item['quantity'];
    $subtotal += $item_subtotal;
}

$packing_fee = 20.00;

$member_discount_amount = 0;
if ($is_member) {
    $discount_rate = 0.10;
    $member_discount_amount = $subtotal * $discount_rate;
}

// Calculate retention offers potential discount
$retention_discount_amount = 0;
$bonus_points_from_offers = 0;

if (isset($_SESSION['auto_applied_offer'])) {
    $auto_offer = $_SESSION['auto_applied_offer'];
    if ($auto_offer['discount_percentage'] > 0) {
        $retention_discount_amount += $subtotal * ($auto_offer['discount_percentage'] / 100);
    }
    if ($auto_offer['points_bonus'] > 0) {
        $bonus_points_from_offers += $auto_offer['points_bonus'];
    }
}

if (isset($_POST['retention_offer_id']) && !empty($_POST['retention_offer_id'])) {
    $offer_id = filter_var($_POST['retention_offer_id'], FILTER_VALIDATE_INT);
    if ($offer_id && $offer_id > 0) {
        foreach ($retention_offers as $offer) {
            if ($offer['offer_id'] == $offer_id) {
                if ($offer['discount_percentage'] > 0) {
                    $retention_discount_amount += $subtotal * ($offer['discount_percentage'] / 100);
                }
                if ($offer['points_bonus'] > 0) {
                    $bonus_points_from_offers += $offer['points_bonus'];
                }
            }
        }
    }
}

$points_discount_amount = 0;
if (isset($_POST['use_points']) && $user_points > 0) {
    $points_discount_amount = min($user_points * $points_to_peso_rate, $subtotal - $member_discount_amount - $retention_discount_amount);
}

$total_amount = $subtotal + $packing_fee - $member_discount_amount - $retention_discount_amount - $points_discount_amount;

// Initialize cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart_stmt_count = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0) as total_items
        FROM carts c
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        WHERE c.user_id = ?
    ");
    $cart_stmt_count->execute([$_SESSION['user_id']]);
    $cart_result = $cart_stmt_count->fetch();
    $cart_count = $cart_result['total_items'] ?? 0;
}

// Get notifications
$discount_notifications = getDiscountNotifications($pdo, $_SESSION['user_id']);
$notification_count = getNotificationCount($pdo, $_SESSION['user_id']);

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
    <title>Complete Order - Coopamart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/order.css">
  <style>
        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #28a745 0%, #177c38 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: white;
            white-space: nowrap;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .header-icon-link {
            position: relative;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px;
            border-radius: 50%;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            min-width: 40px;
            height: 40px;
        }
        
        .header-icon-link:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }
        
        .header-icon-link i {
            font-size: 1.1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 12px;
            border-radius: 25px;
            min-width: max-content;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-greeting {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .mobile-menu-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
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

        .notification-count, .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .notification-count {
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

        /* Compact Payment Section Styles */
        .payment-summary-compact {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .payment-summary-compact .payment-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px dashed #28a745;
        }

        .payment-summary-compact .payment-header i {
            font-size: 2rem;
            color: #28a745;
        }

        .payment-summary-compact .payment-header span {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
        }

        .payment-summary-compact .qr-code {
            width: 180px;
            height: 180px;
            margin: 0 auto 15px;
            display: block;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .payment-summary-compact .payment-amount-box {
            background: #28a745;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }

        .payment-summary-compact .payment-amount-box .label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .payment-summary-compact .payment-amount-box .amount {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .payment-summary-compact .payment-instructions-compact {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .payment-summary-compact .payment-instructions-compact ul {
            margin: 8px 0 0 20px;
            padding: 0;
            font-size: 0.9rem;
        }

        .payment-summary-compact .payment-instructions-compact li {
            margin-bottom: 5px;
        }

        /* Compact File Upload */
        .file-upload-compact {
            background: white;
            border: 2px dashed #28a745;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-compact:hover {
            background: #f8f9fa;
            border-color: #1e7e34;
        }

        .file-upload-compact.has-file {
            background: #d4edda;
            border-color: #28a745;
            border-style: solid;
        }

        .file-upload-compact .upload-icon {
            font-size: 2rem;
            color: #28a745;
            margin-bottom: 8px;
        }

        .file-upload-compact .upload-label {
            font-weight: 600;
            color: #28a745;
            font-size: 1rem;
            display: block;
            margin-bottom: 5px;
        }

        .file-upload-compact .file-input {
            display: none;
        }

        .file-upload-compact .upload-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 5px 0 0 0;
        }

        .file-upload-compact .optional-badge {
            display: inline-block;
            background: #17a2b8;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 5px;
        }

        /* Retention Offers Styles */
        .retention-offers-section {
            background: linear-gradient(135deg, #fce4ec, #f8bbd0);
            border: 1px solid #e83e8c;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }

        .retention-offer-item {
            margin-bottom: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            border-left: 4px solid #e83e8c;
        }

        .retention-offer-item:last-child {
            margin-bottom: 0;
        }

        .offer-highlight {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            padding: 10px 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }

        .bonus-points-badge {
            background: #ffc107;
            color: #000;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 8px;
        }

        .retention-discount-row {
            color: #e83e8c;
            font-weight: 600;
        }

        .bonus-points-row {
            color: #ffc107;
            font-weight: 600;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 0.8rem 0;
            }
            
            .header-content {
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 0 10px;
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

            .payment-summary-compact .qr-code {
                width: 150px;
                height: 150px;
            }

            .payment-summary-compact .payment-amount-box .amount {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .header-content {
                padding: 0 8px;
            }
            
            .logo {
                font-size: 1.4rem;
            }
            
            .logo i {
                font-size: 1.2rem;
            }
            
            .header-icon-link {
                padding: 8px;
                min-width: 36px;
                height: 36px;
            }
            
            .header-icon-link i {
                font-size: 1rem;
            }
            
            .user-avatar {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }
            
            .user-name {
                font-size: 0.8rem;
            }
            
            .user-greeting {
                font-size: 0.7rem;
            }

            .payment-summary-compact .qr-code {
                width: 130px;
                height: 130px;
            }
        }
    </style>
</head>
<body>

<!-- ========== HEADER ========== -->
<header class="header">
    <div class="header-content">
        <a href="index.php" class="logo" style="text-decoration: none;">
            <img src="logo.png" alt="Coopamart Logo" style="height: 66px; width: 78px;">
        </a>

        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="header-actions" id="headerActions">
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

<!-- ========== MAIN CONTENT ========== -->
<main class="main-container">
    <!-- Page Header -->
    <div class="page-header animate-fade-in">
        <h1 class="page-title">
            <i class="fas fa-file-invoice"></i> Complete Your Order
        </h1>
        <p class="page-subtitle">Review your selected items and complete payment</p>
        <div class="selected-items-badge">
            <i class="fas fa-check-circle"></i>
            <?= count($selected_items) ?> item<?= count($selected_items) !== 1 ? 's' : '' ?> selected for checkout
        </div>
    </div>

    <!-- Auto-Apply Success/Error Messages -->
    <?php if (isset($order_success_message)): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> 
            <strong>Special Offer Applied!</strong> 
            <?= htmlspecialchars($order_success_message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (isset($order_error_message)): ?>
        <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> 
            <?= htmlspecialchars($order_error_message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Cart Discount Banner -->
    <?php if ($cart_discount_amount > 0): ?>
        <div class="cart-discount-banner animate-fade-in">
            <i class="fas fa-gift"></i>
            <div class="cart-discount-banner-text">
                <h4>🎉 Special Discounts Applied!</h4>
                <p>You're saving ₱<?= number_format($cart_discount_amount, 2) ?> on <?= $discounted_items_count ?> item<?= $discounted_items_count !== 1 ? 's' : '' ?> (prices already reduced)</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Special Offers Banner -->
    <?php if (!empty($retention_offers)): ?>
        <div class="cart-discount-banner animate-fade-in" style="background: linear-gradient(135deg, #fce4ec, #f8bbd0); border-color: #e83e8c;">
            <i class="fas fa-gift" style="color: #e83e8c;"></i>
            <div class="cart-discount-banner-text">
                <h4>🎁 Special Offers Available!</h4>
                <p>You have <?= count($retention_offers) ?> special offer<?= count($retention_offers) !== 1 ? 's' : '' ?> that can be applied to this order</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Error Alert -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error animate-fade-in">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Order Grid -->
    <div class="order-grid">
        
        <!-- ========== LEFT COLUMN - ORDER ITEMS & GCASH ========== -->
        <div class="animate-fade-in">
            <!-- Order Items Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list card-icon"></i>
                    <h2 class="card-title">Selected Order Items (<?= $total_items ?> items)</h2>
                </div>
                
                <?php if (!empty($cart_items)): ?>
                    <?php foreach ($cart_items as $index => $item): 
                        $has_discount = $item['user_discount_id'] ? true : false;
                        $effective_price = $item['price'];
                        if ($has_discount) {
                            $effective_price = $item['price'] - $item['discount_amount'];
                        }
                        $item_subtotal = $effective_price * $item['quantity'];
                    ?>
                        <div class="order-item" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="item-image">
                                <img src="<?= htmlspecialchars($item['image_path'] ?? 'https://via.placeholder.com/80x80?text=No+Image') ?>"
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                            </div>
                            <div class="item-details">
                                <h3 class="item-name">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <?php if ($has_discount): ?>
                                        <span class="discount-badge-item">
                                            <i class="fas fa-tag"></i> ₱<?= number_format($item['discount_amount'], 2) ?> OFF
                                        </span>
                                    <?php endif; ?>
                                </h3>
                                <?php if ($item['category_name']): ?>
                                    <span class="item-category"><?= htmlspecialchars($item['category_name']) ?></span>
                                <?php endif; ?>
                                <p class="item-price">
                                    <?php if ($has_discount): ?>
                                        <span style="text-decoration: line-through; color: #999; font-size: 0.9rem;">
                                            ₱<?= number_format($item['price'], 2) ?>
                                        </span>
                                        <span style="color: #dc3545; font-weight: bold;">
                                            ₱<?= number_format($effective_price, 2) ?>
                                        </span>
                                    <?php else: ?>
                                        ₱<?= number_format($item['price'], 2) ?>
                                    <?php endif; ?>
                                    each
                                </p>
                            </div>
                            <div class="item-quantity">
                                <p>Qty: <?= $item['quantity'] ?></p>
                                <p class="item-total">
                                    ₱<?= number_format($item_subtotal, 2) ?>
                                </p>
                                <?php if ($has_discount): ?>
                                    <p style="text-decoration: line-through; color: #999; font-size: 0.85rem;">
                                        ₱<?= number_format($item['original_subtotal'], 2) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-shopping-cart"></i>
                        <span>No items selected. Please go back to cart and select items.</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- GCash Payment Section (Under Order Items) -->
            <div class="card payment-card" style="margin-top: 20px;">
                <div class="payment-summary-compact">
                    <div class="payment-header">
                        <i class="fab fa-google-pay"></i>
                        <span>Pay with GCash</span>
                    </div>
                    
                    <img src="https://businessmaker-academy.com/cms/wp-content/uploads/2022/04/Gcash-BMA-QRcode.jpg" 
                         alt="GCash QR Code" 
                         class="qr-code">
                    
                    <div class="payment-amount-box">
                        <div class="label">Total Amount to Pay</div>
                        <div class="amount" id="paymentAmountDisplay">₱<?= number_format($total_amount, 2) ?></div>
                    </div>

                    <div class="payment-instructions-compact">
                        <strong><i class="fas fa-info-circle"></i> Quick Instructions:</strong>
                        <ul>
                            <li>Scan QR code with GCash app</li>
                            <li>Pay the exact amount shown above</li>
                            <li>Upload receipt (optional but recommended)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== RIGHT COLUMN - ORDER SUMMARY ========== -->
        <div class="summary-sticky animate-fade-in">
            
            <!-- Customer Info -->
            <div class="customer-info">
                <div class="card-header mb-0">
                    <i class="fas fa-user-check card-icon"></i>
                    <h3 class="card-title">Customer Information</h3>
                </div>
                <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name'] ?? 'N/A') ?></p>
                <p><strong>Membership:</strong></p>
                <div class="mt-1">
                    <span class="membership-badge <?= $is_member ? 'membership-premium' : 'membership-regular' ?>">
                        <i class="fas fa-<?= $is_member ? 'star' : 'user' ?>"></i>
                        <?= $is_member ? 'SIDC Member' : 'Regular Customer' ?>
                    </span>
                </div>
            </div>

            <!-- Order Summary Section (Separate Card) -->
            <div class="card">
                <form action="order.php" method="POST" enctype="multipart/form-data" id="orderForm">
                    
                    <!-- Hidden fields -->
                    <?php foreach ($selected_items as $item_id): ?>
                        <input type="hidden" name="selected_items[]" value="<?= $item_id ?>">
                    <?php endforeach; ?>
                    
                    <?php foreach ($discounted_items as $item_id): ?>
                        <input type="hidden" name="discounted_items[]" value="<?= $item_id ?>">
                    <?php endforeach; ?>

                    <!-- Compact File Upload (Optional) -->
                    <div class="file-upload-compact" id="fileUpload" onclick="document.getElementById('receipt').click()">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-label">
                            Upload Payment Receipt
                            <span class="optional-badge">Optional</span>
                        </div>
                        <input type="file" 
                               name="receipt" 
                               id="receipt" 
                               class="file-input" 
                               accept="image/*">
                        <p class="upload-hint">
                            <i class="fas fa-info-circle"></i>
                            JPG, PNG, GIF (Max: 5MB)
                        </p>
                    </div>

                    <!-- Order Summary Section -->
                    <div class="card-header">
                        <i class="fas fa-receipt card-icon"></i>
                        <h3 class="card-title">Order Summary</h3>
                    </div>

                    <div class="summary-row">
                        <span><i class="fas fa-calculator"></i> Subtotal (<?= $total_items ?> items):</span>
                        <span class="font-bold">₱<?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($cart_discount_amount > 0): ?>
                        <div class="summary-row" style="color: #666; font-size: 0.9rem;">
                            <span><i class="fas fa-info-circle"></i> Cart discounts applied</span>
                            <span>-₱<?= number_format($cart_discount_amount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_member): ?>
                        <div class="summary-row text-success">
                            <span><i class="fas fa-percentage"></i> Member Discount (10%):</span>
                            <span class="font-bold">-₱<?= number_format($member_discount_amount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Retention Offers Section -->
                    <?php if (!empty($retention_offers)): ?>
                        <div class="retention-offers-section">
                            <div class="points-header">
                                <span class="font-bold">
                                    <i class="fas fa-gift" style="color: #e83e8c;"></i>
                                    Special Offers
                                </span>
                            </div>
                            <?php foreach ($retention_offers as $offer): ?>
                                <div class="retention-offer-item">
                                    <label class="checkbox-container">
                                        <input type="radio" 
                                               name="retention_offer_id" 
                                               value="<?= $offer['offer_id'] ?>"
                                               data-discount="<?= $offer['discount_percentage'] ?>"
                                               data-points="<?= $offer['points_bonus'] ?>"
                                               onchange="updateTotal()"
                                               <?= (isset($_SESSION['auto_applied_offer']) && $_SESSION['auto_applied_offer']['offer_id'] == $offer['offer_id']) ? 'checked' : '' ?>
                                               <?= (isset($_POST['retention_offer_id']) && $_POST['retention_offer_id'] == $offer['offer_id']) ? 'checked' : '' ?>>
                                        <span>
                                            <strong><?= $offer['discount_percentage'] ?>% Discount</strong>
                                            <?php if ($offer['points_bonus'] > 0): ?>
                                                <span class="bonus-points-badge">+<?= $offer['points_bonus'] ?> Points</span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                    <small style="color: #666; display: block; margin-left: 28px;">
                                        <?= htmlspecialchars($offer['message']) ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($retention_discount_amount > 0): ?>
                        <div class="summary-row retention-discount-row">
                            <span><i class="fas fa-gift"></i> Special Offer Discount:</span>
                            <span class="font-bold">-₱<?= number_format($retention_discount_amount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($bonus_points_from_offers > 0): ?>
                        <div class="summary-row bonus-points-row">
                            <span><i class="fas fa-star"></i> Bonus Points to Earn:</span>
                            <span class="font-bold">+<?= $bonus_points_from_offers ?> points</span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span><i class="fas fa-box"></i> Packing Fee:</span>
                        <span class="font-bold">₱<?= number_format($packing_fee, 2) ?></span>
                    </div>

                    <!-- Points Section -->
                    <?php if ($user_points > 0): ?>
                        <div class="points-section">
                            <div class="points-header">
                                <span class="font-bold">
                                    <i class="fas fa-star" style="color: #ffc107;"></i>
                                    Available Points
                                </span>
                                <span class="points-value"><?= $user_points ?> points</span>
                            </div>
                            <label class="checkbox-container">
                                <input type="checkbox" 
                                       name="use_points" 
                                       id="use_points" 
                                       value="1"
                                       onchange="updateTotal()"
                                       <?= isset($_POST['use_points']) ? 'checked' : '' ?>>
                                <span>Use all points for discount (Worth ₱<?= number_format($user_points * $points_to_peso_rate, 2) ?>)</span>
                            </label>
                        </div>
                    <?php endif; ?>

                    <?php if ($points_discount_amount > 0): ?>
                        <div class="summary-row" style="color: #ffc107;">
                            <span><i class="fas fa-star"></i> Points Discount:</span>
                            <span class="font-bold">-₱<?= number_format($points_discount_amount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Total -->
                    <div class="summary-total">
                        <div class="summary-row mb-0">
                            <span><i class="fas fa-money-bill-wave"></i> Total Amount:</span>
                            <span class="font-bold" id="totalAmount">₱<?= number_format($total_amount, 2) ?></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <input type="hidden" name="place_order" value="1">
                    <button type="submit" 
                            class="btn btn-primary btn-lg btn-full" 
                            id="submitBtn"
                            <?= empty($cart_items) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i>
                        <span id="submitBtnText">Complete Order - ₱<?= number_format($total_amount, 2) ?></span>
                    </button>
                </form>

                <!-- Back to Cart Link -->
                <a href="cart.php" class="btn btn-secondary btn-full mt-1">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Cart</span>
                </a>
            </div>
        </div>
    </div>
</main>

<!-- ========== JAVASCRIPT ========== -->
<script>
    function toggleMobileMenu() {
        const headerActions = document.getElementById('headerActions');
        headerActions.classList.toggle('show');
        
        if (headerActions.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    if (window.innerWidth <= 768) {
        document.querySelectorAll('.header-icon-link').forEach(link => {
            link.addEventListener('click', function() {
                const headerActions = document.getElementById('headerActions');
                headerActions.classList.remove('show');
                document.body.style.overflow = '';
            });
        });
    }

    const subtotal = <?= $subtotal ?>;
    const memberDiscount = <?= $member_discount_amount ?>;
    const packingFee = <?= $packing_fee ?>;
    const maxPointsDiscount = <?= $user_points * $points_to_peso_rate ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const orderForm = document.getElementById('orderForm');
        const submitBtn = document.getElementById('submitBtn');
        const fileInput = document.getElementById('receipt');
        const fileUpload = document.getElementById('fileUpload');
        
        orderForm.addEventListener('submit', function(e) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Order...';
            submitBtn.disabled = true;
            document.body.style.pointerEvents = 'none';
            document.body.style.opacity = '0.7';
        });

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('File size must be less than 5MB', 'error');
                    e.target.value = '';
                    return;
                }
                
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showAlert('Please upload a valid image file (JPG, PNG, or GIF)', 'error');
                    e.target.value = '';
                    return;
                }
                
                fileUpload.classList.add('has-file');
                fileUpload.querySelector('.upload-label').innerHTML = `
                    <i class="fas fa-check-circle"></i> Receipt Uploaded
                    <span class="optional-badge">✓</span>
                `;
                showAlert('Receipt uploaded successfully!', 'success');
            } else {
                fileUpload.classList.remove('has-file');
                fileUpload.querySelector('.upload-label').innerHTML = `
                    Upload Payment Receipt
                    <span class="optional-badge">Optional</span>
                `;
            }
        });
        
        function showAlert(message, type) {
            const existingAlerts = document.querySelectorAll('.dynamic-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type === 'error' ? 'error' : 'success'} dynamic-alert animate-fade-in`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                <span>${message}</span>
            `;
            
            const mainContent = document.querySelector('.main-container');
            const pageHeader = document.querySelector('.page-header');
            mainContent.insertBefore(alert, pageHeader.nextSibling);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    });

       function updateTotal() {
        const usePoints = document.getElementById('use_points');
        const retentionRadios = document.querySelectorAll('input[name="retention_offer_id"]:checked');
        const totalAmountElement = document.getElementById('totalAmount');
        const paymentAmountDisplay = document.getElementById('paymentAmountDisplay');
        const submitBtnText = document.getElementById('submitBtnText');
        
        let retentionDiscount = 0;
        let bonusPoints = 0;
        
        retentionRadios.forEach(radio => {
            const discountPercent = parseFloat(radio.getAttribute('data-discount')) || 0;
            const pointsBonus = parseInt(radio.getAttribute('data-points')) || 0;
            
            retentionDiscount += subtotal * (discountPercent / 100);
            bonusPoints += pointsBonus;
        });

        // Calculate total - subtotal already has cart discounts applied
        let currentTotal = subtotal + packingFee - memberDiscount - retentionDiscount;
        
        // Apply points discount if checked
        if (usePoints && usePoints.checked) {
            currentTotal -= maxPointsDiscount;
        }
        
        // Ensure total doesn't go below 0
        currentTotal = Math.max(0, currentTotal);
        
        // Format the total
        const formattedTotal = '₱' + currentTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        // Update all displays
        totalAmountElement.textContent = formattedTotal;
        paymentAmountDisplay.textContent = formattedTotal;
        submitBtnText.textContent = 'Complete Order - ' + formattedTotal;
        
        // Update bonus points display if any
        updateBonusPointsDisplay(bonusPoints);
    }

    function updateBonusPointsDisplay(bonusPoints) {
        // Remove existing bonus points row
        const existingBonusRow = document.querySelector('.bonus-points-row');
        if (existingBonusRow) {
            existingBonusRow.remove();
        }
        
        // Add new bonus points row if there are bonus points
        if (bonusPoints > 0) {
            const summaryTotal = document.querySelector('.summary-total');
            const bonusRow = document.createElement('div');
            bonusRow.className = 'summary-row bonus-points-row';
            bonusRow.innerHTML = `
                <span><i class="fas fa-star"></i> Bonus Points to Earn:</span>
                <span class="font-bold">+${bonusPoints} points</span>
            `;
            summaryTotal.parentNode.insertBefore(bonusRow, summaryTotal);
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

    // Add some additional styling for the new layout
    const style = document.createElement('style');
    style.textContent = `
        .payment-card {
            margin-top: 20px;
            border: 2px solid #28a745 !important;
        }
        
        .order-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }
        
        .summary-sticky {
            position: sticky;
            top: 100px;
        }
        
        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
            
            .summary-sticky {
                position: static;
            }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
</script>

</body>
</html>