<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;

if(!isset($admin_id)){
   header('location:admin_login.php');
   exit();
}

// Notification Class
class NotificationManager {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function createOrderNotification($user_id, $order_id, $message, $type = 'success') {
        try {
            // Check if order_id column exists
            $column_check = $this->conn->prepare("SHOW COLUMNS FROM `notifications` LIKE 'order_id'");
            $column_check->execute();
            
            if($column_check->rowCount() > 0) {
                $insert_notification = $this->conn->prepare(
                    "INSERT INTO `notifications` (user_id, order_id, message, type) VALUES (?, ?, ?, ?)"
                );
                $insert_notification->execute([$user_id, $order_id, $message, $type]);
            } else {
                // Fallback if order_id column doesn't exist
                $insert_notification = $this->conn->prepare(
                    "INSERT INTO `notifications` (user_id, message, type) VALUES (?, ?, ?)"
                );
                $insert_notification->execute([$user_id, $message, $type]);
            }
            return true;
        } catch(PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
}

// OrderManager Class
class OrderManager {
    private $conn;
    private $messages = [];
    private $notificationManager;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->notificationManager = new NotificationManager($db_connection);
    }
    
    public function getMessages() {
        return $this->messages;
    }
    
    public function addMessage($message) {
        $this->messages[] = $message;
    }
    
    public function updatePaymentStatus($order_id, $payment_status) {
        $order_id = filter_var($order_id, FILTER_SANITIZE_NUMBER_INT);
        $payment_status = filter_var($payment_status, FILTER_SANITIZE_STRING);
        
        try {
            // First get order details to know which user to notify
            $get_order = $this->conn->prepare("SELECT * FROM `orders` WHERE id = ?");
            $get_order->execute([$order_id]);
            $order = $get_order->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $this->addMessage('Order not found!');
                return false;
            }
            
            // Update payment status
            $update_payment = $this->conn->prepare("UPDATE `orders` SET payment_status = ? WHERE id = ?");
            $update_payment->execute([$payment_status, $order_id]);
            
            // Create notification for user
            $message = "";
            $type = "info";
            
            switch($payment_status) {
                case 'completed':
                    $message = "Your order #{$order_id} has been approved and is now being processed!";
                    $type = "success";
                    break;
                case 'pending':
                    $message = "Your order #{$order_id} status has been updated to pending.";
                    $type = "info";
                    break;
                case 'cancelled':
                    $message = "Your order #{$order_id} has been cancelled.";
                    $type = "danger";
                    break;
            }
            
            if ($message) {
                $this->notificationManager->createOrderNotification($order['user_id'], $order_id, $message, $type);
            }
            
            $this->addMessage('Payment status updated successfully!');
            return true;
        } catch(PDOException $e) {
            $this->addMessage('Error updating payment status: ' . $e->getMessage());
            return false;
        }
    }
    
