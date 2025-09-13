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
}

if(isset($_GET['delete_all'])){
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ?");
   $delete_wishlist_item->execute([$user_id]);
   header('location:wishlist.php');
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
   <link rel="stylesheet" href="css/wishlist.css">
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="wishlist-section">

   <div class="wishlist-header">
      <h1 class="wishlist-title">Your Wishlist</h1>
      <div class="wishlist-count">
         <?php
            $count_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
            $count_wishlist->execute([$user_id]);
            $total_items = $count_wishlist->rowCount();
         ?>
         <span><?= $total_items ?> items</span>
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
               <h3 class="item-name"><?= $fetch_wishlist['name']; ?></h3>
               <div class="item-price">रु-<?= number_format($fetch_wishlist['price'], 2); ?></div>
               <div class="item-availability">In Stock</div>
               <div class="item-actions">
                  <input type="hidden" name="pid" value="<?= $fetch_wishlist['pid']; ?>">
                  <input type="hidden" name="wishlist_id" value="<?= $fetch_wishlist['id']; ?>">
                  <input type="hidden" name="name" value="<?= $fetch_wishlist['name']; ?>">
                  <input type="hidden" name="price" value="<?= $fetch_wishlist['price']; ?>">
                  <input type="hidden" name="image" value="<?= $fetch_wishlist['image']; ?>">
                  
                  <div class="quantity-selector">
                     <label>Qty:</label>
                     <input type="number" name="qty" class="qty" min="1" max="99" value="1">
                  </div>
                  
                  <div class="action-buttons">
                     <button type="submit" name="add_to_cart" class="btn add-to-cart-btn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                     </button>
                     <button type="submit" name="delete" class="delete-btn" onclick="return confirm('Delete this from wishlist?');">
                        <i class="fas fa-trash"></i> Delete
                     </button>
                  </div>
               </div>
               <a href="quick_view.php?pid=<?= $fetch_wishlist['pid']; ?>" class="quick-view-link">
                  <i class="fas fa-eye"></i> View Details
               </a>
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
               <a href="shop.php" class="btn continue-shopping-btn">Continue Shopping</a>
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

</body>
</html>