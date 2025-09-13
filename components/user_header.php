<?php
// messages.php - Reusable message component (OOP Approach)

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'components/connect.php';

/**
 * MessageManager class for handling flash messages
 */
class MessageManager {
    private static $instance = null;
    private $messages = [];
    
    private function __construct() {
        // Initialize message array
        $this->messages = [];
        
        // Check for session messages
        if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
            $this->messages = (array)$_SESSION['messages'];
            unset($_SESSION['messages']); // Clear after retrieving
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MessageManager();
        }
        return self::$instance;
    }
    
    public function addMessage($text, $type = 'info') {
        if (!isset($_SESSION['messages'])) {
            $_SESSION['messages'] = [];
        }
        $_SESSION['messages'][] = [
            'text' => $text,
            'type' => $type
        ];
    }
    
    public function getMessages() {
        return $this->messages;
    }
    
    public function hasMessages() {
        return !empty($this->messages);
    }
}

/**
 * UserNotificationManager class for handling user notifications
 */
class UserNotificationManager {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function getUnreadCount($user_id) {
        try {
            $count_notifications = $this->conn->prepare(
                "SELECT COUNT(*) as count FROM `notifications` WHERE user_id = ? AND is_read = 0"
            );
            $count_notifications->execute([$user_id]);
            $result = $count_notifications->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['count'] : 0;
        } catch(PDOException $e) {
            error_log("Error counting unread notifications: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getRecentNotifications($user_id, $limit = 5) {
        try {
            $select_notifications = $this->conn->prepare(
                "SELECT * FROM `notifications` WHERE user_id = ? 
                 ORDER BY created_at DESC LIMIT ?"
            );
            $select_notifications->execute([$user_id, $limit]);
            return $select_notifications->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            return [];
        }
    }
    
    public function addNotification($user_id, $message, $type = 'info') {
        try {
            $insert_notification = $this->conn->prepare(
                "INSERT INTO `notifications` (user_id, message, type, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );
            return $insert_notification->execute([$user_id, $message, $type]);
        } catch(PDOException $e) {
            error_log("Error adding notification: " . $e->getMessage());
            return false;
        }
    }
    
    public function hasNotification($user_id, $message) {
        try {
            $check_notification = $this->conn->prepare(
                "SELECT COUNT(*) as count FROM `notifications` WHERE user_id = ? AND message = ?"
            );
            $check_notification->execute([$user_id, $message]);
            $result = $check_notification->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch(PDOException $e) {
            error_log("Error checking notification: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * UserManager class for handling user-related operations
 */
class UserManager {
    private $conn;
    private $user_id;
    private $user_data;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->user_data = null;
        
        if ($this->isLoggedIn()) {
            $this->loadUserData();
        }
    }
    
    public function isLoggedIn() {
        return $this->user_id !== null;
    }
    
    public function getUserId() {
        return $this->user_id;
    }
    
    public function getUserData() {
        return $this->user_data;
    }
    
    private function loadUserData() {
        try {
            $select_profile = $this->conn->prepare("SELECT * FROM `users` WHERE id = ?");
            $select_profile->execute([$this->user_id]);
            
            if ($select_profile->rowCount() > 0) {
                $this->user_data = $select_profile->fetch(PDO::FETCH_ASSOC);
            } else {
                // User ID exists in session but not in database
                $this->logout();
                header('Location: user_login.php');
                exit();
            }
        } catch(PDOException $e) {
            error_log("Error loading user data: " . $e->getMessage());
            $this->user_data = null;
        }
    }
    
    public function logout() {
        session_destroy();
        $this->user_id = null;
        $this->user_data = null;
    }
    
    public function getWishlistCount() {
        if (!$this->isLoggedIn()) return 0;
        
        try {
            $count_wishlist_items = $this->conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
            $count_wishlist_items->execute([$this->user_id]);
            return $count_wishlist_items->rowCount();
        } catch(PDOException $e) {
            error_log("Error counting wishlist items: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getCartCount() {
        if (!$this->isLoggedIn()) return 0;
        
        try {
            $count_cart_items = $this->conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $count_cart_items->execute([$this->user_id]);
            return $count_cart_items->rowCount();
        } catch(PDOException $e) {
            error_log("Error counting cart items: " . $e->getMessage());
            return 0;
        }
    }
}

/**
 * HeaderRenderer class for rendering the header component
 */
class HeaderRenderer {
    private $messageManager;
    private $notificationManager;
    private $userManager;
    
    public function __construct($messageManager, $notificationManager, $userManager) {
        $this->messageManager = $messageManager;
        $this->notificationManager = $notificationManager;
        $this->userManager = $userManager;
    }
    
    public function renderMessages() {
        if (!$this->messageManager->hasMessages()) return;
        
        $messages = $this->messageManager->getMessages();
        ob_start();
        ?>
        <div class="messages-container">
            <?php foreach ($messages as $msg): ?>
                <div class="message <?= htmlspecialchars($msg['type'], ENT_QUOTES) ?>">
                    <span><?= htmlspecialchars($msg['text'], ENT_QUOTES) ?></span>
                    <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        echo ob_get_clean();
    }
    
    public function renderHeader() {
        $unread_count = 0;
        $recent_notifications = [];
        
        if ($this->userManager->isLoggedIn()) {
            $unread_count = $this->notificationManager->getUnreadCount($this->userManager->getUserId());
            $recent_notifications = $this->notificationManager->getRecentNotifications($this->userManager->getUserId(), 5);
            
            // Add sample notifications only if they don't exist
            if (empty($recent_notifications)) {
                $welcome_message = "Welcome to Nepal Store! Enjoy your shopping experience.";
                $order_message = "Your order #12345 has been confirmed and is being processed.";
                
                // Check if notifications already exist before adding them
                if (!$this->notificationManager->hasNotification($this->userManager->getUserId(), $welcome_message)) {
                    $this->notificationManager->addNotification($this->userManager->getUserId(), $welcome_message, 'info');
                }
                
                if (!$this->notificationManager->hasNotification($this->userManager->getUserId(), $order_message)) {
                    $this->notificationManager->addNotification($this->userManager->getUserId(), $order_message, 'success');
                }
                
                // Refresh the notifications
                $recent_notifications = $this->notificationManager->getRecentNotifications($this->userManager->getUserId(), 5);
                $unread_count = $this->notificationManager->getUnreadCount($this->userManager->getUserId());
            }
        }
        
        $total_wishlist_counts = $this->userManager->getWishlistCount();
        $total_cart_counts = $this->userManager->getCartCount();
        
        ob_start();
        ?>
        <header class="header">
            <section class="flex">
                <a href="home.php" class="logo">
                    <i class="fas fa-store"></i>
                    <span>Nepal~Store</span>
                </a>

                <nav class="navbar">
                    <a href="home.php" class="nav-link ">Home</a>
                    <a href="about.php" class="nav-link ">About</a>
                    <a href="orders.php" class="nav-link ">Orders</a>
                    <a href="shop.php" class="nav-link ">Shop</a>
                    <a href="contact.php" class="nav-link ">Contact</a>
                </nav>

                <div class="icons">
                    <div id="menu-btn" class="fas fa-bars hamburger"></div>
                    <a href="search_page.php" class="icon-link" title="Search"><i class="fas fa-search"></i></a>
                    <a href="wishlist.php" class="icon-link" title="Wishlist">
                        <i class="fas fa-heart"></i>
                        <span class="badge"><?= $total_wishlist_counts; ?></span>
                    </a>
                    <a href="cart.php" class="icon-link" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge"><?= $total_cart_counts; ?></span>
                    </a>
                    <a href="user_notifications.php" class="icon-link" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    <div id="user-btn" class="fas fa-user user-icon"></div>
                </div>

                <div class="profile-card">
                    <?php if ($this->userManager->isLoggedIn()): 
                        $user_data = $this->userManager->getUserData();
                    ?>
                    <div class="profile-header">
                        <div class="avatar">
                            <?= substr($user_data["name"], 0, 1); ?>
                        </div>
                        <p class="username"><?= $user_data["name"]; ?></p>
                    </div>
                    <a href="update_user.php" class="profile-btn"><i class="fas fa-user-edit"></i> Update Profile</a>
                   
                    <a href="user_notifications.php" class="profile-btn">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-indicator"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>

                    
                    <div class="flex-btn">
                        <a href="user_register.php" class="option-btn"><i class="fas fa-user-plus"></i> Register</a>
                        <a href="user_login.php" class="option-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <a href="components/user_logout.php" class="logout-btn" onclick="return confirm('Logout from the website?');">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a> 
                    <?php else: ?>
                    <p class="login-prompt">Please login or register first!</p>
                    <div class="flex-btn">
                        <a href="user_register.php" class="option-btn gradient-btn"><i class="fas fa-user-plus"></i> Register</a>
                        <a href="user_login.php" class="option-btn gradient-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <?php endif; ?>      
                </div>
            </section>
        </header>
        <?php
        echo ob_get_clean();
    }
}

// Initialize managers
$messageManager = MessageManager::getInstance();
$notificationManager = new UserNotificationManager($conn);
$userManager = new UserManager($conn);
$headerRenderer = new HeaderRenderer($messageManager, $notificationManager, $userManager);

// Global function for adding messages (backward compatibility)
function addMessage($text, $type = 'info') {
    $messageManager = MessageManager::getInstance();
    $messageManager->addMessage($text, $type);
}

// Render the HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal~Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    /* Message Styles */
    .messages-container {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .message {
        padding: 15px 25px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(0);
        opacity: 1;
        transition: all 0.5s ease;
        max-width: 300px;
        word-wrap: break-word;
    }
    
    .message.info {
        background: #3498db;
        color: white;
    }
    
    .message.success {
        background: #2ecc71;
        color: white;
    }
    
    .message.error {
        background: #e74c3c;
        color: white;
    }
    
    .message.warning {
        background: #f39c12;
        color: #333;
    }
    
    .message span {
        margin-right: 15px;
    }
    
    .message i {
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .message i:hover {
        transform: scale(1.2);
    }
    
    /* Header Styles */
    .header {
        background: linear-gradient(135deg, rgb(247, 61, 24), rgb(123, 96, 128));
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 1rem 0;
    }
    
    .header .flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        position: relative;
    }
    
    .logo {
        font-size: 1.8rem;
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .logo span {
        color: #f39c12;
        font-weight: bold;
    }
    
    .navbar {
        display: flex;
        gap: 20px;
    }
    
    .nav-link {
        color: white;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        position: relative;
    }
    
    .nav-link:hover {
        color: #f39c12;
    }
    
    .nav-link.hover-underline::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 0;
        height: 2px;
        background: #f39c12;
        transition: width 0.3s;
    }
    
    .nav-link.hover-underline:hover::after {
        width: 100%;
    }
    
    .icons {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .icon-link {
        color: white;
        font-size: 1.2rem;
        position: relative;
        transition: transform 0.3s;
        text-decoration: none;
    }
    
    .icon-link:hover {
        transform: translateY(-3px);
        color: #f39c12;
    }
    
    .badge {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #e74c3c;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
    }
    
    .user-icon {
        cursor: pointer;
        transition: all 0.3s;
        font-size: 1.2rem;
        color: white;
    }
    
    .user-icon:hover {
        color: #f39c12;
        transform: scale(1.1);
    }
    
    .profile-card {
        position: absolute;
        right: 2rem;
        top: 100%;
        background: white;
        border-radius: 10px;
        padding: 20px;
        width: 250px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s;
        z-index: 1001;
    }
    
    .profile-card.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .avatar {
        width: 40px;
        height: 40px;
        background: #3498db;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        font-weight: bold;
        font-size: 1.2rem;
    }
    
    .username {
        margin: 0;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .profile-btn, .logout-btn {
        display: block;
        width: 100%;
        padding: 10px;
        margin: 5px 0;
        border-radius: 5px;
        text-align: left;
        transition: all 0.3s;
        text-decoration: none;
        color: #2c3e50;
    }
    
    .profile-btn {
        background: #f8f9fa;
        position: relative;
    }
    
    .profile-btn:hover {
        background: #e9ecef;
    }
    
    .logout-btn {
        background: #f8f9fa;
        color: #e74c3c;
    }
    
    .logout-btn:hover {
        background: #fdecea;
    }
    
    .flex-btn {
        display: flex;
        gap: 10px;
        margin: 15px 0;
    }
    
    .option-btn {
        flex: 1;
        padding: 8px;
        border-radius: 5px;
        text-align: center;
        transition: all 0.3s;
        text-decoration: none;
        font-size: 0.9rem;
    }
    
    .gradient-btn {
        background: linear-gradient(to right, #6a11cb, #2575fc);
        color: white;
    }
    
    .gradient-btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    
    .login-prompt {
        text-align: center;
        color: #7f8c8d;
        margin-bottom: 15px;
    }
    
    .hamburger {
        display: none;
        cursor: pointer;
        font-size: 1.5rem;
        color: white;
    }
    
    .notification-indicator {
        display: inline-block;
        background: #e74c3c;
        color: white;
        border-radius: 10px;
        padding: 2px 8px;
        font-size: 0.8rem;
        margin-left: 5px;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .navbar {
            position: fixed;
            top: 70px;
            left: -100%;
            background: linear-gradient(135deg, rgb(247, 61, 24), rgb(123, 96, 128));
            width: 80%;
            height: calc(100vh - 70px);
            flex-direction: column;
            padding: 20px;
            transition: all 0.5s;
            z-index: 1000;
        }
        
        .navbar.active {
            left: 0;
        }
        
        .nav-link {
            margin: 15px 0;
            font-size: 1.1rem;
        }
        
        .hamburger {
            display: block;
        }
        
        .messages-container {
            width: calc(100% - 40px);
            right: 20px;
            left: 20px;
            top: 70px;
        }
        
        .message {
            max-width: none;
            width: 100%;
        }
        
        .header .flex {
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 1.5rem;
        }
        
        .icons {
            gap: 15px;
        }
    }
    </style>
</head>
<body>
    <?php 
    $headerRenderer->renderMessages();
    $headerRenderer->renderHeader();
    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Message handling
        const messages = document.querySelectorAll('.message');
        
        messages.forEach(message => {
            // Auto-close after 4 seconds
            const autoCloseTimer = setTimeout(() => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            }, 4000);
            
            // Manual close
            const closeBtn = message.querySelector('.fa-times');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    clearTimeout(autoCloseTimer);
                    message.style.opacity = '0';
                    setTimeout(() => message.remove(), 500);
                });
            }
        });
        
        // Header functionality
        const userBtn = document.getElementById('user-btn');
        const profileCard = document.querySelector('.profile-card');
        const menuBtn = document.getElementById('menu-btn');
        const navbar = document.querySelector('.navbar');
        
        // Toggle profile dropdown
        if (userBtn && profileCard) {
            userBtn.addEventListener('click', function() {
                profileCard.classList.toggle('active');
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-icon') && !e.target.closest('.profile-card')) {
                if (profileCard) profileCard.classList.remove('active');
            }
        });
        
        // Mobile menu toggle
        if (menuBtn && navbar) {
            menuBtn.addEventListener('click', function() {
                navbar.classList.toggle('active');
                menuBtn.classList.toggle('fa-times');
            });
        }
    });
    </script>
</body>
</html>