<?php
include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_admins = $conn->prepare("DELETE FROM `admins` WHERE id = ?");
   $delete_admins->execute([$delete_id]);
   header('location:admin_accounts.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Accounts</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <style>
      :root {
         --primary: #4361ee;
         --primary-light: #4895ef;
         --secondary: #3f37c9;
         --dark: #2b2d42;
         --light: #f8f9fa;
         --danger: #ef233c;
         --success: #4cc9f0;
         --border-radius: 8px;
         --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
         --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      }

      * {
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      body {
         background-color: #f5f7fa;
         color: var(--dark);
      }

      .heading {
         text-align: center;
         margin-bottom: 2rem;
         font-size: 2rem;
         color: var(--dark);
         position: relative;
         padding-bottom: 10px;
      }

      .heading:after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 50%;
         transform: translateX(-50%);
         width: 80px;
         height: 3px;
         background: var(--primary);
      }

      .accounts {
         max-width: 1200px;
         margin: 0 auto;
         padding: 2rem;
      }

      .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
         gap: 2rem;
      }

      .box {
         background: white;
         border-radius: var(--border-radius);
         box-shadow: var(--shadow);
         padding: 2rem;
         transition: var(--transition);
         display: flex;
         flex-direction: column;
         gap: 1rem;
      }

      .box:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      }

      .box p {
         font-size: 1rem;
         color: var(--dark);
         line-height: 1.5;
      }

      .box p span {
         font-weight: 600;
         color: var(--primary);
      }

      .option-btn, .delete-btn {
         display: inline-block;
         padding: 0.8rem 1.5rem;
         border-radius: var(--border-radius);
         font-weight: 600;
         text-align: center;
         transition: var(--transition);
         text-decoration: none;
      }

      .option-btn {
         background: var(--primary);
         color: white;
      }

      .option-btn:hover {
         background: var(--secondary);
         transform: translateY(-2px);
      }

      .delete-btn {
         background: #f8d7da;
         color: var(--danger);
      }

      .delete-btn:hover {
         background: #f1aeb5;
         transform: translateY(-2px);
      }

      .flex-btn {
         display: flex;
         gap: 1rem;
         margin-top: 1.5rem;
      }

      .empty {
         text-align: center;
         grid-column: 1/-1;
         padding: 3rem 0;
         color: #6c757d;
      }

      .add-admin-box {
         background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
         color: white;
         display: flex;
         flex-direction: column;
         align-items: center;
         justify-content: center;
         text-align: center;
      }

      .add-admin-box p {
         color: white;
         font-size: 1.2rem;
         margin-bottom: 1rem;
      }

      .add-admin-box .option-btn {
         background: white;
         color: var(--primary);
      }

      .add-admin-box .option-btn:hover {
         background: white;
         color: var(--secondary);
         transform: scale(1.05);
      }

      @media (max-width: 768px) {
         .box-container {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
         }
         
         .accounts {
            padding: 1.5rem;
         }
      }

      @media (max-width: 480px) {
         .flex-btn {
            flex-direction: column;
         }
         
         .heading {
            font-size: 1.5rem;
         }
      }
   </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="accounts">
   <h1 class="heading">Admin Accounts</h1>

   <div class="box-container">

      <div class="box add-admin-box">
         <p>Add New Admin</p>
         <a href="register_admin.php" class="option-btn">Register Admin</a>
      </div>

      <?php
         $select_accounts = $conn->prepare("SELECT * FROM `admins`");
         $select_accounts->execute();
         if($select_accounts->rowCount() > 0){
            while($fetch_accounts = $select_accounts->fetch(PDO::FETCH_ASSOC)){   
      ?>
      <div class="box">
         <p>Admin ID: <span><?= $fetch_accounts['id']; ?></span></p>
         <p>Admin Name: <span><?= $fetch_accounts['name']; ?></span></p>
         <div class="flex-btn">
            <a href="admin_accounts.php?delete=<?= $fetch_accounts['id']; ?>" onclick="return confirm('Delete this account?')" class="delete-btn">Delete</a>
            <?php
               if($fetch_accounts['id'] == $admin_id){
                  echo '<a href="update_profile.php" class="option-btn">Update</a>';
               }
            ?>
         </div>
      </div>
      <?php
            }
         }else{
            echo '<p class="empty">No accounts available!</p>';
         }
      ?>

   </div>
</section>

<script>
   // Simple animation for the account boxes
   document.addEventListener('DOMContentLoaded', function() {
      const boxes = document.querySelectorAll('.box-container .box');
      boxes.forEach((box, index) => {
         box.style.opacity = '0';
         box.style.transform = 'translateY(20px)';
         box.style.transition = 'all 0.4s ease-out';
         
         setTimeout(() => {
            box.style.opacity = '1';
            box.style.transform = 'translateY(0)';
         }, 100 * index);
      });
   });
</script>

<script src="../js/admin_script.js"></script>
   
</body>
</html>