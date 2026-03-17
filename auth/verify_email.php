<?php
session_start();
require_once '../include/config.php';
require_once '../vendor/autoload.php';
require_once '../include/mail_config.php';

// Debug: Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if no pending email
if (!isset($_SESSION['pending_email'])) {
    error_log("Coopmart: No pending_email in session");
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];
$message = '';
$message_type = '';

error_log("Coopmart: Verifying email: $email");

// Check if user exists
$stmt = $pdo->prepare("SELECT user_id, full_name, email_verified, verification_otp, otp_expiry, otp_attempts, otp_requested_at 
                       FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    error_log("Coopmart: User not found for email: $email");
    unset($_SESSION['pending_email']);
    header("Location: register.php?error=user_not_found");
    exit();
}

// If already verified, redirect to login
if ($user['email_verified']) {
    error_log("Coopmart: Email already verified for: $email");
    unset($_SESSION['pending_email']);
    $_SESSION['verified_email'] = $email;
    header("Location: login.php?verified=already");
    exit();
}

// Calculate remaining time for OTP
$time_elapsed = 0;
if ($user['otp_requested_at']) {
    $time_elapsed = time() - strtotime($user['otp_requested_at']);
    error_log("Coopmart: OTP requested " . $time_elapsed . " seconds ago");
}

