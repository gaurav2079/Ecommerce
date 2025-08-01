<?php
include 'components/connect.php';

$email = $_GET['email'] ?? '';
$message = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - GKStore</title>
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
        
        .alert i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .resend-link {
            margin-top: 20px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .resend-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .resend-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="images/logo.jpg" alt="GKStore Logo">
        </div>
        
        <h2>Verify Your Identity</h2>
        <p class="subtitle">We've sent a 6-digit verification code to your email<br><?php echo htmlspecialchars($email); ?></p>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message[0], 'expired') !== false ? 'alert-warning' : 'alert-danger'; ?>">
                <i class="fas <?php echo strpos($message[0], 'expired') !== false ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message[0]; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="otp-form">
            <input type="text" name="otp" placeholder="Enter 6-digit code" required class="otp-input" maxlength="6" pattern="\d{6}" title="Please enter exactly 6 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)">
            
            <button type="submit" class="btn-verify">
                <i class="fas fa-check-circle"></i> Verify OTP
            </button>
        </form>
        
        <p class="resend-link">
            Didn't receive the code? <a href="forgot_password.php">Resend OTP</a>
        </p>
    </div>
</body>
</html>