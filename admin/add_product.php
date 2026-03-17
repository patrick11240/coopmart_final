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
$edit_product = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $product_id = $_POST['product_id'] ?? null;
        $name = trim($_POST['name']);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $category_id = $_POST['category_id'] ?? null;
        $image_path = null;
        $image_url = $_POST['image_url'] ?? '';

        // Check for required fields
        if (empty($name) || $price === false || $price <= 0 || $category_id === null) {
            $error_message = "All fields are required and must be valid.";
        } else {
            // Handle image upload from file or URL
            $file_uploaded = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
            $url_provided = !empty($image_url);

            if ($file_uploaded) {
                // Handle image upload
                $file_name = $_FILES['image']['name'];
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = ['jpeg', 'jpg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed_ext)) {
                    $unique_name = uniqid('product_', true) . '.' . $file_ext;
                    $upload_dir = __DIR__ . '/img/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $destination = $upload_dir . $unique_name;
                    
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $image_path = 'img/' . $unique_name;
                    } else {
                        $error_message = "Failed to upload image.";
                    }
                } else {
                    $error_message = "Invalid image file type. Only JPEG, PNG, and GIF are allowed.";
                }
            } elseif ($url_provided) {
                // Use the provided URL
                if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $image_path = $image_url;
                } else {
                    $error_message = "The provided image URL is not valid.";
                }
            } else {
                // If it's a new product, one image source is required
                if ($action === 'create') {
                    $error_message = "Please upload an image or provide an image URL.";
                }
            }

            if ($action === 'create' && empty($error_message)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO products (name, price, image_path, category_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $price, $image_path, $category_id]);
                    $success_message = "Product '{$name}' added successfully!";
                } catch (PDOException $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
            } elseif ($action === 'update' && empty($error_message)) {
                try {
                    $sql = "UPDATE products SET name = ?, price = ?, category_id = ? WHERE product_id = ?";
                    $params = [$name, $price, $category_id, $product_id];

                    if ($file_uploaded || $url_provided) {
                        // Delete old image if it was a local file
                        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
                        $stmt->execute([$product_id]);
                        $old_image_path = $stmt->fetchColumn();
                        if ($old_image_path && strpos($old_image_path, 'img/') === 0 && file_exists(__DIR__ . '/' . $old_image_path)) {
                            unlink(__DIR__ . '/' . $old_image_path);
                        }
                        $sql = "UPDATE products SET name = ?, price = ?, image_path = ?, category_id = ? WHERE product_id = ?";
                        $params = [$name, $price, $image_path, $category_id, $product_id];
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success_message = "Product updated successfully!";
                } catch (PDOException $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'delete') {
        $product_id = $_POST['product_id'] ?? null;
        if (empty($product_id)) {
            $error_message = "Product ID is required for deletion.";
        } else {
            try {
                // Delete image file first if it's a local file
                $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $image_to_delete = $stmt->fetchColumn();
                if ($image_to_delete && strpos($image_to_delete, 'img/') === 0 && file_exists(__DIR__ . '/' . $image_to_delete)) {
                    unlink(__DIR__ . '/' . $image_to_delete);
                }
                
                $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $success_message = "Product deleted successfully!";
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch a specific product for editing if ID is provided in URL
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$edit_id]);
        $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching product for editing: " . $e->getMessage();
    }
}

// Fetch all categories for the form dropdown
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching categories: " . $e->getMessage();
}

// Pagination setup
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;
$offset = ($current_page - 1) * $items_per_page;

// Fetch total number of products for pagination
$total_products = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn();
} catch (PDOException $e) {
    $error_message = "Error counting products: " . $e->getMessage();
}

// Calculate total pages
$total_pages = ceil($total_products / $items_per_page);

