<?php
session_start();
require_once 'include/config.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// ===== YOUR EXISTING CODE STARTS HERE =====

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get categories for navigation
$categories_stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

// Get trending products (top 3 by monthly_sold)
$trending_stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.monthly_sold > 0
    ORDER BY p.monthly_sold DESC 
    LIMIT 4
");
$trending_stmt->execute();
$trending_products = $trending_stmt->fetchAll();

// Get cart count for current user
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

// Handle category filter
$selected_category = $_GET['category'] ?? '';

// Handle search
$search_query = $_GET['search'] ?? '';

// ===== PAGINATION CODE =====
$products_per_page = 6; // Changed from 8 to 6
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $products_per_page;

// Get total products count based on filters
if ($search_query) {
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.name LIKE ?
    ");
    $count_stmt->execute(['%' . $search_query . '%']);
} elseif ($selected_category) {
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM products p 
        WHERE p.category_id = ?
    ");
    $count_stmt->execute([$selected_category]);
} else {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
    $count_stmt->execute();
}

$total_products = $count_stmt->fetch()['total'];
$total_pages = ceil($total_products / $products_per_page);

// Get products with pagination
if ($search_query) {
    $search_sql = "
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.name LIKE ?
        ORDER BY p.name
        LIMIT $products_per_page OFFSET $offset
    ";
    $search_stmt = $pdo->prepare($search_sql);
    $search_stmt->execute(['%' . $search_query . '%']);
    $products = $search_stmt->fetchAll();
} elseif ($selected_category) {
    $filtered_sql = "
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE p.category_id = ?
        ORDER BY p.name
        LIMIT $products_per_page OFFSET $offset
    ";
    $filtered_stmt = $pdo->prepare($filtered_sql);
    $filtered_stmt->execute([$selected_category]);
    $products = $filtered_stmt->fetchAll();
} else {
    // For main page without filters - show recent products
    $products_sql = "
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        ORDER BY p.created_at DESC 
        LIMIT $products_per_page OFFSET $offset
    ";
    $products_stmt = $pdo->prepare($products_sql);
    $products_stmt->execute();
    $products = $products_stmt->fetchAll();
}
// ===== END PAGINATION CODE =====

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
    <title>Dashboard - Coopamart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/index.css">

    <style>
/* Search Bar Styles */
.search-bar {
    flex: 1;
    max-width: 500px;
    margin: 0 20px;
}

.search-container {
    position: relative;
    display: flex;
    align-items: center;
}

.search-bar input {
    width: 100%;
    padding: 12px 50px 12px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 16px;
    outline: none;
    transition: all 0.3s ease;
    background: white;
}

.search-bar input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.search-btn {
    position: absolute;
    right: 5px;
    background: #007bff;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
}

.search-btn:hover {
    background: #0056b3;
}

/* Pagination Styles */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 40px 0 20px;
    gap: 10px;
    flex-wrap: wrap;
}

