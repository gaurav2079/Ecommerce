<?php
// Start session at the very beginning
session_start();

include 'components/connect.php';

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

function custom_hash($password) {
    $pepper = "N3p@l$t0r3P3pp3r!"; // Your secret pepper
    $salted_password = $password . $pepper;
    
    $key = 0;
    $p = 31;
    $q = 7;
    $m = 1000000007;
    
    // Calculate initial key
    for ($i = 0; $i < strlen($salted_password); $i++) {
        $key = ($key * 31 + ord($salted_password[$i])) % $m;
    }
    
    // Apply multiple iterations
    for ($i = 0; $i < 1000; $i++) {
        $key = ($key * $p + $q) % $m;
    }
    
    return strval($key);
}

if(isset($_POST['submit'])){
   // Sanitize inputs
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
   $pass = $_POST['pass'];
   $cpass = $_POST['cpass'];

   // Validate inputs
   if(empty($name) || empty($email) || empty($pass) || empty($cpass)){
      $message[] = 'All fields are required!';
   } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
      $message[] = 'Invalid email format!';
   } elseif(strlen($pass) < 8){
      $message[] = 'Password must be at least 8 characters!';
   } else {
      // Check if email exists
      $select_user = $conn->prepare("SELECT * FROM users WHERE email = ?");
      $select_user->execute([$email]);
      
      if($select_user->rowCount() > 0){
         $message[] = 'Email already exists!';
      } else {
         if($pass !== $cpass){
            $message[] = 'Confirm password not matched!';
         } else {
            // Hash the password using custom hash function
            $hashed_password = custom_hash($pass);
            
            // Insert user into database
            $insert_user = $conn->prepare("INSERT INTO users(name, email, password) VALUES(?,?,?)");
            $insert_user->execute([$name, $email, $hashed_password]);
            
            if($insert_user->rowCount() > 0){
               $message[] = 'Registered successfully! Please login now.';
            } else {
               $message[] = 'Registration failed! Please try again.';
            }
         }
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
   <title>Register | Nepal Store</title>
   
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
   <!-- Google Fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

   <style>
   :root {
      --primary-color: #4361ee;
      --secondary-color: #3f37c9;
      --accent-color: #4895ef;
      --text-color: #2b2d42;
      --bg-color: #f8f9fa;
      --card-bg: #ffffff;
      --border-color: #e9ecef;
      --error-color: #ef233c;
      --success-color: #4cc9f0;
      --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
   }

   [data-theme="dark"] {
      --primary-color: #4895ef;
      --secondary-color: #4361ee;
      --accent-color: #3f37c9;
      --text-color: #f8f9fa;
      --bg-color: #121212;
      --card-bg: #1e1e1e;
      --border-color: #2d2d2d;
      --error-color: #ff6b6b;
      --success-color: #4cc9f0;
      --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
   }

   * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
   }

   body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      transition: var(--transition);
   }

   .header {
      width: 100%;
      padding: 1.5rem 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: var(--card-bg);
      box-shadow: var(--shadow);
      position: fixed;
      top: 0;
      z-index: 1000;
      transition: var(--transition);
   }

   .logo {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--primary-color);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
   }

   .logo i {
      color: var(--accent-color);
   }

   .theme-toggle {
      background: none;
      border: none;
      color: var(--text-color);
      font-size: 1.2rem;
      cursor: pointer;
      transition: var(--transition);
   }

   .theme-toggle:hover {
      color: var(--primary-color);
      transform: rotate(30deg);
   }

   main {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
      margin-top: 80px;
   }

   .form-container {
      background-color: var(--card-bg);
      padding: 2.5rem;
      border-radius: 16px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 500px;
      animation: fadeInUp 0.6s ease-out;
      transition: var(--transition);
      border: 1px solid var(--border-color);
   }

   @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
   }

   .form-header {
      text-align: center;
      margin-bottom: 2rem;
   }

   .form-header h2 {
      color: var(--primary-color);
      font-size: 2rem;
      margin-bottom: 0.5rem;
   }

   .form-header p {
      color: var(--text-color);
      opacity: 0.8;
   }

   .form-group {
      margin-bottom: 1.5rem;
      position: relative;
   }

   .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--text-color);
   }

   .input-with-icon {
      position: relative;
   }

   .input-with-icon i {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--primary-color);
   }

   .form-control {
      width: 100%;
      padding: 0.9rem 1rem 0.9rem 3rem;
      background-color: var(--bg-color);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: var(--text-color);
      font-size: 1rem;
      transition: var(--transition);
   }

   .form-control:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
   }

   .password-container {
      position: relative;
   }

   .password-toggle {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-color);
      opacity: 0.6;
      cursor: pointer;
      transition: var(--transition);
      background: transparent;
      border: none;
      outline: none;
      z-index: 10;
      padding: 0.5rem;
   }

   .password-toggle:hover {
      opacity: 1;
      color: var(--primary-color);
   }

   .btn {
      width: 100%;
      padding: 1rem;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      margin-top: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
   }

   .btn:hover {
      background-color: var(--secondary-color);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
   }

   .btn i {
      font-size: 1.1rem;
   }

   .form-footer {
      text-align: center;
      margin-top: 1.5rem;
      color: var(--text-color);
      opacity: 0.8;
      font-size: 0.9rem;
   }

   .form-footer a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
   }

   .form-footer a:hover {
      color: var(--accent-color);
      text-decoration: underline;
   }

   .divider {
      display: flex;
      align-items: center;
      margin: 1.5rem 0;
      color: var(--text-color);
      opacity: 0.6;
   }

   .divider::before, .divider::after {
      content: "";
      flex: 1;
      border-bottom: 1px solid var(--border-color);
   }

   .divider span {
      padding: 0 1rem;
   }

   .message {
      padding: 0.8rem 1rem;
      margin-bottom: 1.5rem;
      border-radius: 8px;
      background-color: rgba(239, 35, 60, 0.1);
      border: 1px solid rgba(239, 35, 60, 0.2);
      color: var(--error-color);
      text-align: center;
      font-size: 0.9rem;
      animation: fadeIn 0.3s ease-out;
   }

   .success {
      background-color: rgba(76, 201, 240, 0.1);
      border: 1px solid rgba(76, 201, 240, 0.2);
      color: var(--success-color);
   }

   @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
   }

   /* Responsive adjustments */
   @media (max-width: 576px) {
      .form-container {
         padding: 1.5rem;
      }
      
      .header {
         padding: 1rem 5%;
      }
      
      .logo {
         font-size: 1.5rem;
      }
   }

   /* Logo container */
   .logo {
       display: flex;
       align-items: center;
       gap: 10px;
       text-decoration: none;
   }

   /* Cube container styles */
   .cube-container {
       width: 30px;
       height: 30px;
       display: flex;
       align-items: center;
       justify-content: center;
   }

   .rotating-cube {
       font-size: 1.5rem;
       color: var(--primary-color);
       animation: rotateCube 8s infinite linear;
       transform-style: preserve-3d;
   }

   /* Logo text styles */
   .logo-text {
       font-size: 1.5rem;
       font-weight: 700;
       color: var(--text-color);
       display: inline-block;
       animation: waveText 3s ease-in-out infinite;
   }

   /* Animations */
   @keyframes rotateCube {
       0% {
           transform: rotateY(0deg) rotateZ(0deg);
       }
       25% {
           transform: rotateY(90deg) rotateZ(10deg);
       }
       50% {
           transform: rotateY(180deg) rotateZ(0deg);
       }
       75% {
           transform: rotateY(270deg) rotateZ(-10deg);
       }
       100% {
           transform: rotateY(360deg) rotateZ(0deg);
       }
   }

   @keyframes waveText {
       0%, 100% {
           transform: translateY(0);
       }
       50% {
           transform: translateY(-3px);
       }
   }
   </style>
