<?php
include 'components/connect.php';
session_start();

// Initialize message array
$message = [];

class ContactHandler {
    private $conn;
    private $user_id;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
    }
    
    public function handleRequest(&$message) {
        if(isset($_POST['send'])) {
            $this->processContactForm($message);
        }
    }
    
    private function processContactForm(&$message) {
        // Validate and sanitize inputs
        $name = $this->sanitizeInput($_POST['name']);
        $email = $this->sanitizeInput($_POST['email']);
        $number = $this->sanitizeInput($_POST['number']);
        $msg = $this->sanitizeInput($_POST['msg']);
        
        // Validate inputs
        if(empty($name) || empty($email) || empty($number) || empty($msg)) {
            $message[] = 'All fields are required!';
            return;
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message[] = 'Please enter a valid email address!';
            return;
        }
        
        // Validate phone number
        if(!$this->isValidPhoneNumber($number)) {
            $message[] = 'Please enter a valid 10-digit phone number!';
            return;
        }
        
        if($this->isDuplicateMessage($name, $email, $number, $msg)) {
            $message[] = 'Message already sent!';
        } else {
            if($this->insertMessage($name, $email, $number, $msg)) {
                $message[] = 'Message sent successfully!';
                
                // Clear form fields
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        document.querySelector("form.contact-form").reset();
                    });
                </script>';
            } else {
                $message[] = 'Failed to send message. Please try again.';
            }
        }
    }
    
    private function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    private function isValidPhoneNumber($number) {
        // Remove any non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        
        // Check if it's exactly 10 digits
        return strlen($cleaned) === 10;
    }
    
    private function isDuplicateMessage($name, $email, $number, $msg) {
        // Check for duplicate messages within the last 24 hours
        $select_message = $this->conn->prepare("SELECT * FROM `messages` 
                                              WHERE name = ? AND email = ? AND number = ? AND message = ? 
                                              AND created_at > NOW() - INTERVAL 1 DAY");
        $select_message->execute([$name, $email, $number, $msg]);
        return $select_message->rowCount() > 0;
    }
    
    private function insertMessage($name, $email, $number, $msg) {
        try {
            $insert_message = $this->conn->prepare("INSERT INTO `messages`(user_id, name, email, number, message) 
                                                  VALUES(?,?,?,?,?)");
            return $insert_message->execute([$this->user_id, $name, $email, $number, $msg]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
}

// Create instance and handle request
$contactHandler = new ContactHandler($conn);
$contactHandler->handleRequest($message);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Contact Us | Nepal~Store</title>
   
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
   
    <style>
      :root {
         --primary: #4361ee;
         --secondary: #3a0ca3;
         --accent: #f72585;
         --light: #f8f9fa;
         --dark: #212529;
         --chat-primary: #4361ee;
         --chat-secondary: #3a0ca3;
      }
      
      .contact {
         padding: 5rem 0;
         background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
         min-height: calc(100vh - 160px);
      }
      
      .contact-container {
         display: flex;
         flex-wrap: wrap;
         gap: 3rem;
         max-width: 1200px;
         margin: 0 auto;
         padding: 0 2rem;
      }
      
      .contact-info {
         flex: 1 1 40rem;
      }
      
      .contact-info h3 {
         font-size: 2.5rem;
         color: var(--dark);
         margin-bottom: 2rem;
         position: relative;
      }
      
      .contact-info h3::after {
         content: '';
         position: absolute;
         bottom: -10px;
         left: 0;
         width: 80px;
         height: 4px;
         background: linear-gradient(to right, var(--primary), var(--accent));
         border-radius: 2px;
      }
      
      .contact-info .info-box {
         margin-bottom: 2rem;
         display: flex;
         align-items: flex-start;
         gap: 1.5rem;
      }
      
      .contact-info .info-box i {
         width: 50px;
         height: 50px;
         background: var(--primary);
         color: white;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 1.8rem;
      }
      
      .contact-info .info-box div h4 {
         font-size: 1.8rem;
         color: var(--dark);
         margin-bottom: 0.5rem;
      }
      
      .contact-info .info-box div p {
         font-size: 1.5rem;
         color: #666;
         line-height: 1.6;
      }
      
      .contact-form {
         flex: 1 1 40rem;
         background: white;
         border-radius: 15px;
         padding: 3rem;
         box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      }
      
      .contact-form h3 {
         font-size: 2.5rem;
         color: var(--dark);
         margin-bottom: 2rem;
         position: relative;
      }
      
      .contact-form h3::after {
         content: '';
         position: absolute;
         bottom: -10px;
         left: 0;
         width: 80px;
         height: 4px;
         background: linear-gradient(to right, var(--primary), var(--accent));
         border-radius: 2px;
      }
      
      .contact-form .box {
         width: 100%;
         padding: 1.5rem;
         margin-bottom: 1.5rem;
         border: 2px solid #e0e0e0;
         border-radius: 8px;
         font-size: 1.6rem;
         transition: all 0.3s ease;
      }
      
      .contact-form .box:focus {
         border-color: var(--primary);
         box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
         outline: none;
      }
      
      .contact-form textarea.box {
         height: 150px;
         resize: none;
      }
      
      .contact-form .btn {
         width: 100%;
         padding: 1.5rem;
         background: linear-gradient(to right, var(--primary), var(--secondary));
         color: white;
         border: none;
         border-radius: 8px;
         font-size: 1.6rem;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.3s ease;
         box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
      }
      
      .contact-form .btn:hover {
         transform: translateY(-3px);
         box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
      }
      
      .map-container {
         width: 100%;
         margin-top: 5rem;
         border-radius: 15px;
         overflow: hidden;
         box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      }
      
      .map-container iframe {
         width: 100%;
         height: 400px;
         border: none;
      }
      
      @media (max-width: 768px) {
         .contact-container {
            flex-direction: column;
            padding: 0 1.5rem;
         }
         
         .contact-info, .contact-form {
            flex: 1 1 100%;
         }
         
         .map-container iframe {
            height: 300px;
         }
      }
      
      /* Floating animation */
      @keyframes float {
         0% { transform: translateY(0px); }
         50% { transform: translateY(-10px); }
         100% { transform: translateY(0px); }
      }
      
      .floating {
         animation: float 3s ease-in-out infinite;
      }

      /* Enhanced Chatbot Styles */
      .chatbot-container {
         position: fixed;
         bottom: 30px;
         right: 30px;
         z-index: 1000;
         width: 380px;
         transition: all 0.3s ease;
         font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      
      .chatbot-toggle {
         position: absolute;
         bottom: 0;
         right: 0;
         width: 65px;
         height: 65px;
         background: linear-gradient(135deg, var(--chat-primary), var(--chat-secondary));
         color: white;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
         z-index: 2;
         transition: all 0.3s ease;
         animation: pulse 2s infinite;
      }
      
      @keyframes pulse {
         0% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.7); }
         70% { box-shadow: 0 0 0 10px rgba(67, 97, 238, 0); }
         100% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0); }
      }
      
      .chatbot-toggle:hover {
         transform: scale(1.1) rotate(5deg);
         box-shadow: 0 10px 30px rgba(67, 97, 238, 0.4);
      }
      
      .chatbot-toggle i {
         font-size: 1.8rem;
      }
      
      .chatbot-window {
         background: white;
         border-radius: 20px;
         box-shadow: 0 15px 35px rgba(0,0,0,0.2);
         overflow: hidden;
         transform: translateY(100%);
         opacity: 0;
         height: 0;
         transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
         display: flex;
         flex-direction: column;
      }
      
      .chatbot-window.active {
         transform: translateY(0);
         opacity: 1;
         height: 520px;
         /* FIXED: Removed margin-bottom that was hiding the header */
      }
      
      .chatbot-header {
         background: linear-gradient(135deg, var(--chat-primary), var(--chat-secondary));
         color: white;
         padding: 18px 20px;
         display: flex;
         align-items: center;
         justify-content: space-between;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      .chatbot-header-content {
         display: flex;
         align-items: center;
         gap: 12px;
      }
      
      .chatbot-avatar {
         width: 40px;
         height: 40px;
         background: rgba(255, 255, 255, 0.2);
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         font-size: 1.2rem;
      }
      
      .chatbot-header-text h3 {
         margin: 0;
         font-size: 1.3rem;
         font-weight: 600;
      }
      
      .chatbot-header-text p {
         margin: 2px 0 0;
         font-size: 0.8rem;
         opacity: 0.9;
      }
      
      .chatbot-header .close-btn {
         background: rgba(255, 255, 255, 0.2);
         border: none;
         color: white;
         width: 32px;
         height: 32px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         transition: all 0.2s ease;
      }
      
      .chatbot-header .close-btn:hover {
         background: rgba(255, 255, 255, 0.3);
         transform: rotate(90deg);
      }
      
      .chatbot-body {
         height: 380px;
         overflow-y: auto;
         padding: 20px;
         background: #f8fafd;
         display: flex;
         flex-direction: column;
         gap: 15px;
      }
      
      .chatbot-body::-webkit-scrollbar {
         width: 6px;
      }
      
      .chatbot-body::-webkit-scrollbar-track {
         background: #f1f1f1;
         border-radius: 10px;
      }
      
      .chatbot-body::-webkit-scrollbar-thumb {
         background: #c5c5c5;
         border-radius: 10px;
      }
      
      .chat-message {
         display: flex;
         flex-direction: column;
         max-width: 85%;
         animation: fadeIn 0.3s ease;
      }
      
      .chat-message.bot {
         align-items: flex-start;
      }
      
      .chat-message.user {
         align-items: flex-end;
         align-self: flex-end;
      }
      
      .message-content {
         padding: 12px 16px;
         border-radius: 18px;
         margin-bottom: 5px;
         position: relative;
         line-height: 1.5;
         font-size: 0.95rem;
         box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      }
      
      .bot .message-content {
         background: white;
         color: #333;
         border-bottom-left-radius: 5px;
         box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      }
      
      .user .message-content {
         background: linear-gradient(135deg, var(--chat-primary), var(--chat-secondary));
         color: white;
         border-bottom-right-radius: 5px;
      }
      
      .message-time {
         font-size: 0.7rem;
         color: #999;
         padding: 0 5px;
      }
      
      .chatbot-input-container {
         padding: 15px;
         background: white;
         border-top: 1px solid #eee;
         display: flex;
         align-items: center;
         gap: 10px;
      }
      
      .chatbot-input {
         flex: 1;
         position: relative;
      }
      
      .chatbot-input input {
         width: 100%;
         padding: 12px 15px;
         border: 1px solid #e0e0e0;
         border-radius: 25px;
         outline: none;
         font-size: 0.9rem;
         transition: all 0.3s ease;
         padding-right: 40px;
      }
      
      .chatbot-input input:focus {
         border-color: var(--chat-primary);
         box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
      }
      
      .chatbot-input button {
         position: absolute;
         right: 5px;
         top: 50%;
         transform: translateY(-50%);
         background: var(--chat-primary);
         color: white;
         border: none;
         border-radius: 50%;
         width: 32px;
         height: 32px;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         transition: all 0.3s ease;
      }
      
      .chatbot-input button:hover {
         background: var(--chat-secondary);
         transform: translateY(-50%) scale(1.1);
      }
      
      .chatbot-actions {
         display: flex;
         gap: 8px;
      }
      
      .chatbot-actions button {
         background: #f5f7fa;
         border: none;
         width: 38px;
         height: 38px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         transition: all 0.2s ease;
         color: #666;
      }
      
      .chatbot-actions button:hover {
         background: #e6e9ef;
         color: var(--chat-primary);
      }
      
      .typing-indicator {
         display: flex;
         padding: 10px 15px;
         align-items: center;
         background: white;
         border-radius: 18px;
         width: fit-content;
         box-shadow: 0 2px 5px rgba(0,0,0,0.05);
         margin-bottom: 5px;
      }
      
      .typing-indicator span {
         height: 8px;
         width: 8px;
         background: #ccc;
         border-radius: 50%;
         display: inline-block;
         margin: 0 3px;
      }
      
      .typing-indicator span:nth-child(1) {
         animation: typing 1s infinite ease-in-out;
      }
      
      .typing-indicator span:nth-child(2) {
         animation: typing 1s infinite ease-in-out 0.2s;
      }
      
      .typing-indicator span:nth-child(3) {
         animation: typing 1s infinite ease-in-out 0.4s;
      }
      
      .quick-replies {
         margin-top: 10px;
         padding: 12px;
         background: white;
         border-radius: 12px;
         box-shadow: 0 2px 8px rgba(0,0,0,0.08);
         animation: slideIn 0.3s ease;
      }
      
      .quick-replies p {
         margin-bottom: 8px;
         font-size: 0.85rem;
         color: #666;
         font-weight: 500;
      }
      
      .quick-reply {
         display: inline-block;
         margin: 4px;
         padding: 6px 12px;
         background: #f0f4ff;
         color: var(--chat-primary);
         border: 1px solid #dbe4ff;
         border-radius: 16px;
         font-size: 0.8rem;
         cursor: pointer;
         transition: all 0.2s ease;
      }
      
      .quick-reply:hover {
         background: #e0e7ff;
         transform: translateY(-2px);
         box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }
      
      .ai-toggle {
         display: flex;
         align-items: center;
         justify-content: center;
         padding: 12px;
         background: #f8f9fa;
         border-top: 1px solid #eee;
         font-size: 0.8rem;
         color: #666;
      }
      
      .ai-toggle label {
         display: flex;
         align-items: center;
         cursor: pointer;
         gap: 6px;
      }
      
      .toggle-switch {
         position: relative;
         display: inline-block;
         width: 40px;
         height: 20px;
      }
      
      .toggle-switch input {
         opacity: 0;
         width: 0;
         height: 0;
      }
      
      .toggle-slider {
         position: absolute;
         cursor: pointer;
         top: 0;
         left: 0;
         right: 0;
         bottom: 0;
         background-color: #ccc;
         transition: .4s;
         border-radius: 34px;
      }
      
      .toggle-slider:before {
         position: absolute;
         content: "";
         height: 16px;
         width: 16px;
         left: 2px;
         bottom: 2px;
         background-color: white;
         transition: .4s;
         border-radius: 50%;
      }
      
      input:checked + .toggle-slider {
         background-color: var(--chat-primary);
      }
      
      input:checked + .toggle-slider:before {
         transform: translateX(20px);
      }
      
      .suggested-questions {
         display: flex;
         flex-wrap: wrap;
         gap: 8px;
         margin-top: 10px;
      }
      
      @keyframes typing {
         0%, 100% { transform: translateY(0); opacity: 0.6; }
         50% { transform: translateY(-5px); opacity: 1; }
      }
      
      @keyframes fadeIn {
         from { opacity: 0; transform: translateY(10px); }
         to { opacity: 1; transform: translateY(0); }
      }
      
      @keyframes slideIn {
         from { opacity: 0; transform: translateX(-10px); }
         to { opacity: 1; transform: translateX(0); }
      }
      
      /* Responsive adjustments */
      @media (max-width: 768px) {
         .chatbot-container {
            width: 90%;
            right: 5%;
            bottom: 20px;
         }
         
         .chatbot-window.active {
            height: 70vh;
            /* FIXED: Removed margin-bottom for mobile */
         }
         
         .chatbot-body {
            height: calc(70vh - 150px);
         }
         
         .chat-message {
            max-width: 90%;
         }
      }
   </style>
</head>
<body>
   
   
<?php include 'components/user_header.php'; ?>

<section class="contact">
   <!-- Display messages -->
   <?php
   if(!empty($message)){
      foreach($message as $msg){
         echo '
         <div class="message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
   ?>
   
   <div class="contact-container">
      <div class="contact-info">
         <h3>Contact Information</h3>
         
         <div class="info-box">
            <i class="fas fa-map-marker-alt floating" style="animation-delay: 0.2s"></i>
            <div>
               <h4>Our Location</h4>
               <p>Chitwan, Parsa, Khurkhure 100001</p>
            </div>
         </div>
         
         <div class="info-box">
            <i class="fas fa-phone-alt floating" style="animation-delay: 0.4s"></i>
            <div>
               <h4>Phone Number</h4>
               <p>+977-9840245415</p>
               <p>+977-9801234567</p>
            </div>
         </div>
         
         <div class="info-box">
            <i class="fas fa-envelope floating" style="animation-delay: 0.6s"></i>
            <div>
               <h4>Email Address</h4>
               <p>info@nepalstore.com</p>
               <p>support@nepalstore.com</p>
            </div>
         </div>
         
         <div class="info-box">
            <i class="fas fa-clock floating" style="animation-delay: 0.8s"></i>
            <div>
               <h4>Working Hours</h4>
               <p>Monday - Friday: 9:00 AM - 8:00 PM</p>
               <p>Saturday - Sunday: 10:00 AM - 6:00 PM</p>
            </div>
         </div>
      </div>
      
      <form action="" method="post" class="contact-form" id="contactForm">
         <h3>Get In Touch</h3>
         <input type="text" name="name" placeholder="Enter your name" required maxlength="50" class="box">
         <input type="email" name="email" placeholder="Enter your email" required maxlength="100" class="box">
         <input type="tel" name="number" placeholder="Enter your number" required pattern="[0-9]{10}" class="box">
         <textarea name="msg" class="box" placeholder="Enter your message" cols="30" rows="10" required maxlength="500"></textarea>
         <input type="submit" value="Send Message" name="send" class="btn">
      </form>
   </div>
   
   <div class="map-container">
      <iframe 
        src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14139.276950075726!2d84.6152765!3d27.612783!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjfCsDM2JzQ2LjAiTiA4NMKwMzYnNTUuMCJF!5e0!3m2!1sen!2snp!4v1715956822000!5m2!1sen!2snp" 
        width="100%" 
        height="450" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade"
        aria-label="Our location on Google Maps">
      </iframe>
   </div>
</section>

<!-- Enhanced Chatbot Container -->
<div class="chatbot-container">
   <div class="chatbot-toggle" id="chatbotToggle" aria-label="Open chat">
      <i class="fas fa-comments"></i>
   </div>
   
   <div class="chatbot-window" id="chatbotWindow">
      <div class="chatbot-header">
         <div class="chatbot-header-content">
            <div class="chatbot-avatar">
               <i class="fas fa-robot"></i>
            </div>
            <div class="chatbot-header-text">
               <h3>Nepal~Store Assistant</h3>
               <p>Online • Ready to help</p>
            </div>
         </div>
         <button class="close-btn" id="closeChatbot" aria-label="Close chat"><i class="fas fa-times"></i></button>
      </div>
      
      <div class="chatbot-body" id="chatbotBody">
         <div class="chat-message bot">
            <div class="message-content">
               Hello! 👋 I'm your Nepal~Store assistant. How can I help you today?
            </div>
            <div class="message-time">Just now</div>
            
            <div class="quick-replies">
               <p>Quick questions:</p>
               <div class="suggested-questions">
                  <div class="quick-reply" data-question="What are your contact details?">Contact details</div>
                  <div class="quick-reply" data-question="What are your working hours?">Working hours</div>
                  <div class="quick-reply" data-question="What products do you offer?">Products</div>
                  <div class="quick-reply" data-question="What is your return policy?">Return policy</div>
               </div>
            </div>
         </div>
      </div>
      
      <div class="chatbot-input-container">
         <div class="chatbot-input">
            <input type="text" id="chatbotInput" placeholder="Type your message here..." aria-label="Type your message">
            <button id="sendMessage" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
         </div>
         <div class="chatbot-actions">
            <button id="clearChat" aria-label="Clear chat"><i class="fas fa-trash-alt"></i></button>
         </div>
      </div>
      
      <div class="ai-toggle">
         <label>
            <span class="toggle-switch">
               <input type="checkbox" id="aiToggle">
               <span class="toggle-slider"></span>
            </span>
            <span>AI Assistant Mode</span>
         </label>
      </div>
   </div>
</div>

<?php include 'components/footer.php'; ?>

<script>
// Enhanced Chatbot functionality with LLM approach
document.addEventListener('DOMContentLoaded', function() {
   const chatbotToggle = document.getElementById('chatbotToggle');
   const chatbotWindow = document.getElementById('chatbotWindow');
   const closeChatbot = document.getElementById('closeChatbot');
   const chatbotBody = document.getElementById('chatbotBody');
   const chatbotInput = document.getElementById('chatbotInput');
   const sendMessage = document.getElementById('sendMessage');
   const aiToggle = document.getElementById('aiToggle');
   const clearChat = document.getElementById('clearChat');
   const quickReplies = document.querySelectorAll('.quick-reply');
   
   // Conversation history for context
   let conversationHistory = [];
   
   // Toggle chatbot window
   chatbotToggle.addEventListener('click', function() {
      chatbotWindow.classList.toggle('active');
      if (chatbotWindow.classList.contains('active')) {
         chatbotInput.focus();
      }
   });
   
   // Close chatbot
   closeChatbot.addEventListener('click', function() {
      chatbotWindow.classList.remove('active');
   });
   
   // Clear chat
   clearChat.addEventListener('click', function() {
      if (confirm('Are you sure you want to clear the conversation?')) {
         // Keep only the first welcome message
         const welcomeMessage = chatbotBody.firstElementChild;
         chatbotBody.innerHTML = '';
         chatbotBody.appendChild(welcomeMessage);
         conversationHistory = [];
      }
   });
   
   // Quick reply buttons
   quickReplies.forEach(reply => {
      reply.addEventListener('click', function() {
         const question = this.getAttribute('data-question');
         addMessage('user', question);
         processMessage(question);
      });
   });
   
   // Define intents with training phrases and responses
   const intents = [
      {
         name: 'greeting',
         phrases: ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'hola', 'howdy'],
         response: 'Hello! 👋 Welcome to Nepal~Store. How can I assist you today?'
      },
      {
         name: 'contact',
         phrases: ['contact', 'email', 'phone', 'number', 'address', 'how to reach', 'get in touch', 'call', 'where are you'],
         response: 'You can reach us at:<br>📞 <strong>Phone:</strong> +977 9840245415<br>📧 <strong>Email:</strong> support@nepalstore.com<br>📍 <strong>Address:</strong> Chitwan, Parsa, Khurkhure 100001'
      },
      {
         name: 'hours',
         phrases: ['hours', 'open', 'close', 'time', 'when do you', 'schedule', 'working hours', 'operating hours'],
         response: 'Our working hours are:<br>📅 <strong>Monday-Friday:</strong> 9AM-8PM<br>📅 <strong>Saturday-Sunday:</strong> 10AM-6PM<br>We\'re here to serve you!'
      },
      {
         name: 'products',
         phrases: ['products', 'items', 'what do you sell', 'offer', 'do you have', 'items available', 'electronics', 'inventory'],
         response: 'We offer a wide range of Nepali electronic tools and products, including:<br>📱 <strong>Mobile phones</strong> & accessories<br>💻 <strong>Laptops</strong> & computers<br>🔌 <strong>Chargers</strong> & adapters<br>🎧 <strong>Headphones</strong> & earbuds<br>🏠 <strong>Home appliances</strong><br>Is there a specific product you\'re looking for?'
      },
      {
         name: 'shipping',
         phrases: ['shipping', 'delivery', 'arrive', 'when will', 'how long', 'ship', 'deliver', 'shipping time', 'delivery options'],
         response: 'We offer various shipping options:<br>🚚 <strong>Standard shipping:</strong> 3-5 business days<br>⚡ <strong>Express shipping:</strong> 1-2 business days<br>🌍 We ship across Nepal and internationally!<br>Shipping costs vary based on location and weight.'
      },
      {
         name: 'returns',
         phrases: ['return', 'exchange', 'refund', 'wrong item', 'broken', 'defective', 'warranty', 'return policy'],
         response: 'We have a customer-friendly return policy:<br>📦 <strong>30-day return policy</strong> for most items<br>✅ Items must be unused and in original packaging<br>🔧 Defective items are covered under warranty<br>Please contact support@nepalstore.com for assistance with returns.'
      },
      {
         name: 'payment',
         phrases: ['cash', 'esewa', 'khalti', 'payment methods', 'how to pay', 'credit card', 'debit card', 'payment options'],
         response: 'We accept various payment methods for your convenience:<br>💵 <strong>Cash on delivery</strong><br>📱 <strong>Esewa</strong> & <strong>Khalti</strong><br>💳 <strong>Credit/Debit cards</strong> (Visa, MasterCard)<br>🏦 <strong>Bank transfer</strong><br>Your transaction security is our priority!'
      },
      {
         name: 'thanks',
         phrases: ['thanks', 'thank you', 'appreciate', 'helpful', 'great', 'awesome', 'thank', 'gracias'],
         response: 'You\'re welcome! 😊 Happy to help. Is there anything else you\'d like to know about our products or services?'
      },
      {
         name: 'help',
         phrases: ['help', 'what can you do', 'assist', 'support', 'functions', 'capabilities'],
         response: 'I can help you with information about:<br>📦 Our products and inventory<br>🕐 Store hours and locations<br>📞 Contact information<br>🚚 Shipping options and delivery times<br>📋 Return policy and warranty<br>💳 Payment methods<br>Just ask me anything! I\'m here to help. 😊'
      },
      {
         name: 'website',
         phrases: ['website', 'online', 'web address', 'url', 'site', 'online store'],
         response: 'Our website is <strong>www.nepalstore.com</strong> 🌐<br>You can browse our full product catalog, place orders, and track deliveries online. Is there something specific you\'re looking for on our website?'
      }
   ];
   
   // Common questions for quick replies
   const commonQuestions = [
      'What are your contact details?',
      'What are your working hours?',
      'What products do you offer?',
      'What is your return policy?',
      'What payment methods do you accept?',
      'Do you ship internationally?',
      'What is your website?'
   ];
   
   // Add message to chat
   function addMessage(sender, message) {
      const messageDiv = document.createElement('div');
      messageDiv.className = 'chat-message ' + sender;
      
      const now = new Date();
      const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                         now.getMinutes().toString().padStart(2, '0');
      
      messageDiv.innerHTML = `
         <div class="message-content">${message}</div>
         <div class="message-time">${timeString}</div>
      `;
      
      chatbotBody.appendChild(messageDiv);
      chatbotBody.scrollTop = chatbotBody.scrollHeight;
      
      // Add to conversation history
      conversationHistory.push({
         sender: sender,
         message: message,
         time: timeString
      });
   }
   
   // Show typing indicator
   function showTyping() {
      const typingDiv = document.createElement('div');
      typingDiv.className = 'typing-indicator';
      typingDiv.id = 'typingIndicator';
      typingDiv.innerHTML = `
         <span></span>
         <span></span>
         <span></span>
      `;
      
      chatbotBody.appendChild(typingDiv);
      chatbotBody.scrollTop = chatbotBody.scrollHeight;
   }
   
   // Hide typing indicator
   function hideTyping() {
      const typingIndicator = document.getElementById('typingIndicator');
      if (typingIndicator) {
         typingIndicator.remove();
      }
   }
   
   // Enhanced pattern matching with context awareness
   function patternMatch(message) {
      const lowerMessage = message.toLowerCase();
      
      // Check conversation history for context
      const lastUserMessage = conversationHistory
         .filter(msg => msg.sender === 'user')
         .slice(-2)
         .map(msg => msg.message.toLowerCase());
      
      // Check for matching intents with context awareness
      for (let i = 0; i < intents.length; i++) {
         const intent = intents[i];
         for (let j = 0; j < intent.phrases.length; j++) {
            if (lowerMessage.indexOf(intent.phrases[j]) !== -1) {
               // Check if we're in a follow-up context
               if (lastUserMessage.some(msg => msg.includes('product') || msg.includes('item')) && 
                   intent.name === 'shipping') {
                  return "For product shipping, we typically dispatch within 24 hours of order confirmation. Most domestic orders arrive within 3-5 business days. Would you like to know about a specific product's availability?";
               }
               
               return intent.response;
            }
         }
      }
      
      // If no match found, use a more intelligent fallback
      if (lowerMessage.includes('how much') || lowerMessage.includes('price') || lowerMessage.includes('cost')) {
         return "For pricing information on specific products, please visit our website at www.nepalstore.com or let me know which product you're interested in, and I'll try to help!";
      }
      
      if (lowerMessage.includes('order') || lowerMessage.includes('track')) {
         return "For order tracking, please provide your order number or email address used for the order. You can also check your order status on our website.";
      }
      
      return "I'm not sure I understand. Could you please rephrase your question? You can ask me about our products, shipping, contact information, or store hours. Or try enabling AI mode for more advanced assistance!";
   }
   
   // Call server-side API for AI responses (LLM approach)
   async function getAIResponse(message) {
      try {
         // In a real implementation, this would call your backend API
         // which would then call Hugging Face or another LLM service
         
         // Simulate API call delay
         await new Promise(resolve => setTimeout(resolve, 1500));
         
         // Enhanced simulated AI responses based on message content
         const lowerMessage = message.toLowerCase();
         
         // Check if we should use a more contextual response based on conversation history
         const context = conversationHistory.slice(-3);
         
         // Special cases for more intelligent responses
         if (lowerMessage.includes('recommend') || lowerMessage.includes('suggest')) {
            return "Based on popular choices, I'd recommend our latest smartphone models or noise-cancelling headphones. Many customers are enjoying the <strong>NepalPhone X5</strong> for its excellent battery life and camera quality. Is there a specific type of product you're interested in?";
         }
         
         if (lowerMessage.includes('best') && lowerMessage.includes('product')) {
            return "Our best-selling products include:<br>1. <strong>NepalPhone X5</strong> - Premium smartphone<br>2. <strong>Himalayan Sound Pro headphones</strong> - Excellent noise cancellation<br>3. <strong>Everest PowerBank 20000mAh</strong> - Fast charging<br>Would you like details on any of these?";
         }
         
         if (lowerMessage.includes('discount') || lowerMessage.includes('offer') || lowerMessage.includes('promotion')) {
            return "We currently have special offers on:<br>🎁 <strong>15% off</strong> on all headphones this week<br>🎁 <strong>Free shipping</strong> on orders over NPR 5000<br>🎁 <strong>Buy one get one 50% off</strong> on phone cases<br>Check our website for all current promotions!";
         }
         
         // Default AI response - more conversational and engaging
         return "Thanks for your question! As an AI assistant, I can tell you that Nepal~Store is committed to providing quality electronics at competitive prices. We focus on customer satisfaction and reliable products. For more specific information, could you provide additional details about what you're looking for?";
         
      } catch (error) {
         console.error('Error calling AI API:', error);
         return "I'm experiencing technical difficulties. Please try again later or use the standard mode for immediate assistance.";
      }
   }
   
   // Process user message
   async function processMessage(message) {
      showTyping();
      
      // Simulate delay for "thinking"
      setTimeout(async () => {
         hideTyping();
         
         let response;
         
         if (aiToggle.checked) {
            // Use AI mode
            response = await getAIResponse(message);
         } else {
            // Use pattern matching mode
            response = patternMatch(message);
         }
         
         addMessage('bot', response);
         
         // Add quick replies after bot response
         setTimeout(addQuickReplies, 300);
      }, 1000 + Math.random() * 1000);
   }
   
   // Add quick replies
   function addQuickReplies() {
      // Remove any existing quick replies
      const existingQuickReplies = document.querySelectorAll('.quick-replies');
      existingQuickReplies.forEach(el => {
         if (el.parentElement.classList.contains('chat-message')) return;
         el.remove();
      });
      
      const quickRepliesDiv = document.createElement('div');
      quickRepliesDiv.className = 'quick-replies';
      quickRepliesDiv.innerHTML = `
         <p>Quick questions:</p>
         <div class="suggested-questions">
      `;
      
      // Add buttons for each common question
      commonQuestions.forEach(question => {
         quickRepliesDiv.innerHTML += `
            <div class="quick-reply" data-question="${question}">${question}</div>
         `;
      });
      
      quickRepliesDiv.innerHTML += `</div>`;
      
      chatbotBody.appendChild(quickRepliesDiv);
      chatbotBody.scrollTop = chatbotBody.scrollHeight;
      
      // Add event listeners to the new quick reply buttons
      document.querySelectorAll('.quick-reply').forEach(reply => {
         reply.addEventListener('click', function() {
            const question = this.getAttribute('data-question');
            addMessage('user', question);
            processMessage(question);
         });
      });
   }
   
   // Send message when button is clicked
   sendMessage.addEventListener('click', function() {
      const message = chatbotInput.value.trim();
      if (message) {
         addMessage('user', message);
         chatbotInput.value = '';
         processMessage(message);
      }
   });
   
   // Send message when Enter is pressed
   chatbotInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
         const message = chatbotInput.value.trim();
         if (message) {
            addMessage('user', message);
            chatbotInput.value = '';
            processMessage(message);
         }
      }
   });
   
   // Add quick replies after initial greeting
   setTimeout(addQuickReplies, 1500);
});
</script>

</body>
</html>