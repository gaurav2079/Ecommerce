<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
};

if(isset($_POST['add_product'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $price = $_POST['price'];
   $price = filter_var($price, FILTER_SANITIZE_STRING);
   $details = $_POST['details'];
   $details = filter_var($details, FILTER_SANITIZE_STRING);

   $image_01 = $_FILES['image_01']['name'];
   $image_01 = filter_var($image_01, FILTER_SANITIZE_STRING);
   $image_size_01 = $_FILES['image_01']['size'];
   $image_tmp_name_01 = $_FILES['image_01']['tmp_name'];
   $image_folder_01 = '../uploaded_img/'.$image_01;

   $image_02 = $_FILES['image_02']['name'];
   $image_02 = filter_var($image_02, FILTER_SANITIZE_STRING);
   $image_size_02 = $_FILES['image_02']['size'];
   $image_tmp_name_02 = $_FILES['image_02']['tmp_name'];
   $image_folder_02 = '../uploaded_img/'.$image_02;

   $image_03 = $_FILES['image_03']['name'];
   $image_03 = filter_var($image_03, FILTER_SANITIZE_STRING);
   $image_size_03 = $_FILES['image_03']['size'];
   $image_tmp_name_03 = $_FILES['image_03']['tmp_name'];
   $image_folder_03 = '../uploaded_img/'.$image_03;

   $select_products = $conn->prepare("SELECT * FROM `products` WHERE name = ?");
   $select_products->execute([$name]);

   if($select_products->rowCount() > 0){
      $message[] = 'product name already exist!';
   }else{

      $insert_products = $conn->prepare("INSERT INTO `products`(name, details, price, image_01, image_02, image_03) VALUES(?,?,?,?,?,?)");
      $insert_products->execute([$name, $details, $price, $image_01, $image_02, $image_03]);

      if($insert_products){
         if($image_size_01 > 2000000 OR $image_size_02 > 2000000 OR $image_size_03 > 2000000){
            $message[] = 'image size is too large!';
         }else{
            move_uploaded_file($image_tmp_name_01, $image_folder_01);
            move_uploaded_file($image_tmp_name_02, $image_folder_02);
            move_uploaded_file($image_tmp_name_03, $image_folder_03);
            $message[] = 'new product added!';
         }

      }

   }  

};

if(isset($_GET['delete'])){

   $delete_id = $_GET['delete'];
   $delete_product_image = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
   $delete_product_image->execute([$delete_id]);
   $fetch_delete_image = $delete_product_image->fetch(PDO::FETCH_ASSOC);
   unlink('../uploaded_img/'.$fetch_delete_image['image_01']);
   unlink('../uploaded_img/'.$fetch_delete_image['image_02']);
   unlink('../uploaded_img/'.$fetch_delete_image['image_03']);
   $delete_product = $conn->prepare("DELETE FROM `products` WHERE id = ?");
   $delete_product->execute([$delete_id]);
   $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE pid = ?");
   $delete_cart->execute([$delete_id]);
   $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ?");
   $delete_wishlist->execute([$delete_id]);
   header('location:products.php');
}

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
         top: 20px;
         right: 20px;
         background-color: var(--primary);
         color: white;
         padding: 15px 20px;
         border-radius: var(--border-radius);
         display: flex;
         align-items: center;
         gap: 10px;
         box-shadow: var(--shadow);
         z-index: 1000;
         animation: slideIn 0.3s ease-out;
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

      .inputBox textarea {
         resize: vertical;
         min-height: 150px;
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

      @media (max-width: 768px) {
         .box-container {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
         }
         
         .add-products, .show-products {
            padding: 1.5rem;
         }
      }

      @media (max-width: 480px) {
         .flex-btn {
            flex-direction: column;
         }
         
         .heading {
            font-size: 1.5rem;
         }
      }
   </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<section class="add-products">
   <h1 class="heading">Add Product</h1>

   <form action="" method="post" enctype="multipart/form-data">
      <div class="flex">
         <div class="inputBox">
            <span>Product Name (required)</span>
            <input type="text" class="box" required maxlength="100" placeholder="Enter product name" name="name">
         </div>
         <div class="inputBox">
            <span>Product Price (required)</span>
            <input type="number" min="0" class="box" required max="9999999999" placeholder="Enter product price" onkeypress="if(this.value.length == 10) return false;" name="price">
         </div>
        <div class="inputBox">
            <span>Image 01 (required)</span>
            <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
        </div>
        <div class="inputBox">
            <span>Image 02 (required)</span>
            <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
        </div>
        <div class="inputBox">
            <span>Image 03 (required)</span>
            <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
        </div>
         <div class="inputBox">
            <span>Product Details (required)</span>
            <textarea name="details" placeholder="Enter product details" class="box" required maxlength="500" cols="30" rows="10"></textarea>
         </div>
      </div>
      
      <input type="submit" value="Add Product" class="btn" name="add_product">
   </form>
</section>

<section class="show-products">
   <h1 class="heading">Products Added</h1>

   <div class="box-container">
   <?php
      $select_products = $conn->prepare("SELECT * FROM `products`");
      $select_products->execute();
      if($select_products->rowCount() > 0){
         while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){ 
   ?>
   <div class="box">
      <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" alt="">
      <div class="name"><?= $fetch_products['name']; ?></div>
      <div class="price">₹<span><?= $fetch_products['price']; ?></span>/-</div>
      <div class="details"><span><?= $fetch_products['details']; ?></span></div>
      <div class="flex-btn">
         <a href="update_product.php?update=<?= $fetch_products['id']; ?>" class="option-btn">Update</a>
         <a href="products.php?delete=<?= $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?');">Delete</a>
      </div>
   </div>
   <?php
         }
      }else{
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
   });
</script>

<script src="../js/admin_script.js"></script>
   
</body>
</html>