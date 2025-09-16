<?php
include 'components/connect.php';
session_start();

// OOP Classes for Order Management
class OrderManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function deleteOrder($order_id, $user_id) {
        // Verify the order belongs to the user before deleting
        $verify_order = $this->conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
        $verify_order->execute([$order_id, $user_id]);
        
        if($verify_order->rowCount() > 0) {
            $delete_order = $this->conn->prepare("DELETE FROM `orders` WHERE id = ?");
            $delete_order->execute([$order_id]);
            return 'Order deleted successfully!';
        } else {
            return 'Order not found or you are not authorized to delete this order!';
        }
    }
    
    public function deleteAllOrders($user_id) {
        // Delete all orders for the user
        $delete_orders = $this->conn->prepare("DELETE FROM `orders` WHERE user_id = ?");
        $delete_orders->execute([$user_id]);
        
        $deleted_count = $delete_orders->rowCount();
        
        if($deleted_count > 0) {
            return "Successfully deleted $deleted_count orders from your history!";
        } else {
            return 'No orders found to delete!';
        }
    }
    
    public function deleteProductFromOrder($order_id, $product_id, $user_id) {
        // Verify the order belongs to the user
        $verify_order = $this->conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
        $verify_order->execute([$order_id, $user_id]);
        
        if($verify_order->rowCount() > 0) {
            $order = $verify_order->fetch(PDO::FETCH_ASSOC);
            
            // Parse product details
            $products = explode(' - ', $order['total_products']);
            $product_ids = explode(',', $order['product_ids']);
            
            // Find the product to remove
            $product_index = -1;
            foreach($products as $index => $product) {
                if (strpos($product, "(ID: $product_id)") !== false) {
                    $product_index = $index;
                    break;
                }
            }
            
            // If product found, remove it
            if ($product_index !== -1) {
                // Get product price to adjust total
                preg_match('/रु-([\d,]+\.\d{2})/', $products[$product_index], $price_match);
                $product_price = isset($price_match[1]) ? (float) str_replace(',', '', $price_match[1]) : 0;
                
                // Remove the product
                array_splice($products, $product_index, 1);
                array_splice($product_ids, $product_index, 1);
                
                // Update order
                $new_total_products = implode(' - ', $products);
                $new_product_ids = implode(',', $product_ids);
                $new_total_price = max(0, $order['total_price'] - $product_price);
                
                // If no products left, delete the order
                if (empty($new_total_products)) {
                    $delete_order = $this->conn->prepare("DELETE FROM `orders` WHERE id = ?");
                    $delete_order->execute([$order_id]);
                    return 'Product removed. Order deleted as it contained no more products.';
                } else {
                    // Update the order with remaining products
                    $update_order = $this->conn->prepare("UPDATE `orders` SET total_products = ?, product_ids = ?, total_price = ? WHERE id = ?");
                    $update_order->execute([$new_total_products, $new_product_ids, $new_total_price, $order_id]);
                    return 'Product removed from order successfully!';
                }
            } else {
                return 'Product not found in this order!';
            }
        } else {
            return 'Order not found or you are not authorized to modify this order!';
        }
    }
    
    public function getUserOrders($user_id) {
        if(empty($user_id)) {
            return [];
        }
        
        $select_orders = $this->conn->prepare("SELECT * FROM `orders` WHERE user_id = ? ORDER BY placed_on DESC");
        $select_orders->execute([$user_id]);
        
        return $select_orders->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function parseOrderProducts($order) {
        $products = explode(' - ', $order['total_products']);
        $product_ids = explode(',', $order['product_ids']);
        $parsed_products = [];
        
        foreach($products as $index => $product) {
            if(!empty(trim($product))) {
                // Extract product ID if available
                $id = isset($product_ids[$index]) ? $product_ids[$index] : '';
                
                // Extract price if available
                preg_match('/रु-([\d,]+\.\d{2})/', $product, $price_match);
                $price = isset($price_match[1]) ? (float) str_replace(',', '', $price_match[1]) : 0;
                
                // Clean product name
                $name = preg_replace('/\s*रु-[\d,]+\.\d{2}\s*/', '', $product);
                $name = preg_replace('/\s*\(ID: \d+\)\s*/', '', $name);
                
                $parsed_products[] = [
                    'name' => trim($name),
                    'price' => $price,
                    'id' => $id,
                    'raw' => $product
                ];
            }
        }
        
        return $parsed_products;
    }
}

