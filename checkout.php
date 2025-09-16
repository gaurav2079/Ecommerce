<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
};

// Function to generate and store OTP
function generateOTP($conn, $user_id) {
    $otp = rand(100000, 999999); // 6-digit OTP
    $expiry_time = date('Y-m-d H:i:s', strtotime('+5 minutes')); // OTP valid for 5 minutes
    
    // Store OTP in database
    $insert_otp = $conn->prepare("INSERT INTO `otp_verification` (user_id, otp_code, expiry_time) VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE otp_code = ?, expiry_time = ?");
    $insert_otp->execute([$user_id, $otp, $expiry_time, $otp, $expiry_time]);
    
    return $otp;
}

// Function to verify OTP
function verifyOTP($conn, $user_id, $entered_otp) {
    $current_time = date('Y-m-d H:i:s');
    
    $check_otp = $conn->prepare("SELECT * FROM `otp_verification` WHERE user_id = ? AND otp_code = ? AND expiry_time > ?");
    $check_otp->execute([$user_id, $entered_otp, $current_time]);
    
    if($check_otp->rowCount() > 0) {
        // Delete OTP after successful verification
        $delete_otp = $conn->prepare("DELETE FROM `otp_verification` WHERE user_id = ?");
        $delete_otp->execute([$user_id]);
        return true;
    }
    return false;
}

// Function to place an order
function placeOrder($conn, $user_id, $name, $number, $email, $method, $address, $total_products, $total_price) {
    $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
    $check_cart->execute([$user_id]);

    if($check_cart->rowCount() > 0){
        $product_ids = [];
        $cart_items = [];
        
        while($fetch_cart = $check_cart->fetch(PDO::FETCH_ASSOC)){
            $cart_items[] = $fetch_cart['name'].' ('.$fetch_cart['price'].' x '. $fetch_cart['quantity'].') - ';
            $product_ids[] = $fetch_cart['product_id'];
        }
        
        $product_ids_str = implode(',', $product_ids);

        $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, product_ids) VALUES(?,?,?,?,?,?,?,?,?)");
        $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_products, $total_price, $product_ids_str]);

        $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
        $delete_cart->execute([$user_id]);

        return true;
    }
    return false;
}

// Handle regular order submission
if(isset($_POST['order'])){
    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    $number = $_POST['number'];
    $number = filter_var($number, FILTER_SANITIZE_STRING);
    $email = $_POST['email'];
    $email = filter_var($email, FILTER_SANITIZE_STRING);
    $method = $_POST['method'];
    $method = filter_var($method, FILTER_SANITIZE_STRING);
    $address = $_POST['state'] . ' - '. $_POST['pin_code'];
    $address = filter_var($address, FILTER_SANITIZE_STRING);
    $total_products = $_POST['total_products'];
    $total_price = $_POST['total_price'];

    if(placeOrder($conn, $user_id, $name, $number, $email, $method, $address, $total_products, $total_price)){
        $message[] = 'Order placed successfully!';
        header('location:orders.php');
        exit();
    }else{
        $message[] = 'Your cart is empty';
    }
}

// Handle Esewa payment
if(isset($_POST['esewa_payment'])){
    $esewa_username = $_POST['esewa_username'];
    $esewa_password = $_POST['esewa_password'];
    $otp_code = $_POST['otp_code'];
    
    $valid_username = 'esewa_user';
    $valid_password = 'esewa123';
    
    if($esewa_username === $valid_username && $esewa_password === $valid_password){
        // Verify OTP
        if(verifyOTP($conn, $user_id, $otp_code)) {
            // Get all form data from hidden inputs
            $name = $_POST['name'];
            $name = filter_var($name, FILTER_SANITIZE_STRING);
            $number = $_POST['number'];
            $number = filter_var($number, FILTER_SANITIZE_STRING);
            $email = $_POST['email'];
            $email = filter_var($email, FILTER_SANITIZE_STRING);
            $address = $_POST['state'] . ' - '. $_POST['pin_code'];
            $address = filter_var($address, FILTER_SANITIZE_STRING);
            $total_products = $_POST['total_products'];
            $total_price = $_POST['total_price'];
            $method = 'esewa';
            
            if(placeOrder($conn, $user_id, $name, $number, $email, $method, $address, $total_products, $total_price)){
                $message[] = 'Order placed successfully with Esewa!';
                header('location:orders.php');
                exit();
            }else{
                $message[] = 'Your cart is empty';
            }
        } else {
            $message[] = 'Invalid OTP. Please try again.';
        }
    } else {
        $message[] = 'Invalid Esewa credentials. Please try again.';
    }
}

