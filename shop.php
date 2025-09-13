<?php
include 'components/connect.php';

session_start();
if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
}

// Product class to handle product operations
class Product {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getProducts($limit = 12) {
        $select_products = $this->conn->prepare("SELECT * FROM `products` LIMIT ?");
        $select_products->bindValue(1, $limit, PDO::PARAM_INT);
        $select_products->execute();
        return $select_products;
    }
    
    public function formatPrice($price) {
        return number_format($price, 2);
    }
    
    public function generateRating($min = 3, $max = 5) {
        $rating = rand($min, $max);
        $rating_stars = str_repeat('<i class="fas fa-star"></i>', $rating);
        if($rating < 5) {
            $rating_stars .= str_repeat('<i class="far fa-star"></i>', 5 - $rating);
        }
        return $rating_stars;
    }
    
    public function generateBadge() {
        $random_badge = rand(1, 3);
        if($random_badge == 1) {
            return ['type' => 'sale', 'html' => '<span class="product-badge sale">SALE</span>'];
        } elseif($random_badge == 2) {
            return ['type' => 'new', 'html' => '<span class="product-badge new">NEW</span>'];
        }
        return ['type' => '', 'html' => ''];
    }
    
    public function calculateDiscountPrice($price, $badge_type) {
        if($badge_type == 'sale') {
            $original_price = number_format($price * 1.3, 2);
            $discount_price = $price;
            return ['original' => $original_price, 'discount' => $discount_price];
        }
        return ['original' => '', 'discount' => $price];
    }
}

// Cart handler class
class CartHandler {
    public function addToCart($pid, $name, $price, $image, $qty) {
        // Sanitize inputs
        $pid = filter_var($pid, FILTER_SANITIZE_STRING);
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $price = filter_var($price, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $image = filter_var($image, FILTER_SANITIZE_STRING);
        $qty = filter_var($qty, FILTER_SANITIZE_NUMBER_INT);
        
        // Check if cart already exists
        if(isset($_SESSION['cart'])){
            $product_exists = false;
            
            // Check if this product is already in cart
            foreach($_SESSION['cart'] as &$item){
                if($item['pid'] == $pid){
                    $item['qty'] += $qty;
                    $product_exists = true;
                    return 'Product quantity updated in cart!';
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
                return 'Product added to cart!';
            }
        } else {
            // Cart doesn't exist - create it with this item
            $cart_item = array(
                'pid' => $pid,
                'name' => $name,
                'price' => $price,
                'image' => $image,
                'qty' => $qty
            );
            $_SESSION['cart'] = array($cart_item);
            return 'Product added to cart!';
        }
    }
}

// Handle add to cart functionality
if(isset($_POST['add_to_cart'])){
    $cartHandler = new CartHandler();
    $message[] = $cartHandler->addToCart(
        $_POST['pid'], 
        $_POST['name'], 
        $_POST['price'], 
        $_POST['image'], 
        $_POST['qty']
    );
}

include 'components/wishlist_cart.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shop | electronic things</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- google fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      :root {
         --primary: #3a0ca3;
         --primary-light: #4cc9f0;
         --secondary: #f96305ff;
         --secondary-light: #c0e313ff;
         --dark: #14213d;
         --dark-light: #2b2d42;
         --light: #f8f9fa;
         --light-dark: #e9ecef;
         --success: #4ad66d;
         --warning: #ff9e00;
         --info: #4361ee;
      }
      
