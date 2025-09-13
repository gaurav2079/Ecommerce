<?php
include '../components/connect.php';

session_start();

class ProductUpdater {
    private $conn;
    private $admin_id;
    private $messages = [];
    private $product = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->checkAdminSession();
        $this->processRequest();
    }
    
    private function checkAdminSession() {
        $this->admin_id = $_SESSION['admin_id'] ?? null;
        if(!isset($this->admin_id)){
            header('location:admin_login.php');
            exit();
        }
    }
    
    private function processRequest() {
        if(isset($_POST['update'])){
            $this->updateProduct();
        }
        
        if(isset($_GET['update'])){
            $this->loadProduct();
        } else {
            $this->messages[] = 'No product ID specified!';
        }
    }
    
    private function updateProduct() {
        // Validate product ID
        $pid = filter_var($_POST['pid'], FILTER_SANITIZE_NUMBER_INT);
        
        // Sanitize inputs
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $details = filter_var($_POST['details'], FILTER_SANITIZE_STRING);

        // Update product details
        try {
            $update_product = $this->conn->prepare("UPDATE `products` SET name = ?, price = ?, details = ? WHERE id = ?");
            $update_product->execute([$name, $price, $details, $pid]);
            $this->messages[] = 'Product updated successfully!';
        } catch(PDOException $e) {
            $this->messages[] = 'Error updating product: ' . $e->getMessage();
        }

        // Handle image uploads
        $this->handleImageUploads($pid);
    }
    
    private function handleImageUploads($pid) {
        $image_fields = [
            'image_01' => 'old_image_01',
            'image_02' => 'old_image_02',
            'image_03' => 'old_image_03'
        ];

        foreach($image_fields as $file_field => $old_image_field){
            if(!empty($_FILES[$file_field]['name'])){
                $this->processImageUpload($file_field, $old_image_field, $pid);
            }
        }
    }
    
    private function processImageUpload($file_field, $old_image_field, $pid) {
        $image_name = $_FILES[$file_field]['name'];
        $image_size = $_FILES[$file_field]['size'];
        $image_tmp_name = $_FILES[$file_field]['tmp_name'];
        $old_image = $_POST[$old_image_field];
        $image_folder = '../uploaded_img/'.$image_name;

        // Validate image
        if($image_size > 2000000){
            $this->messages[] = ucfirst($file_field).' size is too large (max 2MB)!';
            return;
        }

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $file_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        
        if(!in_array($file_extension, $allowed_extensions)){
            $this->messages[] = 'Invalid file type for '.$file_field.'. Only JPG, JPEG, PNG, WEBP allowed.';
            return;
        }

        // Update image in database
        try {
            $update_image = $this->conn->prepare("UPDATE `products` SET $file_field = ? WHERE id = ?");
            $update_image->execute([$image_name, $pid]);
            
            // Move uploaded file
            if(move_uploaded_file($image_tmp_name, $image_folder)){
                // Delete old image if it exists
                if(!empty($old_image) && file_exists('../uploaded_img/'.$old_image)){
                    unlink('../uploaded_img/'.$old_image);
                }
                $this->messages[] = ucfirst($file_field).' updated successfully!';
            } else {
                $this->messages[] = 'Failed to upload '.$file_field;
            }
        } catch(PDOException $e) {
            $this->messages[] = 'Error updating '.$file_field.': ' . $e->getMessage();
        }
    }
    
    private function loadProduct() {
        $update_id = filter_var($_GET['update'], FILTER_SANITIZE_NUMBER_INT);
        $select_products = $this->conn->prepare("SELECT * FROM `products` WHERE id = ?");
        $select_products->execute([$update_id]);
        $this->product = $select_products->fetch(PDO::FETCH_ASSOC);
        
        if(!$this->product){
            $this->messages[] = 'No product found!';
        }
    }
    
    public function getMessages() {
        return $this->messages;
    }
    
    public function getProduct() {
        return $this->product;
    }
    
    public function render() {
        $messages = $this->messages;
        $product = $this->product;
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
           <meta charset="UTF-8">
           <meta http-equiv="X-UA-Compatible" content="IE=edge">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>Update Product</title>
           <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
           <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
           <style>
              :root {
                 --primary: #4361ee;
                 --secondary: #3f37c9;
                 --light: #f8f9fa;
                 --dark: #212529;
                 --success: #4bb543;
                 --danger: #ff3333;
                 --warning: #ffcc00;
                 --info: #17a2b8;
              }
              
              body {
                 font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                 background-color: #f5f7fa;
                 color: var(--dark);
                 line-height: 1.6;
                 padding: 0;
                 margin: 0;
              }
              
              .update-product {
                 max-width: 1200px;
                 margin: 2rem auto;
                 padding: 2rem;
                 background: white;
                 border-radius: 10px;
                 box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
              }
              
              .heading {
                 text-align: center;
                 font-size: 2.2rem;
                 color: var(--primary);
                 margin-bottom: 2rem;
                 padding-bottom: 1rem;
                 border-bottom: 2px solid rgba(67, 97, 238, 0.1);
              }
              
              .image-container {
                 display: flex;
                 flex-wrap: wrap;
                 gap: 2rem;
                 margin-bottom: 2rem;
              }
              
              .main-image {
                 flex: 1;
                 min-width: 300px;
                 height: 350px;
                 overflow: hidden;
                 border-radius: 8px;
                 box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
              }
              
              .main-image img {
                 width: 100%;
                 height: 100%;
                 object-fit: cover;
                 transition: transform 0.3s ease;
              }
              
              .main-image:hover img {
                 transform: scale(1.03);
              }
              
              .sub-image {
                 flex: 1;
                 display: grid;
                 grid-template-columns: repeat(3, 1fr);
                 gap: 1rem;
                 min-width: 300px;
              }
              
              .sub-image img {
                 width: 100%;
                 height: 110px;
                 object-fit: cover;
                 border-radius: 5px;
                 cursor: pointer;
                 transition: all 0.3s ease;
                 box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
              }
              
              .sub-image img:hover {
                 transform: translateY(-5px);
                 box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
              }
              
              form span {
                 display: block;
                 margin: 1.5rem 0 0.5rem;
                 font-weight: 600;
                 color: var(--primary);
              }
              
              .box {
                 width: 100%;
                 padding: 12px 15px;
                 margin-bottom: 1rem;
                 border: 1px solid #ddd;
                 border-radius: 5px;
                 font-size: 1rem;
                 transition: all 0.3s;
              }
              
              .box:focus {
                 border-color: var(--primary);
                 box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
                 outline: none;
              }
              
              textarea.box {
                 min-height: 150px;
                 resize: vertical;
              }
              
              .flex-btn {
                 display: flex;
                 gap: 1rem;
                 margin-top: 2rem;
              }
              
              .btn {
                 padding: 12px 25px;
                 background: var(--primary);
                 color: white;
                 border: none;
                 border-radius: 5px;
                 cursor: pointer;
                 font-size: 1rem;
                 font-weight: 600;
                 transition: all 0.3s;
                 text-align: center;
                 flex: 1;
              }
              
              .btn:hover {
                 background: var(--secondary);
                 transform: translateY(-2px);
                 box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
              }
              
              .option-btn {
                 padding: 12px 25px;
                 background: var(--light);
                 color: var(--dark);
                 border: 1px solid #ddd;
                 border-radius: 5px;
                 cursor: pointer;
                 font-size: 1rem;
                 font-weight: 600;
                 transition: all 0.3s;
                 text-align: center;
                 text-decoration: none;
                 flex: 1;
              }
              
              .option-btn:hover {
                 background: #e9ecef;
                 transform: translateY(-2px);
                 box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
              }
              
              .empty {
                 text-align: center;
                 padding: 2rem;
                 background: #f8f9fa;
                 border-radius: 5px;
                 color: #6c757d;
                 font-size: 1.2rem;
              }
              
              .message {
                 padding: 15px;
                 margin-bottom: 20px;
                 border-radius: 5px;
                 font-size: 1rem;
                 position: relative;
              }
              
              .message.success {
                 background: #e6ffed;
                 color: var(--success);
              }
              
              .message.error {
                 background: #ffebee;
                 color: var(--danger);
              }
              
              .message.warning {
                 background: #fff8e1;
                 color: var(--warning);
              }
              
              .message i {
                 position: absolute;
                 right: 15px;
                 top: 15px;
                 cursor: pointer;
              }
              
              /* Animation for form elements */
              form > * {
                 animation: fadeInUp 0.5s ease-out;
              }
              
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
              
              /* File input styling */
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
                 padding: 12px 15px;
                 background: #f8f9fa;
                 border: 1px dashed #ddd;
                 border-radius: 5px;
                 text-align: center;
                 cursor: pointer;
                 transition: all 0.3s;
              }
              
              .file-input-label:hover {
                 background: #e9ecef;
              }
              
              .file-input-label i {
                 margin-right: 8px;
              }
           </style>
        </head>
        <body>

        <?php include '../components/admin_header.php'; ?>

        <section class="update-product animate__animated animate__fadeIn">

           <h1 class="heading">Update Product</h1>

           <?php
           // Display messages
           if(!empty($messages)){
              foreach($messages as $msg){
                 $msgClass = 'message ';
                 if(stripos($msg, 'success') !== false){
                    $msgClass .= 'success';
                 } elseif(stripos($msg, 'error') !== false || stripos($msg, 'invalid') !== false){
                    $msgClass .= 'error';
                 } else {
                    $msgClass .= 'warning';
                 }
                 
                 echo '
                 <div class="'.$msgClass.'">
                    <span>'.$msg.'</span>
                    <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
                 </div>
                 ';
              }
           }
           ?>

           <?php if(!empty($product)): ?>
           <form action="" method="post" enctype="multipart/form-data">
              <input type="hidden" name="pid" value="<?= htmlspecialchars($product['id']); ?>">
              <input type="hidden" name="old_image_01" value="<?= htmlspecialchars($product['image_01']); ?>">
              <input type="hidden" name="old_image_02" value="<?= htmlspecialchars($product['image_02']); ?>">
              <input type="hidden" name="old_image_03" value="<?= htmlspecialchars($product['image_03']); ?>">
              
              <div class="image-container">
                 <div class="main-image">
                    <img src="../uploaded_img/<?= htmlspecialchars($product['image_01']); ?>" alt="Main Product Image" id="mainImage">
                 </div>
                 <div class="sub-image">
                    <img src="../uploaded_img/<?= htmlspecialchars($product['image_01']); ?>" alt="Product Image 1" onclick="setMainImage(this)">
                    <img src="../uploaded_img/<?= htmlspecialchars($product['image_02']); ?>" alt="Product Image 2" onclick="setMainImage(this)">
                    <img src="../uploaded_img/<?= htmlspecialchars($product['image_03']); ?>" alt="Product Image 3" onclick="setMainImage(this)">
                 </div>
              </div>
              
              <span>Update Name</span>
              <input type="text" name="name" required class="box" maxlength="100" placeholder="Enter product name" value="<?= htmlspecialchars($product['name']); ?>">
              
              <span>Update Price</span>
              <input type="number" name="price" required class="box" min="0" max="9999999999" step="0.01" placeholder="Enter product price" value="<?= htmlspecialchars($product['price']); ?>">
              
              <span>Update Details</span>
              <textarea name="details" class="box" required cols="30" rows="10" placeholder="Enter product details"><?= htmlspecialchars($product['details']); ?></textarea>
              
              <span>Update Image 01</span>
              <div class="file-input">
                 <label class="file-input-label">
                    <i class="fas fa-image"></i> Choose Image 01
                    <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" class="box">
                 </label>
              </div>
              
              <span>Update Image 02</span>
              <div class="file-input">
                 <label class="file-input-label">
                    <i class="fas fa-image"></i> Choose Image 02
                    <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" class="box">
                 </label>
              </div>
              
              <span>Update Image 03</span>
              <div class="file-input">
                 <label class="file-input-label">
                    <i class="fas fa-image"></i> Choose Image 03
                    <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" class="box">
                 </label>
              </div>
              
              <div class="flex-btn">
                 <input type="submit" name="update" class="btn" value="Update Product">
                 <a href="products.php" class="option-btn">Go Back</a>
              </div>
           </form>
           <?php else: ?>
              <p class="empty">No product found!</p>
           <?php endif; ?>

        </section>

        <script>
           // Set clicked image as main image
           function setMainImage(imgElement) {
              document.getElementById('mainImage').src = imgElement.src;
           }
           
           // Add animation when scrolling to form elements
           document.addEventListener('DOMContentLoaded', function() {
              const observer = new IntersectionObserver((entries) => {
                 entries.forEach(entry => {
                    if (entry.isIntersecting) {
                       entry.target.style.animation = 'fadeInUp 0.5s ease-out forwards';
                       observer.unobserve(entry.target);
                    }
                 });
              }, { threshold: 0.1 });
              
              document.querySelectorAll('form > *').forEach(el => {
                 observer.observe(el);
              });
           });
           
           // Auto-close messages after 5 seconds
           setTimeout(() => {
              document.querySelectorAll('.message').forEach(msg => {
                 msg.style.transition = 'opacity 0.5s ease';
                 msg.style.opacity = '0';
                 setTimeout(() => msg.remove(), 500);
              });
           }, 5000);
        </script>

        <script src="../js/admin_script.js"></script>
           
        </body>
        </html>
        <?php
    }
}

// Initialize and render the product updater
$productUpdater = new ProductUpdater($conn);
$productUpdater->render();
?>