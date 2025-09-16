<?php
// File: user_notifications.php
session_start();

// Include database connection
include 'components/connect.php';

// Notification Class (OOP Approach)
class Notification {
    private $conn;
    private $table_name = "notifications";
    
    public $id;
    public $user_id;
    public $order_id;
    public $message;
    public $type;
    public $is_read;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get all notifications for a user
    public function getUserNotifications($user_id) {
        $query = "SELECT n.*, o.total_price, o.method, o.status as order_status 
                  FROM " . $this->table_name . " n 
                  LEFT JOIN orders o ON n.order_id = o.id 
                  WHERE n.user_id = ? 
                  ORDER BY n.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get the latest order status directly from the orders table
    public function getCurrentOrderStatus($order_id) {
        if (empty($order_id)) return null;
        
        $query = "SELECT status, method FROM orders WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $order_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row;
        }
        
        return null;
    }
    
    // Count unread notifications for a user
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = ? AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'];
    }
    
    // Mark notification as read
    public function markAsRead($notification_id, $user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $notification_id);
        $stmt->bindParam(2, $user_id);
        
        return $stmt->execute();
    }
    
    // Mark all notifications as read
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE user_id = ? AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        
        return $stmt->execute();
    }
    
    // Delete a notification
    public function delete($notification_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $notification_id);
        $stmt->bindParam(2, $user_id);
        
        return $stmt->execute();
    }
    
    // Delete all notifications for a user
    public function deleteAll($user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        
        return $stmt->execute();
    }
}

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header('location:user_login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Generate a unique token for form submission (CSRF protection)
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Initialize Notification object
$notification = new Notification($conn);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form token to prevent CSRF and duplicate submissions
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        $_SESSION['message'] = 'Invalid form submission';
        header('location:user_notifications.php');
        exit();
    }
    
    // Regenerate token after successful validation
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
    
    $action_performed = false;
    
    if (isset($_POST['mark_as_read']) && !empty($_POST['mark_as_read'])) {
        $notification_id = filter_var($_POST['mark_as_read'], FILTER_SANITIZE_NUMBER_INT);
        if ($notification->markAsRead($notification_id, $user_id)) {
            $_SESSION['message'] = 'Notification marked as read';
        } else {
            $_SESSION['message'] = 'Error marking notification as read';
        }
        $action_performed = true;
    }
    
    if (isset($_POST['delete']) && !empty($_POST['delete'])) {
        $notification_id = filter_var($_POST['delete'], FILTER_SANITIZE_NUMBER_INT);
        if ($notification->delete($notification_id, $user_id)) {
            $_SESSION['message'] = 'Notification deleted successfully';
        } else {
            $_SESSION['message'] = 'Error deleting notification';
        }
        $action_performed = true;
    }
    
    if (isset($_POST['mark_all_read'])) {
        if ($notification->markAllAsRead($user_id)) {
            $_SESSION['message'] = 'All notifications marked as read';
        } else {
            $_SESSION['message'] = 'Error marking notifications as read';
        }
        $action_performed = true;
    }
    
    if (isset($_POST['delete_all'])) {
        if ($notification->deleteAll($user_id)) {
            $_SESSION['message'] = 'All notifications deleted';
        } else {
            $_SESSION['message'] = 'Error deleting notifications';
        }
        $action_performed = true;
    }
    
    // Only redirect if an action was performed
    if ($action_performed) {
        header('location:user_notifications.php');
        exit();
    }
}

// Fetch notifications
$stmt = $notification->getUserNotifications($user_id);
$notifications = $stmt->fetchAll(PDO::FETCH_OBJ);

// For each notification with an order ID, get the latest status
foreach ($notifications as $notif) {
    if (!empty($notif->order_id)) {
        $current_data = $notification->getCurrentOrderStatus($notif->order_id);
        if ($current_data !== null) {
            // Update the status and method with current values from orders table
            $notif->order_status = $current_data['status'];
            $notif->method = $current_data['method'];
        }
    }
}

// Count unread notifications
$unread_count = $notification->getUnreadCount($user_id);

