<?php
session_start();
include 'components/connect.php';

// User class to handle authentication
class User {
    private $conn;
    private $pepper = "N3p@l4598!";
    
    public function __construct($db_conn) {
        $this->conn = $db_conn;
    }
    
    private function custom_hash($password) {
        $salted_password = $password . $this->pepper;
        
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
    
    public function login($email, $password) {
        try {
            $hashed_password = $this->custom_hash($password);
            
            $select_user = $this->conn->prepare("SELECT * FROM `users` WHERE email = ? AND password = ?");
            $select_user->execute([$email, $hashed_password]);
            
            if($select_user->rowCount() > 0){
                $row = $select_user->fetch(PDO::FETCH_ASSOC);
                $_SESSION['user_id'] = $row['id'];
                return true;
            }
            
            return false;
        } catch(PDOException $exception) {
            error_log("Login error: " . $exception->getMessage());
            return false;
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? '';
    }
    
    public function redirectIfLoggedIn($location = 'home.php') {
        if ($this->isLoggedIn()) {
            header("Location: " . $location);
            exit();
        }
    }
}

// Initialize User object
$user = new User($conn);

// Check if user is already logged in
$user_id = $user->getUserId();
$user->redirectIfLoggedIn();

// Message array for displaying errors
$messages = [];

// Process login form
if(isset($_POST['submit'])){
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = $_POST['pass'];

   if($user->login($email, $pass)){
      header('location:home.php');
      exit();
   } else {
      $messages[] = 'Incorrect username or password!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login | Nepal~Store</title>
   
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
      max-width: 450px;
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

   .form-control {
      padding-right: 3rem !important;
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

   @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
   }

   .social-login {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
   }

   .social-btn {
      flex: 1;
      padding: 0.8rem;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      border: 1px solid var(--border-color);
      background-color: var(--card-bg);
      color: var(--text-color);
   }

   .social-btn.google { color: #DB4437; }
   .social-btn.facebook { color: #4267B2; }
   .social-btn.apple { color: #000000; }

   .social-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow);
   }

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

   .logo-text {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-color);
      display: inline-block;
      animation: waveText 3s ease-in-out infinite;
   }

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
            <h2>Welcome back!</h2>
            <p>Sign in to access your account</p>
        </div>
        
        <?php
        if(!empty($messages)){
            foreach($messages as $msg){
                echo '<div class="message">'.$msg.'</div>';
            }
        }
        ?>
        
        <form action="" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required placeholder="Enter your email" class="form-control" oninput="this.value = this.value.replace(/\s/g, '')">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon" style="position: relative;">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="pass" required placeholder="Enter your password" class="form-control" oninput="this.value = this.value.replace(/\s/g, '')">
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-footer" style="text-align: right; margin-top: 0.5rem;">
                    <a href="forget_password.php">Forgot password?</a>
                </div>
            </div>
            
            <button type="submit" class="btn" name="submit">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </button>
            
            <div class="divider">
                <span>or continue with</span>
            </div>
            
            <div class="form-footer">
                Don't have an account? <a href="user_register.php">Sign up</a>
            </div>
        </form>
    </section>
</main>

<script>
   // Password toggle functionality
   const togglePassword = document.querySelector('#togglePassword');
   const password = document.querySelector('#password');
   
   togglePassword.addEventListener('click', function (e) {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
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

   // Add animation to social buttons on hover
   const socialButtons = document.querySelectorAll('.social-btn');
   socialButtons.forEach(button => {
      button.addEventListener('mouseenter', () => {
         const icon = button.querySelector('i');
         icon.style.transform = 'scale(1.2)';
         setTimeout(() => {
            icon.style.transform = 'scale(1)';
         }, 300);
      });
   });
</script>

</body>
</html>