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
   <title>Shop | Nepal~Store</title>
   
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
      
      /* Featured Products Section */
      .featured-products {
         padding: 6rem 5%;
         background: var(--light);
      }
      
      .section-header {
         text-align: center;
         margin-bottom: 4rem;
      }
      
      .section-header h2 {
         font-family: 'Playfair Display', serif;
         font-size: 3rem;
         color: var(--dark);
         margin-bottom: 1rem;
         position: relative;
         display: inline-block;
      }
      
      .section-header h2::after {
         content: '';
         position: absolute;
         bottom: -15px;
         left: 50%;
         transform: translateX(-50%);
         width: 80px;
         height: 4px;
         background: var(--secondary);
         border-radius: 2px;
      }
      
      .section-header p {
         font-size: 1.2rem;
         color: var(--dark-light);
         max-width: 700px;
         margin: 2rem auto 0;
         line-height: 1.6;
      }
      
      .products-grid {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
         gap: 2.5rem;
      }
      
      .product-card {
         background: #fff;
         border-radius: 16px;
         overflow: hidden;
         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
         transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
         position: relative;
         display: flex;
         flex-direction: column;
      }
      
      .product-card:hover {
         transform: translateY(-10px);
         box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
      }
      
      .product-badge-container {
         position: absolute;
         top: 15px;
         left: 15px;
         z-index: 3;
         display: flex;
         flex-direction: column;
         gap: 8px;
      }
      
      .product-badge {
         padding: 0.4rem 1rem;
         border-radius: 50px;
         font-size: 0.75rem;
         font-weight: 700;
         text-transform: uppercase;
         letter-spacing: 0.5px;
         box-shadow: 0 3px 10px rgba(0,0,0,0.15);
      }
      
      .product-badge.sale {
         background: var(--warning);
         color: white;
      }
      
      .product-badge.new {
         background: var(--success);
         color: white;
      }
      
      .product-badge.hot {
         background: var(--secondary);
         color: white;
      }
      
      .product-image-container {
         position: relative;
         overflow: hidden;
         height: 260px;
      }
      
      .product-image {
         width: 100%;
         height: 100%;
         object-fit: cover;
         transition: all 0.6s ease;
      }
      
      .product-card:hover .product-image {
         transform: scale(1.08);
      }
      
      .product-overlay {
         position: absolute;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background: rgba(58, 12, 163, 0.7);
         display: flex;
         align-items: center;
         justify-content: center;
         opacity: 0;
         transition: all 0.4s ease;
      }
      
      .product-card:hover .product-overlay {
         opacity: 1;
      }
      
      .product-actions {
         display: flex;
         gap: 15px;
      }
      
      .action-btn {
         width: 45px;
         height: 45px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         background: white;
         color: var(--dark);
         font-size: 1.1rem;
         cursor: pointer;
         transition: all 0.3s ease;
         border: none;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      }
      
      .action-btn:hover {
         background: var(--secondary);
         color: white;
         transform: translateY(-3px);
      }
      
      .product-info {
         padding: 1.8rem;
         flex-grow: 1;
         display: flex;
         flex-direction: column;
      }
      
      .product-category {
         font-size: 0.85rem;
         color: var(--dark-light);
         text-transform: uppercase;
         letter-spacing: 0.5px;
         margin-bottom: 0.8rem;
         font-weight: 600;
      }
      
      .product-name {
         font-size: 1.25rem;
         color: var(--dark);
         margin-bottom: 1rem;
         font-weight: 700;
         line-height: 1.4;
         transition: color 0.3s ease;
         display: block;
      }
      
      .product-name:hover {
         color: var(--primary);
      }
      
      .product-rating {
         display: flex;
         align-items: center;
         margin-bottom: 1.2rem;
         gap: 8px;
      }
      
      .stars {
         color: var(--warning);
         font-size: 0.9rem;
      }
      
      .rating-count {
         font-size: 0.85rem;
         color: var(--dark-light);
      }
      
      .product-price {
         margin-top: auto;
         display: flex;
         align-items: center;
         justify-content: space-between;
         flex-wrap: wrap;
         gap: 15px;
      }
      
      .price-container {
         display: flex;
         flex-direction: column;
      }
      
      .current-price {
         font-weight: 800;
         color: var(--primary);
         font-size: 1.5rem;
      }
      
      .original-price {
         font-size: 0.95rem;
         color: #999;
         text-decoration: line-through;
      }
      
      .discount-percent {
         background: var(--secondary);
         color: white;
         padding: 0.3rem 0.7rem;
         border-radius: 50px;
         font-size: 0.8rem;
         font-weight: 700;
      }
      
      .add-to-cart-btn {
         display: flex;
         align-items: center;
         gap: 10px;
         padding: 0.9rem 1.8rem;
         background: var(--primary);
         color: white;
         border: none;
         border-radius: 50px;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.3s ease;
         font-size: 0.95rem;
         box-shadow: 0 5px 15px rgba(58, 12, 163, 0.2);
      }
      
      .add-to-cart-btn:hover {
         background: var(--secondary);
         transform: translateY(-3px);
         box-shadow: 0 8px 20px rgba(247, 37, 133, 0.3);
      }
      
      .quantity-selector {
         display: flex;
         align-items: center;
         background: #f8f9fa;
         border-radius: 50px;
         padding: 0.3rem;
         margin-top: 1rem;
      }
      
      .qty-btn {
         width: 32px;
         height: 32px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         background: white;
         border: 1px solid #e9ecef;
         cursor: pointer;
         font-weight: 600;
         transition: all 0.2s ease;
      }
      
      .qty-btn:hover {
         background: var(--primary);
         color: white;
         border-color: var(--primary);
      }
      
      .qty-input {
         width: 40px;
         text-align: center;
         border: none;
         background: transparent;
         font-weight: 600;
         font-size: 1rem;
      }
      
      .qty-input:focus {
         outline: none;
      }
      
      /* Featured banner */
      .featured-banner {
         background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
         color: white;
         padding: 3rem;
         border-radius: 15px;
         margin: 5rem 0;
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
         .featured-products {
            padding: 5rem 5%;
         }
         
         .products-grid {
            gap: 2rem;
         }
      }
      
      @media (max-width: 991px) {
         .shop-hero {
            height: 350px;
         }
         
         .shop-hero-content h1 {
            font-size: 3.2rem;
         }
         
         .section-header h2 {
            font-size: 2.5rem;
         }
         
         .featured-banner {
            flex-direction: column;
            text-align: center;
            gap: 2rem;
         }
         
         .products-grid {
            grid-template-columns: repeat(2, 1fr);
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
         
         .section-header h2 {
            font-size: 2.2rem;
         }
         
         .featured-banner .content h3 {
            font-size: 1.8rem;
         }
         
         .product-price {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
         }
         
         .add-to-cart-btn {
            width: 100%;
            justify-content: center;
         }
      }
      
      @media (max-width: 576px) {
         .shop-hero {
            height: 250px;
         }
         
         .shop-hero-content h1 {
            font-size: 2.2rem;
         }
         
         .section-header h2 {
            font-size: 2rem;
         }
         
         .products-grid {
            grid-template-columns: 1fr;
         }
         
         .empty h3 {
            font-size: 1.8rem;
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
      <a href="#featured-products" class="btn">Shop Now</a>
   </div>
</section>

<!-- Featured Products Section -->
<section class="featured-products" id="featured-products">
   <div class="section-header">
      <h2>Featured Products</h2>
      <p>Discover our handpicked selection of premium products, carefully curated to elevate your shopping experience</p>
   </div>

   <div class="products-grid">

   <?php
   $productManager = new Product($conn);
   $products = $productManager->getProducts(12);
   
   if($products->rowCount() > 0){
      while($fetch_product = $products->fetch(PDO::FETCH_ASSOC)){
         $badge = $productManager->generateBadge();
         $prices = $productManager->calculateDiscountPrice($fetch_product['price'], $badge['type']);
         $rating_stars = $productManager->generateRating();
         $rating_count = rand(15, 120);
         
         // Generate a random discount percentage for sale items
         $discount_percent = '';
         if($badge['type'] == 'sale') {
            $discount_percent = '<span class="discount-percent">-' . rand(15, 30) . '%</span>';
         }
   ?>
   <form action="" method="post" class="product-card">
      <input type="hidden" name="pid" value="<?= htmlspecialchars($fetch_product['id']); ?>">
      <input type="hidden" name="name" value="<?= htmlspecialchars($fetch_product['name']); ?>">
      <input type="hidden" name="price" value="<?= htmlspecialchars($fetch_product['price']); ?>">
      <input type="hidden" name="image" value="<?= htmlspecialchars($fetch_product['image_01']); ?>">
      
      <div class="product-badge-container">
         <?= $badge['html'] ?>
         <?php if(rand(0, 1)): ?>
            <span class="product-badge hot">HOT</span>
         <?php endif; ?>
      </div>
      
      <div class="product-image-container">
         <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="<?= $fetch_product['name']; ?>" class="product-image">
         
         <div class="product-overlay">
            <div class="product-actions">
               <button type="submit" name="add_to_wishlist" class="action-btn">
                  <i class="fas fa-heart"></i>
               </button>
               <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="action-btn">
                  <i class="fas fa-eye"></i>
               </a>
            </div>
         </div>
      </div>
      
      <div class="product-info">
         <span class="product-category"><?= ucfirst($fetch_product['category'] ?? 'Electronics'); ?></span>
         <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="product-name"><?= $fetch_product['name']; ?></a>
         
         <div class="product-rating">
            <div class="stars">
               <?= $rating_stars ?>
            </div>
            <span class="rating-count">(<?= $rating_count; ?>)</span>
         </div>
         
         <div class="quantity-selector">
            <button type="button" class="qty-btn minus">-</button>
            <input type="number" name="qty" class="qty-input" min="1" max="99" value="1">
            <button type="button" class="qty-btn plus">+</button>
         </div>
      </div>
      
      <div class="product-price">
         <div class="price-container">
            <span class="current-price">रु-<?= $productManager->formatPrice($prices['discount']); ?></span>
            <?php if($prices['original']): ?>
               <span class="original-price">रु-<?= $prices['original']; ?></span>
            <?php endif; ?>
         </div>
         <?= $discount_percent ?>
      </div>
      
      <button type="submit" class="add-to-cart-btn" name="add_to_cart">
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

<!-- Featured Banner -->
<div class="container">
   <div class="featured-banner">
      <div class="content">
         <h3>Seasonal Sale - Limited Time!</h3>
         <p>Enjoy exclusive discounts on our curated collection. Offer ends soon.</p>
      </div>
      <a href="#" class="btn">Explore Offers</a>
   </div>
</div>

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
      
      // Quantity buttons functionality
      document.querySelectorAll('.qty-btn').forEach(button => {
         button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.qty-input');
            let value = parseInt(input.value);
            
            if(this.classList.contains('plus')) {
               if(value < 99) input.value = value + 1;
            } else {
               if(value > 1) input.value = value - 1;
            }
         });
      });
      
      // Quantity input validation
      document.querySelectorAll('.qty-input').forEach(input => {
         input.addEventListener('change', function() {
            if(this.value < 1) this.value = 1;
            if(this.value > 99) this.value = 99;
         });
      });
      
      // Add animation on scroll
      const elements = document.querySelectorAll('.product-card, .section-header, .featured-banner');
      
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
      
      // Stagger animation for product cards
      const cards = document.querySelectorAll('.product-card');
      cards.forEach((card, index) => {
         card.style.transitionDelay = `${index * 0.1}s`;
      });
   });
</script>

</body>
</html>