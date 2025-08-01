<style>
/* Brighter Footer Styles */
.footer {
   background: linear-gradient(135deg, #6a89cc, #a4b0be);
   padding: 5rem 2rem 2rem;
   color: #fff;
   position: relative;
   overflow: hidden;
}

.footer::before {
   content: '';
   position: absolute;
   top: 0;
   left: 0;
   width: 100%;
   height: 100%;
   background: url('images/footer-pattern.png') repeat;
   opacity: 0.05;
   z-index: 0;
}

.footer .grid {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
   gap: 3rem;
   max-width: 1200px;
   margin: 0 auto;
   position: relative;
   z-index: 1;
}

.footer .box {
   padding: 2rem;
   transition: all 0.3s ease;
   color: #f9f9f9;
}

.footer .box:hover {
   transform: translateY(-5px);
}

.footer .box h3 {
   font-size: 2rem;
   margin-bottom: 2rem;
   position: relative;
   padding-bottom: 1rem;
   color: #ffffff;
}

.footer .box h3::after {
   content: '';
   position: absolute;
   bottom: 0;
   left: 0;
   width: 50px;
   height: 3px;
   background: #ffe66d;
}

.footer .box a {
   display: block;
   font-size: 1.5rem;
   color: #f0f0f0;
   padding: 0.8rem 0;
   transition: all 0.3s ease;
}

.footer .box a:hover {
   color: #ffe66d;
   padding-left: 1rem;
}

.footer .box a i {
   margin-right: 1rem;
   transition: all 0.3s ease;
}

.footer .box a:hover i {
   transform: rotate(90deg);
   color: #ffe66d;
}

.footer .credit {
   text-align: center;
   padding-top: 3rem;
   margin-top: 3rem;
   font-size: 1.5rem;
   color: #f1f1f1;
   border-top: 1px solid rgba(255,255,255,0.2);
   position: relative;
   z-index: 1;
}

.footer .credit span {
   color: #ffe66d;
   font-weight: 600;
}

.footer .social-icons {
   display: flex;
   gap: 1.5rem;
   margin-top: 2rem;
}

.footer .social-icons a {
   width: 40px;
   height: 40px;
   background: rgba(255,255,255,0.2);
   border-radius: 50%;
   display: flex;
   align-items: center;
   justify-content: center;
   color: white;
   font-size: 1.8rem;
   transition: all 0.3s ease;
}

.footer .social-icons a:hover {
   background: #ffe66d;
   color: #2c3e50;
   transform: translateY(-5px) scale(1.1);
}

/* Newsletter Corner Styles - Top Left (Different from other pages) */
.newsletter-corner.top-left {
  position: fixed;
  top: 30px;
  left: 30px;
  z-index: 1000;
}

.newsletter-toggle {
  width: 60px;
  height: 60px;
  background: linear-gradient(45deg, #ff6b00, #ff8e53);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 5px 25px rgba(255,107,0,0.3);
  position: relative;
  transition: all 0.3s ease;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0) rotate(-5deg); }
  50% { transform: translateY(-10px) rotate(5deg); }
}

.newsletter-toggle:hover {
  transform: scale(1.1) rotate(15deg);
  background: linear-gradient(45deg, #ff8e53, #ff6b00);
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #4361ee;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.newsletter-box {
  position: absolute;
  top: 70px;
  left: 0;
  width: 320px;
  background: white;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.15);
  transform: translateY(-20px);
  opacity: 0;
  pointer-events: none;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  border: 1px solid rgba(0,0,0,0.05);
}

.newsletter-corner.active .newsletter-box {
  transform: translateY(0);
  opacity: 1;
  pointer-events: auto;
}

.newsletter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.newsletter-header h3 {
  color: #ff6b00;
  margin: 0;
  font-size: 20px;
  font-weight: 700;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #aaa;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #ff6b00;
}

.newsletter-form input {
  width: 100%;
  padding: 12px 15px;
  margin: 10px 0;
  border: 2px solid #eee;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s;
}

.newsletter-form input:focus {
  border-color: #ff6b00;
  outline: none;
  box-shadow: 0 0 0 3px rgba(255,107,0,0.1);
}

