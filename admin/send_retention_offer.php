<?php
session_start();
require_once '../include/config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Admin privileges required.']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;
$risk_level = $input['risk_level'] ?? 'medium'; // Get risk level from frontend

if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

try {
    // Validate user_id
    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if (!$user_id || $user_id < 1) {
        throw new Exception("Invalid user ID");
    }

    // Get user details for personalization
    $user_stmt = $pdo->prepare("SELECT user_id, full_name, email, membership_type, last_login_date FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    if (!$user) {
        throw new Exception("User not found");
    }

    // Analyze user behavior to generate personalized offer
    $user_analysis = analyzeUserBehavior($pdo, $user_id);
    $offer_details = generatePersonalizedOffer($user, $user_analysis, $risk_level);
    
    // Use transaction for database operations
    $pdo->beginTransaction();
    
    // Create retention offer record
    $insert_stmt = $pdo->prepare("
        INSERT INTO retention_offers 
        (user_id, offer_type, discount_percentage, points_bonus, message, expires_at, created_by, status, sent_at)
        VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), ?, 'sent', NOW())
    ");
    
    $success = $insert_stmt->execute([
        $user_id,
        $offer_details['offer_type'],
        $offer_details['discount_percentage'],
        $offer_details['points_bonus'],
        $offer_details['message'],
        $_SESSION['user_id']
    ]);
    
    if ($success) {
        $offer_id = $pdo->lastInsertId();
        $pdo->commit();
        
        // Log the action
        error_log("Admin {$_SESSION['user_id']} sent {$risk_level} risk retention offer {$offer_id} to user {$user_id}");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Retention offer sent successfully to ' . htmlspecialchars($user['full_name']) . '!',
            'offer_id' => $offer_id,
            'user_name' => $user['full_name'],
            'risk_level' => $risk_level,
            'offer_details' => $offer_details
        ]);
    } else {
        $pdo->rollBack();
        throw new Exception("Failed to create retention offer in database");
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in send_retention_offer.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in send_retention_offer.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Analyze user behavior to determine the best offer strategy
 */
function analyzeUserBehavior($pdo, $user_id) {
    $analysis = [
        'total_orders' => 0,
        'days_since_last_order' => null,
        'avg_order_value' => 0,
        'total_spent' => 0,
        'days_since_login' => null,
        'abandoned_carts' => 0,
        'has_orders' => false
    ];

    // Get order statistics
    $order_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            DATEDIFF(NOW(), MAX(created_at)) as days_since_last_order,
            AVG(total_price) as avg_order_value,
            SUM(total_price) as total_spent
        FROM orders 
        WHERE user_id = ? AND status NOT IN ('canceled')
    ");
    $order_stmt->execute([$user_id]);
    $order_data = $order_stmt->fetch();
    
    if ($order_data) {
        $analysis['total_orders'] = $order_data['total_orders'] ?? 0;
        $analysis['days_since_last_order'] = $order_data['days_since_last_order'] ?? null;
        
        // Fix division by zero for average order value
        $analysis['avg_order_value'] = ($order_data['total_orders'] ?? 0) > 0 ? 
            floatval(($order_data['total_spent'] ?? 0) / ($order_data['total_orders'] ?? 1)) : 0;
            
        $analysis['total_spent'] = floatval($order_data['total_spent'] ?? 0);
        $analysis['has_orders'] = ($analysis['total_orders'] > 0);
        
        // Handle NULL days_since_last_order for users with no orders
        if ($analysis['days_since_last_order'] === null && $analysis['total_orders'] > 0) {
            $analysis['days_since_last_order'] = 0; // They have orders but calculation failed
        }
    }

    // Get login information
    $user_stmt = $pdo->prepare("SELECT last_login_date, created_at FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();
    
    if ($user_data) {
        if ($user_data['last_login_date']) {
            $analysis['days_since_login'] = floor((time() - strtotime($user_data['last_login_date'])) / (60 * 60 * 24));
        } else {
            // If never logged in, use account creation date
            $analysis['days_since_login'] = floor((time() - strtotime($user_data['created_at'])) / (60 * 60 * 24));
        }
    }

    // Get abandoned carts count
    $cart_stmt = $pdo->prepare("
        SELECT COUNT(*) as abandoned_carts
        FROM carts c 
        JOIN cart_items ci ON c.cart_id = ci.cart_id 
        WHERE c.user_id = ? 
        AND ci.added_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
        AND NOT EXISTS (
            SELECT 1 FROM order_items oi 
            JOIN orders ord ON oi.order_id = ord.order_id 
            WHERE oi.product_id = ci.product_id 
            AND ord.user_id = c.user_id 
            AND ord.created_at > ci.added_at 
            AND ord.status NOT IN ('canceled')
        )
    ");
    $cart_stmt->execute([$user_id]);
    $cart_data = $cart_stmt->fetch();
    $analysis['abandoned_carts'] = $cart_data['abandoned_carts'] ?? 0;

    return $analysis;
}

/**
 * Generate personalized retention offer based on user analysis and risk level
 */
function generatePersonalizedOffer($user, $analysis, $risk_level = 'medium') {
    $membership = $user['membership_type'] ?? 'regular';
    
    // Base offer values that can be adjusted by risk level
    $base_discount = 15;
    $base_points = 50;
    
    // Adjust offer based on risk level
    if ($risk_level === 'high') {
        $base_discount += 5;  // More aggressive discount for high risk
        $base_points += 25;   // More points for high risk
    } elseif ($risk_level === 'medium') {
        $base_discount += 0;  // Standard for medium risk
        $base_points += 0;    // Standard for medium risk
    }
    
    // Determine offer strategy based on user behavior
    if (!$analysis['has_orders'] || $analysis['total_orders'] == 0) {
        // New customer who never ordered
        return [
            'offer_type' => 'welcome',
            'discount_percentage' => max(20, $base_discount), // At least 20% for new customers
            'points_bonus' => max(100, $base_points), // At least 100 points for new customers
            'message' => "Welcome to CoopMart, " . htmlspecialchars($user['full_name']) . "! We'd love for you to try us out. Enjoy " . max(20, $base_discount) . "% off your first order plus " . max(100, $base_points) . " bonus points to get you started!"
        ];
    } 
    elseif ($analysis['days_since_last_order'] > 60) {
        // Very inactive customer
        return [
            'offer_type' => 'win_back',
            'discount_percentage' => max(25, $base_discount + 5),
            'points_bonus' => max(75, $base_points + 25),
            'message' => "We've missed you, " . htmlspecialchars($user['full_name']) . "! It's been a while since your last order. Here's a special " . max(25, $base_discount + 5) . "% discount and " . max(75, $base_points + 25) . " bonus points to welcome you back to CoopMart!"
        ];
    }
    elseif ($analysis['days_since_last_order'] > 30) {
        // Moderately inactive
        return [
            'offer_type' => 're_engagement',
            'discount_percentage' => max(20, $base_discount + 5),
            'points_bonus' => max(50, $base_points),
            'message' => "Hi " . htmlspecialchars($user['full_name']) . "! We noticed you haven't visited us recently. We have a special " . max(20, $base_discount + 5) . "% discount and " . max(50, $base_points) . " bonus points waiting for your next order!"
        ];
    }
    elseif ($analysis['abandoned_carts'] > 0) {
        // Customer with abandoned carts
        return [
            'offer_type' => 'cart_recovery',
            'discount_percentage' => max(15, $base_discount),
            'points_bonus' => max(25, $base_points - 25),
            'message' => "Hi " . htmlspecialchars($user['full_name']) . "! We noticed you left some items in your cart. Here's " . max(15, $base_discount) . "% off and " . max(25, $base_points - 25) . " bonus points to help you complete your purchase!"
        ];
    }
    elseif ($analysis['avg_order_value'] > 100) {
        // High-value customer
        return [
            'offer_type' => 'loyalty_reward',
            'discount_percentage' => max(15, $base_discount),
            'points_bonus' => max(100, $base_points + 50),
            'message' => "Thank you for being a valued customer, " . htmlspecialchars($user['full_name']) . "! As a token of our appreciation, enjoy " . max(15, $base_discount) . "% off your next order plus " . max(100, $base_points + 50) . " bonus points!"
        ];
    }
    elseif ($membership === 'sidc_member') {
        // SIDC member special
        return [
            'offer_type' => 'membership_special',
            'discount_percentage' => max(15, $base_discount),
            'points_bonus' => max(75, $base_points + 25),
            'message' => "Special offer for our SIDC member " . htmlspecialchars($user['full_name']) . "! Enjoy an exclusive " . max(15, $base_discount) . "% discount plus " . max(75, $base_points + 25) . " bonus points on your next purchase!"
        ];
    }
    else {
        // Standard retention offer (adjusted by risk level)
        return [
            'offer_type' => 'standard_retention',
            'discount_percentage' => $base_discount,
            'points_bonus' => $base_points,
            'message' => "Hi " . htmlspecialchars($user['full_name']) . "! We have a special offer just for you - " . $base_discount . "% off your next purchase plus " . $base_points . " bonus points. We appreciate your business!"
        ];
    }
}
?>