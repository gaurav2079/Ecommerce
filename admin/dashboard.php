<?php
include '../components/connect.php';

session_start();

class Dashboard {
    private $conn;
    private $admin_id;
    private $date_column;
    private $product_has_date;
    private $product_date_column;
    private $monthly_data = array();
    private $stats = array();
    private $profile = array();
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->checkAdminSession();
        $this->detectDateColumns();
        $this->fetchMonthlyData();
        $this->fetchDashboardStats();
        $this->fetchAdminProfile();
    }
    
    private function checkAdminSession() {
        if(!isset($_SESSION['admin_id'])){
            header('location:admin_login.php');
            exit();
        }
        $this->admin_id = $_SESSION['admin_id'];
    }
    
    private function detectDateColumns() {
        // Detect the correct date column for orders
        $check_columns = $this->conn->query("SHOW COLUMNS FROM `orders` LIKE '%date%'");
        $this->date_column = 'placed_on'; // default fallback
        if($check_columns->rowCount() > 0) {
            $column_info = $check_columns->fetch(PDO::FETCH_ASSOC);
            $this->date_column = $column_info['Field'];
        }
        
        // Check if products table has a date column
        $check_product_date = $this->conn->query("SHOW COLUMNS FROM `products` LIKE '%date%'");
        $this->product_has_date = $check_product_date->rowCount() > 0;
        if($this->product_has_date) {
            $product_date_info = $check_product_date->fetch(PDO::FETCH_ASSOC);
            $this->product_date_column = $product_date_info['Field'];
        }
    }
    
    private function fetchMonthlyData() {
        for ($i = 1; $i <= 12; $i++) {
            $month = date('Y-m', strtotime(date('Y') . "-" . $i . "-01"));
            $start_date = $month . "-01";
            $end_date = date('Y-m-t', strtotime($start_date));
            
            $select_monthly_orders = $this->conn->prepare("SELECT COUNT(*) as count FROM `orders` WHERE {$this->date_column} BETWEEN ? AND ?");
            $select_monthly_orders->execute([$start_date, $end_date]);
            $monthly_orders = $select_monthly_orders->fetch(PDO::FETCH_ASSOC);
            
            $select_monthly_revenue = $this->conn->prepare("SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'completed' AND {$this->date_column} BETWEEN ? AND ?");
            $select_monthly_revenue->execute([$start_date, $end_date]);
            $monthly_revenue = $select_monthly_revenue->fetch(PDO::FETCH_ASSOC);
            
            // Get monthly product additions (with date check)
            if($this->product_has_date) {
                $select_monthly_products = $this->conn->prepare("SELECT COUNT(*) as count FROM `products` WHERE {$this->product_date_column} BETWEEN ? AND ?");
                $select_monthly_products->execute([$start_date, $end_date]);
            } else {
                $select_monthly_products = $this->conn->prepare("SELECT COUNT(*) as count FROM `products`");
                $select_monthly_products->execute();
            }
            $monthly_products = $select_monthly_products->fetch(PDO::FETCH_ASSOC);
            
            // Get monthly user registrations
            $select_monthly_users = $this->conn->prepare("SELECT COUNT(*) as count FROM `users` WHERE created_at BETWEEN ? AND ?");
            $select_monthly_users->execute([$start_date, $end_date]);
            $monthly_users = $select_monthly_users->fetch(PDO::FETCH_ASSOC);
            
            // Get monthly messages
            $select_monthly_messages = $this->conn->prepare("SELECT COUNT(*) as count FROM `messages` WHERE created_at BETWEEN ? AND ?");
            $select_monthly_messages->execute([$start_date, $end_date]);
            $monthly_messages = $select_monthly_messages->fetch(PDO::FETCH_ASSOC);
            
            $this->monthly_data[$i] = array(
                'month' => date('M', strtotime($start_date)),
                'orders' => $monthly_orders['count'] ?: 0,
                'revenue' => $monthly_revenue['total'] ?: 0,
                'products' => $monthly_products['count'] ?: 0,
                'users' => $monthly_users['count'] ?: 0,
                'messages' => $monthly_messages['count'] ?: 0
            );
        }
    }
    
    private function fetchDashboardStats() {
        // Get pending orders total
        $total_pendings = 0;
        $select_pendings = $this->conn->prepare("SELECT * FROM `orders` WHERE payment_status = ?");
        $select_pendings->execute(['pending']);
        while($fetch_pendings = $select_pendings->fetch(PDO::FETCH_ASSOC)){
            $total_pendings += $fetch_pendings['total_price'];
        }
        
        // Get completed orders total
        $total_completes = 0;
        $select_completes = $this->conn->prepare("SELECT * FROM `orders` WHERE payment_status = ?");
        $select_completes->execute(['completed']);
        while($fetch_completes = $select_completes->fetch(PDO::FETCH_ASSOC)){
            $total_completes += $fetch_completes['total_price'];
        }
        
        // Get counts
        $select_orders = $this->conn->prepare("SELECT * FROM `orders`");
        $select_orders->execute();
        $number_of_orders = $select_orders->rowCount();
        
        $select_products = $this->conn->prepare("SELECT * FROM `products`");
        $select_products->execute();
        $number_of_products = $select_products->rowCount();
        
        $select_users = $this->conn->prepare("SELECT * FROM `users`");
        $select_users->execute();
        $number_of_users = $select_users->rowCount();
        
        $select_admins = $this->conn->prepare("SELECT * FROM `admins`");
        $select_admins->execute();
        $number_of_admins = $select_admins->rowCount();
        
        $select_messages = $this->conn->prepare("SELECT * FROM `messages`");
        $select_messages->execute();
        $number_of_messages = $select_messages->rowCount();
        
        $this->stats = array(
            'total_pendings' => $total_pendings,
            'total_completes' => $total_completes,
            'number_of_orders' => $number_of_orders,
            'number_of_products' => $number_of_products,
            'number_of_users' => $number_of_users,
            'number_of_admins' => $number_of_admins,
            'number_of_messages' => $number_of_messages
        );
    }
    
    private function fetchAdminProfile() {
        $select_profile = $this->conn->prepare("SELECT * FROM `admins` WHERE id = ?");
        $select_profile->execute([$this->admin_id]);
        $this->profile = $select_profile->fetch(PDO::FETCH_ASSOC);
    }
    
    // Getters for the template
    public function getMonthlyData() {
        return $this->monthly_data;
    }
    
    public function getStats() {
        return $this->stats;
    }
    
    public function getProfile() {
        return $this->profile;
    }
    
    public function render() {
        // Extract stats for easier access in template
        extract($this->stats);
        $profile = $this->profile;
        $monthly_data = $this->monthly_data;
        
        // The HTML template part
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
           <meta charset="UTF-8">
           <meta http-equiv="X-UA-Compatible" content="IE=edge">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>Dashboard</title>
           <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
           <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
           <style>
              :root {
                 --main-color: #2980b9;
                 --secondary-color: #3498db;
                 --success-color: #2ecc71;
                 --warning-color: #f39c12;
                 --danger-color: #e74c3c;
                 --dark-color: #2c3e50;
                 --light-color: #ecf0f1;
                 --sidebar-width: 250px;
              }
              
              * {
                 margin: 0;
                 padding: 0;
                 box-sizing: border-box;
                 font-family: 'Poppins', sans-serif;
              }
              
              body {
                 background-color: #f5f7fa;
                 color: #333;
                 display: flex;
                 min-height: 100vh;
              }
              
              /* Sidebar Styles */
              .sidebar {
                 width: var(--sidebar-width);
                 background: var(--dark-color);
                 color: white;
                 position: fixed;
                 height: 100vh;
                 padding: 20px 0;
                 transition: all 0.3s;
                 z-index: 1000;
              }
              
              .sidebar-header {
                 padding: 0 20px 20px;
                 text-align: center;
                 border-bottom: 1px solid rgba(255,255,255,0.1);
              }
              
              .sidebar-header h3 {
                 color: white;
                 font-size: 1.3rem;
                 margin-bottom: 5px;
              }
              
              .sidebar-header p {
                 color: var(--light-color);
                 font-size: 0.9rem;
              }
              
              .sidebar-menu {
                 padding: 20px 0;
              }
              
              .sidebar-menu ul {
                 list-style: none;
              }
              
              .sidebar-menu li {
                 position: relative;
              }
              
              .sidebar-menu a {
                 display: block;
                 padding: 12px 20px;
                 color: var(--light-color);
                 text-decoration: none;
                 transition: all 0.3s;
                 font-size: 0.95rem;
              }
              
              .sidebar-menu a:hover, .sidebar-menu a.active {
                 background: rgba(255,255,255,0.1);
                 color: white;
              }
              
              .sidebar-menu a i {
                 margin-right: 10px;
                 width: 20px;
                 text-align: center;
              }
              
              .sidebar-menu .dropdown-menu {
                 padding-left: 40px;
                 display: none;
              }
              
              .sidebar-menu .dropdown-menu.show {
                 display: block;
              }
              
              .sidebar-menu .has-dropdown > a::after {
                 content: '\f078';
                 font-family: 'Font Awesome 6 Free';
                 font-weight: 900;
                 position: absolute;
                 right: 20px;
                 top: 12px;
                 font-size: 0.8rem;
                 transition: all 0.3s;
              }
              
              .sidebar-menu .has-dropdown.active > a::after {
                 transform: rotate(180deg);
              }
              
              /* Main Content Styles */
              .main-content {
                 flex: 1;
                 margin-left: var(--sidebar-width);
                 padding: 20px;
                 transition: all 0.3s;
              }
              
              .dashboard {
                 max-width: 1400px;
                 margin: 0 auto;
              }
              
              .heading {
                 text-align: center;
                 margin-bottom: 30px;
                 color: var(--main-color);
                 position: relative;
                 display: inline-block;
                 left: 50%;
                 transform: translateX(-50%);
              }
              
              .heading::after {
                 content: '';
                 position: absolute;
                 bottom: -8px;
                 left: 0;
                 width: 100%;
                 height: 3px;
                 background: linear-gradient(90deg, var(--main-color), rgba(0,0,0,0));
                 transform: scaleX(0);
                 transform-origin: left;
                 animation: headingUnderline 1.5s ease-in-out forwards;
              }
              
              .box-container {
                 display: grid;
                 grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                 gap: 20px;
                 margin-bottom: 30px;
              }
              
              .box {
                 animation: fadeIn 0.6s ease-out forwards;
                 opacity: 0;
                 background: white;
                 border-radius: 10px;
                 padding: 20px;
                 text-align: center;
                 box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                 transition: all 0.3s ease;
                 position: relative;
                 overflow: hidden;
              }
              
              .box:hover {
                 transform: translateY(-5px);
                 box-shadow: 0 10px 25px rgba(0,0,0,0.15);
              }
              
              .box h3 {
                 font-size: 2rem;
                 margin-bottom: 10px;
                 color: var(--main-color);
              }
              
              .box p {
                 font-size: 1.1rem;
                 color: #666;
                 margin-bottom: 15px;
              }
              
              .btn {
                 display: inline-block;
                 padding: 8px 20px;
                 background: var(--main-color);
                 color: white;
                 border-radius: 5px;
                 text-decoration: none;
                 transition: all 0.3s ease;
              }
              
              .btn:hover {
                 background: var(--secondary-color);
                 transform: scale(1.05);
              }
              
              .status-indicator {
                 display: inline-block;
                 width: 12px;
                 height: 12px;
                 border-radius: 50%;
                 margin-right: 5px;
              }
              
              .status-active {
                 background-color: var(--success-color);
              }
              
              .status-pending {
                 background-color: var(--warning-color);
              }
              
              .status-danger {
                 background-color: var(--danger-color);
              }
              
              .charts-wrapper {
                 display: flex;
                 flex-wrap: wrap;
                 gap: 20px;
                 margin-top: 30px;
              }
              
              .chart-container {
                 flex: 1;
                 min-width: 300px;
                 background: white;
                 border-radius: 10px;
                 padding: 20px;
                 box-shadow: 0 5px 15px rgba(0,0,0,0.1);
              }
              
              .chart-title {
                 text-align: center;
                 margin-bottom: 15px;
                 font-size: 1.3rem;
                 color: var(--main-color);
              }
              
              .chart-controls {
                 display: flex;
                 justify-content: center;
                 gap: 10px;
                 margin-bottom: 15px;
                 flex-wrap: wrap;
              }
              
              .chart-controls button {
                 padding: 5px 15px;
                 background: var(--main-color);
                 color: white;
                 border: none;
                 border-radius: 5px;
                 cursor: pointer;
                 transition: all 0.3s ease;
                 font-size: 0.9rem;
              }
              
              .chart-controls button:hover {
                 background: var(--secondary-color);
              }
              
              .chart-controls button.active {
                 background: var(--secondary-color);
              }
              
              .chart-holder {
                 width: 100%;
                 height: 300px;
                 position: relative;
              }
              
              #live-clock {
                 margin: 10px 0;
                 font-size: 1rem;
                 color: #666;
              }
              
              /* Responsive styles */
              @media (max-width: 992px) {
                 .sidebar {
                    width: 80px;
                    overflow: hidden;
                 }
                 
                 .sidebar-header h3, 
                 .sidebar-header p,
                 .sidebar-menu a span {
                    display: none;
                 }
                 
                 .sidebar-menu a {
                    text-align: center;
                    padding: 15px 10px;
                 }
                 
                 .sidebar-menu a i {
                    margin-right: 0;
                    font-size: 1.2rem;
                 }
                 
                 .main-content {
                    margin-left: 80px;
                 }
              }
              
              @media (max-width: 768px) {
                 .box-container {
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                 }
                 
                 .charts-wrapper {
                    flex-direction: column;
                 }
              }
              
              @keyframes fadeIn {
                 from { opacity: 0; transform: translateY(20px); }
                 to { opacity: 1; transform: translateY(0); }
              }
              
              @keyframes headingUnderline {
                 to { transform: scaleX(1); }
              }
              
              .box:nth-child(1) { animation-delay: 0.1s; }
              .box:nth-child(2) { animation-delay: 0.2s; }
              .box:nth-child(3) { animation-delay: 0.3s; }
              .box:nth-child(4) { animation-delay: 0.4s; }
              .box:nth-child(5) { animation-delay: 0.5s; }
              .box:nth-child(6) { animation-delay: 0.6s; }
              .box:nth-child(7) { animation-delay: 0.7s; }
              .box:nth-child(8) { animation-delay: 0.8s; }
           </style>
        </head>
        <body>
        <!-- Sidebar -->
        <div class="sidebar">
           <div class="sidebar-header">
              <h3>Nepal~Store</h3>
              <p>Admin Dashboard</p>
           </div>
           <nav class="sidebar-menu">
              <ul>
                 <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                 <li><a href="products.php"><i class="fas fa-box-open"></i> <span>Products</span></a></li>
                 <li><a href="placed_orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
                 <li><a href="users_accounts.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                 <li><a href="admin_accounts.php"><i class="fas fa-user-shield"></i> <span>Admins</span></a></li>
                 <li><a href="messages.php"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
                 <li class="has-dropdown">
                    <a href="#"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
                    <ul class="dropdown-menu">
                       <li><a href="sales_report.php"><i class="fas fa-chart-pie"></i> Sales</a></li>
                       <li><a href="inventory_report.php"><i class="fas fa-warehouse"></i> Inventory</a></li>
                       <li><a href="user_report.php"><i class="fas fa-user-graduate"></i> Users</a></li>
                    </ul>
                 </li>
                 <li><a href="update_profile.php"><i class="fas fa-user-edit"></i> <span>Profile</span></a></li>
                 <li><a href="../components/admin_logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
              </ul>
           </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
           <section class="dashboard">
              <h1 class="heading">Dashboard</h1>

              <div class="box-container">
                 <div class="box">
                    <h3>Welcome!</h3>
                    <p><?= htmlspecialchars($profile['name']); ?></p>
                    <div id="live-clock"></div>
                    <a href="update_profile.php" class="btn">Update Profile</a>
                 </div>

                 <div class="box">
                    <h3><span>रु-</span><?= number_format($total_pendings); ?></h3>
                    <p>Total Pendings</p>
                    <span class="status-indicator status-pending"></span>
                    <a href="placed_orders.php" class="btn">See Orders</a>
                 </div>

                 <div class="box">
                    <h3><span>रु-</span><?= number_format($total_completes); ?></h3>
                    <p>Completed Orders</p>
                    <span class="status-indicator status-active"></span>
                    <a href="placed_orders.php" class="btn">See Orders</a>
                 </div>

                 <div class="box">
                    <h3><?= number_format($number_of_orders); ?></h3>
                    <p>Orders Placed</p>
                    <a href="placed_orders.php" class="btn">See Orders</a>
                 </div>

                 <div class="box">
                    <h3><?= number_format($number_of_products); ?></h3>
                    <p>Products Added</p>
                    <a href="products.php" class="btn">See Products</a>
                 </div>

                 <div class="box">
                    <h3><?= number_format($number_of_users); ?></h3>
                    <p>Normal Users</p>
                    <a href="users_accounts.php" class="btn">See Users</a>
                 </div>

                 <div class="box">
                    <h3><?= number_format($number_of_admins); ?></h3>
                    <p>Admin Users</p>
                    <a href="admin_accounts.php" class="btn">See Admins</a>
                 </div>

                 <div class="box">
                    <h3><?= number_format($number_of_messages); ?></h3>
                    <p>New Messages</p>
                    <a href="messages.php" class="btn">See Messages</a>
                 </div>
              </div>

              <div class="charts-wrapper">
                 <div class="chart-container">
                    <div class="chart-title">Monthly Analytics Nepal~Store</div>
                    <div class="chart-controls">
                       <button class="active" data-type="orders">Orders</button>
                       <button data-type="revenue">Revenue</button>
                       <button data-type="products">Products</button>
                       <button data-type="users">Users</button>
                       <button data-type="messages">Messages</button>
                       <button data-type="all">All Data</button>
                    </div>
                    <div class="chart-holder">
                       <canvas id="barChart"></canvas>
                    </div>
                 </div>
                 <div class="chart-container">
                    <div class="chart-title">Data Distribution Nepal~Store</div>
                    <div class="chart-holder">
                       <canvas id="pieChart"></canvas>
                    </div>
                 </div>
              </div>
           </section>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
           document.addEventListener('DOMContentLoaded', function() {
              // Toggle dropdown menu in sidebar
              const dropdownToggles = document.querySelectorAll('.has-dropdown > a');
              dropdownToggles.forEach(toggle => {
                 toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const parent = this.parentElement;
                    const dropdown = parent.querySelector('.dropdown-menu');
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                       if (menu !== dropdown) {
                          menu.classList.remove('show');
                          menu.parentElement.classList.remove('active');
                       }
                    });
                    
                    // Toggle current dropdown
                    dropdown.classList.toggle('show');
                    parent.classList.toggle('active');
                 });
              });

              // Live clock
              function updateClock() {
                 const now = new Date();
                 const timeString = now.toLocaleTimeString();
                 const dateString = now.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                 });
                 document.getElementById('live-clock').innerHTML = `
                    <i class="fas fa-clock"></i> ${timeString}<br>
                    <i class="fas fa-calendar"></i> ${dateString}
                 `;
              }
              updateClock();
              setInterval(updateClock, 1000);

              // Monthly data
              const monthlyData = {
                 labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
                 orders: <?php echo json_encode(array_column($monthly_data, 'orders')); ?>,
                 revenue: <?php echo json_encode(array_column($monthly_data, 'revenue')); ?>,
                 products: <?php echo json_encode(array_column($monthly_data, 'products')); ?>,
                 users: <?php echo json_encode(array_column($monthly_data, 'users')); ?>,
                 messages: <?php echo json_encode(array_column($monthly_data, 'messages')); ?>
              };

              // Bar Chart
              const barCtx = document.getElementById('barChart').getContext('2d');
              const barChart = new Chart(barCtx, {
                 type: 'bar',
                 data: {
                    labels: monthlyData.labels,
                    datasets: [
                       {
                          label: 'Orders',
                          data: monthlyData.orders,
                          backgroundColor: 'rgba(54, 162, 235, 0.7)',
                          borderColor: 'rgba(54, 162, 235, 1)',
                          borderWidth: 1,
                          hidden: false
                       },
                       {
                          label: 'Revenue (रु)',
                          data: monthlyData.revenue,
                          backgroundColor: 'rgba(75, 192, 192, 0.7)',
                          borderColor: 'rgba(75, 192, 192, 1)',
                          borderWidth: 1,
                          hidden: true
                       },
                       {
                          label: 'Products Added',
                          data: monthlyData.products,
                          backgroundColor: 'rgba(255, 206, 86, 0.7)',
                          borderColor: 'rgba(255, 206, 86, 1)',
                          borderWidth: 1,
                          hidden: true
                       },
                       {
                          label: 'User Registrations',
                          data: monthlyData.users,
                          backgroundColor: 'rgba(153, 102, 255, 0.7)',
                          borderColor: 'rgba(153, 102, 255, 1)',
                          borderWidth: 1,
                          hidden: true
                       },
                       {
                          label: 'Messages',
                          data: monthlyData.messages,
                          backgroundColor: 'rgba(255, 159, 64, 0.7)',
                          borderColor: 'rgba(255, 159, 64, 1)',
                          borderWidth: 1,
                          hidden: true
                       }
                    ]
                 },
                 options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                       legend: {
                          position: 'top',
                       },
                       tooltip: {
                          callbacks: {
                             label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Revenue')) {
                                   label += ': रु' + context.parsed.y.toLocaleString();
                                } else {
                                   label += ': ' + context.parsed.y.toLocaleString();
                                }
                                return label;
                             }
                          }
                       }
                    },
                    scales: {
                       y: {
                          beginAtZero: true,
                          ticks: {
                             callback: function(value) {
                                return Number(value).toLocaleString();
                             }
                          }
                       }
                    }
                 }
              });

              // Chart filter buttons
              document.querySelectorAll('.chart-controls button').forEach(button => {
                 button.addEventListener('click', function() {
                    document.querySelectorAll('.chart-controls button').forEach(btn => {
                       btn.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    const type = this.getAttribute('data-type');
                    barChart.data.datasets.forEach((dataset, i) => {
                       if (type === 'all') {
                          dataset.hidden = false;
                       } else {
                          dataset.hidden = !(
                             (type === 'orders' && i === 0) || 
                             (type === 'revenue' && i === 1) ||
                             (type === 'products' && i === 2) ||
                             (type === 'users' && i === 3) ||
                             (type === 'messages' && i === 4)
                          );
                       }
                    });
                    barChart.update();
                 });
              });

              // Enhanced Pie Chart
              const pieCtx = document.getElementById('pieChart').getContext('2d');
              const pieChart = new Chart(pieCtx, {
                 type: 'doughnut',
                 data: {
                    labels: [
                       'Pending Revenue (रु)', 
                       'Completed Revenue (रु)', 
                       'Products', 
                       'Users', 
                       'Admins', 
                       'Messages',
                       'Total Orders'
                    ],
                    datasets: [{
                       data: [
                          <?= $total_pendings; ?>,
                          <?= $total_completes; ?>,
                          <?= $number_of_products; ?>,
                          <?= $number_of_users; ?>,
                          <?= $number_of_admins; ?>,
                          <?= $number_of_messages; ?>,
                          <?= $number_of_orders; ?>
                       ],
                       backgroundColor: [
                          'rgba(255, 99, 132, 0.7)',
                          'rgba(54, 162, 235, 0.7)',
                          'rgba(255, 206, 86, 0.7)',
                          'rgba(75, 192, 192, 0.7)',
                          'rgba(153, 102, 255, 0.7)',
                          'rgba(255, 159, 64, 0.7)',
                          'rgba(199, 199, 199, 0.7)'
                       ],
                       borderColor: [
                          'rgba(255, 99, 132, 1)',
                          'rgba(54, 162, 235, 1)',
                          'rgba(255, 206, 86, 1)',
                          'rgba(75, 192, 192, 1)',
                          'rgba(153, 102, 255, 1)',
                          'rgba(255, 159, 64, 1)',
                          'rgba(199, 199, 199, 1)'
                       ],
                       borderWidth: 1
                    }]
                 },
                 options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                       legend: {
                          position: 'right',
                          labels: {
                             padding: 20,
                             usePointStyle: true,
                             pointStyle: 'circle',
                             font: {
                                size: 12
                             }
                          }
                       },
                       tooltip: {
                          callbacks: {
                             label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                
                                if (label.includes('Revenue')) {
                                   return `${label}: रु${value.toLocaleString()} (${percentage}%)`;
                                } else {
                                   return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                             }
                          }
                       },
                       title: {
                          display: true,
                          text: 'Data Distribution',
                          font: {
                             size: 16
                          }
                       }
                    },
                    cutout: '60%',
                    animation: {
                       animateScale: true,
                       animateRotate: true
                    }
                 }
              });
           });
        </script>

        </body>
        </html>
        <?php
    }
}

// Initialize and render the dashboard
$dashboard = new Dashboard($conn);
$dashboard->render();