// Display any messages
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Notifications</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        
        .notification-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 0;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .notification-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--primary);
        }
        
        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }
        
        .notification-card.unread {
            border-left: 4px solid var(--info);
            background-color: rgba(72, 149, 239, 0.05);
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .notification-order {
            border-left-color: #4361ee;
        }
        
        .notification-order .notification-icon {
            background-color: rgba(67, 97, 238, 0.15);
            color: #4361ee;
        }
        
        .notification-promotion {
            border-left-color: #ff9500;
        }
        
        .notification-promotion .notification-icon {
            background-color: rgba(255, 149, 0, 0.15);
            color: #ff9500;
        }
        
        .notification-system {
            border-left-color: #34c759;
        }
        
        .notification-system .notification-icon {
            background-color: rgba(52, 199, 89, 0.15);
            color: #34c759;
        }
        
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .badge-unread {
            background-color: var(--info);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .filter-buttons .btn {
            border-radius: 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            padding: 8px 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .mark-all-read {
            cursor: pointer;
            color: var(--info);
            font-weight: 500;
        }
        
        .mark-all-read:hover {
            color: var(--primary);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .action-btn:hover {
            color: var(--primary);
        }
        
        .notification-type-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .type-order {
            background-color: rgba(67, 97, 238, 0.15);
            color: #4361ee;
        }
        
        .type-promotion {
            background-color: rgba(255, 149, 0, 0.15);
            color: #ff9500;
        }
        
        .type-system {
            background-color: rgba(52, 199, 89, 0.15);
            color: #34c759;
        }
        
        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .order-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .floating-action {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .status-updated {
            animation: highlight 2s ease;
        }
        
        @keyframes highlight {
            from { background-color: rgba(76, 201, 240, 0.3); }
            to { background-color: transparent; }
        }
        
        .payment-badge {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <!-- Message Alert -->
    <?php if(!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show alert-message" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <a href="home.php"  style="color: yellow;"><i class="fas fa-bell me-2" ></i>Notifications</a>
                </h1>
                <div>
                    <?php if($unread_count > 0): ?>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                        <button type="submit" name="mark_all_read" class="mark-all-read btn btn-link p-0 text-white text-decoration-none">
                            <i class="fas fa-check-double me-1"></i> Mark all as read
                        </button>
                    </form>
                    <span class="badge bg-light text-dark ms-2"><?= $unread_count ?> unread</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container notification-container">
        <!-- Filter Buttons -->
        <div class="filter-buttons mb-4">
            <button class="btn btn-outline-primary active filter-btn" data-filter="all">All</button>
            <button class="btn btn-outline-primary filter-btn" data-filter="unread">Unread</button>
    
        </div>

        <!-- Notifications List -->
        <div class="notifications-list">
            <?php if(count($notifications) > 0): ?>
                <?php foreach($notifications as $notif): 
                    $is_unread = !$notif->is_read;
                    $type_class = 'notification-' . $notif->type;
                    
                    // Determine badge color based on status
                    $status_class = 'secondary';
                    if ($notif->order_status == 'completed') $status_class = 'success';
                    if ($notif->order_status == 'pending') $status_class = 'warning';
                    if ($notif->order_status == 'cancelled') $status_class = 'danger';
                    if ($notif->order_status == 'processing') $status_class = 'info';
                    
                    // Format payment method for display
                    $payment_method = '';
                    if (!empty($notif->method)) {
                        $payment_method = ucfirst(str_replace('_', ' ', $notif->method));
                        if ($payment_method == 'Esewa') {
                            $payment_method = 'eSewa';
                        } elseif ($payment_method == 'Cod') {
                            $payment_method = 'Cash on Delivery';
                        }
                    }
                ?>
                <div class="notification-card <?= $type_class ?> <?= $is_unread ? 'unread' : '' ?> notification-item" 
                     data-type="<?= $notif->type ?>" 
                     data-read="<?= $is_unread ? 'unread' : 'read' ?>"
                     id="notification-<?= $notif->id ?>">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="notification-icon">
                                <?php 
                                $icon = 'fa-info-circle';
                                if($notif->type == 'order') $icon = 'fa-shopping-cart';
                                if($notif->type == 'promotion') $icon = 'fa-tag';
                                if($notif->type == 'system') $icon = 'fa-cog';
                                ?>
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <div class="notification-content">
                                <h5 class="card-title d-flex align-items-center">
                                    <?= htmlspecialchars($notif->message) ?>
                                    <span class="notification-type-badge type-<?= $notif->type ?>">
                                        <?= ucfirst($notif->type) ?>
                                    </span>
                                    <?php if($is_unread): ?>
                                    <span class="badge-unread ms-2">Unread</span>
                                    <?php endif; ?>
                                </h5>
                                
                                <?php if(!empty($notif->order_id)): ?>
                                <div class="order-details">
                                    <strong>Order #<?= htmlspecialchars($notif->order_id) ?></strong>
                                    <?php if(!empty($notif->total_price)): ?>
                                    <div>Total: ₹<?= number_format($notif->total_price, 2) ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($payment_method)): ?>
                                    <div>Payment Method: <?= $payment_method ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($notif->order_status)): ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="notification-time">
                                        <i class="fas fa-clock me-1"></i> 
                                        <?= date('M d, Y h:i A', strtotime($notif->created_at)) ?>
                                    </small>
                                    <div class="notification-actions">
                                        <?php if($is_unread): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                                            <input type="hidden" name="mark_as_read" value="<?= $notif->id ?>">
                                            <button type="submit" class="action-btn mark-as-read">
                                                <i class="fas fa-envelope-open me-1"></i> Mark as read
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                            <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                                            <input type="hidden" name="delete" value="<?= $notif->id ?>">
                                            <button type="submit" class="action-btn">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Delete All Button -->
                <div class="text-end mt-4">
                    <form method="post" onsubmit="return confirm('Are you sure you want to delete all notifications?');">
                        <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                        <button type="submit" name="delete_all" class="btn btn-outline-danger">
                            <i class="fas fa-trash-alt me-1"></i> Delete All Notifications
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications yet</h3>
                    <p>When you have notifications, they'll appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="floating-action">
        <div class="btn-group-vertical shadow">
            <form method="post" style="display: inline;">
                <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                <button type="submit" name="mark_all_read" class="btn btn-info text-white" title="Mark all as read">
                    <i class="fas fa-check-double"></i>
                </button>
            </form>
            <form method="post" onsubmit="return confirm('Are you sure you want to delete all notifications?');" style="display: inline;">
                <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                <button type="submit" name="delete_all" class="btn btn-danger" title="Delete all notifications">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter buttons functionality
            const filterButtons = document.querySelectorAll('.filter-btn');
            const notificationItems = document.querySelectorAll('.notification-item');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const filterType = this.getAttribute('data-filter');
                    
                    notificationItems.forEach(item => {
                        const itemType = item.getAttribute('data-type');
                        const itemRead = item.getAttribute('data-read');
                        
                        if (filterType === 'all') {
                            item.style.display = 'block';
                        } else if (filterType === 'unread') {
                            if (itemRead === 'unread') {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        } else {
                            if (itemType === filterType) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        }
                    });
                });
            });
            
            // Auto-close alert message after 5 seconds
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            }
        });
    </script>
</body>
</html>