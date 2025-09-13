<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Product Quick View</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- google fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Amazon+Ember:wght@400;500;700&display=swap" rel="stylesheet">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/quick_view.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="quick-view">

   <div class="breadcrumb">
      <a href="home.php">Home</a> &gt; 
      <a href="shop.php">Shop</a> &gt; 
      <span>Product Details</span>
   </div>

   <?php
     $pid = $_GET['pid'];
     $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?"); 
     $select_products->execute([$pid]);
     if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="post" class="product-box">
      <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
      <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
      <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
      <div class="product-container">
         <div class="product-gallery">
            <div class="main-image">
               <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="<?= $fetch_product['name']; ?>" id="zoomable-image">
               <div class="zoom-result"></div>
            </div>
            <div class="thumbnail-container">
               <div class="thumbnail active" data-image="<?= $fetch_product['image_01']; ?>">
                  <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="Thumbnail 1">
               </div>
               <div class="thumbnail" data-image="<?= $fetch_product['image_02']; ?>">
                  <img src="uploaded_img/<?= $fetch_product['image_02']; ?>" alt="Thumbnail 2">
               </div>
               <div class="thumbnail" data-image="<?= $fetch_product['image_03']; ?>">
                  <img src="uploaded_img/<?= $fetch_product['image_03']; ?>" alt="Thumbnail 3">
               </div>
            </div>
         </div>
         <div class="product-details">
            <h1 class="product-title"><?= $fetch_product['name']; ?></h1>
            
            <div class="price-section">
               <span class="price">रु-<?= $fetch_product['price']; ?></span>
               <span class="shipping-info">Free shipping</span>
            </div>
            
            <div class="availability">
               <i class="fas fa-check-circle"></i> In Stock (<?= rand(5, 50); ?> available)
            </div>
            
            <div class="product-highlights">
               <h3>About this item</h3>
               <div class="details"><?= $fetch_product['details']; ?></div>
            </div>
            
            <div class="quantity-selector">
               <label for="qty">Quantity:</label>
               <div class="qty-box">
                  <button type="button" class="qty-minus"><i class="fas fa-minus"></i></button>
                  <input type="number" name="qty" class="qty" min="1" max="99" value="1" id="qty">
                  <button type="button" class="qty-plus"><i class="fas fa-plus"></i></button>
               </div>
            </div>
            
            <div class="action-buttons">
               <button type="submit" class="add-to-cart-btn" name="add_to_cart">
                  <i class="fas fa-shopping-cart"></i> Add to Cart
               </button>
               
               <button type="submit" class="wishlist-btn" name="add_to_wishlist">
                  <i class="fas fa-heart"></i> Add to Wishlist
               </button>
            </div>
            
            <div class="delivery-info">
               <div class="delivery-option">
                  <i class="fas fa-map-marker-alt"></i>
                  <span>Deliver to <strong>Bagmati Province</strong></span>
               </div>
               <div class="delivery-option">
                  <i class="fas fa-undo"></i>
                  <span>Free returns within 30 days</span>
               </div>
            </div>
         </div>
      </div>
   </form>
   <?php
      }
   }else{
      echo '<div class="empty-product">
               <i class="fas fa-exclamation-circle"></i>
               <p>No product found!</p>
               <a href="shop.php" class="btn">Continue Shopping</a>
            </div>';
   }
   ?>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
<script src="js/quick_view.js"></script>

</body>
</html>