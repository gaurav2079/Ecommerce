<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:login.php');
   exit();
}

// User class to handle user-related operations
class User {
    private $conn;
    private $user_id;
    private $profile_data;
    private $column_existence;
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->loadProfile();
        $this->checkColumns();
    }
    
    private function loadProfile() {
        $select_profile = $this->conn->prepare("SELECT * FROM `users` WHERE id = ?");
        $select_profile->execute([$this->user_id]);
        $this->profile_data = $select_profile->fetch(PDO::FETCH_ASSOC);
    }
    
    private function checkColumns() {
        $columns_to_check = ['gender', 'facebook', 'twitter', 'instagram', 'email_verified', 'two_factor_enabled', 'last_login', 'last_login_ip', 'created_at', 'updated_at'];
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
    
    public function updateProfile($data, $files) {
        // Basic information
        $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        $address = filter_var($data['address'], FILTER_SANITIZE_STRING);
        $birth_date = filter_var($data['birth_date'], FILTER_SANITIZE_STRING);
        
        if($this->columnExists('gender')){
            $gender = filter_var($data['gender'], FILTER_SANITIZE_STRING);
        }

        // Social links
        if($this->columnExists('facebook')){
            $facebook = filter_var($data['facebook'], FILTER_SANITIZE_URL);
        }
        
        if($this->columnExists('twitter')){
            $twitter = filter_var($data['twitter'], FILTER_SANITIZE_URL);
        }
        
        if($this->columnExists('instagram')){
            $instagram = filter_var($data['instagram'], FILTER_SANITIZE_URL);
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
        
        if($this->columnExists('facebook')){
            $update_fields['facebook'] = $facebook;
        }
        
        if($this->columnExists('twitter')){
            $update_fields['twitter'] = $twitter;
        }
        
        if($this->columnExists('instagram')){
            $update_fields['instagram'] = $instagram;
        }
        
        $set_clause = implode(', ', array_map(function($field){
            return "`$field` = ?";
        }, array_keys($update_fields)));
        
        $update_values = array_values($update_fields);
        $update_values[] = $this->user_id;
        
        try {
            $update_profile = $this->conn->prepare("UPDATE `users` SET $set_clause WHERE id = ?");
            $update_profile->execute($update_values);
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
            } elseif(!password_verify($old_pass, $prev_pass)){
                return 'Old password not matched!';
            } elseif($new_pass != $cpass){
                return 'Confirm password not matched!';
            } elseif(strlen($new_pass) < 8){
                return 'Password must be at least 8 characters long!';
            } elseif(!preg_match('/[A-Z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[!@#$%^&*]/', $new_pass)){
                return 'Password must contain at least one uppercase letter, one number and one special character!';
            } else {
                $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
                try {
                    $update_admin_pass = $this->conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
                    $update_admin_pass->execute([$hashed_password, $this->user_id]);
                    
                    // Send email notification about password change
                    $to = $email;
                    $subject = 'Password Changed';
                    $message_text = "Hello $name,\n\nYour password has been successfully changed.\n\nIf you didn't make this change, please contact support immediately.";
                    $headers = 'From: noreply@yourdomain.com';
                    mail($to, $subject, $message_text, $headers);
                    
                    return true;
                } catch (PDOException $e) {
                    return 'Database error: ' . $e->getMessage();
                }
            }
        }
        return true;
    }
    
    public function refreshProfile() {
        $this->loadProfile();
    }
}

