<?php
session_start();
require_once '../include/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = "Email and password are required";
    }

    if (empty($errors)) {
        // Check user
        $stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(DAY, last_login_date, CURDATE()) as days_since_login FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if user is inactive for more than 3 days
            $days_since_login = $user['days_since_login'] ?? 999;
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            if ($days_since_login > 3) {
                // User inactive for more than 3 days - redirect to spin_the_win.php
                header("Location: ../spin_the_win.php");
                exit();
            } else {
                // Active user - proceed to index.php
                // Update last login date
                $update_stmt = $pdo->prepare("UPDATE users SET last_login_date = CURDATE() WHERE user_id = ?");
                $update_stmt->execute([$user['user_id']]);
                
                header("Location: ../index.php");
                exit();
            }
        } else {
            $errors[] = "Invalid email or password";
        }
    }
}

// Check for success message from registration
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success_message = '🎉 Registration successful! Please login to continue.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Coopmart - Your Trusted E-Commerce Platform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            --accent-color: #ff6b35;
            --text-dark: #343a40;
            --text-light: #6c757d;
            --bg-light: #f8f9fa;
            --white: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-img {
            height: 50px;
            width: auto;
            background: white;
            padding: 5px;
            border-radius: 8px;
        }

        .brand-name {
            color: white;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: white;
            color: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--bg-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary-color);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.95) 0%, rgba(33, 136, 56, 0.95) 100%),
                        url('https://images.unsplash.com/photo-1556742044-3c52d6e88c62?w=1600') center/cover;
            padding: 120px 20px 80px;
            color: white;
            text-align: center;
            margin-top: 70px;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .hero-badges {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .badge {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* About Section */
        .about-section {
            padding: 80px 20px;
            background: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: var(--text-dark);
            margin-bottom: 50px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .about-card {
            background: var(--bg-light);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .about-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: var(--primary-light);
        }

        .about-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .about-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .about-card p {
            color: var(--text-light);
            line-height: 1.8;
        }

        /* SIDC Partnership Section */
        .sidc-section {
            background: linear-gradient(135deg, var(--primary-light) 0%, white 100%);
            padding: 80px 20px;
        }

        .sidc-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .sidc-text h2 {
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 20px;
        }

        .sidc-text p {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .sidc-logo-container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .partnership-badge {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            border-radius: 30px;
            display: inline-block;
            font-weight: bold;
            margin-top: 20px;
        }

        /* Products Preview Section */
        .products-section {
            padding: 80px 20px;
            background: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-image i {
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.3;
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            color: var(--primary-color);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .product-price {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: bold;
        }

        /* Features Section */
        .features-section {
            padding: 80px 20px;
            background: var(--bg-light);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: var(--primary-color);
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            padding: 80px 20px;
            text-align: center;
            color: white;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: white;
            padding: 40px 20px 20px;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .social-links a {
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Login Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-overlay.closing {
            opacity: 0;
        }

        .login-modal {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 450px;
            position: relative;
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal-overlay.active .login-modal {
            transform: scale(1);
            opacity: 1;
        }

        .modal-overlay.closing .login-modal {
            transform: scale(0.8);
            opacity: 0;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: var(--primary-color);
            background: var(--bg-light);
            transform: rotate(90deg);
        }

        .modal-logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .modal-logo {
            height: 60px;
            width: auto;
            background: var(--primary-light);
            padding: 10px;
            border-radius: 10px;
        }

        .modal-title {
            text-align: center;
            color: var(--text-dark);
            font-size: 1.8rem;
            margin-bottom: 30px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-light);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            background: none;
            border: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            padding: 5px;
            border-radius: 5px;
        }

        .toggle-password:hover {
            background: var(--bg-light);
            transform: translateY(-50%) scale(1.1);
        }

        .modal-submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .modal-submit-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .modal-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .modal-submit-btn.loading {
            position: relative;
            color: transparent;
        }

        .modal-submit-btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message, .success-message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            display: none;
            animation: slideDown 0.3s ease;
        }

        .error-message.show, .success-message.show {
            display: block;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .register-link-modal {
            text-align: center;
            margin-top: 20px;
            color: var(--text-light);
        }

        .register-link-modal a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link-modal a:hover {
            text-decoration: underline;
        }

        /* Message display styles */
        .message-container {
            max-width: 450px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced shake animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .sidc-content {
                grid-template-columns: 1fr;
            }

            .nav-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .brand-name {
                font-size: 1.3rem;
            }

            .logo-img {
                height: 40px;
            }

            .login-modal {
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 100px 15px 60px;
            }

            .hero h1 {
                font-size: 1.5rem;
            }

            .about-section, .products-section, .features-section {
                padding: 50px 15px;
            }

            .section-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo-section">
                <img src="../logo.png" alt="Coopmart Logo" class="logo-img">
                <span class="brand-name">Coopmart</span>
            </div>
            <div class="nav-buttons">
                <button onclick="openLoginModal()" class="btn btn-outline">Login</button>
                <a href="register.php" class="btn btn-primary">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Display PHP Messages -->
    <?php if (!empty($errors)): ?>
        <div class="message-container">
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div>❌ <?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="message-container">
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to Coopmart</h1>
            <p>Your trusted e-commerce platform for quality products at affordable prices. Powered by SIDC Tioang, bringing the cooperative spirit to online shopping.</p>
            <a href="register.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 40px;">
                <i class="fas fa-shopping-cart"></i> Start Shopping Now
            </a>
            <div class="hero-badges">
                <div class="badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure Shopping</span>
                </div>
                <div class="badge">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Pick Up & Pay</span>
                </div>
                <div class="badge">
                    <i class="fas fa-star"></i>
                    <span>Quality Products</span>
                </div>
            </div>
        </div>
    </section>

    <!-- About Coopmart -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title">What is Coopmart?</h2>
            <div class="about-grid">
                <div class="about-card">
                    <div class="about-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3>E-Commerce Platform</h3>
                    <p>Coopmart is a modern online shopping platform that brings the convenience of e-commerce to the Tioang community and beyond. Shop from anywhere, anytime.</p>
                </div>
                <div class="about-card">
                    <div class="about-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community-Driven</h3>
                    <p>Built on cooperative principles, we prioritize our members' needs and reinvest in the community. Your success is our success.</p>
                </div>
                <div class="about-card">
                    <div class="about-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <h3>Trusted & Reliable</h3>
                    <p>Backed by years of cooperative tradition, we ensure every transaction is secure, every product is quality-checked, and every customer is valued.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SIDC Partnership -->
<section class="sidc-section">
    <div class="container">
        <div class="sidc-content">
            <div class="sidc-text">
                <h2>Powered by SIDC</h2>
                <p><strong>Sorosoro Ibaba Development Cooperative (SIDC)</strong> is one of the most successful cooperatives in the Philippines, serving communities through financial services, agriculture, retail operations, and livelihood initiatives.</p>
                <p>CoopMart serves as the digital extension of SIDC’s commitment to empowering its members and local communities. Through this online platform, we provide:</p>
<ul style="list-style: none; padding-left: 0; margin-left: 0;">
    <li><i class="fas fa-check" style="color: var(--primary-color);"></i> Access to quality products at cooperative-friendly prices</li>
    <li><i class="fas fa-check" style="color: var(--primary-color);"></i> Convenient online shopping for members and customers</li>
    <li><i class="fas fa-check" style="color: var(--primary-color);"></i> Support for local producers, farmers, and suppliers</li>
    <li><i class="fas fa-check" style="color: var(--primary-color);"></i> A platform that helps reinvest cooperative earnings back into the community</li>
</ul>

                <div class="partnership-badge">
                    <i class="fas fa-handshake"></i> Official SIDC Online Platform
                </div>
            </div>

            <div class="sidc-logo-container">
                <img src="coopmart.png" alt="SIDC Cooperative Building"
                     style="width: 100%; height: 200px; object-fit: cover; border-radius: 15px; margin-bottom: 20px;">
                
                <h3 style="color: var(--primary-dark); margin-bottom: 5px;">SIDC Tiaong</h3>
                <p style="color: var(--text-light);">Sorosoro Ibaba Development Cooperative Branch</p>
                <p style="color: var(--text-light); font-style: italic; margin-top: 20px;">
                    "Building Communities, Empowering Lives"
                </p>
            </div>
        </div>
    </div>
</section>


    <!-- Product Preview -->
    <section class="products-section">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <p style="text-align: center; color: var(--text-light); margin-bottom: 50px; font-size: 1.1rem;">
                Discover our wide range of products from groceries to household essentials
            </p>
            <div class="products-grid">
                <div class="product-card">
                    <div class="product-image">
                        <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=400&h=300&fit=crop" alt="Fresh Groceries" loading="lazy">
                    </div>
                    <div class="product-info">
                        <div class="product-category">Groceries</div>
                        <div class="product-name">Fresh Produce</div>
                        <div class="product-price">₱99.00</div>
                    </div>
                </div>
                <div class="product-card">
                    <div class="product-image">
                        <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&h=300&fit=crop" alt="Fresh Bread" loading="lazy">
                    </div>
                    <div class="product-info">
                        <div class="product-category">Bakery</div>
                        <div class="product-name">Fresh Bread</div>
                        <div class="product-price">₱45.00</div>
                    </div>
                </div>
                <div class="product-card">
                    <div class="product-image">
                        <img src="https://images.unsplash.com/photo-1563453392212-326f5e854473?w=400&h=300&fit=crop" alt="Household Items" loading="lazy">
                    </div>
                    <div class="product-info">
                        <div class="product-category">Household</div>
                        <div class="product-name">Cleaning Supplies</div>
                        <div class="product-price">₱150.00</div>
                    </div>
                </div>
                <div class="product-card">
                    <div class="product-image">
                        <img src="https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=400&h=300&fit=crop" alt="Fresh Meat" loading="lazy">
                    </div>
                    <div class="product-info">
                        <div class="product-category">Meat & Fish</div>
                        <div class="product-name">Fresh Meat</div>
                        <div class="product-price">₱280.00</div>
                    </div>
                </div>
            </div>
            <div style="text-align: center;">
                <a href="register.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">
                    View All Products <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose Coopmart?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Secure Transactions</h3>
                    <p>Your payment information is protected with industry-standard security measures</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-store-alt"></i>
                    </div>
                    <h3>Easy Pick Up</h3>
                    <p>Order online and pick up at our SIDC Tioang location at your convenience</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Best Prices</h3>
                    <p>Enjoy cooperative prices and exclusive discounts for SIDC members</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Our customer service team is always ready to assist you</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Start Shopping?</h2>
            <p>Join thousands of satisfied customers who trust Coopmart for their daily needs</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary" style="background: white; color: var(--primary-color); padding: 15px 40px; font-size: 1.1rem;">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
                <button onclick="openLoginModal()" class="btn btn-outline" style="padding: 15px 40px; font-size: 1.1rem;">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="Email"><i class="fas fa-envelope"></i></a>
            </div>
            <p>&copy; 2024 Coopmart - Powered by SIDC Tioang. All rights reserved.</p>
            <p style="font-size: 0.9rem; opacity: 0.8; margin-top: 10px;">
                Your trusted e-commerce platform | Building communities together
            </p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal-overlay" id="loginModal">
        <div class="login-modal">
            <button class="modal-close" onclick="closeLoginModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-logo-container">
                <img src="../logo.png" alt="Coopmart Logo" class="modal-logo">
            </div>
            
            <h2 class="modal-title">Login to Coopmart</h2>
            
            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>

            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="modal-email">Email:</label>
                    <input type="email" id="modal-email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="modal-password">Password:</label>
                    <div class="password-wrapper">
                        <input type="password" id="modal-password" name="password" required>
                        <span class="toggle-password" onclick="toggleModalPassword()">👁️</span>
                    </div>
                </div>
                
                <button type="submit" class="modal-submit-btn" id="loginBtn">Login</button>
            </form>
            
            <p class="register-link-modal">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.8s ease forwards';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.about-card, .product-card, .feature-card').forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });

        // Login Modal Functions
        function openLoginModal() {
            const modal = document.getElementById('loginModal');
            modal.classList.remove('closing');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Focus on email input with slight delay for animation
            setTimeout(() => {
                document.getElementById('modal-email').focus();
            }, 300);
        }

        function closeLoginModal() {
            const modal = document.getElementById('loginModal');
            modal.classList.add('closing');
            
            setTimeout(() => {
                modal.classList.remove('active', 'closing');
                document.body.style.overflow = '';
                
                // Clear form and reset state
                document.getElementById('loginForm').reset();
                document.getElementById('errorMessage').classList.remove('show');
                document.getElementById('successMessage').classList.remove('show');
            }, 300);
        }

        // Close modal when clicking outside
        document.getElementById('loginModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLoginModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('loginModal');
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeLoginModal();
            }
        });

        // Prevent modal close when clicking inside
        document.querySelector('.login-modal').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Toggle password visibility
        function toggleModalPassword() {
            const passwordInput = document.getElementById('modal-password');
            const toggleIcon = event.currentTarget;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Auto-open modal if there are errors from PHP form submission
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openLoginModal();
            });
        <?php endif; ?>

        // Auto-open modal if redirected from successful registration
        <?php if (!empty($success_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openLoginModal();
                document.getElementById('successMessage').textContent = '<?php echo $success_message; ?>';
                document.getElementById('successMessage').classList.add('show');
            });
        <?php endif; ?>

        // Add Enter key support for form fields
        document.getElementById('modal-email').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('modal-password').focus();
            }
        });

        document.getElementById('modal-password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>