      /* Hero section */
      .shop-hero {
         background: linear-gradient(135deg, rgba(20, 33, 61, 0.9) 0%, rgba(58, 12, 163, 0.9) 100%), 
                     url('https://images.unsplash.com/photo-1445205170230-053b83016050?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
         background-size: cover;
         background-position: center;
         height: 400px;
         display: flex;
         align-items: center;
         justify-content: center;
         text-align: center;
         color: white;
         margin-bottom: 4rem;
         position: relative;
         overflow: hidden;
      }
      
      .shop-hero::before {
         content: '';
         position: absolute;
         bottom: 0;
         left: 0;
         width: 100%;
         height: 100px;
         background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="%23f8f9fa" opacity=".25"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" fill="%23f8f9fa" opacity=".5"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%23f8f9fa"/></svg>');
         background-size: cover;
         z-index: 1;
      }
      
      .shop-hero-content {
         position: relative;
         z-index: 2;
         max-width: 800px;
         padding: 0 2rem;
      }
      
      .shop-hero-content h1 {
         font-family: 'Playfair Display', serif;
         font-size: 4rem;
         margin-bottom: 1.5rem;
         text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
         line-height: 1.2;
         animation: fadeInUp 0.8s ease;
      }
      
      .shop-hero-content p {
         font-size: 1.3rem;
         max-width: 700px;
         margin: 0 auto 2rem;
         opacity: 0.9;
         animation: fadeInUp 0.8s ease 0.2s forwards;
         opacity: 0;
      }
      
      .shop-hero .btn {
         display: inline-block;
         padding: 0.9rem 2.5rem;
         background: var(--secondary);
         color: white;
         border-radius: 50px;
         font-weight: 600;
         font-size: 1.1rem;
         transition: all 0.3s ease;
         animation: fadeInUp 0.8s ease 0.4s forwards;
         opacity: 0;
      }
      
      .shop-hero .btn:hover {
         background: var(--secondary-light);
         transform: translateY(-3px);
         box-shadow: 0 10px 20px rgba(0,0,0,0.2);
      }
      
      /* Category filters */
      .category-filters {
         display: flex;
         justify-content: center;
         flex-wrap: wrap;
         gap: 1rem;
         margin-bottom: 4rem;
         padding: 0 2rem;
      }
      
      .category-btn {
         padding: 0.8rem 2rem;
         border: 2px solid var(--primary);
         background: white;
         color: var(--primary);
         border-radius: 50px;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.3s ease;
         font-size: 0.95rem;
         text-transform: uppercase;
         letter-spacing: 0.5px;
      }
      
      .category-btn:hover, .category-btn.active {
         background: var(--primary);
         color: white;
         box-shadow: 0 5px 15px rgba(58, 12, 163, 0.2);
      }
      
  
      /* Products section */
      .products {
         padding: 6rem 9%;
         background: var(--light);
      }
      
      .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
         gap: 3rem;
      }
      
      .products .box {
         background: #fff;
         border-radius: 15px;
         overflow: hidden;
         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
         transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
         position: relative;
      }
      
      .products .box:hover {
         transform: translateY(-10px);
         box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      }
      
      .products .box .product-badge {
         position: absolute;
         top: 15px;
         left: 15px;
         background: var(--secondary);
         color: white;
         padding: 0.4rem 1rem;
         border-radius: 50px;
         font-size: 0.8rem;
         font-weight: 600;
         z-index: 2;
         box-shadow: 0 3px 10px rgba(247, 37, 133, 0.3);
      }
      
      .products .box .product-badge.sale {
         background: var(--warning);
         box-shadow: 0 3px 10px rgba(255, 158, 0, 0.3);
      }
      
      .products .box .product-badge.new {
         background: var(--success);
         box-shadow: 0 3px 10px rgba(74, 214, 109, 0.3);
      }
      
      .products .box .image-container {
         overflow: hidden;
         position: relative;
         height: 300px;
      }
      
      .products .box img {
         width: 100%;
         height: 100%;
         object-fit: cover;
         transition: all 0.6s ease;
      }
      
      .products .box:hover img {
         transform: scale(1.1);
      }
      
      .products .box .product-info {
         padding: 2rem;
      }
      
      .products .box .name {
         font-size: 1.2rem;
         color: var(--dark);
         margin-bottom: 0.8rem;
         font-weight: 600;
         transition: color 0.3s ease;
         display: block;
      }
      
      .products .box:hover .name {
         color: var(--primary);
      }
      
