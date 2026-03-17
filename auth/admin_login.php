<?php
session_start();
require_once '../include/config.php';

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    // Corrected path to the admin dashboard
    header("Location: ../admin/admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $errors = [];

    if (empty($email) || empty($password)) {
        $errors[] = "Email and password are required";
    }

    if (empty($errors)) {
        // Check user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Corrected path to the admin dashboard
            header("Location: ../admin/admin_dashboard.php");
            exit();
        } else {
            $errors[] = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
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

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 8px 25px rgba(34, 139, 34, 0.3);
            }
            50% {
                box-shadow: 0 8px 35px rgba(34, 139, 34, 0.5);
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

        .login-container {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 35px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
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
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            animation: slideIn 1s ease-out;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d5016;
            font-weight: 500;
            font-size: 14px;
        }

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

        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #228B22;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(34, 139, 34, 0.1);
            transform: translateY(-2px);
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
        }

        .success {
            color: #fff;
            background: linear-gradient(135deg, #228B22 0%, #32CD32 100%);
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 4px 15px rgba(34, 139, 34, 0.3);
        }

        .success p {
            margin: 5px 0;
            font-size: 14px;
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
        }

        button:hover {
            background: linear-gradient(135deg, #1e7a1e 0%, #2eb82e 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(34, 139, 34, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .register-link {
            margin-top: 25px;
            animation: fadeIn 1.2s ease-out;
        }

        .register-link a {
            color: #228B22;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: #1e7a1e;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
                margin: 20px;
            }

            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <p class="register-link">Don't have an account? <a href="admin_register.php">Register here</a></p>
    </div>
</body>
</html>