// Create user object
$user = new User($conn, $user_id);
$fetch_profile = $user->getProfileData();

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
      
      .verification-badge {
         color: #27ae60;
         font-size: 0.8em;
      }
      
      .input-group-text {
         cursor: pointer;
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

   <form action="" method="post" enctype="multipart/form-data">
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
               </div>
               
               <div class="mb-3">
                  <label for="email" class="form-label">Email Address 
                     <?php if($user->columnExists('email_verified') && isset($fetch_profile['email_verified']) && $fetch_profile['email_verified'] == 1): ?>
                        <span class="verification-badge"><i class="fas fa-check-circle"></i> Verified</span>
                     <?php else: ?>
                        <span class="text-danger"><small>(Not verified)</small></span>
                     <?php endif; ?>
                  </label>
                  <input type="email" name="email" class="form-control" id="email" required value="<?= htmlspecialchars($fetch_profile['email']); ?>">
                  <?php if($user->columnExists('email_verified') && isset($fetch_profile['email_verified']) && $fetch_profile['email_verified'] == 0): ?>
                     <small class="text-muted">Check your email for verification link or <a href="send_verification.php">resend verification email</a></small>
                  <?php endif; ?>
               </div>
               
               <div class="mb-3">
                  <label for="phone" class="form-label">Phone Number</label>
                  <input type="tel" name="phone" class="form-control" id="phone" value="<?= isset($fetch_profile['phone']) ? htmlspecialchars($fetch_profile['phone']) : ''; ?>">
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
            
            <!-- Social Media Section -->
            <div class="form-section">
               <h3 class="section-title">Social Media</h3>
               <p class="text-muted">Connect your social media accounts (optional)</p>
               
               <?php if($user->columnExists('facebook')): ?>
               <div class="mb-3">
                  <label for="facebook" class="form-label"><i class="fab fa-facebook"></i> Facebook</label>
                  <input type="url" name="facebook" class="form-control" id="facebook" placeholder="https://facebook.com/username" value="<?= isset($fetch_profile['facebook']) ? htmlspecialchars($fetch_profile['facebook']) : ''; ?>">
               </div>
               <?php endif; ?>
               
               <?php if($user->columnExists('twitter')): ?>
               <div class="mb-3">
                  <label for="twitter" class="form-label"><i class="fab fa-twitter"></i> Twitter</label>
                  <input type="url" name="twitter" class="form-control" id="twitter" placeholder="https://twitter.com/username" value="<?= isset($fetch_profile['twitter']) ? htmlspecialchars($fetch_profile['twitter']) : ''; ?>">
               </div>
               <?php endif; ?>
               
               <?php if($user->columnExists('instagram')): ?>
               <div class="mb-3">
                  <label for="instagram" class="form-label"><i class="fab fa-instagram"></i> Instagram</label>
                  <input type="url" name="instagram" class="form-control" id="instagram" placeholder="https://instagram.com/username" value="<?= isset($fetch_profile['instagram']) ? htmlspecialchars($fetch_profile['instagram']) : ''; ?>">
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
                     <i class="fas fa-clock"></i> Last login: <?= ($user->columnExists('last_login') && isset($fetch_profile['last_login'])) ? date('F j, Y \a\t g:i a', strtotime($fetch_profile['last_login'])) : 'Never logged in'; ?>
                     <?php if($user->columnExists('last_login_ip') && isset($fetch_profile['last_login_ip'])): ?>
                        <br><i class="fas fa-network-wired"></i> IP: <?= htmlspecialchars($fetch_profile['last_login_ip']); ?>
                     <?php endif; ?>
                  </div>
                  <a href="login_history.php" class="btn btn-sm btn-outline-primary">View login history</a>
               </div>
               
               <div class="mb-3">
                  <label class="form-label">Account Status</label>
                  <div class="alert alert-light">
                     <i class="fas fa-user-shield"></i> Account created: <?= ($user->columnExists('created_at') && isset($fetch_profile['created_at'])) ? date('F j, Y', strtotime($fetch_profile['created_at'])) : 'Unknown'; ?>
                     <br><i class="fas fa-sync-alt"></i> Last updated: <?= ($user->columnExists('updated_at') && isset($fetch_profile['updated_at'])) ? date('F j, Y \a\t g:i a', strtotime($fetch_profile['updated_at'])) : 'Unknown'; ?>
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
   
   // Form validation
   document.querySelector('form').addEventListener('submit', function(e) {
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
   });
</script>

</body>
</html>