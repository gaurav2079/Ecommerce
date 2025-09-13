<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
}

if(isset($_POST['submit'])){
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);
   $cpass = sha1($_POST['cpass']);
   $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

   // Enhanced validation
   if(strlen($name) < 4 || strlen($name) > 20) {
      $message[] = 'Username must be 4-20 characters!';
   } 
   elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s]).{6,}$/', $_POST['pass'])) {
      $message[] = 'Password must contain at least 1 uppercase, 1 lowercase, 1 number, and 1 special character!';
   }
   elseif($_POST['pass'] != $_POST['cpass']) {
      $message[] = 'Passwords do not match!';
   } else {
      $select_admin = $conn->prepare("SELECT * FROM `admins` WHERE name = ?");
      $select_admin->execute([$name]);

      if($select_admin->rowCount() > 0){
         $message[] = 'Username already exists!';
      } else {
         $insert_admin = $conn->prepare("INSERT INTO `admins`(name, password) VALUES(?,?)");
         $insert_admin->execute([$name, $cpass]);
         $message[] = 'New admin registered successfully!';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Registration</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Amazon+Ember:wght@400;500;700&display=swap" rel="stylesheet">
   
   <style>
      :root {
         --amazon-orange: #FF9900;
         --amazon-dark: #131921;
         --amazon-light: #232F3E;
         --amazon-gray: #EAEDED;
         --amazon-text: #0F1111;
         --amazon-error: #C40000;
         --amazon-success: #007600;
      }
      
      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Amazon Ember', Arial, sans-serif;
      }
      
      body {
         background-color: var(--amazon-gray);
         color: var(--amazon-text);
      }
      
      .registration-container {
         max-width: 400px;
         margin: 40px auto;
         padding: 20px 26px;
         background: white;
         border-radius: 4px;
         box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      
      .logo {
         text-align: center;
         margin-bottom: 20px;
      }
      
      .logo i {
         font-size: 2.5rem;
         color: var(--amazon-orange);
      }
      
      h1 {
         font-weight: 400;
         font-size: 28px;
         line-height: 1.2;
         margin-bottom: 10px;
         padding-bottom: 4px;
      }
      
      .form-group {
         margin-bottom: 14px;
      }
      
      label {
         display: block;
         font-weight: 700;
         font-size: 13px;
         margin-bottom: 2px;
      }
      
      .required-field::after {
         content: " *";
         color: var(--amazon-error);
      }
      
      input[type="text"],
      input[type="password"] {
         width: 100%;
         height: 31px;
         padding: 3px 7px;
         border: 1px solid #a6a6a6;
         border-radius: 3px;
         box-shadow: 0 1px 0 rgba(255,255,255,.5), 0 1px 0 rgba(0,0,0,.07) inset;
         outline: 0;
      }
      
      input[type="text"]:focus,
      input[type="password"]:focus {
         border-color: var(--amazon-orange);
         box-shadow: 0 0 0 3px rgba(255,153,0,.3);
      }
      
      .password-requirements {
         font-size: 12px;
         margin-top: 5px;
         color: #555;
      }
      
      .requirement {
         display: flex;
         align-items: center;
         margin-bottom: 3px;
      }
      
      .requirement i {
         margin-right: 5px;
         font-size: 10px;
      }
      
      .requirement.valid i {
         color: var(--amazon-success);
      }
      
      .requirement.invalid i {
         color: #ccc;
      }
      
      .submit-btn {
         width: 100%;
         background: linear-gradient(to bottom,#f7dfa5,#f0c14b);
         border: 1px solid #a88734;
         border-radius: 3px;
         padding: 8px;
         font-size: 13px;
         cursor: pointer;
         margin-top: 10px;
      }
      
      .submit-btn:hover {
         background: linear-gradient(to bottom,#f5d78e,#eeb933);
      }
      
      .login-link {
         margin-top: 20px;
         font-size: 13px;
         text-align: center;
      }
      
      .login-link a {
         color: #0066c0;
         text-decoration: none;
      }
      
      .login-link a:hover {
         text-decoration: underline;
         color: var(--amazon-orange);
      }
      
      .message {
         padding: 10px;
         margin-bottom: 15px;
         border-radius: 3px;
         font-size: 13px;
      }
      
      .message.error {
         background-color: #FFE8E8;
         border: 1px solid var(--amazon-error);
         color: var(--amazon-error);
      }
      
      .message.success {
         background-color: #E6F4EA;
         border: 1px solid var(--amazon-success);
         color: var(--amazon-success);
      }
      
      .divider {
         text-align: center;
         position: relative;
         margin: 20px 0;
      }
      
      .divider::before {
         content: "";
         position: absolute;
         top: 50%;
         left: 0;
         right: 0;
         height: 1px;
         background-color: #e7e7e7;
         z-index: 1;
      }
      
      .divider span {
         position: relative;
         display: inline-block;
         padding: 0 10px;
         background-color: white;
         z-index: 2;
         color: #767676;
         font-size: 12px;
      }
   </style>
</head>
<body>
   <?php include '../components/admin_header.php'; ?>
   <div class="registration-container">
      <div class="logo">
         <i class="fas fa-cog"></i>
      </div>
      
      <h1>Create Admin Account</h1>
      
      <?php
      if(isset($message)){
         foreach($message as $msg){
            $msgClass = strpos($msg, 'successfully') !== false ? 'success' : 'error';
            echo '<div class="message '.$msgClass.'">'.$msg.'</div>';
         }
      }
      ?>
      
      <form action="" method="post" id="registerForm">
         <div class="form-group">
            <label for="name" class="required-field">Username</label>
            <input type="text" name="name" id="name" required 
                   placeholder="4-10 characters, no spaces" 
                   pattern="[a-zA-Z0-9]{4,10}"
                   oninput="this.value = this.value.replace(/\s/g, '')">
            <div id="username-error" class="error-message" style="color: var(--amazon-error); font-size: 12px; margin-top: 5px;"></div>
         </div>
         
         <div class="form-group">
            <label for="pass" class="required-field">Password</label>
            <input type="password" name="pass" id="pass" required 
                   placeholder="At least 6 characters"
                   oninput="validatePassword(this.value)">
            <div id="password-error" class="error-message" style="color: var(--amazon-error); font-size: 12px; margin-top: 5px;"></div>
            
            <div class="password-requirements">
               <div class="requirement invalid" id="req-length">
                  <i class="fas fa-circle"></i>
                  <span>Minimum 6 characters</span>
               </div>
               <div class="requirement invalid" id="req-upper">
                  <i class="fas fa-circle"></i>
                  <span>At least 1 uppercase letter</span>
               </div>
               <div class="requirement invalid" id="req-lower">
                  <i class="fas fa-circle"></i>
                  <span>At least 1 lowercase letter</span>
               </div>
               <div class="requirement invalid" id="req-number">
                  <i class="fas fa-circle"></i>
                  <span>At least 1 number</span>
               </div>
               <div class="requirement invalid" id="req-special">
                  <i class="fas fa-circle"></i>
                  <span>At least 1 special character</span>
               </div>
            </div>
         </div>
         
         <div class="form-group">
            <label for="cpass" class="required-field">Re-enter password</label>
            <input type="password" name="cpass" id="cpass" required
                   oninput="checkPasswordMatch()">
            <div id="confirm-error" class="error-message" style="color: var(--amazon-error); font-size: 12px; margin-top: 5px;"></div>
            <div id="confirm-success" style="color: var(--amazon-success); font-size: 12px; margin-top: 5px;"></div>
         </div>
         
         <button type="submit" class="submit-btn" name="submit">Create your admin account</button>
      </form>
      
      <div class="divider">
         <span>Already have an account?</span>
      </div>
      
      <div class="login-link">
         <a href="admin_login.php">Sign in</a>
      </div>
   </div>

   <script>
      function validatePassword(password) {
         // Update requirement indicators
         document.getElementById('req-length').className = password.length >= 6 ? 'requirement valid' : 'requirement invalid';
         document.getElementById('req-upper').className = /[A-Z]/.test(password) ? 'requirement valid' : 'requirement invalid';
         document.getElementById('req-lower').className = /[a-z]/.test(password) ? 'requirement valid' : 'requirement invalid';
         document.getElementById('req-number').className = /[0-9]/.test(password) ? 'requirement valid' : 'requirement invalid';
         document.getElementById('req-special').className = /[^A-Za-z0-9]/.test(password) ? 'requirement valid' : 'requirement invalid';
         
         // Update error message
         const errorElement = document.getElementById('password-error');
         if (password.length > 0 && !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s]).{6,}$/.test(password)) {
            errorElement.textContent = 'Password must meet all requirements';
            errorElement.style.display = 'block';
         } else {
            errorElement.style.display = 'none';
         }
         
         // Check password match if confirm password exists
         if (document.getElementById('cpass').value.length > 0) {
            checkPasswordMatch();
         }
      }
      
      function checkPasswordMatch() {
         const password = document.getElementById('pass').value;
         const confirmPassword = document.getElementById('cpass').value;
         const errorElement = document.getElementById('confirm-error');
         const successElement = document.getElementById('confirm-success');
         
         if (confirmPassword.length === 0) {
            errorElement.style.display = 'none';
            successElement.style.display = 'none';
         } else if (password !== confirmPassword) {
            errorElement.textContent = 'Passwords do not match';
            errorElement.style.display = 'block';
            successElement.style.display = 'none';
         } else {
            errorElement.style.display = 'none';
            successElement.textContent = 'Passwords match';
            successElement.style.display = 'block';
         }
      }
      
      document.getElementById('registerForm').addEventListener('submit', function(e) {
         const username = document.getElementById('name').value;
         const password = document.getElementById('pass').value;
         const confirmPassword = document.getElementById('cpass').value;
         let isValid = true;
         
         // Validate username
         if (username.length < 4 || username.length > 10) {
            document.getElementById('username-error').textContent = 'Username must be 4-20 characters';
            document.getElementById('username-error').style.display = 'block';
            isValid = false;
         } else {
            document.getElementById('username-error').style.display = 'none';
         }
         
         // Validate password
         if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s]).{6,}$/.test(password)) {
            document.getElementById('password-error').textContent = 'Password must meet all requirements';
            document.getElementById('password-error').style.display = 'block';
            isValid = false;
         } else {
            document.getElementById('password-error').style.display = 'none';
         }
         
         // Validate password match
         if (password !== confirmPassword) {
            document.getElementById('confirm-error').textContent = 'Passwords do not match';
            document.getElementById('confirm-error').style.display = 'block';
            document.getElementById('confirm-success').style.display = 'none';
            isValid = false;
         } else if (confirmPassword.length > 0) {
            document.getElementById('confirm-error').style.display = 'none';
            document.getElementById('confirm-success').style.display = 'block';
         }
         
         if (!isValid) {
            e.preventDefault();
            
            // Scroll to first error
            const firstError = document.querySelector('.error-message[style*="display: block"]');
            if (firstError) {
               firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
         }
      });
      
      // Real-time username validation
      document.getElementById('name').addEventListener('input', function() {
         const username = this.value;
         const errorElement = document.getElementById('username-error');
         
         if (username.length > 0 && (username.length < 4 || username.length > 20)) {
            errorElement.textContent = 'Username must be 4-20 characters';
            errorElement.style.display = 'block';
         } else {
            errorElement.style.display = 'none';
         }
      });
   </script>
</body>
</html>