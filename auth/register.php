<?php
session_start();
require_once '../include/config.php';
require_once '../vendor/autoload.php';
require_once '../include/mail_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    $errors = [];

    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $existing_user = $stmt->fetch();
        if ($existing_user['email_verified']) {
            $errors[] = "Email already registered and verified";
        } else {
            // Email exists but not verified - allow re-registration
            // We'll update the existing record
        }
    }

    if (empty($errors)) {
        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if user already exists (unverified)
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND email_verified = FALSE");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing unverified user
            $user = $stmt->fetch();
            $stmt = $pdo->prepare("UPDATE users SET 
                full_name = ?, 
                password_hash = ?, 
                verification_otp = ?, 
                otp_expiry = ?,
                otp_attempts = 0,
                otp_requested_at = NOW()
                WHERE user_id = ?");
            $stmt->execute([$full_name, $password_hash, $otp, $otp_expiry, $user['user_id']]);
            $user_id = $user['user_id'];
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users 
                (full_name, email, password_hash, email_verified, verification_otp, otp_expiry, otp_requested_at) 
                VALUES (?, ?, ?, FALSE, ?, ?, NOW())");
            $stmt->execute([$full_name, $email, $password_hash, $otp, $otp_expiry]);
            $user_id = $pdo->lastInsertId();
        }
        
        // Send OTP email
        $mailer = new Mailer();
        if ($mailer->sendOTP($email, $full_name, $otp)) {
            // Store in session for verification page
            $_SESSION['pending_email'] = $email;
            $_SESSION['pending_user_id'] = $user_id;
            $_SESSION['registration_success'] = "Registration successful! Please check your email for the 6-digit OTP.";
            
            header("Location: verify_email.php");
            exit();
        } else {
            $errors[] = "Failed to send verification email. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Coopamart</title>
    <link rel="stylesheet" href="../assets/css/auth-styles.css">
    <style>
        /* Enhanced Authentication Pages Styles */

/* Import Google Fonts for better typography */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

/* Color Theme Variables */
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
    font-family: 'Poppins', Arial, sans-serif;
    background-color: var(--background-color);
    min-height: 100vh;
    margin: 0;
    padding: 20px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow-y: auto;
    /* Background Image Styles */
    background-image: url('coopmart.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    position: relative;
}

/* Add overlay for better readability */
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

.login-container,
.register-container {
    background: var(--card-background);
    padding: 40px 40px;
    border-radius: 20px;
    box-shadow: 0 20px 60px var(--shadow-strong);
    width: 100%;
    max-width: 450px;
    text-align: center;
    margin: auto;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    animation: slideUp 0.5s ease-out;
    /* Semi-transparent background for better contrast */
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Logo/Brand Area */
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
    color: var(--text-color);
    font-size: 28px;
    font-weight: 600;
}

.subtitle {
    color: var(--secondary-color);
    font-size: 14px;
    margin-bottom: 30px;
}

/* Form Groups */
.form-group {
    margin-bottom: 20px;
    text-align: left;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-color);
    font-weight: 500;
    font-size: 14px;
}

/* Input Fields with Icons */
.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 15px;
    color: var(--secondary-color);
    font-size: 18px;
    pointer-events: none;
    transition: color 0.3s ease;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] {
    width: 100%;
    padding: 14px 45px 14px 45px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    box-sizing: border-box;
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 15px;
    transition: all 0.3s ease;
    background: var(--background-color);
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="password"]:focus {
    outline: none;
    border-color: var(--primary-color);
    background: white;
    box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
}

.form-group input[type="text"]:focus + .input-icon,
.form-group input[type="email"]:focus + .input-icon,
.form-group input[type="password"]:focus + .input-icon {
    color: var(--primary-color);
}

/* Password fields specific styling */
#password,
#confirm_password {
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 15px;
    letter-spacing: normal;
}

#password[type="text"],
#confirm_password[type="text"] {
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 15px;
    letter-spacing: normal;
}

/* Password Wrapper */
.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: var(--secondary-color);
    font-size: 20px;
    user-select: none;
    transition: all 0.3s ease;
    z-index: 10;
}

.toggle-password:hover {
    color: var(--primary-color);
    transform: translateY(-50%) scale(1.1);
}

/* Password Strength Indicator */
.password-strength {
    margin-top: 8px;
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.password-strength.show {
    opacity: 1;
}

.password-strength-bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.password-strength-bar.weak {
    width: 33%;
    background: #dc3545;
}

.password-strength-bar.medium {
    width: 66%;
    background: #ffc107;
}

.password-strength-bar.strong {
    width: 100%;
    background: var(--primary-color);
}

.password-strength-text {
    font-size: 12px;
    margin-top: 5px;
    text-align: right;
}

.password-strength-text.weak {
    color: #dc3545;
}

.password-strength-text.medium {
    color: #ffc107;
}

.password-strength-text.strong {
    color: var(--primary-color);
}

/* Error Messages */
.error {
    color: #721c24;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: left;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.error p {
    margin: 5px 0;
    font-size: 14px;
    display: flex;
    align-items: center;
}

.error p::before {
    content: '⚠️';
    margin-right: 8px;
}

/* Success Messages */
.success {
    color: #155724;
    background: var(--primary-light);
    border: 1px solid #c3e6cb;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: left;
    animation: slideDown 0.5s ease;
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

.success p {
    margin: 0;
    font-size: 14px;
    display: flex;
    align-items: center;
}

.success p::before {
    content: '✅';
    margin-right: 8px;
}

/* Submit Button */
button[type="submit"] {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
    position: relative;
    overflow: hidden;
}

button[type="submit"]::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.2);
    transition: left 0.5s ease;
}