$remaining_time = max(0, 600 - $time_elapsed); // 10 minutes

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Coopmart: POST request received");
    
    // Handle OTP verification
    if (isset($_POST['verify_otp'])) {
        error_log("Coopmart: Verify OTP button clicked");
        
        // Get OTP from form
        $otp = '';
        if (isset($_POST['otp'])) {
            if (is_array($_POST['otp'])) {
                $otp = implode('', $_POST['otp']);
            } else {
                $otp = trim($_POST['otp']);
            }
        }
        
        error_log("Coopmart: Submitted OTP: '$otp'");
        
        // Validate OTP format
        if (empty($otp)) {
            $message = "Please enter the OTP code.";
            $message_type = 'error';
            error_log("Coopmart: OTP is empty");
        } elseif (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $message = "Please enter a valid 6-digit OTP code.";
            $message_type = 'error';
            error_log("Coopmart: Invalid OTP format: '$otp'");
        } else {
            // Get fresh user data
            $stmt = $pdo->prepare("SELECT user_id, verification_otp, otp_expiry, otp_attempts, email_verified, full_name 
                                   FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $current_user = $stmt->fetch();
            
            if (!$current_user) {
                $message = "User not found. Please register again.";
                $message_type = 'error';
                error_log("Coopmart: User disappeared from database");
            } else {
                error_log("Coopmart: DB OTP: '" . $current_user['verification_otp'] . "', Attempts: " . $current_user['otp_attempts']);
                error_log("Coopmart: OTP Expiry: " . $current_user['otp_expiry']);
                
                // Check OTP attempts
                if ($current_user['otp_attempts'] >= 5) {
                    $message = "Too many failed attempts. Please request a new OTP.";
                    $message_type = 'error';
                    error_log("Coopmart: OTP attempts exceeded");
                }
                // Check if OTP expired
                elseif (!$current_user['otp_expiry'] || strtotime($current_user['otp_expiry']) < time()) {
                    $message = "OTP has expired. Please request a new one.";
                    $message_type = 'error';
                    error_log("Coopmart: OTP expired at " . $current_user['otp_expiry']);
                }
                // Check if OTP matches (EXACT MATCH)
                elseif ($current_user['verification_otp'] === $otp) {
                    error_log("Coopmart: OTP MATCHED! Verifying user...");
                    
                    // Verify the user account - MATCHING YOUR TABLE STRUCTURE
                    $update_stmt = $pdo->prepare("UPDATE users SET 
                        email_verified = 1, 
                        verification_otp = NULL, 
                        otp_expiry = NULL,
                        otp_attempts = 0,
                        updated_at = NOW()
                        WHERE email = ? AND user_id = ?");
                    
                    if ($update_stmt->execute([$email, $current_user['user_id']])) {
                        error_log("Coopmart: User verified successfully!");
                        
                        // Clear session
                        unset($_SESSION['pending_email']);
                        
                        // Set success message
                        $_SESSION['verification_success'] = "✅ Email verified successfully! You can now login.";
                        $_SESSION['verified_user_name'] = $current_user['full_name'];
                        
                        // Redirect to login
                        header("Location: login.php?verified=success");
                        exit();
                    } else {
                        $message = "Database error during verification. Please try again.";
                        $message_type = 'error';
                        error_log("Coopmart: Database update failed");
                    }
                } else {
                    error_log("Coopmart: OTP MISMATCH. Expected: '" . $current_user['verification_otp'] . "', Got: '$otp'");
                    
                    // Increment failed attempts
                    $update_stmt = $pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE email = ?");
                    $update_stmt->execute([$email]);
                    
                    $remaining_attempts = 5 - ($current_user['otp_attempts'] + 1);
                    $message = "Invalid OTP. You have $remaining_attempts attempt(s) remaining.";
                    $message_type = 'error';
                }
            }
        }
    }
    
    // Handle OTP resend
    if (isset($_POST['resend_otp'])) {
        error_log("Coopmart: Resend OTP requested");
        
        // Check if user can resend
        $stmt = $pdo->prepare("SELECT full_name, otp_requested_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            $can_resend = true;
            
            // Check cooldown (60 seconds)
            if ($user_data['otp_requested_at']) {
                $last_request = strtotime($user_data['otp_requested_at']);
                $time_since_request = time() - $last_request;
                
                if ($time_since_request < 60) {
                    $can_resend = false;
                    $wait_time = 60 - $time_since_request;
                    $message = "Please wait $wait_time seconds before requesting a new OTP.";
                    $message_type = 'error';
                }
            }
            
            if ($can_resend) {
                // Generate new OTP
                $new_otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                error_log("Coopmart: New OTP generated: $new_otp");
                
                // Update database with new OTP
                $update_stmt = $pdo->prepare("UPDATE users SET 
                    verification_otp = ?, 
                    otp_expiry = ?,
                    otp_attempts = 0,
                    otp_requested_at = NOW()
                    WHERE email = ?");
                
                if ($update_stmt->execute([$new_otp, $otp_expiry, $email])) {
                    // Send email
                    $mailer = new Mailer();
                    if ($mailer->sendOTP($email, $user_data['full_name'], $new_otp)) {
                        $_SESSION['otp_message'] = "✅ New OTP has been sent to your email!";
                        $_SESSION['otp_message_type'] = 'success';
                        error_log("Coopmart: New OTP email sent successfully");
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        $message = "❌ Failed to send OTP email.";
                        $message_type = 'error';
                        error_log("Coopmart: Failed to send OTP email");
                    }
                } else {
                    $message = "❌ Database error. Please try again.";
                    $message_type = 'error';
                }
            }
        } else {
            $message = "User not found.";
            $message_type = 'error';
        }
    }
}

// Display session messages
if (isset($_SESSION['otp_message'])) {
    $message = $_SESSION['otp_message'];
    $message_type = $_SESSION['otp_message_type'];
    unset($_SESSION['otp_message']);
    unset($_SESSION['otp_message_type']);
}

// Get updated timer info
$stmt = $pdo->prepare("SELECT otp_requested_at FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
$remaining_time = 600;
if ($user && $user['otp_requested_at']) {
    $time_elapsed = time() - strtotime($user['otp_requested_at']);
    $remaining_time = max(0, 600 - $time_elapsed);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Coopmart</title>
    <link rel="stylesheet" href="../assets/css/auth_styles.css">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            background-image: url('coopmart.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            z-index: -1;
        }
        
        .verify-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 450px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 25px;
            padding: 10px;
        }

        .login-logo {
            max-width: 120px;
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .login-logo:hover {
            transform: scale(1.05);
        }
        
        h2 {
            margin-bottom: 10px;
            color: #343a40;
            font-size: 28px;
            font-weight: 600;
        }
        
        .subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .email-display {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
            font-weight: 500;
            color: #155724;
            border: 1px solid #c3e6cb;
            font-size: 15px;
        }
        
        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 30px 0;
        }
        
        .otp-input {
            width: 55px;
            height: 65px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .otp-input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
            outline: none;
            background: white;
            transform: translateY(-2px);
        }
        
        .timer {
            font-size: 14px;
            color: #6c757d;
            margin: 15px 0;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        
        .timer.expired {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .verify-btn, .resend-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .verify-btn {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }
        
        .verify-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.5);
        }
        
        .verify-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .resend-btn {
            background: #f8f9fa;
            border: 2px solid #28a745;
            color: #28a745;
        }
        
        .resend-btn:hover:not(.disabled) {
            background: #28a745;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .resend-btn.disabled {
            background: #e9ecef;
            border-color: #adb5bd;
            color: #adb5bd;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
            animation: slideDown 0.5s ease;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
            animation: shake 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-link {
            margin-top: 25px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .login-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 480px) {
            .verify-container {
                padding: 30px 25px;
                border-radius: 15px;
            }
            
            .otp-input {
                width: 45px;
                height: 55px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="logo-container">
            <img src="../logo.png" alt="Coopmart Logo" class="login-logo">
        </div>
        
        <h2>Verify Your Email</h2>
        <p class="subtitle">We've sent a 6-digit OTP to:</p>
        
        <div class="email-display">
            📧 <?php echo htmlspecialchars($email); ?>
        </div>
        
        <?php if ($message): ?>
            <div class="<?php echo $message_type; ?>" id="messageBox">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="otpForm">
            <div class="form-group">
                <label style="display: block; margin-bottom: 10px; font-weight: 500; color: #343a40;">Enter the 6-digit code:</label>
                <div class="otp-inputs">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" 
                               name="otp[]" 
                               class="otp-input" 
                               maxlength="1" 
                               pattern="[0-9]"
                               inputmode="numeric"
                               autocomplete="off"
                               <?php echo $i === 0 ? 'autofocus' : ''; ?>
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="timer" id="timer">
                ⏰ OTP expires in: <span id="time">10:00</span>
            </div>
            
            <button type="submit" name="verify_otp" class="verify-btn" id="verifyBtn">
                Verify Email
            </button>
            
            <button type="submit" name="resend_otp" class="resend-btn" id="resendBtn">
                <span id="resendText">Resend OTP</span>
            </button>
        </form>
        
        <p class="login-link">
            Didn't receive the email? Check your spam folder or 
            <a href="register.php">register with a different email</a>
        </p>
    </div>
    
    <script>
        const otpInputs = document.querySelectorAll('.otp-input');
        const verifyBtn = document.getElementById('verifyBtn');
        const resendBtn = document.getElementById('resendBtn');
        const timerElement = document.getElementById('time');
        const timerContainer = document.getElementById('timer');
        
        // Auto-navigate between OTP inputs
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                
                checkOTPComplete();
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
        
        // Check if all OTP fields are filled
        function checkOTPComplete() {
            const allFilled = Array.from(otpInputs).every(input => input.value.length === 1);
            verifyBtn.disabled = !allFilled;
        }
        
        // Timer functionality
        let timeLeft = <?php echo $remaining_time; ?>;
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerContainer.classList.add('expired');
                timerElement.textContent = '00:00 (Expired)';
                resendBtn.disabled = false;
                resendBtn.classList.remove('disabled');
            } else {
                timeLeft--;
            }
        }
        
        // Start timer
        let timerInterval = setInterval(updateTimer, 1000);
        updateTimer();
        
        // Initialize
        checkOTPComplete();
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messageBox = document.getElementById('messageBox');
            if (messageBox) {
                messageBox.style.transition = 'opacity 0.5s';
                messageBox.style.opacity = '0';
                setTimeout(() => {
                    if (messageBox.parentNode) {
                        messageBox.parentNode.removeChild(messageBox);
                    }
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>