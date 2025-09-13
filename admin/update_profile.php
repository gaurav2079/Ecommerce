<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
   header('location:admin_login.php');
   exit;
}

// Fetch current admin profile
$fetch_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
$fetch_profile->execute([$admin_id]);
$fetch_profile = $fetch_profile->fetch(PDO::FETCH_ASSOC);

$messages = [];

if (isset($_POST['submit'])) {

   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);

   // Update name
   $update_profile_name = $conn->prepare("UPDATE `admins` SET name = ? WHERE id = ?");
   $update_profile_name->execute([$name, $admin_id]);
   $messages[] = 'Username updated successfully!';

   $empty_pass = sha1('');
   $prev_pass = $_POST['prev_pass'];
   $old_pass = filter_var(sha1($_POST['old_pass']), FILTER_SANITIZE_STRING);
   $new_pass = filter_var(sha1($_POST['new_pass']), FILTER_SANITIZE_STRING);
   $confirm_pass = filter_var(sha1($_POST['confirm_pass']), FILTER_SANITIZE_STRING);

   if ($_POST['old_pass'] != '') {
      if ($old_pass != $prev_pass) {
         $messages[] = 'Old password not matched!';
      } elseif ($_POST['new_pass'] == '') {
         $messages[] = 'Please enter a new password!';
      } elseif ($new_pass != $confirm_pass) {
         $messages[] = 'Confirm password not matched!';
      } else {
         $update_pass = $conn->prepare("UPDATE `admins` SET password = ? WHERE id = ?");
         $update_pass->execute([$confirm_pass, $admin_id]);
         $messages[] = 'Password updated successfully!';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Update Profile | Admin</title>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- Google Font -->
   <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">

   <style>
      * {
         font-family: 'Roboto', sans-serif;
         margin: 0;
         padding: 0;
         box-sizing: border-box;
      }

      body {
         background: #f3f4f6;
         color: #333;
      }

      .form-container {
         display: flex;
         justify-content: center;
         align-items: center;
         height: 100vh;
         padding: 1rem;
      }

      form {
         background: white;
         padding: 2rem;
         border-radius: 10px;
         box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
         width: 100%;
         max-width: 450px;
      }

      h3 {
         text-align: center;
         margin-bottom: 1.5rem;
         font-size: 1.6rem;
         color: #1e3a8a;
      }

      .box {
         width: 100%;
         padding: 0.8rem 1rem;
         border: 1px solid #ccc;
         border-radius: 5px;
         margin-bottom: 1rem;
         font-size: 1rem;
      }

      .btn {
         width: 100%;
         background-color: #2563eb;
         color: white;
         border: none;
         padding: 0.8rem;
         border-radius: 5px;
         font-size: 1rem;
         cursor: pointer;
         transition: background 0.3s;
      }

      .btn:hover {
         background-color: #1e40af;
      }

      .message {
         background-color: #e0f2fe;
         color: #0369a1;
         padding: 0.8rem;
         margin-bottom: 1rem;
         border-radius: 5px;
         text-align: center;
      }

      .password-toggle {
         position: relative;
      }

      .password-toggle i {
         position: absolute;
         right: 10px;
         top: 50%;
         transform: translateY(-50%);
         cursor: pointer;
         color: #555;
      }
   </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="form-container">

   <form action="" method="post" onsubmit="return validateForm();">
      <h3>Update Profile</h3>

      <?php
      if (!empty($messages)) {
         foreach ($messages as $msg) {
            echo '<div class="message">' . htmlspecialchars($msg) . '</div>';
         }
      }
      ?>

      <input type="hidden" name="prev_pass" value="<?= $fetch_profile['password']; ?>">

      <input type="text" name="name" value="<?= htmlspecialchars($fetch_profile['name']); ?>" required placeholder="Enter your username" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">

      <div class="password-toggle">
         <input type="password" name="old_pass" placeholder="Enter old password" maxlength="20" class="box" id="old_pass">
         <i class="fa fa-eye" onclick="toggleVisibility('old_pass', this)"></i>
      </div>

      <div class="password-toggle">
         <input type="password" name="new_pass" placeholder="Enter new password" maxlength="20" class="box" id="new_pass">
         <i class="fa fa-eye" onclick="toggleVisibility('new_pass', this)"></i>
      </div>

      <div class="password-toggle">
         <input type="password" name="confirm_pass" placeholder="Confirm new password" maxlength="20" class="box" id="confirm_pass">
         <i class="fa fa-eye" onclick="toggleVisibility('confirm_pass', this)"></i>
      </div>

      <input type="submit" value="Update Now" class="btn" name="submit">
   </form>

</section>

<script>
// Password toggle
function toggleVisibility(fieldId, icon) {
   const field = document.getElementById(fieldId);
   if (field.type === "password") {
      field.type = "text";
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
   } else {
      field.type = "password";
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
   }
}

// Basic validation
function validateForm() {
   const newPass = document.getElementById("new_pass").value;
   const confirmPass = document.getElementById("confirm_pass").value;

   if (newPass !== confirmPass) {
      alert("New password and confirm password do not match!");
      return false;
   }

   if (newPass.length > 0 && newPass.length < 6) {
      alert("New password must be at least 6 characters long!");
      return false;
   }

   return true;
}
</script>

<script src="../js/admin_script.js"></script>

</body>
</html>
