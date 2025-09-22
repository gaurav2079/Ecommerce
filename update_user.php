<?php 
include 'components/connect.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path as needed

session_start();

// Function to ensure required columns exist in users table
function ensureUserColumnsExist($conn) {
    $columns_to_check = [
        'last_login' => "ALTER TABLE `users` ADD `last_login` DATETIME NULL AFTER `password`",
        'last_login_ip' => "ALTER TABLE `users` ADD `last_login_ip` VARCHAR(45) NULL AFTER `last_login`",
        'created_at' => "ALTER TABLE `users` ADD `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `last_login_ip`",
        'updated_at' => "ALTER TABLE `users` ADD `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`"
    ];
    
    foreach ($columns_to_check as $column => $sql) {
        try {
            $stmt = $conn->prepare("SHOW COLUMNS FROM `users` LIKE ?");
            $stmt->execute([$column]);
            if($stmt->rowCount() == 0) {
                $conn->exec($sql);
                error_log("Added column $column to users table");
            }
        } catch (PDOException $e) {
            error_log("Error checking/adding column $column: " . $e->getMessage());
        }
    }
}

// Call this function to ensure columns exist
ensureUserColumnsExist($conn);

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
   
   // Update last login time and IP when user accesses the page
   // This will run on every page load, but we'll check if it's a new login
   $current_time = date('Y-m-d H:i:s');
   $ip_address = $_SERVER['REMOTE_ADDR'];
   $user_agent = $_SERVER['HTTP_USER_AGENT'];
   
   // Check if we need to update the login time (first visit in this session)
   if (!isset($_SESSION['login_updated'])) {
      $update_login = $conn->prepare("UPDATE `users` SET last_login = ?, last_login_ip = ?, updated_at = ? WHERE id = ?");
      $update_login->execute([$current_time, $ip_address, $current_time, $user_id]);
      
      // Record login history (we'll handle this in the User class)
      $_SESSION['login_updated'] = true;
   }
   
}else{
   $user_id = '';
   header('location:login.php');
   exit();
}

