<?php
session_start();
require_once '../include/config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the admin login page if not authenticated as an admin
    header("Location: ../auth/admin_login.php");
    exit();
}

$success_message = '';
$error_message = '';
$edit_category = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $category_name = trim($_POST['category_name']);
        if (empty($category_name)) {
            $error_message = "Category name cannot be empty.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
                $stmt->execute([$category_name]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "A category with this name already exists.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt->execute([$category_name]);
                    $success_message = "Category '{$category_name}' added successfully!";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($action === 'update') {
        $category_id = $_POST['category_id'] ?? null;
        $category_name = trim($_POST['category_name'] ?? '');
        if (empty($category_name) || empty($category_id)) {
            $error_message = "Category name and ID are required.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND category_id != ?");
                $stmt->execute([$category_name, $category_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "A category with this name already exists.";
                } else {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
                    $stmt->execute([$category_name, $category_id]);
                    $success_message = "Category updated successfully!";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $category_id = $_POST['category_id'] ?? null;
        if (empty($category_id)) {
            $error_message = "Category ID is required for deletion.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $success_message = "Category deleted successfully!";
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch a specific category for editing if ID is provided in URL
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
        $stmt->execute([$edit_id]);
        $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching category for editing: " . $e->getMessage();
    }
}

// Pagination setup
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;
$offset = ($current_page - 1) * $items_per_page;

// Fetch total number of categories for pagination
$total_categories = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $total_categories = $stmt->fetchColumn();
} catch (PDOException $e) {
    $error_message = "Error counting categories: " . $e->getMessage();
}

// Calculate total pages
$total_pages = ceil($total_categories / $items_per_page);

// Fetch categories with pagination
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching categories: " . $e->getMessage();
}

// Fetch product counts for each category
$category_product_counts = [];
try {
    $stmt = $pdo->query("
        SELECT c.category_id, COUNT(p.product_id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.category_id = p.category_id 
        GROUP BY c.category_id
    ");
    $category_product_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    // If products table doesn't exist or there's an error, continue without product counts
    $category_product_counts = [];
}

// Calculate auto-increment numbers for display
$start_number = ($current_page - 1) * $items_per_page + 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Manage Categories</title>
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
            --delete-red: #d9534f;
            --edit-blue: #5cb85c;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--medium-green) 100%);
            color: var(--white);
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            z-index: 1000;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            color: var(--white);
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .sidebar a {
            color: var(--white);
            text-decoration: none;
            padding: 15px 20px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .sidebar a:hover {
            background-color: rgba(255,255,255,0.15);
            transform: translateX(5px);
            opacity: 1;
        }

        .sidebar a.active {
            background-color: rgba(255,255,255,0.2);
            border-left: 4px solid var(--accent-green);
            opacity: 1;
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex-grow: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s ease;
            margin-left: 280px;
            width: calc(100% - 280px);
        }
        
        .form-container, .table-container {
            background: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 900px;
            text-align: left;
            margin-bottom: 30px;
        }

        h1 {
            color: var(--text-dark);
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
            border-bottom: 2px solid var(--light-green);
            padding-bottom: 10px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-dark);
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        .form-group input[type="text"]:focus {
            border-color: var(--light-green);
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
            outline: none;
        }

        .form-group button {
            width: 100%;
            padding: 14px;
            background-color: var(--light-green);
            border: none;
            border-radius: 8px;
            color: var(--white);
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .form-group button:hover {
            background-color: var(--medium-green);
            transform: translateY(-2px);
        }

        .message.success {
            color: #1a632e;
            background-color: #e4f4e7;
            border: 1px solid #c9e2d3;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: bold;
        }
        
        .message.error {
            color: #8c2626;
            background-color: #fcebeb;
            border: 1px solid #f2c7c7;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: bold;
        }
        
        /* Table Styles */
        .category-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        .category-table th, .category-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .category-table th {
            background-color: var(--dark-green);
            color: var(--white);
            font-weight: bold;
        }
        .category-table tr:hover {
            background-color: #f0f2f5;
        }
        .action-buttons button, .action-buttons a {
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--white);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-block;
            margin-right: 8px;
        }
        .action-buttons .edit-btn {
            background-color: var(--edit-blue);
        }
        .action-buttons .edit-btn:hover {
            background-color: #49a94a;
        }
        .action-buttons .delete-btn {
            background-color: var(--delete-red);
        }
        .action-buttons .delete-btn:hover {
            background-color: #c94c48;
        }

        /* Product Count Badge */
        .product-count {
            display: inline-block;
            background-color: var(--light-green);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
        }

        /* Serial Number Column */
        .serial-number {
            text-align: center;
            font-weight: bold;
            color: var(--text-dark);
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 10px;
        }

        .pagination a, .pagination span {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .pagination a:hover {
            background-color: var(--light-green);
            color: white;
            border-color: var(--light-green);
        }

        .pagination .current {
            background-color: var(--dark-green);
            color: white;
            border-color: var(--dark-green);
        }

        .pagination .disabled {
            color: var(--text-light);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .results-info {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 15px;
            font-style: italic;
        }

        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            background-color: var(--dark-green);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 18px;
            cursor: pointer;
            z-index: 1001;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 60px 20px 20px;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .overlay.active {
                display: block;
            }
            
            .form-container, .table-container {
                padding: 20px;
            }

            .pagination {
                flex-wrap: wrap;
                gap: 5px;
            }

            .pagination a, .pagination span {
                padding: 8px 12px;
                font-size: 14px;
            }

            .category-table th, .category-table td {
                padding: 10px 8px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 250px;
            }
            
            .category-table {
                font-size: 14px;
            }
            
            .category-table th, .category-table td {
                padding: 8px 6px;
            }
            
            .action-buttons button, .action-buttons a {
                padding: 6px 8px;
                font-size: 12px;
                margin-right: 4px;
            }

            .product-count {
                font-size: 10px;
                padding: 2px 6px;
                margin-left: 4px;
            }

            .serial-number {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
  <button class="mobile-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="overlay" id="overlay"></div>
    
    <div class="sidebar" id="sidebar">
        <h2><i class="fas fa-cogs"></i> Admin Panel</h2>
        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
        <a href="add_product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
        <a href="add_category.php" class="active"><i class="fas fa-tags"></i> Add Category</a>
        <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="../auth/admin_logout.php" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="form-container">
            <h1><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h1>
            
            <?php if (!empty($success_message)): ?>
                <div class="message success">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <form action="add_category.php" method="POST">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($edit_category['category_id']); ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="create">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="category_name">Category Name:</label>
                    <input type="text" id="category_name" name="category_name" value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit"><?php echo $edit_category ? 'Update Category' : 'Add Category'; ?></button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h1>Existing Categories</h1>
            
            <?php if (empty($categories)): ?>
                <p style="text-align:center; color:var(--text-light);">No categories found.</p>
            <?php else: ?>
                <!-- Results Information -->
                <div class="results-info">
                    Showing <?php echo count($categories); ?> of <?php echo $total_categories; ?> categories
                    (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                </div>

                <table class="category-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Products</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = $start_number;
                        foreach ($categories as $category): 
                        ?>
                            <tr>
                                <td class="serial-number"><?php echo $counter++; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                    <?php if (isset($category_product_counts[$category['category_id']]) && $category_product_counts[$category['category_id']] > 0): ?>
                                        <span class="product-count" title="Number of products in this category">
                                            <?php echo $category_product_counts[$category['category_id']]; ?> product(s)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo isset($category_product_counts[$category['category_id']]) ? $category_product_counts[$category['category_id']] : '0'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($category['created_at']); ?></td>
                                <td class="action-buttons">
                                    <a href="add_category.php?edit_id=<?php echo htmlspecialchars($category['category_id']); ?>" class="edit-btn">Edit</a>
                                    <form action="add_category.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                        <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this category?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <!-- First Page -->
                        <?php if ($current_page > 1): ?>
                            <a href="add_category.php?page=1<?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>">First</a>
                        <?php else: ?>
                            <span class="disabled">First</span>
                        <?php endif; ?>

                        <!-- Previous Page -->
                        <?php if ($current_page > 1): ?>
                            <a href="add_category.php?page=<?php echo $current_page - 1; ?><?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>">Previous</a>
                        <?php else: ?>
                            <span class="disabled">Previous</span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $current_page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="add_category.php?page=<?php echo $i; ?><?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="add_category.php?page=<?php echo $current_page + 1; ?><?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>

                        <!-- Last Page -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="add_category.php?page=<?php echo $total_pages; ?><?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>">Last</a>
                        <?php else: ?>
                            <span class="disabled">Last</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('overlay');
            
            // Toggle sidebar on button click
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            
            // Close sidebar when clicking on overlay
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
            
            // Close sidebar when clicking on a link (on mobile)
            if (window.innerWidth <= 768) {
                const sidebarLinks = document.querySelectorAll('.sidebar a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>