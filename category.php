<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
}

include 'components/wishlist_cart.php';

// 1. INPUT SANITIZATION
$category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING);

// 2. CACHE SYSTEM INITIALIZATION
$cacheDir = __DIR__ . '/cache/';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// 3. CACHE KEY GENERATION
$cacheKey = 'products_' . md5($category);
$cacheFile = $cacheDir . $cacheKey . '.cache';

// 4. CACHE VALIDATION ALGORITHM
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
    // Cache hit - load from file
    $products = unserialize(file_get_contents($cacheFile));
} else {
    // Cache miss - query database
    $select_products = $conn->prepare("SELECT * FROM `products` WHERE name LIKE ?");
    $select_products->execute(["%$category%"]);
    $products = $select_products->fetchAll(PDO::FETCH_ASSOC);
    
    // Update cache
    file_put_contents($cacheFile, serialize($products));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>category</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- Google fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <!-- Animate.css -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      /* Enhanced Styling */
      :root {
         --primary: #6c63ff;
         --secondary: #ff6584;
         --dark: #2d2b3a;
         --light: #f9f9ff;
         --gray: #e2e2e2;
         --success: #4caf50;
      }
      
      body {
         font-family: 'Poppins', sans-serif;
         background-color: #f5f5f7;
         color: var(--dark);
      }
      
      .heading {
         text-align: center;
         font-size: 2.5rem;
         color: var(--dark);
         margin-bottom: 2rem;
         position: relative;
         padding-bottom: 15px;
      }
      
      .heading:after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 50%;
         transform: translateX(-50%);
         width: 100px;
         height: 4px;
         background: linear-gradient(to right, var(--primary), var(--secondary));
         border-radius: 2px;
      }
      
      .products {
         padding: 5rem 9%;
         background: url('https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80') no-repeat;
         background-size: cover;
         background-position: center;
         background-attachment: fixed;
         position: relative;
      }
      
      .products:before {
         content: '';
         position: absolute;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background: rgba(255, 255, 255, 0.9);
      }
      
      .products .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
         gap: 2rem;
         position: relative;
         z-index: 1;
      }
      
      .products .box-container .box {
         background: white;
         border-radius: 12px;
         overflow: hidden;
         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
         transition: all 0.3s ease;
         position: relative;
      }
      
      .products .box-container .box:hover {
         transform: translateY(-10px);
         box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
      }
      
      .products .box-container .box img {
         width: 100%;
         height: 220px;
         object-fit: cover;
         border-bottom: 1px solid var(--gray);
      }
      
      .products .box-container .box .name {
         font-size: 1.1rem;
         color: var(--dark);
         padding: 1rem;
         font-weight: 600;
      }
      
      .products .box-container .box .flex {
         display: flex;
         align-items: center;
         justify-content: space-between;
         padding: 0 1rem 1rem;
      }
      
      .products .box-container .box .price {
         font-size: 1.4rem;
         color: var(--primary);
         font-weight: 700;
      }
      
      .products .box-container .box .price span {
         font-size: 1rem;
         color: #777;
      }
      
      .products .box-container .box .qty {
         width: 70px;
         padding: 0.5rem;
         border: 1px solid var(--gray);
         border-radius: 5px;
         text-align: center;
      }
      
      .products .box-container .box .btn {
         display: block;
         width: calc(100% - 2rem);
         margin: 0 auto 1rem;
         background: linear-gradient(to right, var(--primary), var(--secondary));
         color: white;
         border: none;
         border-radius: 5px;
         padding: 0.7rem;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.3s ease;
      }
      
      .products .box-container .box .btn:hover {
         opacity: 0.9;
         transform: translateY(-2px);
      }
      
      .products .box-container .box .fa-heart {
         position: absolute;
         top: 1rem;
         right: 1rem;
         font-size: 1.5rem;
         color: #ccc;
         cursor: pointer;
         z-index: 2;
         transition: all 0.3s ease;
      }
      
      .products .box-container .box .fa-heart:hover {
         color: var(--secondary);
         transform: scale(1.1);
      }
      
      .products .box-container .box .fa-eye {
         position: absolute;
         top: 1rem;
         left: 1rem;
         font-size: 1.5rem;
         color: #ccc;
         cursor: pointer;
         z-index: 2;
         transition: all 0.3s ease;
      }
      
      .products .box-container .box .fa-eye:hover {
         color: var(--primary);
         transform: scale(1.1);
      }
      
      .empty {
         text-align: center;
         grid-column: 1/-1;
         font-size: 1.2rem;
         color: #777;
         padding: 3rem;
         background: white;
         border-radius: 12px;
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      }
      
      @media (max-width: 768px) {
         .products {
            padding: 3rem 5%;
         }
         
         .heading {
            font-size: 2rem;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="products">

   <h1 class="heading">category</h1>

   <div class="box-container">

   <?php
     $category = $_GET['category'];
     $select_products = $conn->prepare("SELECT * FROM `products` WHERE name LIKE '%{$category}%'"); 
     $select_products->execute();
     if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
      <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
      <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
      <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
      <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="">
      <div class="name"><?= $fetch_product['name']; ?></div>
      <div class="flex">
         <div class="price"><span>₹</span><?= $fetch_product['price']; ?><span>/-</span></div>
         <input type="number" name="qty" class="qty" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="1">
      </div>
      <input type="submit" value="add to cart" class="btn" name="add_to_cart">
   </form>
   <?php
      }
   }else{
      echo '<p class="empty">no products found!</p>';
   }
   ?>

   </div>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

<!-- Additional JS for animations -->
<script>
   document.addEventListener('DOMContentLoaded', function() {
      const boxes = document.querySelectorAll('.box');
      boxes.forEach((box, index) => {
         box.style.animationDelay = `${index * 0.1}s`;
         box.classList.add('animate__animated', 'animate__fadeInUp');
      });
   });
</script>

</body>
</html>