<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
};

if(isset($_POST['delete'])){
   $cart_id = $_POST['cart_id'];
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
   $delete_cart_item->execute([$cart_id]);
}

if(isset($_GET['delete_all'])){
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
   $delete_cart_item->execute([$user_id]);
   header('location:cart.php');
}

if(isset($_POST['update_qty'])){
   $cart_id = $_POST['cart_id'];
   $qty = $_POST['qty'];
   $qty = filter_var($qty, FILTER_SANITIZE_STRING);
   $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
   $update_qty->execute([$qty, $cart_id]);
   $message[] = 'cart quantity updated';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shopping Cart | YourStore</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- Google Fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      :root {
         --primary: #ff9900;
         --primary-dark: #ff8c00;
         --secondary: #232f3e;
         --light: #f7f7f7;
         --dark: #131921;
         --gray: #ddd;
         --text: #333;
         --text-light: #555;
      }
      
      body {
         font-family: 'Poppins', sans-serif;
         background-color: #f5f5f5;
         color: var(--text);
      }
      
      .shopping-cart {
         max-width: 1200px;
         margin: 0 auto;
         padding: 2rem 1rem;
      }
      
      .cart-header {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-bottom: 2rem;
         padding-bottom: 1rem;
         border-bottom: 1px solid var(--gray);
      }
      
      .cart-header h3 {
         font-size: 2.2rem;
         font-weight: 600;
         color: var(--secondary);
         margin: 0;
      }
      
      .item-count {
         color: var(--text-light);
         font-size: 1.1rem;
      }
      
      .cart-container {
         display: flex;
         gap: 2rem;
         align-items: flex-start;
      }
      
      .cart-items {
         flex: 1 1 70%;
         background: white;
         border-radius: 8px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.05);
         padding: 1.5rem;
      }
      
      .cart-summary {
         flex: 1 1 30%;
         background: white;
         border-radius: 8px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.05);
         padding: 1.5rem;
         position: sticky;
         top: 1rem;
      }
      
      .cart-item {
         display: flex;
         gap: 1.5rem;
         padding: 1.5rem 0;
         border-bottom: 1px solid #f0f0f0;
      }
      
      .cart-item:last-child {
         border-bottom: none;
      }
      
      .cart-item-img {
         width: 120px;
         height: 120px;
         object-fit: contain;
         border-radius: 4px;
         border: 1px solid #f0f0f0;
         padding: 0.5rem;
      }
      
      .cart-item-details {
         flex: 1;
      }
      
      .cart-item-title {
         font-size: 1.1rem;
         font-weight: 500;
         margin-bottom: 0.5rem;
         color: var(--text);
      }
      
      .cart-item-title:hover {
         color: var(--primary);
      }
      
      .cart-item-price {
         font-size: 1.2rem;
         font-weight: 600;
         color: var(--text);
         margin-bottom: 0.5rem;
      }
      
      .cart-item-actions {
         display: flex;
         flex-direction: column;
         align-items: flex-end;
         min-width: 150px;
      }
      
      .qty-control {
         display: flex;
         align-items: center;
         gap: 0.5rem;
         margin-bottom: 1rem;
      }
      
      .qty-control input {
         width: 60px;
         text-align: center;
         padding: 0.5rem;
         border: 1px solid var(--gray);
         border-radius: 4px;
         font-size: 1rem;
      }
      
      .update-btn {
         background: none;
         border: none;
         color: var(--primary);
         cursor: pointer;
         font-size: 1rem;
      }
      
      .update-btn:hover {
         color: var(--primary-dark);
      }
      
      .delete-btn {
         background: none;
         border: none;
         color: #ff4d4d;
         cursor: pointer;
         font-size: 0.9rem;
         display: flex;
         align-items: center;
         gap: 0.3rem;
      }
      
      .delete-btn:hover {
         color: #e60000;
         text-decoration: underline;
      }
      
      .sub-total {
         font-size: 1.1rem;
         font-weight: 500;
         color: var(--text);
         margin-bottom: 1rem;
      }
      
      .summary-title {
         font-size: 1.3rem;
         font-weight: 600;
         margin-bottom: 1.5rem;
         color: var(--secondary);
         padding-bottom: 1rem;
         border-bottom: 1px solid #f0f0f0;
      }
      
      .summary-details {
         margin-bottom: 1.5rem;
      }
      
      .summary-row {
         display: flex;
         justify-content: space-between;
         margin-bottom: 0.8rem;
      }
      
      .summary-label {
         color: var(--text-light);
      }
      
      .summary-value {
         font-weight: 500;
      }
      
      .grand-total {
         font-size: 1.2rem;
         font-weight: 600;
         padding: 1rem 0;
         border-top: 1px solid #f0f0f0;
         border-bottom: 1px solid #f0f0f0;
         margin: 1rem 0;
      }
      
      .checkout-btn {
         background-color: var(--primary);
         color: white;
         border: none;
         padding: 0.8rem;
         border-radius: 4px;
         font-size: 1rem;
         font-weight: 500;
         cursor: pointer;
         width: 100%;
         text-align: center;
         transition: background 0.3s;
         margin-bottom: 1rem;
      }
      
      .checkout-btn:hover {
         background-color: var(--primary-dark);
      }
      
      .secondary-btn {
         background-color: white;
         color: var(--text);
         border: 1px solid var(--gray);
         padding: 0.8rem;
         border-radius: 4px;
         font-size: 1rem;
         font-weight: 500;
         cursor: pointer;
         width: 100%;
         text-align: center;
         transition: all 0.3s;
      }
      
      .secondary-btn:hover {
         background-color: #f7f7f7;
         border-color: #ccc;
      }
      
      .empty-cart {
         text-align: center;
         padding: 3rem 0;
      }
      
      .empty-cart-icon {
         font-size: 5rem;
         color: #ccc;
         margin-bottom: 1rem;
      }
      
      .empty-cart-text {
         font-size: 1.2rem;
         color: var(--text-light);
         margin-bottom: 1.5rem;
      }
      
      .shop-now-btn {
         background-color: var(--primary);
         color: white;
         padding: 0.8rem 2rem;
         border-radius: 4px;
         text-decoration: none;
         font-weight: 500;
         display: inline-block;
      }
      
      .shop-now-btn:hover {
         background-color: var(--primary-dark);
      }
      
      @media (max-width: 768px) {
         .cart-container {
            flex-direction: column;
         }
         
         .cart-items, .cart-summary {
            width: 100%;
         }
         
         .cart-item {
            flex-direction: column;
            align-items: center;
            text-align: center;
         }
         
         .cart-item-actions {
            align-items: center;
            margin-top: 1rem;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="shopping-cart">
   <div class="cart-header">
      <h3>Shopping Cart</h3>
      <?php
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         $item_count = $select_cart->rowCount();
      ?>
      <div class="item-count"><?= $item_count ?> item<?= $item_count != 1 ? 's' : '' ?></div>
   </div>

   <div class="cart-container">
      <div class="cart-items">
      <?php
         $grand_total = 0;
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
               $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
               $grand_total += $sub_total;
      ?>
         <form action="" method="post" class="cart-item">
            <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
            <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="<?= $fetch_cart['name']; ?>" class="cart-item-img">
            
            <div class="cart-item-details">
               <a href="quick_view.php?pid=<?= $fetch_cart['pid']; ?>" class="cart-item-title"><?= $fetch_cart['name']; ?></a>
               <div class="cart-item-price">₹<?= number_format($fetch_cart['price'], 2); ?></div>
               <div>In stock</div>
               <div>Eligible for FREE Shipping</div>
            </div>
            
            <div class="cart-item-actions">
               <div class="qty-control">
                  <label for="qty">Qty:</label>
                  <input type="number" name="qty" class="qty" min="1" max="99" value="<?= $fetch_cart['quantity']; ?>">
                  <button type="submit" class="update-btn" name="update_qty" title="Update Quantity">
                     <i class="fas fa-sync-alt"></i>
                  </button>
               </div>
               
               <div class="sub-total">Subtotal: ₹<?= number_format($sub_total, 2); ?></div>
               
               <button type="submit" onclick="return confirm('Delete this item from cart?');" class="delete-btn" name="delete">
                  <i class="fas fa-trash"></i> Remove
               </button>
            </div>
         </form>
      <?php
            }
         } else {
      ?>
         <div class="empty-cart">
            <div class="empty-cart-icon">
               <i class="fas fa-shopping-cart"></i>
            </div>
            <h3>Your Cart is Empty</h3>
            <p class="empty-cart-text">Looks like you haven't added anything to your cart yet</p>
            <a href="shop.php" class="shop-now-btn">Shop Now</a>
         </div>
      <?php
         }
      ?>
      </div>

      <?php if($select_cart->rowCount() > 0) { ?>
      <div class="cart-summary">
         <h3 class="summary-title">Order Summary</h3>
         
         <div class="summary-details">
            <div class="summary-row">
               <span class="summary-label">Subtotal (<?= $item_count ?> item<?= $item_count != 1 ? 's' : '' ?>):</span>
               <span class="summary-value">₹<?= number_format($grand_total, 2); ?></span>
            </div>
            <div class="summary-row">
               <span class="summary-label">Delivery:</span>
               <span class="summary-value">FREE</span>
            </div>
         </div>
         
         <div class="grand-total">
            <div class="summary-row">
               <span>Total:</span>
               <span>₹<?= number_format($grand_total, 2); ?></span>
            </div>
         </div>
         
         <a href="checkout.php" class="checkout-btn <?= ($grand_total > 1)?'':'disabled'; ?>">
            Proceed to Checkout
         </a>
         
         <a href="shop.php" class="secondary-btn">
            Continue Shopping
         </a>
         
         <a href="cart.php?delete_all" class="delete-btn" onclick="return confirm('Delete all items from cart?');" style="justify-content: center; margin-top: 1rem;">
            <i class="fas fa-trash"></i> Empty Cart
         </a>
      </div>
      <?php } ?>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>