// Fetch products with pagination
$products = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.name ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching products: " . $e->getMessage();
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
    <title>Add Product</title>
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

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="file"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
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
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        .product-table th, .product-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .product-table th {
            background-color: var(--dark-green);
            color: var(--white);
            font-weight: bold;
        }
        .product-table tr:hover {
            background-color: #f0f2f5;
        }
        .product-table td img {
            max-width: 80px;
            height: auto;
            border-radius: 4px;
            cursor: pointer;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.9);
            padding-top: 60px;
        }

        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
        }

        .modal-content, #caption {
            animation-name: zoom;
            animation-duration: 0.6s;
        }

        @keyframes zoom {
            from {transform: scale(0)}
            to {transform: scale(1)}
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }

        .close:hover, .close:focus {
            color: #bbb;
            text-decoration: none;
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
            
            .modal-content {
                width: 100%;
            }
            
            .product-table {
                font-size: 14px;
            }
            
            .product-table th, .product-table td {
                padding: 10px 8px;
            }
            
            .action-buttons button, .action-buttons a {
                padding: 8px 10px;
                font-size: 12px;
                margin-right: 5px;
            }
            
            .product-table td img {
                max-width: 60px;
            }

            .pagination {
                flex-wrap: wrap;
                gap: 5px;
            }

            .pagination a, .pagination span {
                padding: 8px 12px;
                font-size: 14px;
            }

            .serial-number {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 250px;
            }
            
            .product-table {
                font-size: 12px;
            }
            
            .product-table th, .product-table td {
                padding: 8px 5px;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .action-buttons button, .action-buttons a {
                padding: 6px 8px;
                font-size: 11px;
                margin-right: 0;
                text-align: center;
            }

            .serial-number {
                font-size: 11px;
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
        <a href="add_product.php" class="active"><i class="fas fa-plus-circle"></i> Add Product</a>
        <a href="add_category.php"><i class="fas fa-tags"></i> Add Category</a>
        <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="../auth/admin_logout.php" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="form-container">
            <h1><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h1>

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

            <form action="add_product.php" method="POST" enctype="multipart/form-data">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($edit_product['product_id']); ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="create">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="price">Price (Peso):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $edit_product ? htmlspecialchars($edit_product['price']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="image">Product Image (Upload File):</label>
                    <input type="file" id="image" name="image">
                </div>

                <div class="form-group">
                    <label for="image_url">Or, Product Image (Use URL):</label>
                    <input type="text" id="image_url" name="image_url" placeholder="Paste image link here" value="<?php echo ($edit_product && strpos($edit_product['image_path'], 'http') === 0) ? htmlspecialchars($edit_product['image_path']) : ''; ?>">
                    <?php if ($edit_product && $edit_product['image_path']): ?>
                        <p style="margin-top: 10px; font-size: 0.9em; color: var(--text-light);">Current image: <a href="<?php echo htmlspecialchars($edit_product['image_path']); ?>" target="_blank">View</a></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category_id" required>
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['category_id']); ?>"
                                <?php echo ($edit_product && $edit_product['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit"><?php echo $edit_product ? 'Update Product' : 'Add Product'; ?></button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h1>Existing Products</h1>
            
            <?php if (empty($products)): ?>
                <p style="text-align:center; color:var(--text-light);">No products found.</p>
            <?php else: ?>
                <!-- Results Information -->
                <div class="results-info">
                    Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
                    (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                </div>

                <table class="product-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = $start_number;
                        foreach ($products as $product): 
                        ?>
                            <tr>
                                <td class="serial-number"><?php echo $counter++; ?></td>
                                <td>
                                    <?php if ($product['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" data-image-url="<?php echo htmlspecialchars($product['image_path']); ?>">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    <a href="add_product.php?edit_id=<?php echo htmlspecialchars($product['product_id']); ?>" class="edit-btn">Edit</a>
                                    <form action="add_product.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                        <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
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
                            <a href="add_product.php?page=1<?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>">First</a>
                        <?php else: ?>
                            <span class="disabled">First</span>
                        <?php endif; ?>

                        <!-- Previous Page -->
                        <?php if ($current_page > 1): ?>
                            <a href="add_product.php?page=<?php echo $current_page - 1; ?><?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>">Previous</a>
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
                                <a href="add_product.php?page=<?php echo $i; ?><?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="add_product.php?page=<?php echo $current_page + 1; ?><?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>

                        <!-- Last Page -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="add_product.php?page=<?php echo $total_pages; ?><?php echo isset($_GET['edit_id']) ? '&edit_id=' . htmlspecialchars($_GET['edit_id']) : ''; ?>">Last</a>
                        <?php else: ?>
                            <span class="disabled">Last</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- The Modal -->
    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
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

            // Image modal functionality
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const productImages = document.querySelectorAll('.product-table img');
            const span = document.querySelector('.close');

            productImages.forEach(image => {
                image.addEventListener('click', function() {
                    modal.style.display = 'block';
                    modalImg.src = this.getAttribute('data-image-url');
                });
            });

            span.onclick = function() {
                modal.style.display = 'none';
            }

            modal.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>