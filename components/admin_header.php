<?php
if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
   :root {
      --dashboard-primary: #5c6bc0;
      --dashboard-secondary: #3949ab;
      --dashboard-accent: #7986cb;
      --dashboard-danger: #e53935;
      --dashboard-success: #43a047;
      --dashboard-dark: #1a237e;
      --dashboard-light: #e8eaf6;
      --dashboard-text: #ffffff;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
   }

   * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
   }

   /* Message Styling */
   .message {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: var(--dashboard-success);
      color: var(--dashboard-text);
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: var(--shadow);
      display: flex;
      align-items: center;
      justify-content: space-between;
      max-width: 400px;
      z-index: 9999;
      animation: slideIn 0.5s ease-out forwards;
   }

   @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
   }

   .message i {
      margin-left: 15px;
      cursor: pointer;
      transition: var(--transition);
   }

   .message i:hover {
      transform: scale(1.2);
   }

   /* Header Styling - FIXED HEIGHT SOLUTION */
   .dashboard-header {
      background: linear-gradient(135deg, var(--dashboard-primary), var(--dashboard-secondary));
      color: var(--dashboard-text);
      padding: 0 30px;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: var(--shadow);
      animation: fadeInDown 0.8s ease-out forwards;
      height: 70px; /* Fixed height */
      min-height: 70px; /* Prevents collapse */
      display: flex;
      align-items: center;
      width: 100%;
   }

   @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
   }

   .header-flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      height: 70px; /* Match header height */
   }

   .dashboard-logo {
      font-size: 24px;
      font-weight: 700;
      color: var(--dashboard-text);
      text-decoration: none;
      display: flex;
      align-items: center;
      transition: var(--transition);
      height: 100%;
      padding: 0 10px;
   }

   .dashboard-logo i {
      margin-right: 10px;
      color: var(--dashboard-accent);
   }

   .dashboard-logo span {
      color: var(--dashboard-accent);
      transition: var(--transition);
   }

   .dashboard-logo:hover {
      transform: translateY(-2px);
   }

   .dashboard-logo:hover span {
      color: var(--dashboard-text);
   }

   /* Navigation */
   .dashboard-nav {
      display: flex;
      align-items: center;
      height: 100%;
   }

   .dashboard-nav a {
      margin: 0 15px;
      text-decoration: none;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
      position: relative;
      padding: 5px 0;
      transition: var(--transition);
      display: flex;
      align-items: center;
      height: 100%;
   }

   .dashboard-nav a i {
      margin-right: 8px;
      font-size: 16px;
   }

   .dashboard-nav a:hover {
      color: var(--dashboard-text);
      transform: translateY(-2px);
   }

   .dashboard-nav a::after {
      content: '';
      position: absolute;
      width: 0%;
      height: 3px;
      left: 0;
      bottom: 0;
      background-color: var(--dashboard-accent);
      transition: var(--transition);
      border-radius: 3px;
   }

   .dashboard-nav a:hover::after {
      width: 100%;
   }

   /* Icons */
   .dashboard-icons {
      display: flex;
      gap: 20px;
      align-items: center;
      height: 100%;
   }

   .dashboard-icons div {
      color: var(--dashboard-text);
      font-size: 20px;
      cursor: pointer;
      position: relative;
      transition: var(--transition);
      display: flex;
      align-items: center;
      height: 100%;
      padding: 0 5px;
   }

   .dashboard-icons div:hover {
      color: var(--dashboard-accent);
      transform: translateY(-2px);
   }

   /* Profile Dropdown */
   .dashboard-profile {
      position: absolute;
      top: 70px; /* Matches header height */
      right: 30px;
      background-color: white;
      border-radius: 10px;
      box-shadow: var(--shadow);
      padding: 20px;
      width: 280px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: var(--transition);
      z-index: 1001;
   }

   .dashboard-profile.active {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
   }

   .dashboard-profile::before {
      content: '';
      position: absolute;
      top: -10px;
      right: 20px;
      width: 0;
      height: 0;
      border-left: 10px solid transparent;
      border-right: 10px solid transparent;
      border-bottom: 10px solid white;
   }

   .profile-header {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
   }

   .profile-img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background-color: var(--dashboard-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 20px;
      margin-right: 15px;
   }

   .profile-info h4 {
      color: var(--dashboard-dark);
      margin-bottom: 5px;
   }

   .profile-info p {
      color: #6c757d;
      font-size: 13px;
   }

   .profile-links {
      display: flex;
      flex-direction: column;
      gap: 10px;
   }

   .profile-links a {
      display: flex;
      align-items: center;
      padding: 10px 15px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--dashboard-dark);
      transition: var(--transition);
   }

   .profile-links a i {
      margin-right: 10px;
      color: var(--dashboard-primary);
   }

   .profile-links a:hover {
      background-color: var(--dashboard-light);
      transform: translateX(5px);
   }

   .profile-links a:hover i {
      color: var(--dashboard-accent);
   }

   /* Buttons */
   .dashboard-btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 500;
      text-align: center;
      transition: var(--transition);
      display: inline-block;
      border: none;
      cursor: pointer;
   }

   .btn-primary {
      background-color: var(--dashboard-primary);
      color: white;
   }

   .btn-primary:hover {
      background-color: var(--dashboard-secondary);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(92, 107, 192, 0.3);
   }

   .btn-danger {
      background-color: var(--dashboard-danger);
      color: white;
   }

   .btn-danger:hover {
      background-color: #c62828;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(229, 57, 53, 0.3);
   }

   .flex-btn {
      display: flex;
      gap: 10px;
      margin: 15px 0;
   }

   /* Mobile Menu */
   #menu-btn {
      display: none;
   }

   @media (max-width: 992px) {
      .dashboard-nav {
         position: fixed;
         top: 70px;
         left: 0;
         width: 100%;
         background-color: white;
         flex-direction: column;
         align-items: flex-start;
         padding: 20px;
         box-shadow: var(--shadow);
         transform: translateY(-150%);
         opacity: 0;
         transition: var(--transition);
         z-index: 999;
      }

      .dashboard-nav.active {
         transform: translateY(0);
         opacity: 1;
      }

      .dashboard-nav a {
         color: var(--dashboard-dark);
         margin: 10px 0;
         padding: 10px 15px;
         width: 100%;
         border-radius: 8px;
         height: auto;
      }

      .dashboard-nav a:hover {
         background-color: var(--dashboard-light);
      }

      .dashboard-nav a::after {
         display: none;
      }

      #menu-btn {
         display: block;
      }

      .dashboard-profile {
         right: 15px;
      }
   }
