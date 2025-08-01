<?php
include 'components/connect.php';

session_start();
if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
}

// Handle add to cart functionality
if(isset($_POST['add_to_cart'])){
   // Sanitize inputs
   $pid = filter_var($_POST['pid'], FILTER_SANITIZE_STRING);
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
   $image = filter_var($_POST['image'], FILTER_SANITIZE_STRING);
   $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);
   
   // Check if cart already exists
   if(isset($_SESSION['cart'])){
      $product_exists = false;
      
      // Check if this product is already in cart
      foreach($_SESSION['cart'] as &$item){
         if($item['pid'] == $pid){
            $item['qty'] += $qty;
            $product_exists = true;
            $message[] = 'Product quantity updated in cart!';
            break;
         }
      }
      
      // Product doesn't exist - add new item
      if(!$product_exists){
         $cart_item = array(
            'pid' => $pid,
            'name' => $name,
            'price' => $price,
            'image' => $image,
            'qty' => $qty
         );
         $_SESSION['cart'][] = $cart_item;
         $message[] = 'Product added to cart!';
      }
   }else{
      // Cart doesn't exist - create it with this item
      $cart_item = array(
         'pid' => $pid,
         'name' => $name,
         'price' => $price,
         'image' => $image,
         'qty' => $qty
      );
      $_SESSION['cart'] = array($cart_item);
      $message[] = 'Product added to cart!';
   }
}