// Handle OTP request
if(isset($_POST['request_otp'])){
    $esewa_username = $_POST['esewa_username'];
    $esewa_password = $_POST['esewa_password'];
    
    $valid_username = 'esewa_user';
    $valid_password = 'esewa123';
    
    if($esewa_username === $valid_username && $esewa_password === $valid_password){
        // Generate and send OTP
        $otp = generateOTP($conn, $user_id);
        
        // In a real application, you would send this OTP via  email
        // For demo purposes, we'll just return it in the response
        echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully', 'demo_otp' => $otp]);
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Esewa credentials']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Checkout - NS</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   
   <style>
      :root {
         --daraz-orange: #f57224;
         --daraz-dark: #212121;
         --daraz-gray: #f2f2f2;
         --daraz-light: #ffffff;
         --daraz-border: #e0e0e0;
         --daraz-text: #555555;
         --daraz-success: #4CAF50;
         --daraz-error: #f44336;
      }
      
      body {
         background-color: #f5f5f5;
         font-family: 'Roboto', Arial, sans-serif;
         color: var(--daraz-text);
      }
      
      .checkout-container {
         max-width: 1200px;
         margin: 20px auto;
         display: grid;
         grid-template-columns: 7fr 3fr;
         gap: 20px;
      }
      
      .checkout-card {
         background: var(--daraz-light);
         border-radius: 4px;
         box-shadow: 0 1px 3px rgba(0,0,0,0.1);
         padding: 20px;
         margin-bottom: 20px;
      }
      
      .checkout-header {
         font-size: 18px;
         font-weight: 500;
         color: var(--daraz-dark);
         padding-bottom: 15px;
         border-bottom: 1px solid var(--daraz-border);
         margin-bottom: 20px;
         display: flex;
         align-items: center;
      }
      
      .checkout-header i {
         margin-right: 10px;
         color: var(--daraz-orange);
      }
      
      .delivery-address, .payment-method {
         margin-bottom: 30px;
      }
      
      .form-group {
         margin-bottom: 15px;
         position: relative;
      }
      
      .form-label {
         display: block;
         margin-bottom: 8px;
         font-size: 14px;
         color: var(--daraz-dark);
         font-weight: 500;
      }
      
      .form-control {
         width: 100%;
         padding: 10px 12px;
         border: 1px solid var(--daraz-border);
         border-radius: 2px;
         font-size: 14px;
         transition: all 0.3s;
      }
      
      .form-control:focus {
         border-color: var(--daraz-orange);
         outline: none;
         box-shadow: 0 0 0 1px var(--daraz-orange);
      }
      
      .form-control.error {
         border-color: var(--daraz-error);
      }
      
      .error-message {
         color: var(--daraz-error);
         font-size: 12px;
         margin-top: 5px;
         display: none;
      }
      
      .address-fields {
         display: grid;
         grid-template-columns: repeat(2, 1fr);
         gap: 15px;
      }
      
      .payment-options {
         display: flex;
         flex-direction: column;
         gap: 10px;
      }
      
      .payment-option {
         border: 1px solid var(--daraz-border);
         border-radius: 4px;
         padding: 15px;
         cursor: pointer;
         transition: all 0.3s;
      }
      
      .payment-option:hover {
         border-color: var(--daraz-orange);
      }
      
      .payment-option.active {
         border-color: var(--daraz-orange);
         background-color: rgba(245, 114, 36, 0.05);
      }
      
      .payment-option input {
         margin-right: 10px;
      }
      
      .order-summary {
         position: sticky;
         top: 20px;
      }
      
      .order-items {
         max-height: 200px;
         overflow-y: auto;
         margin-bottom: 15px;
         border-bottom: 1px solid var(--daraz-border);
      }
      
      .order-item {
         display: flex;
         justify-content: space-between;
         padding: 10px 0;
         border-bottom: 1px dashed var(--daraz-border);
      }
      
      .order-item:last-child {
         border-bottom: none;
      }
      
      .price-summary {
         margin-top: 15px;
      }
      
      .price-row {
         display: flex;
         justify-content: space-between;
         margin-bottom: 10px;
      }
      
      .total-price {
         font-size: 18px;
         font-weight: 500;
         color: var(--daraz-dark);
         padding-top: 15px;
         border-top: 1px solid var(--daraz-border);
      }
      
      .btn-checkout {
         width: 100%;
         background-color: var(--daraz-orange);
         color: white;
         border: none;
         border-radius: 2px;
         padding: 12px;
         font-size: 16px;
         font-weight: 500;
         cursor: pointer;
         margin-top: 20px;
         transition: all 0.3s;
      }
      
      .btn-checkout:hover {
         background-color: #e0611a;
      }
      
      .btn-checkout.disabled {
         background-color: #cccccc;
         cursor: not-allowed;
      }
      
      /* Esewa Modal Styles */
      .modal {
         display: none;
         position: fixed;
         z-index: 1000;
         left: 0;
         top: 0;
         width: 100%;
         height: 100%;
         overflow: auto;
         background-color: rgba(0,0,0,0.4);
      }
      
      .modal-content {
         background-color: #fefefe;
         margin: 10% auto;
         padding: 25px;
         border: 1px solid #888;
         width: 400px;
         max-width: 90%;
         border-radius: 5px;
         box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      }
      
      .modal-header {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-bottom: 20px;
         padding-bottom: 10px;
         border-bottom: 1px solid #e0e0e0;
      }
      
      .modal-header h3 {
         margin: 0;
         color: var(--daraz-orange);
      }
      
      .close {
         color: #aaa;
         font-size: 28px;
         font-weight: bold;
         cursor: pointer;
      }
      
      .close:hover {
         color: black;
      }
      
      .esewa-form .form-group {
         margin-bottom: 15px;
      }
      
      .esewa-form .form-label {
         display: block;
         margin-bottom: 5px;
         font-weight: 500;
      }
      
      .esewa-form .form-control {
         width: 100%;
         padding: 10px;
         border: 1px solid #ddd;
         border-radius: 3px;
      }
      
      .btn-esewa {
         background-color: #5cb85c;
         color: white;
         border: none;
         padding: 12px;
         width: 100%;
         border-radius: 3px;
         cursor: pointer;
         font-weight: 500;
         font-size: 16px;
         margin-top: 10px;
      }
      
      .btn-esewa:hover {
         background-color: #4cae4c;
      }
      
      .esewa-logo {
         text-align: center;
         margin-bottom: 20px;
      }
      
      .esewa-logo img {
         height: 40px;
      }
      
      .demo-credentials {
         margin-top: 15px;
         padding: 10px;
         background-color: #f8f9fa;
         border-radius: 4px;
         font-size: 13px;
         text-align: center;
      }
      
      /* Additional styles for OTP section */
      .otp-section {
         display: none;
         margin-top: 20px;
         padding-top: 20px;
         border-top: 1px solid var(--daraz-border);
      }
      
      .otp-timer {
         text-align: center;
         font-size: 14px;
         margin-top: 10px;
         color: var(--daraz-orange);
      }
      
      .resend-otp {
         color: var(--daraz-orange);
         cursor: pointer;
         text-align: center;
         margin-top: 10px;
         font-size: 14px;
      }
      
      .resend-otp.disabled {
         color: var(--daraz-text);
         cursor: not-allowed;
      }
      
      .btn-request-otp {
         background-color: #f0ad4e;
         color: white;
         border: none;
         padding: 12px;
         width: 100%;
         border-radius: 3px;
         cursor: pointer;
         font-weight: 500;
         font-size: 16px;
         margin-top: 10px;
      }
      
      .btn-request-otp:hover {
         background-color: #ec971f;
      }
      
      @media (max-width: 768px) {
         .checkout-container {
            grid-template-columns: 1fr;
            padding: 10px;
         }
         
         .address-fields {
            grid-template-columns: 1fr;
         }
         
         .order-summary {
            position: static;
         }
         
         .modal-content {
            margin: 20% auto;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="checkout-form">
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
</section>

<div class="checkout-container">
   <div class="checkout-main">
      <div class="checkout-card delivery-address">
         <div class="checkout-header">
            <i class="fas fa-map-marker-alt"></i>
            <span>Delivery Address</span>
         </div>
         <form action="" method="POST" id="checkoutForm">
            <div class="form-group">
               <label class="form-label">Full Name</label>
               <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
               <span class="error-message">Please enter your full name</span>
            </div>
            
            <div class="form-group">
               <label class="form-label">Phone Number</label>
               <input type="tel" name="number" class="form-control" placeholder="Enter your phone number" required>
               <span class="error-message">Please enter a valid phone number</span>
            </div>
            
            <div class="form-group">
               <label class="form-label">Email Address</label>
               <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
               <span class="error-message">Please enter a valid email address</span>
            </div>
            
            <div class="address-fields">
               <div class="form-group">
                  <label class="form-label">State</label>
                  <input type="text" name="state" class="form-control" placeholder="Your state" required>
                  <span class="error-message">Please enter your state</span>
               </div>
               
               <div class="form-group">
                  <label class="form-label">Postal Code</label>
                  <input type="text" name="pin_code" class="form-control" placeholder="Postal code" required>
                  <span class="error-message">Please enter a valid postal code</span>
               </div>
            </div>
      </div>
      
      <div class="checkout-card payment-method">
         <div class="checkout-header">
            <i class="fas fa-credit-card"></i>
            <span>Payment Method</span>
         </div>
         
         <div class="payment-options">
            <label class="payment-option active">
               <input type="radio" name="method" value="cash on delivery" checked>
               <i class="fas fa-money-bill-wave"></i> Cash on Delivery
            </label>
            
            <label class="payment-option">
               <input type="radio" name="method" value="esewa">
               <i class="fas fa-wallet"></i> Esewa
            </label>
         </div>
      </div>
   </div>
   
   <div class="order-summary">
      <div class="checkout-card">
         <div class="checkout-header">
            <i class="fas fa-shopping-bag"></i>
            <span>Order Summary</span>
         </div>
         
         <div class="order-items">
         <?php
            $grand_total = 0;
            $cart_items = [];
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if($select_cart->rowCount() > 0){
               while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
                  $cart_items[] = $fetch_cart['name'].' ('.$fetch_cart['price'].' x '. $fetch_cart['quantity'].') - ';
                  $grand_total += ($fetch_cart['price'] * $fetch_cart['quantity']);
         ?>
            <div class="order-item">
               <span><?= $fetch_cart['name']; ?> × <?= $fetch_cart['quantity']; ?></span>
               <span>₹<?= $fetch_cart['price'] * $fetch_cart['quantity']; ?></span>
            </div>
         <?php
               }
               $total_products = implode($cart_items);
            }else{
               echo '<p class="empty">Your cart is empty!</p>';
            }
         ?>
         </div>
         
         <input type="hidden" name="total_products" value="<?= isset($total_products) ? $total_products : ''; ?>">
         <input type="hidden" name="total_price" value="<?= $grand_total; ?>">
         
         <div class="price-summary">
            <div class="price-row">
               <span>Subtotal</span>
               <span>₹<?= $grand_total; ?></span>
            </div>
            <div class="price-row">
               <span>Delivery Fee</span>
               <span>₹0</span>
            </div>
            <div class="price-row total-price">
               <span>Total</span>
               <span>₹<?= $grand_total; ?></span>
            </div>
         </div>
         
         <button type="submit" name="order" class="btn-checkout <?= ($grand_total > 1)?'':'disabled'; ?>">
            PLACE ORDER
         </button>
         </form>
      </div>
   </div>
</div>

<!-- Esewa Payment Modal -->
<div id="esewaModal" class="modal">
   <div class="modal-content">
      <div class="modal-header">
         <h3>Esewa Payment</h3>
         <span class="close">&times;</span>
      </div>
      
      <div class="esewa-logo">
         <img src="https://esewa.com.np/common/images/esewa_logo.png" alt="Esewa Logo">
      </div>
      
      <form class="esewa-form" method="POST" id="esewaForm">
         <input type="hidden" name="total_price" value="<?= $grand_total; ?>">
         <input type="hidden" name="total_products" value="<?= isset($total_products) ? $total_products : ''; ?>">
         <input type="hidden" name="name" value="<?= isset($_POST['name']) ? $_POST['name'] : ''; ?>">
         <input type="hidden" name="number" value="<?= isset($_POST['number']) ? $_POST['number'] : ''; ?>">
         <input type="hidden" name="email" value="<?= isset($_POST['email']) ? $_POST['email'] : ''; ?>">
         <input type="hidden" name="state" value="<?= isset($_POST['state']) ? $_POST['state'] : ''; ?>">
         <input type="hidden" name="pin_code" value="<?= isset($_POST['pin_code']) ? $_POST['pin_code'] : ''; ?>">
         
         <div class="form-group">
            <label class="form-label">Esewa Username</label>
            <input type="text" name="esewa_username" class="form-control" placeholder="Enter your Esewa username" required>
         </div>
         
         <div class="form-group">
            <label class="form-label">Esewa Password</label>
            <input type="password" name="esewa_password" class="form-control" placeholder="Enter your Esewa password" required>
         </div>
         
         <div class="form-group">
            <button type="button" id="requestOtpBtn" class="btn-request-otp">
               REQUEST OTP
            </button>
         </div>
         
         <div class="otp-section" id="otpSection">
            <div class="form-group">
               <label class="form-label">Enter OTP</label>
               <input type="text" name="otp_code" class="form-control" placeholder="Enter 6-digit OTP" maxlength="6" required>
            </div>
            
            <div class="otp-timer" id="otpTimer">
               OTP valid for: <span id="countdown">04:00</span>
            </div>
            
            <div class="resend-otp" id="resendOtp">
               Didn't receive OTP? Resend
            </div>
            
            <div class="form-group">
               <label class="form-label">Amount to Pay</label>
               <input type="text" class="form-control" value="₹<?= $grand_total; ?>" readonly>
            </div>
            
            <button type="submit" name="esewa_payment" class="btn-esewa">
               VERIFY & PAY
            </button>
         </div>
         
         <div class="demo-credentials">
            <p><strong>Demo Credentials:</strong></p>
            <p>Username: <strong>esewa_user</strong></p>
            <p>Password: <strong>esewa123</strong></p>
         </div>
      </form>
   </div>
</div>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>

<script>
   document.addEventListener('DOMContentLoaded', function() {
      // Form validation functions
      function validateName(name) {
         return name.trim().length >= 3;
      }
      
      function validatePhone(phone) {
         const phoneRegex = /^(98|97)\d{8}$/;
         return phoneRegex.test(phone);
      }
      
      function validateEmail(email) {
         const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
         return emailRegex.test(email);
      }
      
      function validateState(state) {
         return state.trim().length >= 2;
      }
      
      function validatePinCode(pinCode) {
         const pinRegex = /^[0-9]{4,10}$/;
         return pinRegex.test(pinCode);
      }
      
      // Real-time validation
      const form = document.getElementById('checkoutForm');
      const fields = {
         name: form.querySelector('[name="name"]'),
         number: form.querySelector('[name="number"]'),
         email: form.querySelector('[name="email"]'),
         state: form.querySelector('[name="state"]'),
         pin_code: form.querySelector('[name="pin_code"]')
      };
      
      // Add event listeners for real-time validation
      Object.keys(fields).forEach(fieldName => {
         const field = fields[fieldName];
         const errorMessage = field.nextElementSibling;
         
         field.addEventListener('input', function() {
            validateField(fieldName);
         });
         
         field.addEventListener('blur', function() {
            validateField(fieldName);
         });
      });
      
      function validateField(fieldName) {
         const field = fields[fieldName];
         const value = field.value.trim();
         const errorMessage = field.nextElementSibling;
         let isValid = false;
         
         switch(fieldName) {
            case 'name':
               isValid = validateName(value);
               break;
            case 'number':
               isValid = validatePhone(value);
               break;
            case 'email':
               isValid = validateEmail(value);
               break;
            case 'state':
               isValid = validateState(value);
               break;
            case 'pin_code':
               isValid = validatePinCode(value);
               break;
         }
         
         if (isValid) {
            field.classList.remove('error');
            errorMessage.style.display = 'none';
         } else {
            field.classList.add('error');
            errorMessage.style.display = 'block';
         }
         
         return isValid;
      }
      
      // Validate entire form
      function validateForm() {
         let isValid = true;
         
         Object.keys(fields).forEach(fieldName => {
            if (!validateField(fieldName)) {
               isValid = false;
            }
         });
         
         return isValid;
      }
      
      // Payment method selection
      document.querySelectorAll('.payment-option').forEach(option => {
         option.addEventListener('click', function() {
            document.querySelectorAll('.payment-option').forEach(opt => {
               opt.classList.remove('active');
            });
            this.classList.add('active');
            this.querySelector('input').checked = true;
         });
      });
      
      // OTP functionality
      const requestOtpBtn = document.getElementById('requestOtpBtn');
      const otpSection = document.getElementById('otpSection');
      const resendOtp = document.getElementById('resendOtp');
      const otpTimer = document.getElementById('otpTimer');
      const countdown = document.getElementById('countdown');
      let otpCountdown;
      let canResend = false;
      
      requestOtpBtn.addEventListener('click', function() {
         const username = document.querySelector('#esewaModal input[name="esewa_username"]').value;
         const password = document.querySelector('#esewaModal input[name="esewa_password"]').value;
         
         if(!username || !password) {
            alert('Please enter your Esewa credentials first');
            return;
         }
         
         // Request OTP via AJAX
         const formData = new FormData();
         formData.append('request_otp', true);
         formData.append('esewa_username', username);
         formData.append('esewa_password', password);
         
         fetch('', {
            method: 'POST',
            body: formData
         })
         .then(response => response.json())
         .then(data => {
            if(data.status === 'success') {
               // Show OTP section
               otpSection.style.display = 'block';
               requestOtpBtn.style.display = 'none';
               
               // Start countdown timer
               startOtpCountdown();
               
               // Show demo OTP (for testing purposes)
               alert('Demo OTP: ' + data.demo_otp + '\nIn a real application, this would be sent in essewa page.');
            } else {
               alert(data.message);
            }
         })
         .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while requesting OTP');
         });
      });
      
      // Start OTP countdown timer
      function startOtpCountdown() {
         let timeLeft = 300; // 5 minutes in seconds
         canResend = false;
         resendOtp.classList.add('disabled');
         
         clearInterval(otpCountdown);
         
         otpCountdown = setInterval(function() {
            timeLeft--;
            
            if(timeLeft <= 0) {
               clearInterval(otpCountdown);
               canResend = true;
               resendOtp.classList.remove('disabled');
               otpTimer.innerHTML = 'OTP expired';
            } else {
               const minutes = Math.floor(timeLeft / 60);
               const seconds = timeLeft % 60;
               countdown.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
         }, 1000);
      }
      
      // Resend OTP
      resendOtp.addEventListener('click', function() {
         if(canResend) {
            requestOtpBtn.click();
         }
      });
      
      // Esewa form submission
      document.getElementById('esewaForm').addEventListener('submit', function(e) {
         const otpInput = document.querySelector('input[name="otp_code"]');
         
         if(otpInput && !otpInput.value) {
            e.preventDefault();
            alert('Please enter the OTP');
         }
      });
      
      // Esewa modal functionality
      const esewaRadio = document.querySelector('input[value="esewa"]');
      const esewaModal = document.getElementById('esewaModal');
      const closeBtn = document.querySelector('.close');
      const placeOrderBtn = document.querySelector('button[name="order"]');
      
      placeOrderBtn.addEventListener('click', function(e) {
         if(esewaRadio.checked) {
            e.preventDefault();
            
            if (validateForm()) {
               // Collect all form data and populate hidden fields in modal
               document.querySelector('#esewaModal input[name="name"]').value = fields.name.value;
               document.querySelector('#esewaModal input[name="number"]').value = fields.number.value;
               document.querySelector('#esewaModal input[name="email"]').value = fields.email.value;
               document.querySelector('#esewaModal input[name="state"]').value = fields.state.value;
               document.querySelector('#esewaModal input[name="pin_code"]').value = fields.pin_code.value;
               
               esewaModal.style.display = 'block';
            } else {
               alert('Please fix all errors before proceeding to payment.');
            }
         } else {
            if (!validateForm()) {
               e.preventDefault();
               alert('Please fix all errors before placing your order.');
            }
         }
      });
      
      closeBtn.addEventListener('click', function() {
         esewaModal.style.display = 'none';
         // Reset OTP section
         otpSection.style.display = 'none';
         requestOtpBtn.style.display = 'block';
         clearInterval(otpCountdown);
      });
      
      window.addEventListener('click', function(e) {
         if(e.target === esewaModal) {
            esewaModal.style.display = 'none';
            // Reset OTP section
            otpSection.style.display = 'none';
            requestOtpBtn.style.display = 'block';
            clearInterval(otpCountdown);
         }
      });
   });
</script>
</body>
</html>