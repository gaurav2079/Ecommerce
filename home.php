<?php
// Database connection and session start
include 'components/connect.php';
session_start();

// Define classes for OOP approach
class UserSession {
    private $user_id;
    
    public function __construct() {
        $this->user_id = $_SESSION['user_id'] ?? '';
    }
    
    public function getUserId() {
        return $this->user_id;
    }
    
    public function setUserId($id) {
        $this->user_id = $id;
        $_SESSION['user_id'] = $id;
    }
}

class CSRFProtection {
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

class ProductViewTracker {
    public static function trackProduct($productId) {
        $productId = htmlspecialchars(strip_tags(trim($productId)));
        
        if (!isset($_SESSION['viewed_products'])) {
            $_SESSION['viewed_products'] = array();
        }
        
        if (!in_array($productId, $_SESSION['viewed_products'])) {
            array_unshift($_SESSION['viewed_products'], $productId);
            // Keep only last 5 viewed products
            $_SESSION['viewed_products'] = array_slice($_SESSION['viewed_products'], 0, 5);
        }
    }
}

class DatabaseManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getLatestProducts($limit = 6) {
        $query = "
            SELECT p.*, 
                   COALESCE(r.avg_rating, 0) as avg_rating,
                   COALESCE(r.review_count, 0) as review_count
            FROM products p
            LEFT JOIN (
                SELECT product_id, 
                       AVG(rating) as avg_rating, 
                       COUNT(*) as review_count 
                FROM reviews 
                GROUP BY product_id
            ) r ON p.id = r.product_id
            ORDER BY p.id DESC 
            LIMIT :limit
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCurrentOffers() {
        $query = "SELECT * FROM `offers` WHERE end_date > NOW() ORDER BY discount DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function testConnection() {
        try {
            $test = $this->conn->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getProductCount() {
        $query = $this->conn->query("SELECT COUNT(*) as product_count FROM products");
        return $query->fetch(PDO::FETCH_ASSOC)['product_count'];
    }
}

class ProductRenderer {
    public static function renderProduct($product) {
        $output = '
        <form action="" method="post" class="swiper-slide slide animate__animated animate__fadeInUp">
            <input type="hidden" name="pid" value="' . htmlspecialchars($product['id']) . '">
            <input type="hidden" name="name" value="' . htmlspecialchars($product['name']) . '">
            <input type="hidden" name="price" value="' . htmlspecialchars($product['price']) . '">
            <input type="hidden" name="image" value="' . htmlspecialchars($product['image_01']) . '">
            
            <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
            <a href="quick_view.php?pid=' . $product['id'] . '" class="fas fa-eye"></a>
            
            <img src="uploaded_img/' . $product['image_01'] . '" 
                 alt="' . htmlspecialchars($product['name']) . '"
                 onerror="this.src=\'../images/default-product.jpg\'">
            
            <div class="name">' . htmlspecialchars($product['name']) . '</div>';
        
        // Rating stars
        $stars = round($product['avg_rating'] ?? 0);
        $output .= '<div class="product-rating">';
        for ($i = 1; $i <= 5; $i++) {
            $output .= $i <= $stars ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
        }
        $output .= '<span>(' . ($product['review_count'] ?? 0) . ')</span></div>';
        
        // Stock status
        $output .= '<div class="stock-status">';
        if ($product['quantity'] > 10) {
            $output .= '<span class="in-stock"><i class="fas fa-check-circle"></i> In Stock</span>';
        } elseif ($product['quantity'] > 0) {
            $output .= '<span class="low-stock"><i class="fas fa-exclamation-circle"></i> Only ' . $product['quantity'] . ' left</span>';
        } else {
            $output .= '<span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>';
        }
        $output .= '</div>';
        
        // Price
        $output .= '
            <div class="flex">
                <div class="price"><span>रु-</span>' . number_format($product['price'], 2) . '</div>
            </div>
        </form>';
        
        return $output;
    }
}

// Initialize objects
$userSession = new UserSession();
$csrfToken = CSRFProtection::generateToken();

// Track viewed products if PID is provided
if (isset($_GET['pid'])) {
    ProductViewTracker::trackProduct($_GET['pid']);
}

// Initialize database manager
$dbManager = new DatabaseManager($conn);

// Include wishlist and cart functionality
include 'components/wishlist_cart.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Home | Nepal~Store</title>

   <!-- Swiper CSS -->
   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- Animate.css -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
   
   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/category.css">

   <style>
      :root {
         --primary: #4361ee;
         --secondary: #3a0ca3;
         --accent: #f72585;
         --light: #f8f9fa;
         --dark: #212529;
      }
      
      /* Home Section */
      .home-bg {
         background: linear-gradient(135deg, #3a0ca3, #4361ee, #4cc9f0);
         border-radius: 0 0 30px 30px;
         box-shadow: 0 10px 30px rgba(58, 12, 163, 0.2);
         overflow: hidden;
         position: relative;
      }
      
      .home-bg::before {
         content: '';
         position: absolute;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background: url('images/dots-pattern.png') repeat;
         opacity: 0.05;
         z-index: 0;
      }
      
      .home .slide {
         display: flex;
         align-items: center;
         justify-content: center;
         padding: 2rem 0;
      }
      
      .home .slide .image {
         flex: 1;
         animation: float 3s ease-in-out infinite;
      }
      
      @keyframes float {
         0% { transform: translateY(0px); }
         50% { transform: translateY(-20px); }
         100% { transform: translateY(0px); }
      }
      
      .home .slide .content {
         flex: 1;
         background: rgba(255, 255, 255, 0.15);
         backdrop-filter: blur(10px);
         border-radius: 20px;
         padding: 2rem;
         border: 1px solid rgba(255, 255, 255, 0.2);
         animation: fadeInUp 1s ease;
      }
      
      .home .slide .content span {
         background: var(--accent);
         color: white;
         padding: 0.5rem 1.5rem;
         border-radius: 50px;
         display: inline-block;
         margin-bottom: 1rem;
         font-weight: bold;
         animation: pulse 2s infinite;
      }
      
      .home .slide .content h3 {
         font-size: 3rem;
         color: white;
         margin-bottom: 1rem;
         text-transform: uppercase;
      }
      
      /* Category Section */
      .category {
         padding: 4rem 0;
      }
      
      .category .heading {
         position: relative;
         display: inline-block;
         margin-bottom: 3rem;
         font-size: 2.5rem;
         color: var(--dark);
      }
      
      .category .heading:after {
         content: '';
         position: absolute;
         bottom: -10px;
         left: 50%;
         transform: translateX(-50%);
         width: 80px;
         height: 4px;
         background: linear-gradient(to right, var(--primary), var(--accent));
         border-radius: 2px;
      }
      
      .category-slider .slide {
         transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
         border-radius: 15px;
         overflow: hidden;
         box-shadow: 0 3px 6px rgba(0,0,0,0.1);
         background: white;
         text-align: center;
         padding: 1.5rem;
      }
      
      .category-slider .slide:hover {
         transform: translateY(-10px);
         box-shadow: 0 10px 20px rgba(0,0,0,0.15);
      }
      
      .category-slider .slide img {
         transition: all 0.5s ease;
         height: 80px;
         margin-bottom: 1rem;
      }
      
      .category-slider .slide:hover img {
         transform: scale(1.1);
      }
      
      .category-slider .slide h3 {
         font-size: 1.2rem;
         color: var(--dark);
      }
      
      /* Products Section */
      .home-products {
         padding: 4rem 0;
         background: var(--light);
      }
      
      .home-products .heading {
         position: relative;
         display: inline-block;
         margin-bottom: 3rem;
         font-size: 2.5rem;
         color: var(--dark);
      }
      
      .home-products .heading:after {
         content: '';
         position: absolute;
         bottom: -10px;
         left: 50%;
         transform: translateX(-50%);
         width: 80px;
         height: 4px;
         background: linear-gradient(to right, var(--primary), var(--accent));
         border-radius: 2px;
      }
      
      .products-slider .slide {
         background: white;
         border-radius: 15px;
         overflow: hidden;
         box-shadow: 0 5px 15px rgba(0,0,0,0.05);
         transition: all 0.3s ease;
         border: 1px solid rgba(0,0,0,0.05);
         padding: 1.5rem;
         position: relative;
      }
      
      .products-slider .slide:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      }
      
      .products-slider .slide .fa-heart,
      .products-slider .slide .fa-eye {
         position: absolute;
         top: 1.5rem;
         background: white;
         border-radius: 50%;
         width: 40px;
         height: 40px;
         line-height: 40px;
         text-align: center;
         color: var(--dark);
         cursor: pointer;
         transition: all 0.3s ease;
         box-shadow: 0 2px 5px rgba(0,0,0,0.1);
         z-index: 10;
      }
      
      .products-slider .slide .fa-heart {
         right: 1.5rem;
      }
      
      .products-slider .slide .fa-eye {
         right: 5rem;
      }
      
      .products-slider .slide .fa-heart:hover {
         color: var(--accent);
         transform: scale(1.1);
      }
      
      .products-slider .slide .fa-eye:hover {
         color: var(--primary);
         transform: scale(1.1);
      }
      
      .products-slider .slide img {
         width: 100%;
         height: 200px;
         object-fit: contain;
         margin-bottom: 1rem;
         transition: all 0.3s ease;
      }
      
      .products-slider .slide:hover img {
         transform: scale(1.05);
      }
      
      .products-slider .slide .name {
         font-size: 1.2rem;
         color: var(--dark);
         margin-bottom: 0.5rem;
      }
      
      .products-slider .slide .price {
         font-size: 1.5rem;
         color: var(--accent);
         font-weight: bold;
      }
      
      .products-slider .slide .qty {
         width: 60px;
         text-align: center;
         border: 1px solid #ddd;
         border-radius: 5px;
         padding: 0.5rem;
      }
      
      .products-slider .slide .btn {
         width: 100%;
         margin-top: 1rem;
         background: linear-gradient(to right, var(--primary), var(--secondary));
         transition: all 0.3s ease;
      }
      
      .products-slider .slide .btn:hover {
         transform: translateY(-3px);
         box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
      }
      
      /* Stock Status Styles */
      .stock-status {
         margin: 0.5rem 0;
         font-size: 0.9rem;
      }
      
      .in-stock {
         color: #28a745;
      }
      
      .low-stock {
         color: #ffc107;
      }
      
      .out-of-stock {
         color: #dc3545;
      }
      
      /* Product Rating */
      .product-rating {
         margin: 0.5rem 0;
         color: #ffc107;
      }
      
      .product-rating span {
         color: #6c757d;
         font-size: 0.8rem;
         margin-left: 0.5rem;
      }
      
      /* Swiper Pagination */
      .swiper-pagination-bullet {
         background: white;
         opacity: 0.7;
         width: 12px;
         height: 12px;
         transition: all 0.3s;
      }
      
      .swiper-pagination-bullet-active {
         background: var(--accent);
         opacity: 1;
         transform: scale(1.2);
      }

      /* Special Offers Banner */
      .offer-banner {
         background: linear-gradient(to right, var(--accent), #ff6b6b);
         color: white;
         text-align: center;
         padding: 3rem 2rem;
         margin: 3rem auto;
         border-radius: 15px;
         max-width: 1200px;
         box-shadow: 0 10px 30px rgba(247, 37, 133, 0.3);
         position: relative;
         overflow: hidden;
      }

      .offer-banner h2 {
         font-size: 2.5rem;
         margin-bottom: 1rem;
      }

      .offer-banner p {
         font-size: 1.2rem;
         margin-bottom: 2rem;
      }

      .countdown-timer {
         margin: 1.5rem 0;
      }

      .countdown-timer h4 {
         margin-bottom: 0.5rem;
         font-size: 1.1rem;
      }

      .countdown-timer .timer {
         display: flex;
         justify-content: center;
         gap: 1rem;
         font-size: 1.5rem;
         font-weight: bold;
      }

      .countdown-timer .timer span {
         background: rgba(255, 255, 255, 0.2);
         padding: 0.5rem 1rem;
         border-radius: 5px;
         min-width: 60px;
         display: inline-block;
         text-align: center;
      }

      /* Brands Section */
      .brands {
         padding: 4rem 0;
         background: var(--light);
      }

      .brands-slider .slide {
         background: white;
         border-radius: 15px;
         padding: 2rem;
         text-align: center;
         box-shadow: 0 5px 15px rgba(0,0,0,0.05);
         transition: all 0.3s ease;
      }

      .brands-slider .slide:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      }

      .brands-slider .slide img {
         height: 60px;
         object-fit: contain;
         filter: grayscale(100%);
         opacity: 0.7;
         transition: all 0.3s ease;
      }

      .brands-slider .slide:hover img {
         filter: grayscale(0%);
         opacity: 1;
      }

      /* Testimonials Section */
      .testimonials {
         padding: 4rem 0;
         background: white;
      }

      .testimonials-slider .slide {
         background: var(--light);
         border-radius: 15px;
         padding: 2rem;
         margin-bottom: 4rem;
         box-shadow: 0 5px 15px rgba(0,0,0,0.05);
         position: relative;
      }

      .testimonials-slider .slide .stars {
         color: #ffc107;
         margin-bottom: 1rem;
      }

      .testimonials-slider .slide p {
         font-size: 1.1rem;
         line-height: 1.6;
         color: var(--dark);
         margin-bottom: 1.5rem;
      }

      .testimonials-slider .slide .user {
         display: flex;
         align-items: center;
      }

      .testimonials-slider .slide .user img {
         width: 60px;
         height: 60px;
         border-radius: 50%;
         object-fit: cover;
         margin-right: 1rem;
      }

      .testimonials-slider .slide .user h3 {
         font-size: 1.2rem;
         color: var(--dark);
      }

      .testimonials-slider .slide .user span {
         font-size: 0.9rem;
         color: #666;
      }

      /* Newsletter Section */
      .newsletter {
         background: linear-gradient(to right, var(--primary), var(--secondary));
         color: white;
         text-align: center;
         padding: 5rem 2rem;
         margin: 3rem 0;
      }

      .newsletter .content {
         max-width: 800px;
         margin: 0 auto;
      }

      .newsletter h3 {
         font-size: 2.5rem;
         margin-bottom: 1.5rem;
         text-transform: uppercase;
      }

      .newsletter p {
         font-size: 1.2rem;
         margin-bottom: 2.5rem;
         max-width: 600px;
         margin-left: auto;
         margin-right: auto;
         line-height: 1.6;
      }

      .newsletter form {
         display: flex;
         flex-direction: column;
         align-items: center;
         gap: 1rem;
         max-width: 600px;
         margin: 0 auto;
      }

      .newsletter .box {
         width: 100%;
         padding: 1.2rem 1.5rem;
         border-radius: 50px;
         border: none;
         font-size: 1rem;
         background: rgba(255, 255, 255, 0.9);
         color: var(--dark);
         transition: all 0.3s ease;
      }

      .newsletter .box:focus {
         outline: none;
         box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
         background: white;
      }

      .newsletter .btn {
         background: white;
         color: var(--primary);
         font-weight: bold;
         padding: 1rem 2.5rem;
         border-radius: 50px;
         text-transform: uppercase;
         letter-spacing: 1px;
         transition: all 0.3s ease;
      }

      .newsletter .btn:hover {
         background: var(--accent);
         color: white;
         transform: translateY(-3px);
         box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      }

      .newsletter .message {
         width: 100%;
         margin-top: 1rem;
      }

      .newsletter .alert {
         padding: 0.75rem 1.25rem;
         border-radius: 50px;
         font-size: 0.9rem;
      }

      .newsletter .success {
         background: rgba(40, 167, 69, 0.2);
         color: white;
      }

      .newsletter .error {
         background: rgba(220, 53, 69, 0.2);
         color: white;
      }

      /* Instagram Section */
      .instagram {
         padding: 4rem 0;
         background: var(--light);
      }

      .instagram-slider .slide {
         position: relative;
         border-radius: 15px;
         overflow: hidden;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      }

      .instagram-slider .slide img {
         width: 100%;
         height: 250px;
         object-fit: cover;
         transition: all 0.3s ease;
      }

      .instagram-slider .slide:hover img {
         transform: scale(1.1);
      }

      .instagram-slider .slide .icon {
         position: absolute;
         top: 50%;
         left: 50%;
         transform: translate(-50%, -50%);
         opacity: 0;
         transition: all 0.3s ease;
      }

      .instagram-slider .slide:hover .icon {
         opacity: 1;
      }

      .instagram-slider .slide .icon a {
         display: inline-block;
         width: 50px;
         height: 50px;
         line-height: 50px;
         text-align: center;
         background: white;
         color: var(--accent);
         border-radius: 50%;
         font-size: 1.5rem;
      }
      
      /* Quick View Modal */
      .quick-view-modal {
         display: none;
         position: fixed;
         z-index: 1000;
         left: 0;
         top: 0;
         width: 100%;
         height: 100%;
         overflow: auto;
         background-color: rgba(0,0,0,0.8);
      }
      
      .quick-view-modal .modal-content {
         background-color: #fefefe;
         margin: 5% auto;
         padding: 2rem;
         border-radius: 15px;
         max-width: 800px;
         width: 90%;
         animation: modalFadeIn 0.3s;
      }
      
      @keyframes modalFadeIn {
         from {opacity: 0; transform: translateY(-50px);}
         to {opacity: 1; transform: translateY(0);}
      }
      
      .close-modal {
         color: #aaa;
         float: right;
         font-size: 28px;
         font-weight: bold;
         cursor: pointer;
      }
      
      .close-modal:hover,
      .close-modal:focus {
         color: var(--accent);
         text-decoration: none;
      }
      
      .product-details {
         display: flex;
         flex-wrap: wrap;
         gap: 2rem;
      }
      
      .product-details .image-container {
         flex: 1;
         min-width: 300px;
      }
      
      .product-details .image-container img {
         width: 100%;
         max-height: 400px;
         object-fit: contain;
         border-radius: 10px;
      }
      
      .product-details .info-container {
         flex: 1;
         min-width: 300px;
      }
      
      .product-details .name {
         font-size: 1.8rem;
         color: var(--dark);
         margin-bottom: 1rem;
      }
      
      .product-details .price {
         font-size: 1.8rem;
         color: var(--accent);
         font-weight: bold;
         margin-bottom: 1rem;
      }
      
      .product-details .description {
         margin-bottom: 1.5rem;
         line-height: 1.6;
      }
      
      .product-details .details-list {
         margin-bottom: 1.5rem;
      }
      
      .product-details .details-list li {
         margin-bottom: 0.5rem;
      }
      
      /* Responsive */
      @media (max-width: 768px) {
         .home .slide {
            flex-direction: column;
            text-align: center;
         }
         
         .home .slide .content {
            margin-top: 2rem;
         }
         
         .home .slide .content h3 {
            font-size: 2rem;
         }

         .offer-banner h2 {
            font-size: 2rem;
         }

         .offer-banner p {
            font-size: 1rem;
         }

         .newsletter h3 {
            font-size: 2rem;
         }

         .newsletter p {
            font-size: 1rem;
         }
         
         .product-details {
            flex-direction: column;
         }
      }

      @media (min-width: 768px) {
         .newsletter form {
            flex-direction: row;
         }
         
         .newsletter .box {
            flex: 1;
            margin-right: 1rem;
         }
         
         .newsletter .btn {
            width: auto;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- Home Section -->
<div class="home-bg">
   <section class="home">
      <div class="swiper home-slider">
         <div class="swiper-wrapper">

            <div class="swiper-slide slide">
               <div class="image animate__animated animate__fadeInLeft">
                  <img src="images/home-img-1.png" alt="Latest Smartphones">
               </div>
               <div class="content animate__animated animate__fadeInRight">
                  <span>upto 50% off</span>
                  <h3>latest smartphones</h3>
                  <p>Discover cutting-edge technology with our newest collection</p>
                  <a href="shop.php" class="btn">shop now</a>
               </div>
            </div>

            <div class="swiper-slide slide">
               <div class="image animate__animated animate__fadeInLeft">
                  <img src="images/home-img-2.png" alt="Premium Watches">
               </div>
               <div class="content animate__animated animate__fadeInRight">
                  <span>upto 50% off</span>
                  <h3>premium watches</h3>
                  <p>Elegant timepieces that complement your style</p>
                  <a href="shop.php" class="btn">shop now</a>
               </div>
            </div>

            <div class="swiper-slide slide">
               <div class="image animate__animated animate__fadeInLeft">
                  <img src="images/home-img-3.png" alt="High-End Headsets">
               </div>
               <div class="content animate__animated animate__fadeInRight">
                  <span>upto 50% off</span>
                  <h3>premium headsets</h3>
                  <p>Immersive audio experience with noise cancellation</p>
                  <a href="shop.php" class="btn">shop now</a>
               </div>
            </div>

         </div>
         <div class="swiper-pagination"></div>
      </div>
   </section>
</div>

<!-- Category Section -->
<section class="category">
   <h1 class="heading animate__animated animate__fadeIn">shop by category</h1>
   <div class="swiper category-slider">
      <div class="swiper-wrapper">
         <a href="category.php?category=laptop" class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/icon-1.png" alt="Laptops">
            <h3>laptops</h3>
         </a>
         <a href="category.php?category=tv" class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/icon-2.png" alt="TVs">
            <h3>tvs</h3>
         </a>
         <a href="category.php?category=camera" class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/icon-3.png" alt="Cameras">
            <h3>cameras</h3>
         </a>
         <a href="category.php?category=mouse" class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/icon-4.png" alt="Mice">
            <h3>mice</h3>
         </a>
         <a href="category.php?category=fridge" class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/icon-5.png" alt="Fridges">
            <h3>fridges</h3>
         </a>
         <a href="category.php?category=washing" class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/icon-6.png" alt="Washing Machines">
            <h3>washing machines</h3>
         </a>
         <a href="category.php?category=smartphone" class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/icon-7.png" alt="Smartphones">
            <h3>smartphones</h3>
         </a>
         <a href="category.php?category=watch" class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/icon-8.png" alt="Watches">
            <h3>watches</h3>
         </a>
      </div>
      <div class="swiper-pagination"></div>
   </div>
</section>

<!-- Latest Products -->
<section class="home-products">
   <h1 class="heading animate__animated animate__fadeIn">latest products</h1>
   
   <?php
   // Debugging: Check database connection first
   if (!$dbManager->testConnection()) {
       echo '<p class="empty animate__animated animate__fadeIn">Database connection error</p>';
   } else {
       // Debugging: Check if products exist in database
       $product_count = $dbManager->getProductCount();
       error_log("Total products in database: " . $product_count);
       
       // Get latest products
       $products = $dbManager->getLatestProducts();
       
       if (count($products) > 0) {
           error_log("Found " . count($products) . " products");
           echo '<div class="swiper products-slider">
                   <div class="swiper-wrapper">';
           
           foreach ($products as $product) {
               error_log("Displaying product ID: " . $product['id'] . " - " . $product['name']);
               echo ProductRenderer::renderProduct($product);
           }
           
           echo '</div>
                 <div class="swiper-pagination"></div>
               </div>';
       } else {
           error_log("No products found in database");
           echo '<p class="empty animate__animated animate__fadeIn">No products available at the moment. Please check back later.</p>';
       }
   }
   ?>
</section>

<!-- Special Offers Banner -->
<?php
$offer = $dbManager->getCurrentOffers();
if ($offer) {
?>
<section class="offer-banner animate__animated animate__fadeIn">
   <div class="banner-content">
      <h2><?= $offer['title']; ?></h2>
      <p><?= $offer['description']; ?> Get <?= $offer['discount']; ?>% off!</p>
      <div class="countdown-timer">
         <h4>Offer ends in:</h4>
         <div class="timer">
            <span id="days">00</span>d 
            <span id="hours">00</span>h 
            <span id="minutes">00</span>m 
            <span id="seconds">00</span>s
         </div>
      </div>
      <a href="<?= $offer['link']; ?>" class="btn">Shop Now</a>
   </div>
</section>
<?php } ?>

<!-- Featured Brands Section -->
<section class="brands">
   <h1 class="heading animate__animated animate__fadeIn">featured brands</h1>
   <div class="swiper brands-slider">
      <div class="swiper-wrapper">
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="project images/laptop-1.webp" alt="Brand 1">
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="project images/fridge-1.webp" alt="Brand 2">
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="project images/camera-1.webp" alt="Brand 3">
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="project images/watch-1.webp" alt="Brand 4">
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="project images/smartphone-1.webp" alt="Brand 5">
         </div>
      </div>
      <div class="swiper-pagination"></div>
   </div>
</section>

<!-- Customer Testimonials -->
<section class="testimonials">
   <h1 class="heading animate__animated animate__fadeIn">what our customers say</h1>
   <div class="swiper testimonials-slider">
      <div class="swiper-wrapper">
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
            </div>
            <p>"The product quality exceeded my expectations. Fast delivery and excellent customer service!"</p>
            <div class="user">
               <img src="images/pic-1.png" alt="Customer 1">
               <div class="info">
                  <h3>John Doe</h3>
                  <span>happy customer</span>
               </div>
            </div>
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star-half-alt"></i>
            </div>
            <p>"Great prices and the products arrived in perfect condition. Will definitely shop here again."</p>
            <div class="user">
               <img src="images/pic-2.png" alt="Customer 2">
               <div class="info">
                  <h3>Jane Smith</h3>
                  <span>happy customer</span>
               </div>
            </div>
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
            </div>
            <p>"Excellent customer support helped me choose the perfect product for my needs. Highly recommended!"</p>
            <div class="user">
               <img src="images/pic-3.png" alt="Customer 3">
               <div class="info">
                  <h3>Michael Johnson</h3>
                  <span>happy customer</span>
               </div>
            </div>
         </div>
      </div>
      <div class="swiper-pagination"></div>
   </div>
</section>

<!-- Instagram Feed -->
<section class="instagram">
   <h1 class="heading animate__animated animate__fadeIn">follow us on instagram</h1>
   <div class="swiper instagram-slider">
      <div class="swiper-wrapper">
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/pic-1.png" alt="Instagram Post 1">
            <div class="icon">
               <a href="#" class="fab fa-instagram"></a>
            </div>
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/pic-2.png" alt="Instagram Post 2">
            <div class="icon">
               <a href="#" class="fab fa-instagram"></a>
            </div>
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/aaa.jpg" alt="Instagram Post 3">
            <div class="icon">
               <a href="#" class="fab fa-instagram"></a>
            </div>
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/gk.png" alt="Instagram Post 4">
            <div class="icon">
               <a href="#" class="fab fa-instagram"></a>
            </div>
         </div>
         <div class="swiper-slide slide animate__animated animate__fadeInUp">
            <img src="images/pic-5.png" alt="Instagram Post 5">
            <div class="icon">
               <a href="#" class="fab fa-instagram"></a>
            </div>
         </div>
      </div>
      <div class="swiper-pagination"></div>
   </div>
</section>

<!-- Quick View Modal -->
<div class="quick-view-modal">
   <div class="modal-content">
      <span class="close-modal">&times;</span>
      <div class="product-details">
         <!-- Content will be loaded via AJAX -->
      </div>
   </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
<script src="js/script.js"></script>

<script>
// All JavaScript code remains exactly the same
document.addEventListener('DOMContentLoaded', function() {
   // Home slider
   var homeSwiper = new Swiper(".home-slider", {
      loop: true,
      effect: 'fade',
      speed: 1000,
      autoplay: {
         delay: 5000,
         disableOnInteraction: false,
      },
      pagination: {
         el: ".swiper-pagination",
         clickable: true,
      },
   });
   // Category slider
   var categorySwiper = new Swiper(".category-slider", {
      loop: true,
      spaceBetween: 20,
      speed: 800,
      autoplay: {
         delay: 3000,
         disableOnInteraction: false,
      },
      pagination: {
         el: ".swiper-pagination",
         clickable: true,
      },
      breakpoints: {
         0: { slidesPerView: 2 },
         650: { slidesPerView: 3 },
         768: { slidesPerView: 4 },
         1024: { slidesPerView: 5 },
      },
   });

   // Products sliders
   var productsSliders = document.querySelectorAll('.products-slider');
   productsSliders.forEach(function(slider) {
      new Swiper(slider, {
         loop: true,
         spaceBetween: 20,
         speed: 800,
         autoplay: {
            delay: 4000,
            disableOnInteraction: false,
         },
         pagination: {
            el: slider.querySelector(".swiper-pagination"),
            clickable: true,
         },
         breakpoints: {
            550: { slidesPerView: 2 },
            768: { slidesPerView: 2 },
            1024: { slidesPerView: 3 },
         },
      });
   });

   // Brands slider
   var brandsSwiper = new Swiper(".brands-slider", {
      loop: true,
      spaceBetween: 20,
      speed: 1000,
      autoplay: {
         delay: 2500,
         disableOnInteraction: false,
      },
      pagination: {
         el: ".swiper-pagination",
         clickable: true,
      },
      breakpoints: {
         0: { slidesPerView: 2 },
         650: { slidesPerView: 3 },
         768: { slidesPerView: 4 },
         1024: { slidesPerView: 5 },
      },
   });

   // Testimonials slider
   var testimonialsSwiper = new Swiper(".testimonials-slider", {
      loop: true,
      spaceBetween: 20,
      speed: 800,
      autoplay: {
         delay: 5000,
         disableOnInteraction: false,
      },
      pagination: {
         el: ".swiper-pagination",
         clickable: true,
      },
      breakpoints: {
         0: { slidesPerView: 1 },
         768: { slidesPerView: 2 },
         1024: { slidesPerView: 3 },
      },
   });

   // Instagram slider
   var instagramSwiper = new Swiper(".instagram-slider", {
      loop: true,
      spaceBetween: 20,
      speed: 1000,
      autoplay: {
         delay: 3000,
         disableOnInteraction: false,
      },
      pagination: {
         el: ".swiper-pagination",
         clickable: true,
      },
      breakpoints: {
         0: { slidesPerView: 2 },
         650: { slidesPerView: 3 },
         1024: { slidesPerView: 4 },
      },
   });

   // Add scroll animations
   const animateElements = document.querySelectorAll('.animate__animated');
   
   const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
         if (entry.isIntersecting) {
            const animationClass = entry.target.classList[1]; // Get the animation class
            entry.target.classList.add(animationClass);
            observer.unobserve(entry.target);
         }
      });
   }, { threshold: 0.1 });
   
   animateElements.forEach(element => {
      observer.observe(element);
   });

   // Countdown timer for special offer
   <?php if(isset($fetch_offer)): ?>
   function updateCountdown() {
      const now = new Date().getTime();
      const endDate = new Date("<?= $fetch_offer['end_date'] ?>").getTime();
      const distance = endDate - now;
      
      if (distance < 0) {
         document.querySelector('.countdown-timer').innerHTML = '<h4>Offer has expired</h4>';
         return;
      }
      
      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);
      
      document.getElementById('days').innerText = days.toString().padStart(2, '0');
      document.getElementById('hours').innerText = Math.floor(hours).toString().padStart(2, '0');
      document.getElementById('minutes').innerText = Math.floor(minutes).toString().padStart(2, '0');
      document.getElementById('seconds').innerText = Math.floor(seconds).toString().padStart(2, '0');
   }

