<?php
include 'components/connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? '';

// Handle order deletion
if(isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    $order_id = filter_var($order_id, FILTER_SANITIZE_STRING);
    
    // Verify the order belongs to the user before deleting
    $verify_order = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
    $verify_order->execute([$order_id, $user_id]);
    
    if($verify_order->rowCount() > 0) {
        $delete_order = $conn->prepare("DELETE FROM `orders` WHERE id = ?");
        $delete_order->execute([$order_id]);
        $message[] = 'Order deleted successfully!';
    } else {
        $message[] = 'Order not found or you are not authorized to delete this order!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Orders | Nepal~Shop</title>
   
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/orders.css">
</head>
<body>
   
<!-- Header Section -->
<?php include 'components/user_header.php'; ?>

<!-- Display messages -->
<?php
if(isset($message)) {
   foreach($message as $msg) {
      echo '
      <div class="message">
         <span>'.$msg.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<!-- Main Content -->
<section class="orders-section">
   <div class="container">
      <div class="orders-header">
         <h1 class="orders-title">My Orders</h1>
      </div>

      <div class="orders-container">
      <?php
         if($user_id == '') {
            echo '
            <div class="login-prompt">
               <h2 class="login-title">Please <a href="user_login.php" class="login-link" style="color: blue;">login</a> to view your orders</h2>
               <p>View and manage all your orders in one place</p>
            </div>
            ';
         } else {
            $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ? ORDER BY placed_on DESC");
            $select_orders->execute([$user_id]);
            
            if($select_orders->rowCount() > 0) {
               while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                  $status = strtolower($fetch_orders['payment_status']);
                  $status_class = 'status-' . $status;
                  $products = explode(' - ', $fetch_orders['total_products']);
                  $order_date = date('M d, Y', strtotime($fetch_orders['placed_on']));
      ?>
      <div class="order-card">
         <div class="order-header">
            <div class="order-id">Order #<?= $fetch_orders['id'] ?></div>
            <div class="order-date"><?= $order_date ?></div>
         </div>
         
         <div class="order-details">
            <?php foreach($products as $product): ?>
               <?php if(!empty(trim($product))): ?>
                  <div class="order-product"><?= htmlspecialchars($product) ?></div>
               <?php endif; ?>
            <?php endforeach; ?>
         </div>
         
         <div class="order-price">Total: रु-<?= number_format($fetch_orders['total_price'], 2) ?></div>
         
         <div class="order-status <?= $status_class ?>">
            <i class="fas fa-<?= 
               $status == 'pending' ? 'clock' : 
               ($status == 'processing' ? 'cog' : 
               ($status == 'shipped' ? 'truck' : 
               ($status == 'delivered' ? 'check-circle' : 'times-circle')))
            ?>"></i>
            <?= ucfirst($status) ?>
         </div>
         
         <div class="order-actions">
            <button class="order-btn order-btn-primary">
               <i class="fas fa-eye"></i> View Details
            </button>
            <button class="order-btn order-btn-primary" onclick="window.location.href='shop.php'">
               <i class="fas fa-cart"></i> Shop
            </button>
            <form method="POST" style="display: inline;">
               <input type="hidden" name="order_id" value="<?= $fetch_orders['id'] ?>">
               <button type="submit" name="delete_order" class="order-btn order-btn-danger" onclick="return confirm('Are you sure you want to cancel this order?');">
                  <i class="fas fa-trash"></i> Cancel
               </button>
            </form>
         </div>
      </div>
      <?php
               }
            } else {
               echo '
               <div class="empty-orders">
                  <div class="empty-icon">
                     <i class="fas fa-box-open"></i>
                  </div>
                  <h2 class="empty-title">No Orders Yet</h2>
                  <p class="empty-description">You haven\'t placed any orders with us yet. Start shopping to discover amazing products!</p>
                  <a href="shop.php" class="empty-action">Start Shopping</a>
               </div>
               ';
            }
         }
      ?>
      </div>
   </div>
</section>

<!-- Footer Section -->
<?php include 'components/footer.php'; ?>

<!-- JavaScript -->
<script src="js/script.js"></script>
<script>
// Orders page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
   // Auto-close messages after 5 seconds
   const messages = document.querySelectorAll('.message');
   messages.forEach(message => {
      setTimeout(() => {
         message.style.opacity = '0';
         setTimeout(() => message.remove(), 300);
      }, 5000);
   });
});
</script>

</body>
</html>