      .products .box .category {
         font-size: 0.85rem;
         color: var(--dark-light);
         margin-bottom: 0.8rem;
         display: block;
         text-transform: uppercase;
         letter-spacing: 0.5px;
      }
      
      .products .box .rating {
         color: var(--warning);
         font-size: 0.9rem;
         margin-bottom: 1.2rem;
      }
      
      .products .box .flex {
         display: flex;
         align-items: center;
         justify-content: space-between;
         margin-top: 1.5rem;
      }
      
      .products .box .price {
         font-weight: 700;
         color: var(--primary);
         font-size: 1.5rem;
      }
      
      .products .box .price span {
         font-size: 1rem;
         color: #999;
         text-decoration: line-through;
         margin-left: 0.5rem;
      }
      
      .products .box .qty {
         width: 70px;
         padding: 0.6rem;
         border: 1px solid #eee;
         border-radius: 5px;
         text-align: center;
         font-weight: 600;
         background: #f9f9f9;
         transition: all 0.3s ease;
      }
      
      .products .box .qty:focus {
         border-color: var(--primary-light);
         outline: none;
         box-shadow: 0 0 0 3px rgba(58, 12, 163, 0.1);
      }
      
      .products .box .btn {
         display: block;
         width: calc(100% - 4rem);
         margin: 0 2rem 2rem;
         background: var(--primary);
         color: #fff;
         border: none;
         border-radius: 8px;
         padding: 1rem;
         cursor: pointer;
         font-weight: 600;
         transition: all 0.3s ease;
         text-align: center;
         font-size: 1rem;
         text-transform: uppercase;
         letter-spacing: 0.5px;
         box-shadow: 0 5px 15px rgba(58, 12, 163, 0.2);
      }
      
      .products .box .btn:hover {
         background: var(--secondary);
         transform: translateY(-3px);
         box-shadow: 0 8px 25px rgba(247, 37, 133, 0.3);
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
         gap: 0.8rem;
         z-index: 2;
      }
      
      .products .box .action-buttons button {
         background: rgba(255, 255, 255, 0.95);
         border: none;
         width: 40px;
         height: 40px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         transition: all 0.3s ease;
         color: var(--dark);
         font-size: 1rem;
         box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      }
      
      .products .box .action-buttons button:hover {
         background: var(--secondary);
         color: white;
         transform: scale(1.1);
      }
      
      /* Empty state */
      .empty {
         text-align: center;
         grid-column: 1/-1;
         padding: 6rem 0;
      }
      
      .empty img {
         width: 250px;
         margin-bottom: 2.5rem;
         opacity: 0.8;
      }
      
      .empty h3 {
         font-size: 2rem;
         color: var(--dark);
         margin-bottom: 1.5rem;
         font-family: 'Playfair Display', serif;
      }
      
      .empty p {
         font-size: 1.2rem;
         color: var(--dark-light);
         margin-bottom: 2rem;
         max-width: 600px;
         margin-left: auto;
         margin-right: auto;
      }
      
      .empty .btn {
         display: inline-block;
         padding: 1rem 3rem;
         background: var(--primary);
         color: white;
         border-radius: 50px;
         font-weight: 600;
         transition: all 0.3s ease;
         font-size: 1.1rem;
         text-transform: uppercase;
         letter-spacing: 1px;
      }
      
      .empty .btn:hover {
         background: var(--secondary);
         transform: translateY(-3px);
         box-shadow: 0 10px 20px rgba(0,0,0,0.1);
      }
      
      /* Featured banner */
      .featured-banner {
         background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
         color: white;
         padding: 3rem;
         border-radius: 15px;
         margin-bottom: 4rem;
         display: flex;
         align-items: center;
         justify-content: space-between;
         box-shadow: 0 10px 30px rgba(58, 12, 163, 0.2);
         position: relative;
         overflow: hidden;
      }
      
