<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
}

// Detect the correct date column
$check_columns = $conn->query("SHOW COLUMNS FROM `orders` LIKE '%date%'");
$date_column = 'placed_on'; // default fallback
if($check_columns->rowCount() > 0) {
    $column_info = $check_columns->fetch(PDO::FETCH_ASSOC);
    $date_column = $column_info['Field'];
}

// Get monthly data
$monthly_data = array();
for ($i = 1; $i <= 12; $i++) {
    $month = date('Y-m', strtotime(date('Y') . "-" . $i . "-01"));
    $start_date = $month . "-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $select_monthly_orders = $conn->prepare("SELECT COUNT(*) as count FROM `orders` WHERE $date_column BETWEEN ? AND ?");
    $select_monthly_orders->execute([$start_date, $end_date]);
    $monthly_orders = $select_monthly_orders->fetch(PDO::FETCH_ASSOC);
    
    $select_monthly_revenue = $conn->prepare("SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'completed' AND $date_column BETWEEN ? AND ?");
    $select_monthly_revenue->execute([$start_date, $end_date]);
    $monthly_revenue = $select_monthly_revenue->fetch(PDO::FETCH_ASSOC);
    
    $monthly_data[$i] = array(
        'month' => date('M', strtotime($start_date)),
        'orders' => $monthly_orders['count'] ?: 0,
        'revenue' => $monthly_revenue['total'] ?: 0
    );
}

// Get dashboard stats
$total_pendings = 0;
$select_pendings = $conn->prepare("SELECT * FROM `orders` WHERE payment_status = ?");
$select_pendings->execute(['pending']);
while($fetch_pendings = $select_pendings->fetch(PDO::FETCH_ASSOC)){
    $total_pendings += $fetch_pendings['total_price'];
}

$total_completes = 0;
$select_completes = $conn->prepare("SELECT * FROM `orders` WHERE payment_status = ?");
$select_completes->execute(['completed']);
while($fetch_completes = $select_completes->fetch(PDO::FETCH_ASSOC)){
    $total_completes += $fetch_completes['total_price'];
}

$select_orders = $conn->prepare("SELECT * FROM `orders`");
$select_orders->execute();
$number_of_orders = $select_orders->rowCount();

$select_products = $conn->prepare("SELECT * FROM `products`");
$select_products->execute();
$number_of_products = $select_products->rowCount();

$select_users = $conn->prepare("SELECT * FROM `users`");
$select_users->execute();
$number_of_users = $select_users->rowCount();

$select_admins = $conn->prepare("SELECT * FROM `admins`");
$select_admins->execute();
$number_of_admins = $select_admins->rowCount();

$select_messages = $conn->prepare("SELECT * FROM `messages`");
$select_messages->execute();
$number_of_messages = $select_messages->rowCount();

$select_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
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
      }
      
      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      
      body {
         background-color: #f5f7fa;
         color: #333;
      }
      
      .dashboard {
         padding: 20px;
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
      }
      
      .chart-controls button {
         padding: 5px 15px;
         background: var(--main-color);
         color: white;
         border: none;
         border-radius: 5px;
         cursor: pointer;
         transition: all 0.3s ease;
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
<?php include '../components/admin_header.php'; ?>
<section class="dashboard">
   <h1 class="heading">Dashboard</h1>

   <div class="box-container">
      <div class="box">
         <h3>Welcome!</h3>
         <p><?= htmlspecialchars($fetch_profile['name']); ?></p>
         <div id="live-clock"></div>
         <a href="update_profile.php" class="btn">Update Profile</a>
      </div>

      <div class="box">
         <h3><span>₹</span><?= number_format($total_pendings); ?><span>/-</span></h3>
         <p>Total Pendings</p>
         <span class="status-indicator status-pending"></span>
         <a href="placed_orders.php" class="btn">See Orders</a>
      </div>

      <div class="box">
         <h3><span>₹</span><?= number_format($total_completes); ?><span>/-</span></h3>
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
         <div class="chart-title">Monthly Analytics</div>
         <div class="chart-controls">
            <button class="active" data-type="orders">Orders</button>
            <button data-type="revenue">Revenue</button>
            <button data-type="both">Both</button>
         </div>
         <div class="chart-holder">
            <canvas id="lineChart"></canvas>
         </div>
      </div>
      <div class="chart-container">
         <div class="chart-title">Data Distribution</div>
         <div class="chart-holder">
            <canvas id="pieChart"></canvas>
         </div>
      </div>
   </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
   document.addEventListener('DOMContentLoaded', function() {
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
         revenue: <?php echo json_encode(array_column($monthly_data, 'revenue')); ?>
      };

      // Line Chart
      const lineCtx = document.getElementById('lineChart').getContext('2d');
      const lineChart = new Chart(lineCtx, {
         type: 'line',
         data: {
            labels: monthlyData.labels,
            datasets: [{
               label: 'Orders',
               data: monthlyData.orders,
               borderColor: 'rgba(54, 162, 235, 1)',
               backgroundColor: 'rgba(54, 162, 235, 0.1)',
               borderWidth: 2,
               tension: 0.4,
               fill: true
            }, {
               label: 'Revenue ($)',
               data: monthlyData.revenue,
               borderColor: 'rgba(75, 192, 192, 1)',
               backgroundColor: 'rgba(75, 192, 192, 0.1)',
               borderWidth: 2,
               tension: 0.4,
               fill: true,
               hidden: true
            }]
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
                           label += ': $' + context.parsed.y.toLocaleString();
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
                  beginAtZero: true
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
            lineChart.data.datasets.forEach((dataset, i) => {
               dataset.hidden = !(type === 'both' || 
                                 (type === 'orders' && i === 0) || 
                                 (type === 'revenue' && i === 1));
            });
            lineChart.update();
         });
      });

      // Pie Chart
      const pieCtx = document.getElementById('pieChart').getContext('2d');
      const pieChart = new Chart(pieCtx, {
         type: 'pie',
         data: {
            labels: ['Pending Orders', 'Completed Orders', 'Products', 'Users', 'Admins', 'Messages'],
            datasets: [{
               data: [
                  <?= $total_pendings; ?>,
                  <?= $total_completes; ?>,
                  <?= $number_of_products; ?>,
                  <?= $number_of_users; ?>,
                  <?= $number_of_admins; ?>,
                  <?= $number_of_messages; ?>
               ],
               backgroundColor: [
                  'rgba(255, 99, 132, 0.7)',
                  'rgba(54, 162, 235, 0.7)',
                  'rgba(255, 206, 86, 0.7)',
                  'rgba(75, 192, 192, 0.7)',
                  'rgba(153, 102, 255, 0.7)',
                  'rgba(255, 159, 64, 0.7)'
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
               },
               tooltip: {
                  callbacks: {
                     label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                     }
                  }
               }
            }
         }
      });
   });
</script>
</body>
</html>