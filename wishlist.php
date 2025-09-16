<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
};

include 'components/wishlist_cart.php';

if(isset($_POST['delete'])){
   $wishlist_id = $_POST['wishlist_id'];
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE id = ?");
   $delete_wishlist_item->execute([$wishlist_id]);
   $message[] = 'Item removed from wishlist!';
}

if(isset($_GET['delete_all'])){
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ?");
   $delete_wishlist_item->execute([$user_id]);
   header('location:wishlist.php');
   $message[] = 'All items removed from wishlist!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Your Wishlist</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <!-- wishlist specific css -->
   <style>
      :root {
         --primary: #3a86ff;
         --secondary: #ff006e;
         --accent: #8338ec;
         --light: #f8f9fa;
         --dark: #212529;
         --success: #28a745;
         --border: #dee2e6;
         --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
         --transition: all 0.3s ease;
      }
      
      .wishlist-section {
         padding: 2rem 9%;
         background: #f9fafb;
         min-height: 80vh;
      }
      
      .wishlist-header {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-bottom: 2.5rem;
         flex-wrap: wrap;
         gap: 1rem;
      }
      
      .wishlist-title {
         font-size: 2.2rem;
         color: var(--dark);
         font-weight: 700;
         position: relative;
         padding-bottom: 0.5rem;
      }
      
      .wishlist-title::after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 0;
         width: 60px;
         height: 4px;
         background: var(--secondary);
         border-radius: 2px;
      }
      
      .wishlist-count {
         background: white;
         padding: 0.6rem 1.2rem;
         border-radius: 50px;
         box-shadow: var(--shadow);
         font-weight: 600;
         color: var(--dark);
         display: flex;
         align-items: center;
         gap: 0.5rem;
      }
      
      .wishlist-count span {
         color: var(--secondary);
         font-size: 1.1rem;
      }
      
      .wishlist-container {
         display: grid;
         grid-template-columns: 1fr 320px;
         gap: 2rem;
      }
      
      @media (max-width: 991px) {
         .wishlist-container {
            grid-template-columns: 1fr;
         }
      }
      
      .wishlist-items {
         display: flex;
         flex-direction: column;
         gap: 1.5rem;
      }
      
      .wishlist-item {
         background: white;
         border-radius: 12px;
         overflow: hidden;
         box-shadow: var(--shadow);
         transition: var(--transition);
         display: flex;
         padding: 1.5rem;
         gap: 1.5rem;
      }
      
      .wishlist-item:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
      }
      
      @media (max-width: 768px) {
         .wishlist-item {
            flex-direction: column;
         }
      }
      
      .item-image {
         flex: 0 0 180px;
         height: 180px;
         border-radius: 10px;
         overflow: hidden;
      }
      
      .item-image img {
         width: 100%;
         height: 100%;
         object-fit: cover;
         transition: var(--transition);
      }
      
      .wishlist-item:hover .item-image img {
         transform: scale(1.05);
      }
      
      .item-details {
         flex: 1;
         display: flex;
         flex-direction: column;
         justify-content: space-between;
      }
      
      .item-name {
         font-size: 1.3rem;
         font-weight: 600;
         color: var(--dark);
         margin-bottom: 0.5rem;
      }
      
      .item-price {
         font-size: 1.4rem;
         font-weight: 700;
         color: var(--primary);
         margin-bottom: 0.8rem;
      }
      
      .item-availability {
         display: inline-flex;
         align-items: center;
         gap: 0.4rem;
         color: var(--success);
         font-weight: 500;
         margin-bottom: 1.2rem;
      }
      
      .item-availability::before {
         content: '';
         width: 10px;
         height: 10px;
         background: var(--success);
         border-radius: 50%;
      }
      
      .item-actions {
         display: flex;
         flex-wrap: wrap;
         gap: 1rem;
         margin-bottom: 1rem;
         align-items: center;
      }
      
      .quantity-selector {
         display: flex;
         align-items: center;
         gap: 0.5rem;
         margin-right: auto;
      }
      
      .quantity-selector label {
         font-weight: 500;
         color: var(--dark);
      }
      
      .quantity-selector .qty {
         width: 70px;
         padding: 0.5rem;
         border: 1px solid var(--border);
         border-radius: 5px;
         text-align: center;
      }
      
      .action-buttons {
         display: flex;
         gap: 0.8rem;
      }
      
      .add-to-cart-btn {
         background: var(--primary);
         color: white;
         border: none;
         padding: 0.6rem 1.2rem;
         border-radius: 6px;
         cursor: pointer;
         font-weight: 500;
         transition: var(--transition);
         display: flex;
         align-items: center;
         gap: 0.5rem;
      }
      
      .add-to-cart-btn:hover {
         background: #2563eb;
         transform: translateY(-2px);
      }
      
      .delete-btn {
         background: #f8f9fa;
         color: #dc3545;
         border: 1px solid #f1f3f5;
         padding: 0.6rem 1rem;
         border-radius: 6px;
         cursor: pointer;
         font-weight: 500;
         transition: var(--transition);
         display: flex;
         align-items: center;
         gap: 0.5rem;
      }
      
      .delete-btn:hover {
         background: #fff5f5;
         color: #c53030;
         border-color: #fed7d7;
      }
      
      .quick-view-link {
         display: inline-flex;
         align-items: center;
         gap: 0.5rem;
         color: var(--accent);
         font-weight: 500;
         transition: var(--transition);
         margin-top: auto;
         align-self: flex-start;
      }
      
      .quick-view-link:hover {
         color: #6c2bd9;
         text-decoration: underline;
      }
      
      .empty-wishlist {
         text-align: center;
         padding: 3rem;
         background: white;
         border-radius: 12px;
         box-shadow: var(--shadow);
      }
      
      .empty-icon {
         font-size: 4rem;
         color: #e5e7eb;
         margin-bottom: 1.5rem;
      }
      
      .empty-wishlist h3 {
         font-size: 1.5rem;
         color: var(--dark);
         margin-bottom: 1rem;
      }
      
      .empty-wishlist p {
         color: #6b7280;
         margin-bottom: 2rem;
      }
      
      .wishlist-summary {
         position: sticky;
         top: 2rem;
         height: fit-content;
      }
      
      .summary-card {
         background: white;
         border-radius: 12px;
         padding: 1.8rem;
         box-shadow: var(--shadow);
      }
      
      .summary-card h3 {
         font-size: 1.4rem;
         color: var(--dark);
         margin-bottom: 1.5rem;
         padding-bottom: 0.8rem;
         border-bottom: 1px solid var(--border);
      }
      
      .summary-row {
         display: flex;
         justify-content: space-between;
         margin-bottom: 1.2rem;
      }
      
      .summary-row span:first-child {
         color: #6b7280;
      }
      
      .summary-row span:last-child {
         font-weight: 600;
         color: var(--dark);
      }
      
      .summary-actions {
         display: flex;
         flex-direction: column;
         gap: 0.8rem;
         margin-top: 1.5rem;
      }
      
      .continue-shopping-btn {
         background: white;
         color: var(--primary);
         border: 1px solid var(--primary);
         padding: 0.8rem;
         border-radius: 6px;
         text-align: center;
         font-weight: 500;
         transition: var(--transition);
      }
      
      .continue-shopping-btn:hover {
         background: #eff6ff;
      }
      
      .delete-all-btn {
         background: #fef2f2;
         color: #dc2626;
         border: 1px solid #fecaca;
         padding: 0.8rem;
         border-radius: 6px;
         text-align: center;
         font-weight: 500;
         transition: var(--transition);
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 0.5rem;
      }
      
      .delete-all-btn:hover {
         background: #fef2f2;
         border-color: #fca5a5;
      }
      
      .delete-all-btn.disabled {
         opacity: 0.5;
         pointer-events: none;
      }
      
      .message {
         position: sticky;
         top: 0;
         left: 0;
         right: 0;
         padding: 15px 10px;
         background-color: var(--white);
         text-align: center;
         z-index: 1000;
         box-shadow: var(--box-shadow);
         font-size: 20px;
         color: var(--black);
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

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

<section class="wishlist-section">

   <div class="wishlist-header">
      <h1 class="wishlist-title">Your Wishlist</h1>
      <div class="wishlist-count">
         <i class="fas fa-heart"></i>
         <?php
            $count_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
            $count_wishlist->execute([$user_id]);
            $total_items = $count_wishlist->rowCount();
         ?>
         <span><?= $total_items ?> item<?= $total_items != 1 ? 's' : '' ?></span>
      </div>
   </div>

   <div class="wishlist-container">
      <div class="wishlist-items">
         <?php
            $grand_total = 0;
            $select_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
            $select_wishlist->execute([$user_id]);
            if($select_wishlist->rowCount() > 0){
               while($fetch_wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC)){
                  $grand_total += $fetch_wishlist['price'];  
         ?>
         <form action="" method="post" class="wishlist-item">
            <div class="item-image">
               <img src="uploaded_img/<?= $fetch_wishlist['image']; ?>" alt="<?= $fetch_wishlist['name']; ?>">
            </div>
            <div class="item-details">
               <div>
                  <h3 class="item-name"><?= $fetch_wishlist['name']; ?></h3>
                  <div class="item-price">रु-<?= number_format($fetch_wishlist['price'], 2); ?></div>
                  <div class="item-availability">In Stock</div>
               </div>
               
               <div class="item-actions">
                  <div class="quantity-selector">
                     <label for="qty_<?= $fetch_wishlist['id']; ?>">Qty:</label>
                     <input type="number" id="qty_<?= $fetch_wishlist['id']; ?>" name="qty" class="qty" min="1" max="99" value="1">
                  </div>
                  
                  <div class="action-buttons">
                     <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                     </button>
                     <button type="submit" name="delete" class="delete-btn">
                        <i class="fas fa-trash"></i> Delete
                     </button>
                  </div>
               </div>
               
               <a href="quick_view.php?pid=<?= $fetch_wishlist['pid']; ?>" class="quick-view-link">
                  <i class="fas fa-eye"></i> View Details
               </a>
               
               <input type="hidden" name="pid" value="<?= $fetch_wishlist['pid']; ?>">
               <input type="hidden" name="wishlist_id" value="<?= $fetch_wishlist['id']; ?>">
               <input type="hidden" name="name" value="<?= $fetch_wishlist['name']; ?>">
               <input type="hidden" name="price" value="<?= $fetch_wishlist['price']; ?>">
               <input type="hidden" name="image" value="<?= $fetch_wishlist['image']; ?>">
            </div>
         </form>
         <?php
               }
            }else{
               echo '<div class="empty-wishlist">
                  <div class="empty-icon"><i class="far fa-heart"></i></div>
                  <h3>Your Wishlist is Empty</h3>
                  <p>You have no items in your wishlist. Start adding!</p>
                  <a href="shop.php" class="btn">Continue Shopping</a>
               </div>';
            }
         ?>
      </div>

      <?php if($select_wishlist->rowCount() > 0): ?>
      <div class="wishlist-summary">
         <div class="summary-card">
            <h3>Wishlist Summary</h3>
            <div class="summary-row">
               <span>Items (<?= $total_items ?>)</span>
               <span>रु-<?= number_format($grand_total, 2); ?></span>
            </div>
            <div class="summary-actions">
               <a href="shop.php" class="continue-shopping-btn">Continue Shopping</a>
               <a href="wishlist.php?delete_all" class="delete-all-btn <?= ($grand_total > 1)?'':'disabled'; ?>" onclick="return confirm('Delete all from wishlist?');">
                  <i class="fas fa-trash"></i> Delete All Items
               </a>
            </div>
         </div>
      </div>
      <?php endif; ?>
   </div>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
<script>
   // Add confirmation for delete buttons
   document.querySelectorAll('.delete-btn').forEach(button => {
      button.addEventListener('click', function(e) {
         if(!confirm('Delete this from wishlist?')) {
            e.preventDefault();
         }
      });
   });
   
   // Add animation when items are added to cart
   document.querySelectorAll('.add-to-cart-btn').forEach(button => {
      button.addEventListener('click', function() {
         this.innerHTML = '<i class="fas fa-check"></i> Added!';
         setTimeout(() => {
            this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
         }, 2000);
      });
   });
</script>

</body>
</html>