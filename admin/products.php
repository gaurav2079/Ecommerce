<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;

if(!isset($admin_id)){
   header('location:admin_login.php');
   exit();
}

// Define ProductManager class
class ProductManager {
    private $conn;
    private $messages = [];
    private $formErrors = [];
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function getMessages() {
        return $this->messages;
    }
    
    public function getFormErrors() {
        return $this->formErrors;
    }
    
    public function addMessage($message, $type = 'info') {
        $this->messages[] = ['text' => $message, 'type' => $type];
    }
    
    public function addFormError($field, $message) {
        $this->formErrors[$field] = $message;
    }
    
    public function validateProductName($name) {
        // Remove extra spaces
        $name = trim($name);
        
        // Check if empty
        if (empty($name)) {
            $this->addFormError('name', 'Product name is required');
            return false;
        }
        
        // Check minimum length
        if (strlen($name) < 3) {
            $this->addFormError('name', 'Product name must be at least 3 characters long');
            return false;
        }
        
        // Check maximum length
        if (strlen($name) > 100) {
            $this->addFormError('name', 'Product name must be less than 100 characters');
            return false;
        }
        
        return true;
    }
    
    public function validateProductPrice($price) {
        // Check if empty
        if (empty($price)) {
            $this->addFormError('price', 'Product price is required');
            return false;
        }
        
        // Check if it's a valid number
        if (!is_numeric($price)) {
            $this->addFormError('price', 'Product price must be a valid number');
            return false;
        }
        
        // Convert to float for further validation
        $price_float = (float)$price;
        
        // Check if price is at least 1
        if ($price_float < 1) {
            $this->addFormError('price', 'Product price must be at least ₹1');
            return false;
        }
        
        return true;
    }
    
    public function validateProductDetails($details) {
        // Remove extra spaces
        $details = trim($details);
        
        // Check if empty
        if (empty($details)) {
            $this->addFormError('details', 'Product details are required');
            return false;
        }
        
        // Check minimum length
        if (strlen($details) < 10) {
            $this->addFormError('details', 'Product details must be at least 10 characters long');
            return false;
        }
        
        return true;
    }
    
    public function validateStock($stock) {
        // Check if empty
        if (empty($stock)) {
            $this->addFormError('stock', 'Product stock is required');
            return false;
        }
        
        // Check if it's a valid integer
        if (!filter_var($stock, FILTER_VALIDATE_INT) || $stock < 0) {
            $this->addFormError('stock', 'Stock must be a valid positive integer');
            return false;
        }
        
        return true;
    }
    
    public function validateQuantity($quantity) {
        // Check if empty
        if (empty($quantity)) {
            $this->addFormError('quantity', 'Product quantity is required');
            return false;
        }
        
        // Check if it's a valid integer
        if (!filter_var($quantity, FILTER_VALIDATE_INT) || $quantity < 0) {
            $this->addFormError('quantity', 'Quantity must be a valid positive integer');
            return false;
        }
        
        return true;
    }
    
    public function validateProductImages($files) {
        $upload_errors = [];
        $image_names = [];
        
        $image_fields = ['image_01', 'image_02', 'image_03'];
        
        foreach($image_fields as $field){
            if(!empty($files[$field]['name'])){
                $image_name = $files[$field]['name'];
                $image_size = $files[$field]['size'];
                $image_tmp_name = $files[$field]['tmp_name'];
                $image_error = $files[$field]['error'];
                
                // Check for upload errors
                if ($image_error !== UPLOAD_ERR_OK) {
                    $upload_errors[$field] = 'File upload error: ' . $image_error;
                    continue;
                }
                
                // Validate image size
                if($image_size > 2000000){
                    $upload_errors[$field] = 'File size is too large (max 2MB)';
                    continue;
                }
                
                // Validate file type
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                $file_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                
                if(!in_array($file_extension, $allowed_extensions)){
                    $upload_errors[$field] = 'Invalid file type. Only JPG, JPEG, PNG, WEBP allowed';
                    continue;
                }
                
                // Generate unique filename
                $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                $image_names[$field] = $unique_name;
            } else {
                $upload_errors[$field] = 'Image is required';
            }
        }
        
        if (!empty($upload_errors)) {
            $this->formErrors = array_merge($this->formErrors, $upload_errors);
            return false;
        }
        
        return $image_names;
    }
    