</style>

<header class="dashboard-header">
   <section class="header-flex">

      <a href="dashboard.php" class="dashboard-logo">
         <i class="fas fa-tachometer-alt"></i> Admin<span>Dashboard</span>
      </a>

      <nav class="dashboard-nav" id="dashboardNav">
         <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
         <a href="products.php"><i class="fas fa-boxes"></i> Products</a>
         <a href="placed_orders.php"><i class="fas fa-clipboard-list"></i> Orders</a>
         <a href="admin_accounts.php"><i class="fas fa-user-cog"></i> Admins</a>
         <a href="users_accounts.php"><i class="fas fa-users"></i> Users</a>
         <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
      </nav>

      <div class="dashboard-icons">
         <div id="menu-btn" class="fas fa-bars"></div>
         <div id="user-btn" class="fas fa-user"></div>
      </div>

      <div class="dashboard-profile" id="dashboardProfile">
         <?php
            $select_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
            $select_profile->execute([$admin_id]);
            $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
            
            $name_parts = explode(' ', $fetch_profile['name']);
            $initials = '';
            foreach($name_parts as $part) {
               $initials .= strtoupper(substr($part, 0, 1));
            }
         ?>
         <div class="profile-header">
            <div class="profile-img"><?= $initials ?></div>
            <div class="profile-info">
               <h4><?= $fetch_profile['name'] ?></h4>
               <p>Administrator</p>
            </div>
         </div>
         
         <div class="profile-links">
            <a href="update_profile.php"><i class="fas fa-user-edit"></i> Update Profile</a>
            <a href="register_admin.php"><i class="fas fa-user-plus"></i> Register Admin</a>
         </div>
         
         <div class="flex-btn">
            <a href="admin_login.php" class="dashboard-btn btn-primary">
               <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="../components/admin_logout.php" 
               class="dashboard-btn btn-danger" 
               onclick="return confirm('Are you sure you want to logout?');">
               <i class="fas fa-sign-out-alt"></i> Logout
            </a>
         </div>
      </div>

   </section>
</header>

<script>
   document.addEventListener('DOMContentLoaded', function() {
      const userBtn = document.getElementById('user-btn');
      const profile = document.getElementById('dashboardProfile');
      const menuBtn = document.getElementById('menu-btn');
      const navbar = document.getElementById('dashboardNav');
      
      // Toggle profile dropdown
      userBtn.addEventListener('click', () => {
         profile.classList.toggle('active');
      });
      
      // Toggle mobile menu
      menuBtn.addEventListener('click', () => {
         navbar.classList.toggle('active');
         menuBtn.classList.toggle('fa-times');
      });
      
      // Close dropdowns when clicking outside
      document.addEventListener('click', (e) => {
         if (!e.target.closest('#dashboardProfile') && !e.target.matches('#user-btn')) {
            profile.classList.remove('active');
         }
         if (!e.target.closest('#dashboardNav') && !e.target.matches('#menu-btn')) {
            navbar.classList.remove('active');
            menuBtn.classList.remove('fa-times');
         }
      });
      
      // Auto-remove messages after 5 seconds
      const messages = document.querySelectorAll('.message');
      messages.forEach(message => {
         setTimeout(() => {
            message.style.animation = 'slideOut 0.5s ease-out forwards';
            setTimeout(() => message.remove(), 500);
         }, 5000);
      });

      // Force consistent header height
      const header = document.querySelector('.dashboard-header');
      if(header) {
         header.style.height = '70px';
         header.style.minHeight = '70px';
      }
   });
</script>