button[type="submit"]:hover::before {
    left: 100%;
}

button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.5);
}

button[type="submit"]:active {
    transform: translateY(0);
}

button[type="submit"]:disabled {
    background: var(--secondary-color);
    cursor: not-allowed;
    box-shadow: none;
}

/* Loading State */
.button-loading {
    pointer-events: none;
    opacity: 0.7;
}

.button-loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Links */
.register-link,
.login-link {
    margin-top: 25px;
    font-size: 14px;
    color: var(--secondary-color);
}

.register-link a,
.login-link a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.register-link a:hover,
.login-link a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* Divider */
.divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 25px 0;
    color: var(--secondary-color);
    font-size: 13px;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e2e8f0;
}

.divider span {
    padding: 0 10px;
}

/* Mobile Responsive */
@media (max-width: 480px) {
    body {
        padding: 10px;
        background-attachment: scroll;
    }
    
    .login-container,
    .register-container {
        padding: 30px 25px;
        max-height: none;
        border-radius: 15px;
    }
    
    h2 {
        font-size: 24px;
    }
    
    .login-logo {
        max-width: 100px;
    }
    
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        padding: 12px 40px 12px 40px;
        font-size: 14px;
    }
    
    button[type="submit"] {
        padding: 12px;
        font-size: 15px;
    }
}

/* Tablet */
@media (min-width: 481px) and (max-width: 768px) {
    .login-container,
    .register-container {
        max-width: 400px;
    }
}

/* Custom Scrollbar */
.login-container::-webkit-scrollbar,
.register-container::-webkit-scrollbar {
    width: 8px;
}

.login-container::-webkit-scrollbar-track,
.register-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.login-container::-webkit-scrollbar-thumb,
.register-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

.login-container::-webkit-scrollbar-thumb:hover,
.register-container::-webkit-scrollbar-thumb:hover {
    background: var(--primary-color);
}
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
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

        /* Ensure the register container has proper spacing */
        .register-container {
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Logo Section -->
        <div class="logo-container">
            <img src="../logo.png" alt="Coopamart Logo" class="login-logo">
        </div>
        
        <h2 style="text-align: center; margin-bottom: 20px; color: #333;">Create Account</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['registration_success'])): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($_SESSION['registration_success']); ?></p>
                <?php unset($_SESSION['registration_success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <div class="input-with-icon">
                    <span class="input-icon">👤</span>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" 
                           required placeholder="Enter your full name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <div class="input-with-icon">
                    <span class="input-icon">📧</span>
                    <input type="email" id="email" name="email" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                           required placeholder="Enter your email">
                </div>
                <small id="emailStatus" style="font-size: 12px; margin-top: 5px; display: block;"></small>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <div class="password-wrapper">
                    <div class="input-with-icon">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your password (min. 6 characters)">
                    </div>
                    <span class="toggle-password" onclick="togglePassword('password', this)">👁️</span>
                </div>
                <div class="password-strength" id="password-strength">
                    <div class="password-strength-bar" id="password-strength-bar"></div>
                </div>
                <div class="password-strength-text" id="password-strength-text"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <div class="password-wrapper">
                    <div class="input-with-icon">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm your password">
                    </div>
                    <span class="toggle-password" onclick="togglePassword('confirm_password', this)">👁️</span>
                </div>
                <small id="passwordMatch" style="font-size: 12px; margin-top: 5px; display: block;"></small>
            </div>
            
            <div class="form-group">
                <div class="terms" style="margin: 15px 0; font-size: 14px;">
                    <input type="checkbox" id="terms" name="terms" required style="margin-right: 8px;">
                    <label for="terms">I agree to the <a href="#" style="color: #28a745;">Terms of Service</a> and <a href="#" style="color: #28a745;">Privacy Policy</a></label>
                </div>
            </div>
            
            <button type="submit" id="submitBtn">Create Account</button>
        </form>
        
        <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
    </div>
    
    <script>
        // Real-time password matching
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchElement = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchElement.textContent = '';
                matchElement.style.color = '';
            } else if (password === confirmPassword) {
                matchElement.textContent = '✓ Passwords match';
                matchElement.style.color = '#28a745';
            } else {
                matchElement.textContent = '✗ Passwords do not match';
                matchElement.style.color = '#dc3545';
            }
        });

        // Check email availability
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const statusElement = document.getElementById('emailStatus');
            
            if (!email || !email.includes('@')) {
                statusElement.textContent = '';
                return;
            }
            
            // Simple email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                statusElement.textContent = '✗ Please enter a valid email address';
                statusElement.style.color = '#dc3545';
                return;
            }
            
            statusElement.textContent = '✓ Valid email format';
            statusElement.style.color = '#28a745';
        });

        // Your existing password strength function
        document.getElementById('password').addEventListener('input', function() {
            // Your existing password strength code here
        });

        function togglePassword(fieldId, element) {
            const passwordField = document.getElementById(fieldId);
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                element.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                element.textContent = '👁️';
            }
        }
        
        // Form submission validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            const submitBtn = document.getElementById('submitBtn');
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Please agree to the Terms of Service and Privacy Policy.');
                return;
            }
            
            // Disable button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Creating Account...';
        });
    </script>
</body>
</html>