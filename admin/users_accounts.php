<?php
include '../components/connect.php';
session_start();
function custom_hash($password) {
    $pepper = "N3p@l4598!";
    $salted_password = $password . $pepper;
    
    $key = 0;
    $p = 31;
    $q = 7;
    $m = 1000000007;
    
    // Calculate initial key
    for ($i = 0; $i < strlen($salted_password); $i++) {
        $key = ($key * 31 + ord($salted_password[$i])) % $m;
    }
    
    // Apply multiple iterations
    for ($i = 0; $i < 1000; $i++) {
        $key = ($key * $p + $q) % $m;
    }
    
    return strval($key);
}
class UserManager {
    private $conn;
    private $admin_id;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->checkAdminSession();
        $this->handleDeleteRequest();
    }
    
    private function checkAdminSession() {
        $this->admin_id = $_SESSION['admin_id'] ?? null;
        if (!isset($this->admin_id)) {
            header('location:admin_login.php');
            exit;
        }
    }
    
    private function handleDeleteRequest() {
        if (isset($_GET['delete'])) {
            $delete_id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
            $this->deleteUser($delete_id);
            header('location:users_accounts.php');
            exit;
        }
    }
    
    private function deleteUser($user_id) {
        // Prepare all delete statements
        $statements = [
            "DELETE FROM `users` WHERE id = ?",
            "DELETE FROM `orders` WHERE user_id = ?",
            "DELETE FROM `messages` WHERE user_id = ?",
            "DELETE FROM `cart` WHERE user_id = ?",
            "DELETE FROM `wishlist` WHERE user_id = ?"
        ];
        
        // Execute all delete operations
        foreach ($statements as $sql) {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$user_id]);
        }
    }
    
    public function getUsers() {
        $select_accounts = $this->conn->prepare("SELECT * FROM `users`");
        $select_accounts->execute();
        return $select_accounts->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function render() {
        $users = $this->getUsers();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
           <meta charset="UTF-8">
           <meta http-equiv="X-UA-Compatible" content="IE=edge">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>User Accounts | Admin</title>

           <!-- Font Awesome -->
           <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
           
           <!-- Google Font -->
           <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
           
           <!-- Custom CSS -->
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
   
        <!-- Main Content -->
        <section class="accounts">
           <h1 class="heading">User Accounts</h1>

           <div class="box-container">
              <?php if (!empty($users)): ?>
                 <?php foreach ($users as $user): ?>
                 <div class="box">
                    <p><strong>User ID:</strong> <span><?= $user['id']; ?></span></p>
                    <p><strong>Username:</strong> <span><?= htmlspecialchars($user['name']); ?></span></p>
                    <p><strong>Email:</strong> <span><?= htmlspecialchars($user['email']); ?></span></p>
                    <a href="users_accounts.php?delete=<?= $user['id']; ?>" class="delete-btn" onclick="return confirm('Delete this account? All associated data will be removed.')">Delete</a>
                 </div>
                 <?php endforeach; ?>
              <?php else: ?>
                 <p class="empty">No user accounts found.</p>
              <?php endif; ?>
           </div>
        </section>

        <script>
           // Toggle profile dropdown
           function toggleProfile() {
              const profile = document.getElementById('profile');
              profile.classList.toggle('active');
           }
           
           // Close profile dropdown when clicking outside
           document.addEventListener('click', function(event) {
              const profile = document.getElementById('profile');
              const userBtn = document.querySelector('#user-btn');
              
              if (!profile.contains(event.target) && event.target !== userBtn && !userBtn.contains(event.target)) {
                 profile.classList.remove('active');
              }
           });
           
           // Menu button functionality for mobile
           document.getElementById('menu-btn').addEventListener('click', function() {
              const navbar = document.querySelector('.header .navbar');
              navbar.classList.toggle('active');
           });
        </script>

        </body>
        </html>
        <?php
    }
}

// Initialize and render the user manager
$userManager = new UserManager($conn);
$userManager->render();
?>