    public function addProduct($name, $price, $details, $stock, $quantity, $files) {
        // Reset form errors
        $this->formErrors = [];
        
        // Validate inputs
        $isNameValid = $this->validateProductName($name);
        $isPriceValid = $this->validateProductPrice($price);
        $isDetailsValid = $this->validateProductDetails($details);
        $isStockValid = $this->validateStock($stock);
        $isQuantityValid = $this->validateQuantity($quantity);
        $image_names = $this->validateProductImages($files);
        
        // Debug: Check what's being validated
        error_log("Validation - Name: $isNameValid, Price: $isPriceValid, Details: $isDetailsValid, Stock: $isStockValid, Quantity: $isQuantityValid, Images: " . ($image_names ? 'valid' : 'invalid'));
        
        // If any validation failed, return false
        if (!$isNameValid || !$isPriceValid || !$isDetailsValid || !$isStockValid || !$isQuantityValid || $image_names === false) {
            error_log("Validation failed. Errors: " . print_r($this->formErrors, true));
            return false;
        }
        
        // If all validations passed, proceed with database insertion
        try {
            $insert_products = $this->conn->prepare("INSERT INTO `products` (name, details, price, stock, quantity, image_01, image_02, image_03, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            
            $insert_result = $insert_products->execute([
                $name, 
                $details, 
                $price, 
                $stock,
                $quantity,
                $image_names['image_01'], 
                $image_names['image_02'], 
                $image_names['image_03']
            ]);
            
            if ($insert_result) {
                // Create uploaded_img directory if it doesn't exist
                $upload_dir = '../uploaded_img/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Move uploaded files
                $image_fields = ['image_01', 'image_02', 'image_03'];
                foreach($image_fields as $field){
                    $source = $files[$field]['tmp_name'];
                    $destination = $upload_dir . $image_names[$field];
                    
                    if (is_uploaded_file($source)) {
                        if (!move_uploaded_file($source, $destination)) {
                            error_log("Failed to move uploaded file: $source to $destination");
                        }
                    } else {
                        error_log("File not uploaded properly: $source");
                    }
                }
                
                $this->addMessage('New product added successfully!', 'success');
                error_log("Product added successfully: $name");
                return true;
            } else {
                $errorInfo = $insert_products->errorInfo();
                $this->addMessage('Failed to add product to database: ' . $errorInfo[2], 'error');
                error_log("Database error: " . print_r($errorInfo, true));
                return false;
            }
        } catch(PDOException $e) {
            $this->addMessage('Error adding product: ' . $e->getMessage(), 'error');
            error_log("PDO Exception: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteProduct($product_id) {
        $product_id = filter_var($product_id, FILTER_SANITIZE_NUMBER_INT);
        
        try {
            // Get product images
            $delete_product_image = $this->conn->prepare("SELECT * FROM `products` WHERE id = ?");
            $delete_product_image->execute([$product_id]);
            $fetch_delete_image = $delete_product_image->fetch(PDO::FETCH_ASSOC);
            
            // Delete images from server
            if($fetch_delete_image){
                $images_to_delete = [
                    $fetch_delete_image['image_01'],
                    $fetch_delete_image['image_02'],
                    $fetch_delete_image['image_03']
                ];
                
                foreach($images_to_delete as $image){
                    if(file_exists('../uploaded_img/'.$image)){
                        unlink('../uploaded_img/'.$image);
                    }
                }
            }
            
            // Delete from database
            $delete_product = $this->conn->prepare("DELETE FROM `products` WHERE id = ?");
            $delete_product->execute([$product_id]);
            
            $this->addMessage('Product deleted successfully!', 'success');
            return true;
        } catch(PDOException $e) {
            $this->addMessage('Error deleting product: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    public function getAllProducts() {
        try {
            $select_products = $this->conn->prepare("SELECT * FROM `products` ORDER BY id DESC");
            $select_products->execute();
            return $select_products;
        } catch(PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize ProductManager
$productManager = new ProductManager($conn);

// Handle form submissions
if(isset($_POST['add_product'])){
    $success = $productManager->addProduct(
        $_POST['name'] ?? '', 
        $_POST['price'] ?? '', 
        $_POST['details'] ?? '', 
        $_POST['stock'] ?? '', 
        $_POST['quantity'] ?? '', 
        $_FILES
    );
    
    // If product was added successfully, clear form values
    if ($success) {
        $_POST = array(); // Clear form data
        // Redirect to avoid form resubmission
        header('Location: products.php?success=1');
        exit();
    }
}

if(isset($_GET['delete'])){
    $productManager->deleteProduct($_GET['delete']);
    header('location:products.php');
    exit();
}

// Check for success parameter
if(isset($_GET['success']) && $_GET['success'] == 1) {
    $productManager->addMessage('New product added successfully!', 'success');
}

// Get messages and form errors for display
$messages = $productManager->getMessages();
$formErrors = $productManager->getFormErrors();

// Get previous form values if validation failed
$nameValue = $_POST['name'] ?? '';
$priceValue = $_POST['price'] ?? '';
$detailsValue = $_POST['details'] ?? '';
$stockValue = $_POST['stock'] ?? '';
$quantityValue = $_POST['quantity'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Products Management</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <style>
      /* Your existing CSS styles remain the same */
      :root {
         --primary: #4361ee;
         --primary-light: #4895ef;
         --secondary: #3f37c9;
         --dark: #2b2d42;
         --light: #f8f9fa;
         --danger: #ef233c;
         --success: #4cc9f0;
         --warning: #ffcc00;
         --border-radius: 8px;
         --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
         --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      }

      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      body {
         background-color: #f5f7fa;
         color: var(--dark);
      }

      .message {
         position: fixed;
         top: 90px;
         right: 20px;
         padding: 15px 20px;
         border-radius: var(--border-radius);
         display: flex;
         align-items: center;
         gap: 10px;
         box-shadow: var(--shadow);
         z-index: 1000;
         animation: slideIn 0.3s ease-out;
      }

      .message.success {
         background: #e6ffed;
         color: var(--success);
         border-left: 4px solid var(--success);
      }

      .message.error {
         background: #ffebee;
         color: var(--danger);
         border-left: 4px solid var(--danger);
      }

      .message.warning {
         background: #fff8e1;
         color: var(--warning);
         border-left: 4px solid var(--warning);
      }

      @keyframes slideIn {
         from { transform: translateX(100%); opacity: 0; }
         to { transform: translateX(0); opacity: 1; }
      }

      .message i {
         cursor: pointer;
         margin-left: 10px;
      }

      .heading {
         text-align: center;
         margin-bottom: 2rem;
         font-size: 2rem;
         color: var(--dark);
         position: relative;
         padding-bottom: 10px;
      }

      .heading:after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 50%;
         transform: translateX(-50%);
         width: 80px;
         height: 3px;
         background: var(--primary);
      }

      .add-products, .show-products {
         max-width: 1200px;
         margin: 0 auto;
         padding: 2rem;
      }

      .add-products form {
         background: white;
         padding: 2rem;
         border-radius: var(--border-radius);
         box-shadow: var(--shadow);
         margin-bottom: 3rem;
      }

      .flex {
         display: flex;
         flex-wrap: wrap;
         gap: 1.5rem;
         margin-bottom: 1.5rem;
      }

      .inputBox {
         flex: 1 1 30rem;
         position: relative;
      }

      .inputBox span {
         display: block;
         margin-bottom: 0.5rem;
         font-weight: 500;
         color: var(--dark);
      }

      .inputBox .box, .inputBox textarea {
         width: 100%;
         padding: 1rem;
         border: 1px solid #e0e3e8;
         border-radius: var(--border-radius);
         font-size: 1rem;
         transition: var(--transition);
      }

      .inputBox .box:focus, .inputBox textarea:focus {
         outline: none;
         border-color: var(--primary);
         box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .inputBox .box.error, .inputBox textarea.error {
         border-color: var(--danger);
      }

      .inputBox textarea {
         resize: vertical;
         min-height: 150px;
      }

      .error-message {
         color: var(--danger);
         font-size: 0.85rem;
         margin-top: 0.5rem;
         display: block;
      }

      .file-input {
         position: relative;
         margin-bottom: 1rem;
      }

      .file-input input[type="file"] {
         position: absolute;
         left: 0;
         top: 0;
         width: 100%;
         height: 100%;
         opacity: 0;
         cursor: pointer;
      }

      .file-input-label {
         display: block;
         padding: 1rem;
         background: #f8f9fa;
         border: 1px dashed #ddd;
         border-radius: var(--border-radius);
         text-align: center;
         cursor: pointer;
         transition: var(--transition);
      }

      .file-input-label.error {
         border-color: var(--danger);
         background: #fff5f5;
      }

      .file-input-label:hover {
         background: #e9ecef;
      }

      .file-input-label i {
         margin-right: 8px;
      }

      .file-preview {
         margin-top: 10px;
         display: flex;
         gap: 10px;
         flex-wrap: wrap;
      }

      .file-preview img {
         max-width: 100px;
         max-height: 100px;
         border-radius: 4px;
         border: 1px solid #ddd;
      }

      .btn {
         display: inline-block;
         padding: 1rem 2rem;
         background: var(--primary);
         color: white;
         border: none;
         border-radius: var(--border-radius);
         font-size: 1rem;
         font-weight: 600;
         cursor: pointer;
         transition: var(--transition);
         text-align: center;
      }

      .btn:hover {
         background: var(--secondary);
         transform: translateY(-2px);
         box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
      }

      .btn:disabled {
         background: #ccc;
         cursor: not-allowed;
         transform: none;
         box-shadow: none;
      }

      .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
         gap: 2rem;
      }

      .box-container .box {
         background: white;
         border-radius: var(--border-radius);
         box-shadow: var(--shadow);
         overflow: hidden;
         transition: var(--transition);
      }

      .box-container .box:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      }

      .box-container .box img {
         width: 100%;
         height: 200px;
         object-fit: cover;
      }

      .box-container .box .name {
         padding: 1rem;
         font-size: 1.2rem;
         font-weight: 600;
         color: var(--dark);
      }

      .box-container .box .price {
         padding: 0 1rem;
         font-size: 1.5rem;
         color: var(--primary);
         font-weight: 700;
      }

      .box-container .box .details {
         padding: 1rem;
         color: #6c757d;
         font-size: 0.9rem;
         line-height: 1.5;
         display: -webkit-box;
         -webkit-line-clamp: 3;
         -webkit-box-orient: vertical;
         overflow: hidden;
      }

      .flex-btn {
         display: flex;
         gap: 1rem;
         padding: 1rem;
      }

      .option-btn, .delete-btn {
         flex: 1;
         text-align: center;
         padding: 0.8rem;
         border-radius: var(--border-radius);
         font-weight: 600;
         transition: var(--transition);
         text-decoration: none;
      }

      .option-btn {
         background: var(--primary-light);
         color: white;
      }

      .option-btn:hover {
         background: var(--primary);
      }

      .delete-btn {
         background: #f8d7da;
         color: var(--danger);
      }

      .delete-btn:hover {
         background: #f1aeb5;
      }

      .empty {
         text-align: center;
         grid-column: 1/-1;
         padding: 3rem 0;
         color: #6c757d;
      }

      .loading {
         display: inline-block;
         width: 20px;
         height: 20px;
         border: 3px solid rgba(255,255,255,.3);
         border-radius: 50%;
         border-top-color: #fff;
         animation: spin 1s ease-in-out infinite;
      }

      @keyframes spin {
         to { transform: rotate(360deg); }
      }
   </style>
</head>
<body>

<?php
// Include and display the admin header
include '../components/admin_header.php';
$adminHeader = new AdminDashboardHeader($conn, $admin_id, $messages);
$adminHeader->display();
?>

<?php
// Display messages
if(!empty($messages)){
   foreach($messages as $msg){
      $msgClass = 'message ' . $msg['type'];
      
      echo '
      <div class="'.$msgClass.'">
         <span>'.$msg['text'].'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<section class="add-products">
   <h1 class="heading">Add Product</h1>

   <form action="" method="post" enctype="multipart/form-data" id="productForm" novalidate>
      <div class="flex">
         <div class="inputBox">
            <span>Product Name (required)</span>
            <input type="text" class="box <?php echo isset($formErrors['name']) ? 'error' : ''; ?>" required maxlength="100" placeholder="Enter product name" name="name" value="<?= htmlspecialchars($nameValue) ?>">
            <?php if (isset($formErrors['name'])): ?>
               <span class="error-message"><?= $formErrors['name'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Product Price (required)</span>
            <input type="number" min="1" step="0.01" class="box <?php echo isset($formErrors['price']) ? 'error' : ''; ?>" required placeholder="Enter product price" name="price" value="<?= htmlspecialchars($priceValue) ?>">
            <?php if (isset($formErrors['price'])): ?>
               <span class="error-message"><?= $formErrors['price'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Stock (required)</span>
            <input type="number" min="0" class="box <?php echo isset($formErrors['stock']) ? 'error' : ''; ?>" required placeholder="Enter product stock" name="stock" value="<?= htmlspecialchars($stockValue) ?>">
            <?php if (isset($formErrors['stock'])): ?>
               <span class="error-message"><?= $formErrors['stock'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Quantity (required)</span>
            <input type="number" min="0" class="box <?php echo isset($formErrors['quantity']) ? 'error' : ''; ?>" required placeholder="Enter product quantity" name="quantity" value="<?= htmlspecialchars($quantityValue) ?>">
            <?php if (isset($formErrors['quantity'])): ?>
               <span class="error-message"><?= $formErrors['quantity'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Image 01 (required)</span>
            <div class="file-input">
               <label class="file-input-label <?php echo isset($formErrors['image_01']) ? 'error' : ''; ?>">
                  <i class="fas fa-image"></i> Choose Image 01
                  <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
               </label>
            </div>
            <?php if (isset($formErrors['image_01'])): ?>
               <span class="error-message"><?= $formErrors['image_01'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Image 02 (required)</span>
            <div class="file-input">
               <label class="file-input-label <?php echo isset($formErrors['image_02']) ? 'error' : ''; ?>">
                  <i class="fas fa-image"></i> Choose Image 02
                  <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
               </label>
            </div>
            <?php if (isset($formErrors['image_02'])): ?>
               <span class="error-message"><?= $formErrors['image_02'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Image 03 (required)</span>
            <div class="file-input">
               <label class="file-input-label <?php echo isset($formErrors['image_03']) ? 'error' : ''; ?>">
                  <i class="fas fa-image"></i> Choose Image 03
                  <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
               </label>
            </div>
            <?php if (isset($formErrors['image_03'])): ?>
               <span class="error-message"><?= $formErrors['image_03'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Product Details (required)</span>
            <textarea name="details" placeholder="Enter product details" class="box <?php echo isset($formErrors['details']) ? 'error' : ''; ?>" required maxlength="500" cols="30" rows="10"><?= htmlspecialchars($detailsValue) ?></textarea>
            <?php if (isset($formErrors['details'])): ?>
               <span class="error-message"><?= $formErrors['details'] ?></span>
            <?php endif; ?>
            <div class="char-count" style="text-align: right; font-size: 0.8rem; color: #6c757d; margin-top: 5px;">
               <span id="details-char-count"><?= strlen($detailsValue) ?></span>/500 characters
            </div>
         </div>
      </div>
      
      <input type="submit" value="Add Product" class="btn" name="add_product" id="submitBtn">
   </form>
</section>

<section class="show-products">
   <h1 class="heading">Products Added</h1>

   <div class="box-container">
   <?php
      $products = $productManager->getAllProducts();
      
      if($products && $products->rowCount() > 0){
         while($fetch_products = $products->fetch(PDO::FETCH_ASSOC)){ 
   ?>
   <div class="box">
      <img src="../uploaded_img/<?= htmlspecialchars($fetch_products['image_01']); ?>" alt="<?= htmlspecialchars($fetch_products['name']); ?>">
      <div class="name"><?= htmlspecialchars($fetch_products['name']); ?></div>
      <div class="price">₹<span><?= number_format($fetch_products['price'], 2); ?></span>/-</div>
      <div class="details"><span><?= htmlspecialchars($fetch_products['details']); ?></span></div>
      <div class="flex-btn">
         <a href="update_product.php?update=<?= $fetch_products['id']; ?>" class="option-btn">Update</a>
         <a href="products.php?delete=<?= $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
      </div>
   </div>
   <?php
         }
      } else {
         echo '<p class="empty">No products added yet!</p>';
      }
   ?>
   </div>
</section>

<script>
   // Update character count for details textarea
   document.addEventListener('DOMContentLoaded', function() {
      const detailsTextarea = document.querySelector('textarea[name="details"]');
      const charCount = document.getElementById('details-char-count');
      
      if (detailsTextarea && charCount) {
         detailsTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
         });
      }

      // Auto-close messages after 5 seconds
      setTimeout(() => {
         document.querySelectorAll('.message').forEach(msg => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
         });
      }, 5000);
   });
</script>
   
</body>
</html>