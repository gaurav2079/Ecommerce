<?php
include 'connect.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
   // Verify CSRF token
   if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
      echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
      exit;
   }
   
   $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
   
   if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
      echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
      exit;
   }
   
   // Check if email already exists
   $check_subscriber = $conn->prepare("SELECT * FROM `newsletter_subscribers` WHERE email = ?");
   $check_subscriber->execute([$email]);
   
   if($check_subscriber->rowCount() > 0){
      echo json_encode(['success' => false, 'message' => 'This email is already subscribed']);
      exit;
   }
   
   // Insert new subscriber
   $insert_subscriber = $conn->prepare("INSERT INTO `newsletter_subscribers` (email) VALUES (?)");
   if($insert_subscriber->execute([$email])){
      echo json_encode(['success' => true, 'message' => 'Thank you for subscribing to our newsletter!']);
   } else {
      echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again.']);
   }
} else {
   echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>