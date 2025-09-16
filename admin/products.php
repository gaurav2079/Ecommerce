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
        
        // Check if contains only letters, spaces, and basic punctuation
        if (!preg_match('/^[a-zA-Z\s\-\'\.\,]+$/', $name)) {
            $this->addFormError('name', 'Product name can only contain letters, spaces, hyphens, apostrophes, commas, and periods');
            return false;
        }
        
        // Check if product already exists
        $select_products = $this->conn->prepare("SELECT * FROM `products` WHERE name = ?");
        $select_products->execute([$name]);
        
        if($select_products->rowCount() > 0){
            $this->addFormError('name', 'Product name already exists!');
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
    
    // Decode URL encoding to catch + symbols that were encoded as spaces
    $decoded_price = urldecode($price);
    
    // Check if value contains + or - symbols
    if (strpos($decoded_price, '-') !== false || strpos($decoded_price, '+') !== false) {
        $this->addFormError('price', 'Product price cannot contain + or - symbols');
        return false;
    }
    
    // Check if it's a valid number (allowing decimal points)
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $decoded_price)) {
        $this->addFormError('price', 'Product price must be a valid number');
        return false;
    }
    
    // Convert to float for further validation
    $price_float = (float)$decoded_price;
    
    // Check if price is at least 1
    if ($price_float < 1) {
        $this->addFormError('price', 'Product price must be at least ₹1');
        return false;
    }
    
    // Check if price is reasonable (less than 10 million)
    if ($price_float > 10000000) {
        $this->addFormError('price', 'Product price must be less than ₹10,000,000');
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
        
        // Check maximum length
        if (strlen($details) > 500) {
            $this->addFormError('details', 'Product details must be less than 500 characters');
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
                    switch ($image_error) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $upload_errors[$field] = 'File size is too large';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $upload_errors[$field] = 'File was only partially uploaded';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $upload_errors[$field] = 'No file was uploaded';
                            break;
                        default:
                            $upload_errors[$field] = 'Unknown upload error';
                    }
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
                
                // Validate MIME type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $image_tmp_name);
                finfo_close($finfo);
                
                $allowed_mime_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!in_array($mime_type, $allowed_mime_types)) {
                    $upload_errors[$field] = 'Invalid file type. Only images are allowed';
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
    
    public function addProduct($name, $price, $details, $files) {
        // Reset form errors
        $this->formErrors = [];
        
        // Validate inputs
        $isNameValid = $this->validateProductName($name);
        $isPriceValid = $this->validateProductPrice($price);
        $isDetailsValid = $this->validateProductDetails($details);
        $image_names = $this->validateProductImages($files);
        
        // If any validation failed, return false
        if (!$isNameValid || !$isPriceValid || !$isDetailsValid || $image_names === false) {
            return false;
        }
        
        // If all validations passed, proceed with database insertion
        try {
            $insert_products = $this->conn->prepare("INSERT INTO `products`(name, details, price, image_01, image_02, image_03) VALUES(?,?,?,?,?,?)");
            $insert_products->execute([
                $name, 
                $details, 
                $price, 
                $image_names['image_01'], 
                $image_names['image_02'], 
                $image_names['image_03']
            ]);
            
            // Move uploaded files
            $image_fields = ['image_01', 'image_02', 'image_03'];
            foreach($image_fields as $field){
                move_uploaded_file($files[$field]['tmp_name'], '../uploaded_img/'.$image_names[$field]);
            }
            
            $this->addMessage('New product added successfully!', 'success');
            return true;
        } catch(PDOException $e) {
            $this->addMessage('Error adding product: ' . $e->getMessage(), 'error');
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
            
            // Delete from related tables
            $delete_cart = $this->conn->prepare("DELETE FROM `cart` WHERE pid = ?");
            $delete_cart->execute([$product_id]);
            
            $delete_wishlist = $this->conn->prepare("DELETE FROM `wishlist` WHERE pid = ?");
            $delete_wishlist->execute([$product_id]);
            
            $this->addMessage('Product deleted successfully!', 'success');
            return true;
        } catch(PDOException $e) {
            $this->addMessage('Error deleting product: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    public function getAllProducts() {
        $select_products = $this->conn->prepare("SELECT * FROM `products` ORDER BY id DESC");
        $select_products->execute();
        return $select_products;
    }
}

// Initialize ProductManager
$productManager = new ProductManager($conn);

// Handle form submissions
if(isset($_POST['add_product'])){
    $success = $productManager->addProduct($_POST['name'], $_POST['price'], $_POST['details'], $_FILES);
    
    // If product was added successfully, clear form values
    if ($success) {
        $_POST = array(); // Clear form data
    }
}

if(isset($_GET['delete'])){
    $productManager->deleteProduct($_GET['delete']);
    header('location:products.php');
    exit();
}

// Get messages and form errors for display
$messages = $productManager->getMessages();
$formErrors = $productManager->getFormErrors();

// Get previous form values if validation failed
$nameValue = $_POST['name'] ?? '';
$priceValue = $_POST['price'] ?? '';
$detailsValue = $_POST['details'] ?? '';
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

      @media (max-width: 768px) {
         .box-container {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
         }
         
         .add-products, .show-products {
            padding: 1.5rem;
         }
         
         body {
            padding-top: 60px;
         }
      }

      @media (max-width: 480px) {
         .flex-btn {
            flex-direction: column;
         }
         
         .heading {
            font-size: 1.5rem;
         }
         
         .add-products form {
            padding: 1.5rem;
         }
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
            <input type="text" class="box <?php echo isset($formErrors['name']) ? 'error' : ''; ?>" required maxlength="100" placeholder="Enter product name" name="name" value="<?= htmlspecialchars($nameValue) ?>" oninput="validateProductName(this)">
            <?php if (isset($formErrors['name'])): ?>
               <span class="error-message"><?= $formErrors['name'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Product Price (required)</span>
            <input type="number" min="1" step="0.01" class="box <?php echo isset($formErrors['price']) ? 'error' : ''; ?>" required max="9999999999" placeholder="Enter product price" name="price" value="<?= htmlspecialchars($priceValue) ?>" oninput="validateProductPrice(this)">
            <?php if (isset($formErrors['price'])): ?>
               <span class="error-message"><?= $formErrors['price'] ?></span>
            <?php endif; ?>
         </div>
         <div class="inputBox">
            <span>Image 01 (required)</span>
            <div class="file-input">
               <label class="file-input-label <?php echo isset($formErrors['image_01']) ? 'error' : ''; ?>">
                  <i class="fas fa-image"></i> Choose Image 01
                  <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required onchange="previewImage(this, 'preview-01'); validateImage(this, 'image_01')">
               </label>
            </div>
            <?php if (isset($formErrors['image_01'])): ?>
               <span class="error-message"><?= $formErrors['image_01'] ?></span>
            <?php endif; ?>
            <div class="file-preview" id="preview-01"></div>
         </div>
         <div class="inputBox">
            <span>Image 02 (required)</span>
            <div class="file-input">
               <label class="file-input-label <?php echo isset($formErrors['image_02']) ? 'error' : ''; ?>">
                  <i class="fas fa-image"></i> Choose Image 02
                  <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required onchange="previewImage(this, 'preview-02'); validateImage(this, 'image_02')">
               </label>
            </div>
            <?php if (isset($formErrors['image_02'])): ?>
               <span class="error-message"><?= $formErrors['image_02'] ?></span>
            <?php endif; ?>
            <div class="file-preview" id="preview-02"></div>
         </div>
         <div class="inputBox">
            <span>Image 03 (required)</span>
            <div class="file-input">
               <label class="file-input-label <?php echo isset($formErrors['image_03']) ? 'error' : ''; ?>">
                  <i class="fas fa-image"></i> Choose Image 03
                  <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required onchange="previewImage(this, 'preview-03'); validateImage(this, 'image_03')">
               </label>
            </div>
            <?php if (isset($formErrors['image_03'])): ?>
               <span class="error-message"><?= $formErrors['image_03'] ?></span>
            <?php endif; ?>
            <div class="file-preview" id="preview-03"></div>
         </div>
         <div class="inputBox">
            <span>Product Details (required)</span>
            <textarea name="details" placeholder="Enter product details" class="box <?php echo isset($formErrors['details']) ? 'error' : ''; ?>" required maxlength="500" cols="30" rows="10" oninput="validateProductDetails(this)"><?= htmlspecialchars($detailsValue) ?></textarea>
            <?php if (isset($formErrors['details'])): ?>
               <span class="error-message"><?= $formErrors['details'] ?></span>
            <?php endif; ?>
            <div class="char-count" style="text-align: right; font-size: 0.8rem; color: #6c757d; margin-top: 5px;">
               <span id="details-char-count">0</span>/500 characters
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
      
      if($products->rowCount() > 0){
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
   // Simple animation for the product boxes
   document.addEventListener('DOMContentLoaded', function() {
      const boxes = document.querySelectorAll('.box-container .box');
      boxes.forEach((box, index) => {
         box.style.opacity = '0';
         box.style.transform = 'translateY(20px)';
         box.style.transition = 'all 0.4s ease-out';
         
         setTimeout(() => {
            box.style.opacity = '1';
            box.style.transform = 'translateY(0)';
         }, 100 * index);
      });
      
      // Auto-close messages after 5 seconds
      setTimeout(() => {
         document.querySelectorAll('.message').forEach(msg => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
         });
      }, 5000);
      
      // Form submission handling
      const form = document.getElementById('productForm');
      const submitBtn = document.getElementById('submitBtn');
      
      if(form) {
         form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Adding Product <span class="loading"></span>';
         });
      }
      
      // Update character count for details textarea
      const detailsTextarea = document.querySelector('textarea[name="details"]');
      const charCount = document.getElementById('details-char-count');
      
      if (detailsTextarea && charCount) {
         charCount.textContent = detailsTextarea.value.length;
         
         detailsTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
         });
      }
   });
   
   // Image preview function
   function previewImage(input, previewId) {
      const preview = document.getElementById(previewId);
      preview.innerHTML = '';
      
      if (input.files && input.files[0]) {
         const reader = new FileReader();
         
         reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Preview';
            preview.appendChild(img);
         }
         
         reader.readAsDataURL(input.files[0]);
      }
   }
   
   // Validation functions
   function validateProductName(input) {
      const value = input.value.trim();
      const errorElement = input.nextElementSibling;
      const nameRegex = /^[a-zA-Z\s\-\'\.\,]+$/;
      
      if (value === '') {
         showError(input, 'Product name is required');
         return false;
      }
      
      if (value.length < 3) {
         showError(input, 'Product name must be at least 3 characters long');
         return false;
      }
      
      if (value.length > 100) {
         showError(input, 'Product name must be less than 100 characters');
         return false;
      }
      
      if (!nameRegex.test(value)) {
         showError(input, 'Product name can only contain letters, spaces, hyphens, apostrophes, commas, and periods');
         return false;
      }
      
      clearError(input);
      return true;
   }
   
   function validateProductPrice(input) {
      const value = input.value.trim();
      const errorElement = input.nextElementSibling;
      
      if (value === '') {
         showError(input, 'Product price is required');
         return false;
      }
      
      // Check if value contains + or - symbols
      if (value.includes('+') || value.includes('-')) {
         showError(input, 'Product price cannot contain + or - symbols');
         return false;
      }
      
      // Check if it's a valid number (allowing decimal points)
      if (!/^\d*\.?\d*$/.test(value)) {
         showError(input, 'Product price must be a valid number');
         return false;
      }
      
      // Check if it's a finite number
      if (isNaN(value) || !isFinite(value)) {
         showError(input, 'Product price must be a valid number');
         return false;
      }
      
      const price = parseFloat(value);
      
      if (price < 1) {
         showError(input, 'Product price must be at least ₹1');
         return false;
      }
      
      if (price > 10000000) {
         showError(input, 'Product price must be less than ₹10,000,000');
         return false;
      }
      
      // Check decimal places
      if (value.includes('.')) {
         const decimalPart = value.split('.')[1];
         if (decimalPart.length > 2) {
            showError(input, 'Product price can have at most 2 decimal places');
            return false;
         }
      }
      
      clearError(input);
      return true;
   }
   
   function validateProductDetails(textarea) {
      const value = textarea.value.trim();
      const errorElement = textarea.nextElementSibling;
      
      if (value === '') {
         showError(textarea, 'Product details are required');
         return false;
      }
      
      if (value.length < 10) {
         showError(textarea, 'Product details must be at least 10 characters long');
         return false;
      }
      
      if (value.length > 500) {
         showError(textarea, 'Product details must be less than 500 characters');
         return false;
      }
      
      clearError(textarea);
      return true;
   }
   
   function validateImage(input, fieldName) {
      const file = input.files[0];
      const label = input.parentElement;
      const errorElement = label.nextElementSibling;
      
      if (!file) {
         showError(input, 'Image is required', fieldName);
         return false;
      }
      
      // Check file size (2MB max)
      if (file.size > 2000000) {
         showError(input, 'File size is too large (max 2MB)', fieldName);
         return false;
      }
      
      // Check file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
      if (!allowedTypes.includes(file.type)) {
         showError(input, 'Invalid file type. Only JPG, JPEG, PNG, WEBP allowed', fieldName);
         return false;
      }
      
      clearError(input, fieldName);
      return true;
   }
   
   function showError(input, message, fieldName = null) {
      input.classList.add('error');
      
      if (fieldName) {
         // For file inputs, we need to find the error element differently
         const label = input.parentElement;
         let errorElement = label.nextElementSibling;
         
         if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.textContent = message;
         } else {
            errorElement = document.createElement('span');
            errorElement.className = 'error-message';
            errorElement.textContent = message;
            label.parentNode.insertBefore(errorElement, label.nextSibling);
         }
         
         label.classList.add('error');
      } else {
         let errorElement = input.nextElementSibling;
         
         if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.textContent = message;
         } else {
            errorElement = document.createElement('span');
            errorElement.className = 'error-message';
            errorElement.textContent = message;
            input.parentNode.insertBefore(errorElement, input.nextSibling);
         }
      }
   }
   
   function clearError(input, fieldName = null) {
      input.classList.remove('error');
      
      if (fieldName) {
         // For file inputs
         const label = input.parentElement;
         let errorElement = label.nextElementSibling;
         
         if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.remove();
         }
         
         label.classList.remove('error');
      } else {
         let errorElement = input.nextElementSibling;
         
         if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.remove();
         }
      }
   }
   
   // Form validation before submit
   document.getElementById('productForm').addEventListener('submit', function(e) {
      let isValid = true;
      
      // Validate product name
      const nameInput = document.querySelector('input[name="name"]');
      if (!validateProductName(nameInput)) {
         isValid = false;
      }
      
      // Validate product price
      const priceInput = document.querySelector('input[name="price"]');
      if (!validateProductPrice(priceInput)) {
         isValid = false;
      }
      
      // Validate product details
      const detailsTextarea = document.querySelector('textarea[name="details"]');
      if (!validateProductDetails(detailsTextarea)) {
         isValid = false;
      }
      
      // Validate images
      const imageInputs = [
         document.querySelector('input[name="image_01"]'),
         document.querySelector('input[name="image_02"]'),
         document.querySelector('input[name="image_03"]')
      ];
      
      imageInputs.forEach((input, index) => {
         if (!validateImage(input, `image_0${index + 1}`)) {
            isValid = false;
         }
      });
      
      if (!isValid) {
         e.preventDefault();
         // Scroll to the first error
         const firstError = document.querySelector('.error');
         if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
         }
      }
   });
</script>
<script src="../js/admin_script.js"></script>
   
</body>
</html>