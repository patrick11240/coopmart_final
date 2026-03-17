<?php
session_start();
require_once 'include/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Function to check if user hasn't played in a while
function shouldShowWelcomeModal($pdo, $user_id) {
    // Check if user has never spun before
    $sql = "SELECT COUNT(*) as spin_count FROM spin_discounts WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $spin_count = $stmt->fetch()['spin_count'];
    
    if ($spin_count == 0) {
        return true; // First time user
    }
    
    // Check last spin date
    $sql = "SELECT MAX(created_at) as last_spin FROM spin_discounts WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $last_spin = $stmt->fetch()['last_spin'];
    
    if (!$last_spin) {
        return true;
    }
    
    $last_spin_time = strtotime($last_spin);
    $current_time = time();
    $days_since_last_spin = floor(($current_time - $last_spin_time) / (60 * 60 * 24));
    
    // Show welcome modal if it's been more than 3 days since last spin
    return $days_since_last_spin > 3;
}

// Check if we should show welcome modal
$show_welcome_modal = shouldShowWelcomeModal($pdo, $user_id);

// Spin the wheel function (modified for points)
function spinWheelPoints($pdo, $user_id) {
    // Check if user can spin
    $sql = "SELECT daily_spins, last_spin_date FROM users WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $today = date('Y-m-d');
    $remaining_spins = 0;
    
    if ($user['last_spin_date'] != $today) {
        $remaining_spins = 3; // New day, reset spins
    } else {
        $remaining_spins = max(0, 3 - $user['daily_spins']);
    }
    
    if ($remaining_spins <= 0) {
        return ['success' => false, 'message' => 'No spins left today'];
    }
    
    // Random points: 10, 20, 30, or 50
    $points_options = [10, 20, 30, 50];
    $points_won = $points_options[array_rand($points_options)];
    
    // Add points to user account
    $sql = "UPDATE users SET points = points + ? WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$points_won, $user_id]);
    
    // Save spin result for history
    $sql = "INSERT INTO spin_discounts (user_id, discount_percent) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $points_won]);
    
    // Update user spins
    $sql = "UPDATE users 
            SET daily_spins = CASE 
                WHEN last_spin_date = ? THEN daily_spins + 1 
                ELSE 1 
            END,
            last_spin_date = ?
            WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today, $today, $user_id]);
    
    // Calculate peso value
    $peso_value = ($points_won / 20) * 5;
    
    return [
        'success' => true, 
        'points' => $points_won,
        'peso_value' => number_format($peso_value, 2),
        'message' => "You won {$points_won} points! (₱{$peso_value} value)"
    ];
}

// Get user spin data
function getUserSpinDataComplete($pdo, $user_id) {
    // Get user points
    $sql = "SELECT points, daily_spins, last_spin_date FROM users WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $today = date('Y-m-d');
    $remaining_spins = 0;
    
    if ($user) {
        if ($user['last_spin_date'] != $today) {
            $remaining_spins = 3;
        } else {
            $remaining_spins = max(0, 3 - $user['daily_spins']);
        }
    }
    
    // Get total spins
    $sql = "SELECT COUNT(*) as total_spins FROM spin_discounts WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $total_spins = $stmt->fetch()['total_spins'];
    
    // Get recent wins
    $sql = "SELECT discount_percent as points, created_at 
            FROM spin_discounts 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $recent_wins = $stmt->fetchAll();
    
    return [
        'remaining_spins' => $remaining_spins,
        'total_spins' => $total_spins ?: 0,
        'total_points' => $user['points'] ?: 0,
        'recent_wins' => $recent_wins,
        'can_spin' => $remaining_spins > 0
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'spin':
                $result = spinWheelPoints($pdo, $user_id);
                echo json_encode($result);
                exit;
                
            case 'get_user_data':
                $data = getUserSpinDataComplete($pdo, $user_id);
                echo json_encode($data);
                exit;
                
            case 'dismiss_welcome':
                // Set session flag to not show welcome modal again
                $_SESSION['welcome_modal_shown'] = true;
                echo json_encode(['success' => true]);
                exit;
        }
    }
}

