<?php
session_start();
require_once '../include/config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Analytics & Insights</title>
    <style>
        :root {
            --dark-green: #1a4d2e;
            --medium-green: #3a754f;
            --light-green: #4caf50;
            --accent-green: #66bb6a;
            --light-bg: #f7f9fc;
            --white: #ffffff;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #e0e0e0;
            --shadow: rgba(0, 0, 0, 0.1);
            --success-bg: #e8f5e9;
            --warning-bg: #fff9e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: linear-gradient(135deg, #f7f9fc 0%, #e8f5e9 100%);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--dark-green) 0%, var(--medium-green) 100%);
            color: var(--white);
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px var(--shadow);
            transition: all 0.3s ease;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            color: var(--accent-green);
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .sidebar a {
            color: var(--white);
            text-decoration: none;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: all 0.3s;
            display: block;
            font-weight: 500;
        }
        
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            padding-left: 25px;
            transform: translateX(5px);
        }

        .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 4px solid var(--accent-green);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .main-content {
            flex-grow: 1;
            padding: 40px;
            transition: all 0.3s ease;
        }

        .analytics-container {
            background: var(--white);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: var(--dark-green);
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-header .subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            font-weight: 400;
            letter-spacing: 0.5px;
            padding: 10px 20px;
            background: linear-gradient(90deg, transparent, var(--success-bg), transparent);
            border-radius: 20px;
        }

        .generate-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--light-green) 0%, var(--accent-green) 100%);
            border: none;
            border-radius: 12px;
            color: var(--white);
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 35px;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
            position: relative;
            overflow: hidden;
        }

        .generate-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .generate-btn:hover::before {
            left: 100%;
        }

        .generate-btn:hover {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--light-green) 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }

        .generate-btn:disabled {
            background: linear-gradient(135deg, var(--text-light) 0%, #999 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .loading {
            text-align: center;
            padding: 60px 40px;
            display: none;
            background: linear-gradient(135deg, var(--success-bg) 0%, var(--white) 100%);
            border-radius: 15px;
            border: 2px dashed var(--light-green);
        }

        .loading.active {
            display: block;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .spinner {
            border: 5px solid var(--border-color);
            border-top: 5px solid var(--light-green);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 25px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: var(--medium-green);
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.05);
        }

        .insights-content {
            display: none;
            line-height: 1.9;
            color: var(--text-dark);
        }

        .insights-content.active {
            display: block;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .insights-content h2 {
            color: var(--dark-green);
            margin-top: 35px;
            margin-bottom: 20px;
            font-size: 1.7rem;
            font-weight: bold;
            padding: 18px 25px;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(102, 187, 106, 0.05) 100%);
            border-left: 6px solid var(--light-green);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .insights-content h2::before {
            content: '📊';
            margin-right: 12px;
            font-size: 1.5rem;
        }

        .insights-content h2::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(76, 175, 80, 0.1));
        }

        .insights-content h3 {
            color: var(--medium-green);
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 1.3rem;
            font-weight: 600;
            padding-left: 20px;
            border-left: 4px solid var(--accent-green);
            position: relative;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.03);
        }

        .insights-content h3::before {
            content: '▸';
            position: absolute;
            left: 0;
            color: var(--accent-green);
            font-size: 1.5rem;
        }

        .insights-content ul {
            margin: 20px 0 25px 20px;
            background: linear-gradient(135deg, var(--light-bg) 0%, var(--white) 100%);
            padding: 25px 30px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .insights-content li {
            margin-bottom: 15px;
            padding: 12px 15px 12px 35px;
            position: relative;
            color: var(--text-dark);
            background: var(--white);
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
        }

        .insights-content li:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.15);
            background: linear-gradient(90deg, var(--white) 0%, rgba(76, 175, 80, 0.05) 100%);
        }

        .insights-content li::before {
            content: "✓";
            color: var(--light-green);
            font-weight: bold;
            font-size: 1.3rem;
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .insights-content p {
            margin-bottom: 18px;
            padding: 15px 20px;
            color: var(--text-dark);
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            line-height: 1.8;
            font-size: 1.05rem;
            font-weight: 400;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .insights-content p:hover {
            background: var(--white);
            border-left-color: var(--accent-green);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .insights-content strong {
            color: var(--dark-green);
            font-weight: 700;
            padding: 2px 6px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 4px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.03);
        }

        .insights-content code {
            background: linear-gradient(135deg, rgba(26, 77, 46, 0.1) 0%, rgba(76, 175, 80, 0.08) 100%);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            color: var(--dark-green);
            font-weight: 600;
            border: 1px solid rgba(76, 175, 80, 0.2);
            font-size: 0.95em;
        }

        .insights-section {
            background: linear-gradient(135deg, var(--white) 0%, rgba(247, 249, 252, 0.5) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .insights-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(76, 175, 80, 0.03) 0%, transparent 70%);
            pointer-events: none;
        }

        .insights-section:hover {
            border-color: var(--light-green);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.15);
            transform: translateY(-2px);
        }

        .error-message {
            color: #8c2626;
            background: linear-gradient(135deg, #fcebeb 0%, #ffe6e6 100%);
            border: 2px solid #f2c7c7;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: bold;
            display: none;
            box-shadow: 0 4px 12px rgba(140, 38, 38, 0.1);
        }

        .error-message.active {
            display: block;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .metric-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--light-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            margin: 0 5px;
            box-shadow: 0 3px 8px rgba(76, 175, 80, 0.3);
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                flex-direction: row;
                justify-content: space-around;
                align-items: center;
                box-shadow: 0 2px 10px var(--shadow);
            }
            .sidebar h2 {
                display: none;
            }
            .sidebar a {
                padding: 12px;
                margin-bottom: 0;
                flex-grow: 1;
                text-align: center;
                font-size: 0.9rem;
            }
            .main-content {
                padding: 20px;
            }
            .analytics-container {
                padding: 25px;
            }
            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="user_management.php">User Management</a>
        <a href="add_product.php">Add Product</a>
        <a href="add_category.php">Add Category</a>
        <a href="analytics.php">Analytics</a>
        <a href="ai_analytics_insights.php" class="active">Analytics Overview</a>

        <a href="../auth/admin_logout.php" style="margin-top: auto;">Logout</a>
    </div>

    <div class="main-content">
        <div class="analytics-container">
            <div class="page-header">
                <h1>
                    🤖 AI-Powered Analytics
                </h1>
                <p class="subtitle">Transform Your Data into Actionable Business Intelligence</p>
            </div>
            
            <button class="generate-btn" id="generateBtn">
                ✨ Generate AI Insights
            </button>

            <div class="error-message" id="errorMessage"></div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p class="loading-text">🔍 Analyzing your business data... This may take a moment.</p>
            </div>

            <div class="insights-content" id="insightsContent"></div>
        </div>
    </div>

    <script>
        document.getElementById('generateBtn').addEventListener('click', function() {
            const btn = this;
            const loading = document.getElementById('loading');
            const insightsContent = document.getElementById('insightsContent');
            const errorMessage = document.getElementById('errorMessage');

            // Reset states
            btn.disabled = true;
            btn.textContent = '⏳ Generating Insights...';
            loading.classList.add('active');
            insightsContent.classList.remove('active');
            errorMessage.classList.remove('active');

            // Fetch AI insights
            fetch('ai_analytics_insights.php')
                .then(response => response.json())
                .then(data => {
                    loading.classList.remove('active');
                    btn.disabled = false;
                    btn.textContent = '🔄 Regenerate AI Insights';

                    if (data.success) {
                        // Convert markdown-style formatting to HTML with enhanced styling
                        let htmlContent = data.insights
                            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                            .replace(/`(.+?)`/g, '<code>$1</code>')
                            .replace(/₱([\d,]+(?:\.\d{2})?)/g, '<span class="metric-badge">₱$1</span>')
                            .replace(/(\d+)%/g, '<span class="metric-badge">$1%</span>')
                            .replace(/^### (.+)$/gm, '<h3>$1</h3>')
                            .replace(/^## (.+)$/gm, '<h2>$1</h2>')
                            .replace(/^# (.+)$/gm, '<h2>$1</h2>')
                            .replace(/^(\d+)\.\s+(.+)$/gm, '<li>$2</li>')
                            .replace(/^[\-\*]\s+(.+)$/gm, '<li>$1</li>')
                            .replace(/\n\n/g, '</p><p>')
                            .replace(/(<li>.*?<\/li>\s*)+/gs, '<ul>$&</ul>')
                            .replace(/<\/ul>\s*<ul>/g, '');

                        // Wrap sections
                        htmlContent = htmlContent.split('<h2>').map((section, index) => {
                            if (index === 0) return section;
                            return '<div class="insights-section"><h2>' + section + '</div>';
                        }).join('');

                        // Wrap remaining content in paragraphs
                        if (!htmlContent.includes('<p>') && !htmlContent.startsWith('<h2>')) {
                            htmlContent = '<p>' + htmlContent + '</p>';
                        }

                        insightsContent.innerHTML = htmlContent;
                        insightsContent.classList.add('active');
                    } else {
                        errorMessage.textContent = '❌ Error: ' + (data.error || 'Failed to generate insights');
                        errorMessage.classList.add('active');
                    }
                })
                .catch(error => {
                    loading.classList.remove('active');
                    btn.disabled = false;
                    btn.textContent = '✨ Generate AI Insights';
                    errorMessage.textContent = '❌ Error: ' + error.message;
                    errorMessage.classList.add('active');
                });
        });
    </script>
</body>
</html>