// LoginHistory class to handle login history operations
class LoginHistory {
    private $conn;
    private $user_id;
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->createTableIfNotExists();
    }
    
    private function createTableIfNotExists() {
        // Check if table exists
        $table_exists = $this->conn->query("SHOW TABLES LIKE 'login_history'")->rowCount() > 0;
        
        if (!$table_exists) {
            // Create the login_history table
            $sql = "CREATE TABLE `login_history` (
                `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT(11) NOT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `user_agent` TEXT NOT NULL,
                `success` TINYINT(1) NOT NULL DEFAULT 1,
                `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `user_id_index` (`user_id`),
                INDEX `login_time_index` (`login_time`)
            )";
            
            try {
                $this->conn->exec($sql);
                error_log("Login history table created successfully");
            } catch (PDOException $e) {
                error_log("Error creating login_history table: " . $e->getMessage());
            }
        }
    }
    
    public function recordLoginHistory($ip_address, $user_agent, $success = true) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO `login_history` (`user_id`, `ip_address`, `user_agent`, `success`) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$this->user_id, $ip_address, $user_agent, $success]);
        } catch (PDOException $e) {
            error_log("Error recording login: " . $e->getMessage());
            return false;
        }
    }
    
    public function getLoginHistory($limit = 10) {
        try {
            $limit = (int)$limit;
            $stmt = $this->conn->prepare("
                SELECT * FROM `login_history` 
                WHERE `user_id` = ? 
                ORDER BY `login_time` DESC 
                LIMIT $limit
            ");
            $stmt->execute([$this->user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching login history: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalLogins() {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total FROM `login_history` 
                WHERE `user_id` = ? AND `success` = 1
            ");
            $stmt->execute([$this->user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error counting logins: " . $e->getMessage());
            return 0;
        }
    }
}

// ActivityLog class for tracking profile updates
class ActivityLog {
    private $conn;
    private $user_id;
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->createTableIfNotExists();
    }
    
    private function createTableIfNotExists() {
        // Check if table exists
        $table_exists = $this->conn->query("SHOW TABLES LIKE 'activity_log'")->rowCount() > 0;
        
        if (!$table_exists) {
            // Create the activity_log table
            $sql = "CREATE TABLE `activity_log` (
                `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT(11) NOT NULL,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_details` TEXT,
                `ip_address` VARCHAR(45),
                `activity_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `user_id_index` (`user_id`),
                INDEX `activity_time_index` (`activity_time`),
                INDEX `activity_type_index` (`activity_type`)
            )";
            
            try {
                $this->conn->exec($sql);
                error_log("Activity log table created successfully");
            } catch (PDOException $e) {
                error_log("Error creating activity_log table: " . $e->getMessage());
            }
        }
    }
    
    public function recordActivity($activity_type, $activity_details = null) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $stmt = $this->conn->prepare("
                INSERT INTO `activity_log` (`user_id`, `activity_type`, `activity_details`, `ip_address`) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$this->user_id, $activity_type, $activity_details, $ip_address]);
        } catch (PDOException $e) {
            error_log("Error recording activity: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentActivities($limit = 10) {
        try {
            $limit = (int)$limit;
            $stmt = $this->conn->prepare("
                SELECT * FROM `activity_log` 
                WHERE `user_id` = ? 
                ORDER BY `activity_time` DESC 
                LIMIT $limit
            ");
            $stmt->execute([$this->user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching activities: " . $e->getMessage());
            return [];
        }
    }
}

// User class to handle user-related operations
class User {
    private $conn;
    private $user_id;
    private $profile_data;
    private $column_existence;
    private $pepper = "N3p@l4598!";
    private $loginHistory;
    private $activityLog;
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->loginHistory = new LoginHistory($conn, $user_id);
        $this->activityLog = new ActivityLog($conn, $user_id);
        $this->loadProfile();
        $this->checkColumns();
    }
    
    private function loadProfile() {
        $select_profile = $this->conn->prepare("SELECT * FROM `users` WHERE id = ?");
        $select_profile->execute([$this->user_id]);
        $this->profile_data = $select_profile->fetch(PDO::FETCH_ASSOC);
    }
    
    private function checkColumns() {
        $columns_to_check = ['gender', 'last_login', 'last_login_ip', 'created_at', 'updated_at'];
        $this->column_existence = [];
        
        foreach ($columns_to_check as $column) {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM `users` LIKE ?");
            $stmt->execute([$column]);
            $this->column_existence[$column] = ($stmt->rowCount() > 0);
        }
    }
    
    public function getProfileData() {
        return $this->profile_data;
    }
    
    public function columnExists($column) {
        return isset($this->column_existence[$column]) ? $this->column_existence[$column] : false;
    }
    
    public function getLoginHistory() {
        return $this->loginHistory->getLoginHistory();
    }
    
    public function getTotalLogins() {
        return $this->loginHistory->getTotalLogins();
    }
    
    public function getRecentActivities() {
        return $this->activityLog->getRecentActivities();
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
    
    public function updateProfile($data, $files) {
        // Basic information with validation
        $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        $address = filter_var($data['address'], FILTER_SANITIZE_STRING);
        $birth_date = filter_var($data['birth_date'], FILTER_SANITIZE_STRING);
        
        // Name validation - only letters and spaces allowed
        if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
            return 'Name can only contain letters and spaces!';
        }
        
        // Email validation with specific format
        if (!preg_match('/^\d{0,3}[A-Za-z]+[0-9]*@gmail\.com$/', $email)) {
            return 'Invalid email format! Email must be in the format: 0-3 digits followed by letters and optional numbers, ending with @gmail.com';
        }
        
        // Phone validation - only numbers, and 8-10 digits starting with 97, 98, or 96
        $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-numeric characters
        if (!empty($phone) && !preg_match("/^(97|98|96)[0-9]{8}$/", $phone)) {
            return 'Phone number must be 10 digits starting with 97, 98, or 96!';
        }
        
        if($this->columnExists('gender')){
            $gender = filter_var($data['gender'], FILTER_SANITIZE_STRING);
        }

        // Handle profile picture upload
        $profile_pic = $this->profile_data['profile_pic'];
        
        if(!empty($files['profile_pic']['name'])){
            $upload_result = $this->handleProfilePictureUpload($files['profile_pic'], $profile_pic);
            if(is_array($upload_result) && isset($upload_result['error'])) {
                return $upload_result['error'];
            } else {
                $profile_pic = $upload_result;
            }
        }

        // Build update query dynamically based on available columns
        $update_fields = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'profile_pic' => $profile_pic,
            'birth_date' => $birth_date
        ];
        
        if($this->columnExists('gender')){
            $update_fields['gender'] = $gender;
        }
        
        // Add updated_at timestamp if column exists
        $current_time = date('Y-m-d H:i:s');
        if($this->columnExists('updated_at')) {
            $update_fields['updated_at'] = $current_time;
        }
        
        $set_clause = implode(', ', array_map(function($field){
            return "`$field` = ?";
        }, array_keys($update_fields)));
        
        $update_values = array_values($update_fields);
        $update_values[] = $this->user_id;
        
        try {
            $update_profile = $this->conn->prepare("UPDATE `users` SET $set_clause WHERE id = ?");
            $update_profile->execute($update_values);
            
            // Record profile update in activity log
            $this->activityLog->recordActivity('profile_update', 'User updated their profile information');
            
            return true;
        } catch (PDOException $e) {
            return 'Database error: ' . $e->getMessage();
        }
    }
    
    private function handleProfilePictureUpload($file, $current_pic) {
        $pic_name = $file['name'];
        $pic_tmp_name = $file['tmp_name'];
        $pic_size = $file['size'];
        $pic_error = $file['error'];
        $pic_type = $file['type'];
        
        $pic_ext = explode('.', $pic_name);
        $pic_actual_ext = strtolower(end($pic_ext));
        
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if(in_array($pic_actual_ext, $allowed)){
            if($pic_error === 0){
                if($pic_size < 5000000){
                    $pic_new_name = uniqid('', true).".".$pic_actual_ext;
                    $pic_destination = 'uploaded_img/'.$pic_new_name;
                    
                    if(move_uploaded_file($pic_tmp_name, $pic_destination)){
                        if($current_pic != '' && file_exists('uploaded_img/'.$current_pic)){
                            unlink('uploaded_img/'.$current_pic);
                        }
                        return $pic_new_name;
                    } else {
                        return ['error' => 'There was an error uploading your picture!'];
                    }
                } else {
                    return ['error' => 'Your picture is too big! Max 5MB allowed.'];
                }
            } else {
                return ['error' => 'There was an error uploading your picture!'];
            }
        } else {
            return ['error' => 'You cannot upload files of this type! Allowed: JPG, JPEG, PNG, GIF, WEBP'];
        }
    }
    
    public function updatePassword($old_pass, $new_pass, $cpass, $email, $name) {
        $prev_pass = $this->profile_data['password'];
        
        if(!empty($old_pass) || !empty($new_pass) || !empty($cpass)){
            if(empty($old_pass)){
                return 'Please enter your old password!';
            } elseif($this->custom_hash($old_pass) !== $prev_pass){
                return 'Old password not matched!';
            } elseif($new_pass != $cpass){
                return 'Confirm password not matched!';
            } elseif(strlen($new_pass) < 8){
                return 'Password must be at least 8 characters long!';
            } elseif(!preg_match('/[A-Z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[!@#$%^&*]/', $new_pass)){
                return 'Password must contain at least one uppercase letter, one number and one special character!';
            } else {
                $hashed_password = $this->custom_hash($new_pass);
                try {
                    $update_admin_pass = $this->conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
                    $update_admin_pass->execute([$hashed_password, $this->user_id]);
                    
                    // Update the updated_at timestamp if column exists
                    if($this->columnExists('updated_at')) {
                        $current_time = date('Y-m-d H:i:s');
                        $update_timestamp = $this->conn->prepare("UPDATE `users` SET updated_at = ? WHERE id = ?");
                        $update_timestamp->execute([$current_time, $this->user_id]);
                    }
                    
                    // Log password change activity
                    $this->activityLog->recordActivity('password_change', 'User changed their password');
                    
                    // Send email notification about password change using PHPMailer
                    $this->sendPasswordChangeEmail($email, $name);
                    
                    return true;
                } catch (PDOException $e) {
                    return 'Database error: ' . $e->getMessage();
                }
            }
        }
        return true;
    }
    
    private function sendPasswordChangeEmail($email, $name) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
      
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'kandelgaurav04@gmail.com';
            $mail->Password   = 'xmnfszxvelzuettu';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            
            // Recipients
            $mail->setFrom('Nepal~Store@gmail.com', 'Nepal~Store Support');
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Changed';
            $mail->Body    = "
                <html>
                <body>
                    <h2>Password Changed Successfully</h2>
                    <p>Hello $name,</p>
                    <p>Your password has been successfully changed.</p>
                    <p>If you didn't make this change, please contact support immediately.</p>
                    <br>
                    <p>Best regards,<br>Nepal Store Team</p>
                </body>
                </html>
            ";
            
            $mail->AltBody = "Hello $name,\n\nYour password has been successfully changed.\n\nIf you didn't make this change, please contact support immediately.\n\nContact no: 9840245415";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log error but don't show to user as it's not critical
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    public function refreshProfile() {
        $this->loadProfile();
    }
    
    public function recordLoginHistory($ip_address, $user_agent) {
        return $this->loginHistory->recordLoginHistory($ip_address, $user_agent);
    }
}

// Create user object
$user = new User($conn, $user_id);
$fetch_profile = $user->getProfileData();

// Record the login history after creating the user object
if (!isset($_SESSION['login_recorded'])) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $user->recordLoginHistory($ip_address, $user_agent);
    $_SESSION['login_recorded'] = true;
}

// Get login history for display
$login_history = $user->getLoginHistory();
$total_logins = $user->getTotalLogins();

if(isset($_POST['submit'])){
    // Update profile information
    $update_result = $user->updateProfile($_POST, $_FILES);
    
    if($update_result !== true) {
        $message[] = $update_result;
    }
    
    // Password update logic
    $password_result = $user->updatePassword(
        $_POST['old_pass'], 
        $_POST['new_pass'], 
        $_POST['cpass'],
        $_POST['email'],
        $_POST['name']
    );
    
    if($password_result !== true) {
        $message[] = $password_result;
    } else {
        if(!empty($_POST['old_pass']) || !empty($_POST['new_pass']) || !empty($_POST['cpass'])) {
            $message[] = 'Profile and password updated successfully!';
        } else {
            $message[] = 'Profile updated successfully!';
        }
    }
    
    // Refresh profile data after update
    $user->refreshProfile();
    $fetch_profile = $user->getProfileData();
    
    // Refresh login history after update
    $login_history = $user->getLoginHistory();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Profile</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- bootstrap cdn link -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

   <!-- custom css file links -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/profile.css">

   <style>
      .profile-container {
         max-width: 1200px;
         margin: 30px auto;
         padding: 20px;
         background: #fff;
         border-radius: 10px;
         box-shadow: 0 0 15px rgba(0,0,0,0.1);
      }
      
      .form-section {
         background: #f9f9f9;
         padding: 20px;
         border-radius: 8px;
         margin-bottom: 20px;
      }
      
      .section-title {
         color: #3498db;
         border-bottom: 2px solid #3498db;
         padding-bottom: 10px;
         margin-bottom: 20px;
      }
      
      .profile-pic-container {
         position: relative;
         display: inline-block;
         margin-bottom: 20px;
      }
      
      .profile-pic {
         width: 150px;
         height: 150px;
         object-fit: cover;
         border-radius: 50%;
         border: 3px solid #3498db;
      }
      
      .profile-pic-upload {
         position: absolute;
         bottom: 5px;
         right: 5px;
         background: #3498db;
         color: white;
         border-radius: 50%;
         width: 40px;
         height: 40px;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
      }
      
      .profile-pic-upload input {
         display: none;
      }
      
      .btn-update {
         background: #3498db;
         color: white;
         padding: 12px 30px;
         border-radius: 50px;
         font-weight: bold;
         transition: all 0.3s;
      }
      
      .btn-update:hover {
         background: #2980b9;
         transform: translateY(-2px);
      }
      
      .password-strength {
         height: 5px;
         background: #eee;
         margin-top: 5px;
         border-radius: 3px;
         overflow: hidden;
      }
      
      .password-strength-bar {
         height: 100%;
         width: 0;
         transition: width 0.3s;
      }
      
      .password-strength-bar.weak {
         background: #e74c3c;
      }
      
      .password-strength-bar.medium {
         background: #f39c12;
      }
      
      .password-strength-bar.good {
         background: #2ecc71;
      }
      
      .password-strength-bar.strong {
         background: #27ae60;
      }
      
      .progress-text {
         font-size: 12px;
         margin-top: 5px;
         color: #7f8c8d;
      }
      
      .input-group-text {
         cursor: pointer;
      }
      
      .error-message {
         color: red;
         font-size: 14px;
         margin-top: 5px;
         display: none;
      }
      
      .login-history-table {
         font-size: 14px;
         max-height: 200px;
         overflow-y: auto;
      }
      
      .login-history-table th {
         background-color: #f8f9fa;
         position: sticky;
         top: 0;
      }
      
      .login-item {
         border-bottom: 1px solid #dee2e6;
         padding: 8px 0;
      }
      
      .login-item:last-child {
         border-bottom: none;
      }
      
      .ip-address {
         font-family: monospace;
         background: #e9ecef;
         padding: 2px 6px;
         border-radius: 4px;
         font-size: 0.8rem;
      }
      
      .device-icon {
         font-size: 1.2rem;
         margin-right: 8px;
         color: #6c757d;
      }
   </style>

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="profile-container">
   <?php
   if(isset($message)){
      foreach($message as $msg){
         echo '
         <div class="alert alert-warning alert-dismissible fade show" role="alert">
            '.htmlspecialchars($msg, ENT_QUOTES).'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
         ';
      }
   }
   ?>

   <form action="" method="post" enctype="multipart/form-data" id="profile-form">
      <div class="row">
         <div class="col-md-6">
            <div class="form-section">
               <h3 class="section-title">Personal Information</h3>
               
               <div class="mb-3 text-center">
                  <div class="profile-pic-container">
                     <?php if(!empty($fetch_profile['profile_pic'])): ?>
                        <img src="uploaded_img/<?= htmlspecialchars($fetch_profile['profile_pic']); ?>" class="profile-pic" id="profile-pic-preview" alt="Profile Picture">
                     <?php else: ?>
                        <img src="images/default-avatar.png" class="profile-pic" id="profile-pic-preview" alt="Default Profile Picture">
                     <?php endif; ?>
                     <label class="profile-pic-upload">
                        <i class="fas fa-camera"></i>
                        <input type="file" name="profile_pic" id="profile-pic-input" accept="image/*">
                     </label>
                  </div>
               </div>
               
               <div class="mb-3">
                  <label for="name" class="form-label">Full Name</label>
                  <input type="text" name="name" class="form-control" id="name" required value="<?= htmlspecialchars($fetch_profile['name']); ?>">
                  <div class="error-message" id="name-error">Name can only contain letters and spaces!</div>
               </div>
               
               <div class="mb-3">
                  <label for="email" class="form-label">Email Address</label>
                  <input type="email" name="email" class="form-control" id="email" required value="<?= htmlspecialchars($fetch_profile['email']); ?>">
                  <div class="error-message" id="email-error">Email must be in the format: 0-3 digits followed by letters and optional numbers, ending with @gmail.com</div>
               </div>
               
               <div class="mb-3">
                  <label for="phone" class="form-label">Phone Number</label>
                  <input type="tel" name="phone" class="form-control" id="phone" value="<?= isset($fetch_profile['phone']) ? htmlspecialchars($fetch_profile['phone']) : ''; ?>" placeholder="98XXXXXXXX">
                  <div class="error-message" id="phone-error">Phone number must be 10 digits starting with 97/98!</div>
               </div>
               
               <div class="mb-3">
                  <label for="address" class="form-label">Address</label>
                  <textarea name="address" class="form-control" id="address" rows="3"><?= isset($fetch_profile['address']) ? htmlspecialchars($fetch_profile['address']) : ''; ?></textarea>
               </div>
               
               <div class="mb-3">
                  <label for="birth_date" class="form-label">Birth Date</label>
                  <input type="date" name="birth_date" class="form-control" id="birth_date" value="<?= isset($fetch_profile['birth_date']) ? htmlspecialchars($fetch_profile['birth_date']) : ''; ?>">
               </div>
               
               <?php if($user->columnExists('gender')): ?>
               <div class="mb-3">
                  <label for="gender" class="form-label">Gender</label>
                  <select name="gender" class="form-control" id="gender">
                     <option value="">Select Gender</option>
                     <option value="male" <?= (isset($fetch_profile['gender']) && $fetch_profile['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                     <option value="female" <?= (isset($fetch_profile['gender']) && $fetch_profile['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                     <option value="other" <?= (isset($fetch_profile['gender']) && $fetch_profile['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                     <option value="prefer_not_to_say" <?= (isset($fetch_profile['gender']) && $fetch_profile['gender'] == 'prefer_not_to_say') ? 'selected' : ''; ?>>Prefer not to say</option>
                  </select>
               </div>
               <?php endif; ?>
            </div>
         </div>
         
         <div class="col-md-6">
            <div class="form-section">
               <h3 class="section-title">Change Password</h3>
               <p class="text-muted">Leave password fields blank if you don't want to change it.</p>
               
               <input type="hidden" name="prev_pass" value="<?= htmlspecialchars($fetch_profile['password']); ?>">
               
               <div class="mb-3 input-group">
                  <input type="password" name="old_pass" class="form-control" id="old_pass" placeholder="Enter your old password">
                  <span class="input-group-text" onclick="togglePassword('old_pass')"><i class="fas fa-eye password-toggle"></i></span>
               </div>
               
               <div class="mb-3 input-group">
                  <input type="password" name="new_pass" class="form-control" id="new_pass" placeholder="Enter your new password">
                  <span class="input-group-text" onclick="togglePassword('new_pass')"><i class="fas fa-eye password-toggle"></i></span>
               </div>
               <div class="password-strength">
                  <div class="password-strength-bar" id="password-strength-bar"></div>
               </div>
               <div class="progress-text" id="password-strength-text">Password strength</div>
               
               <div class="mb-3 input-group">
                  <input type="password" name="cpass" class="form-control" id="cpass" placeholder="Confirm your new password">
                  <span class="input-group-text" onclick="togglePassword('cpass')"><i class="fas fa-eye password-toggle"></i></span>
               </div>
               
               <div class="alert alert-info">
                  <i class="fas fa-info-circle"></i> Password requirements:
                  <ul class="mb-0">
                     <li>Minimum 8 characters</li>
                     <li>At least one uppercase letter</li>
                     <li>At least one number</li>
                     <li>At least one special character (!@#$%^&*)</li>
                  </ul>
               </div>
            </div>
            
            <div class="form-section">
               <h3 class="section-title">Account Security</h3>
               
               <div class="mb-3">
                  <label class="form-label">Login Activity</label>
                  <div class="alert alert-light">
                     <i class="fas fa-clock"></i> Last login: <?= ($user->columnExists('last_login') && isset($fetch_profile['last_login']) && !empty($fetch_profile['last_login'])) ? date('F j, Y \a\t g:i a', strtotime($fetch_profile['last_login'])) : 'Never logged in'; ?>
                     <?php if($user->columnExists('last_login_ip') && isset($fetch_profile['last_login_ip']) && !empty($fetch_profile['last_login_ip'])): ?>
                        <br><i class="fas fa-network-wired"></i> IP: <?= htmlspecialchars($fetch_profile['last_login_ip']); ?>
                     <?php endif; ?>
                  </div>
                  
                  <?php if(!empty($login_history)): ?>
                  <div class="mt-3">
                     <h6>Recent Login History <span class="badge bg-secondary"><?= $total_logins ?> total logins</span></h6>
                     <div class="login-history-table">
                        <table class="table table-sm">
                           <thead>
                              <tr>
                                 <th>Date & Time</th>
                                 <th>Device</th>
                                 <th>IP Address</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php foreach($login_history as $login): ?>
                              <tr class="login-item">
                                 <td><?= date('M j, g:i a', strtotime($login['login_time'])) ?></td>
                                 <td>
                                    <?php
                                    $user_agent = $login['user_agent'];
                                    $device_icon = 'desktop';
                                    $device_type = 'Desktop';
                                    if (preg_match('/mobile|android|iphone|ipad|ipod/i', $user_agent)) {
                                        $device_icon = 'mobile-alt';
                                        $device_type = 'Mobile';
                                    } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
                                        $device_icon = 'tablet-alt';
                                        $device_type = 'Tablet';
                                    }
                                    ?>
                                    <i class="fas fa-<?= $device_icon ?> device-icon" title="<?= $device_type ?>"></i>
                                 </td>
                                 <td><span class="ip-address"><?= htmlspecialchars($login['ip_address']) ?></span></td>
                              </tr>
                              <?php endforeach; ?>
                           </tbody>
                        </table>
                     </div>
                  </div>
                  <?php endif; ?>
                  
                  <div class="mt-2">
                     <a href="login_history.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-history"></i> View full login history
                     </a>
                  </div>
               </div>
               
               <div class="mb-3">
                  <label class="form-label">Account Status</label>
                  <div class="alert alert-light">
                     <i class="fas fa-user-shield"></i> Account created: <?= ($user->columnExists('created_at') && isset($fetch_profile['created_at'])) ? date('F j, Y', strtotime($fetch_profile['created_at'])) : 'Unknown'; ?>
                     <br><i class="fas fa-sync-alt"></i> Last updated: <?= ($user->columnExists('updated_at') && isset($fetch_profile['updated_at']) && !empty($fetch_profile['updated_at'])) ? date('F j, Y \a\t g:i a', strtotime($fetch_profile['updated_at'])) : 'Unknown'; ?>
                  </div>
               </div>
            </div>
         </div>
      </div>
      
      <div class="text-center mt-4">
         <button type="submit" name="submit" class="btn btn-update btn-lg">
            <i class="fas fa-save"></i> Update Profile
         </button>
      </div>
   </form>
</div>

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>

<script>
   // Profile picture preview
   document.getElementById('profile-pic-input').addEventListener('change', function(e) {
      if (this.files && this.files[0]) {
         var reader = new FileReader();
         
         reader.onload = function(e) {
            document.getElementById('profile-pic-preview').setAttribute('src', e.target.result);
         }
         
         reader.readAsDataURL(this.files[0]);
      }
   });
   
   // Password toggle visibility
   function togglePassword(id) {
      const input = document.getElementById(id);
      const icon = input.parentElement.querySelector('.password-toggle');
      
      if (input.type === 'password') {
         input.type = 'text';
         icon.classList.remove('fa-eye');
         icon.classList.add('fa-eye-slash');
      } else {
         input.type = 'password';
         icon.classList.remove('fa-eye-slash');
         icon.classList.add('fa-eye');
      }
   }
   
   // Password strength meter
   document.getElementById('new_pass').addEventListener('input', function() {
      const password = this.value;
      const strengthBar = document.getElementById('password-strength-bar');
      const strengthText = document.getElementById('password-strength-text');
      
      if(password.length === 0) {
         strengthBar.style.width = '0%';
         strengthBar.className = 'password-strength-bar';
         strengthText.textContent = 'Password strength';
         return;
      }
      
      const result = zxcvbn(password);
      const score = result.score;
      
      switch(score) {
         case 0:
         case 1:
            strengthBar.style.width = '25%';
            strengthBar.className = 'password-strength-bar weak';
            strengthText.textContent = 'Weak password';
            break;
         case 2:
            strengthBar.style.width = '50%';
            strengthBar.className = 'password-strength-bar medium';
            strengthText.textContent = 'Medium strength password';
            break;
         case 3:
            strengthBar.style.width = '75%';
            strengthBar.className = 'password-strength-bar good';
            strengthText.textContent = 'Good password';
            break;
         case 4:
            strengthBar.style.width = '100%';
            strengthBar.className = 'password-strength-bar strong';
            strengthText.textContent = 'Strong password!';
            break;
      }
   });
   
   // Name validation - only letters and spaces
   document.getElementById('name').addEventListener('input', function() {
      const name = this.value;
      const nameError = document.getElementById('name-error');
      
      if (!/^[a-zA-Z ]*$/.test(name)) {
         nameError.style.display = 'block';
         this.classList.add('is-invalid');
      } else {
         nameError.style.display = 'none';
         this.classList.remove('is-invalid');
      }
   });
   
   // Email validation with specific format
   document.getElementById('email').addEventListener('input', function() {
      const email = this.value;
      const emailError = document.getElementById('email-error');
      const emailPattern = /^\d{0,3}[A-Za-z]+[0-9]*@gmail\.com$/;
      
      if (!emailPattern.test(email)) {
         emailError.style.display = 'block';
         this.classList.add('is-invalid');
      } else {
         emailError.style.display = 'none';
         this.classList.remove('is-invalid');
      }
   });
   
   // Phone validation and formatting
   document.getElementById('phone').addEventListener('input', function(e) {
      const phone = this.value.replace(/\D/g, '');
      const phoneError = document.getElementById('phone-error');
      
      // Format phone number as user types
      if (phone.length > 0) {
         if (phone.startsWith('97') || phone.startsWith('98')) {
            if (phone.length <= 10) {
               this.value = phone;
               phoneError.style.display = 'none';
               this.classList.remove('is-invalid');
            } else {
               this.value = phone.substring(0, 10);
            }
         } else {
            phoneError.style.display = 'block';
            this.classList.add('is-invalid');
         }
      } else {
         phoneError.style.display = 'none';
         this.classList.remove('is-invalid');
      }
   });
   
   // Form validation
   document.getElementById('profile-form').addEventListener('submit', function(e) {
      let isValid = true;
      
      // Name validation
      const name = document.getElementById('name').value;
      if (!/^[a-zA-Z ]*$/.test(name)) {
         document.getElementById('name-error').style.display = 'block';
         document.getElementById('name').classList.add('is-invalid');
         isValid = false;
      }
      
      // Email validation with specific format
      const email = document.getElementById('email').value;
      const emailPattern = /^\d{0,3}[A-Za-z]+[0-9]*@gmail\.com$/;
      if (!emailPattern.test(email)) {
         document.getElementById('email-error').style.display = 'block';
         document.getElementById('email').classList.add('is-invalid');
         isValid = false;
      }
      
      // Phone validation
      const phone = document.getElementById('phone').value;
      if (phone && !/^(97|98)[0-9]{8}$/.test(phone)) {
         document.getElementById('phone-error').style.display = 'block';
         document.getElementById('phone').classList.add('is-invalid');
         isValid = false;
      }
      
      const newPass = document.getElementById('new_pass').value;
      const cPass = document.getElementById('cpass').value;
      const oldPass = document.getElementById('old_pass').value;
      
      // Only validate passwords if any password field is filled
      if (newPass || cPass || oldPass) {
         if (newPass !== cPass) {
            alert('New password and confirm password must match!');
            e.preventDefault();
            return;
         }
         
         if (newPass.length > 0 && newPass.length < 8) {
            alert('Password must be at least 8 characters long!');
            e.preventDefault();
            return;
         }
         
         if (newPass.length > 0 && !/[A-Z]/.test(newPass)) {
            alert('Password must contain at least one uppercase letter!');
            e.preventDefault();
            return;
         }
         
         if (newPass.length > 0 && !/[0-9]/.test(newPass)) {
            alert('Password must contain at least one number!');
            e.preventDefault();
            return;
         }
         
         if (newPass.length > 0 && !/[!@#$%^&*]/.test(newPass)) {
            alert('Password must contain at least one special character (!@#$%^&*)!');
            e.preventDefault();
            return;
         }
      }
      
      if (!isValid) {
         e.preventDefault();
         alert('Please fix the validation errors before submitting.');
      }
   });
</script>

</body>
</html>