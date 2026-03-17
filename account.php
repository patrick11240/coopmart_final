<?php
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

// Handle personal details update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_personal_details') {
        try {
            // Check if terms were accepted
            if (!isset($_POST['accept_terms']) || $_POST['accept_terms'] !== 'yes') {
                $error_message = "You must accept the Terms & Conditions to update your personal details.";
            } else {
                $phone = $_POST['phone'] ?? null;
                $gender = $_POST['gender'] ?? null;
                $birth_date = $_POST['birth_date'] ?? null;
                $address = $_POST['address'] ?? null;
                $city = $_POST['city'] ?? null;
                $province = $_POST['province'] ?? null;
                $zip_code = $_POST['zip_code'] ?? null;
                
                $update_stmt = $pdo->prepare("
                    UPDATE users 
                    SET phone = ?, gender = ?, birth_date = ?, address = ?, 
                        city = ?, province = ?, zip_code = ?, terms_accepted_at = NOW()
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
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
    
    // Handle membership update
    if ($_POST['action'] === 'update_membership') {
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
    }
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

$streak_info = getStreakInfo($user['login_streak']);
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
    <title>Account - Coopamart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/account.css">
    <style>
        /* Personal Details Section Styles */
        .personal-details-section {
            background: var(--card-bg, #ffffff);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-color, #333);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: var(--primary-color, #007bff);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color, #007bff);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .save-details-btn {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .save-details-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .save-details-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .completion-indicator {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }

        .completion-text {
            font-weight: 600;
            color: #28a745;
        }

        /* Terms & Conditions Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .close-modal:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 2rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        .terms-content {
            line-height: 1.6;
            color: #555;
        }

        .terms-content h3 {
            color: #007bff;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .terms-content p {
            margin-bottom: 1rem;
        }

        .terms-content ul {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }

        .terms-content li {
            margin-bottom: 0.5rem;
        }

        .terms-checkbox {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 0.2rem;
        }

        .checkbox-group label {
            font-weight: 600;
            color: #333;
            cursor: pointer;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .terms-trigger {
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 600;
        }

        .terms-trigger:hover {
            color: #0056b3;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
            
            .modal-body {
                padding: 1.5rem;
                max-height: 70vh;
            }
            
            .modal-header {
                padding: 1rem 1.5rem;
            }
            
            .modal-footer {
                padding: 1rem 1.5rem;
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

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

        /* Make sure the header icon links have relative positioning */
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
                    <div style="font-size: 0.8rem; opacity: 0.8;">Welcome!</div>
                </div>
            </div>
        </div>
    </div>
</header>

    <div class="page-container">
        <main class="main-content">
            <h1 class="page-title">My Account</h1>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Login Streak Section -->
            <section class="streak-section">
                <h2 class="section-title">
                    <i class="fas fa-fire"></i>
                    Login Streak
                </h2>
                
                <div class="streak-container">
                    <div class="streak-header">
                        <div>
                            <div class="streak-number"><?= $user['login_streak'] ?></div>
                            <div class="streak-message"><?= $streak_info['message'] ?></div>
                            <div class="streak-description">
                                <?php if ($user['login_streak'] == 0): ?>
                                    Log in daily to build your streak and unlock rewards!
                                <?php else: ?>
                                    You've logged in for <?= $user['login_streak'] ?> consecutive <?= $user['login_streak'] == 1 ? 'day' : 'days' ?>!
                                    Keep it up! 🌟
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="streak-icon" style="color: <?= $streak_info['color'] ?>;">
                            <i class="fas <?= $streak_info['icon'] ?>"></i>
                        </div>
                    </div>

                    <div class="streak-progress">
                        <div class="streak-milestones">
                            <div class="milestone <?= $user['login_streak'] >= 1 ? 'achieved' : '' ?>">
                                <div class="milestone-number">1</div>
                                <div class="milestone-label">First Day</div>
                            </div>
                            <div class="milestone <?= $user['login_streak'] >= 7 ? 'achieved' : '' ?>">
                                <div class="milestone-number">7</div>
                                <div class="milestone-label">One Week</div>
                            </div>
                            <div class="milestone <?= $user['login_streak'] >= 30 ? 'achieved' : '' ?>">
                                <div class="milestone-number">30</div>
                                <div class="milestone-label">One Month</div>
                            </div>
                            <div class="milestone <?= $user['login_streak'] >= 100 ? 'achieved' : '' ?>">
                                <div class="milestone-number">100</div>
                                <div class="milestone-label">Legend</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profile Information Section -->
            <section class="profile-section">
                <h2 class="section-title">
                    <i class="fas fa-user-circle"></i>
                    Profile Information
                </h2>
                
                <div class="profile-info">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?= htmlspecialchars($user['full_name']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Date Joined</div>
                        <div class="info-value"><?= htmlspecialchars(date('F j, Y', strtotime($user['created_at']))) ?></div>
                    </div>
                </div>
            </section>

            <!-- Personal Details Section - NEW -->
            <section class="personal-details-section">
                <h2 class="section-title">
                    <i class="fas fa-id-card"></i>
                    Personal Details
                </h2>

                <?php
                // Calculate profile completion
                $fields = ['phone', 'gender', 'birth_date', 'address', 'city', 'province', 'zip_code'];
                $completed = 0;
                foreach ($fields as $field) {
                    if (!empty($user[$field])) $completed++;
                }
                $completion_percentage = round(($completed / count($fields)) * 100);
                ?>

                <div class="completion-indicator">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $completion_percentage ?>%;"></div>
                    </div>
                    <div class="completion-text">
                        <i class="fas fa-chart-line"></i> <?= $completion_percentage ?>% Complete
                    </div>
                </div>

                <form action="account.php" method="POST" id="personalDetailsForm">
                    <input type="hidden" name="action" value="update_personal_details">
                    <input type="hidden" name="accept_terms" id="acceptTerms" value="no">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Phone Number
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   placeholder="e.g., 09123456789"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="gender">
                                <i class="fas fa-venus-mars"></i> Gender
                            </label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                <option value="prefer_not_to_say" <?= ($user['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : '' ?>>Prefer not to say</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="birth_date">
                                <i class="fas fa-birthday-cake"></i> Birth Date
                            </label>
                            <input type="date" 
                                   id="birth_date" 
                                   name="birth_date"
                                   max="<?= date('Y-m-d') ?>"
                                   value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>">
                        </div>

                        <div class="form-group form-group-full">
                            <label for="address">
                                <i class="fas fa-map-marker-alt"></i> Street Address
                            </label>
                            <textarea id="address" 
                                      name="address" 
                                      placeholder="e.g., 123 Main Street, Barangay Sample"
                                      rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="city">
                                <i class="fas fa-city"></i> City
                            </label>
                            <input type="text" 
                                   id="city" 
                                   name="city" 
                                   placeholder="e.g., Manila"
                                   value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="province">
                                <i class="fas fa-map"></i> Province
                            </label>
                            <input type="text" 
                                   id="province" 
                                   name="province" 
                                   placeholder="e.g., Metro Manila"
                                   value="<?= htmlspecialchars($user['province'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="zip_code">
                                <i class="fas fa-mailbox"></i> ZIP Code
                            </label>
                            <input type="text" 
                                   id="zip_code" 
                                   name="zip_code" 
                                   placeholder="e.g., 1000"
                                   maxlength="10"
                                   value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="terms-notice" style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff;">
                        <p style="margin: 0;">
                            By saving your personal details, you agree to our 
                            <span class="terms-trigger" onclick="openTermsModal()">Terms & Conditions</span> 
                            regarding data collection and usage.
                        </p>
                    </div>

                    <button type="submit" class="save-details-btn" id="saveDetailsBtn" disabled>
                        <i class="fas fa-save"></i>
                        Save Personal Details
                    </button>
                </form>
            </section>

            <!-- Membership Section -->
            <section class="membership-section">
                <h2 class="section-title">
                    <i class="fas fa-crown"></i>
                    Membership
                </h2>
                
                <div class="current-membership">
                    <?php $current_membership_details = getMembershipDetails($user['membership_type']); ?>
                    <div class="membership-badge" style="background: <?= $current_membership_details['color'] ?>;">
                        <i class="fas fa-user-tag"></i>
                        <span><?= $current_membership_details['name'] ?></span>
                    </div>
                    <div class="membership-description" style="color: var(--text-color);">
                        You are currently a "<?= $current_membership_details['name'] ?>". You enjoy a discount of <strong><?= $current_membership_details['discount'] ?></strong> on all products.
                    </div>
                </div>

                <h3 class="section-title" style="margin-top: 2rem;">
                    <i class="fas fa-exchange-alt"></i>
                    Change My Membership
                </h3>

                <form action="account.php" method="post">
                    <input type="hidden" name="action" value="update_membership">
                    <div class="membership-options">
                        <?php foreach (['regular', 'sidc_member', 'non_member'] as $type): ?>
                            <?php $details = getMembershipDetails($type); ?>
                            <label for="<?= $type ?>" class="membership-card <?= $user['membership_type'] === $type ? 'selected' : '' ?>">
                                <input type="radio" id="<?= $type ?>" name="membership_type" value="<?= $type ?>" <?= $user['membership_type'] === $type ? 'checked' : '' ?>>
                                <div class="membership-header">
                                    <div class="membership-name"><?= $details['name'] ?></div>
                                    <div class="discount-badge" style="background: <?= $details['color'] ?>;">
                                        <?= $details['discount'] ?> OFF
                                    </div>
                                </div>
                                <div class="membership-description">
                                    <?= $details['description'] ?>
                                </div>
                                <ul class="benefits-list">
                                    <li><i class="fas fa-check-circle"></i> Exclusive Discounts</li>
                                    <li><i class="fas fa-check-circle"></i> Priority Customer Support</li>
                                    <li><i class="fas fa-check-circle"></i> Early Access to Sales</li>
                                </ul>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="update-btn" id="update-btn" disabled>Update Membership</button>
                </form>
            </section>

        </main>
    </div>

    <!-- Terms & Conditions Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-contract"></i> Terms & Conditions</h2>
                <button type="button" class="close-modal" onclick="closeTermsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="terms-content">
                    <h3>1. Data Collection and Usage</h3>
                    <p>By providing your personal information, you agree that Coopamart may collect, store, and process your data for the following purposes:</p>
                    <ul>
                        <li>To provide and personalize our services to you</li>
                        <li>To process your orders and manage your account</li>
                        <li>To communicate with you about products, services, and promotions</li>
                        <li>To improve our website and customer experience</li>
                        <li>To comply with legal obligations</li>
                    </ul>

                    <h3>2. Data Protection</h3>
                    <p>We are committed to protecting your personal information and have implemented appropriate security measures to prevent unauthorized access, disclosure, modification, or unauthorized destruction of your data.</p>

                    <h3>3. Data Sharing</h3>
                    <p>We do not sell, trade, or rent your personal identification information to others. We may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners and trusted affiliates.</p>

                    <h3>4. Your Rights</h3>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access and review your personal information</li>
                        <li>Correct inaccurate or incomplete data</li>
                        <li>Request deletion of your personal data</li>
                        <li>Object to processing of your personal data</li>
                        <li>Request transfer of your data to another organization</li>
                    </ul>

                    <h3>5. Cookies and Tracking</h3>
                    <p>Our website uses cookies to enhance user experience. You may choose to set your web browser to refuse cookies, or to alert you when cookies are being sent.</p>

                    <h3>6. Changes to Terms</h3>
                    <p>Coopamart has the discretion to update these terms and conditions at any time. We encourage Users to frequently check this page for any changes to stay informed about how we are helping to protect the personal information we collect.</p>

                    <h3>7. Contact Information</h3>
                    <p>If you have any questions about these Terms and Conditions, the practices of this site, or your dealings with this site, please contact us at:</p>
                    <p>Email: privacy@coopamart.com<br>
                    Phone: (02) 8-123-4567<br>
                    Address: 123 Cooperative Street, Manila, Philippines</p>

                    <div class="terms-checkbox">
                        <div class="checkbox-group">
                            <input type="checkbox" id="modalAcceptTerms" name="modal_accept_terms">
                            <label for="modalAcceptTerms">
                                I have read, understood, and agree to be bound by these Terms & Conditions. 
                                I consent to the collection and processing of my personal data as described above.
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTermsModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="acceptTermsBtn" onclick="acceptTerms()" disabled>Accept & Continue</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Membership update functionality
            const membershipCards = document.querySelectorAll('.membership-card');
            const updateButton = document.getElementById('update-btn');
            const initialMembership = document.querySelector('input[name="membership_type"]:checked').value;

            membershipCards.forEach(card => {
                card.addEventListener('click', () => {
                    membershipCards.forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    
                    const selectedMembership = card.querySelector('input[name="membership_type"]').value;
                    if (selectedMembership !== initialMembership) {
                        updateButton.disabled = false;
                    } else {
                        updateButton.disabled = true;
                    }
                });
            });

            // Personal details form functionality
            const personalDetailsForm = document.getElementById('personalDetailsForm');
            const saveDetailsBtn = document.getElementById('saveDetailsBtn');
            const formInputs = personalDetailsForm.querySelectorAll('input, select, textarea');
            
            // Store initial form values
            const initialFormData = new FormData(personalDetailsForm);
            
            // Enable save button when form changes
            formInputs.forEach(input => {
                input.addEventListener('input', () => {
                    checkFormChanges();
                });
                input.addEventListener('change', () => {
                    checkFormChanges();
                });
            });
            
            function checkFormChanges() {
                const currentFormData = new FormData(personalDetailsForm);
                let hasChanges = false;
                
                for (let [key, value] of currentFormData.entries()) {
                    if (key !== 'action' && initialFormData.get(key) !== value) {
                        hasChanges = true;
                        break;
                    }
                }
                
                saveDetailsBtn.disabled = !hasChanges;
            }

            // Form validation
            personalDetailsForm.addEventListener('submit', (e) => {
                const phone = document.getElementById('phone').value;
                const acceptTerms = document.getElementById('acceptTerms').value;
                
                // Check if terms are accepted
                if (acceptTerms !== 'yes') {
                    e.preventDefault();
                    openTermsModal();
                    return false;
                }
                
                // Basic phone validation for Philippines
                if (phone && !/^(09|\+639)\d{9}$/.test(phone.replace(/\s/g, ''))) {
                    e.preventDefault();
                    alert('Please enter a valid Philippine phone number (e.g., 09123456789)');
                    return false;
                }
            });

            // Terms modal checkbox functionality
            const modalAcceptCheckbox = document.getElementById('modalAcceptTerms');
            const acceptTermsBtn = document.getElementById('acceptTermsBtn');

            modalAcceptCheckbox.addEventListener('change', () => {
                acceptTermsBtn.disabled = !modalAcceptCheckbox.checked;
            });
        });

        // Terms & Conditions Modal Functions
        function openTermsModal() {
            const modal = document.getElementById('termsModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeTermsModal() {
            const modal = document.getElementById('termsModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            
            // Reset checkbox
            document.getElementById('modalAcceptTerms').checked = false;
            document.getElementById('acceptTermsBtn').disabled = true;
        }

        function acceptTerms() {
            // Set the hidden input value
            document.getElementById('acceptTerms').value = 'yes';
            
            // Close the modal
            closeTermsModal();
            
            // Submit the form
            document.getElementById('personalDetailsForm').submit();
        }

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('termsModal');
            if (e.target === modal) {
                closeTermsModal();
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