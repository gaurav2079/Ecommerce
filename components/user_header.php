<?php
// messages.php - Reusable message component

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize message array
$messages = [];

// Check for session messages
if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
    $messages = (array)$_SESSION['messages'];
    unset($_SESSION['messages']); // Clear after displaying
}

// Function to add message (can be used from other files)
function addMessage($text, $type = 'info') {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = [
        'text' => $text,
        'type' => $type
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal~Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if (!empty($messages)): ?>
        <div class="messages-container">
            <?php foreach ($messages as $msg): ?>
                <div class="message <?= htmlspecialchars($msg['type'], ENT_QUOTES) ?>">
                    <span><?= htmlspecialchars($msg['text'], ENT_QUOTES) ?></span>
                    <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <header class="header">
        <section class="flex">
            <a href="home.php" class="logo">
                <i class="fas fa-cube"></i>
                <span>Nepal~Store</span>
            </a>

            <nav class="navbar">
                <a href="home.php" class="nav-link hover-underline">Home</a>
                <a href="about.php" class="nav-link hover-underline">About</a>
                <a href="orders.php" class="nav-link hover-underline">Orders</a>
                <a href="shop.php" class="nav-link hover-underline">Shop</a>
                <a href="contact.php" class="nav-link hover-underline">Contact</a>
            </nav>

            <div class="icons">
                <?php
                $count_wishlist_items = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
                $count_wishlist_items->execute([$user_id]);
                $total_wishlist_counts = $count_wishlist_items->rowCount();

                $count_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
                $count_cart_items->execute([$user_id]);
                $total_cart_counts = $count_cart_items->rowCount();
                ?>
                <div id="menu-btn" class="fas fa-bars hamburger"></div>
                <a href="search_page.php" class="icon-link" title="Search"><i class="fas fa-search"></i></a>
                <a href="wishlist.php" class="icon-link" title="Wishlist">
                    <i class="fas fa-heart"></i>
                    <span class="badge pulse"><?= $total_wishlist_counts; ?></span>
                </a>
                <a href="cart.php" class="icon-link" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge pulse"><?= $total_cart_counts; ?></span>
                </a>
                <div id="user-btn" class="fas fa-user user-icon"></div>
            </div>

            <div class="profile-card">
                <?php          
                $select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
                $select_profile->execute([$user_id]);
                if($select_profile->rowCount() > 0) {
                    $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="profile-header">
                    <div class="avatar">
                        <?= substr($fetch_profile["name"], 0, 1); ?>
                    </div>
                    <p class="username"><?= $fetch_profile["name"]; ?></p>
                </div>
                <a href="update_user.php" class="profile-btn"><i class="fas fa-user-edit"></i> Update Profile</a>
                
                <div class="flex-btn">
                    <a href="user_register.php" class="option-btn"><i class="fas fa-user-plus"></i> Register</a>
                    <a href="user_login.php" class="option-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                </div>
                <a href="components/user_logout.php" class="logout-btn" onclick="return confirm('Logout from the website?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a> 
                <?php } else { ?>
                <p class="login-prompt">Please login or register first!</p>
                <div class="flex-btn">
                    <a href="user_register.php" class="option-btn gradient-btn"><i class="fas fa-user-plus"></i> Register</a>
                    <a href="user_login.php" class="option-btn gradient-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                </div>
                <?php } ?>      
            </div>
        </section>
    </header>

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
        animation: slide-in 0.5s forwards;
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
        background: #f4c90c;
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
    
    @keyframes slide-in {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @media (max-width: 768px) {
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
    }
    
    /* Header Styles */
    .header {
        background: linear-gradient(135deg, rgb(128, 172, 185), rgb(75, 119, 139));
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
        transition: all 0.3s ease;
    }
    
    .logo {
        font-size: 1.8rem;
        color: white;
        position: relative;
        text-decoration: none;
    }
    
    .logo span {
        color: #f39c12;
    }
    
    .logo-pulse {
        position: absolute;
        width: 8px;
        height: 8px;
        background: #f39c12;
        border-radius: 50%;
        top: -5px;
        right: -5px;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(0.95); opacity: 0.8; }
        70% { transform: scale(1.3); opacity: 0.2; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }
    
    .nav-link {
        color: white;
        margin: 0 15px;
        position: relative;
        text-transform: uppercase;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .nav-link:hover {
        color: #ffffff;
        text-shadow: 0 0 5px rgba(255, 255, 255, 0.8);
        transform: translateY(-2px);
        transition: all 0.3s ease;
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
    
    .pulse {
        animation: pulse 1.5s infinite;
    }
    
    .user-icon {
        cursor: pointer;
        transition: all 0.3s;
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
    }
    
    .profile-btn {
        background: #f8f9fa;
        color: #2c3e50;
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
    
    .option-btn {
        display: inline-block;
        width: 48%;
        padding: 8px;
        margin: 5px 0;
        border-radius: 5px;
        text-align: center;
        transition: all 0.3s;
    }
    
    .gradient-btn {
        background: linear-gradient(to right, rgb(153, 188, 212), #2ecc71);
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
    }
    
    @media (max-width: 768px) {
        .navbar {
            position: fixed;
            top: 70px;
            left: -100%;
            background: rgb(11, 129, 247);
            width: 80%;
            height: calc(100vh - 70px);
            flex-direction: column;
            padding: 20px;
            transition: all 0.5s;
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
    }
    </style>

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
        
        // Close dropdown when clicking outside
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