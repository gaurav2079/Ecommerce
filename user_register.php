<?php
// Start session at the very beginning
session_start();

include 'components/connect.php';

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

// User class to handle registration and validation
class UserRegistration {
    private $conn;
    private $name;
    private $email;
    private $pass;
    private $cpass;
    private $errors = [];
    private $pepper = "N3p@l4598!";
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function setData($post_data) {
        $this->name = filter_var($post_data['name'], FILTER_SANITIZE_STRING);
        $this->email = filter_var($post_data['email'], FILTER_SANITIZE_EMAIL);
        $this->pass = $post_data['pass'];
        $this->cpass = $post_data['cpass'];
    }
    
    public function validate() {
        // Reset errors
        $this->errors = [];
        
        // Validate name
        if (empty($this->name)) {
            $this->errors[] = 'Name is required!';
        } elseif (strlen($this->name) < 3) {
            $this->errors[] = 'Name must be at least 3 characters!';
        } elseif (strlen($this->name) > 20) {
            $this->errors[] = 'Name cannot exceed 20 characters!';
        } elseif (!preg_match("/^[a-zA-Z ]*$/", $this->name)) {
            $this->errors[] = 'Name can only contain letters and spaces!';
        }
        
        // Validate email
        if (empty($this->email)) {
            $this->errors[] = 'Email is required!';
        } elseif (strlen($this->email) > 50) {
            $this->errors[] = 'Email cannot exceed 50 characters!';
        } elseif (!preg_match('/^\d{0,3}[A-Za-z]+[0-9]*@gmail\.com$/', $this->email)) {
            $this->errors[] = 'Invalid email format! Email must be in the format: 0-3 digits followed by letters and optional numbers, ending with @gmail.com';
        }
        
        // Validate password
        if (empty($this->pass)) {
            $this->errors[] = 'Password is required!';
        } elseif (strlen($this->pass) < 8) {
            $this->errors[] = 'Password must be at least 8 characters!';
        } elseif (strlen($this->pass) > 20) {
            $this->errors[] = 'Password cannot exceed 20 characters!';
        } elseif (!preg_match("/[A-Z]/", $this->pass)) {
            $this->errors[] = 'Password must contain at least one uppercase letter!';
        } elseif (!preg_match("/[a-z]/", $this->pass)) {
            $this->errors[] = 'Password must contain at least one lowercase letter!';
        } elseif (!preg_match("/[0-9]/", $this->pass)) {
            $this->errors[] = 'Password must contain at least one number!';
        } elseif (!preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $this->pass)) {
            $this->errors[] = 'Password must contain at least one special character!';
        } elseif (preg_match("/\s/", $this->pass)) {
            $this->errors[] = 'Password cannot contain spaces!';
        }
        
        // Validate confirm password
        if (empty($this->cpass)) {
            $this->errors[] = 'Confirm password is required!';
        } elseif ($this->pass !== $this->cpass) {
            $this->errors[] = 'Passwords do not match!';
        }
        