// Get initial user data for page load
$userData = getUserSpinDataComplete($pdo, $user_id);

// Initialize cart count before it's used in the HTML
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
    <title>Spin for Points - CoopMart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/spin.css">
  <style>
    /* Welcome Modal Styles with New Color Scheme */
    .welcome-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .welcome-modal-overlay.show {
        display: flex;
        opacity: 1;
    }

    .welcome-modal {
        background: var(--primary-gradient);
        color: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: var(--shadow-strong);
        width: 90%;
        max-width: 500px;
        position: relative;
        text-align: center;
        transform: scale(0.8);
        opacity: 0;
        transition: all 0.4s ease;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .welcome-modal-overlay.show .welcome-modal {
        transform: scale(1);
        opacity: 1;
    }

    .welcome-icon {
        font-size: 4rem;
        margin-bottom: 20px;
        animation: bounce 2s infinite;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
    }

    .welcome-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 15px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        background: var(--gold-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .welcome-subtitle {
        font-size: 1.2rem;
        margin-bottom: 25px;
        opacity: 0.9;
        line-height: 1.5;
        color: var(--bg-primary);
    }

    .welcome-features {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 20px;
        margin: 25px 0;
        text-align: left;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .welcome-feature {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        font-size: 1rem;
        color: var(--bg-primary);
    }

    .welcome-feature:last-child {
        margin-bottom: 0;
    }

    .welcome-feature i {
        margin-right: 12px;
        font-size: 1.2rem;
        background: var(--gold-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .welcome-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .welcome-btn {
        padding: 15px 30px;
        border: none;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: var(--shadow-light);
    }

    .welcome-btn-primary {
        background: var(--gold-gradient);
        color: var(--text-primary);
        box-shadow: 0 5px 20px rgba(255, 193, 7, 0.4);
    }

    .welcome-btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(255, 193, 7, 0.6);
        background: var(--warning-gradient);
    }

    .welcome-btn-secondary {
        background: rgba(255, 255, 255, 0.2);
        color: var(--bg-primary);
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .welcome-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        box-shadow: var(--shadow-light);
    }

    .welcome-close {
        position: absolute;
        top: 15px;
        right: 20px;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: var(--bg-primary);
        font-size: 1.5rem;
        cursor: pointer;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .welcome-close:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
        box-shadow: var(--shadow-light);
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }

    /* Points highlight */
    .points-highlight {
        background: var(--gold-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Tip section */
    .welcome-tip {
        margin-top: 20px;
        font-size: 0.9rem;
        opacity: 0.8;
        color: var(--bg-primary);
    }

    .welcome-tip i {
        background: var(--gold-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .welcome-modal {
            padding: 30px 20px;
            margin: 20px;
        }

        .welcome-title {
            font-size: 1.6rem;
        }

        .welcome-subtitle {
            font-size: 1rem;
        }

        .welcome-buttons {
            flex-direction: column;
        }

        .welcome-btn {
            width: 100%;
            justify-content: center;
        }

        .welcome-features {
            padding: 15px;
        }

        .welcome-feature {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        .welcome-modal {
            padding: 25px 15px;
        }

        .welcome-title {
            font-size: 1.4rem;
        }

        .welcome-icon {
            font-size: 3rem;
        }
    }

    /* Enhanced focus states for accessibility */
    .welcome-btn:focus {
        outline: 2px solid var(--gold);
        outline-offset: 2px;
    }

    .welcome-close:focus {
        outline: 2px solid var(--gold);
        outline-offset: 2px;
    }

    /* Loading state for buttons */
    .welcome-btn.loading {
        position: relative;
        color: transparent;
    }

    .welcome-btn.loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
</head>
<body>
    <!-- Welcome Back Modal -->
    <div class="welcome-modal-overlay" id="welcomeModal">
        <div class="welcome-modal">
            <button class="welcome-close" onclick="closeWelcomeModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="welcome-icon">🎮</div>
            <h1 class="welcome-title">Welcome Back, <?= htmlspecialchars($user['full_name'] ?? 'Valued Customer') ?>! 👋</h1>
            
            <p class="welcome-subtitle">
                <?php if ($userData['total_spins'] == 0): ?>
                  <span class="points-highlight">Spin the wheel</span> and earn amazing points that convert to real discounts! 🎉
                <?php else: ?>
                    It's been a while since your last spin! <span class="points-highlight">Play this game</span> and win points to get discounts on your purchases! 💫
                <?php endif; ?>
            </p>

            <div class="welcome-features">
                <div class="welcome-feature">
                    <i class="fas fa-sync-alt"></i>
                    <span><strong>3 Free Spins Daily</strong> - Reset every midnight</span>
                </div>
                <div class="welcome-feature">
                    <i class="fas fa-coins"></i>
                    <span><strong>Win 10-50 Points</strong> per spin</span>
                </div>
                <div class="welcome-feature">
                    <i class="fas fa-tag"></i>
                    <span><strong>20 Points = ₱5 Discount</strong> on your orders</span>
                </div>
                <div class="welcome-feature">
                    <i class="fas fa-gift"></i>
                    <span><strong>Points never expire</strong> - Use anytime!</span>
                </div>
            </div>

            <div class="welcome-buttons">
                <button class="welcome-btn welcome-btn-primary" onclick="startPlaying()">
                    <i class="fas fa-play-circle"></i>
                    Start Playing Now!
                </button>
                <button class="welcome-btn welcome-btn-secondary" onclick="closeWelcomeModal()">
                    <i class="fas fa-shopping-cart"></i>
                    Shop First
                </button>
            </div>

            <div style="margin-top: 20px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-lightbulb"></i>
                <strong>Pro Tip:</strong> You currently have <span class="points-highlight"><?= $userData['total_points'] ?> points</span> available!
            </div>
        </div>
    </div>

    <!-- Enhanced Header - Matching Cart.php -->
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
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></div>
                        <div class="user-greeting">Welcome back!</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Enhanced Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">🎡 Spin for Points!</h1>
            <p class="page-subtitle">
                Earn amazing points with every spin! Convert 20 points to ₱5 discount. 
                Get up to 3 free spins daily and start winning now!
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container">
        <!-- Game Section -->
        <section class="game-section">
            <!-- Wheel Container -->
            <div class="wheel-container">
                <h2 class="wheel-title">
                    <i class="fas fa-dice"></i>
                    Spin to Win
                </h2>
                
                <!-- FIXED WHEEL VISIBILITY -->
                <div class="wheel-wrapper">
                    <div class="wheel-pointer"></div>
                    <div class="wheel" id="wheel">
                        <div class="wheel-segment segment-1">
                            <div>10<br>pts</div>
                        </div>
                        <div class="wheel-segment segment-2">
                            <div>50<br>pts</div>
                        </div>
                        <div class="wheel-segment segment-3">
                            <div>20<br>pts</div>
                        </div>
                        <div class="wheel-segment segment-4">
                            <div>30<br>pts</div>
                        </div>
                    </div>
                </div>
                
                <button class="spin-button" id="spinBtn">
                    <i class="fas fa-sync-alt"></i>
                    Spin the Wheel!
                </button>
                
                <p class="spin-status">
                    You have <span class="remaining-spins" id="remainingSpins"><?= $userData['remaining_spins'] ?></span> spins left today
                </p>
            </div>

            <!-- Info Panel -->
            <div class="info-panel">
                <h2 class="info-title">
                    <i class="fas fa-chart-line"></i>
                    Your Stats
                </h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" id="totalSpins"><?= $userData['total_spins'] ?></div>
                        <div class="stat-label">Total Spins</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalPoints"><?= $userData['total_points'] ?></div>
                        <div class="stat-label">Total Points</div>
                    </div>
                </div>

                <div class="recent-wins">
                    <h3>
                        <i class="fas fa-trophy"></i>
                        Recent Wins
                    </h3>
                    <div class="wins-list" id="discountsList">
                        <?php if (empty($userData['recent_wins'])): ?>
                            <div class="empty-state">
                                <i class="fas fa-gift"></i>
                                <p>No points earned yet.<br>Spin the wheel to start winning!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_reverse($userData['recent_wins']) as $win): ?>
                                <?php $pesoValue = ($win['points'] / 20) * 5; ?>
                                <div class="win-item">
                                    <div class="win-content">
                                        <div class="points-earned">+<?= $win['points'] ?> Points</div>
                                        <div class="peso-value">Worth: ₱<?= number_format($pesoValue, 2) ?> discount</div>
                                        <div class="win-date">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('M d, Y', strtotime($win['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="win-icon">🎉</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tips-card">
                        <strong>💡 Pro Tip:</strong> Use your points during checkout!
                        <small style="display: block; margin-top: 0.5rem; color: var(--text-secondary);">
                            20 points = ₱5 discount on your next order
                        </small>
                    </div>
                </div>
            </div>
        </section>

        <!-- Rules Section -->
        <section class="rules-section">
            <h3 class="rules-title">
                <i class="fas fa-info-circle"></i>
                Game Rules
            </h3>
            <ul class="rules-list">
                <li class="rules-item">You can spin the wheel 3 times per day</li>
                <li class="rules-item">Spins reset every day at midnight</li>
                <li class="rules-item">Win points: 10, 20, 30, or 50 points per spin</li>
                <li class="rules-item">20 points equals ₱5 discount on orders</li>
                <li class="rules-item">Points are automatically added to your account</li>
                <li class="rules-item">Use points anytime during checkout process</li>
            </ul>
        </section>
    </main>

    <!-- Enhanced Result Popup -->
    <div class="popup-overlay" id="popupOverlay"></div>
    <div class="result-popup" id="resultPopup">
        <div class="result-icon" id="resultIcon">🎉</div>
        <div class="result-text" id="resultText">Congratulations!</div>
        <button class="close-popup" onclick="closePopup()">
            <i class="fas fa-check"></i>
           Continue Shopping
        </button>
    </div>

    <!-- Zero Spins Modal -->
    <div class="popup-overlay" id="zeroSpinsOverlay"></div>
    <div class="result-popup zero-spins-popup" id="zeroSpinsPopup">
        <div class="result-icon">🎯</div>
        <div class="result-text">
            <h3>Continue Shopping?</h3>
            <p>You've used all your spins for today!</p>
            <div class="points-reminder">
                <i class="fas fa-info-circle"></i>
                <div class="reminder-content">
                    <strong>You have <span id="currentPointsDisplay"><?= $userData['total_points'] ?></span> points available!</strong>
                    <p>Would you like to use your points for discounts on your next purchase?</p>
                    <small>20 points = ₱5 discount • Use during checkout</small>
                </div>
            </div>
        </div>
        <div class="popup-buttons">
            <button class="popup-button secondary" onclick="closeZeroSpinsPopup()">
                <i class="fas fa-times"></i>
                Maybe Later
            </button>
            <button class="popup-button primary" onclick="goToShop()">
                <i class="fas fa-shopping-cart"></i>
                Continue Shopping
            </button>
            <button class="popup-button points-btn" onclick="usePointsForDiscount()" id="usePointsBtn">
                <i class="fas fa-tag"></i>
                Use Points for Discount
            </button>
        </div>
    </div>

    <script>
    let isSpinning = false;
    let currentUserPoints = <?= $userData['total_points'] ?>;
    let welcomeModalShown = false;

    // DOM Elements
    const spinBtn = document.getElementById('spinBtn');
    const wheel = document.getElementById('wheel');
    const remainingSpinsEl = document.getElementById('remainingSpins');
    const totalSpinsEl = document.getElementById('totalSpins');
    const totalPointsEl = document.getElementById('totalPoints');
    const discountsListEl = document.getElementById('discountsList');
    const popupOverlay = document.getElementById('popupOverlay');
    const resultPopup = document.getElementById('resultPopup');
    const resultIcon = document.getElementById('resultIcon');
    const resultText = document.getElementById('resultText');
    const zeroSpinsOverlay = document.getElementById('zeroSpinsOverlay');
    const zeroSpinsPopup = document.getElementById('zeroSpinsPopup');
    const currentPointsDisplay = document.getElementById('currentPointsDisplay');
    const usePointsBtn = document.getElementById('usePointsBtn');
    const welcomeModal = document.getElementById('welcomeModal');

    // Show welcome modal on page load if conditions are met
    document.addEventListener('DOMContentLoaded', function() {
        const remainingSpins = parseInt(remainingSpinsEl.textContent);
        
        // Disable spin button if no spins left
        if (remainingSpins <= 0) {
            spinBtn.disabled = true;
            spinBtn.innerHTML = '<i class="fas fa-clock"></i> No Spins Left';
        }

        // Show welcome modal after a short delay if conditions are met
        setTimeout(() => {
            <?php if ($show_welcome_modal && !isset($_SESSION['welcome_modal_shown'])): ?>
                showWelcomeModal();
            <?php endif; ?>
        }, 1000);
    });

    // Welcome Modal Functions
    function showWelcomeModal() {
        welcomeModal.classList.add('show');
        welcomeModalShown = true;
        document.body.style.overflow = 'hidden';
    }

    function closeWelcomeModal() {
        welcomeModal.classList.remove('show');
        document.body.style.overflow = '';
        
        // Mark as shown in session
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=dismiss_welcome'
        });
    }

    function startPlaying() {
        closeWelcomeModal();
        // Optionally focus on spin button or scroll to wheel
        spinBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Spin button event listener
    spinBtn.addEventListener('click', function() {
        if (isSpinning) return;
        
        const remainingSpins = parseInt(remainingSpinsEl.textContent);
        if (remainingSpins <= 0) {
            showZeroSpinsPopup();
            return;
        }

        spinWheel();
    });

    // Spin wheel function
    function spinWheel() {
        if (isSpinning) return;
        
        isSpinning = true;
        spinBtn.disabled = true;
        spinBtn.style.opacity = '0.6';
        spinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Spinning...';

        // Add spinning animation
        wheel.classList.add('spinning');

        // Make AJAX request
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=spin'
        })
        .then(response => response.json())
        .then(data => {
            setTimeout(() => {
                handleSpinResult(data);
            }, 4000); // Wait for animation to complete
        })
        .catch(error => {
            console.error('Error:', error);
            setTimeout(() => {
                resetSpinButton();
                showPopup('❌', 'Something went wrong! Please try again.');
            }, 4000);
        });
    }

    // Handle spin result
    function handleSpinResult(data) {
        wheel.classList.remove('spinning');
        resetSpinButton();

        if (data.success) {
            showPopup('🎉', data.message);
            updateUserData();
        } else {
            showPopup('⏰', data.message);
        }

        isSpinning = false;
    }

    // Reset spin button
    function resetSpinButton() {
        spinBtn.disabled = false;
        spinBtn.style.opacity = '1';
        spinBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Spin the Wheel!';
    }

    // Update user data
    function updateUserData() {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_user_data'
        })
        .then(response => response.json())
        .then(data => {
            // Update current points
            currentUserPoints = data.total_points;
            
            // Animate number changes
            animateValue(remainingSpinsEl, parseInt(remainingSpinsEl.textContent), data.remaining_spins, 500);
            animateValue(totalSpinsEl, parseInt(totalSpinsEl.textContent), data.total_spins, 500);
            animateValue(totalPointsEl, parseInt(totalPointsEl.textContent), data.total_points, 1000);
            
            // Update recent wins
            updateRecentWins(data.recent_wins);
            
            // Disable button if no spins left and show popup
            if (data.remaining_spins <= 0) {
                spinBtn.disabled = true;
                spinBtn.innerHTML = '<i class="fas fa-clock"></i> No Spins Left';
                
                // Show popup after spin when spins reach zero
                setTimeout(() => {
                    showZeroSpinsPopup();
                }, 1500);
            }
        })
        .catch(error => {
            console.error('Error updating user data:', error);
        });
    }

    // Show zero spins popup
    function showZeroSpinsPopup() {
        // Update points display
        currentPointsDisplay.textContent = currentUserPoints;
        
        // Enable/disable use points button based on available points
        if (currentUserPoints >= 20) {
            usePointsBtn.disabled = false;
            usePointsBtn.style.opacity = '1';
            usePointsBtn.innerHTML = '<i class="fas fa-tag"></i> Use Points for Discount';
        } else {
            usePointsBtn.disabled = true;
            usePointsBtn.style.opacity = '0.6';
            usePointsBtn.innerHTML = '<i class="fas fa-info-circle"></i> Need 20+ Points';
        }
        
        zeroSpinsOverlay.classList.add('show');
        setTimeout(() => {
            zeroSpinsPopup.classList.add('show');
        }, 100);
    }

    // Close zero spins popup
    function closeZeroSpinsPopup() {
        zeroSpinsPopup.classList.remove('show');
        setTimeout(() => {
            zeroSpinsOverlay.classList.remove('show');
        }, 300);
    }

    // Go to shop function
    function goToShop() {
        window.location.href = 'index.php';
    }

    // Use points for discount function
    function usePointsForDiscount() {
        if (currentUserPoints < 20) {
            showPopup('⚠️', 'You need at least 20 points to get a discount!');
            return;
        }
        
        // Redirect to shop with message about points usage
        showPopup('💰', 'Great! Your points will be available during checkout. Redirecting to shop...');
        setTimeout(() => {
            window.location.href = 'index.php?points_available=true';
        }, 2000);
    }

    // Animate value changes
    function animateValue(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                element.textContent = end;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    }

    // Update recent wins display
    function updateRecentWins(recentWins) {
        if (!recentWins || recentWins.length === 0) {
            discountsListEl.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-gift"></i>
                    <p>No points earned yet.<br>Spin the wheel to start winning!</p>
                </div>
            `;
            return;
        }

        let html = '';
        recentWins.reverse().forEach(win => {
            const pesoValue = (win.points / 20) * 5;
            const date = new Date(win.created_at).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
            
            html += `
                <div class="win-item">
                    <div class="win-content">
                        <div class="points-earned">+${win.points} Points</div>
                        <div class="peso-value">Worth: ₱${pesoValue.toFixed(2)} discount</div>
                        <div class="win-date">
                            <i class="fas fa-calendar"></i>
                            ${date}
                        </div>
                    </div>
                    <div class="win-icon">🎉</div>
                </div>
            `;
        });

        discountsListEl.innerHTML = html;
    }

    // Show popup
    function showPopup(icon, message) {
        resultIcon.textContent = icon;
        resultText.textContent = message;
        
        popupOverlay.classList.add('show');
        setTimeout(() => {
            resultPopup.classList.add('show');
        }, 100);
    }

    // Close popup
    function closePopup() {
        resultPopup.classList.remove('show');
        setTimeout(() => {
            popupOverlay.classList.remove('show');
        }, 300);
    }

    // Close popup when clicking overlay
    popupOverlay.addEventListener('click', closePopup);
    zeroSpinsOverlay.addEventListener('click', closeZeroSpinsPopup);
    welcomeModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeWelcomeModal();
        }
    });

    // Mobile menu toggle function
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