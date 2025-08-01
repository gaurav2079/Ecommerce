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

// Fetch current user data
$select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
$select_profile->execute([$user_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Check if columns exist
$columns_to_check = ['gender', 'facebook', 'twitter', 'instagram', 'email_verified', 'two_factor_enabled', 'last_login', 'last_login_ip'];
$column_existence = [];

foreach ($columns_to_check as $column) {
    $stmt = $conn->prepare("SHOW COLUMNS FROM `users` LIKE ?");
    $stmt->execute([$column]);
    $column_existence[$column] = ($stmt->rowCount() > 0);
}

if(isset($_POST['submit'])){
   // Basic information
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_EMAIL);
   $phone = $_POST['phone'];
   $phone = filter_var($phone, FILTER_SANITIZE_STRING);
   $address = $_POST['address'];
   $address = filter_var($address, FILTER_SANITIZE_STRING);
   $birth_date = $_POST['birth_date'];
   $birth_date = filter_var($birth_date, FILTER_SANITIZE_STRING);
   
   if($column_existence['gender']){
      $gender = $_POST['gender'];
      $gender = filter_var($gender, FILTER_SANITIZE_STRING);
   }

   // Social links
   if($column_existence['facebook']){
      $facebook = $_POST['facebook'];
      $facebook = filter_var($facebook, FILTER_SANITIZE_URL);
   }
   
   if($column_existence['twitter']){
      $twitter = $_POST['twitter'];
      $twitter = filter_var($twitter, FILTER_SANITIZE_URL);
   }
   
   if($column_existence['instagram']){
      $instagram = $_POST['instagram'];
      $instagram = filter_var($instagram, FILTER_SANITIZE_URL);
   }

   // Handle profile picture upload
   $profile_pic = $fetch_profile['profile_pic'];
   
   if(!empty($_FILES['profile_pic']['name'])){
      $pic_name = $_FILES['profile_pic']['name'];
      $pic_tmp_name = $_FILES['profile_pic']['tmp_name'];
      $pic_size = $_FILES['profile_pic']['size'];
      $pic_error = $_FILES['profile_pic']['error'];
      $pic_type = $_FILES['profile_pic']['type'];
      
      $pic_ext = explode('.', $pic_name);
      $pic_actual_ext = strtolower(end($pic_ext));
      
      $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
      
      if(in_array($pic_actual_ext, $allowed)){
         if($pic_error === 0){
            if($pic_size < 5000000){
               $pic_new_name = uniqid('', true).".".$pic_actual_ext;
               $pic_destination = 'uploaded_img/'.$pic_new_name;
               
               if(move_uploaded_file($pic_tmp_name, $pic_destination)){
                  if($fetch_profile['profile_pic'] != '' && file_exists('uploaded_img/'.$fetch_profile['profile_pic'])){
                     unlink('uploaded_img/'.$fetch_profile['profile_pic']);
                  }
                  $profile_pic = $pic_new_name;
               } else {
                  $message[] = 'There was an error uploading your picture!';
               }
            } else {
               $message[] = 'Your picture is too big! Max 5MB allowed.';
            }
         } else {
            $message[] = 'There was an error uploading your picture!';
         }
      } else {
         $message[] = 'You cannot upload files of this type! Allowed: JPG, JPEG, PNG, GIF, WEBP';
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
   
   if($column_existence['gender']){
      $update_fields['gender'] = $gender;
   }
   
   if($column_existence['facebook']){
      $update_fields['facebook'] = $facebook;
   }
   
   if($column_existence['twitter']){
      $update_fields['twitter'] = $twitter;
   }
   
   if($column_existence['instagram']){
      $update_fields['instagram'] = $instagram;
   }
   
   $set_clause = implode(', ', array_map(function($field){
      return "`$field` = ?";
   }, array_keys($update_fields)));
   
   $update_values = array_values($update_fields);
   $update_values[] = $user_id;
   
   try {
      $update_profile = $conn->prepare("UPDATE `users` SET $set_clause WHERE id = ?");
      $update_profile->execute($update_values);
   } catch (PDOException $e) {
      $message[] = 'Database error: ' . $e->getMessage();
   }

   // Password update logic
   $prev_pass = $fetch_profile['password'];
   $old_pass = $_POST['old_pass'];
   $new_pass = $_POST['new_pass'];
   $cpass = $_POST['cpass'];

   if(!empty($old_pass) || !empty($new_pass) || !empty($cpass)){
      if(empty($old_pass)){
         $message[] = 'Please enter your old password!';
      } elseif(!password_verify($old_pass, $prev_pass)){
         $message[] = 'Old password not matched!';
      } elseif($new_pass != $cpass){
         $message[] = 'Confirm password not matched!';
      } elseif(strlen($new_pass) < 8){
         $message[] = 'Password must be at least 8 characters long!';
      } elseif(!preg_match('/[A-Z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[!@#$%^&*]/', $new_pass)){
         $message[] = 'Password must contain at least one uppercase letter, one number and one special character!';
      } else {
         $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
         try {
            $update_admin_pass = $conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
            $update_admin_pass->execute([$hashed_password, $user_id]);
            $message[] = 'Profile and password updated successfully!';
            
            // Send email notification about password change
            $to = $email;
            $subject = 'Password Changed';
            $message_text = "Hello $name,\n\nYour password has been successfully changed.\n\nIf you didn't make this change, please contact support immediately.";
            $headers = 'From: noreply@yourdomain.com';
            mail($to, $subject, $message_text, $headers);
         } catch (PDOException $e) {
            $message[] = 'Database error: ' . $e->getMessage();
         }
      }
   } else {
      $message[] = 'Profile updated successfully!';
   }
   
   // Refresh profile data after update
   $select_profile->execute([$user_id]);
   $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
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

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      .profile-container {
         max-width: 1200px;
         margin: 0 auto;
         padding: 2rem;
      }
      .profile-header {
         text-align: center;
         margin-bottom: 2rem;
      }
      .profile-pic-container {
         position: relative;
         width: 150px;
         height: 150px;
         margin: 0 auto 1rem;
         border-radius: 50%;
         overflow: hidden;
         border: 5px solid #fff;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      }
      .profile-pic {
         width: 100%;
         height: 100%;
         object-fit: cover;
      }
      .profile-pic-upload {
         position: absolute;
         bottom: 0;
         left: 0;
         right: 0;
         background: rgba(0,0,0,0.5);
         color: white;
         text-align: center;
         padding: 5px;
         cursor: pointer;
      }
      .profile-pic-upload input {
         display: none;
      }
      .form-section {
         background: #fff;
         border-radius: 10px;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
         padding: 2rem;
         margin-bottom: 2rem;
      }
      .section-title {
         color: #2980b9;
         border-bottom: 2px solid #f1f1f1;
         padding-bottom: 10px;
         margin-bottom: 20px;
      }
      .form-control {
         padding: 12px 15px;
         margin-bottom: 15px;
         border-radius: 5px;
         border: 1px solid #ddd;
      }
      .form-control:focus {
         border-color: #2980b9;
         box-shadow: 0 0 0 0.25rem rgba(41, 128, 185, 0.25);
      }
      .btn-update {
         background: #2980b9;
         color: white;
         padding: 12px 30px;
         border: none;
         border-radius: 5px;
         font-weight: 600;
         transition: all 0.3s;
      }
      .btn-update:hover {
         background: #3498db;
         transform: translateY(-2px);
      }
      .password-toggle {
         position: absolute;
         right: 10px;
         top: 50%;
         transform: translateY(-50%);
         cursor: pointer;
      }
      .input-group {
         position: relative;
      }
      .password-strength {
         height: 5px;
         margin-top: -10px;
         margin-bottom: 15px;
         background: #eee;
         border-radius: 3px;
         overflow: hidden;
      }
      .password-strength-bar {
         height: 100%;
         width: 0%;
         transition: width 0.3s;
      }
      .weak { background: #ff4757; width: 30%; }
      .medium { background: #ffa502; width: 60%; }
      .strong { background: #2ed573; width: 100%; }
      .progress-text {
         font-size: 12px;
         color: #666;
         margin-top: -5px;
      }
      .social-links .btn {
         margin-right: 10px;
         margin-bottom: 10px;
      }
      .verification-badge {
         color: #2ecc71;
         font-size: 14px;
         margin-left: 5px;
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
            '.htmlspecialchars($msg).'
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
                        <i class="fas fa-camera"></i> Change Photo
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
                     <?php if($column_existence['email_verified'] && $fetch_profile['email_verified'] == 1): ?>
                        <span class="verification-badge"><i class="fas fa-check-circle"></i> Verified</span>
                     <?php else: ?>
                        <span class="text-danger"><small>(Not verified)</small></span>
                     <?php endif; ?>
                  </label>
                  <input type="email" name="email" class="form-control" id="email" required value="<?= htmlspecialchars($fetch_profile['email']); ?>">
                  <?php if($column_existence['email_verified'] && $fetch_profile['email_verified'] == 0): ?>
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
               
               <?php if($column_existence['gender']): ?>
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
            
            <div class="form-section">
               <h3 class="section-title">Social Links</h3>
               <div class="mb-3">
                  <label for="facebook" class="form-label">Facebook</label>
                  <div class="input-group">
                     <span class="input-group-text"><i class="fab fa-facebook-f"></i></span>
                     <input type="url" name="facebook" class="form-control" id="facebook" placeholder="https://facebook.com/username" value="<?= isset($fetch_profile['facebook']) ? htmlspecialchars($fetch_profile['facebook']) : ''; ?>">
                  </div>
               </div>
               
               <div class="mb-3">
                  <label for="twitter" class="form-label">Twitter</label>
                  <div class="input-group">
                     <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                     <input type="url" name="twitter" class="form-control" id="twitter" placeholder="https://twitter.com/username" value="<?= isset($fetch_profile['twitter']) ? htmlspecialchars($fetch_profile['twitter']) : ''; ?>">
                  </div>
               </div>
               
               <div class="mb-3">
                  <label for="instagram" class="form-label">Instagram</label>
                  <div class="input-group">
                     <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                     <input type="url" name="instagram" class="form-control" id="instagram" placeholder="https://instagram.com/username" value="<?= isset($fetch_profile['instagram']) ? htmlspecialchars($fetch_profile['instagram']) : ''; ?>">
                  </div>
               </div>
            </div>
         </div>
         
         <div class="col-md-6">
            <div class="form-section">
               <h3 class="section-title">Change Password</h3>
               <p class="text-muted">Leave password fields blank if you don't want to change it.</p>
               
               <input type="hidden" name="prev_pass" value="<?= htmlspecialchars($fetch_profile['password']); ?>">
               
               <div class="mb-3 input-group">
                  <input type="password" name="old_pass" class="form-control" id="old_pass" placeholder="Enter your old password">
                  <i class="fas fa-eye password-toggle" onclick="togglePassword('old_pass')"></i>
               </div>
               
               <div class="mb-3 input-group">
                  <input type="password" name="new_pass" class="form-control" id="new_pass" placeholder="Enter your new password">
                  <i class="fas fa-eye password-toggle" onclick="togglePassword('new_pass')"></i>
               </div>
               <div class="password-strength">
                  <div class="password-strength-bar" id="password-strength-bar"></div>
               </div>
               <div class="progress-text" id="password-strength-text">Password strength</div>
               
               <div class="mb-3 input-group">
                  <input type="password" name="cpass" class="form-control" id="cpass" placeholder="Confirm your new password">
                  <i class="fas fa-eye password-toggle" onclick="togglePassword('cpass')"></i>
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
                  <label class="form-label">Two-Factor Authentication</label>
                  <div class="form-check form-switch">
                     <input class="form-check-input" type="checkbox" id="twoFactorAuth" <?= ($column_existence['two_factor_enabled'] && $fetch_profile['two_factor_enabled']) ? 'checked' : ''; ?>>
                     <label class="form-check-label" for="twoFactorAuth">Enable two-factor authentication</label>
                  </div>
                  <small class="text-muted">Add an extra layer of security to your account</small>
               </div>
               
               <div class="mb-3">
                  <label class="form-label">Login Activity</label>
                  <div class="alert alert-light">
                     <i class="fas fa-clock"></i> Last login: <?= ($column_existence['last_login'] && isset($fetch_profile['last_login'])) ? date('F j, Y \a\t g:i a', strtotime($fetch_profile['last_login'])) : 'Never logged in'; ?>
                     <?php if($column_existence['last_login_ip'] && isset($fetch_profile['last_login_ip'])): ?>
                        <br><i class="fas fa-network-wired"></i> IP: <?= htmlspecialchars($fetch_profile['last_login_ip']); ?>
                     <?php endif; ?>
                  </div>
                  <a href="login_history.php" class="btn btn-sm btn-outline-primary">View login history</a>
               </div>
               
               <div class="mb-3">
                  <label class="form-label">Account Status</label>
                  <div class="alert alert-light">
                     <i class="fas fa-user-shield"></i> Account created: <?= date('F j, Y', strtotime($fetch_profile['created_at'])); ?>
                     <br><i class="fas fa-sync-alt"></i> Last updated: <?= date('F j, Y \a\t g:i a', strtotime($fetch_profile['updated_at'])); ?>
                  </div>
               </div>
            </div>
            
            <div class="form-section">
               <h3 class="section-title">Danger Zone</h3>
               <div class="alert alert-danger">
                  <h5><i class="fas fa-exclamation-triangle"></i> Warning</h5>
                  <p>These actions are irreversible. Please proceed with caution.</p>
                  
                  <div class="d-grid gap-2">
                     <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                        <i class="fas fa-user-slash"></i> Deactivate Account
                     </button>
                     <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash-alt"></i> Delete Account Permanently
                     </button>
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

<!-- Deactivate Account Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="deactivateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deactivateModalLabel">Deactivate Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Your account will be deactivated and your profile will not be visible to others. You can reactivate your account by logging in again.</p>
        <p>Are you sure you want to deactivate your account?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="deactivate_account.php" class="btn btn-danger">Deactivate Account</a>
      </div>
    </div>
  </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Delete Account Permanently</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>This action cannot be undone. All your data will be permanently deleted.</p>
        <p>Are you absolutely sure you want to delete your account?</p>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="confirmDelete">
          <label class="form-check-label" for="confirmDelete">
            I understand this action is irreversible
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="delete_account.php" class="btn btn-danger" id="deleteAccountBtn" disabled>Delete Account</a>
      </div>
    </div>
  </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
<script src="js/script.js"></script>

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
      const icon = input.nextElementSibling;
      
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
            strengthBar.className = 'password-strength-bar weak';
            strengthText.textContent = 'Weak password';
            break;
         case 2:
         case 3:
            strengthBar.className = 'password-strength-bar medium';
            strengthText.textContent = 'Medium strength password';
            break;
         case 4:
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
   
   // Delete account confirmation
   document.getElementById('confirmDelete').addEventListener('change', function() {
      document.getElementById('deleteAccountBtn').disabled = !this.checked;
   });
   
   // Check if email is already taken (AJAX)
   document.getElementById('email').addEventListener('blur', function() {
      const email = this.value;
      const currentEmail = '<?= $fetch_profile['email']; ?>';
      
      if(email === currentEmail) return;
      
      fetch('check_email.php?email=' + encodeURIComponent(email))
         .then(response => response.json())
         .then(data => {
            if(data.exists) {
               alert('This email is already registered!');
               this.value = currentEmail;
            }
         })
         .catch(error => console.error('Error:', error));
   });
</script>

</body>
</html>