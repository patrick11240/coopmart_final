<?php
session_start();

// Store user name for goodbye message
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Coopamart</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        .logout-container {
            background: var(--card-background);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px var(--shadow-strong);
            text-align: center;
            animation: fadeInScale 0.5s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .logout-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            position: relative;
            animation: waveGoodbye 2s ease-in-out infinite;
        }

        @keyframes waveGoodbye {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(15deg); }
            75% { transform: rotate(-15deg); }
        }

        .logout-icon svg {
            width: 100%;
            height: 100%;
            fill: var(--primary-color);
        }

        h2 {
            color: var(--text-color);
            margin-bottom: 10px;
            font-size: 28px;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }

        p {
            color: var(--secondary-color);
            margin-bottom: 30px;
            font-size: 16px;
            animation: fadeIn 0.8s ease-out 0.4s both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .spinner {
            width: 50px;
            height: 50px;
            margin: 20px auto;
            border: 4px solid var(--primary-light);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .redirect-text {
            color: #999;
            font-size: 14px;
            margin-top: 20px;
            animation: fadeIn 0.8s ease-out 0.6s both;
        }

        .dots {
            display: inline-block;
            width: 20px;
        }

        .dots span {
            animation: blink 1.4s infinite;
            animation-fill-mode: both;
        }

        .dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes blink {
            0%, 80%, 100% {
                opacity: 0;
            }
            40% {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 012 2v2h-2V4H5v16h9v-2h2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h9z"/>
            </svg>
        </div>
        
        <h2>Goodbye, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <p>You have been successfully logged out.</p>
        
        <div class="spinner"></div>
        
        <p class="redirect-text">
            Redirecting to login page<span class="dots"><span>.</span><span>.</span><span>.</span></span>
        </p>
    </div>

    <script>
        // Redirect to login page after 3 seconds
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
    </script>
</body>
</html>