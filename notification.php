<?php
// Add this for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'include/config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Function to update login streak - FIXED VERSION
function updateLoginStreak($pdo, $user_id) {
    $today = date('Y-m-d');
    
    // Get current user data
    $user_stmt = $pdo->prepare("SELECT login_streak, last_login_date FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();
    
    if (!$user_data) {
        return false;
    }
    
    $current_streak = $user_data['login_streak'] ?? 0;
    $last_login = $user_data['last_login_date'];
    
    // If already logged in today, don't update
    if ($last_login === $today) {
        return $current_streak;
    }
    
    $new_streak = $current_streak;
    
    if ($last_login === null) {
        // First time login
        $new_streak = 1;
    } else {
        $last_login_date = new DateTime($last_login);
        $today_date = new DateTime($today);
        $interval = $last_login_date->diff($today_date);
        $days_diff = $interval->days;
        
        if ($days_diff == 1) {
            // Consecutive day - increment streak
            $new_streak = $current_streak + 1;
        } else if ($days_diff > 1) {
            // Missed days - reset streak to 1
            $new_streak = 1;
        }
    }
    
    // Update the database
    $update_stmt = $pdo->prepare("
        UPDATE users 
        SET login_streak = ?, last_login_date = ?
        WHERE user_id = ?
    ");
    $update_stmt->execute([$new_streak, $today, $user_id]);
    
    return $new_streak;
}

// Update streak on page load
$current_streak = updateLoginStreak($pdo, $_SESSION['user_id']);

// Get cart count for current user
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

// Get user information
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch();

// Function to check for active discount notifications
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

// Function to get retention offers notifications - FIXED VERSION
function getRetentionOffers($pdo, $user_id) {
    $current_time = date('Y-m-d H:i:s');
    
    $retention_stmt = $pdo->prepare("
        SELECT 
            ro.offer_id,
            ro.user_id,
            ro.offer_type,
            ro.discount_percentage,
            ro.points_bonus,
            ro.message,
            ro.status,
            ro.sent_at,
            ro.expires_at,
            ro.created_by,
            u.full_name as sender_name
        FROM retention_offers ro
        LEFT JOIN users u ON ro.created_by = u.user_id
        WHERE ro.user_id = ? 
        AND ro.status IN ('sent', 'opened')
        AND (ro.expires_at IS NULL OR ro.expires_at > ?)
        ORDER BY ro.sent_at DESC
    ");
    
    $retention_stmt->execute([$user_id, $current_time]);
    $offers = $retention_stmt->fetchAll();
    
    return $offers;
}

// Function to get notification count - FIXED VERSION
function getNotificationCount($pdo, $user_id) {
    $current_time = date('Y-m-d H:i:s');
    
    // Count discount notifications
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as notification_count
        FROM user_discounts 
        WHERE user_id = ? 
        AND is_used = 0 
        AND (expires_at IS NULL OR expires_at > ?)
    ");
    
    $count_stmt->execute([$user_id, $current_time]);
    $discount_count = $count_stmt->fetch()['notification_count'] ?? 0;
    
    // Count retention offers - FIXED with expiry check
    $retention_count_stmt = $pdo->prepare("
        SELECT COUNT(*) as offer_count
        FROM retention_offers 
        WHERE user_id = ? 
        AND status IN ('sent', 'opened')
        AND (expires_at IS NULL OR expires_at > ?)
    ");
    
    $retention_count_stmt->execute([$user_id, $current_time]);
    $retention_count = $retention_count_stmt->fetch()['offer_count'] ?? 0;
    
    return $discount_count + $retention_count;
}

// Get notifications for current user
$discount_notifications = getDiscountNotifications($pdo, $_SESSION['user_id']);
$retention_offers = getRetentionOffers($pdo, $_SESSION['user_id']);
$notification_count = getNotificationCount($pdo, $_SESSION['user_id']);

// Handle discount usage (mark as used)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_discount_used' && isset($_POST['discount_id'])) {
        try {
            $discount_id = filter_var($_POST['discount_id'], FILTER_VALIDATE_INT);
            if (!$discount_id || $discount_id < 1) {
                throw new Exception("Invalid discount ID");
            }
            
            $used_at = date('Y-m-d H:i:s');
            
            $update_stmt = $pdo->prepare("
                UPDATE user_discounts 
                SET is_used = 1, used_at = ?
                WHERE user_discount_id = ? AND user_id = ?
            ");
            
            if ($update_stmt->execute([$used_at, $discount_id, $_SESSION['user_id']])) {
                $success_message = "Discount marked as used!";
                // Refresh notifications
                $discount_notifications = getDiscountNotifications($pdo, $_SESSION['user_id']);
                $notification_count = getNotificationCount($pdo, $_SESSION['user_id']);
            } else {
                $error_message = "Failed to update discount.";
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
    
    // Handle retention offer actions
    if ($_POST['action'] === 'open_retention_offer' && isset($_POST['offer_id'])) {
        try {
            $offer_id = filter_var($_POST['offer_id'], FILTER_VALIDATE_INT);
            if (!$offer_id || $offer_id < 1) {
                throw new Exception("Invalid offer ID");
            }
            
            $update_stmt = $pdo->prepare("
                UPDATE retention_offers 
                SET status = 'opened'
                WHERE offer_id = ? AND user_id = ?
            ");
            
            if ($update_stmt->execute([$offer_id, $_SESSION['user_id']])) {
                // Refresh offers
                $retention_offers = getRetentionOffers($pdo, $_SESSION['user_id']);
            }
        } catch (Exception $e) {
            // Silent fail for opening offers
            error_log("Error opening retention offer: " . $e->getMessage());
        }
    }
    
    // Handle retention offer claim - FIXED VERSION (no points_bonus column error)
    if ($_POST['action'] === 'claim_retention_offer' && isset($_POST['offer_id'])) {
        try {
            $offer_id = filter_var($_POST['offer_id'], FILTER_VALIDATE_INT);
            if (!$offer_id || $offer_id < 1) {
                throw new Exception("Invalid offer ID");
            }
            
            // Get offer details
            $offer_stmt = $pdo->prepare("SELECT * FROM retention_offers WHERE offer_id = ? AND user_id = ?");
            $offer_stmt->execute([$offer_id, $_SESSION['user_id']]);
            $offer = $offer_stmt->fetch();
            
            if ($offer) {
                // Use transaction for both operations
                $pdo->beginTransaction();
                
                $success_details = [];
                
                // Handle discount if present
                if ($offer['discount_percentage'] > 0) {
                    // Create user discount based on retention offer
                    $discount_code = generateDiscountCode();
                    $description = "Retention offer: " . $offer['message'];
                    
                    // Create user discount (without points_bonus column)
                    $discount_stmt = $pdo->prepare("
                        INSERT INTO user_discounts 
                        (user_id, discount_code, discount_type, discount_amount, discount_percentage, expires_at, description)
                        VALUES (?, ?, 'retention_offer', 0, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), ?)
                    ");
                    
                    $discount_stmt->execute([
                        $_SESSION['user_id'],
                        $discount_code,
                        $offer['discount_percentage'],
                        $description
                    ]);
                    
                    $success_details[] = $offer['discount_percentage'] . "% discount";
                }
                
                // Handle points bonus if present
                if ($offer['points_bonus'] > 0) {
                    // Add points to user account
                    $update_points_stmt = $pdo->prepare("
                        UPDATE users 
                        SET points = COALESCE(points, 0) + ? 
                        WHERE user_id = ?
                    ");
                    $update_points_stmt->execute([
                        $offer['points_bonus'],
                        $_SESSION['user_id']
                    ]);
                    
                    $success_details[] = $offer['points_bonus'] . " bonus points";
                }
                
                // Mark retention offer as used
                $update_stmt = $pdo->prepare("
                    UPDATE retention_offers 
                    SET status = 'used'
                    WHERE offer_id = ? AND user_id = ?
                ");
                
                if ($update_stmt->execute([$offer_id, $_SESSION['user_id']])) {
                    $pdo->commit();
                    
                    $success_message = "Retention offer claimed successfully!";
                    if (!empty($success_details)) {
                        $success_message .= " You received: " . implode(" and ", $success_details) . ".";
                    }
                    
                    // Refresh data
                    $retention_offers = getRetentionOffers($pdo, $_SESSION['user_id']);
                    $discount_notifications = getDiscountNotifications($pdo, $_SESSION['user_id']);
                    $notification_count = getNotificationCount($pdo, $_SESSION['user_id']);
                    
                    // Refresh user data to get updated points
                    $user_stmt->execute([$_SESSION['user_id']]);
                    $user = $user_stmt->fetch();
                } else {
                    $pdo->rollBack();
                    throw new Exception("Failed to mark offer as used");
                }
            } else {
                throw new Exception("Offer not found");
            }
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = "Error claiming offer: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'update_personal_details') {
        try {
            $phone = filter_var($_POST['phone'] ?? null, FILTER_SANITIZE_STRING);
            $gender = in_array($_POST['gender'] ?? null, ['male', 'female', 'other', '']) ? $_POST['gender'] : null;
            
            // Validate birth date
            $birth_date = null;
            if (!empty($_POST['birth_date'])) {
                $birth_timestamp = strtotime($_POST['birth_date']);
                if ($birth_timestamp !== false) {
                    // Validate reasonable age (between 13 and 120 years)
                    $min_age = strtotime('-120 years');
                    $max_age = strtotime('-13 years');
                    if ($birth_timestamp >= $min_age && $birth_timestamp <= $max_age) {
                        $birth_date = date('Y-m-d', $birth_timestamp);
                    }
                }
            }
            
            $address = filter_var($_POST['address'] ?? null, FILTER_SANITIZE_STRING);
            $city = filter_var($_POST['city'] ?? null, FILTER_SANITIZE_STRING);
            $province = filter_var($_POST['province'] ?? null, FILTER_SANITIZE_STRING);
            $zip_code = filter_var($_POST['zip_code'] ?? null, FILTER_SANITIZE_STRING);
            
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET phone = ?, gender = ?, birth_date = ?, address = ?, 
                    city = ?, province = ?, zip_code = ?
                WHERE user_id = ?
            ");
            
            if ($update_stmt->execute([$phone, $gender, $birth_date, $address, $city, $province, $zip_code, $_SESSION['user_id']])) {
                $success_message = "Personal details updated successfully!";
                // Refresh user data
                $user_stmt->execute([$_SESSION['user_id']]);
                $user = $user_stmt->fetch();
            } else {
                $error_message = "Failed to update personal details.";
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
    
    // Handle membership update
    if ($_POST['action'] === 'update_membership') {
        try {
            $new_membership = $_POST['membership_type'] ?? '';
            $valid_memberships = ['regular', 'sidc_member', 'non_member'];
            
            if (in_array($new_membership, $valid_memberships)) {
                $update_stmt = $pdo->prepare("UPDATE users SET membership_type = ? WHERE user_id = ?");
                if ($update_stmt->execute([$new_membership, $_SESSION['user_id']])) {
                    $success_message = "Membership updated successfully!";
                    // Refresh user data
                    $user_stmt->execute([$_SESSION['user_id']]);
                    $user = $user_stmt->fetch();
                } else {
                    $error_message = "Failed to update membership.";
                }
            } else {
                $error_message = "Invalid membership type selected.";
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Helper function to generate discount code
function generateDiscountCode() {
    $prefix = 'RETENTION';
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . $random;
}

// Function to get membership details
function getMembershipDetails($type) {
    $memberships = [
        'sidc_member' => [
            'name' => 'SIDC Member',
            'discount' => '10%',
            'description' => 'Special discount for SIDC members',
            'color' => '#28a745'
        ],
        'regular' => [
            'name' => 'Regular Member',
            'discount' => '5%',
            'description' => 'Standard membership benefits',
            'color' => '#6c757d'
        ],
        'non_member' => [
            'name' => 'Non-Member',
            'discount' => '0%',
            'description' => 'No membership discount',
            'color' => '#dc3545'
        ]
    ];
    
    return $memberships[$type] ?? $memberships['regular'];
}

// Function to get streak message and icon
function getStreakInfo($streak) {
    if ($streak == 0) {
        return ['message' => 'Start your streak!', 'icon' => 'fa-play', 'color' => '#6c757d'];
    } else if ($streak == 1) {
        return ['message' => 'Great start!', 'icon' => 'fa-seedling', 'color' => '#28a745'];
    } else if ($streak < 7) {
        return ['message' => 'Building momentum!', 'icon' => 'fa-chart-line', 'color' => '#28a745'];
    } else if ($streak < 30) {
        return ['message' => 'On fire! 🔥', 'icon' => 'fa-fire', 'color' => '#fd7e14'];
    } else {
        return ['message' => 'Legendary streak! 🏆', 'icon' => 'fa-crown', 'color' => '#ffc107'];
    }
}

// Function to get discount type badge
function getDiscountTypeBadge($type) {
    $badges = [
        'inactivity' => ['text' => 'Welcome Back', 'color' => '#007bff', 'icon' => 'fa-calendar-check'],
        'promotional' => ['text' => 'Special Offer', 'color' => '#28a745', 'icon' => 'fa-tag'],
        'loyalty' => ['text' => 'Loyalty Reward', 'color' => '#ffc107', 'icon' => 'fa-award'],
        'retention_offer' => ['text' => 'Retention Offer', 'color' => '#e83e8c', 'icon' => 'fa-heart']
    ];
    
    return $badges[$type] ?? $badges['promotional'];
}

// Function to get retention offer type badge
function getRetentionOfferBadge($offer_type) {
    $badges = [
        'welcome' => ['text' => 'Welcome Offer', 'color' => '#17a2b8', 'icon' => 'fa-handshake'],
        'win_back' => ['text' => 'Win-Back Offer', 'color' => '#dc3545', 'icon' => 'fa-heartbeat'],
        're_engagement' => ['text' => 'Re-engagement', 'color' => '#fd7e14', 'icon' => 'fa-redo'],
        'cart_recovery' => ['text' => 'Cart Recovery', 'color' => '#6f42c1', 'icon' => 'fa-shopping-cart'],
        'loyalty_reward' => ['text' => 'Loyalty Reward', 'color' => '#20c997', 'icon' => 'fa-crown'],
        'membership_special' => ['text' => 'Membership Special', 'color' => '#e83e8c', 'icon' => 'fa-id-card'],
        'standard_retention' => ['text' => 'Special Offer', 'color' => '#28a745', 'icon' => 'fa-gift']
    ];
    
    return $badges[$offer_type] ?? $badges['standard_retention'];
}

$streak_info = getStreakInfo($user['login_streak']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Coopamart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/account.css">
     <style>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #28a745 0%, #177c38 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 15px var(--shadow-light);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-bar {
            flex: 1;
            max-width: 500px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 50px 12px 20px;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            box-shadow: inset 0 1px 3px var(--shadow-light);
        }

        .search-bar button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .search-bar button:hover {
            background: var(--primary-dark);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.2rem;
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
        
        .header-icon-link:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }
        
        .header-icon-link i {
            font-size: 1.2rem;
        }

        .cart-count, .notification-count {
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 15px;
            border-radius: 30px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Mobile Menu Styles */
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

            /* Adjust page layout for mobile */
            .page-container {
                flex-direction: column;
                padding: 0 15px;
            }

            .sidebar-nav {
                width: 100%;
                min-width: auto;
                order: 2;
            }

            .main-content {
                order: 1;
                padding: 1.5rem;
            }
        }

        /* Main Layout */
        .page-container {
            display: flex;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            gap: 2rem;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            width: 280px;
            min-width: 280px;
            background: var(--card-background);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 25px var(--shadow-light);
            align-self: flex-start;
        }

        .sidebar-nav-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav a {
            display: block;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--secondary-color);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar-nav a.active, .sidebar-nav a:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
            transform: translateX(5px);
        }
        
        .sidebar-nav a i {
            margin-right: 12px;
            color: var(--primary-color);
            transition: color 0.3s ease;
        }
        
        .sidebar-nav a.active i, .sidebar-nav a:hover i {
            color: var(--primary-dark);
        }

        .main-content {
            flex: 1;
            background: var(--card-background);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 8px 25px var(--shadow-light);
        }

        /* Notification Styles */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .notification-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-card {
            background: var(--card-background);
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .notification-card:hover {
            box-shadow: 0 4px 15px var(--shadow-light);
            transform: translateY(-2px);
        }

        .notification-card.new {
            border-left: 4px solid var(--primary-color);
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .notification-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .discount-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }

        .discount-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0.5rem 0;
        }

        .notification-message {
            color: var(--secondary-color);
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .notification-time {
            font-size: 0.85rem;
            color: var(--secondary-color);
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
        }

        .btn-outline:hover {
            background: var(--secondary-color);
            color: white;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }

        .expiry-warning {
            color: #dc3545;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.5rem;
        }

        .discount-type-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .notification-card.retention-offer {
            border-left: 4px solid #e83e8c;
            background: linear-gradient(135deg, #fce4ec 0%, #ffffff 100%);
        }

        .points-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: #ffc107;
            margin: 0.5rem 0;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: black;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .offer-highlight {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            padding: 10px 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
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

            <!-- 🔔 Notification Icon -->
            <a href="notification.php" class="header-icon-link" aria-label="Notifications" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="icon-label">Notifications</span>
                <?php if ($notification_count > 0): ?>
                    <span class="notification-count"><?= htmlspecialchars($notification_count, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </a>

            <a href="cart.php" class="header-icon-link" aria-label="Cart" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="icon-label">Cart</span>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?= htmlspecialchars($cart_count, ENT_QUOTES, 'UTF-8') ?></span>
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
                    <div style="font-weight: 600;"><?= htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size: 0.8rem; opacity: 0.8;">Welcome back!</div>
                </div>
            </div>
        </div>
    </div>
</header>

    <div class="page-container">
        <nav class="sidebar-nav">
            <h2 class="sidebar-nav-title">My Account</h2>
            <ul>
                <li><a href="account.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="order_details.php"><i class="fas fa-box"></i> My Orders</a></li>
                <li><a href="notification.php" class="active"><i class="fas fa-bell"></i> Notifications 
                    <?php if ($notification_count > 0): ?>
                        <span style="background: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7rem; margin-left: 5px;"><?= htmlspecialchars($notification_count, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Shopping Cart</a></li>
                <li><a href="auth/log_out.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Notifications</h1>
                <p>Manage your discount alerts and special offers</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- Retention Offers Section -->
            <?php if (!empty($retention_offers)): ?>
                <div class="notification-section">
                    <h2 class="section-title">
                        <i class="fas fa-heart" style="color: #e83e8c;"></i>
                        Special Retention Offers
                        <span style="background: #e83e8c; color: white; border-radius: 50%; padding: 2px 8px; font-size: 0.8rem;"><?= count($retention_offers) ?></span>
                    </h2>

                    <div class="notification-list">
                        <?php foreach ($retention_offers as $offer): ?>
                            <?php 
                            $offer_badge = getRetentionOfferBadge($offer['offer_type']);
                            $is_new = $offer['status'] === 'sent';
                            $expires_soon = false;
                            if ($offer['expires_at']) {
                                $expires_date = new DateTime($offer['expires_at']);
                                $now = new DateTime();
                                $days_until_expiry = $now->diff($expires_date)->days;
                                $expires_soon = $days_until_expiry <= 3;
                            }
                            ?>
                            <div class="notification-card retention-offer <?= $is_new ? 'new' : '' ?>" id="offer-<?= htmlspecialchars($offer['offer_id'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="notification-header">
                                    <div>
                                        <div class="discount-type-info">
                                            <i class="fas <?= htmlspecialchars($offer_badge['icon'], ENT_QUOTES, 'UTF-8') ?>" style="color: <?= htmlspecialchars($offer_badge['color'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                            <span style="color: <?= htmlspecialchars($offer_badge['color'], ENT_QUOTES, 'UTF-8') ?>; font-weight: 600;"><?= htmlspecialchars($offer_badge['text'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if ($is_new): ?>
                                                <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 8px;">NEW</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-title">
                                            Personalized Offer Just For You!
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($offer['discount_percentage']): ?>
                                            <div class="discount-amount">
                                                <?= htmlspecialchars($offer['discount_percentage'], ENT_QUOTES, 'UTF-8') ?>% OFF
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($offer['points_bonus']): ?>
                                            <div class="points-amount">
                                                +<?= htmlspecialchars($offer['points_bonus'], ENT_QUOTES, 'UTF-8') ?> Points
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="notification-message">
                                    <?= htmlspecialchars($offer['message'], ENT_QUOTES, 'UTF-8') ?>
                                    
                                    <?php if ($offer['discount_percentage']): ?>
                                        <div class="offer-highlight">
                                            <i class="fas fa-gift"></i> Get <strong><?= htmlspecialchars($offer['discount_percentage'], ENT_QUOTES, 'UTF-8') ?>% discount</strong> on your next purchase!
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($offer['points_bonus']): ?>
                                        <div class="offer-highlight">
                                            <i class="fas fa-star"></i> Receive <strong><?= htmlspecialchars($offer['points_bonus'], ENT_QUOTES, 'UTF-8') ?> bonus points</strong> on your next order!
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="notification-footer">
                                    <div>
                                        <div class="notification-time">
                                            <i class="far fa-clock"></i>
                                            Sent on <?= htmlspecialchars(date('M j, Y', strtotime($offer['sent_at'])), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="notification-time">
                                            <i class="fas fa-user-tie"></i>
                                            From: <?= htmlspecialchars($offer['sender_name'] ?? 'CoopMart Admin', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <?php if ($offer['expires_at']): ?>
                                            <div class="notification-time <?= $expires_soon ? 'expiry-warning' : '' ?>">
                                                <i class="fas fa-hourglass-end"></i>
                                                Expires on <?= htmlspecialchars(date('M j, Y', strtotime($offer['expires_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($expires_soon): ?>
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-actions">
                                        <?php if ($is_new): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="action" value="open_retention_offer">
                                                <input type="hidden" name="offer_id" value="<?= htmlspecialchars($offer['offer_id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-outline btn-sm">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="claim_retention_offer">
                                            <input type="hidden" name="offer_id" value="<?= htmlspecialchars($offer['offer_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Claim this special offer?')">
                                                <i class="fas fa-gift"></i> Claim Offer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Regular Discounts Section -->
            <div class="notification-section">
                <h2 class="section-title">
                    <i class="fas fa-tags"></i>
                    Active Discounts
                    <?php if (!empty($discount_notifications)): ?>
                        <span style="background: var(--primary-color); color: white; border-radius: 50%; padding: 2px 8px; font-size: 0.8rem;"><?= count($discount_notifications) ?></span>
                    <?php endif; ?>
                </h2>

                <div class="notification-list">
                    <?php if (empty($discount_notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No active discount notifications</h3>
                            <p>You don't have any active discount notifications at the moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($discount_notifications as $notification): ?>
                            <?php 
                            $discount_badge = getDiscountTypeBadge($notification['discount_type']);
                            $expires_soon = false;
                            if ($notification['expires_at']) {
                                $expires_date = new DateTime($notification['expires_at']);
                                $now = new DateTime();
                                $days_until_expiry = $now->diff($expires_date)->days;
                                $expires_soon = $days_until_expiry <= 3;
                            }
                            ?>
                            <div class="notification-card new">
                                <div class="notification-header">
                                    <div>
                                        <div class="discount-type-info">
                                            <i class="fas <?= htmlspecialchars($discount_badge['icon'], ENT_QUOTES, 'UTF-8') ?>" style="color: <?= htmlspecialchars($discount_badge['color'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                            <span style="color: <?= htmlspecialchars($discount_badge['color'], ENT_QUOTES, 'UTF-8') ?>; font-weight: 600;"><?= htmlspecialchars($discount_badge['text'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <div class="notification-title">
                                            <?= $notification['product_name'] ? htmlspecialchars($notification['product_name'], ENT_QUOTES, 'UTF-8') : 'Special Discount' ?>
                                        </div>
                                    </div>
                                    <div class="discount-amount">
                                        $<?= htmlspecialchars(number_format($notification['discount_amount'], 2), ENT_QUOTES, 'UTF-8') ?> OFF
                                    </div>
                                </div>

                                <div class="notification-message">
                                    <?php if ($notification['discount_type'] === 'inactivity'): ?>
                                        Welcome back! We've missed you. Here's a special discount for your return.
                                    <?php elseif ($notification['discount_type'] === 'promotional'): ?>
                                        Special promotional discount applied to your account.
                                    <?php elseif ($notification['discount_type'] === 'loyalty'): ?>
                                        Thank you for your loyalty! Enjoy this exclusive reward.
                                    <?php elseif ($notification['discount_type'] === 'retention_offer'): ?>
                                        Special retention offer for valued customers like you!
                                    <?php endif; ?>
                                    
                                    <?php if ($notification['original_price'] > 0): ?>
                                        <br><strong>Original price: $<?= htmlspecialchars(number_format($notification['original_price'], 2), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php endif; ?>
                                </div>

                                <div class="notification-footer">
                                    <div>
                                        <div class="notification-time">
                                            <i class="far fa-clock"></i>
                                            Added on <?= htmlspecialchars(date('M j, Y', strtotime($notification['applied_at'])), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <?php if ($notification['expires_at']): ?>
                                            <div class="notification-time <?= $expires_soon ? 'expiry-warning' : '' ?>">
                                                <i class="fas fa-hourglass-half"></i>
                                                Expires on <?= htmlspecialchars(date('M j, Y', strtotime($notification['expires_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($expires_soon): ?>
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="mark_discount_used">
                                            <input type="hidden" name="discount_id" value="<?= htmlspecialchars($notification['user_discount_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Mark this discount as used?')">
                                                <i class="fas fa-check"></i> Mark Used
                                            </button>
                                        </form>
                                        <a href="cart.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-shopping-cart"></i> Use Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const successMessages = document.querySelectorAll('[style*="background: #d4edda"]');
            successMessages.forEach(msg => {
                msg.style.display = 'none';
            });
        }, 5000);

        // Auto-mark retention offers as opened when viewed
        document.addEventListener('DOMContentLoaded', function() {
            const newOffers = document.querySelectorAll('.notification-card.retention-offer.new');
            newOffers.forEach(offer => {
                const offerId = offer.id.replace('offer-', '');
                // Mark as opened after 2 seconds of being in view
                setTimeout(() => {
                    const formData = new FormData();
                    formData.append('action', 'open_retention_offer');
                    formData.append('offer_id', offerId);
                    formData.append('csrf_token', '<?= $csrf_token ?>');
                    
                    fetch('notification.php', {
                        method: 'POST',
                        body: formData
                    });
                }, 2000);
            });
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
    </script>
</body>
</html>