    public function deleteOrder($order_id) {
        $order_id = filter_var($order_id, FILTER_SANITIZE_NUMBER_INT);
        
        try {
            // Get order details before deletion for notification
            $get_order = $this->conn->prepare("SELECT * FROM `orders` WHERE id = ?");
            $get_order->execute([$order_id]);
            $order = $get_order->fetch(PDO::FETCH_ASSOC);
            
            $delete_order = $this->conn->prepare("DELETE FROM `orders` WHERE id = ?");
            $delete_order->execute([$order_id]);
            
            // Notify user about order deletion
            if ($order) {
                $this->notificationManager->createOrderNotification(
                    $order['user_id'], 
                    $order_id, 
                    "Your order #{$order_id} has been deleted by admin.", 
                    "danger"
                );
            }
            
            $this->addMessage('Order deleted successfully!');
            return true;
        } catch(PDOException $e) {
            $this->addMessage('Error deleting order: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getOrders($search = '') {
        $search = filter_var($search, FILTER_SANITIZE_STRING);
        $search_param = "%$search%";
        
        try {
            $select_orders = $this->conn->prepare("SELECT * FROM `orders` 
                                                  WHERE id LIKE ? OR name LIKE ? OR number LIKE ? OR address LIKE ?
                                                  ORDER BY placed_on DESC");
            $select_orders->execute([$search_param, $search_param, $search_param, $search_param]);
            return $select_orders;
        } catch(PDOException $e) {
            $this->addMessage('Error fetching orders: ' . $e->getMessage());
            return false;
        }
    }
}

// Initialize OrderManager
$orderManager = new OrderManager($conn);

// Handle form submissions
if(isset($_POST['update_payment'])){
   $orderManager->updatePaymentStatus($_POST['order_id'], $_POST['payment_status']);
   // Refresh the page to show updated status
   header('location:placed_orders.php?search=' . urlencode($_GET['search'] ?? ''));
   exit();
}

if(isset($_GET['delete'])){
   $orderManager->deleteOrder($_GET['delete']);
   header('location:placed_orders.php');
   exit();
}

// Handle search
$search = '';
if(isset($_GET['search'])){
   $search = $_GET['search'];
}

// Get orders
$orders = $orderManager->getOrders($search);
$messages = $orderManager->getMessages();

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Placed Orders | Admin Panel</title>

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
   <!-- Bootstrap 5 -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   
   <!-- Custom CSS -->
   <link rel="stylesheet" href="../css/admin_style.css">

   <style>
      .order-card {
         border-radius: 10px;
         box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
         transition: transform 0.3s ease;
         margin-bottom: 20px;
         border: none;
      }
      .order-card:hover {
         transform: translateY(-5px);
      }
      .order-header {
         background-color: #f8f9fa;
         border-bottom: 1px solid #eee;
         padding: 15px 20px;
         border-radius: 10px 10px 0 0 !important;
      }
      .order-body {
         padding: 20px;
      }
      .order-footer {
         background-color: #f8f9fa;
         padding: 15px 20px;
         border-top: 1px solid #eee;
         border-radius: 0 0 10px 10px;
      }
      .order-id {
         font-weight: 700;
         color: #2c3e50;
      }
      .order-date {
         color: #7f8c8d;
         font-size: 0.9rem;
      }
      .customer-info {
         margin-bottom: 15px;
      }
      .customer-name {
         font-weight: 600;
         margin-bottom: 5px;
      }
      .customer-contact {
         color: #7f8c8d;
         font-size: 0.9rem;
      }
      .order-amount {
         font-weight: 700;
         font-size: 1.2rem;
         color: #27ae60;
      }
      .badge-pending {
         background-color: #f39c12;
      }
      .badge-completed {
         background-color: #2ecc71;
      }
      .products-count {
         background-color: #e0f7fa;
         color: #00acc1;
         padding: 5px 10px;
         border-radius: 20px;
         font-weight: 600;
      }
      .payment-method {
         display: inline-block;
         padding: 5px 10px;
         border-radius: 20px;
         background-color: #e3f2fd;
         color: #1976d2;
         font-weight: 500;
      }
      .search-box {
         max-width: 400px;
         margin-bottom: 20px;
      }
      .status-select {
         width: 120px;
         display: inline-block;
      }
      .action-btn {
         width: 36px;
         height: 36px;
         display: inline-flex;
         align-items: center;
         justify-content: center;
      }
      .message {
         position: fixed;
         top: 90px;
         right: 20px;
         padding: 15px 20px;
         border-radius: 8px;
         box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
         display: flex;
         align-items: center;
         gap: 10px;
         z-index: 1000;
         animation: slideIn 0.3s ease-out;
      }
      .message.success {
         background: #e6ffed;
         color: #43a047;
         border-left: 4px solid #43a047;
      }
      .message.error {
         background: #ffebee;
         color: #e53935;
         border-left: 4px solid #e53935;
      }
      @keyframes slideIn {
         from { transform: translateX(100%); opacity: 0; }
         to { transform: translateX(0); opacity: 1; }
      }
   </style>
</head>
<body>
<?php
// Include and display the admin header
include '../components/admin_header.php';
$adminHeader = new AdminDashboardHeader($conn, $admin_id, $messages);
$adminHeader->display();
?>

<?php
// Display messages
if(!empty($messages)){
   foreach($messages as $msg){
      echo '
      <div class="message success">
         <span>'.$msg.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<section class="orders py-4">
   <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
         <h1 class="heading">Placed Orders</h1>
         <form class="search-box" method="GET" action="">
            <div class="input-group">
               <input type="text" class="form-control" placeholder="Search orders..." name="search" value="<?= htmlspecialchars($search) ?>">
               <button class="btn btn-primary" type="submit">Search</button>
            </div>
         </form>
      </div>

      <?php
      if($orders && $orders->rowCount() > 0){
         while($fetch_orders = $orders->fetch(PDO::FETCH_ASSOC)){
            $status_class = $fetch_orders['payment_status'] == 'completed' ? 'completed' : 'pending';
      ?>
      <div class="card order-card mb-4">
         <div class="card-header order-header d-flex justify-content-between align-items-center">
            <div>
               <span class="order-id">Order #<?= $fetch_orders['id'] ?></span>
               <span class="order-date ms-2"><?= date('M d, Y h:i A', strtotime($fetch_orders['placed_on'])) ?></span>
            </div>
            <div>
               <span class="payment-method"><?= ucfirst($fetch_orders['method']) ?></span>
            </div>
         </div>
         <div class="card-body order-body">
            <div class="row">
               <div class="col-md-4 customer-info">
                  <div class="customer-name"><?= $fetch_orders['name'] ?></div>
                  <div class="customer-contact mb-2"><?= $fetch_orders['number'] ?></div>
                  <div class="text-muted small"><?= $fetch_orders['address'] ?></div>
               </div>
               <div class="col-md-3 d-flex align-items-center">
                  <div>
                     <div class="text-muted small">Total Amount</div>
                     <div class="order-amount">₹<?= number_format($fetch_orders['total_price'], 2) ?></div>
                  </div>
               </div>
               <div class="col-md-2 d-flex align-items-center">
                  <div>
                     <div class="text-muted small">Products</div>
                     <div class="products-count"><?= $fetch_orders['total_products'] ?></div>
                  </div>
               </div>
               <div class="col-md-3 d-flex align-items-center justify-content-end">
                  <form action="" method="post" class="me-2">
                     <input type="hidden" name="order_id" value="<?= $fetch_orders['id'] ?>">
                     <input type="hidden" name="update_payment" value="1">
                     <select name="payment_status" class="form-select status-select" onchange="this.form.submit()">
                        <option value="pending" <?= $fetch_orders['payment_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="completed" <?= $fetch_orders['payment_status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $fetch_orders['payment_status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                     </select>
                  </form>
                  <a href="placed_orders.php?delete=<?= $fetch_orders['id'] ?>" 
                     class="btn btn-danger action-btn" 
                     onclick="return confirm('Are you sure you want to delete this order?');">
                     <i class="fas fa-trash"></i>
                  </a>
               </div>
            </div>
         </div>
         <div class="card-footer order-footer d-flex justify-content-between align-items-center">
            <div>
               <span class="badge rounded-pill bg-<?= $status_class ?>">
                  <?= ucfirst($fetch_orders['payment_status']) ?>
               </span>
            </div>
            <div>
               <a href="order_details.php?id=<?= $fetch_orders['id'] ?>" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-eye me-1"></i> View Details
               </a>
            </div>
         </div>
      </div>
      <?php
         }
      } else {
      ?>
      <div class="text-center py-5">
         <div class="py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
            <h3 class="text-muted">No orders found</h3>
            <p class="text-muted"><?= $search ? 'No orders match your search criteria.' : 'There are currently no orders placed.' ?></p>
         </div>
      </div>
      <?php } ?>
   </div>
</section>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="../js/admin_script.js"></script>

<script>
   // Auto-close messages after 5 seconds
   setTimeout(() => {
      document.querySelectorAll('.message').forEach(msg => {
         msg.style.transition = 'opacity 0.5s ease';
         msg.style.opacity = '0';
         setTimeout(() => msg.remove(), 500);
      });
   }, 5000);
</script>

</body>
</html>