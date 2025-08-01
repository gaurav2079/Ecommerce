<?php
include 'components/connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? '';

if(isset($_POST['send'])){
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_STRING);
   $msg = $_POST['msg'];
   $msg = filter_var($msg, FILTER_SANITIZE_STRING);

   $select_message = $conn->prepare("SELECT * FROM `messages` WHERE name = ? AND email = ? AND number = ? AND message = ?");
   $select_message->execute([$name, $email, $number, $msg]);

   if($select_message->rowCount() > 0){
      $message[] = 'Message already sent!';
   }else{
      $insert_message = $conn->prepare("INSERT INTO `messages`(user_id, name, email, number, message) VALUES(?,?,?,?,?)");
      $insert_message->execute([$user_id, $name, $email, $number, $msg]);
      $message[] = 'Message sent successfully!';
   }
}
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

   <!-- Animate.css -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

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
         animation: fadeInLeft 1s ease;
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
         animation: fadeInRight 1s ease;
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
         animation: fadeInUp 1s ease;
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
         animation: fadeIn 0.3s ease;
      }
      
      .bot .message-content {
         background: white;
         color: var(--dark);
         border-bottom-left-radius: 5px;
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
         animation: typing 1s infinite ease-in-out;
      }
      
      .typing-indicator span:nth-child(1) {
         animation-delay: 0s;
      }
      
      .typing-indicator span:nth-child(2) {
         animation-delay: 0.2s;
      }
      
      .typing-indicator span:nth-child(3) {
         animation-delay: 0.4s;
      }
      
      @keyframes typing {
         0%, 100% { transform: translateY(0); }
         50% { transform: translateY(-5px); }
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
   <div class="contact-container">
      <div class="contact-info">
         <h3>Contact Information</h3>
         
         <div class="info-box">
            <i class="fas fa-map-marker-alt floating" style="animation-delay: 0.2s"></i>
            <div>
               <h4>Our Location</h4>
               <p> Chitwan,Parsa,Khurkhure 100001</p>
            </div>
         </div>
         
         <div class="info-box">
            <i class="fas fa-phone-alt floating" style="animation-delay: 0.4s"></i>
            <div>
               <h4>Phone Number</h4>
               <p>+977___________</p>
               <p>+977___________ </p>
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
      
      <form action="" method="post" class="contact-form">
         <h3>Get In Touch</h3>
         <input type="text" name="name" placeholder="Enter your name" required maxlength="20" class="box">
         <input type="email" name="email" placeholder="Enter your email" required maxlength="50" class="box">
         <input type="number" name="number" placeholder="Enter your number" required onkeypress="if(this.value.length == 10) return false;" class="box">
         <textarea name="msg" class="box" placeholder="Enter your message" cols="30" rows="10"></textarea>
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
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
   </div>
</section>

<!-- Chatbot Container -->
<div class="chatbot-container">
   <div class="chatbot-toggle" id="chatbotToggle">
      <i class="fas fa-comment-dots"></i>
   </div>
   
   <div class="chatbot-window" id="chatbotWindow">
      <div class="chatbot-header">
         <h3>Nepal~Store Assistant</h3>
         <button class="close-btn" id="closeChatbot"><i class="fas fa-times"></i></button>
      </div>
      
      <div class="chatbot-body" id="chatbotBody">
         <div class="chat-message bot">
            <div class="message-content">
               Hello! I'm your Nepal~Store assistant. How can I help you today?
            </div>
            <div class="message-time">Just now</div>
         </div>
         
         <!-- More messages will be added here dynamically -->
      </div>
      
      <div class="chatbot-input">
         <input type="text" id="chatbotInput" placeholder="Type your message here...">
         <button id="sendMessage"><i class="fas fa-paper-plane"></i></button>
      </div>
   </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

<script>
// Chatbot functionality
document.addEventListener('DOMContentLoaded', function() {
   const chatbotToggle = document.getElementById('chatbotToggle');
   const chatbotWindow = document.getElementById('chatbotWindow');
   const closeChatbot = document.getElementById('closeChatbot');
   const chatbotBody = document.getElementById('chatbotBody');
   const chatbotInput = document.getElementById('chatbotInput');
   const sendMessage = document.getElementById('sendMessage');
   
   // Toggle chatbot window
   chatbotToggle.addEventListener('click', function() {
      chatbotWindow.classList.toggle('active');
   });
   
   // Close chatbot
   closeChatbot.addEventListener('click', function() {
      chatbotWindow.classList.remove('active');
   });
   
   // Sample responses
   const responses = {
      'hello': 'Hello there! How can I assist you with your Nepal~Store experience today?',
      'hi': 'Hi! Welcome to Nepal~Store. What can I help you with?',
      'contact': 'You can reach us at:<br>- Phone: +977 9840245415<br>- Email: support@Nepal~Store.com<br>- Address: 123 Shopping Street',
      'hours': 'Our working hours are:<br>Monday-Friday: 9AM-8PM<br>Saturday-Sunday: 10AM-6PM',
      'order': 'For order inquiries, please provide your order number and we\'ll check the status for you.',
      'return': 'Our return policy allows returns within 30 days of purchase. Please contact support for assistance.',
      'shipping': 'We offer standard shipping (3-5 days) and express shipping (1-2 days). Shipping costs vary by location.',
      'payment': 'We accept cash on delivery and esewa.',
      'default': 'I\'m sorry, I didn\'t understand that. Could you please rephrase your question? Here are some things I can help with:<br>- Order status<br>- Returns<br>- Shipping info<br>- Payment methods<br>- Contact information'
   };
   
   // Common questions
   // const commonQuestions = [
   //    'What are your contact details?',
   //    'What are your working hours?',
   //    'How can I track my order?',
   //    'What is your return policy?',
   //    'What payment methods do you accept?'
   // ];
   
   // Add message to chat
   function addMessage(sender, message) {
      const messageDiv = document.createElement('div');
      messageDiv.className = `chat-message ${sender}`;
      
      const now = new Date();
      const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      
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
   
   // Process user message
   function processMessage(message) {
      showTyping();
      
      // Simulate delay for "thinking"
      setTimeout(() => {
         hideTyping();
         
         const lowerMessage = message.toLowerCase();
         let response = responses['default'];
         
         // Check for matching responses
         if (lowerMessage.includes('hello') || lowerMessage.includes('hi')) {
            response = responses['hello'];
         } else if (lowerMessage.includes('contact') || lowerMessage.includes('email') || lowerMessage.includes('phone')) {
            response = responses['contact'];
         } else if (lowerMessage.includes('hour') || lowerMessage.includes('time') || lowerMessage.includes('open')) {
            response = responses['hours'];
         } else if (lowerMessage.includes('order') || lowerMessage.includes('status') || lowerMessage.includes('track')) {
            response = responses['order'];
         } else if (lowerMessage.includes('return') || lowerMessage.includes('refund')) {
            response = responses['return'];
         } else if (lowerMessage.includes('shipping') || lowerMessage.includes('delivery')) {
            response = responses['shipping'];
         } else if (lowerMessage.includes('payment') || lowerMessage.includes('pay') || lowerMessage.includes('credit card')) {
            response = responses['payment'];
         }
         
         addMessage('bot', response);
      }, 1000 + Math.random() * 1000);
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
   
   // Add common questions as quick replies
   function addQuickReplies() {
      const quickRepliesDiv = document.createElement('div');
      quickRepliesDiv.className = 'quick-replies';
      quickRepliesDiv.innerHTML = `
         <p>Quick questions:</p>
         ${commonQuestions.map(q => `<button class="quick-reply">${q}</button>`).join('')}
      `;
      
      chatbotBody.appendChild(quickRepliesDiv);
      chatbotBody.scrollTop = chatbotBody.scrollHeight;
      
      // Add click handlers to quick replies
      document.querySelectorAll('.quick-reply').forEach(button => {
         button.addEventListener('click', function() {
            const question = this.textContent;
            addMessage('user', question);
            processMessage(question);
         });
      });
   }
   
   // Add quick replies after initial greeting
   setTimeout(addQuickReplies, 1500);
});

// Add animation to form elements with delay
document.addEventListener('DOMContentLoaded', function() {
   const formElements = document.querySelectorAll('.contact-form .box, .contact-form .btn');
   
   formElements.forEach((element, index) => {
      element.style.animationDelay = `${index * 0.1}s`;
   });
   
   // Add hover effect to info boxes
   const infoBoxes = document.querySelectorAll('.info-box');
   infoBoxes.forEach(box => {
      box.addEventListener('mouseenter', function() {
         this.querySelector('i').style.transform = 'scale(1.1)';
      });
      
      box.addEventListener('mouseleave', function() {
         this.querySelector('i').style.transform = 'scale(1)';
      });
   });
});
</script>

</body>
</html>