</head>
<body>
   
<header class="header">
    <a href="home.php" class="logo">
        <div class="cube-container">
            <i class="fas fa-cube rotating-cube"></i>
        </div>
        <span class="logo-text">Nepal~Store</span>
    </a>
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
</header>

<main>
    <section class="form-container">
        <div class="form-header">
            <h2>Create Your Account</h2>
            <p>Join our community today</p>
        </div>
        
        <?php
        if(isset($message)){
            foreach($message as $msg){
                $class = (strpos($msg, 'successfully') !== false) ? 'message success' : 'message';
                echo '<div class="'.$class.'">'.$msg.'</div>';
            }
        }
        ?>
        
        <form action="" method="post">
            <div class="form-group">
                <label for="name">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" required placeholder="Enter your username" maxlength="20" class="form-control" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required placeholder="Enter your email" maxlength="50" class="form-control" oninput="this.value = this.value.replace(/\s/g, '')" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon password-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="pass" required placeholder="Enter your password (min 8 characters)" minlength="8" maxlength="20" class="form-control" oninput="this.value = this.value.replace(/\s/g, '')">
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="cpassword">Confirm Password</label>
                <div class="input-with-icon password-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="cpassword" name="cpass" required placeholder="Confirm your password" minlength="8" maxlength="20" class="form-control" oninput="this.value = this.value.replace(/\s/g, '')">
                    <button type="button" class="password-toggle" id="toggleCPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn" name="submit">
                <i class="fas fa-user-plus"></i>
                <span>Register Now</span>
            </button>
            
            <div class="form-footer">
                Already have an account? <a href="user_login.php">Login here</a>
            </div>
        </form>
    </section>
</main>

<script>
   // Password toggle functionality
   const togglePassword = document.querySelector('#togglePassword');
   const password = document.querySelector('#password');
   const toggleCPassword = document.querySelector('#toggleCPassword');
   const cpassword = document.querySelector('#cpassword');
   
   togglePassword.addEventListener('click', function() {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      this.querySelector('i').classList.toggle('fa-eye');
      this.querySelector('i').classList.toggle('fa-eye-slash');
   });
   
   toggleCPassword.addEventListener('click', function() {
      const type = cpassword.getAttribute('type') === 'password' ? 'text' : 'password';
      cpassword.setAttribute('type', type);
      this.querySelector('i').classList.toggle('fa-eye');
      this.querySelector('i').classList.toggle('fa-eye-slash');
   });

   // Theme toggle functionality
   const themeToggle = document.getElementById('themeToggle');
   const themeIcon = themeToggle.querySelector('i');
   const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
   
   // Check for saved theme preference or use system preference
   const currentTheme = localStorage.getItem('theme');
   if (currentTheme === 'dark' || (!currentTheme && prefersDarkScheme.matches)) {
      document.documentElement.setAttribute('data-theme', 'dark');
      themeIcon.classList.remove('fa-moon');
      themeIcon.classList.add('fa-sun');
   }
   
   themeToggle.addEventListener('click', function() {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      if (currentTheme === 'dark') {
         document.documentElement.removeAttribute('data-theme');
         themeIcon.classList.remove('fa-sun');
         themeIcon.classList.add('fa-moon');
         localStorage.setItem('theme', 'light');
      } else {
         document.documentElement.setAttribute('data-theme', 'dark');
         themeIcon.classList.remove('fa-moon');
         themeIcon.classList.add('fa-sun');
         localStorage.setItem('theme', 'dark');
      }
   });
</script>

</body>
</html>