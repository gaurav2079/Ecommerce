<?php
include 'components/connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>About Us | Nepal~Store</title>

   <!-- Swiper CSS -->
   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   
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
      }
      
      /* About Section */
      .about {
         padding: 5rem 0;
         background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      }
      
      .about .row {
         display: flex;
         align-items: center;
         flex-wrap: wrap;
         gap: 3rem;
      }
      
      .about .row .image {
         flex: 1 1 40rem;
         overflow: hidden;
         border-radius: 15px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.1);
         animation: fadeInLeft 1s ease;
      }
      
      .about .row .image img {
         width: 100%;
         height: 100%;
         object-fit: cover;
         transition: transform 0.5s ease;
      }
      
      .about .row .image:hover img {
         transform: scale(1.05);
      }
      
      .about .row .content {
         flex: 1 1 40rem;
         animation: fadeInRight 1s ease;
      }
      
      .about .row .content h3 {
         font-size: 3rem;
         color: var(--dark);
         margin-bottom: 1.5rem;
      }
      
      .about .row .content p {
         font-size: 1.6rem;
         line-height: 1.8;
         color: #666;
         padding: 1rem 0;
      }
      
      .about .row .content .btn {
         display: inline-block;
         margin-top: 1rem;
         background: linear-gradient(to right, var(--primary), var(--secondary));
         color: white;
         padding: 1rem 3rem;
         border-radius: 50px;
         transition: all 0.3s ease;
         box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
      }
      
      .about .row .content .btn:hover {
         transform: translateY(-5px);
         box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
      }
      
      /* Stats Section */
      .stats {
         padding: 5rem 0;
         background: linear-gradient(135deg, var(--primary), var(--secondary));
         color: white;
         text-align: center;
      }
      
      .stats .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr));
         gap: 2rem;
      }
      
      .stats .box {
         padding: 3rem;
         background: rgba(255,255,255,0.1);
         border-radius: 15px;
         backdrop-filter: blur(5px);
         border: 1px solid rgba(255,255,255,0.2);
         transition: all 0.3s ease;
      }
      
      .stats .box:hover {
         transform: translateY(-10px);
         background: rgba(255,255,255,0.2);
      }
      
      .stats .box i {
         font-size: 4rem;
         margin-bottom: 1.5rem;
         color: white;
      }
      
      .stats .box h3 {
         font-size: 4rem;
         margin-bottom: 0.5rem;
      }
      
      .stats .box p {
         font-size: 1.6rem;
         opacity: 0.9;
      }
      
      /* Team Section */
      .team {
         padding: 5rem 0;
         background: var(--light);
      }
      
      .team .heading {
         text-align: center;
         margin-bottom: 4rem;
      }
      
      .team .heading h1 {
         font-size: 3.5rem;
         color: var(--dark);
         margin-bottom: 1rem;
      }
      
      .team .heading p {
         font-size: 1.6rem;
         color: #666;
         max-width: 700px;
         margin: 0 auto;
      }
      
      .team .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr));
         gap: 3rem;
      }
      
      .team .box {
         background: white;
         border-radius: 15px;
         overflow: hidden;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
         transition: all 0.3s ease;
         text-align: center;
         padding: 3rem 2rem;
      }
      
      .team .box:hover {
         transform: translateY(-10px);
         box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      }
      
      .team .box img {
         width: 15rem;
         height: 15rem;
         object-fit: cover;
         border-radius: 50%;
         margin-bottom: 1.5rem;
         border: 5px solid var(--primary);
      }
      
      .team .box h3 {
         font-size: 2rem;
         color: var(--dark);
         margin-bottom: 0.5rem;
      }
      
      .team .box span {
         font-size: 1.4rem;
         color: var(--accent);
         display: block;
         margin-bottom: 1.5rem;
      }
      
      .team .box .share {
         display: flex;
         justify-content: center;
         gap: 1rem;
      }
      
      .team .box .share a {
         width: 4rem;
         height: 4rem;
         line-height: 4rem;
         text-align: center;
         background: #f0f0f0;
         border-radius: 50%;
         font-size: 1.6rem;
         color: var(--dark);
         transition: all 0.3s ease;
      }
      
      .team .box .share a:hover {
         background: var(--primary);
         color: white;
      }
      
      /* Reviews Section */
      .reviews {
         padding: 5rem 0;
         background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      }
      
      .reviews .heading {
         text-align: center;
         margin-bottom: 4rem;
      }
      
      .reviews .heading h1 {
         font-size: 3.5rem;
         color: var(--dark);
         margin-bottom: 1rem;
      }
      
      .reviews .slide {
         background: white;
         border-radius: 15px;
         padding: 3rem;
         box-shadow: 0 5px 15px rgba(0,0,0,0.1);
         transition: all 0.3s ease;
         text-align: center;
         margin-bottom: 3rem;
      }
      
      .reviews .slide:hover {
         transform: translateY(-10px);
         box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      }
      
      .reviews .slide img {
         width: 10rem;
         height: 10rem;
         object-fit: cover;
         border-radius: 50%;
         margin-bottom: 1.5rem;
         border: 5px solid var(--primary);
      }
      
      .reviews .slide p {
         font-size: 1.6rem;
         line-height: 1.8;
         color: #666;
         margin-bottom: 1.5rem;
      }
      
      .reviews .slide .stars {
         margin-bottom: 1.5rem;
      }
      
      .reviews .slide .stars i {
         font-size: 1.8rem;
         color: #ffc107;
      }
      
      .reviews .slide h3 {
         font-size: 2rem;
         color: var(--dark);
      }
      
      /* Responsive */
      @media (max-width: 768px) {
         .about .row {
            flex-direction: column;
         }
         
         .about .row .image {
            flex: 1 1 100%;
         }
         
         .about .row .content {
            flex: 1 1 100%;
            text-align: center;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- About Section -->
<section class="about">
   <div class="row">
      <div class="image animate__animated animate__fadeInLeft">
         <img src="images/about-img.svg" alt="About Us">
      </div>
      <div class="content animate__animated animate__fadeInRight">
         <h3>Why Choose Us?</h3>
         <p>At Nepal~Store, we're committed to providing the best shopping experience with high-quality products, competitive prices, and exceptional customer service. Our team works tirelessly to ensure your complete satisfaction.</p>
         <p>We source products directly from trusted manufacturers to guarantee authenticity and offer a 30-day money-back guarantee on all purchases.</p>
         <a href="contact.php" class="btn">Contact Us</a>
      </div>
   </div>
</section>


<!-- Team Section -->
<section class="team">
   <div class="heading animate__animated animate__fadeIn">
      <h1>Meet Our Team</h1>
      <p>Our dedicated team of professionals works around the clock to ensure you get the best shopping experience</p>
   </div>
   
   <div class="box-container">
      <div class="box animate__animated animate__fadeInUp" data-wow-delay="0.1s">
         <img src="images/gk.png" alt="Team Member">
         <h3>Gaurav Kandel</h3>
         <span>Handle frontend and backend</span>
         <div class="share">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
         </div>
      </div>
      
      <div class="box animate__animated animate__fadeInUp" data-wow-delay="0.3s">
         <img src="images/aaa.jpg" alt="Team Member">
         <h3>Aashish Thapa</h3>
         <span>Handle UI/UX and Frontend</span>
         <div class="share">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
         </div>
      </div>
      
      
      </div>
   </div>
</section>

<!-- Reviews Section -->
<section class="reviews">
   <div class="heading animate__animated animate__fadeIn">
      <h1>Client's Reviews</h1>
      <p>What our customers say about us</p>
   </div>

   <div class="swiper reviews-slider">
      <div class="swiper-wrapper">
         <div class="swiper-slide slide animate__animated animate__fadeIn">
            <img src="images/pic-1.png" alt="Customer Review">
            <p>"The best online shopping experience I've ever had! Fast delivery and excellent customer service. Will definitely shop here again!"</p>
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
            </div>
            <h3>John Deo</h3>
         </div>

         <div class="swiper-slide slide animate__animated animate__fadeIn">
            <img src="images/pic-2.png" alt="Customer Review">
            <p>"Amazing product quality and the prices are very competitive. I'm impressed with their return policy too!"</p>
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star-half-alt"></i>
            </div>
            <h3>Sarah Smith</h3>
         </div>

         <div class="swiper-slide slide animate__animated animate__fadeIn">
            <img src="images/pic-3.png" alt="Customer Review">
            <p>"The customer support team was very helpful when I had questions about my order. Great communication throughout the process."</p>
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
            </div>
            <h3>Michael Johnson</h3>
         </div>

         <div class="swiper-slide slide animate__animated animate__fadeIn">
            <img src="images/pic-4.png" alt="Customer Review">
            <p>"I've ordered multiple times and never been disappointed. The products always match the descriptions perfectly."</p>
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
            </div>
            <h3>Emily Brown</h3>
         </div>

         <div class="swiper-slide slide animate__animated animate__fadeIn">
            <img src="images/pic-5.png" alt="Customer Review">
            <p>"Fast shipping and excellent packaging. My items arrived in perfect condition. Will recommend to friends!"</p>
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star-half-alt"></i>
            </div>
            <h3>David Wilson</h3>
         </div>

         <div class="swiper-slide slide animate__animated animate__fadeIn">
            <img src="images/pic-6.png" alt="Customer Review">
            <p>"The website is easy to navigate and the checkout process was smooth. Received my order sooner than expected!"</p>
            <div class="stars">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
            </div>
            <h3>Jessica Taylor</h3>
         </div>
      </div>
      <div class="swiper-pagination"></div>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
<script src="js/script.js"></script>

<script>
// Initialize Swiper
var reviewsSwiper = new Swiper(".reviews-slider", {
   loop: true,
   spaceBetween: 30,
   autoplay: {
      delay: 5000,
      disableOnInteraction: false,
   },
   pagination: {
      el: ".swiper-pagination",
      clickable: true,
   },
   breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 3 },
   },
});

// Counter Animation
document.addEventListener('DOMContentLoaded', function() {
   const counters = document.querySelectorAll('.count');
   const speed = 200;
   
   counters.forEach(counter => {
      const animate = () => {
         const target = +counter.getAttribute('data-count');
         const count = +counter.innerText;
         const increment = target / speed;
         
         if(count < target) {
            counter.innerText = Math.ceil(count + increment);
            setTimeout(animate, 1);
         } else {
            counter.innerText = target;
         }
      }
      
      const observer = new IntersectionObserver((entries) => {
         if(entries[0].isIntersecting) {
            animate();
         }
      });
      
      observer.observe(counter);
   });
   
   // Scroll animations
   const animateElements = document.querySelectorAll('.animate__animated');
   
   const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
         if(entry.isIntersecting) {
            entry.target.classList.add(entry.target.dataset.animate);
            observer.unobserve(entry.target);
         }
      });
   }, { threshold: 0.1 });
   
   animateElements.forEach(element => {
      observer.observe(element);
   });
});
</script>

</body>
</html>