      .featured-banner::before {
         content: '';
         position: absolute;
         top: -50px;
         right: -50px;
         width: 200px;
         height: 200px;
         background: rgba(255, 255, 255, 0.1);
         border-radius: 50%;
      }
      
      .featured-banner::after {
         content: '';
         position: absolute;
         bottom: -80px;
         left: -80px;
         width: 250px;
         height: 250px;
         background: rgba(255, 255, 255, 0.05);
         border-radius: 50%;
      }
      
      .featured-banner .content h3 {
         font-size: 2.2rem;
         margin-bottom: 1rem;
         font-family: 'Playfair Display', serif;
         position: relative;
         z-index: 2;
      }
      
      .featured-banner .content p {
         font-size: 1.2rem;
         opacity: 0.9;
         max-width: 600px;
         position: relative;
         z-index: 2;
      }
      
      .featured-banner .btn {
         background: white;
         color: var(--primary);
         padding: 1rem 2.5rem;
         border-radius: 50px;
         font-weight: 600;
         transition: all 0.3s ease;
         position: relative;
         z-index: 2;
         font-size: 1.1rem;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      }
      
      .featured-banner .btn:hover {
         transform: translateY(-3px);
         box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      }
      
      /* Notification message */
      .notification {
         position: fixed;
         top: 30px;
         right: 30px;
         background: var(--success);
         color: white;
         padding: 1.2rem 2rem;
         border-radius: 10px;
         box-shadow: 0 10px 30px rgba(74, 214, 109, 0.3);
         z-index: 1000;
         display: flex;
         align-items: center;
         gap: 15px;
         transform: translateX(150%);
         transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      }
      
      .notification.show {
         transform: translateX(0);
      }
      
      .notification.error {
         background: var(--secondary);
         box-shadow: 0 10px 30px rgba(247, 37, 133, 0.3);
      }
      
      .notification i {
         font-size: 1.5rem;
      }
      
      /* Load more button */
      .load-more {
         text-align: center;
         margin-top: 5rem;
      }
      
      .load-more .btn {
         display: inline-block;
         padding: 1rem 3rem;
         background: var(--primary);
         color: white;
         border-radius: 50px;
         font-weight: 600;
         transition: all 0.3s ease;
         font-size: 1.1rem;
         border: none;
         cursor: pointer;
         box-shadow: 0 5px 15px rgba(58, 12, 163, 0.2);
      }
      
      .load-more .btn:hover {
         background: var(--secondary);
         transform: translateY(-3px);
         box-shadow: 0 10px 25px rgba(247, 37, 133, 0.3);
      }
      
      .load-more .btn i {
         margin-right: 10px;
      }
      
      /* Animations */
      @keyframes fadeInUp {
         from {
            opacity: 0;
            transform: translateY(20px);
         }
         to {
            opacity: 1;
            transform: translateY(0);
         }
      }
      
      /* Responsive adjustments */
      @media (max-width: 1200px) {
         .products {
            padding: 5rem 5%;
         }
         
         .box-container {
            gap: 2.5rem;
         }
      }
      
      @media (max-width: 991px) {
         .shop-hero {
            height: 350px;
         }
         
         .shop-hero-content h1 {
            font-size: 3.2rem;
         }
         
         .heading {
            font-size: 2.5rem;
         }
         
         .featured-banner {
            flex-direction: column;
            text-align: center;
            gap: 2rem;
         }
      }
      
      @media (max-width: 768px) {
         .shop-hero {
            height: 300px;
         }
         
         .shop-hero-content h1 {
            font-size: 2.8rem;
         }
         
         .shop-hero-content p {
            font-size: 1.1rem;
         }
         
         .box-container {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
         }
         
         .featured-banner .content h3 {
            font-size: 1.8rem;
         }
      }
      
