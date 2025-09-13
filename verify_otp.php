<?php
include 'components/connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include Composer autoload
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

// Email Sender class
class EmailSender {
    public static function sendOTP($email, $otp) {
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
            $mail->addAddress($email);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body    = "Hello,<br><br>Your OTP for password reset is: <strong>$otp</strong><br><br>This OTP will expire in 5 minutes.<br><br>Regards,<br>Nepal~Store Team";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$email = $_GET['email'] ?? '';
$message = [];

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['otp'])) {
        // OTP verification
        $entered_otp = $_POST['otp'];

        $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (time() <= $user['otp_expiry']) {
                if ($entered_otp == $user['otp']) {
                    // OTP is correct and valid
                    header("Location: reset_password_form.php?email=" . urlencode($email));
                    exit();
                } else {
                    $message[] = "Incorrect OTP. Please try again.";
                }
            } else {
                $message[] = "OTP has expired. Please request a new one.";
            }
        } else {
            $message[] = "User not found.";
        }
    } elseif (isset($_POST['resend_otp'])) {
        // Resend OTP
        $otp = OTPGenerator::generate();
        $expiry_time = time() + 300; // 5 minutes expiry

        $update_otp = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
        if ($update_otp->execute([$otp, $expiry_time, $email])) {
            if (EmailSender::sendOTP($email, $otp)) {
                $message[] = "New OTP has been sent to your email.";
                // Set session variable to track when OTP was last sent
                $_SESSION['otp_sent_time'] = time();
            } else {
                $message[] = "Failed to send OTP. Please try again.";
            }
        } else {
            $message[] = "Error generating OTP. Please try again.";
        }
    }
}

// Check if OTP was recently sent to determine countdown time
$otp_sent_time = $_SESSION['otp_sent_time'] ?? time();
$elapsed_time = time() - $otp_sent_time;
$countdown_time = max(0, 15 - $elapsed_time); // 15 seconds countdown
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Nepal~Store</title>
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
        
        h2 {
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
        
        .otp-form {
            margin-top: 20px;
        }
        
        .otp-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 8px;
            margin-bottom: 20px;
            transition: all 0.3s;
            background-color: #f8fafc;
        }
        
        .otp-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: white;
        }
        
        .btn-verify {
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
        }
        
        .btn-verify:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
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
        
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(245, 158, 11, 0.2);
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
        
        .resend-section {
            margin-top: 20px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .countdown {
            font-weight: 600;
            color: var(--primary);
        }
        
        .resend-link {
            display: none;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }
        
        .resend-link:hover {
            text-decoration: underline;
        }
        
        .resend-form {
            display: inline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="images/logo.jpg" alt="Nepal~Store Logo">
        </div>
        
        <h2>Verify Your Identity</h2>
        <p class="subtitle">We've sent a 6-digit verification code to your email<br><?php echo htmlspecialchars($email); ?></p>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php 
                if (strpos($message[0], 'expired') !== false) echo 'alert-warning';
                elseif (strpos($message[0], 'New OTP') !== false) echo 'alert-success';
                else echo 'alert-danger';
            ?>">
                <i class="fas <?php 
                    if (strpos($message[0], 'expired') !== false) echo 'fa-exclamation-triangle';
                    elseif (strpos($message[0], 'New OTP') !== false) echo 'fa-check-circle';
                    else echo 'fa-exclamation-circle';
                ?>"></i>
                <?php echo $message[0]; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="otp-form">
            <input type="text" name="otp" placeholder="Enter 6-digit code" required class="otp-input" maxlength="6" pattern="\d{6}" title="Please enter exactly 6 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)">
            
            <button type="submit" class="btn-verify">
                <i class="fas fa-check-circle"></i> Verify OTP
            </button>
        </form>
        
        <div class="resend-section">
            <span id="countdown-text" class="countdown">Resend OTP in <span id="countdown"><?php echo $countdown_time; ?></span> seconds</span>
            <form method="POST" class="resend-form" id="resend-form">
                <input type="hidden" name="resend_otp" value="1">
                <a class="resend-link" onclick="document.getElementById('resend-form').submit();">Resend OTP</a>
            </form>
        </div>
    </div>

    <script>
        // Countdown timer
        let countdown = <?php echo $countdown_time; ?>;
        const countdownElement = document.getElementById('countdown');
        const countdownText = document.getElementById('countdown-text');
        const resendLink = document.querySelector('.resend-link');
        
        function updateCountdown() {
            if (countdown > 0) {
                countdown--;
                countdownElement.textContent = countdown;
                setTimeout(updateCountdown, 1000);
            } else {
                countdownText.style.display = 'none';
                resendLink.style.display = 'inline';
            }
        }
        
        // Start countdown if needed
        if (countdown > 0) {
            setTimeout(updateCountdown, 1000);
        } else {
            countdownText.style.display = 'none';
            resendLink.style.display = 'inline';
        }
    </script>
</body>
</html>