.newsletter-form .btn {
  width: 100%;
  background: linear-gradient(45deg, #4361ee, #3f37c9);
  color: white;
  border: none;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s;
}

.newsletter-form .btn:hover {
  background: linear-gradient(45deg, #3f37c9, #4361ee);
  transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
   .footer .grid {
      grid-template-columns: 1fr 1fr;
   }
   
   .newsletter-corner.top-left {
     top: 20px;
     left: 20px;
   }
   
   .newsletter-box {
     width: 280px;
   }
}

@media (max-width: 480px) {
   .footer .grid {
      grid-template-columns: 1fr;
   }

   .footer .box {
      text-align: center;
   }

   .footer .box h3::after {
      left: 50%;
      transform: translateX(-50%);
   }

   .footer .social-icons {
      justify-content: center;
   }
   
   .newsletter-corner.top-left {
     top: 15px;
     left: 15px;
   }
   
   .newsletter-toggle {
     width: 50px;
     height: 50px;
     font-size: 20px;
   }
   
   .newsletter-box {
     width: 260px;
   }
}
</style>

<footer class="footer">
   <section class="grid">
      <div class="box animate__animated animate__fadeInUp">
         <h3>Quick Links</h3>
         <a href="home.php"><i class="fas fa-angle-right"></i> Home</a>
         <a href="about.php"><i class="fas fa-angle-right"></i> About</a>
         <a href="shop.php"><i class="fas fa-angle-right"></i> Shop</a>
         <a href="contact.php"><i class="fas fa-angle-right"></i> Contact</a>
      </div>

      <div class="box animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
         <h3>Account</h3>
         <a href="user_login.php"><i class="fas fa-angle-right"></i> Login</a>
         <a href="user_register.php"><i class="fas fa-angle-right"></i> Register</a>
         <a href="cart.php"><i class="fas fa-angle-right"></i> Cart</a>
         <a href="orders.php"><i class="fas fa-angle-right"></i> Orders</a>
      </div>

      <div class="box animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
         <h3>Contact Us</h3>
         <a href="tel:9840245415"><i class="fas fa-phone"></i> +977 9840245415</a>
         <a href="tel:9840245415"><i class="fas fa-phone"></i> +977 9840245415</a>
         <a href="mailto:gkstore@gmail.com"><i class="fas fa-envelope"></i> nepalstore@gmail.com</a>
         <a href="https://www.google.com/maps" target="_blank"><i class="fas fa-map-marker-alt"></i> Chitwan, Nepal</a>
      </div>

      <div class="box animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
         <h3>Follow Us</h3>
         <p>Stay connected with us on social media</p>
         <div class="social-icons">
            <a href="#" class="fab fa-facebook-f"></a>
            <a href="#" class="fab fa-twitter"></a>
            <a href="#" class="fab fa-instagram"></a>
            <a href="#" class="fab fa-linkedin-in"></a>
            <a href="#" class="fab fa-youtube"></a>
         </div>
         
         <div class="payment-methods" style="margin-top: 2rem;">
            <h4>We Accept:</h4>
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
               <i class="fab fa-cc-visa" style="font-size: 2.5rem;"></i>
               <i class="fab fa-cc-mastercard" style="font-size: 2.5rem;"></i>
               <i class="fab fa-cc-paypal" style="font-size: 2.5rem;"></i>
            </div>
         </div>
      </div>
   </section>

   <!-- Top-Left Newsletter (Different from other pages) -->
   <div class="newsletter-corner top-left">
     <div class="newsletter-toggle">
       <i class="fas fa-gift"></i>
       <span class="notification-badge">NEW</span>
     </div>
     
     <div class="newsletter-box">
       <div class="newsletter-header">
         <h3>Special Offer!</h3>
         <button class="close-btn">&times;</button>
       </div>
       <p>Subscribe now and get <strong>10% OFF</strong> your first purchase!</p>
       <form class="newsletter-form">
         <input type="email" placeholder="Enter your email" required>
         <button type="submit" class="btn">
           <i class="fas fa-paper-plane"></i> Claim Discount
         </button>
       </form>
     </div>
   </div>

   <div class="credit">
      &copy; <?= date('Y'); ?> <span>Neal~Store</span>. All Rights Reserved. | 
      Designed by <i class="fas fa-heart" style="color: #ff6b00;"></i> <span>Gaurav Kandel & Aashish Thapa</span>
   </div>
</footer>

<script>
// Newsletter Functionality
document.addEventListener('DOMContentLoaded', function() {
   const newsletter = document.querySelector('.newsletter-corner.top-left');
   const toggle = newsletter.querySelector('.newsletter-toggle');
   const closeBtn = newsletter.querySelector('.close-btn');
   const form = newsletter.querySelector('.newsletter-form');

   // Toggle newsletter box
   toggle.addEventListener('click', () => {
      newsletter.classList.toggle('active');
   });

   closeBtn.addEventListener('click', () => {
      newsletter.classList.remove('active');
   });

   // Form submission
   form.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = form.querySelector('input').value;
      
      // Simulate successful submission
      form.innerHTML = `
        <div style="text-align: center; padding: 10px 0;">
          <i class="fas fa-check-circle" style="font-size: 40px; color: #4cc9f0;"></i>
          <h3 style="margin: 10px 0; color: #4361ee;">Thank You!</h3>
          <p>Check your email for your discount code</p>
        </div>
      `;
      
      // Hide after 3 seconds
      setTimeout(() => {
        newsletter.classList.remove('active');
        // Reset form after animation
        setTimeout(() => {
          form.innerHTML = `
            <input type="email" placeholder="Enter your email" required>
            <button type="submit" class="btn">
              <i class="fas fa-paper-plane"></i> Claim Discount
            </button>
          `;
        }, 300);
      }, 3000);
   });
   
   // Footer animations
   const footerBoxes = document.querySelectorAll('.footer .box');
   footerBoxes.forEach((box, index) => {
      box.style.animationDelay = `${index * 0.1}s`;
   });
});
</script>