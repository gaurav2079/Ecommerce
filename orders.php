<?php
include 'components/connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Orders | Nepal~Shop</title>
   
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- Animate.css -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
   
   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      :root {
         --primary: #4361ee;
         --primary-light: #5e72e4;
         --secondary: #3a0ca3;
         --accent: #f72585;
         --accent-light: #ff66a3;
         --light: #f8f9fa;
         --lighter: #f9fafb;
         --light-gray: #f1f3f5;
         --gray: #adb5bd;
         --dark: #212529;
         --dark-gray: #495057;
         --success: #4caf50;
         --warning: #ff9800;
         --danger: #f44336;
         --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
         --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
         --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
         --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      }
      
      .orders {
         padding: 6rem 2rem 8rem;
         min-height: calc(100vh - 160px);
         background: var(--lighter);
         background-image: 
            radial-gradient(circle at 10% 20%, rgba(248, 249, 250, 0.8) 0%, transparent 20%),
            radial-gradient(circle at 90% 80%, rgba(248, 249, 250, 0.8) 0%, transparent 20%);
      }
      
      .orders .heading {
         text-align: center;
         font-size: 3.5rem;
         color: var(--dark);
         margin-bottom: 4rem;
         position: relative;
         padding-bottom: 1.5rem;
         font-weight: 700;
         letter-spacing: -0.5px;
      }
      
      .orders .heading::after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 50%;
         transform: translateX(-50%);
         width: 120px;
         height: 5px;
         background: linear-gradient(90deg, var(--primary), var(--accent));
         border-radius: 5px;
         box-shadow: 0 2px 8px rgba(67, 97, 238, 0.3);
      }
      
      .orders .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
         gap: 3rem;
         max-width: 1300px;
         margin: 0 auto;
      }
      
      .orders .box {
         background: white;
         border-radius: 16px;
         padding: 3rem;
         box-shadow: var(--shadow-md);
         transition: var(--transition);
         animation: fadeInUp 0.8s ease;
         border-left: 6px solid var(--primary);
         position: relative;
         overflow: hidden;
         z-index: 1;
      }
      
      .orders .box:hover {
         transform: translateY(-8px);
         box-shadow: var(--shadow-lg);
      }
      
      .orders .box::before {
         content: '';
         position: absolute;
         top: 0;
         left: 0;
         width: 100%;
         height: 6px;
         background: linear-gradient(90deg, var(--primary), var(--accent));
         z-index: -1;
      }
      
      .orders .box::after {
         content: '';
         position: absolute;
         top: 0;
         right: 0;
         width: 80px;
         height: 80px;
         background: var(--light-gray);
         border-radius: 50%;
         transform: translate(40px, -40px);
         z-index: -1;
      }
      
      .orders .box p {
         font-size: 1.7rem;
         color: var(--dark-gray);
         margin-bottom: 1.2rem;
         line-height: 1.6;
         display: flex;
         justify-content: space-between;
         position: relative;
      }
      
      .orders .box p::before {
         content: '';
         position: absolute;
         bottom: -5px;
         left: 0;
         width: 100%;
         height: 1px;
         background: linear-gradient(90deg, transparent, var(--gray), transparent);
         opacity: 0.3;
      }
      
      .orders .box p:last-child::before {
         display: none;
      }
      
      .orders .box p span {
         color: var(--dark);
         font-weight: 600;
         text-align: right;
      }
      
      .orders .box .status {
         display: inline-block;
         padding: 0.6rem 1.8rem;
         border-radius: 50px;
         font-weight: 600;
         font-size: 1.4rem;
         margin-top: 1.5rem;
         text-transform: uppercase;
         letter-spacing: 0.5px;
         box-shadow: var(--shadow-sm);
      }
      
      .orders .box .status.pending {
         background-color: #fff3e0;
         color: var(--warning);
         border: 1px solid #ffe0b2;
      }
      
      .orders .box .status.completed {
         background-color: #e8f5e9;
         color: var(--success);
         border: 1px solid #c8e6c9;
      }
      
      .orders .empty {
         text-align: center;
         font-size: 2.2rem;
         color: var(--dark-gray);
         grid-column: 1/-1;
         padding: 6rem 0;
         background: white;
         border-radius: 16px;
         box-shadow: var(--shadow-md);
         max-width: 800px;
         margin: 0 auto;
         width: 100%;
      }
      
      .orders .empty a {
         display: inline-block;
         margin-top: 2.5rem;
         padding: 1.2rem 2.5rem;
         background: linear-gradient(135deg, var(--primary), var(--secondary));
         color: white;
         border-radius: 8px;
         font-weight: 600;
         transition: var(--transition);
         box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
      }
      
      .orders .empty a:hover {
         transform: translateY(-3px);
         box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
      }
      
      .orders .login-prompt {
         text-align: center;
         font-size: 2.2rem;
         color: var(--dark-gray);
         grid-column: 1/-1;
         padding: 6rem 0;
         background: white;
         border-radius: 16px;
         box-shadow: var(--shadow-md);
         max-width: 800px;
         margin: 0 auto;
         width: 100%;
      }
      
      .orders .login-prompt a {
         color: var(--primary);
         text-decoration: none;
         font-weight: 700;
         transition: var(--transition);
         position: relative;
      }
      
      .orders .login-prompt a::after {
         content: '';
         position: absolute;
         bottom: -2px;
         left: 0;
         width: 100%;
         height: 2px;
         background: var(--primary);
         transform: scaleX(0);
         transform-origin: right;
         transition: transform 0.3s ease;
      }
      
      .orders .login-prompt a:hover {
         color: var(--secondary);
      }
      
      .orders .login-prompt a:hover::after {
         transform: scaleX(1);
         transform-origin: left;
      }
      
      @keyframes fadeInUp {
         from {
            opacity: 0;
            transform: translateY(30px);
         }
         to {
            opacity: 1;
            transform: translateY(0);
         }
      }
      
      /* Order action buttons */
      .order-actions {
         display: flex;
         gap: 1.5rem;
         margin-top: 2.5rem;
      }
      
      .order-actions .btn {
         padding: 1rem 2rem;
         border-radius: 10px;
         font-size: 1.5rem;
         font-weight: 600;
         transition: var(--transition);
         flex: 1;
         text-align: center;
         box-shadow: var(--shadow-sm);
      }
      
      .order-actions .btn.track {
         background: linear-gradient(135deg, var(--primary), var(--secondary));
         color: white;
      }
      
      .order-actions .btn.reorder {
         background: white;
         color: var(--primary);
         border: 2px solid var(--primary);
      }
      
      .order-actions .btn:hover {
         transform: translateY(-5px);
         box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      }
      
      .order-actions .btn.track:hover {
         background: linear-gradient(135deg, var(--primary-light), var(--secondary));
         box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
      }
      
      .order-actions .btn.reorder:hover {
         background: var(--primary);
         color: white;
         box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
      }
      
      /* Floating order badge */
      .order-badge {
         position: absolute;
         top: 20px;
         right: 20px;
         background: var(--primary);
         color: white;
         width: 50px;
         height: 50px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         font-weight: 700;
         font-size: 1.6rem;
         box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
      }
      
      /* Responsive */
      @media (max-width: 991px) {
         .orders {
            padding: 5rem 2rem;
         }
         
         .orders .heading {
            font-size: 3rem;
            margin-bottom: 3rem;
         }
      }
      
      @media (max-width: 768px) {
         .orders .box-container {
            grid-template-columns: 1fr;
            gap: 2.5rem;
         }
         
         .orders .box {
            padding: 2.5rem;
         }
         
         .orders .heading {
            font-size: 2.8rem;
         }
      }
      
      @media (max-width: 480px) {
         .orders .box {
            padding: 2rem;
         }
         
         .orders .box p {
            flex-direction: column;
            gap: 0.5rem;
         }
         
         .orders .box p span {
            text-align: left;
         }
         
         .order-actions {
            flex-direction: column;
            gap: 1rem;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="orders">
   <h1 class="heading animate__animated animate__fadeIn">My Orders</h1>

   <div class="box-container">
   <?php
      if($user_id == ''){
         echo '<p class="login-prompt animate__animated animate__fadeIn">Please <a href="user_login.php">login</a> to view your orders</p>';
      }else{
         $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ? ORDER BY placed_on DESC");
         $select_orders->execute([$user_id]);
         if($select_orders->rowCount() > 0){
            while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){
               $status_class = $fetch_orders['payment_status'] == 'pending' ? 'pending' : 'completed';
   ?>
   <div class="box animate__animated animate__fadeInUp">
      <div class="order-badge">#<?= $fetch_orders['id']; ?></div>
      <p>Order Date: <span><?= date('F j, Y', strtotime($fetch_orders['placed_on'])); ?></span></p>
      <p>Recipient: <span><?= $fetch_orders['name']; ?></span></p>
      <p>Contact: <span><?= $fetch_orders['number']; ?></span></p>
      <p>Delivery Address: <span><?= $fetch_orders['address']; ?></span></p>
      <p>Payment Method: <span><?= ucfirst($fetch_orders['method']); ?></span></p>
      <p>Items Ordered: <span><?= $fetch_orders['total_products']; ?></span></p>
      <p>Total Amount: <span>$<?= number_format($fetch_orders['total_price'], 2); ?></span></p>
      <p>Status: <span class="status <?= $status_class; ?>"><?= ucfirst($fetch_orders['payment_status']); ?></span></p>
      
      <div class="order-actions">
         <a href="#" class="btn track">Track Order</a>
         <a href="shop.php" class="btn reorder">Reorder</a>
      </div>
   </div>
   <?php
            }
         }else{
            echo '<p class="empty animate__animated animate__fadeIn">No orders placed yet! <br> <a href="shop.php" class="btn" style="margin-top:1.5rem;">Start Shopping</a></p>';
         }
      }
   ?>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

<script>
// Add animation to order boxes with delay
document.addEventListener('DOMContentLoaded', function() {
   const orderBoxes = document.querySelectorAll('.orders .box');
   
   orderBoxes.forEach((box, index) => {
      box.style.animationDelay = `${index * 0.1}s`;
   });
   
   // Add hover effect to order boxes
   orderBoxes.forEach(box => {
      box.addEventListener('mouseenter', function() {
         this.style.transform = 'translateY(-8px)';
         this.style.boxShadow = '0 15px 30px rgba(0,0,0,0.15)';
      });
      
      box.addEventListener('mouseleave', function() {
         this.style.transform = 'translateY(0)';
         this.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
      });
   });
});
</script>

</body>
</html>