class OrderRenderer {
    public static function getPaymentMethodInfo($method) {
        $payment_method = strtolower($method);
        
        switch($payment_method) {
            case 'credit card':
            case 'debit card':
            case 'card':
                return [
                    'icon' => 'fa-credit-card',
                    'class' => 'payment-card',
                    'text' => 'Credit/Debit Card'
                ];
            case 'khalti':
                return [
                    'icon' => 'fa-mobile-alt',
                    'class' => 'payment-khalti',
                    'text' => 'Khalti'
                ];
            case 'esewa':
                return [
                    'icon' => 'fa-wallet',
                    'class' => 'payment-esewa',
                    'text' => 'eSewa'
                ];
            case 'cash on delivery':
            case 'cod':
            default:
                return [
                    'icon' => 'fa-money-bill-wave',
                    'class' => 'payment-cod',
                    'text' => 'Cash on Delivery'
                ];
        }
    }
    
    public static function getStatusIcon($status) {
        $status = strtolower($status);
        
        switch($status) {
            case 'pending': return 'clock';
            case 'processing': return 'cog';
            case 'shipped': return 'truck';
            case 'delivered':
            case 'completed': return 'check-circle';
            case 'cancelled': return 'times-circle';
            default: return 'question-circle';
        }
    }
    
    public static function renderOrderCard($order, $orderManager) {
        $status = strtolower($order['payment_status']);
        $status_class = 'status-' . $status;
        $order_date = date('M d, Y', strtotime($order['placed_on']));
        
        $payment_info = self::getPaymentMethodInfo($order['method']);
        $status_icon = self::getStatusIcon($status);
        
        // Parse products
        $products = $orderManager->parseOrderProducts($order);
        
        ob_start();
        ?>
        <div class="order-card">
            <div class="order-header">
                <div class="order-id">Order #<?= $order['id'] ?></div>
                <div class="order-date"><?= $order_date ?></div>
            </div>
            
            <div class="order-details">
                <?php foreach($products as $product): ?>
                    <div class="order-product">
                        <span class="product-name"><?= htmlspecialchars($product['name']) ?></span>
                     
                        <?php if($status == 'pending' || $status == 'processing'): ?>
                        <form method="POST" class="delete-product-form">
                            <input type="hidden" >
                        
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-price">Total: रु-<?= number_format($order['total_price'], 2) ?></div>
            
            <div class="payment-method <?= $payment_info['class'] ?>">
                <i class="fas <?= $payment_info['icon'] ?>"></i>
                <span>Payment: <?= $payment_info['text'] ?></span>
            </div>
            
            <div class="order-status <?= $status_class ?>">
                <i class="fas fa-<?= $status_icon ?>"></i>
                <?= ucfirst($status) ?>
            </div>
            
            <div class="order-actions">
                <button class="order-btn order-btn-primary" onclick="window.location.href='shop.php'">
                    <i class="fas fa-cart"></i> Shop More
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" name="delete_order" class="order-btn order-btn-danger" onclick="return confirm('Are you sure you want to delete this order from your history?');">
                        <i class="fas fa-trash"></i> Delete Order
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize objects
$orderManager = new OrderManager($conn);
$user_id = $_SESSION['user_id'] ?? '';

// Handle order deletion
if(isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    $order_id = filter_var($order_id, FILTER_SANITIZE_STRING);
    
    $message[] = $orderManager->deleteOrder($order_id, $user_id);
}

// Handle delete all orders
if(isset($_POST['delete_all_orders'])) {
    $message[] = $orderManager->deleteAllOrders($user_id);
}

// Handle product deletion from order
if(isset($_POST['delete_product'])) {
    $order_id = $_POST['order_id'];
    $product_id = $_POST['product_id'];
    
    $order_id = filter_var($order_id, FILTER_SANITIZE_STRING);
    $product_id = filter_var($product_id, FILTER_SANITIZE_STRING);
    
    $message[] = $orderManager->deleteProductFromOrder($order_id, $product_id, $user_id);
}

// Get user orders
$user_orders = $orderManager->getUserOrders($user_id);
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
   
   <!-- Google Fonts -->
   <link href="https://fonts.googleapis/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
   <style>
      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Poppins', sans-serif;
      }
      
      :root {
         --primary: #4a6cf7;
         --secondary: #6c757d;
         --success: #28a745;
         --danger: #dc3545;
         --warning: #ffc107;
         --info: #17a2b8;
         --light: #f8f9fa;
         --dark: #343a40;
         --white: #ffffff;
         --card-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
         --transition: all 0.3s ease;
      }
      
      body {
         background-color: #f5f7fb;
         color: #333;
         line-height: 1.6;
      }
      
      .container {
         max-width: 1200px;
         margin: 0 auto;
         padding: 0 15px;
      }
      
      /* Orders Section */
      .orders-section {
         padding: 40px 0;
         min-height: 70vh;
      }
      
