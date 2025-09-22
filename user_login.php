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
      overflow-x: hidden;
      position: relative;
   }

   /* Animated background elements */
   .bg-bubbles {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      overflow: hidden;
      pointer-events: none;
   }

   .bg-bubbles li {
      position: absolute;
      list-style: none;
      display: block;
      width: 40px;
      height: 40px;
      background-color: rgba(67, 97, 238, 0.1);
      bottom: -160px;
      border-radius: 50%;
      animation: square 25s infinite;
      transition-timing-function: linear;
   }

   .bg-bubbles li:nth-child(1) {
      left: 10%;
      animation-delay: 0s;
      width: 80px;
      height: 80px;
   }

   .bg-bubbles li:nth-child(2) {
      left: 20%;
      animation-delay: 2s;
      animation-duration: 17s;
      width: 60px;
      height: 60px;
   }

   .bg-bubbles li:nth-child(3) {
      left: 25%;
      animation-delay: 4s;
      width: 100px;
      height: 100px;
   }

   .bg-bubbles li:nth-child(4) {
      left: 40%;
      animation-delay: 0s;
      animation-duration: 22s;
      width: 120px;
      height: 120px;
   }

   .bg-bubbles li:nth-child(5) {
      left: 70%;
      animation-delay: 3s;
      width: 70px;
      height: 70px;
   }

   .bg-bubbles li:nth-child(6) {
      left: 80%;
      animation-delay: 2s;
      width: 90px;
      height: 90px;
   }

   .bg-bubbles li:nth-child(7) {
      left: 32%;
      animation-delay: 6s;
      width: 110px;
      height: 110px;
   }

   .bg-bubbles li:nth-child(8) {
      left: 55%;
      animation-delay: 8s;
      animation-duration: 18s;
      width: 50px;
      height: 50px;
   }

   .bg-bubbles li:nth-child(9) {
      left: 25%;
      animation-delay: 9s;
      animation-duration: 20s;
      width: 30px;
      height: 30px;
   }

   .bg-bubbles li:nth-child(10) {
      left: 90%;
      animation-delay: 11s;
      width: 70px;
      height: 70px;
   }

   @keyframes square {
      0% {
         transform: translateY(0) rotate(0deg);
         opacity: 0.5;
         border-radius: 50%;
      }
      50% {
         opacity: 0.7;
      }
      100% {
         transform: translateY(-1000px) rotate(720deg);
         opacity: 0;
         border-radius: 50%;
      }
   }

   .floating-shapes {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      overflow: hidden;
      pointer-events: none;
   }

   .shape {
      position: absolute;
      opacity: 0.1;
      animation: float 15s infinite linear;
   }

   .shape--0 {
      top: 15%;
      left: 10%;
      width: 100px;
      height: 100px;
      background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
      border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
      animation-delay: 0s;
      animation-duration: 20s;
   }

   .shape--1 {
      top: 65%;
      left: 75%;
      width: 120px;
      height: 120px;
      background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
      border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
      animation-delay: 2s;
      animation-duration: 25s;
   }

   .shape--2 {
      top: 25%;
      left: 80%;
      width: 80px;
      height: 80px;
      background: linear-gradient(45deg, var(--accent-color), var(--secondary-color));
      border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
      animation-delay: 4s;
      animation-duration: 18s;
   }

   .shape--3 {
      top: 70%;
      left: 15%;
      width: 90px;
      height: 90px;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      border-radius: 38% 62% 63% 37% / 41% 44% 56% 59%;
      animation-delay: 1s;
      animation-duration: 22s;
   }

   @keyframes float {
      0% {
         transform: translate(0, 0) rotate(0deg);
      }
      25% {
         transform: translate(5px, 10px) rotate(5deg);
      }
      50% {
         transform: translate(10px, 5px) rotate(0deg);
      }
      75% {
         transform: translate(5px, 0px) rotate(-5deg);
      }
      100% {
         transform: translate(0, 0) rotate(0deg);
      }
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
      animation: slideDown 0.5s ease-out;
   }

   @keyframes slideDown {
      from {
         transform: translateY(-100%);
         opacity: 0;
      }
      to {
         transform: translateY(0);
         opacity: 1;
      }
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
      animation: pulse 2s infinite;
   }

   @keyframes pulse {
      0% {
         transform: scale(1);
      }
      50% {
         transform: scale(1.1);
      }
      100% {
         transform: scale(1);
      }
   }

   .theme-toggle:hover {
      color: var(--primary-color);
      transform: rotate(30deg);
      animation: none;
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
      animation: fadeInUp 0.6s ease-out, glow 3s infinite alternate;
      transition: var(--transition);
      border: 1px solid var(--border-color);
      position: relative;
      overflow: hidden;
   }

   .form-container::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(
         to bottom right,
         rgba(67, 97, 238, 0.1),
         rgba(73, 149, 239, 0.1),
         rgba(63, 55, 201, 0.1)
      );
      transform: rotate(30deg);
      z-index: 0;
      animation: shimmer 8s infinite linear;
   }

   @keyframes shimmer {
      0% {
         transform: rotate(30deg) translateX(-100%);
      }
      100% {
         transform: rotate(30deg) translateX(100%);
      }
   }

   @keyframes glow {
      0% {
         box-shadow: var(--shadow);
      }
      100% {
         box-shadow: 0 0 25px rgba(67, 97, 238, 0.2);
      }
   }

   @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
   }

   .form-header {
      text-align: center;
      margin-bottom: 2rem;
      position: relative;
      z-index: 1;
   }

   .form-header h2 {
      color: var(--primary-color);
      font-size: 2rem;
      margin-bottom: 0.5rem;
      animation: typing 2s steps(15), blink 0.5s step-end infinite alternate;
      white-space: nowrap;
      overflow: hidden;
      border-right: 3px solid;
      width: 15ch;
      margin: 0 auto 0.5rem;
   }

   @keyframes typing {
      from {
         width: 0;
      }
   }
   
   @keyframes blink {
      50% {
         border-color: transparent;
      }
   }

   .form-header p {
      color: var(--text-color);
      opacity: 0.8;
      animation: fadeIn 1s ease-out 1s both;
   }

   @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 0.8; }
   }

   .form-group {
      margin-bottom: 1.5rem;
      position: relative;
      z-index: 1;
      animation: slideInLeft 0.5s ease-out;
   }

   @keyframes slideInLeft {
      from {
         opacity: 0;
         transform: translateX(-20px);
      }
      to {
         opacity: 1;
         transform: translateX(0);
      }
   }

   .form-group:nth-child(1) {
      animation-delay: 0.2s;
   }

   .form-group:nth-child(2) {
      animation-delay: 0.4s;
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
      transition: var(--transition);
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
      position: relative;
      z-index: 1;
   }

   .form-control:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
   }

   .form-control:focus + i {
      color: var(--accent-color);
      transform: translateY(-50%) scale(1.2);
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
      transform: translateY(-50%) scale(1.1);
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
      position: relative;
      z-index: 1;
      overflow: hidden;
      animation: bounceIn 1s;
   }

   @keyframes bounceIn {
      0% {
         transform: scale(0.8);
         opacity: 0;
      }
      60% {
         transform: scale(1.05);
      }
      100% {
         transform: scale(1);
         opacity: 1;
      }
   }

   .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: 0.5s;
   }

   .btn:hover::before {
      left: 100%;
   }

   .btn:hover {
      background-color: var(--secondary-color);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
   }

   .btn i {
      font-size: 1.1rem;
      transition: var(--transition);
   }

   .btn:hover i {
      transform: translateX(3px);
   }

   .form-footer {
      text-align: center;
      margin-top: 1.5rem;
      color: var(--text-color);
      opacity: 0.8;
      font-size: 0.9rem;
      position: relative;
      z-index: 1;
      animation: fadeIn 1s ease-out 0.8s both;
   }

   .form-footer a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
      position: relative;
   }

   .form-footer a::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      width: 0;
      height: 2px;
      background-color: var(--accent-color);
      transition: var(--transition);
   }

   .form-footer a:hover {
      color: var(--accent-color);
   }

   .form-footer a:hover::after {
      width: 100%;
   }

   .divider {
      display: flex;
      align-items: center;
      margin: 1.5rem 0;
      color: var(--text-color);
      opacity: 0.6;
      position: relative;
      z-index: 1;
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
      animation: shake 0.5s ease-in-out, fadeIn 0.3s ease-out;
      position: relative;
      z-index: 1;
   }

   @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
   }

   .social-login {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      position: relative;
      z-index: 1;
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
      animation: fadeIn 1s ease-out;
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

      .bg-bubbles li {
         width: 30px !important;
         height: 30px !important;
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

   /* Confetti animation */
   .confetti {
      position: absolute;
      z-index: 2;
      pointer-events: none;
   }

   @keyframes confettiFall {
      0% {
         transform: translateY(-100px) rotate(0deg);
         opacity: 1;
      }
      100% {
         transform: translateY(1000px) rotate(720deg);
         opacity: 0;
      }
   }
   </style>
</head>
<body>
   
   <!-- Animated background elements -->
   <ul class="bg-bubbles">
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
   </ul>

   <div class="floating-shapes">
      <div class="shape shape--0"></div>
      <div class="shape shape--1"></div>
      <div class="shape shape--2"></div>
      <div class="shape shape--3"></div>
   </div>

<header class="header">
    <a href="home.php" class="logo">
        <div class="cube-container">
            <i class="fas fa-store rotating-cube"></i>
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

   // Add animation to input fields on focus
   const inputs = document.querySelectorAll('.form-control');
   inputs.forEach(input => {
      input.addEventListener('focus', () => {
         input.parentElement.querySelector('i').style.transform = 'translateY(-50%) scale(1.2)';
         input.style.boxShadow = '0 0 15px rgba(67, 97, 238, 0.2)';
      });
      
      input.addEventListener('blur', () => {
         input.parentElement.querySelector('i').style.transform = 'translateY(-50%) scale(1)';
         input.style.boxShadow = 'none';
      });
   });

   // Create confetti animation on button click
   const loginBtn = document.querySelector('.btn');
   loginBtn.addEventListener('click', function(e) {
      if (!this.closest('form').checkValidity()) return;
      
      // Create confetti elements
      for (let i = 0; i < 30; i++) {
         const confetti = document.createElement('div');
         confetti.classList.add('confetti');
         confetti.innerHTML = '<i class="fas fa-star"></i>';
         confetti.style.left = Math.random() * 100 + '%';
         confetti.style.color = ['#4361ee', '#3f37c9', '#4895ef', '#4cc9f0'][Math.floor(Math.random() * 4)];
         confetti.style.fontSize = (Math.random() * 10 + 10) + 'px';
         confetti.style.animation = `confettiFall ${Math.random() * 3 + 2}s linear forwards`;
         document.body.appendChild(confetti);
         
         // Remove confetti after animation completes
         setTimeout(() => {
            confetti.remove();
         }, 5000);
      }
   });

   // Add subtle hover animation to form container
   const formContainer = document.querySelector('.form-container');
   formContainer.addEventListener('mousemove', function(e) {
      const x = e.clientX / window.innerWidth;
      const y = e.clientY / window.innerHeight;
      
      this.style.transform = `perspective(1000px) rotateX(${(y - 0.5) * 2}deg) rotateY(${(x - 0.5) * 2}deg)`;
   });
   
   formContainer.addEventListener('mouseleave', function() {
      this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
   });
</script>

</body>
</html>