.pagination-btn {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.pagination-numbers {
    display: flex;
    gap: 5px;
    margin: 0 15px;
}

.pagination-number {
    padding: 10px 15px;
    background: #f8f9fa;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    min-width: 45px;
    text-align: center;
}

.pagination-number:hover {
    background: #e9ecef;
}

.pagination-number.active {
    background: #007bff;
    color: white;
}

.pagination-ellipsis {
    padding: 10px 5px;
    color: #6c757d;
}

/* Results Count */
.results-count {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: normal;
    margin-left: 10px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .search-bar {
        order: 2;
        max-width: 100%;
        margin: 10px 0;
        flex: 0 0 100%;
    }
    
    .mobile-menu-toggle {
        order: 1;
    }
    
    .logo {
        order: 0;
    }
    
    .header-actions {
        order: 3;
    }
    
    .pagination {
        flex-direction: column;
        gap: 15px;
    }
    
    .pagination-numbers {
        margin: 10px 0;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination-number {
        padding: 8px 12px;
        min-width: 40px;
    }
    
    .pagination-btn {
        padding: 8px 16px;
        font-size: 14px;
    }
    
    .results-count {
        display: block;
        margin-left: 0;
        margin-top: 5px;
    }
}

@media (max-width: 480px) {
    .pagination-numbers {
        gap: 3px;
    }
    
    .pagination-number {
        padding: 6px 10px;
        min-width: 35px;
        font-size: 14px;
    }
    
    .search-bar input {
        padding: 10px 45px 10px 15px;
        font-size: 14px;
    }
    
    .search-btn {
        width: 35px;
        height: 35px;
    }
}
/* Mobile-only search bar */
.mobile-search-bar {
    display: none;
    width: 100%;
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
}

@media (max-width: 768px) {
    .mobile-search-bar {
        display: block;
    }
    
    /* Hide the main search bar on mobile */
    .search-bar {
        display: none;
    }
    
    /* Show main search bar on desktop */
    @media (min-width: 769px) {
        .search-bar {
            display: block;
        }
        .mobile-search-bar {
            display: none;
        }
    }
}
.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    width: 100%;
}

.logo {
    flex-shrink: 0;
}

.search-bar {
    flex: 1;
    min-width: 200px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Mobile menu styles */
@media (max-width: 768px) {
    .header-actions {
        position: fixed;
        top: 0;
        right: -100%;
        width: 300px;
        height: 100vh;
        background: white;
        flex-direction: column;
        padding: 20px;
        box-shadow: -5px 0 15px rgba(0,0,0,0.1);
        transition: right 0.3s ease;
        z-index: 1000;
        align-items: flex-start;
        gap: 20px;
    }
    
    .header-actions.show {
        right: 0;
    }
    
    .mobile-menu-close {
        align-self: flex-end;
    }
}
    </style>
</head>
<body>
<header class="header">
    <div class="header-content">
        <a href="index.php" class="logo" style="text-decoration: none;">
            <img src="logo.png" alt="Coopamart Logo" style="height: 66px; width: 78px;">
        </a>

        <!-- Search Bar -->
        <div class="search-bar">
            <form method="GET" action="">
                <div class="search-container">
                    <input type="text" name="search" placeholder="Search products..." 
                           value="<?= htmlspecialchars($search_query) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="header-actions" id="headerActions">
            <!-- Close button for mobile -->
            <button class="mobile-menu-close" onclick="toggleMobileMenu()">
                <i class="fas fa-times"></i>
            </button>

                <div class="mobile-search-bar">
        <form method="GET" action="">
            <div class="search-container">
                <input type="text" name="search" placeholder="Search products..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

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
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></div>
                    <div style="font-size: 0.8rem; opacity: 0.8;">Welcome!</div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Image Modal for Full View -->
<div class="image-modal" id="imageModal">
    <button class="image-modal-close" onclick="closeImageModal()">
        <i class="fas fa-times"></i>
    </button>
    <div class="image-modal-content">
        <img id="modalImage" src="" alt="Product Image">
    </div>
</div>

    <div class="page-container">
        <!-- Mobile Category Toggle Button -->
        <button class="mobile-nav-toggle" id="mobileNavToggle" onclick="toggleCategories()">
            <i class="fas fa-list"></i>
            Browse Categories
            <i class="fas fa-chevron-down"></i>
        </button>

        <aside class="sidebar-nav" id="sidebarNav">
            <div class="sidebar-nav-title">Categories</div>
            <ul>
                <li>
                    <a href="?" class="<?= empty($selected_category) ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> All Products
                    </a>
                </li>
                <?php foreach ($categories as $category): ?>
                <li>
                    <a href="?category=<?= $category['category_id'] ?>" 
                       class="<?= $selected_category == $category['category_id'] ? 'active' : '' ?>">
                        <i class="fas fa-tag"></i> <?= htmlspecialchars($category['name']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="main-content">
            <?php if (!empty($trending_products) && !$search_query && !$selected_category): ?>
                <div class="section-title trending">
                    <i class="fas fa-fire"></i>
                    Trending Products
                </div>

                <div class="trending-carousel">
                    <?php foreach ($trending_products as $product): ?>
                        <div class="product-card trending">
                            <div class="trending-badge">
                                <i class="fas fa-fire"></i> 
                                <?= $product['monthly_sold'] ?> sold
                            </div>
                            
                            <div class="product-image" onclick="openImageModal('<?= htmlspecialchars($product['image_path'] ?: 'https://placehold.co/600x400?text=No+Image') ?>')">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <img src="<?= htmlspecialchars($product['image_path'] ?: 'https://placehold.co/280x200?text=No+Image') ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     onerror="this.onerror=null;this.src='https://placehold.co/280x200?text=No+Image'"
                                     onload="this.parentElement.classList.remove('loading')"
                                     onloadstart="this.parentElement.classList.add('loading')">
                            </div>
                            
                            <div class="product-info">
                                <?php if ($product['category_name']): ?>
                                    <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                                <?php endif; ?>
                                
                                <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                                
                                <div class="product-stats">
                                    <span class="monthly-sold">
                                        <i class="fas fa-chart-line"></i> <?= $product['monthly_sold'] ?> sold this month
                                    </span>
                                </div>
                                
                                <div class="product-actions">
                                    <button class="btn btn-primary" onclick="addToCart(<?= $product['product_id'] ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            <div class="section-title">
                <i class="fas fa-store"></i>
                <?php if ($search_query): ?>
                    Search Results for "<?= htmlspecialchars($search_query) ?>"
                    <?php if ($total_products > 0): ?>
                        <span class="results-count">(<?= $total_products ?> results found)</span>
                    <?php endif; ?>
                <?php elseif ($selected_category): ?>
                    <?php 
                    $selected_cat = array_filter($categories, fn($cat) => $cat['category_id'] == $selected_category);
                    $selected_cat = reset($selected_cat);
                    echo htmlspecialchars($selected_cat['name'] ?? 'Category');
                    ?> Products
                    <?php if ($total_products > 0): ?>
                        <span class="results-count">(<?= $total_products ?> products)</span>
                    <?php endif; ?>
                <?php else: ?>
                    All Products
                    <?php if ($total_products > 0): ?>
                        <span class="results-count">(<?= $total_products ?> products)</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (empty($products)): ?>
                <div class="no-products">
                    <i class="fas fa-box-open"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your search or browse different categories.</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image" onclick="openImageModal('<?= htmlspecialchars($product['image_path'] ?: 'https://placehold.co/600x400?text=No+Image') ?>')">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <img src="<?= htmlspecialchars($product['image_path'] ?: 'https://placehold.co/280x200?text=No+Image') ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     onerror="this.onerror=null;this.src='https://placehold.co/280x200?text=No+Image'"
                                     onload="this.parentElement.classList.remove('loading')"
                                     onloadstart="this.parentElement.classList.add('loading')">
                            </div>
                            
                            <div class="product-info">
                                <?php if ($product['category_name']): ?>
                                    <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                                <?php endif; ?>
                                
                                <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                                
                                <?php if ($product['monthly_sold'] > 0): ?>
                                <div class="product-stats">
                                    <span class="monthly-sold">
                                        <i class="fas fa-chart-line"></i> <?= $product['monthly_sold'] ?> sold
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <button class="btn btn-primary" onclick="addToCart(<?= $product['product_id'] ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="pagination-numbers">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                   class="pagination-number <?= $i == $current_page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php elseif ($i == $current_page - 3 || $i == $current_page + 3): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>" class="pagination-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // ===== YOUR EXISTING JAVASCRIPT CODE =====

        // Mobile header menu toggle
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

        // Mobile categories toggle
        function toggleCategories() {
            const sidebarNav = document.getElementById('sidebarNav');
            const mobileNavToggle = document.getElementById('mobileNavToggle');
            
            sidebarNav.classList.toggle('show');
            mobileNavToggle.classList.toggle('active');
            
            // Update button text
            if (sidebarNav.classList.contains('show')) {
                mobileNavToggle.innerHTML = '<i class="fas fa-list"></i> Hide Categories <i class="fas fa-chevron-up"></i>';
            } else {
                mobileNavToggle.innerHTML = '<i class="fas fa-list"></i> Browse Categories <i class="fas fa-chevron-down"></i>';
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

            // Close categories when clicking on a category link on mobile
            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                link.addEventListener('click', function() {
                    const sidebarNav = document.getElementById('sidebarNav');
                    const mobileNavToggle = document.getElementById('mobileNavToggle');
                    
                    sidebarNav.classList.remove('show');
                    mobileNavToggle.classList.remove('active');
                    mobileNavToggle.innerHTML = '<i class="fas fa-list"></i> Browse Categories <i class="fas fa-chevron-down"></i>';
                });
            });
        }

        // Image Modal Functions
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            
            modalImage.src = imageSrc.replace('280x200', '600x400'); // Use higher resolution if available
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });

        // Auto-update notification count every 30 seconds
        function updateNotificationCount() {
            fetch('get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const notificationCountElements = document.querySelectorAll('.notification-count');
                    const sidebarCountElements = document.querySelectorAll('.sidebar-nav .notification-count');
                    
                    if (data.count > 0) {
                        // Update header notification count
                        notificationCountElements.forEach(element => {
                            element.textContent = data.count;
                            element.style.display = 'flex';
                        });
                        
                        // Update sidebar notification count
                        sidebarCountElements.forEach(element => {
                            element.textContent = data.count;
                            element.style.display = 'inline';
                        });
                    } else {
                        // Hide notification count if zero
                        notificationCountElements.forEach(element => {
                            element.style.display = 'none';
                        });
                        sidebarCountElements.forEach(element => {
                            element.style.display = 'none';
                        });
                    }
                })
                .catch(error => console.error('Error updating notification count:', error));
        }

        // Update every 30 seconds
        setInterval(updateNotificationCount, 30000);

        // Also update when page becomes visible
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                updateNotificationCount();
            }
        });

        // Add to cart functionality
        function addToCart(productId) {
            fetch('include/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCountSpan = document.querySelector('.cart-count');
                    let currentCount = parseInt(cartCountSpan ? cartCountSpan.textContent : '0');
                    if (!cartCountSpan) {
                         const cartIcon = document.querySelector('.header-icon-link[title="Cart"]');
                         const newCount = document.createElement('span');
                         newCount.className = 'cart-count';
                         newCount.textContent = '1';
                         cartIcon.appendChild(newCount);
                    } else {
                        cartCountSpan.textContent = currentCount + 1;
                    }
                    showNotification('Product added to cart!', 'success');
                } else {
                    showNotification(data.message || 'Error adding to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding to cart', 'error');
            });
        }

        // Simple notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                ${type === 'success' ? 'background: #28a745;' : 
                  type === 'error' ? 'background: #dc3545;' : 'background: #6c757d;'}
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Search functionality
        document.querySelector('.search-bar input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });

        // Preload images for better performance
        function preloadImages() {
            const images = document.querySelectorAll('.product-image img');
            images.forEach(img => {
                const src = img.getAttribute('src');
                if (src) {
                    const preload = new Image();
                    preload.src = src;
                }
            });
        }

        // Initialize image preloading
        window.addEventListener('load', preloadImages);

        // Auto-show categories if a category is selected on mobile
        window.addEventListener('load', function() {
            if (window.innerWidth <= 768 && '<?= $selected_category ?>' !== '') {
                toggleCategories();
            }
        });
    </script>
</body>
</html>