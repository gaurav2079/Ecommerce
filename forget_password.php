<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include Composer autoload
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection class
class Database {
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $dbname = 'ns';
    public $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}

// OTP Generator class
class OTPGenerator {
    public static function generate($length = 6) {
        $otp = '';
        $characters = '0123456789';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[random_int(0, $max)];
        }
        
        return $otp;
    }
}

// Password Reset Handler class
class PasswordReset {
    private $db;
    private $email;
    private $message = [];
    
    public function __construct($db, $email) {
        $this->db = $db;
        $this->email = filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    public function processRequest() {
        if ($this->emailExists()) {
            $otp = OTPGenerator::generate();
            $expiry_time = time() + 30; // 30 seconds expiry
            
            if ($this->storeOTP($otp, $expiry_time)) {
                if ($this->sendEmail($otp)) {
                    header("Location: verify_otp.php?email=" . urlencode($this->email));
                    exit();
                } else {
                    $this->message[] = "❌ Error sending email. Please try again later.";
                }
            } else {
                $this->message[] = "❌ Error generating OTP. Please try again.";
            }
        } else {
            $this->message[] = "❌ Email not found in our system.";
        }
        
        return $this->message;
    }
    
    private function emailExists() {
        $check_email = $this->db->conn->prepare("SELECT * FROM `users` WHERE email = ?");
        $check_email->execute([$this->email]);
        return $check_email->rowCount() > 0;
    }
    
    private function storeOTP($otp, $expiry_time) {
        $update_otp = $this->db->conn->prepare("UPDATE `users` SET otp = ?, otp_expiry = ? WHERE email = ?");
        return $update_otp->execute([$otp, $expiry_time, $this->email]);
    }
    
    private function sendEmail($otp) {
        $mail = new PHPMailer(true);
        
        try {
            // SMTP config
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'kandelgaurav04@gmail.com';
            $mail->Password   = 'xmnfszxvelzuettu';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Sender & recipient
            $mail->setFrom('Nepal~Store@gmail.com', 'Nepal~Store Support');
            $mail->addAddress($this->email);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body    = "Hello,<br><br>Your OTP for password reset is: <strong>$otp</strong><br><br>This OTP will expire in 30 sec.<br><br>Regards,<br>Nepal~Store Team";

            return $mail->send();
        } catch (Exception $e) {
            $this->message[] = "❌ Mailer Error: " . $mail->ErrorInfo;
            return false;
        }
    }
}

// Main execution
$message = [];

if (isset($_POST['submit'])) {
    $db = new Database();
    $passwordReset = new PasswordReset($db, $_POST['email']);
    $message = $passwordReset->processRequest();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Nepal~Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --danger: #dc2626;
            --success: #16a34a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        }
        
        .container {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 480px;
            padding: 40px;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo {
            margin-bottom: 24px;
        }
        
        .logo img {
            height: 60px;
            width: auto;
            object-fit: contain;
        }
        
        h1 {
            color: var(--dark);
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: var(--gray);
            font-size: 15px;
            margin-bottom: 32px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 24px;
            text-align: left;
        }
        
        label {
            display: block;
            color: var(--dark);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #f8fafc;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: white;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            margin-top: 8px;
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .back-to-login {
            margin-top: 24px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .back-to-login a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
            color: var(--primary-dark);
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-danger {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--danger);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
        
        .alert-success {
            background-color: rgba(22, 163, 74, 0.1);
            color: var(--success);
            border: 1px solid rgba(22, 163, 74, 0.2);
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .illustration {
            margin: 20px 0 30px;
        }
        
        .illustration img {
            width: 180px;
            height: auto;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="images/logo.jpg" alt="NepalStore Logo">
        </div>
        
        <div class="illustration">
            <img src="https://cdn-icons-png.flaticon.com/512/6195/6195699.png" alt="Password Reset Illustration">
        </div>
        
        <h1>Forgot your password?</h1>
        <p class="subtitle">Enter your registered email address and we'll send you a verification code to reset your password</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $message[0]; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <button type="submit" name="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Send Verification Code
            </button>
        </form>
        
        <p class="back-to-login">
            Remember your password? <a href="user_login.php">Sign in here</a>
        </p>
    </div>
</body>
</html>