<?php
session_start();
require_once '../include/config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle AJAX requests for modal content
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (isset($_GET['view_user']) && is_numeric($_GET['view_user'])) {
        $user_id = (int)$_GET['view_user'];
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    u.*,
                    COUNT(o.order_id) as total_orders,
                    COALESCE(SUM(o.total_price), 0) as total_spent,
                    MAX(o.created_at) as last_order_date
                FROM users u 
                LEFT JOIN orders o ON u.user_id = o.user_id 
                WHERE u.user_id = ?
                GROUP BY u.user_id
            ");
            $stmt->execute([$user_id]);
            $user_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_details) {
                $stmt = $pdo->prepare("
                    SELECT 
                        o.*,
                        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
                    FROM orders o 
                    WHERE o.user_id = ? 
                    ORDER BY o.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute([$user_id]);
                $user_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Output user modal content
                ?>
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-label">User ID</div>
                        <div class="detail-value">#<?php echo htmlspecialchars($user_details['user_id']); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user_details['full_name']); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user_details['email']); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user_details['phone'] ?? 'Not provided'); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Membership Type</div>
                        <div class="detail-value">
                            <span class="membership-badge membership-<?php echo $user_details['membership_type']; ?>">
                                <i class="fas fa-<?php echo $user_details['membership_type'] === 'sidc_member' ? 'crown' : 'user'; ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $user_details['membership_type'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Total Points</div>
                        <div class="detail-value"><?php echo number_format($user_details['points']); ?> points</div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Login Streak</div>
                        <div class="detail-value"><?php echo $user_details['login_streak']; ?> days</div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Total Orders</div>
                        <div class="detail-value"><?php echo $user_details['total_orders']; ?> orders</div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Total Spent</div>
                        <div class="detail-value">₱<?php echo number_format($user_details['total_spent'], 2); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Last Order</div>
                        <div class="detail-value">
                            <?php echo $user_details['last_order_date'] ? date('M j, Y H:i', strtotime($user_details['last_order_date'])) : 'No orders yet'; ?>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Account Created</div>
                        <div class="detail-value"><?php echo date('F j, Y H:i:s', strtotime($user_details['created_at'])); ?></div>
                    </div>
                </div>

                <h3 class="section-title">
                    <i class="fas fa-shopping-bag"></i> Order History (<?php echo count($user_orders); ?>)
                </h3>
                
                <?php if (!empty($user_orders)): ?>
                    <div class="table-container">
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Total Price</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                        <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td><?php echo $order['item_count']; ?> items</td>
                                        <td>
                                            <span class="status-badge status-<?php echo str_replace('_', '-', $order['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary view-order-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data" style="padding: 30px;">
                        <i class="fas fa-shopping-cart"></i>
                        <p>This user hasn't placed any orders yet.</p>
                    </div>
                <?php endif; ?>
                <?php
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-error">Error fetching user details: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        exit;
    }
    
    if (isset($_GET['view_order']) && is_numeric($_GET['view_order'])) {
        $order_id = (int)$_GET['view_order'];
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    o.*, 
                    u.full_name, 
                    u.email, 
                    u.phone, 
                    u.membership_type,
                    u.address,
                    u.city,
                    u.province
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.user_id 
                WHERE o.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order_details) {
                // Fetch order items
                $stmt = $pdo->prepare("
                    SELECT 
                        oi.*,
                        p.name as product_name,
                        p.image_path,
                        (oi.quantity * oi.price) as item_total
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.product_id
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order_id]);
                $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Output order modal content
                ?>
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-label">Order ID</div>
                        <div class="detail-value">#<?php echo htmlspecialchars($order_details['order_id']); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Customer Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order_details['full_name']); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Customer Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order_details['email']); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order_details['phone'] ?? 'Not provided'); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Membership Type</div>
                        <div class="detail-value">
                            <span class="membership-badge membership-<?php echo $order_details['membership_type']; ?>">
                                <i class="fas fa-<?php echo $order_details['membership_type'] === 'sidc_member' ? 'crown' : 'user'; ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $order_details['membership_type'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Total Price</div>
                        <div class="detail-value">₱<?php echo number_format($order_details['total_price'], 2); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Discount Amount</div>
                        <div class="detail-value">₱<?php echo number_format($order_details['discount_amount'] ?? 0, 2); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Packing Fee</div>
                        <div class="detail-value">₱<?php echo number_format($order_details['packing_fee'] ?? 0, 2); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Current Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?php echo str_replace('_', '-', $order_details['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order_details['status'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Spin Discount</div>
                        <div class="detail-value"><?php echo $order_details['spin_discount_percent'] ?? 0; ?>%</div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Points Discount</div>
                        <div class="detail-value">₱<?php echo number_format($order_details['points_discount'] ?? 0, 2); ?></div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Customer Address</div>
                        <div class="detail-value">
                            <?php 
                            $address = [];
                            if (!empty($order_details['address'])) $address[] = $order_details['address'];
                            if (!empty($order_details['city'])) $address[] = $order_details['city'];
                            if (!empty($order_details['province'])) $address[] = $order_details['province'];
                            echo $address ? htmlspecialchars(implode(', ', $address)) : 'Not provided';
                            ?>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Receipt Image</div>
                        <div class="detail-value">
                            <?php if (!empty($order_details['receipt_image'])): ?>
                                <?php 
                                $receipt_filename = basename($order_details['receipt_image']);
                                $receipt_path = '../uploads/receipts/' . htmlspecialchars($receipt_filename);
                                ?>
                                <img src="<?php echo $receipt_path; ?>" 
                                     alt="Receipt" 
                                     class="receipt-image" 
                                     onclick="showReceipt('<?php echo $receipt_path; ?>')"
                                     onerror="this.style.display='none'">
                            <?php else: ?>
                                <span style="color: var(--text-light); font-style: italic;">
                                    <i class="fas fa-receipt"></i> Not uploaded
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">Order Created</div>
                        <div class="detail-value"><?php echo date('F j, Y H:i:s', strtotime($order_details['created_at'])); ?></div>
                    </div>
                </div>

                <?php if (!empty($order_items)): ?>
                    <h3 class="section-title">
                        <i class="fas fa-boxes"></i> Order Items (<?php echo count($order_items); ?>)
                    </h3>
                    
                    <div class="table-container">
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></strong>
                                            <?php if (!empty($item['image_path'])): ?>
                                                <div style="margin-top: 5px;">
                                                    <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?>"
                                                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"
                                                         onerror="this.style.display='none'">
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td><strong>₱<?php echo number_format($item['item_total'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background: #f8f9fa;">
                                    <td colspan="3" style="text-align: right; font-weight: bold;">Grand Total:</td>
                                    <td style="font-weight: bold; font-size: 1.1rem;">
                                        ₱<?php echo number_format($order_details['total_price'], 2); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="status-update-form">
                    <h4><i class="fas fa-edit"></i> Update Order Status</h4>
                    <form method="post">
                        <input type="hidden" name="action" value="update_order_status">
                        <input type="hidden" name="order_id" value="<?php echo $order_details['order_id']; ?>">
                        <div class="form-group">
                            <label for="new_status">Select New Status:</label>
                            <select name="new_status" id="new_status" required>
                                <option value="pending_payment" <?php echo $order_details['status'] == 'pending_payment' ? 'selected' : ''; ?>>Pending Payment</option>
                                <option value="paid" <?php echo $order_details['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="processing_purchased_product" <?php echo $order_details['status'] == 'processing_purchased_product' ? 'selected' : ''; ?>>Processing Purchased Product</option>
                                <option value="ready_to_pick_the_purchased_product" <?php echo $order_details['status'] == 'ready_to_pick_the_purchased_product' ? 'selected' : ''; ?>>Ready to Pick</option>
                                <option value="completed" <?php echo $order_details['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="canceled" <?php echo $order_details['status'] == 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </form>
                </div>
                <?php
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-error">Error fetching order details: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        exit;
    }
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_order_status') {
        $order_id = $_POST['order_id'] ?? null;
        $new_status = $_POST['new_status'] ?? null;
        
        if (!empty($order_id) && !empty($new_status)) {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $stmt->execute([$new_status, $order_id]);
                $success_message = "Order status updated successfully!";
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        } else {
            $error_message = "Order ID and status are required.";
        }
    } elseif ($action === 'delete_user') {
        $user_id = $_POST['user_id'] ?? null;
        
        if (!empty($user_id)) {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // First delete related order items
                $stmt = $pdo->prepare("DELETE oi FROM order_items oi INNER JOIN orders o ON oi.order_id = o.order_id WHERE o.user_id = ?");
                $stmt->execute([$user_id]);
                
                // Then delete related orders
                $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user discounts
                $stmt = $pdo->prepare("DELETE FROM user_discounts WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete cart items
                $stmt = $pdo->prepare("DELETE ci FROM cart_items ci INNER JOIN carts c ON ci.cart_id = c.cart_id WHERE c.user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete cart
                $stmt = $pdo->prepare("DELETE FROM carts WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Finally delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                $success_message = "User and all related data deleted successfully!";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_order') {
        $order_id = $_POST['order_id'] ?? null;
        
        if (!empty($order_id)) {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // First delete order items
                $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
                $stmt->execute([$order_id]);
                
                // Then delete the order
                $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
                $stmt->execute([$order_id]);
                
                $pdo->commit();
                $success_message = "Order and related items deleted successfully!";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Pagination setup
$limit = 10; // Number of items per page
$users_page = isset($_GET['users_page']) ? (int)$_GET['users_page'] : 1;
$orders_page = isset($_GET['orders_page']) ? (int)$_GET['orders_page'] : 1;

// Ensure page numbers are at least 1
$users_page = max(1, $users_page);
$orders_page = max(1, $orders_page);

// Calculate offset
$users_offset = ($users_page - 1) * $limit;
$orders_offset = ($orders_page - 1) * $limit;

// Fetch total counts for pagination
try {
    $users_count_stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $users_total = $users_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $orders_count_stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $orders_total = $orders_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $error_message = "Error fetching counts: " . $e->getMessage();
}

// Calculate total pages
$users_total_pages = ceil($users_total / $limit);
$orders_total_pages = ceil($orders_total / $limit);

// Fetch users with their order count - FIXED QUERY with pagination
$users = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.*, 
            COUNT(o.order_id) as order_count,
            COALESCE(SUM(o.total_price), 0) as total_spent
        FROM users u 
        LEFT JOIN orders o ON u.user_id = o.user_id 
        GROUP BY u.user_id 
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $users_offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching users: " . $e->getMessage();
}

// Fetch orders with user information - ENHANCED QUERY with pagination
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*, 
            u.full_name, 
            u.email,
            u.phone,
            u.membership_type,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $orders_offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
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
    --warning-orange: #f0ad4e;
    --info-blue: #5bc0de;
    --success-green: #28a745;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--light-bg);
    margin: 0;
    padding: 0;
    min-height: 100vh;
    display: flex;
    line-height: 1.6;
}

.sidebar {
    width: 280px;
    background: linear-gradient(135deg, var(--dark-green) 0%, var(--medium-green) 100%);
    color: var(--white);
    padding: 20px;
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
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
    margin-left: 280px;
    padding: 30px;
    background-color: var(--light-bg);
    min-height: 100vh;
    width: calc(100% - 280px);
    transition: all 0.3s ease;
}

.header {
    background-color: var(--white);
    padding: 25px 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    position: relative;
    z-index: 100;
    border-left: 5px solid var(--accent-green);
}

.header h1 {
    color: var(--dark-green);
    margin: 0;
    font-size: 2.2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 15px;
}

.header h1 i {
    color: var(--accent-green);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--white);
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    text-align: center;
    border-top: 4px solid var(--accent-green);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-green);
    margin-bottom: 10px;
}

.stat-label {
    color: var(--text-light);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.9rem;
}

.alert {
    padding: 15px 20px;
    margin-bottom: 25px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.alert-success {
    background: linear-gradient(135deg, #d4ffd4 0%, #a8e6a8 100%);
    color: #155724;
    border-left: 4px solid var(--success-green);
}

.alert-error {
    background: linear-gradient(135deg, #ffd4d4 0%, #e6a8a8 100%);
    color: #721c24;
    border-left: 4px solid var(--delete-red);
}

.tabs {
    display: flex;
    margin-bottom: 30px;
    background-color: var(--white);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.tab-button {
    flex: 1;
    padding: 18px;
    background-color: var(--white);
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.tab-button:hover {
    background-color: #f8f9fa;
    color: var(--dark-green);
}

.tab-button.active {
    background: linear-gradient(135deg, var(--dark-green) 0%, var(--medium-green) 100%);
    color: var(--white);
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--accent-green);
}

.tab-content {
    display: none;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.tab-content.active {
    display: block;
}

.table-container {
    background-color: var(--white);
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    border: 1px solid var(--border-color);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

th, td {
    padding: 16px 20px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

th {
    background: linear-gradient(135deg, var(--dark-green) 0%, var(--medium-green) 100%);
    color: var(--white);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.9rem;
    position: sticky;
    top: 0;
}

tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.btn {
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    margin: 2px;
    transition: all 0.3s ease;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--info-blue) 0%, #4a90e2 100%);
    color: var(--white);
}

.btn-warning {
    background: linear-gradient(135deg, var(--warning-orange) 0%, #e68900 100%);
    color: var(--white);
}

.btn-danger {
    background: linear-gradient(135deg, var(--delete-red) 0%, #c9302c 100%);
    color: var(--white);
}

.btn-success {
    background: linear-gradient(135deg, var(--success-green) 0%, #218838 100%);
    color: var(--white);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.btn:active {
    transform: translateY(0);
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    min-width: 120px;
    text-align: center;
}

.status-pending { 
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); 
    color: #856404; 
    border: 1px solid #ffeaa7;
}
.status-paid { 
    background: linear-gradient(135deg, #d1ecf1 0%, #a6e1ec 100%); 
    color: #0c5460; 
    border: 1px solid #a6e1ec;
}
.status-processing { 
    background: linear-gradient(135deg, #f8d7da 0%, #f5b5ba 100%); 
    color: #721c24; 
    border: 1px solid #f5b5ba;
}
.status-ready { 
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); 
    color: #155724; 
    border: 1px solid #c3e6cb;
}
.status-completed { 
    background: linear-gradient(135deg, #d1ecf1 0%, #a6e1ec 100%); 
    color: #0c5460; 
    border: 1px solid #a6e1ec;
}
.status-canceled { 
    background: linear-gradient(135deg, #f8d7da 0%, #f5b5ba 100%); 
    color: #721c24; 
    border: 1px solid #f5b5ba;
}

.membership-badge {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.membership-regular { 
    background: linear-gradient(135deg, #e7f3ff 0%, #cce5ff 100%); 
    color: #004085; 
    border: 1px solid #cce5ff;
}
.membership-sidc_member { 
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); 
    color: #856404; 
    border: 1px solid #ffeaa7;
}
.membership-non_member { 
    background: linear-gradient(135deg, #f8d7da 0%, #f5b5ba 100%); 
    color: #721c24; 
    border: 1px solid #f5b5ba;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background-color: var(--white);
    margin: 3% auto;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 900px;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.4s ease;
}

@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    background: linear-gradient(135deg, var(--dark-green) 0%, var(--medium-green) 100%);
    color: var(--white);
    padding: 25px 30px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}

.close {
    color: var(--white);
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.3s ease;
    background: none;
    border: none;
    padding: 5px;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close:hover {
    opacity: 1;
    background: rgba(255,255,255,0.1);
}

.modal-body {
    padding: 30px;
}

.modal-body .detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.modal-body .detail-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid var(--accent-green);
    transition: transform 0.2s ease;
}

.modal-body .detail-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.modal-body .detail-label {
    font-weight: 600;
    color: var(--dark-green);
    margin-bottom: 8px;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modal-body .detail-value {
    color: #000000;
    font-size: 1.1rem;
    font-weight: 500;
}

.modal-body .section-title {
    color: var(--dark-green);
    margin: 25px 0 15px 0;
    font-size: 1.3rem;
    font-weight: 700;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-body .status-update-form {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 25px;
    border-radius: 12px;
    margin-top: 25px;
    border: 1px solid var(--border-color);
}

.modal-body .form-group {
    margin-bottom: 20px;
}

.modal-body .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark-green);
    font-size: 1rem;
}

.modal-body .form-group select, 
.modal-body .form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--white);
}

.modal-body .form-group select:focus, 
.modal-body .form-group input:focus {
    outline: none;
    border-color: var(--accent-green);
    box-shadow: 0 0 0 3px rgba(102, 187, 106, 0.1);
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-light);
    font-style: italic;
    font-size: 1.1rem;
}

.no-data i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Receipt Image Styles */
.receipt-image {
    max-width: 200px;
    max-height: 150px;
    cursor: pointer;
    border-radius: 8px;
    border: 2px solid var(--border-color);
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.receipt-image:hover {
    transform: scale(1.05);
    border-color: var(--accent-green);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.receipt-modal {
    display: none;
    position: fixed;
    z-index: 3000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    backdrop-filter: blur(10px);
}

.receipt-modal-content {
    display: block;
    margin: auto;
    max-width: 90%;
    max-height: 90%;
    margin-top: 3%;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    animation: zoomIn 0.3s ease;
}

@keyframes zoomIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.receipt-close {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
    background: rgba(0,0,0,0.5);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.receipt-close:hover {
    color: #bbb;
    background: rgba(0,0,0,0.7);
}

/* Order Items Table */
.order-items-table {
    width: 100%;
    margin-top: 20px;
    background: var(--white);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.order-items-table th {
    background: var(--medium-green);
}

/* Mobile Toggle Button */
.mobile-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    background: linear-gradient(135deg, var(--dark-green) 0%, var(--medium-green) 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 15px;
    font-size: 18px;
    cursor: pointer;
    z-index: 1001;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.mobile-toggle:hover {
    transform: scale(1.05);
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
    backdrop-filter: blur(5px);
}

/* Pagination Styles */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
    padding: 20px;
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    gap: 10px;
}

.pagination-info {
    margin: 0 20px;
    color: var(--text-dark);
    font-weight: 600;
    white-space: nowrap;
}

.page-btn {
    padding: 10px 20px;
    border: 2px solid var(--border-color);
    background: var(--white);
    color: var(--text-dark);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 80px;
    justify-content: center;
}

.page-btn:hover:not(.disabled) {
    background: var(--accent-green);
    color: var(--white);
    border-color: var(--accent-green);
    transform: translateY(-2px);
}

.page-btn.active {
    background: var(--dark-green);
    color: var(--white);
    border-color: var(--dark-green);
}

.page-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

/* Search and Filter Styles */
.search-filter {
    background: var(--white);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-box input:focus {
    outline: none;
    border-color: var(--accent-green);
    box-shadow: 0 0 0 3px rgba(102, 187, 106, 0.1);
}

.search-box i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
}

.filter-select {
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    background: var(--white);
    min-width: 150px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:focus {
    outline: none;
    border-color: var(--accent-green);
    box-shadow: 0 0 0 3px rgba(102, 187, 106, 0.1);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-buttons .btn {
    flex: 1;
    min-width: 80px;
    justify-content: center;
    text-align: center;
}

/* Loading Animation */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Modal Show State */
.modal.show {
    display: block !important;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .main-content {
        padding: 20px;
    }
    
    .header {
        padding: 20px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .modal-content {
        width: 95%;
        margin: 5% auto;
    }
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
        padding: 70px 15px 15px;
    }
    
    .mobile-toggle {
        display: block;
    }
    
    .overlay.active {
        display: block;
    }
    
    .modal-content {
        width: 95%;
        margin: 5% auto;
        padding: 0;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-body .detail-grid {
        grid-template-columns: 1fr;
    }

    .detail-label {
        width: auto;
        margin-bottom: 5px;
    }
    
    .tabs {
        flex-direction: column;
    }
    
    .tab-button {
        padding: 15px;
        font-size: 1rem;
    }
    
    table {
        font-size: 14px;
        min-width: auto;
    }
    
    th, td {
        padding: 12px 10px;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .btn {
        padding: 8px 12px;
        font-size: 0.8rem;
        margin: 2px 0;
        justify-content: center;
    }
    
    .receipt-image {
        max-width: 120px;
        max-height: 90px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .pagination {
        flex-wrap: wrap;
        gap: 10px;
    }

    .pagination-info {
        order: -1;
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
    }
    
    .search-filter {
        flex-direction: column;
        gap: 10px;
    }
    
    .search-box {
        min-width: 100%;
    }
    
    .filter-select {
        min-width: 100%;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 250px;
    }
    
    .header {
        padding: 15px;
    }
    
    .header h1 {
        font-size: 1.6rem;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 98%;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    table {
        font-size: 12px;
    }
    
    th, td {
        padding: 10px 8px;
    }
    
    .status-badge, .membership-badge {
        font-size: 0.7rem;
        padding: 6px 10px;
        min-width: 100px;
    }
    
    .btn {
        padding: 6px 10px;
        font-size: 0.7rem;
    }

    .stat-number {
        font-size: 2rem;
    }

    .page-btn {
        padding: 8px 12px;
        font-size: 0.8rem;
        min-width: 60px;
    }
    
    .modal-header {
        padding: 20px;
    }
    
    .modal-header h2 {
        font-size: 1.3rem;
    }
    
    .close {
        width: 35px;
        height: 35px;
        font-size: 24px;
    }
}

/* Print Styles */
@media print {
    .sidebar, .mobile-toggle, .tabs, .action-buttons {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .alert {
        display: none !important;
    }
    
    .btn {
        display: none !important;
    }
}

/* Additional Modal Enhancements */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1999;
}

.modal-dialog {
    max-width: 900px;
    margin: 1.75rem auto;
}

.modal-title {
    font-weight: 600;
    color: var(--dark-green);
}

/* Smooth transitions for modal */
.modal.fade .modal-content {
    transform: translate(0, -50px);
    transition: transform 0.3s ease-out;
}

.modal.show .modal-content {
    transform: translate(0, 0);
}

/* Enhanced button styles */
.btn-sm {
    padding: 8px 16px;
    font-size: 0.85rem;
}

.btn-lg {
    padding: 12px 24px;
    font-size: 1rem;
}

/* Badge variants */
.badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 6px;
}

.badge-success {
    background-color: var(--success-green);
    color: white;
}

.badge-warning {
    background-color: var(--warning-orange);
    color: white;
}

.badge-danger {
    background-color: var(--delete-red);
    color: white;
}

.badge-info {
    background-color: var(--info-blue);
    color: white;
}

/* Custom scrollbar for modal */
.modal-content::-webkit-scrollbar {
    width: 8px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 0 0 15px 0;
}

.modal-content::-webkit-scrollbar-thumb {
    background: var(--accent-green);
    border-radius: 4px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: var(--medium-green);
}

/* Focus states for accessibility */
.btn:focus,
.filter-select:focus,
.search-box input:focus,
.modal-body select:focus,
.modal-body input:focus {
    outline: 2px solid var(--accent-green);
    outline-offset: 2px;
}

/* Loading overlay for better UX */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    border-radius: 15px;
}

/* Animation for table rows */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.table-container tbody tr {
    animation: slideIn 0.3s ease;
}

/* Hover effects for cards */
.stat-card, .detail-card, .table-container {
    transition: all 0.3s ease;
}

.stat-card:hover, .detail-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Custom select styling */
.filter-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
    padding-right: 40px;
}

/* Enhanced modal responsiveness */
@media (max-height: 600px) {
    .modal-content {
        margin: 2% auto;
        max-height: 96vh;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .modal-header {
        padding: 20px;
    }
}

/* Print optimization */
@media print {
    .modal {
        position: absolute;
        width: 100%;
        height: auto;
        background: white;
    }
    
    .modal-content {
        box-shadow: none;
        margin: 0;
        max-height: none;
    }
    
    .modal-header {
        background: #333 !important;
        color: white !important;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .modal-content {
        background-color: #2d3748;
     color: #000000;
    }
    
    .modal-body .detail-card {
        background: #4a5568;
        color: #000000;
    }
    
    .modal-body .detail-label {
               color: #252525;
    }
    
    .modal-body .detail-value {
               color: #2b3440;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .btn {
        border: 2px solid;
    }
    
    .modal-content {
        border: 2px solid var(--dark-green);
    }
    
    .stat-card {
        border: 2px solid var(--accent-green);
    }
}
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: var(--white);
            margin: 3% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideUp 0.4s ease;
        }

        .modal.show {
            display: block !important;
        }

        .modal-content .btn {
            margin: 2px;
        }

        /* Ensure modal content is properly styled */
        .modal-body .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .modal-body .detail-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--accent-green);
        }

        .modal-body .section-title {
            color: var(--dark-green);
            margin: 25px 0 15px 0;
            font-size: 1.3rem;
            font-weight: 700;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }

        /* Fix for status update form in modal */
        .modal-body .status-update-form {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 12px;
            margin-top: 25px;
            border: 1px solid var(--border-color);
        }

        /* Ensure buttons in modal are properly styled */
        .modal-body .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Responsive fixes for modal */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .modal-body .detail-grid {
                grid-template-columns: 1fr;
            }

            .modal-body .action-buttons {
                flex-direction: column;
            }

            .modal-body .btn {
                width: 100%;
                justify-content: center;
            }
        }
        /* Enhanced Order Status Styles */
.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    min-width: 120px;
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.status-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.status-badge:hover::before {
    left: 100%;
}

.status-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Pending Payment */
.status-pending-payment { 
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); 
    color: #856404; 
    border-color: #ffd351;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
}

.status-pending-payment::after {
    content: '⏳';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Paid */
.status-paid { 
    background: linear-gradient(135deg, #d1edff 0%, #a6d8ff 100%); 
    color: #004085; 
    border-color: #66b3ff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}

.status-paid::after {
    content: '💰';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Processing */
.status-processing { 
    background: linear-gradient(135deg, #ffe6e6 0%, #ffcccc 100%); 
    color: #721c24; 
    border-color: #ff9999;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
}

.status-processing::after {
    content: '⚙️';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Processing Purchased Product */
.status-processing-purchased-product { 
    background: linear-gradient(135deg, #e6f3ff 0%, #cce7ff 100%); 
    color: #004085; 
    border-color: #99ccff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}

.status-processing-purchased-product::after {
    content: '📦';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Ready to Pick */
.status-ready-to-pick-the-purchased-product { 
    background: linear-gradient(135deg, #e8f5e8 0%, #d4edd9 100%); 
    color: #155724; 
    border-color: #a3d9a5;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
}

.status-ready-to-pick-the-purchased-product::after {
    content: '✅';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Ready */
.status-ready { 
    background: linear-gradient(135deg, #d4f8d4 0%, #b8f0b8 100%); 
    color: #0f5132; 
    border-color: #7ae97a;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.status-ready::after {
    content: '🚀';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Completed */
.status-completed { 
    background: linear-gradient(135deg, #d1f7d1 0%, #a8e6a8 100%); 
    color: #0f5132; 
    border-color: #5cb85c;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.status-completed::after {
    content: '🎉';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Canceled */
.status-canceled { 
    background: linear-gradient(135deg, #f8d7da 0%, #f5b5ba 100%); 
    color: #721c24; 
    border-color: #e6a8ab;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
}

.status-canceled::after {
    content: '❌';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Shipped */
.status-shipped { 
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); 
    color: #0d47a1; 
    border-color: #90caf9;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

.status-shipped::after {
    content: '🚚';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Delivered */
.status-delivered { 
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%); 
    color: #1b5e20; 
    border-color: #81c784;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
}

.status-delivered::after {
    content: '📬';
    margin-left: 5px;
    font-size: 0.8em;
}

/* On Hold */
.status-on-hold { 
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); 
    color: #e65100; 
    border-color: #ffb74d;
    box-shadow: 0 2px 8px rgba(255, 152, 0, 0.2);
}

.status-on-hold::after {
    content: '⏸️';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Refunded */
.status-refunded { 
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%); 
    color: #4a148c; 
    border-color: #ba68c8;
    box-shadow: 0 2px 8px rgba(156, 39, 176, 0.2);
}

.status-refunded::after {
    content: '💸';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Failed */
.status-failed { 
    background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); 
    color: #b71c1c; 
    border-color: #ef5350;
    box-shadow: 0 2px 8px rgba(244, 67, 54, 0.2);
}

.status-failed::after {
    content: '💥';
    margin-left: 5px;
    font-size: 0.8em;
}

/* Status with pulse animation for active orders */
.status-pending-payment,
.status-processing,
.status-processing-purchased-product {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
    }
}

/* Status priority indicators */
.status-badge.priority-high {
    border-left: 4px solid #dc3545;
    border-right: 4px solid #dc3545;
}

.status-badge.priority-medium {
    border-left: 4px solid #ffc107;
    border-right: 4px solid #ffc107;
}

.status-badge.priority-low {
    border-left: 4px solid #28a745;
    border-right: 4px solid #28a745;
}

/* Status in tables */
table .status-badge {
    margin: 2px 0;
    font-size: 0.8rem;
    min-width: 110px;
}

/* Status in modals */
.modal-body .status-badge {
    font-size: 0.9rem;
    min-width: 130px;
    margin: 5px 0;
}

/* Status filter styles */
.status-filter-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
}

.status-filter-option .status-badge {
    min-width: 80px;
    font-size: 0.75rem;
    padding: 4px 8px;
}

/* Status timeline in order details */
.status-timeline {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin: 20px 0;
}

.status-timeline-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.status-timeline-item.active {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4edd9 100%);
    border-left: 4px solid var(--success-green);
}

.status-timeline-item.completed {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    opacity: 0.7;
}

.status-timeline-item .status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #6c757d;
}

.status-timeline-item.active .status-indicator {
    background: var(--success-green);
    animation: bounce 1s infinite;
}

.status-timeline-item.completed .status-indicator {
    background: var(--success-green);
}

@keyframes bounce {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
}

/* Status progress bar */
.status-progress {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 15px 0;
}

.status-progress-bar {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.status-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent-green), var(--success-green));
    border-radius: 4px;
    transition: width 0.5s ease;
}

.status-progress-text {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-dark);
    min-width: 80px;
}

/* Status update history */
.status-history {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-top: 20px;
}

.status-history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.status-history-item:last-child {
    border-bottom: none;
}

.status-history-from-to {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-history-arrow {
    color: var(--text-light);
    font-weight: bold;
}

.status-history-time {
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Status quick actions */
.status-quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 15px 0;
}

.status-quick-action {
    padding: 8px 16px;
    border: 2px solid var(--border-color);
    border-radius: 6px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-quick-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.status-quick-action.next {
    border-color: var(--success-green);
    color: var(--success-green);
}

.status-quick-action.next:hover {
    background: var(--success-green);
    color: white;
}

.status-quick-action.cancel {
    border-color: var(--delete-red);
    color: var(--delete-red);
}

.status-quick-action.cancel:hover {
    background: var(--delete-red);
    color: white;
}

/* Status statistics */
.status-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.status-stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-top: 4px solid transparent;
    transition: all 0.3s ease;
}

.status-stat-card.pending {
    border-top-color: #ffc107;
}

.status-stat-card.processing {
    border-top-color: #fd7e14;
}

.status-stat-card.completed {
    border-top-color: #28a745;
}

.status-stat-card.canceled {
    border-top-color: #dc3545;
}

.status-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.status-stat-count {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.status-stat-label {
    font-size: 0.9rem;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Responsive status styles */
@media (max-width: 768px) {
    .status-badge {
        min-width: 100px;
        font-size: 0.75rem;
        padding: 6px 12px;
    }
    
    .status-timeline-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .status-quick-actions {
        flex-direction: column;
    }
    
    .status-quick-action {
        text-align: center;
    }
    
    .status-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .status-badge {
        min-width: 90px;
        font-size: 0.7rem;
        padding: 4px 8px;
    }
    
    .status-badge::after {
        display: none; /* Hide emojis on very small screens */
    }
    
    .status-stats {
        grid-template-columns: 1fr;
    }
    
    .status-stat-card {
        padding: 15px;
    }
    
    .status-stat-count {
        font-size: 1.5rem;
    }
}

/* Print styles for status */
@media print {
    .status-badge {
        border: 1px solid #000 !important;
        background: #fff !important;
        color: #000 !important;
        box-shadow: none !important;
    }
    
    .status-badge::after,
    .status-badge::before {
        display: none !important;
    }
}

/* Dark mode support for status */
@media (prefers-color-scheme: dark) {
    .status-badge {
        opacity: 0.9;
    }
    
    .status-timeline-item {
        background: #2d3748;
    }
    
    .status-stat-card {
        background: #2d3748;
        color: #e2e8f0;
    }
}

/* High contrast mode for status */
@media (prefers-contrast: high) {
    .status-badge {
        border-width: 3px;
        font-weight: 700;
    }
    
    .status-badge:hover {
        transform: none;
        border-width: 4px;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .status-badge,
    .status-badge::before,
    .status-badge:hover,
    .status-stat-card,
    .status-timeline-item {
        transition: none;
        animation: none;
    }
    
    .status-badge:hover {
        transform: none;
    }
    
    .pulse-animation {
        animation: none;
    }
}

/* Status with icons */
.status-with-icon {
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-icon {
    font-size: 1.1em;
    opacity: 0.8;
}

/* Status in compact view */
.status-compact {
    min-width: 80px !important;
    padding: 4px 8px !important;
    font-size: 0.7rem !important;
}

.status-compact::after {
    display: none;
}

/* Status group headers */
.status-group-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 10px 0;
    font-weight: 600;
    color: var(--text-dark);
}

.status-group-count {
    background: var(--accent-green);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
}
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <button class="mobile-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="overlay" id="overlay"></div>
    
    <div class="sidebar" id="sidebar">
        <h2><i class="fas fa-cogs"></i> Admin Panel</h2>
        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="user_management.php" class="active"><i class="fas fa-users-cog"></i> User Management</a>
        <a href="add_product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
        <a href="add_category.php"><i class="fas fa-tags"></i> Add Category</a>
        <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="../auth/admin_logout.php" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p style="color: var(--text-light); margin-top: 8px; font-size: 1.1rem;">
                Manage users, orders, and monitor platform activity
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $users_total; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $orders_total; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    ₱<?php 
                    $total_revenue_stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders");
                    $total_revenue = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    echo number_format($total_revenue, 2); 
                    ?>
                </div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $active_users_stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM orders");
                    $active_users = $active_users_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    echo $active_users;
                    ?>
                </div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Section -->
        <div class="search-filter">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search users or orders...">
                <i class="fas fa-search"></i>
            </div>
            <select class="filter-select" id="statusFilter">
                <option value="">All Statuses</option>
                <option value="pending_payment">Pending Payment</option>
                <option value="paid">Paid</option>
                <option value="processing">Processing</option>
                <option value="ready">Ready</option>
                <option value="completed">Completed</option>
                <option value="canceled">Canceled</option>
            </select>
            <select class="filter-select" id="membershipFilter">
                <option value="">All Memberships</option>
                <option value="regular">Regular</option>
                <option value="sidc_member">SIDC Member</option>
                <option value="non_member">Non Member</option>
            </select>
        </div>

        <div class="tabs">
            <button class="tab-button active" onclick="openTab(event, 'users-tab')">
                <i class="fas fa-users"></i> Users (<?php echo $users_total; ?>)
            </button>
            <button class="tab-button" onclick="openTab(event, 'orders-tab')">
                <i class="fas fa-shopping-cart"></i> Orders (<?php echo $orders_total; ?>)
            </button>
        </div>

        <!-- Users Tab -->
        <div id="users-tab" class="tab-content active">
            <div class="table-container">
                <?php if (!empty($users)): ?>
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Membership</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Points</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr data-user-id="<?php echo $user['user_id']; ?>" 
                                    data-membership="<?php echo $user['membership_type']; ?>">
                                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="membership-badge membership-<?php echo $user['membership_type']; ?>">
                                            <i class="fas fa-<?php echo $user['membership_type'] === 'sidc_member' ? 'crown' : 'user'; ?>"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $user['membership_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="detail-value"><?php echo $user['order_count']; ?></span>
                                    </td>
                                    <td>
                                        <strong>₱<?php echo number_format($user['total_spent'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="detail-value"><?php echo $user['points']; ?></span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-primary view-user-btn" data-user-id="<?php echo $user['user_id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this user and all their data? This action cannot be undone.');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Users Pagination -->
                    <div class="pagination">
                        <?php if ($users_page > 1): ?>
                            <a href="?users_page=1&orders_page=<?php echo $orders_page; ?>" class="page-btn">
                                <i class="fas fa-angle-double-left"></i> First
                            </a>
                            <a href="?users_page=<?php echo $users_page - 1; ?>&orders_page=<?php echo $orders_page; ?>" class="page-btn">
                                <i class="fas fa-angle-left"></i> Prev
                            </a>
                        <?php else: ?>
                            <span class="page-btn disabled"><i class="fas fa-angle-double-left"></i> First</span>
                            <span class="page-btn disabled"><i class="fas fa-angle-left"></i> Prev</span>
                        <?php endif; ?>

                        <span class="pagination-info">
                            Page <?php echo $users_page; ?> of <?php echo $users_total_pages; ?>
                        </span>

                        <?php if ($users_page < $users_total_pages): ?>
                            <a href="?users_page=<?php echo $users_page + 1; ?>&orders_page=<?php echo $orders_page; ?>" class="page-btn">
                                Next <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?users_page=<?php echo $users_total_pages; ?>&orders_page=<?php echo $orders_page; ?>" class="page-btn">
                                Last <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-btn disabled">Next <i class="fas fa-angle-right"></i></span>
                            <span class="page-btn disabled">Last <i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-users-slash"></i>
                        <p>No users found in the system.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Orders Tab -->
        <div id="orders-tab" class="tab-content">
            <div class="table-container">
                <?php if (!empty($orders)): ?>
                    <table id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Receipt</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr data-status="<?php echo $order['status']; ?>"
                                    data-customer="<?php echo htmlspecialchars(strtolower($order['full_name'])); ?>">
                                    <td><strong>#<?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['full_name']); ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--text-light);">
                                                <?php echo htmlspecialchars($order['email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="detail-value"><?php echo $order['item_count']; ?> items</span>
                                    </td>
                                    <td>
                                        <strong>₱<?php echo number_format($order['total_price'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $order['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($order['receipt_image'])): ?>
                                            <?php 
                                            $receipt_filename = basename($order['receipt_image']);
                                            $receipt_path = '../uploads/receipts/' . htmlspecialchars($receipt_filename);
                                            ?>
                                            <img src="<?php echo $receipt_path; ?>" 
                                                 alt="Receipt" 
                                                 class="receipt-image" 
                                                 onclick="showReceipt('<?php echo $receipt_path; ?>')"
                                                 onerror="this.style.display='none'">
                                        <?php else: ?>
                                            <span style="color: var(--text-light); font-style: italic;">
                                                <i class="fas fa-receipt"></i> No receipt
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-primary view-order-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button type="button" onclick="openStatusModal(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')" 
                                                    class="btn btn-warning">
                                                <i class="fas fa-edit"></i> Status
                                            </button>
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this order? This action cannot be undone.');">
                                                <input type="hidden" name="action" value="delete_order">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Orders Pagination -->
                    <div class="pagination">
                        <?php if ($orders_page > 1): ?>
                            <a href="?users_page=<?php echo $users_page; ?>&orders_page=1" class="page-btn">
                                <i class="fas fa-angle-double-left"></i> First
                            </a>
                            <a href="?users_page=<?php echo $users_page; ?>&orders_page=<?php echo $orders_page - 1; ?>" class="page-btn">
                                <i class="fas fa-angle-left"></i> Prev
                            </a>
                        <?php else: ?>
                            <span class="page-btn disabled"><i class="fas fa-angle-double-left"></i> First</span>
                            <span class="page-btn disabled"><i class="fas fa-angle-left"></i> Prev</span>
                        <?php endif; ?>

                        <span class="pagination-info">
                            Page <?php echo $orders_page; ?> of <?php echo $orders_total_pages; ?>
                        </span>

                        <?php if ($orders_page < $orders_total_pages): ?>
                            <a href="?users_page=<?php echo $users_page; ?>&orders_page=<?php echo $orders_page + 1; ?>" class="page-btn">
                                Next <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?users_page=<?php echo $users_page; ?>&orders_page=<?php echo $orders_total_pages; ?>" class="page-btn">
                                Last <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-btn disabled">Next <i class="fas fa-angle-right"></i></span>
                            <span class="page-btn disabled">Last <i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No orders found in the system.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> User Details</h2>
                <button type="button" class="close" onclick="closeModal('userModal')">&times;</button>
            </div>
            <div class="modal-body" id="userModalBody">
                <!-- User details will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Order Details</h2>
                <button type="button" class="close" onclick="closeModal('orderModal')">&times;</button>
            </div>
            <div class="modal-body" id="orderModalBody">
                <!-- Order details will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Order Status</h2>
                <button type="button" class="close" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" id="statusForm">
                    <input type="hidden" name="action" value="update_order_status">
                    <input type="hidden" name="order_id" id="modalOrderId">
                    
                    <div class="form-group">
                        <label>Current Status:</label>
                        <div id="currentStatus" class="detail-value" style="padding: 10px; background: #f8f9fa; border-radius: 6px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalNewStatus">Select New Status:</label>
                        <select name="new_status" id="modalNewStatus" required>
                            <option value="pending_payment">Pending Payment</option>
                            <option value="paid">Paid</option>
                            <option value="processing_purchased_product">Processing Purchased Product</option>
                            <option value="ready_to_pick_the_purchased_product">Ready to Pick</option>
                            <option value="completed">Completed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    
                    <div style="margin-top: 25px; display: flex; gap: 15px;">
                        <button type="submit" class="btn btn-warning" style="flex: 1;">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                        <button type="button" class="btn btn-primary" onclick="closeModal('statusModal')" style="flex: 1;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Receipt Image Modal -->
    <div id="receiptModal" class="receipt-modal">
        <span class="receipt-close" onclick="closeReceiptModal()">&times;</span>
        <img class="receipt-modal-content" id="receiptModalImg">
    </div>

    <script>
        // Enhanced JavaScript functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('overlay');
            
            // Toggle sidebar on button click
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            });
            
            // Close sidebar when clicking on overlay
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Close sidebar when clicking on a link (on mobile)
            if (window.innerWidth <= 768) {
                const sidebarLinks = document.querySelectorAll('.sidebar a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            // Initialize search and filter functionality
            initializeSearchAndFilter();
            
            // Add event listeners for view buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('view-user-btn') || e.target.closest('.view-user-btn')) {
                    const btn = e.target.classList.contains('view-user-btn') ? e.target : e.target.closest('.view-user-btn');
                    const userId = btn.getAttribute('data-user-id');
                    viewUser(userId);
                }
                
                if (e.target.classList.contains('view-order-btn') || e.target.closest('.view-order-btn')) {
                    const btn = e.target.classList.contains('view-order-btn') ? e.target : e.target.closest('.view-order-btn');
                    const orderId = btn.getAttribute('data-order-id');
                    viewOrder(orderId);
                }
            });
            
            // Add loading states to buttons
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn && !this.classList.contains('no-loading')) {
                        submitBtn.innerHTML = '<div class="loading"></div> Processing...';
                        submitBtn.disabled = true;
                    }
                });
            });

            // Add event listeners for dynamically loaded view buttons in modals
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('view-order-btn') || e.target.closest('.view-order-btn')) {
                    const btn = e.target.classList.contains('view-order-btn') ? e.target : e.target.closest('.view-order-btn');
                    const orderId = btn.getAttribute('data-order-id');
                    closeModal('userModal');
                    setTimeout(() => viewOrder(orderId), 300);
                }
            });
        });

        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
            
            // Reinitialize search and filter for the active tab
            setTimeout(initializeSearchAndFilter, 100);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = "none";
            modal.classList.remove('show');
        }

        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = "block";
            modal.classList.add('show');
        }

        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            const statusText = currentStatus.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            document.getElementById('currentStatus').innerHTML = 
                '<span class="status-badge status-' + currentStatus.replace(/_/g, '-') + '">' + statusText + '</span>';
            document.getElementById('modalNewStatus').value = currentStatus;
            showModal('statusModal');
        }

        function showReceipt(imageSrc) {
            document.getElementById('receiptModal').style.display = 'block';
            document.getElementById('receiptModalImg').src = imageSrc;
            document.body.style.overflow = 'hidden';
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // AJAX functions for viewing user and order details
        function viewUser(userId) {
            const modalBody = document.getElementById('userModalBody');
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading"></div><p>Loading user details...</p></div>';
            
            showModal('userModal');
            
            fetch(`?view_user=${userId}&ajax=1`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    modalBody.innerHTML = data;
                    // Re-initialize any event listeners for buttons in the modal
                    initializeModalEvents();
                })
                .catch(error => {
                    modalBody.innerHTML = '<div class="alert alert-error">Error loading user details. Please try again.</div>';
                    console.error('Error:', error);
                });
        }

        function viewOrder(orderId) {
            const modalBody = document.getElementById('orderModalBody');
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading"></div><p>Loading order details...</p></div>';
            
            showModal('orderModal');
            
            fetch(`?view_order=${orderId}&ajax=1`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    modalBody.innerHTML = data;
                    // Re-initialize any event listeners for buttons in the modal
                    initializeModalEvents();
                })
                .catch(error => {
                    modalBody.innerHTML = '<div class="alert alert-error">Error loading order details. Please try again.</div>';
                    console.error('Error:', error);
                });
        }

        function initializeModalEvents() {
            // Add event listeners for any buttons in the modal
            document.querySelectorAll('#orderModalBody .view-order-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    closeModal('orderModal');
                    setTimeout(() => viewOrder(orderId), 300);
                });
            });

            // Prevent form submission from closing modal
            document.querySelectorAll('#orderModalBody form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<div class="loading"></div> Updating...';
                        submitBtn.disabled = true;
                    }
                    // Form will submit normally and refresh page with success message
                });
            });
        }

        // Enhanced search and filter functionality
        function initializeSearchAndFilter() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const membershipFilter = document.getElementById('membershipFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterTables);
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', filterTables);
            }
            if (membershipFilter) {
                membershipFilter.addEventListener('change', filterTables);
            }
        }

        function filterTables() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const membershipFilter = document.getElementById('membershipFilter').value;
            
            // Filter users table
            const userRows = document.querySelectorAll('#usersTable tbody tr');
            userRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const membership = row.getAttribute('data-membership');
                
                const matchesSearch = text.includes(searchTerm);
                const matchesMembership = !membershipFilter || membership === membershipFilter;
                
                row.style.display = (matchesSearch && matchesMembership) ? '' : 'none';
            });
            
            // Filter orders table
            const orderRows = document.querySelectorAll('#ordersTable tbody tr');
            orderRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                const customer = row.getAttribute('data-customer');
                
                const matchesSearch = text.includes(searchTerm) || customer.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modals = document.getElementsByClassName('modal');
            for (var i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = "none";
                    modals[i].classList.remove('show');
                }
            }
            
            // Close receipt modal when clicking outside the image
            if (event.target == document.getElementById('receiptModal')) {
                closeReceiptModal();
            }
        }

        // Auto-close success/error messages after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // Handle keyboard navigation for modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Close any open modals
                var modals = document.querySelectorAll('.modal');
                modals.forEach(function(modal) {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                    }
                });
                
                // Close receipt modal
                var receiptModal = document.getElementById('receiptModal');
                if (receiptModal.style.display === 'block') {
                    closeReceiptModal();
                }
                
                // Close sidebar on mobile
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('overlay').classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });

        // Enhanced error handling for image loading
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                if (this.classList.contains('receipt-image')) {
                    this.style.display = 'none';
                    const parent = this.parentElement;
                    const errorSpan = document.createElement('span');
                    errorSpan.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Image not found';
                    errorSpan.style.color = 'var(--warning-orange)';
                    errorSpan.style.fontStyle = 'italic';
                    parent.appendChild(errorSpan);
                }
            });
        });

        // Add smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add some visual feedback for actions
        document.addEventListener('click', function(e) {
            if (e.target.matches('.btn') || e.target.closest('.btn')) {
                const btn = e.target.matches('.btn') ? e.target : e.target.closest('.btn');
                if (!btn.disabled) {
                    btn.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        btn.style.transform = '';
                    }, 150);
                }
            }
        });

        // Initialize tooltips for better UX
        function initTooltips() {
            const elements = document.querySelectorAll('[title]');
            elements.forEach(el => {
                el.addEventListener('mouseenter', function(e) {
                    // Tooltip implementation would go here
                });
            });
        }

        // Call initialization functions
        initTooltips();
    </script>
</body>
</html>