include 'components/wishlist_cart.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shop | Modern </title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- google fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      /* Modern color scheme */
      :root {
         --primary: #ff6b6b;
         --primary-light: #ff8e8e;
         --secondary: #5f27cd;
         --secondary-light: #7d5fff;
         --dark: #2f3542;
         --dark-light: #57606f;
         --light: #f1f2f6;
         --light-dark: #dfe4ea;
         --success: #1dd1a1;
         --warning: #ff9f43;
      }
      
      /* Hero section */
      .shop-hero {
         background: linear-gradient(135deg, rgba(95,39,205,0.9) 0%, rgba(255,107,107,0.9) 100%), 
                     url('https://images.unsplash.com/photo-1469334031218-e382a71b716b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
         background-size: cover;
         background-position: center;
         height: 300px;
         display: flex;
         align-items: center;
         justify-content: center;
         text-align: center;
         color: white;
         margin-bottom: 3rem;
      }
      
      .shop-hero-content h1 {
         font-family: 'Playfair Display', serif;
         font-size: 3.5rem;
         margin-bottom: 1rem;
         text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
      }
      
      .shop-hero-content p {
         font-size: 1.2rem;
         max-width: 700px;
         margin: 0 auto;
      }
      
      /* Category filters */
      .category-filters {
         display: flex;
         justify-content: center;
         flex-wrap: wrap;
         gap: 1rem;
         margin-bottom: 3rem;
         padding: 0 2rem;
      }
      
      .category-btn {
         padding: 0.7rem 1.5rem;
         border: 2px solid var(--primary);
         background: white;
         color: var(--primary);
         border-radius: 30px;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.3s ease;
      }
      
      .category-btn:hover, .category-btn.active {
         background: var(--primary);
         color: white;
      }
      
      /* Heading style */
      .heading {
         font-family: 'Playfair Display', serif;
         font-weight: 600;
         color: var(--dark);
         position: relative;
         margin-bottom: 3rem;
         text-align: center;
         font-size: 2.5rem;
      }
      
      .heading:after {
         content: '';
         position: absolute;
         bottom: -10px;
         left: 50%;
         transform: translateX(-50%);
         width: 80px;
         height: 3px;
         background: var(--primary);
      }
      
      /* Products section */
      .products {
         padding: 5rem 9%;
         background: #f9f9f9;
      }
      
      .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
         gap: 2.5rem;
      }
      
      .products .box {
         background: #fff;
         border-radius: 15px;
         overflow: hidden;
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
         transition: all 0.3s ease;
         position: relative;
      }
      
      .products .box:hover {
         transform: translateY(-10px);
         box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
      }
      
      .products .box .product-badge {
         position: absolute;
         top: 15px;
         left: 15px;
         background: var(--primary);
         color: white;
         padding: 0.3rem 0.8rem;
         border-radius: 30px;
         font-size: 0.8rem;
         font-weight: 600;
         z-index: 2;
      }
      
      .products .box .product-badge.sale {
         background: var(--warning);
      }
      
      .products .box .product-badge.new {
         background: var(--success);
      }
      
      .products .box img {
         width: 100%;
         height: 250px;
         object-fit: cover;
         transition: all 0.5s ease;
      }
      
      .products .box:hover img {
         transform: scale(1.05);
      }
      
      .products .box .product-info {
         padding: 1.5rem;
      }
      
      .products .box .name {
         font-size: 1.1rem;
         color: var(--dark);
         margin-bottom: 0.5rem;
         font-weight: 600;
         transition: color 0.3s ease;
      }
      
      .products .box:hover .name {
         color: var(--primary);
      }
      
      .products .box .category {
         font-size: 0.8rem;
         color: var(--dark-light);
         margin-bottom: 0.5rem;
         display: block;
      }
      
      .products .box .rating {
         color: var(--warning);
         font-size: 0.9rem;
         margin-bottom: 0.8rem;
      }
      
      .products .box .flex {
         display: flex;
         align-items: center;
         justify-content: space-between;
         margin-top: 1rem;
      }
      
      .products .box .price {
         font-weight: 700;
         color: var(--primary);
         font-size: 1.3rem;
      }
      
      .products .box .price span {
         font-size: 1rem;
         color: #777;
         text-decoration: line-through;
         margin-left: 0.5rem;
      }
      
      .products .box .qty {
         width: 60px;
         padding: 0.5rem;
         border: 1px solid #ddd;
         border-radius: 5px;
         text-align: center;
      }
      
      .products .box .btn {
         display: block;
         width: calc(100% - 3rem);
         margin: 0 1.5rem 1.5rem;
         background: var(--primary);
         color: #fff;
         border: none;
         border-radius: 5px;
         padding: 0.8rem;
         cursor: pointer;
         font-weight: 600;
         transition: all 0.3s ease;
         text-align: center;
      }
      
      .products .box .btn:hover {
         background: var(--secondary);
         transform: translateY(-2px);
      }
      
      .products .box .btn i {
         margin-right: 8px;
      }
      
      .products .box .action-buttons {
         position: absolute;
         top: 15px;
         right: 15px;
         display: flex;
         flex-direction: column;
         gap: 0.5rem;
         z-index: 2;
      }
      
      .products .box .action-buttons button {
         background: rgba(255, 255, 255, 0.9);
         border: none;
         width: 35px;
         height: 35px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         transition: all 0.3s ease;
         color: var(--dark);
         font-size: 1rem;
         box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }
      
      .products .box .action-buttons button:hover {
         background: var(--primary);
         color: white;
         transform: scale(1.1);
      }
      
      /* Empty state */
      .empty {
         text-align: center;
         grid-column: 1/-1;
         padding: 5rem 0;
      }
      
      .empty img {
         width: 200px;
         margin-bottom: 2rem;
         opacity: 0.7;
      }
      
      .empty h3 {
         font-size: 1.5rem;
         color: var(--dark);
         margin-bottom: 1rem;
      }
      
      .empty p {
         font-size: 1.1rem;
         color: var(--dark-light);
         margin-bottom: 1.5rem;
      }
      
      .empty .btn {
         display: inline-block;
         padding: 0.8rem 2rem;
         background: var(--primary);
         color: white;
         border-radius: 5px;
         font-weight: 600;
         transition: all 0.3s ease;
      }
      
      .empty .btn:hover {
         background: var(--secondary);
         transform: translateY(-2px);
      }
      
      /* Featured banner */
      .featured-banner {
         background: linear-gradient(to right, var(--secondary), var(--primary));
         color: white;
         padding: 2rem;
         border-radius: 10px;
         margin-bottom: 3rem;
         display: flex;
         align-items: center;
         justify-content: space-between;
      }
      
      .featured-banner .content h3 {
         font-size: 1.8rem;
         margin-bottom: 0.5rem;
      }
      
      .featured-banner .content p {
         font-size: 1.1rem;
         opacity: 0.9;
      }
      
      .featured-banner .btn {
         background: white;
         color: var(--primary);
         padding: 0.8rem 2rem;
         border-radius: 30px;
         font-weight: 600;
         transition: all 0.3s ease;
      }
      
      .featured-banner .btn:hover {
         transform: translateY(-3px);
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      }
      
      /* Notification message */
      .notification {
         position: fixed;
         top: 20px;
         right: 20px;
         background: var(--success);
         color: white;
         padding: 1rem 1.5rem;
         border-radius: 5px;
         box-shadow: 0 3px 10px rgba(0,0,0,0.2);
         z-index: 1000;
         display: flex;
         align-items: center;
         gap: 10px;
         transform: translateX(150%);
         transition: transform 0.3s ease;
      }
      
      .notification.show {
         transform: translateX(0);
      }
      
      .notification.error {
         background: var(--primary);
      }
      
      /* Responsive adjustments */
      @media (max-width: 1200px) {
         .products {
            padding: 4rem 5%;
         }
      }
      
      @media (max-width: 991px) {
         .shop-hero {
            height: 250px;
         }
         
         .shop-hero-content h1 {
            font-size: 2.8rem;
         }
         
         .products {
            padding: 3rem 2rem;
         }
         
         .featured-banner {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
         }
      }
      
      @media (max-width: 768px) {
         .box-container {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
         }
         
         .shop-hero-content h1 {
            font-size: 2.3rem;
         }
      }
      
      @media (max-width: 450px) {
         .heading {
            font-size: 2rem;
         }
         
         .box-container {
            grid-template-columns: 1fr;
         }
         
         .shop-hero {
            height: 200px;
         }
         
         .shop-hero-content h1 {
            font-size: 2rem;
         }
         
         .shop-hero-content p {
            font-size: 1rem;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- Notification Message -->
<?php if(isset($message)): ?>
   <div class="notification <?php echo strpos($message[0], 'added') !== false ? '' : 'error' ?>" id="notification">
      <i class="fas fa-<?php echo strpos($message[0], 'added') !== false ? 'check-circle' : 'exclamation-circle' ?>"></i>
      <span><?php echo $message[0]; ?></span>
   </div>
<?php endif; ?>

<!-- Hero Section -->
<section class="shop-hero">
   <div class="shop-hero-content">
      <h1>Shop Our Collection</h1>
      <p>Discover handpicked items that blend style, comfort, and quality for your everyday life</p>
   </div>
</section>

<!-- Featured Banner -->
<section class="products">
   <div class="featured-banner">
      <div class="content">
         <h3>Summer Sale!</h3>
         <p>Get 30% off on selected items. Limited time offer.</p>
      </div>
      <a href="#" class="btn">Shop Now</a>
   </div>

   <h1 class="heading">Our Products</h1>

   <div class="box-container">

   <?php
     $select_products = $conn->prepare("SELECT * FROM `products` LIMIT 12"); 
     $select_products->execute();
     if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
         
         // Add some product badges for demo purposes
         $badge = '';
         $original_price = '';
         $discount_price = $fetch_product['price'];
         
         // Randomly assign badges for demo
         $random_badge = rand(1, 3);
         if($random_badge == 1) {
            $badge = '<span class="product-badge sale">SALE</span>';
            $original_price = number_format($fetch_product['price'] * 1.3, 2);
            $discount_price = $fetch_product['price'];
         } elseif($random_badge == 2) {
            $badge = '<span class="product-badge new">NEW</span>';
         }
         
         // Generate random rating (for demo)
         $rating = rand(3, 5);
         $rating_stars = str_repeat('<i class="fas fa-star"></i>', $rating);
         if($rating < 5) {
            $rating_stars .= str_repeat('<i class="far fa-star"></i>', 5 - $rating);
         }
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= htmlspecialchars($fetch_product['id']); ?>">
      <input type="hidden" name="name" value="<?= htmlspecialchars($fetch_product['name']); ?>">
      <input type="hidden" name="price" value="<?= htmlspecialchars($fetch_product['price']); ?>">
      <input type="hidden" name="image" value="<?= htmlspecialchars($fetch_product['image_01']); ?>">
      
      <?= $badge ?>
      
      <div class="action-buttons">
         <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
         <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
      </div>
      
      <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="<?= $fetch_product['name']; ?>">
      
      <div class="product-info">
         <span class="category"><?= ucfirst($fetch_product['category'] ?? 'Fashion'); ?></span>
         <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="name"><?= $fetch_product['name']; ?></a>
         <div class="rating">
            <?= $rating_stars ?> (<?= rand(15, 120); ?>)
         </div>
         
         <div class="flex">
            <div class="price">
               ₹<?= number_format($discount_price, 2); ?>
               <?php if($original_price): ?>
                  <span>₹<?= $original_price; ?></span>
               <?php endif; ?>
            </div>
            <input type="number" name="qty" class="qty" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="1">
         </div>
      </div>
      
      <button type="submit" class="btn" name="add_to_cart">
         <i class="fas fa-shopping-cart"></i> Add to Cart
      </button>
   </form>
   <?php
      }
   }else{
      echo '
      <div class="empty">
         <img src="https://cdn-icons-png.flaticon.com/512/4076/4076478.png" alt="Empty shop">
         <h3>Our Shop is Currently Empty</h3>
         <p>We\'re preparing something amazing for you! Check back soon for our new collection.</p>
         <a href="contact.php" class="btn">Contact Us</a>
      </div>';
   }
   ?>

   </div>
   
   <!-- Load More Button -->
   <div style="text-align: center; margin-top: 3rem;">
      <button class="btn" style="padding: 0.8rem 2.5rem; background: var(--secondary);">
         <i class="fas fa-sync-alt"></i> Load More
      </button>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