   // Update every second
   setInterval(updateCountdown, 1000);
   updateCountdown(); // Initial call
   <?php endif; ?>

   // Enhanced quick view modal
   document.querySelectorAll('.fa-eye').forEach(eye => {
      eye.addEventListener('click', function(e) {
         e.preventDefault();
         const pid = this.closest('form').querySelector('input[name="pid"]').value;
         
         fetch(`quick_view.php?pid=${pid}`)
            .then(response => response.text())
            .then(data => {
               document.querySelector('.quick-view-modal .product-details').innerHTML = data;
               document.querySelector('.quick-view-modal').style.display = 'block';
               document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
            })
            .catch(error => {
               console.error('Error loading quick view:', error);
            });
      });
   });

   // Close modal
   document.querySelector('.close-modal').addEventListener('click', function() {
      document.querySelector('.quick-view-modal').style.display = 'none';
      document.body.style.overflow = 'auto'; // Re-enable scrolling
   });

   // Close modal when clicking outside
   window.addEventListener('click', function(event) {
      if (event.target === document.querySelector('.quick-view-modal')) {
         document.querySelector('.quick-view-modal').style.display = 'none';
         document.body.style.overflow = 'auto';
      }
   });

   // Newsletter form submission
   document.getElementById('newsletterForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const messageDiv = this.querySelector('.message');
      
      fetch('components/newsletter_subscribe.php', {
         method: 'POST',
         body: formData
      })
      .then(response => response.json())
      .then(data => {
         messageDiv.innerHTML = `<div class="alert ${data.success ? 'success' : 'error'}">${data.message}</div>`;
         if(data.success) this.reset();
         
         // Hide message after 5 seconds
         setTimeout(() => {
            messageDiv.innerHTML = '';
         }, 5000);
      })
      .catch(error => {
         messageDiv.innerHTML = '<div class="alert error">An error occurred. Please try again.</div>';
         setTimeout(() => {
            messageDiv.innerHTML = '';
         }, 5000);
      });
   });
});
</script>

</body>
</html>