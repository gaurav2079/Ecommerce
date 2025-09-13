<?php
// File: components/ProductSearch.php
class ProductSearch {
    private $conn;
    private $all_products;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadAllProducts();
    }
    
    private function loadAllProducts() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM `products`");
            $stmt->execute();
            $this->all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->sortProductsByName();
        } catch (PDOException $e) {
            error_log("Product loading error: " . $e->getMessage());
            $this->all_products = [];
        }
    }
    
    private function sortProductsByName() {
        usort($this->all_products, function($a, $b) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });
    }
    
    public function binarySearch($search_term) {
        if (empty($search_term) || empty($this->all_products)) {
            return [];
        }
        
        $matches = [];
        $low = 0;
        $high = count($this->all_products) - 1;
        $search_term = strtolower($search_term);
        
        while ($low <= $high) {
            $mid = floor(($low + $high) / 2);
            $current_name = strtolower($this->all_products[$mid]['name']);
            
            if ($this->containsString($current_name, $search_term)) {
                $matches[] = $this->all_products[$mid];
                
                // Check left side
                $left = $mid - 1;
                while ($left >= 0 && $this->containsString(
                    strtolower($this->all_products[$left]['name']), $search_term)) {
                    $matches[] = $this->all_products[$left];
                    $left--;
                }
                
                // Check right side
                $right = $mid + 1;
                while ($right < count($this->all_products) && $this->containsString(
                    strtolower($this->all_products[$right]['name']), $search_term)) {
                    $matches[] = $this->all_products[$right];
                    $right++;
                }
                
                return $matches;
            }
            
            if (strcmp($search_term, $current_name) < 0) {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }
        
        return $matches;
    }
    
    private function containsString($haystack, $needle) {
        $haystack_length = $this->stringLength($haystack);
        $needle_length = $this->stringLength($needle);
        
        if ($needle_length === 0) return true;
        if ($haystack_length < $needle_length) return false;
        
        for ($i = 0; $i <= $haystack_length - $needle_length; $i++) {
            $match = true;
            for ($j = 0; $j < $needle_length; $j++) {
                if ($haystack[$i + $j] !== $needle[$j]) {
                    $match = false;
                    break;
                }
            }
            if ($match) return true;
        }
        
        return false;
    }
    
    private function stringLength($str) {
        $length = 0;
        while (isset($str[$length])) {
            $length++;
        }
        return $length;
    }
}

// File: components/SearchController.php
class SearchController {
    private $product_search;
    private $search_term;
    private $search_results;
    private $search_message;
    
    public function __construct($conn, $search_term) {
        $this->product_search = new ProductSearch($conn);
        $this->search_term = trim($search_term);
        $this->search_results = [];
        $this->search_message = '';
    }
    
    public function executeSearch() {
        if (!empty($this->search_term)) {
            try {
                $this->search_results = $this->product_search->binarySearch($this->search_term);
                $result_count = count($this->search_results);
                $this->search_message = $result_count . " results for \"" . htmlspecialchars($this->search_term) . "\"";
            } catch (Exception $e) {
                error_log("Search error: " . $e->getMessage());
                $this->search_message = "Search temporarily unavailable";
            }
        }
    }
    
    public function getSearchResults() {
        return $this->search_results;
    }
    
    public function getSearchMessage() {
        return $this->search_message;
    }
    
    public function getSearchTerm() {
        return $this->search_term;
    }
}

// Main execution
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/connect.php';
include 'components/wishlist_cart.php';

$search_term = isset($_GET['search_box']) ? $_GET['search_box'] : '';
$search_controller = new SearchController($conn, $search_term);
$search_controller->executeSearch();

$search_results = $search_controller->getSearchResults();
$search_message = $search_controller->getSearchMessage();
$search_term = $search_controller->getSearchTerm();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Search Products - Nepal~Store</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/search.css">
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="search-page-container">
   <div class="search-header">
      <form action="" method="get" class="search-form">
         <div class="search-input-container">
            <input type="text" name="search_box" placeholder="Search for products..." 
                   value="<?= htmlspecialchars($search_term) ?>" required>
            <button type="submit" class="search-button">
               <i class="fas fa-search"></i>
            </button>
         </div>
      </form>
   </div>

   <div class="search-content">
      <?php if(!empty($search_term)): ?>
      <div class="search-results-header">
         <div class="results-count"><?= $search_message ?></div>
      </div>

      <div class="search-results-container">
         <?php if(!empty($search_results)): ?>
            <div class="product-grid">
               <?php foreach($search_results as $product): ?>
               <div class="product-card">
                  <div class="product-image">
                     <a href="quick_view.php?pid=<?= $product['id'] ?>">
                        <img src="uploaded_img/<?= $product['image_01'] ?>" alt="<?= $product['name'] ?>">
                     </a>
                     <form action="" method="post" class="wishlist-form">
                        <input type="hidden" name="pid" value="<?= $product['id'] ?>">
                        <input type="hidden" name="name" value="<?= $product['name'] ?>">
                        <input type="hidden" name="price" value="<?= $product['price'] ?>">
                        <input type="hidden" name="image" value="<?= $product['image_01'] ?>">
                        <button type="submit" name="add_to_wishlist" class="wishlist-button">
                           <i class="far fa-heart"></i>
                        </button>
                     </form>
                     <div class="quick-view">
                        <a href="quick_view.php?pid=<?= $product['id'] ?>">Quick View</a>
                     </div>
                  </div>
                  <div class="product-info">
                     <h3 class="product-title">
                        <a href="quick_view.php?pid=<?= $product['id'] ?>"><?= $product['name'] ?></a>
                     </h3>
                     <div class="product-price">
                        <span class="price-symbol">₹</span>
                        <span class="price-amount"><?= number_format($product['price'], 2) ?></span>
                     </div>
                     <form action="" method="post" class="add-to-cart-form">
                        <input type="hidden" name="pid" value="<?= $product['id'] ?>">
                        <input type="hidden" name="name" value="<?= $product['name'] ?>">
                        <input type="hidden" name="price" value="<?= $product['price'] ?>">
                        <input type="hidden" name="image" value="<?= $product['image_01'] ?>">
                        <div class="quantity-container">
                           <label>Qty:</label>
                           <input type="number" name="qty" min="1" max="99" value="1">
                        </div>
                        <button type="submit" name="add_to_cart" class="add-to-cart-button">
                           <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                     </form>
                  </div>
               </div>
               <?php endforeach; ?>
            </div>
         <?php else: ?>
            <div class="no-results-found">
               <div class="no-results-icon">
                  <i class="fas fa-search-minus"></i>
               </div>
               <h3>No products found for "<?= htmlspecialchars($search_term) ?>"</h3>
               <p>Try checking your spelling or use more general terms</p>
               <a href="home.php" class="continue-shopping-btn">Continue Shopping</a>
            </div>
         <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="search-empty-state">
         <div class="empty-icon">
            <i class="fas fa-search"></i>
         </div>
         <h3>Search for products</h3>
         <p>Find your favorite products by typing in the search box above</p>
      </div>
      <?php endif; ?>
   </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>