<?php

// Database connection class
class Database {
    private $conn;
    
    public function __construct($host, $dbname, $username, $password) {
        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Message class to handle message operations
class Message {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db->getConnection();
    }
    
    public function getAllMessages() {
        $stmt = $this->conn->prepare("SELECT * FROM `messages` ORDER BY id DESC");
        $stmt->execute();
        return $stmt;
    }
    
    public function deleteMessage($id) {
        $stmt = $this->conn->prepare("DELETE FROM `messages` WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

// Admin session management
class AdminSession {
    public function __construct() {
        session_start();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
    
    public function getAdminId() {
        return $_SESSION['admin_id'] ?? null;
    }
    
    public function redirect($url) {
        header('location:' . $url);
        exit;
    }
}

// Initialize components
$adminSession = new AdminSession();

if (!$adminSession->isLoggedIn()) {
    $adminSession->redirect('admin_login.php');
}

// Create database connection (replace with your actual credentials)
$db = new Database('localhost', 'ns', 'root', '');
$message = new Message($db);

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($message->deleteMessage($delete_id)) {
        $adminSession->redirect('messages.php');
    }
}

// Get all messages
$select_messages = $message->getAllMessages();

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Messages | Admin Panel</title>

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- Google Font -->
   <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">

   <!-- Custom Inline Style (Replace with admin_style.css if needed) -->
   <style>
      :root {
         --primary: #4361ee;
         --primary-light: #4895ef;
         --secondary: #3f37c9;
         --dark: #2b2d42;
         --light: #f8f9fa;
         --danger: #ef233c;
         --success: #4cc9f0;
         --border-radius: 8px;
         --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
         --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      }

      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      body {
         background-color: #f5f7fa;
         color: var(--dark);
         padding-top: 80px; /* Added padding to prevent header overlap */
      }

      /* Header Styles */
      .admin-header {
         background-color: var(--primary);
         color: white;
         padding: 1rem 2rem;
         position: fixed;
         top: 0;
         left: 0;
         right: 0;
         z-index: 1000;
         display: flex;
         justify-content: space-between;
         align-items: center;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }

      .admin-header .logo {
         font-size: 1.8rem;
         font-weight: 700;
         color: white;
         text-decoration: none;
      }

      .admin-header .logo span {
         color: var(--light);
      }

      .admin-header .navbar {
         display: flex;
         gap: 1.5rem;
      }

      .admin-header .navbar a {
         color: white;
         font-size: 1.1rem;
         text-decoration: none;
         transition: var(--transition);
      }

      .admin-header .navbar a:hover {
         color: var(--light);
         transform: translateY(-2px);
      }

      .admin-header .icons a {
         color: white;
         font-size: 1.1rem;
         margin-left: 1.5rem;
         text-decoration: none;
      }

      .admin-header .icons a:hover {
         color: var(--light);
      }

      .heading {
         text-align: center;
         margin-bottom: 2rem;
         font-size: 2rem;
         color: var(--dark);
         position: relative;
         padding-bottom: 10px;
      }

      .heading:after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 50%;
         transform: translateX(-50%);
         width: 80px;
         height: 3px;
         background: var(--primary);
      }

      .accounts {
         max-width: 1200px;
         margin: 0 auto;
         padding: 2rem;
      }

      .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
         gap: 2rem;
      }

      .box {
         background: white;
         border-radius: var(--border-radius);
         box-shadow: var(--shadow);
         padding: 2rem;
         transition: var(--transition);
         display: flex;
         flex-direction: column;
         gap: 1rem;
      }

      .box:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      }

      .box p {
         font-size: 1rem;
         color: var(--dark);
         line-height: 1.5;
      }

      .box p span {
         font-weight: 600;
         color: var(--primary);
      }

      .option-btn, .delete-btn {
         display: inline-block;
         padding: 0.8rem 1.5rem;
         border-radius: var(--border-radius);
         font-weight: 600;
         text-align: center;
         transition: var(--transition);
         text-decoration: none;
         cursor: pointer;
         border: none;
      }

      .option-btn {
         background: var(--primary);
         color: white;
      }

      .option-btn:hover {
         background: var(--secondary);
         transform: translateY(-2px);
      }

      .delete-btn {
         background: #f8d7da;
         color: var(--danger);
      }

      .delete-btn:hover {
         background: #f1aeb5;
         transform: translateY(-2px);
      }

      .flex-btn {
         display: flex;
         gap: 1rem;
         margin-top: 1.5rem;
      }

      .empty {
         text-align: center;
         grid-column: 1/-1;
         padding: 3rem 0;
         color: #6c757d;
      }

      .add-admin-box {
         background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
         color: white;
         display: flex;
         flex-direction: column;
         align-items: center;
         justify-content: center;
         text-align: center;
      }

      .add-admin-box p {
         color: white;
         font-size: 1.2rem;
         margin-bottom: 1rem;
      }

      .add-admin-box .option-btn {
         background: white;
         color: var(--primary);
      }

      .add-admin-box .option-btn:hover {
         background: white;
         color: var(--secondary);
         transform: scale(1.05);
      }

      @media (max-width: 768px) {
         .box-container {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
         }
         
         .accounts {
            padding: 1.5rem;
         }

         .admin-header {
            flex-direction: column;
            padding: 1rem;
            text-align: center;
         }

         .admin-header .navbar {
            margin-top: 1rem;
            flex-wrap: wrap;
            justify-content: center;
         }

         .admin-header .navbar a {
            margin: 0 0.5rem;
         }

         .admin-header .icons {
            margin-top: 1rem;
         }
      }

      @media (max-width: 480px) {
         .flex-btn {
            flex-direction: column;
         }
         
         .heading {
            font-size: 1.5rem;
         }
      }
   </style>
</head>
<body>

<!-- Directly included header to ensure it shows -->
<header class="admin-header">
   <a href="dashboard.php" class="logo">Admin<span>Panel</span></a>
   
   <nav class="navbar">
      <a href="dashboard.php">Home</a>
      <a href="products.php">Products</a>
      <a href="placed_orders.php">Orders</a>
      <a href="admin_accounts.php">Admins</a>
      <a href="users_accounts.php">Users</a>
      <a href="messages.php">Messages</a>
   </nav>
   
   <div class="icons">
      <a href="admin_logout.php" onclick="return confirm('logout from this website?');">Logout</a>
   </div>
</header>
   
<section class="contacts">
   <h1 class="heading">Messages</h1>

   <div class="box-container">
      <?php
      if ($select_messages->rowCount() > 0) {
         while ($fetch_message = $select_messages->fetch(PDO::FETCH_ASSOC)) {
      ?>
         <div class="box">
            <p><strong>User ID:</strong> <span><?= htmlspecialchars($fetch_message['user_id']); ?></span></p>
            <p><strong>Name:</strong> <span><?= htmlspecialchars($fetch_message['name']); ?></span></p>
            <p><strong>Email:</strong> <span><?= htmlspecialchars($fetch_message['email']); ?></span></p>
            <p><strong>Phone:</strong> <span><?= htmlspecialchars($fetch_message['number']); ?></span></p>
            <p><strong>Message:</strong> <br><span><?= nl2br(htmlspecialchars($fetch_message['message'])); ?></span></p>
            <a href="messages.php?delete=<?= $fetch_message['id']; ?>" onclick="return confirm('Are you sure you want to delete this message?');" class="delete-btn">Delete</a>
         </div>
      <?php
         }
      } else {
         echo '<p class="empty">You have no messages.</p>';
      }
      ?>
   </div>
</section>

<script src="../js/admin_script.js"></script>

</body>
</html>