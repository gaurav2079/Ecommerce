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
        } elseif (!preg_match("/^[a-zA-Z0-9_ ]+$/", $this->name)) {
            $this->errors[] = 'Name can only contain letters, numbers, spaces and underscores!';
        }
        
        // Validate email
        if (empty($this->email)) {
            $this->errors[] = 'Email is required!';
        } elseif (!$this->isValidEmail($this->email)) {
            $this->errors[] = 'Invalid email format!';
        } elseif (strlen($this->email) > 50) {
            $this->errors[] = 'Email cannot exceed 50 characters!';
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
    
    // Enhanced email validation
    private function isValidEmail($email) {
        // Basic format check
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Check for valid local part (before @)
        $parts = explode('@', $email);
        $local = $parts[0];
        $domain = $parts[1];
        
        // Local part validation
        if (strlen($local) > 64) {
            return false;
        }
        
        // Check if local part starts or ends with a dot
        if (substr($local, 0, 1) == '.' || substr($local, -1) == '.') {
            return false;
        }
        
        // Check for consecutive dots
        if (strpos($local, '..') !== false) {
            return false;
        }
        
        // Check if local part contains only valid characters
        if (!preg_match('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+$/', $local)) {
            return false;
        }
        
        // Domain validation
        if (!checkdnsrr($domain, 'MX')) {
            return false;
        }
        
        return true;
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

   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/register.css">
   
   <style>
        .error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .password-requirements {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .email-requirements {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .input-error {
            border-color: #dc3545 !important;
        }
        .password-strength-meter {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            background: #e9ecef;
            overflow: hidden;
        }
        .password-strength-meter-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }
        .valid {
            color: #28a745;
        }
        .invalid {
            color: #dc3545;
        }
   </style>
</head>
<body>
   
<header class="header">
    <a href="home.php" class="logo">
        <div class="cube-container">
            <i class="fas fas fa-store rotating-store"></i>
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
                <label for="name">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" required placeholder="Enter your username (3-20 characters)" maxlength="20" class="form-control" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
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
                    Must be a valid email format with proper domain
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
    
    // Basic email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorElement.textContent = 'Please enter a valid email address!';
        emailInput.classList.add('input-error');
        return false;
    }
    
    // Check for valid local part
    const parts = email.split('@');
    const local = parts[0];
    const domain = parts[1];
    
    // Local part validation
    if (local.length > 64) {
        errorElement.textContent = 'Email username too long!';
        emailInput.classList.add('input-error');
        return false;
    }
    
    if (local.startsWith('.') || local.endsWith('.')) {
        errorElement.textContent = 'Email cannot start or end with a dot!';
        emailInput.classList.add('input-error');
        return false;
    }
    
    if (local.includes('..')) {
        errorElement.textContent = 'Email cannot contain consecutive dots!';
        emailInput.classList.add('input-error');
        return false;
    }
    
    // Check for valid characters in local part
    const localRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+$/;
    if (!localRegex.test(local)) {
        errorElement.textContent = 'Email contains invalid characters!';
        emailInput.classList.add('input-error');
        return false;
    }
    
    // Domain validation (basic check)
    const domainRegex = /^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!domainRegex.test(domain)) {
        errorElement.textContent = 'Email domain is invalid!';
        emailInput.classList.add('input-error');
        return false;
    }
    
    // If all validations pass
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
        strengthMeter.style.backgroundColor = '#dc3545';
        return false;
    }
    
    // Check length
    if (password.length < 8) {
        errorElement.textContent = 'Password must be at least 8 characters!';
        passwordInput.classList.add('input-error');
        strengthMeter.style.width = '20%';
        strengthMeter.style.backgroundColor = '#dc3545';
        return false;
    }
    
    if (password.length > 20) {
        errorElement.textContent = 'Password cannot exceed 20 characters!';
        passwordInput.classList.add('input-error');
        strengthMeter.style.width = '20%';
        strengthMeter.style.backgroundColor = '#dc3545';
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
        strengthMeter.style.backgroundColor = '#dc3545';
        return false;
    }
    
    // If all validations pass
    strengthMeter.style.width = '100%';
    strengthMeter.style.backgroundColor = '#28a745';
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
    if (!name) {
        document.getElementById('nameError').textContent = 'Name is required!';
        document.getElementById('name').classList.add('input-error');
        isValid = false;
    } else if (name.length < 3) {
        document.getElementById('nameError').textContent = 'Name must be at least 3 characters!';
        document.getElementById('name').classList.add('input-error');
        isValid = false;
    } else if (name.length > 20) {
        document.getElementById('nameError').textContent = 'Name cannot exceed 20 characters!';
        document.getElementById('name').classList.add('input-error');
        isValid = false;
    } else if (!/^[a-zA-Z0-9_ ]+$/.test(name)) {
        document.getElementById('nameError').textContent = 'Name can only contain letters, numbers, spaces and underscores!';
        document.getElementById('name').classList.add('input-error');
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