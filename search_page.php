<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

// Pagination setup
$per_page = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Search functionality
$search_results = [];
$total_results = 0;
$search_term = '';
$search_message = '';

if(isset($_POST['search_box']) || isset($_POST['search_btn'])){
    $search_term = trim($_POST['search_box']);
    
    if(!empty($search_term)){
        try {
            // First try a simple LIKE search (works on all MySQL versions)
            $stmt = $conn->prepare("
                SELECT * FROM `products` 
                WHERE name LIKE :search
                ORDER BY name ASC
                LIMIT :offset, :per_page
            ");
            $search_param = "%$search_term%";
            $stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
            $stmt->execute();
            $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count for pagination
            $count_stmt = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM `products`
                WHERE name LIKE :search
            ");
            $count_stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
            $count_stmt->execute();
            $total_results = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $search_message = "Search results for:";
            
        } catch (PDOException $e) {
            error_log("Search error: " . $e->getMessage());
            echo '<div class="no-results animate__animated animate__fadeIn">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p class="empty">Search temporarily unavailable</p>
                  </div>';
        }
    }
}

// Calculate total pages for pagination
$total_pages = ceil($total_results / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Search Products</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- animate.css for loading animations -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
   
   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   
   <style>
      .search-form {
         background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
         padding: 3rem 2rem;
         border-radius: 10px;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
         margin: 2rem auto;
         max-width: 800px;
      }
      
      .search-form form {
         display: flex;
         align-items: center;
      }
      
      .search-form .box {
         flex: 1;
         padding: 1.2rem;
         font-size: 1.1rem;
         border: 2px solid #ddd;
         border-radius: 50px;
         transition: all 0.3s;
      }
      
      .search-form .box:focus {
         border-color: #2980b9;
         outline: none;
         box-shadow: 0 0 10px rgba(41, 128, 185, 0.3);
      }
      
      .search-form .fas.fa-search {
         background: #2980b9;
         color: white;
         border: none;
         padding: 1.2rem 1.5rem;
         border-radius: 50px;
         margin-left: -50px;
         cursor: pointer;
         transition: all 0.3s;
      }
      
      .search-form .fas.fa-search:hover {
         background: #3498db;
         transform: scale(1.05);
      }
      
      .products .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
         gap: 2rem;
         padding: 2rem;
      }
      
      .products .box {
         transition: transform 0.3s, box-shadow 0.3s;
      }
      
      .products .box:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 20px rgba(0,0,0,0.1);
      }
      
      .no-results {
         text-align: center;
         grid-column: 1 / -1;
         padding: 3rem;
         background: #f8f9fa;
         border-radius: 10px;
      }
      
      .no-results i {
         font-size: 3rem;
         color: #ccc;
         margin-bottom: 1rem;
      }
      
      .search-info {
         text-align: center;
         margin: 1rem 0;
         color: #666;
         font-style: italic;
      }
      
      .loading {
         display: none;
         text-align: center;
         padding: 2rem;
      }
      
      .loading-spinner {
         width: 50px;
         height: 50px;
         border: 5px solid #f3f3f3;
         border-top: 5px solid #3498db;
         border-radius: 50%;
         animation: spin 1s linear infinite;
         margin: 0 auto;
      }
      
      .pagination {
         display: flex;
         justify-content: center;
         margin: 2rem 0;
         flex-wrap: wrap;
      }
      
      .pagination a {
         color: #2980b9;
         padding: 0.5rem 1rem;
         margin: 0 0.25rem;
         text-decoration: none;
         border: 1px solid #ddd;
         border-radius: 4px;
         transition: all 0.3s;
      }
      
      .pagination a.active,
      .pagination a:hover {
         background: #2980b9;
         color: white;
         border-color: #2980b9;
      }
      
      .pagination a.disabled {
         color: #ccc;
         pointer-events: none;
      }
      
      @keyframes spin {
         0% { transform: rotate(0deg); }
         100% { transform: rotate(360deg); }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="search-form animate__animated animate__fadeIn">
   <form action="" method="post" id="searchForm">
      <input type="text" name="search_box" placeholder="Search for products..." maxlength="100" class="box" required 
             value="<?= htmlspecialchars($search_term) ?>">
      <button type="submit" class="fas fa-search" name="search_btn"></button>
   </form>
</section>

<section class="products" style="padding-top: 0; min-height:100vh;">
   <div id="loading" class="loading">
      <div class="loading-spinner"></div>
      <p>Searching products...</p>
   </div>
   
   <div class="box-container" id="searchResults">
   <?php
   if(!empty($search_term)){
      echo '<div class="search-info">'.$search_message.' <strong>"'.htmlspecialchars($search_term).'"</strong></div>';
      
      if(!empty($search_results)){
         foreach($search_results as $product){
   ?>
   <form action="" method="post" class="box animate__animated animate__fadeInUp">
      <input type="hidden" name="pid" value="<?= $product['id'] ?>">
      <input type="hidden" name="name" value="<?= $product['name'] ?>">
      <input type="hidden" name="price" value="<?= $product['price'] ?>">
      <input type="hidden" name="image" value="<?= $product['image_01'] ?>">
      <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
      <a href="quick_view.php?pid=<?= $product['id'] ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $product['image_01'] ?>" alt="<?= $product['name'] ?>" loading="lazy">
      <div class="name"><?= $product['name'] ?></div>
      <div class="flex">
         <div class="price"><span>₹</span><?= $product['price'] ?><span>/-</span></div>
                  <input type="number" name="qty" class="qty" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="1">

      </div>
      <input type="submit" value="add to cart" class="btn" name="add_to_cart">
   </form>
   <?php
         }
      } else {
         echo '<div class="no-results animate__animated animate__fadeIn">
                  <i class="fas fa-search-minus"></i>
                  <p class="empty">No products found matching your search!</p>
                  <p>Try different keywords or check our featured products.</p>
               </div>';
      }
      
      // Pagination
      if($total_pages > 1){
         echo '<div class="pagination">';
         if($page > 1){
            echo '<a href="?search_box='.urlencode($search_term).'&page='.($page-1).'">&laquo; Previous</a>';
         }
         
         for($i = 1; $i <= $total_pages; $i++){
            if($i == $page){
               echo '<a class="active">'.$i.'</a>';
            } else {
               echo '<a href="?search_box='.urlencode($search_term).'&page='.$i.'">'.$i.'</a>';
            }
         }
         
         if($page < $total_pages){
            echo '<a href="?search_box='.urlencode($search_term).'&page='.($page+1).'">Next &raquo;</a>';
         }
         echo '</div>';
      }
   }
   ?>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

<script>
   document.addEventListener('DOMContentLoaded', function() {
      const searchForm = document.getElementById('searchForm');
      const searchResults = document.getElementById('searchResults');
      const loading = document.getElementById('loading');
      
      if(searchForm) {
         searchForm.addEventListener('submit', function() {
            searchResults.style.display = 'none';
            loading.style.display = 'block';
            
            setTimeout(() => {
               loading.style.display = 'none';
               searchResults.style.display = 'grid';
            }, 800);
         });
      }
      
      const productBoxes = document.querySelectorAll('.products .box');
      productBoxes.forEach(box => {
         box.addEventListener('mouseenter', () => {
            box.classList.add('animate__pulse');
         });
         box.addEventListener('mouseleave', () => {
            box.classList.remove('animate__pulse');
         });
      });
      
      // Auto-focus search input on page load if there's a search term
      <?php if(!empty($search_term)): ?>
         document.querySelector('input[name="search_box"]').focus();
      <?php endif; ?>
   });
</script>

</body>
</html>