      .orders-header {
         text-align: center;
         margin-bottom: 40px;
         position: relative;
      }
      
      .orders-title {
         font-size: 2.5rem;
         color: var(--primary);
         margin-bottom: 10px;
         position: relative;
         display: inline-block;
      }
      
      .orders-title::after {
         content: '';
         position: absolute;
         bottom: -10px;
         left: 50%;
         transform: translateX(-50%);
         width: 80px;
         height: 4px;
         background: var(--primary);
         border-radius: 2px;
      }
      
      .delete-all-container {
         position: absolute;
         top: 0;
         right: 0;
      }
      
      .delete-all-btn {
         padding: 10px 20px;
         background: var(--danger);
         color: white;
         border: none;
         border-radius: 8px;
         font-size: 0.9rem;
         font-weight: 500;
         cursor: pointer;
         transition: var(--transition);
         display: inline-flex;
         align-items: center;
         gap: 8px;
      }
      
      .delete-all-btn:hover {
         background: #bd2130;
         transform: translateY(-2px);
      }
      
      /* Orders Container */
      .orders-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
         gap: 25px;
      }
      
      /* Order Card */
      .order-card {
         background: var(--white);
         border-radius: 15px;
         overflow: hidden;
         box-shadow: var(--card-shadow);
         transition: var(--transition);
         position: relative;
      }
      
      .order-card:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
      }
      
      .order-header {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding: 20px;
         background: linear-gradient(135deg, #4a6cf7 0%, #2b4fef 100%);
         color: white;
      }
      
      .order-id {
         font-weight: 600;
         font-size: 1.1rem;
      }
      
      .order-date {
         font-size: 0.9rem;
         opacity: 0.9;
      }
      
      .order-details {
         padding: 15px 20px;
         border-bottom: 1px solid #eee;
      }
      
      .order-product {
         padding: 12px 10px;
         border-bottom: 1px dashed #f1f1f1;
         display: flex;
         align-items: center;
         justify-content: space-between;
         position: relative;
      }
      
      .order-product:last-child {
         border-bottom: none;
      }
      
      .product-name {
         flex: 1;
         padding-right: 10px;
      }
      
      .product-price {
         font-weight: 600;
         color: var(--primary);
         margin-right: 10px;
      }
      
      .delete-product-form {
         display: inline;
      }
      
      .delete-product-btn {
         background: transparent;
         border: none;
         color: #ccc;
         cursor: pointer;
         padding: 5px;
         border-radius: 50%;
         width: 28px;
         height: 28px;
         display: flex;
         align-items: center;
         justify-content: center;
         transition: var(--transition);
      }
      
      .delete-product-btn:hover {
         background: rgba(220, 53, 69, 0.1);
         color: var(--danger);
      }
      
      .order-price {
         padding: 15px 20px;
         font-size: 1.2rem;
         font-weight: 600;
         color: var(--dark);
         background: #f9fafc;
         border-bottom: 1px solid #eee;
      }
      
      /* Payment Method */
      .payment-method {
         display: flex;
         align-items: center;
         gap: 10px;
         margin: 15px 20px;
         padding: 12px 15px;
         border-radius: 10px;
         font-size: 0.9rem;
         font-weight: 500;
      }
      
      .payment-method i {
         font-size: 1.2rem;
      }
      
      .payment-cash, .payment-cod {
         background: rgba(74, 108, 247, 0.1);
         color: #4a6cf7;
      }
      
      .payment-card {
         background: rgba(255, 193, 7, 0.1);
         color: #ffc107;
      }
      
      .payment-khalti {
         background: rgba(23, 162, 184, 0.1);
         color: #17a2b8;
      }
      
      .payment-esewa {
         background: rgba(220, 53, 69, 0.1);
         color: #dc3545;
      }
      
      /* Order Status */
      .order-status {
         display: inline-flex;
         align-items: center;
         gap: 8px;
         margin: 0 20px 15px;
         padding: 8px 15px;
         border-radius: 20px;
         font-size: 0.85rem;
         font-weight: 500;
      }
      
      .status-pending {
         background: rgba(255, 193, 7, 0.1);
         color: #ffc107;
      }
      
      .status-processing {
         background: rgba(23, 162, 184, 0.1);
         color: #17a2b8;
      }
      
      .status-shipped {
         background: rgba(74, 108, 247, 0.1);
         color: #4a6cf7;
      }
      
      .status-completed, .status-delivered {
         background: rgba(40, 167, 69, 0.1);
         color: #28a745;
      }
      
      .status-cancelled {
         background: rgba(220, 53, 69, 0.1);
         color: #dc3545;
      }
      
      /* Order Actions */
      .order-actions {
         padding: 0 20px 20px;
         display: flex;
         gap: 10px;
         flex-wrap: wrap;
      }
      
      .order-btn {
         padding: 12px 20px;
         border: none;
         border-radius: 8px;
         font-size: 0.95rem;
         font-weight: 500;
         cursor: pointer;
         transition: var(--transition);
         display: inline-flex;
         align-items: center;
         gap: 8px;
         flex: 1;
         justify-content: center;
      }
      
      .order-btn-primary {
         background: var(--primary);
         color: white;
      }
      
      .order-btn-primary:hover {
         background: #3a5cd8;
         transform: translateY(-2px);
      }
      
      .order-btn-danger {
         background: var(--danger);
         color: white;
      }
      
      .order-btn-danger:hover {
         background: #bd2130;
         transform: translateY(-2px);
      }
      
      /* Empty Orders */
      .empty-orders {
         text-align: center;
         padding: 60px 20px;
         grid-column: 1 / -1;
      }
      
      .empty-icon {
         font-size: 5rem;
         color: #ddd;
         margin-bottom: 20px;
      }
      
      .empty-title {
         font-size: 1.8rem;
         color: #6c757d;
         margin-bottom: 15px;
      }
      
      .empty-description {
         color: #6c757d;
         margin-bottom: 30px;
         max-width: 500px;
         margin-left: auto;
         margin-right: auto;
      }
      
      .empty-action {
         display: inline-block;
         padding: 12px 30px;
         background: var(--primary);
         color: white;
         text-decoration: none;
         border-radius: 8px;
         font-weight: 500;
         transition: var(--transition);
      }
      
      .empty-action:hover {
         background: #3a5cd8;
         transform: translateY(-2px);
      }
      
      /* Login Prompt */
      .login-prompt {
         text-align: center;
         padding: 60px 20px;
         grid-column: 1 / -1;
      }
      
      .login-title {
         font-size: 1.8rem;
         color: #6c757d;
         margin-bottom: 15px;
      }
      
      .login-link {
         color: var(--primary);
         text-decoration: none;
         font-weight: 600;
      }
      
      .login-link:hover {
         text-decoration: underline;
      }
      
      /* Messages */
      .message {
         position: fixed;
         top: 20px;
         right: 20px;
         padding: 15px 20px;
         background: var(--success);
         color: white;
         border-radius: 8px;
         display: flex;
         align-items: center;
         gap: 10px;
         box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
         z-index: 1000;
         animation: slideIn 0.3s ease;
      }
      
      .message.error {
         background: var(--danger);
      }
      
      .message i {
         cursor: pointer;
      }
      
      @keyframes slideIn {
         from {
            transform: translateX(100%);
            opacity: 0;
         }
         to {
            transform: translateX(0);
            opacity: 1;
         }
      }
      
      /* Responsive */
      @media (max-width: 768px) {
         .orders-container {
            grid-template-columns: 1fr;
         }
         
         .orders-title {
            font-size: 2rem;
         }
         
         .orders-header {
            padding-bottom: 60px;
         }
         
         .delete-all-container {
            top: auto;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
         }
         
         .delete-all-btn {
            width: 100%;
            justify-content: center;
         }
         
         .order-product {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
         }
         
         .order-actions {
            flex-direction: column;
         }
      }
   </style>
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
         <p>Manage your orders and products</p>
         
         <?php if($user_id && !empty($user_orders)): ?>
         <div class="delete-all-container">
            <form method="POST">
               <button type="submit" name="delete_all_orders" class="delete-all-btn" onclick="return confirm('Are you sure you want to delete ALL your order history? This action cannot be undone.');">
                  <i class="fas fa-trash"></i> Delete All Orders
               </button>
            </form>
         </div>
         <?php endif; ?>
      </div>

      <div class="orders-container">
      <?php
         if($user_id == '') {
            echo '
            <div class="login-prompt">
               <h2 class="login-title">Please <a href="user_login.php" class="login-link">login</a> to view your orders</h2>
               <p>View and manage all your orders in one place</p>
            </div>
            ';
         } else if(empty($user_orders)) {
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
         } else {
            foreach($user_orders as $order) {
               echo OrderRenderer::renderOrderCard($order, $orderManager);
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
   
   // Add animation to order cards when they come into view
   const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
   };
   
   const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
         if (entry.isIntersecting) {
            entry.target.style.opacity = 1;
            entry.target.style.transform = 'translateY(0)';
         }
      });
   }, observerOptions);
   
   // Observe all order cards
   document.querySelectorAll('.order-card').forEach(card => {
      card.style.opacity = 0;
      card.style.transform = 'translateY(20px)';
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      observer.observe(card);
   });
});
</script>

</body>
</html>