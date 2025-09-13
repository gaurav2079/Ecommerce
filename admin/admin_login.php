<?php
// Include database connection component
include '../components/connect.php';

session_start();

// Admin class to handle authentication
class AdminAuth {
    private $conn;
    private $name;
    private $pass;
    private $message = array();

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function sanitizeInput($input) {
        return filter_var($input, FILTER_SANITIZE_STRING);
    }

    public function setCredentials($name, $pass) {
        $this->name = $this->sanitizeInput($name);
        $this->pass = sha1($this->sanitizeInput($pass));
    }

    public function authenticate() {
        $select_admin = $this->conn->prepare("SELECT * FROM `admins` WHERE name = ? AND password = ?");
        $select_admin->execute([$this->name, $this->pass]);
        
        if($select_admin->rowCount() > 0){
            $row = $select_admin->fetch(PDO::FETCH_ASSOC);
            $_SESSION['admin_id'] = $row['id'];
            header('location:dashboard.php');
            exit();
        } else {
            $this->message[] = 'incorrect username or password!';
            return false;
        }
    }

    public function getMessages() {
        return $this->message;
    }
}

// Process form submission
if(isset($_POST['submit'])){
    $adminAuth = new AdminAuth($conn);
    $adminAuth->setCredentials($_POST['name'], $_POST['pass']);
    $adminAuth->authenticate();
    $messages = $adminAuth->getMessages();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Login</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <style>
      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      body {
         background-color: #f5f7fa;
         display: flex;
         justify-content: center;
         align-items: center;
         min-height: 100vh;
         background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      }

      .message {
         position: fixed;
         top: 20px;
         right: 20px;
         background-color: #ff6b6b;
         color: white;
         padding: 15px 20px;
         border-radius: 8px;
         display: flex;
         align-items: center;
         gap: 10px;
         box-shadow: 0 4px 12px rgba(0,0,0,0.1);
         z-index: 1000;
         animation: slideIn 0.3s ease-out;
      }

      @keyframes slideIn {
         from { transform: translateX(100%); opacity: 0; }
         to { transform: translateX(0); opacity: 1; }
      }

      .message i {
         cursor: pointer;
      }

      .form-container {
         background-color: white;
         width: 100%;
         max-width: 450px;
         padding: 40px;
         border-radius: 12px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.08);
         text-align: center;
      }

      .form-container h3 {
         color: #2b2d42;
         font-size: 24px;
         margin-bottom: 20px;
         font-weight: 600;
      }

      .form-container p {
         color: #6c757d;
         margin-bottom: 25px;
         font-size: 14px;
      }

      .form-container p span {
         color: #4361ee;
         font-weight: 500;
      }

      .form-container .box {
         width: 100%;
         padding: 14px 20px;
         margin-bottom: 20px;
         border: 1px solid #e0e3e8;
         border-radius: 8px;
         font-size: 15px;
         transition: all 0.3s ease;
      }

      .form-container .box:focus {
         outline: none;
         border-color: #4361ee;
         box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .form-container .btn {
         width: 100%;
         padding: 14px;
         background-color: #4361ee;
         color: white;
         border: none;
         border-radius: 8px;
         font-size: 16px;
         font-weight: 500;
         cursor: pointer;
         transition: all 0.3s ease;
      }

      .form-container .btn:hover {
         background-color: #3a56d4;
         transform: translateY(-2px);
      }

      .login-illustration {
         width: 180px;
         margin-bottom: 30px;
      }

      .input-group {
         position: relative;
         margin-bottom: 20px;
      }

      .input-group i {
         position: absolute;
         left: 15px;
         top: 50%;
         transform: translateY(-50%);
         color: #6c757d;
      }

      .input-group .box {
         padding-left: 45px;
      }

      @media (max-width: 480px) {
         .form-container {
            padding: 30px 20px;
            margin: 0 15px;
         }
      }
   </style>
</head>
<body>

<?php
   if(isset($messages)){
      foreach($messages as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<section class="form-container">
   <svg class="login-illustration" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
      <path fill="#4361ee" d="M45.1,-65.1C58.5,-56.5,69.7,-44.1,75.8,-28.8C81.9,-13.5,82.8,4.7,77.2,20.3C71.6,35.9,59.5,48.9,44.8,58.1C30.1,67.3,12.8,72.7,-3.3,76.6C-19.4,80.5,-38.8,82.9,-52.7,74.5C-66.6,66.1,-75,46.9,-77.4,27.8C-79.8,8.7,-76.2,-10.3,-67.4,-26.3C-58.6,-42.3,-44.6,-55.3,-30.1,-63.5C-15.6,-71.7,-0.5,-75.1,14.1,-71.3C28.7,-67.5,57.4,-56.4,71.8,-40.8C86.2,-25.2,86.3,-5.1,81.7,12.2C77.1,29.5,67.8,44,54.9,55.5C42,67,25.5,75.5,8.3,79.2C-8.9,82.9,-26.9,81.8,-41.7,73.7C-56.5,65.5,-68.1,50.2,-74.3,32.6C-80.5,15,-81.3,-5,-75.1,-21.8C-68.9,-38.6,-55.7,-52.2,-40.5,-60.1C-25.3,-68,-8.1,-70.3,7.8,-69.1C23.7,-68,47.4,-63.5,58.8,-52.8C70.2,-42.1,69.4,-25.2,70.9,-8.9C72.4,7.4,76.2,23.1,70.3,35.1C64.4,47.1,48.8,55.4,33.8,62.2C18.8,69,4.4,74.3,-9.5,74.7C-23.4,75.1,-46.8,70.6,-59.9,59.2C-73,47.8,-75.8,29.5,-76.2,11.4C-76.6,-6.7,-74.6,-24.6,-65.5,-38.5C-56.4,-52.4,-40.2,-62.3,-24.2,-69.6C-8.2,-76.9,7.6,-81.6,21.9,-77.8C36.2,-74,48.9,-61.7,45.1,-65.1Z" transform="translate(100 100)" />
   </svg>

   <form action="" method="post">
      <h3>Admin Login</h3>
      <p>Default username = <span>admin</span> & password = <span>111</span></p>
      
      <div class="input-group">
         <i class="fas fa-user"></i>
         <input type="text" name="name" required placeholder="Enter your username" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      </div>
      
      <div class="input-group">
         <i class="fas fa-lock"></i>
         <input type="password" name="pass" required placeholder="Enter your password" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      </div>
      
      <input type="submit" value="Login Now" class="btn" name="submit">
   </form>
</section>

<script>
   // Simple animation for the login form
   document.addEventListener('DOMContentLoaded', function() {
      const formContainer = document.querySelector('.form-container');
      formContainer.style.opacity = '0';
      formContainer.style.transform = 'translateY(20px)';
      formContainer.style.transition = 'all 0.4s ease-out';
      
      setTimeout(() => {
         formContainer.style.opacity = '1';
         formContainer.style.transform = 'translateY(0)';
      }, 100);
   });
</script>
   
</body>
</html>