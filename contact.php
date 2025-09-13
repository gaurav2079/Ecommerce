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

      /* Chatbot Styles */
      .chatbot-container {
         position: fixed;
         bottom: 30px;
         right: 30px;
         z-index: 1000;
         width: 350px;
         transition: all 0.3s ease;
      }
      
      .chatbot-toggle {
         position: absolute;
         bottom: 0;
         right: 0;
         width: 60px;
         height: 60px;
         background: linear-gradient(to right, var(--chat-primary), var(--chat-secondary));
         color: white;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         box-shadow: 0 5px 15px rgba(0,0,0,0.2);
         z-index: 2;
         transition: all 0.3s ease;
      }
      
      .chatbot-toggle:hover {
         transform: scale(1.1);
      }
      
      .chatbot-toggle i {
         font-size: 1.5rem;
      }
      
      .chatbot-window {
         background: white;
         border-radius: 15px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.2);
         overflow: hidden;
         transform: translateY(100%);
         opacity: 0;
         height: 0;
         transition: all 0.3s ease;
      }
      
      .chatbot-window.active {
         transform: translateY(0);
         opacity: 1;
         height: 500px;
         margin-bottom: 70px;
      }
      
      .chatbot-header {
         background: linear-gradient(to right, var(--chat-primary), var(--chat-secondary));
         color: white;
         padding: 15px 20px;
         display: flex;
         align-items: center;
         justify-content: space-between;
      }
      
      .chatbot-header h3 {
         margin: 0;
         font-size: 1.2rem;
      }
      
      .chatbot-header .close-btn {
         background: none;
         border: none;
         color: white;
         font-size: 1.2rem;
         cursor: pointer;
      }
      
      .chatbot-body {
         height: 370px;
         overflow-y: auto;
         padding: 15px;
         background: #f8f9fa;
      }
      
      .chat-message {
         margin-bottom: 15px;
         display: flex;
         flex-direction: column;
      }
      
      .chat-message.bot {
         align-items: flex-start;
      }
      
      .chat-message.user {
         align-items: flex-end;
      }
      
      .message-content {
         max-width: 80%;
         padding: 10px 15px;
         border-radius: 18px;
         margin-bottom: 5px;
         position: relative;
      }
      
      .bot .message-content {
         background: white;
         color: var(--dark);
         border-bottom-left-radius: 5px;
         box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }
      
      .user .message-content {
         background: var(--chat-primary);
         color: white;
         border-bottom-right-radius: 5px;
      }
      
      .message-time {
         font-size: 0.7rem;
         color: #777;
      }
      
      .chatbot-input {
         display: flex;
         padding: 15px;
         background: white;
         border-top: 1px solid #eee;
      }
      
      .chatbot-input input {
         flex: 1;
         padding: 10px 15px;
         border: 1px solid #ddd;
         border-radius: 30px;
         outline: none;
         font-size: 14px;
      }
      
      .chatbot-input button {
         background: var(--chat-primary);
         color: white;
         border: none;
         border-radius: 50%;
         width: 40px;
         height: 40px;
         margin-left: 10px;
         cursor: pointer;
         transition: all 0.3s ease;
      }
      
      .chatbot-input button:hover {
         background: var(--chat-secondary);
      }
      
      .typing-indicator {
         display: flex;
         padding: 10px 15px;
         align-items: center;
      }
      
      .typing-indicator span {
         height: 8px;
         width: 8px;
         background: #ccc;
         border-radius: 50%;
         display: inline-block;
         margin: 0 2px;
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
         padding: 10px;
         background: white;
         border-radius: 10px;
         box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }
      
      .quick-replies p {
         margin-bottom: 8px;
         font-size: 0.9rem;
         color: #666;
      }
      
      .quick-reply {
         display: inline-block;
         margin: 4px;
         padding: 6px 12px;
         background: #eef2ff;
         border: 1px solid #dbe4ff;
         border-radius: 15px;
         font-size: 0.8rem;
         cursor: pointer;
         transition: all 0.2s ease;
      }
      
      .quick-reply:hover {
         background: #e0e7ff;
         transform: translateY(-2px);
      }
      
      .ai-toggle {
         display: flex;
         align-items: center;
         justify-content: center;
         padding: 8px;
         background: #f8f9fa;
         border-top: 1px solid #eee;
         font-size: 0.8rem;
         color: #666;
      }
      
      .ai-toggle label {
         display: flex;
         align-items: center;
         cursor: pointer;
      }
      
      .ai-toggle input {
         margin-right: 5px;
      }
      
      @keyframes typing {
         0%, 100% { transform: translateY(0); opacity: 0.6; }
         50% { transform: translateY(-5px); opacity: 1; }
      }
      
      @keyframes fadeIn {
         from { opacity: 0; transform: translateY(10px); }
         to { opacity: 1; transform: translateY(0); }
      }
      
      /* Responsive adjustments */
      @media (max-width: 768px) {
         .chatbot-container {
            width: 300px;
            right: 15px;
            bottom: 15px;
         }
         
         .chatbot-window.active {
            height: 400px;
         }
         
         .chatbot-body {
            height: 270px;
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

<!-- Chatbot Container -->
<div class="chatbot-container">
   <div class="chatbot-toggle" id="chatbotToggle" aria-label="Open chat">
      <i class="fas fa-comment-dots"></i>
   </div>
   
   <div class="chatbot-window" id="chatbotWindow">
      <div class="chatbot-header">
         <h3>Nepal~Store Assistant</h3>
         <button class="close-btn" id="closeChatbot" aria-label="Close chat"><i class="fas fa-times"></i></button>
      </div>
      
      <div class="chatbot-body" id="chatbotBody">
         <div class="chat-message bot">
            <div class="message-content">
               Hello! I'm your Nepal~Store assistant. How can I help you today?
            </div>
            <div class="message-time">Just now</div>
         </div>
      </div>
      
      <div class="chatbot-input">
         <input type="text" id="chatbotInput" placeholder="Type your message here..." aria-label="Type your message">
         <button id="sendMessage" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
      </div>
      
      <div class="ai-toggle">
         <label>
            <input type="checkbox" id="aiToggle"> Use AI mode (Hugging Face)
         </label>
      </div>
   </div>
</div>

<?php include 'components/footer.php'; ?>

<script>
// Chatbot functionality with pattern matching algorithm
document.addEventListener('DOMContentLoaded', function() {
   const chatbotToggle = document.getElementById('chatbotToggle');
   const chatbotWindow = document.getElementById('chatbotWindow');
   const closeChatbot = document.getElementById('closeChatbot');
   const chatbotBody = document.getElementById('chatbotBody');
   const chatbotInput = document.getElementById('chatbotInput');
   const sendMessage = document.getElementById('sendMessage');
   const aiToggle = document.getElementById('aiToggle');
   
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
   
   // Define intents with training phrases and responses
   const intents = [
      {
         name: 'greeting',
         phrases: ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening'],
         response: 'Hello! Welcome to Nepal~Store. How can I assist you today?'
      },
      {
         name: 'contact',
         phrases: ['contact', 'email', 'phone', 'number', 'address', 'how to reach', 'get in touch'],
         response: 'You can reach us at:<br>- Phone: +977 9840245415<br>- Email: support@nepalstore.com<br>- Address: Chitwan, Parsa, Khurkhure 100001'
      },
      {
         name: 'hours',
         phrases: ['hours', 'open', 'close', 'time', 'when do you', 'schedule', 'working hours'],
         response: 'Our working hours are:<br>Monday-Friday: 9AM-8PM<br>Saturday-Sunday: 10AM-6PM'
      },
      {
         name: 'products',
         phrases: ['products', 'items', 'what do you sell', 'offer', 'do you have', 'items available'],
         response: 'We offer a wide range of Nepali electronic tools and products, including:<br>- Mobile phones<br>- Laptops<br>- Chargers & Adapters<br>- Headphones & Earbuds<br>- Home appliances'
      },
      {
         name: 'shipping',
         phrases: ['shipping', 'delivery', 'arrive', 'when will', 'how long', 'ship', 'deliver'],
         response: 'We offer:<br>- Standard shipping: 3-5 business days<br>- Express shipping: 1-2 business days<br>We ship across Nepal and internationally!'
      },
      {
         name: 'returns',
         phrases: ['return', 'exchange', 'refund', 'wrong item', 'broken', 'defective'],
         response: 'We have a 30-day return policy. Items must be unused and in original packaging. Please contact support@nepalstore.com for assistance.'
      },
      {
         name: 'payment',
         phrases: ['cash', 'esewa', 'khalti', 'payment methods'],
         response: 'We accept various payment methods:<br>- Cash on delivery<br>- Esewa<br>- Khalti'
      },
      {
         name: 'thanks',
         phrases: ['thanks', 'thank you', 'appreciate', 'helpful', 'great', 'awesome'],
         response: 'You\'re welcome! Happy to help. Is there anything else you\'d like to know?'
      },
      {
         name: 'help',
         phrases: ['help', 'what can you do', 'assist', 'support'],
         response: 'I can help you with information about:<br>- Our products<br>- Store hours<br>- Contact information<br>- Shipping options<br>- Return policy<br>- Payment methods<br>Just ask me anything!'
      }
   ];
   
   // Common questions for quick replies
   const commonQuestions = [
      'What are your contact details?',
      'What are your working hours?',
      'What products do you offer?',
      'What is your return policy?',
      'What payment methods do you accept?',
      'Do you ship internationally?'
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
   
   // Simple pattern matching algorithm
   function patternMatch(message) {
      const lowerMessage = message.toLowerCase();
      
      // Check for matching intents
      for (let i = 0; i < intents.length; i++) {
         const intent = intents[i];
         for (let j = 0; j < intent.phrases.length; j++) {
            if (lowerMessage.indexOf(intent.phrases[j]) !== -1) {
               return intent.response;
            }
         }
      }
      
      return "I'm not sure I understand. Could you please rephrase your question? You can ask me about our products, shipping, contact information, or store hours.";
   }
   
   // Call server-side API for AI responses
   async function getAIResponse(message) {
      try {
         const response = await fetch('chatbot-api.php', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(message)
         });
         
         const data = await response.json();
         
         if (data && data.response) {
            return data.response;
         } else {
            return "I'm having trouble connecting to the AI service. Please try again later.";
         }
      } catch (error) {
         console.error('Error calling AI API:', error);
         return "I'm experiencing technical difficulties. Please try again later.";
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
      const existingQuickReplies = document.querySelector('.quick-replies');
      if (existingQuickReplies) {
         existingQuickReplies.remove();
      }
      
      const quickRepliesDiv = document.createElement('div');
      quickRepliesDiv.className = 'quick-replies';
      quickRepliesDiv.innerHTML = `
         <p>Quick questions:</p>
      `;
      
      // Add buttons for each common question
      for (let i = 0; i < commonQuestions.length; i++) {
         const button = document.createElement('button');
         button.className = 'quick-reply';
         button.textContent = commonQuestions[i];
         button.addEventListener('click', function() {
            const question = this.textContent;
            addMessage('user', question);
            processMessage(question);
         });
         quickRepliesDiv.appendChild(button);
      }
      
      chatbotBody.appendChild(quickRepliesDiv);
      chatbotBody.scrollTop = chatbotBody.scrollHeight;
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