      @media (max-width: 576px) {
         .shop-hero {
            height: 250px;
         }
         
         .shop-hero-content h1 {
            font-size: 2.2rem;
         }
         
         .heading {
            font-size: 2rem;
         }
         
         .products .box .btn {
            width: calc(100% - 2rem);
            margin: 0 1rem 1.5rem;
         }
         
         .empty h3 {
            font-size: 1.8rem;
         }
      }
  
    
      /* Updated for 4 products per row */
      .box-container {
         display: grid;
         grid-template-columns: repeat(4, 1fr); /* 4 products per row */
         gap: 2rem;
      }
      
      /* Responsive adjustments for 4-column layout */
      @media (max-width: 1200px) {
         .box-container {
            grid-template-columns: repeat(3, 1fr); /* 3 products per row on medium screens */
         }
      }
      
      @media (max-width: 900px) {
         .box-container {
            grid-template-columns: repeat(2, 1fr); /* 2 products per row on tablets */
         }
      }
      
      @media (max-width: 576px) {
         .box-container {
            grid-template-columns: 1fr; /* 1 product per row on mobile */
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
      <h1>Discover Our Premium Collection</h1>
      <p>Elevate your style with electronic device for the modern lifestyle change</p>
      <a href="#products" class="btn">Shop Now</a>
   </div>
</section>

<!-- Featured Banner -->
<section class="products" id="products">
   <div class="featured-banner">
      <div class="content">
         <h3>Seasonal Sale - Limited Time!</h3>
         <p>Enjoy exclusive discounts on our curated collection. Offer ends soon.</p>
      </div>
      <a href="#" class="btn">Explore Offers</a>
   </div>

   <h1 class="heading">Featured Products</h1>

   <div class="box-container">

   <?php
   $productManager = new Product($conn);
   $products = $productManager->getProducts(12);
   
   if($products->rowCount() > 0){
      while($fetch_product = $products->fetch(PDO::FETCH_ASSOC)){
         $badge = $productManager->generateBadge();
         $prices = $productManager->calculateDiscountPrice($fetch_product['price'], $badge['type']);
         $rating_stars = $productManager->generateRating();
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= htmlspecialchars($fetch_product['id']); ?>">
      <input type="hidden" name="name" value="<?= htmlspecialchars($fetch_product['name']); ?>">
      <input type="hidden" name="price" value="<?= htmlspecialchars($fetch_product['price']); ?>">
      <input type="hidden" name="image" value="<?= htmlspecialchars($fetch_product['image_01']); ?>">
      
      <?= $badge['html'] ?>
      
      <div class="action-buttons">
         <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
         <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
      </div>
      
      <div class="image-container">
         <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="<?= $fetch_product['name']; ?>">
      </div>
      
      <div class="product-info">
         <span class="category"><?= ucfirst($fetch_product['category'] ?? 'Fashion'); ?></span>
         <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="name"><?= $fetch_product['name']; ?></a>
         <div class="rating">
            <?= $rating_stars ?> (<?= rand(15, 120); ?>)
         </div>
         
         <div class="flex">
            <div class="price">
               रु-<?= $productManager->formatPrice($prices['discount']); ?>
               <?php if($prices['original']): ?>
                  <span>रु-<?= $prices['original']; ?></span>
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
   } else {
      echo '
      <div class="empty">
         <img src="https://cdn-icons-png.flaticon.com/512/4076/4076478.png" alt="Empty shop">
         <h3>Our Collection is Coming Soon</h3>
         <p>We\'re crafting something extraordinary for you. Sign up for our newsletter to be the first to know when we launch.</p>
         <a href="contact.php" class="btn">Notify Me</a>
      </div>';
   }
   ?>

   </div>
   
   <!-- Load More Button -->
   <div class="load-more">
      <button class="btn">
         <i class="fas fa-sync-alt"></i> Load More Products
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
            }, 400);
         }, 3500);
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
         el.style.transform = 'translateY(30px)';
         el.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
         observer.observe(el);
      });
      
      // Stagger animation for product boxes
      const boxes = document.querySelectorAll('.box');
      boxes.forEach((box, index) => {
         box.style.transitionDelay = `${index * 0.1}s`;
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
         });
      });
   });
</script>

</body>
</html>