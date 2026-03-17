<?php
session_start();
require_once '../include/config.php';

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
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Email already registered";
    }

    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'admin';

        // Insert admin user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $password_hash, $role]);

        $_SESSION['success'] = "Admin registration successful! Please login.";
        header("Location: admin_login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 8px 25px rgba(34, 139, 34, 0.3);
            }
            50% {
                box-shadow: 0 8px 35px rgba(34, 139, 34, 0.5);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('Sidc.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
            padding: 20px 0;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(34, 139, 34, 0.85) 0%, rgba(0, 100, 0, 0.75) 100%);
            z-index: 1;
        }

        .register-container {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 35px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(34, 139, 34, 0.3);
            animation: fadeIn 0.6s ease-out;
        }

        h2 {
            margin-bottom: 30px;
            color: #228B22;
            font-size: 28px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: slideIn 0.8s ease-out;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #228B22, #32CD32);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            animation: slideIn 1s ease-out;
        }

        .form-group:nth-child(even) {
            animation: slideInRight 1s ease-out;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d5016;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #90EE90;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: #228B22;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(34, 139, 34, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:focus + label,
        .form-group input:valid + label {
            color: #228B22;
        }

        .error {
            color: #fff;
            background: linear-gradient(135deg, #c9302c 0%, #d9534f 100%);
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 4px 15px rgba(217, 83, 79, 0.3);
        }

        .error p {
            margin: 5px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .error p::before {
            content: '⚠';
            margin-right: 8px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #228B22 0%, #32CD32 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
            animation: pulse 2s infinite;
            position: relative;
            overflow: hidden;
        }

        button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        button:hover::before {
            width: 300px;
            height: 300px;
        }

        button:hover {
            background: linear-gradient(135deg, #1e7a1e 0%, #2eb82e 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(34, 139, 34, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .login-link {
            margin-top: 25px;
            animation: fadeIn 1.2s ease-out;
        }

        .login-link a {
            color: #228B22;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #228B22;
            transition: width 0.3s ease;
        }

        .login-link a:hover::after {
            width: 100%;
        }

        .login-link a:hover {
            color: #1e7a1e;
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .password-strength.show {
            opacity: 1;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { width: 33%; background: #d9534f; }
        .strength-medium { width: 66%; background: #f0ad4e; }
        .strength-strong { width: 100%; background: #32CD32; }

        /* Responsive */
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 25px;
                margin: 20px;
            }

            h2 {
                font-size: 24px;
            }
        }

        /* Icon animation */
        .icon-check {
            display: inline-block;
            animation: float 2s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2><span class="icon-check">🛡️</span> Admin Register</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <div class="password-strength" id="passwordStrength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">Register Admin</button>
        </form>
        
        <p class="login-link">Already have an admin account? <a href="admin_login.php">Login here</a></p>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.getElementById('passwordStrength');
        const strengthBar = document.getElementById('strengthBar');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            if (password.length === 0) {
                strengthIndicator.classList.remove('show');
                strengthBar.className = 'password-strength-bar';
                return;
            }
            
            strengthIndicator.classList.add('show');
            strengthBar.className = 'password-strength-bar';
            
            if (strength < 40) {
                strengthBar.classList.add('strength-weak');
            } else if (strength < 70) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength += 20;
            if (password.length >= 10) strength += 20;
            if (/[a-z]/.test(password)) strength += 15;
            if (/[A-Z]/.test(password)) strength += 15;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
            
            return strength;
        }

        // Form animation on submit
        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function() {
            const button = this.querySelector('button');
            button.style.transform = 'scale(0.95)';
            setTimeout(() => {
                button.style.transform = 'scale(1)';
            }, 200);
        });
    </script>
</body>
</html>