        return empty($this->errors);
    }
    
    public function emailExists() {
        $select_user = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $select_user->execute([$this->email]);
        return $select_user->rowCount() > 0;
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
    
    public function register() {
        if ($this->emailExists()) {
            $this->errors[] = 'Email already exists!';
            return false;
        }
        
        $hashed_password = $this->custom_hash($this->pass);
        
        $insert_user = $this->conn->prepare("INSERT INTO users(name, email, password) VALUES(?,?,?)");
        $success = $insert_user->execute([$this->name, $this->email, $hashed_password]);
        
        if (!$success) {
            $this->errors[] = 'Registration failed! Please try again.';
            return false;
        }
        
        return true;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getName() {
        return $this->name;
    }
}

// Process form submission
if(isset($_POST['submit'])){
    $userRegistration = new UserRegistration($conn);
    $userRegistration->setData($_POST);
    
    if ($userRegistration->validate()) {
        if ($userRegistration->register()) {
            $success_message = 'Registered successfully! Redirecting to login page...';
            header("refresh:3;url=user_login.php");
        } else {
            $message = $userRegistration->getErrors();
        }
    } else {
        $message = $userRegistration->getErrors();
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
      animation: typing 2s steps(20), blink 0.5s step-end infinite alternate;
      white-space: nowrap;
      overflow: hidden;
      border-right: 3px solid;
      width: 20ch;
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
      margin-bottom: 20px;
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

   .form-group:nth-child(3) {
      animation-delay: 0.6s;
   }

   .form-group:nth-child(4) {
      animation-delay: 0.8s;
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

   .message {
      padding: 0.8rem 1rem;
      margin-bottom: 1.5rem;
      border-radius: 8px;
      text-align: center;
      font-size: 0.9rem;
      animation: fadeIn 0.3s ease-out;
      position: relative;
      z-index: 1;
   }

   .message.error {
      background-color: rgba(239, 35, 60, 0.1);
      border: 1px solid rgba(239, 35, 60, 0.2);
      color: var(--error-color);
      animation: shake 0.5s ease-in-out, fadeIn 0.3s ease-out;
   }

   .message.success {
      background-color: rgba(76, 201, 240, 0.1);
      border: 1px solid rgba(76, 201, 240, 0.2);
      color: var(--success-color);
   }

   @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
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

   .rotating-store {
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

   .error {
      color: var(--error-color);
      font-size: 14px;
      margin-top: 5px;
      display: block;
      animation: fadeIn 0.3s ease-out;
   }

   .password-requirements {
      font-size: 12px;
      color: var(--text-color);
      opacity: 0.7;
      margin-top: 5px;
   }

   .email-requirements {
      font-size: 12px;
      color: var(--text-color);
      opacity: 0.7;
      margin-top: 5px;
   }

   .input-error {
      border-color: var(--error-color) !important;
   }

   .password-strength-meter {
      height: 5px;
      margin-top: 5px;
      border-radius: 3px;
      background: var(--border-color);
      overflow: hidden;
   }

   .password-strength-meter-fill {
      height: 100%;
      width: 0;
      transition: width 0.3s ease;
   }

   .valid {
      color: var(--success-color);
   }

   .invalid {
      color: var(--error-color);
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
            <i class="fas fa-store rotating-store"></i>
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
                echo '<div class="message error">'.$msg.'</div>';
            }
        }
        if(isset($success_message)){
            echo '<div class="message success">'.$success_message.'</div>';
        }
        ?>
        
        <form action="" method="post" id="registrationForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" required placeholder="Enter your full name" maxlength="20" class="form-control" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                </div>
                <div id="nameError" class="error"></div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required placeholder="Enter your email" maxlength="50" class="form-control" oninput="validateEmail(this.value)" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                <div class="email-requirements">
                    Format: 0-3 digits, letters, optional numbers, ending with @gmail.com
                </div>
                <div id="emailError" class="error"></div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon password-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="pass" required placeholder="Enter your password" minlength="8" maxlength="20" class="form-control" oninput="validatePassword(this.value)">
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength-meter">
                    <div class="password-strength-meter-fill" id="passwordStrengthMeter"></div>
                </div>
                <div class="password-requirements">
                    Must contain: 8+ characters, uppercase, lowercase, number, special character
                </div>
                <div id="passwordError" class="error"></div>
            </div>
            
            <div class="form-group">
                <label for="cpassword">Confirm Password</label>
                <div class="input-with-icon password-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="cpassword" name="cpass" required placeholder="Confirm your password" minlength="8" maxlength="20" class="form-control" oninput="validateConfirmPassword(this.value)">
                    <button type="button" class="password-toggle" id="toggleCPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="cpasswordError" class="error"></div>
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

   // Create confetti animation on successful registration
   <?php if(isset($success_message)): ?>
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
   <?php endif; ?>

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

   // Name validation function
   function validateName(name) {
      const errorElement = document.getElementById('nameError');
      const nameInput = document.getElementById('name');
      
      // Clear previous error
      errorElement.textContent = '';
      nameInput.classList.remove('input-error');
      
      // Check if empty
      if (!name) {
         errorElement.textContent = 'Name is required!';
         nameInput.classList.add('input-error');
         return false;
      }
      
      // Check length
      if (name.length < 3) {
         errorElement.textContent = 'Name must be at least 3 characters!';
         nameInput.classList.add('input-error');
         return false;
      }
      
      if (name.length > 20) {
         errorElement.textContent = 'Name cannot exceed 20 characters!';
         nameInput.classList.add('input-error');
         return false;
      }
      
      // Check pattern (only letters and spaces)
      if (!/^[a-zA-Z ]*$/.test(name)) {
         errorElement.textContent = 'Name can only contain letters and spaces!';
         nameInput.classList.add('input-error');
         return false;
      }
      
      return true;
   }

   // Email validation function
   function validateEmail(email) {
      const errorElement = document.getElementById('emailError');
      const emailInput = document.getElementById('email');
      
      // Clear previous error
      errorElement.textContent = '';
      emailInput.classList.remove('input-error');
      
      // Check if empty
      if (!email) {
         errorElement.textContent = 'Email is required!';
         emailInput.classList.add('input-error');
         return false;
      }
      
      // Check length
      if (email.length > 50) {
         errorElement.textContent = 'Email cannot exceed 50 characters!';
         emailInput.classList.add('input-error');
         return false;
      }
      
      // Check specific pattern: 0-3 digits, letters, optional numbers, @gmail.com
      const emailRegex = /^\d{0,3}[A-Za-z]+[0-9]*@gmail\.com$/;
      if (!emailRegex.test(email)) {
         errorElement.textContent = 'Invalid email format! Email must be in the format: 0-3 digits followed by letters and optional numbers, ending with @gmail.com';
         emailInput.classList.add('input-error');
         return false;
      }
      
      return true;
   }

   // Password validation functions
   function validatePassword(password) {
      const errorElement = document.getElementById('passwordError');
      const passwordInput = document.getElementById('password');
      const strengthMeter = document.getElementById('passwordStrengthMeter');
      
      // Clear previous error
      errorElement.textContent = '';
      passwordInput.classList.remove('input-error');
      
      // Check if empty
      if (!password) {
         errorElement.textContent = 'Password is required!';
         passwordInput.classList.add('input-error');
         strengthMeter.style.width = '0%';
         strengthMeter.style.backgroundColor = 'var(--error-color)';
         return false;
      }
      
      // Check length
      if (password.length < 8) {
         errorElement.textContent = 'Password must be at least 8 characters!';
         passwordInput.classList.add('input-error');
         strengthMeter.style.width = '20%';
         strengthMeter.style.backgroundColor = 'var(--error-color)';
         return false;
      }
      
      if (password.length > 20) {
         errorElement.textContent = 'Password cannot exceed 20 characters!';
         passwordInput.classList.add('input-error');
         strengthMeter.style.width = '20%';
         strengthMeter.style.backgroundColor = 'var(--error-color)';
         return false;
      }
      
      // Check for uppercase
      if (!/[A-Z]/.test(password)) {
         errorElement.textContent = 'Password must contain at least one uppercase letter!';
         passwordInput.classList.add('input-error');
         strengthMeter.style.width = '40%';
         strengthMeter.style.backgroundColor = '#fd7e14';
         return false;
      }
      
      // Check for lowercase
      if (!/[a-z]/.test(password)) {
         errorElement.textContent = 'Password must contain at least one lowercase letter!';
         passwordInput.classList.add('input-error');
         strengthMeter.style.width = '40%';
         strengthMeter.style.backgroundColor = '#fd7e14';
         return false;
      }
      
      // Check for number
      if (!/[0-9]/.test(password)) {
         errorElement.textContent = 'Password must contain at least one number!';
         passwordInput.classList.add('input-error');
         strengthMeter.style.width = '60%';
         strengthMeter.style.backgroundColor = '#ffc107';
         return false;
      }
      
      // Check for special character
      if (!/[!@#$%^&*()\-_=+{};:,<.>]/.test(password)) {
         errorElement.textContent = 'Password must contain at least one special character!';
         passwordInput.classList.add('input-error');
         strengthMeter.style.width = '60%';
         strengthMeter.style.backgroundColor = '#ffc107';
         return false;
      }
      
      // Check for spaces
      if (/\s/.test(password)) {
         errorElement.textContent = 'Password cannot contain spaces!';
         passwordInput.classList.add('input-error');
         strengthMeter.style.width = '20%';
         strengthMeter.style.backgroundColor = 'var(--error-color)';
         return false;
      }
      
      // If all validations pass
      strengthMeter.style.width = '100%';
      strengthMeter.style.backgroundColor = 'var(--success-color)';
      return true;
   }

   function validateConfirmPassword(cpassword) {
      const errorElement = document.getElementById('cpasswordError');
      const cpasswordInput = document.getElementById('cpassword');
      const password = document.getElementById('password').value;
      
      // Clear previous error
      errorElement.textContent = '';
      cpasswordInput.classList.remove('input-error');
      
      // Check if empty
      if (!cpassword) {
         errorElement.textContent = 'Please confirm your password!';
         cpasswordInput.classList.add('input-error');
         return false;
      }
      
      // Check if passwords match
      if (password !== cpassword) {
         errorElement.textContent = 'Passwords do not match!';
         cpasswordInput.classList.add('input-error');
         return false;
      }
      
      return true;
   }

   // Form validation on submit
   document.getElementById('registrationForm').addEventListener('submit', function(e) {
      let isValid = true;
      
      // Clear previous errors
      document.querySelectorAll('.error').forEach(el => el.textContent = '');
      document.querySelectorAll('.form-control').forEach(el => el.classList.remove('input-error'));
      
      // Validate name
      const name = document.getElementById('name').value.trim();
      if (!validateName(name)) {
         isValid = false;
      }
      
      // Validate email
      const email = document.getElementById('email').value.trim();
      if (!validateEmail(email)) {
         isValid = false;
      }
      
      // Validate password
      const password = document.getElementById('password').value;
      if (!validatePassword(password)) {
         isValid = false;
      }
      
      // Validate confirm password
      const cpassword = document.getElementById('cpassword').value;
      if (!validateConfirmPassword(cpassword)) {
         isValid = false;
      }
      
      if (!isValid) {
         e.preventDefault();
      }
   });

   // Password visibility toggle
   document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordInput = document.getElementById('password');
      const icon = this.querySelector('i');
      
      if (passwordInput.type === 'password') {
         passwordInput.type = 'text';
         icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
         passwordInput.type = 'password';
         icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
   });

   document.getElementById('toggleCPassword').addEventListener('click', function() {
      const cpasswordInput = document.getElementById('cpassword');
      const icon = this.querySelector('i');
      
      if (cpasswordInput.type === 'password') {
         cpasswordInput.type = 'text';
         icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
         cpasswordInput.type = 'password';
         icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
   });

   // Validate fields on page load if there are values
   document.addEventListener('DOMContentLoaded', function() {
      const name = document.getElementById('name').value.trim();
      if (name) {
         validateName(name);
      }
      
      const email = document.getElementById('email').value;
      if (email) {
         validateEmail(email);
      }
      
      const password = document.getElementById('password').value;
      if (password) {
         validatePassword(password);
      }
      
      const cpassword = document.getElementById('cpassword').value;
      if (cpassword) {
         validateConfirmPassword(cpassword);
      }
   });
</script>
</body>
</html>