<script>
   // Notification animation
   document.addEventListener('DOMContentLoaded', function() {
      const notification = document.getElementById('notification');
      if(notification) {
         // Show notification
         setTimeout(() => {
            notification.classList.add('show');
         }, 100);
         
         // Hide after 3 seconds
         setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
               notification.remove();
            }, 300);
         }, 3000);
      }
      
      // Add animation on scroll
      const elements = document.querySelectorAll('.box, .category-btn, .featured-banner, .shop-hero-content');
      
      const observer = new IntersectionObserver((entries) => {
         entries.forEach(entry => {
            if (entry.isIntersecting) {
               entry.target.style.opacity = 1;
               entry.target.style.transform = 'translateY(0)';
            }
         });
      }, { threshold: 0.1 });
      
      elements.forEach(el => {
         el.style.opacity = 0;
         el.style.transform = 'translateY(20px)';
         el.style.transition = 'all 0.6s ease';
         observer.observe(el);
      });
      
      // Quantity input validation
      const quantityInputs = document.querySelectorAll('.qty');
      quantityInputs.forEach(input => {
         input.addEventListener('change', function() {
            if(this.value < 1) this.value = 1;
            if(this.value > 99) this.value = 99;
         });
      });
      
      // Form submission handling
      const forms = document.querySelectorAll('form.box');
      forms.forEach(form => {
         form.addEventListener('submit', function(e) {
            const qtyInput = this.querySelector('input[name="qty"]');
            if(qtyInput.value < 1 || qtyInput.value > 99) {
               e.preventDefault();
               alert('Please enter a valid quantity (1-99)');
               qtyInput.focus();
               return;
            }
            
            // You could add AJAX submission here if needed
            // For now, let the form submit normally
         });
      });
   });
</script>

</body>
</html>