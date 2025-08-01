<?php
include 'components/connect.php';

$email = $_GET['email'] ?? '';
$message = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message[] = "Passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $message[] = "Password must be at least 6 characters long";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in the database
        $stmt = $conn->prepare("UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);

        $message[] = "Password updated successfully! Redirecting to login page...";
        $success = true;
        
        // Redirect to login page after 3 seconds
        header("refresh:3;url=user_login.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GKStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #3a86ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--dark);
        }

        .container {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--success));
        }

        .logo {
            margin-bottom: 25px;
        }

        .logo img {
            max-width: 120px;
            height: auto;
            border-radius: 50%;
            border: 3px solid var(--light);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        h2 {
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .subtitle {
            color: var(--gray);
            margin-bottom: 30px;
            font-size: 15px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.2);
            color: #0a9396;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-warning {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning);
            border-left: 4px solid var(--warning);
        }

        .redirect-message {
            margin-top: 20px;
            font-size: 14px;
            color: var(--gray);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        .password-container {
            position: relative;
            margin-bottom: 20px;
        }

        .password-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
            background-color: #f8f9fa;
        }

        .password-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
            background-color: var(--white);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .password-strength {
            margin-top: 8px;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 5px;
        }

        .btn-reset {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .btn-reset:active {
            transform: translateY(0);
        }

        .login-link {
            margin-top: 25px;
            color: var(--gray);
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .login-link a:hover {
            text-decoration: underline;
            color: var(--secondary);
        }

        .password-hints {
            text-align: left;
            margin: 15px 0;
            font-size: 13px;
            color: var(--gray);
        }

        .password-hints ul {
            list-style-type: none;
            padding-left: 5px;
        }

        .password-hints li {
            margin-bottom: 5px;
            position: relative;
            padding-left: 20px;
        }

        .password-hints li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--primary);
        }

        .password-hints li.valid {
            color: var(--success);
        }

        .password-hints li.valid::before {
            content: '✓';
            color: var(--success);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="images/logo.jpg" alt="GKStore Logo">
        </div>
        
        <h2>Create New Password</h2>
        <p class="subtitle">Secure your account with a strong password</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php 
                if (isset($success)) echo 'alert-success';
                elseif (strpos($message[0], 'match') !== false) echo 'alert-danger';
                else echo 'alert-warning';
            ?>">
                <i class="fas <?php 
                    if (isset($success)) echo 'fa-check-circle';
                    elseif (strpos($message[0], 'match') !== false) echo 'fa-times-circle';
                    else echo 'fa-exclamation-triangle';
                ?>"></i>
                <?php echo $message[0]; ?>
            </div>
            
            <?php if (isset($success)): ?>
                <p class="redirect-message">
                    <i class="fas fa-spinner fa-spin"></i> You will be redirected to login page shortly...
                </p>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!isset($success)): ?>
        <form method="POST" id="passwordForm">
            <div class="password-container">
                <input type="password" name="new_password" id="newPassword" placeholder="New Password" required class="password-input" minlength="6" oninput="checkPasswordStrength(this.value)">
                <span class="password-toggle" onclick="togglePassword('newPassword')">
                    <i class="fas fa-eye"></i>
                </span>
                <div class="password-strength">
                    <div class="strength-meter" id="strengthMeter"></div>
                </div>
            </div>
            
            <div class="password-hints">
                <ul>
                    <li id="lengthHint">At least 6 characters</li>
                    <li id="caseHint">Both uppercase and lowercase</li>
                    <li id="numberHint">At least one number</li>
                    <li id="specialHint">At least one special character</li>
                </ul>
            </div>
            
            <div class="password-container">
                <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm Password" required class="password-input" minlength="6">
                <span class="password-toggle" onclick="togglePassword('confirmPassword')">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            
            <button type="submit" class="btn-reset">
                <i class="fas fa-key"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>
        
        <p class="login-link">
            Remember your password? <a href="user_login.php">Log in here</a>
        </p>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const icon = passwordField.nextElementSibling.querySelector('i');
            
            if(passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function checkPasswordStrength(password) {
            const strengthMeter = document.getElementById('strengthMeter');
            let strength = 0;
            
            // Check length
            const lengthHint = document.getElementById('lengthHint');
            if (password.length >= 6) {
                strength += 1;
                lengthHint.classList.add('valid');
            } else {
                lengthHint.classList.remove('valid');
            }
            if (password.length >= 8) strength += 1;
            
            // Check for mixed case
            const caseHint = document.getElementById('caseHint');
            if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) {
                strength += 1;
                caseHint.classList.add('valid');
            } else {
                caseHint.classList.remove('valid');
            }
            
            // Check for numbers
            const numberHint = document.getElementById('numberHint');
            if (password.match(/([0-9])/)) {
                strength += 1;
                numberHint.classList.add('valid');
            } else {
                numberHint.classList.remove('valid');
            }
            
            // Check for special chars
            const specialHint = document.getElementById('specialHint');
            if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) {
                strength += 1;
                specialHint.classList.add('valid');
            } else {
                specialHint.classList.remove('valid');
            }
            
            // Update meter
            const width = strength * 25;
            strengthMeter.style.width = width + '%';
            
            // Update color
            if (strength <= 1) {
                strengthMeter.style.backgroundColor = 'var(--danger)';
            } else if (strength <= 3) {
                strengthMeter.style.backgroundColor = 'var(--warning)';
            } else {
                strengthMeter.style.backgroundColor = 'var(--success)';
            }
        }
        
        // Validate password match on form submit
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match!';
                
                const container = document.querySelector('.container');
                const form = document.getElementById('passwordForm');
                container.insertBefore(alertDiv, form);
                
                // Remove alert after 3 seconds
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>