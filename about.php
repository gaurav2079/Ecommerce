<?php
// Database connection and session setup
include 'components/connect.php';
session_start();

class AboutPage {
    private $conn;
    private $user_id;
    private $stats;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->user_id = $_SESSION['user_id'] ?? '';
        $this->stats = [
            'happy_customers' => 0,
            'products_available' => 0,
            'orders_delivered' => 0
        ];
    }
    
    public function fetchStatistics() {
        try {
            // Count total customers
            $count_customers = $this->conn->prepare("SELECT COUNT(*) FROM `users`");
            $count_customers->execute();
            $this->stats['happy_customers'] = $count_customers->fetchColumn();
            
            // Count total products available
            $count_products = $this->conn->prepare("SELECT SUM(stock) FROM `products`");
            $count_products->execute();
            $this->stats['products_available'] = $count_products->fetchColumn();
            
            // Count delivered orders
            $count_orders = $this->conn->prepare("SELECT COUNT(*) FROM `orders` WHERE status = 'pending'");
            $count_orders->execute();
            $this->stats['orders_delivered'] = $count_orders->fetchColumn();
            
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    public function getStat($key) {
        return $this->stats[$key] ?? 0;
    }
    
    public function renderHeader() {
        include 'components/user_header.php';
    }
    
    public function renderFooter() {
        include 'components/footer.php';
    }
    
    public function renderHeroSection() {
        echo '
        <section class="video-background">
            <video autoplay muted loop playsinline id="bgVideo">
                <source src="video/vv.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="video-overlay"></div>
            <div class="hero-content animate__animated animate__fadeIn">
                <h1>Welcome to Nepal~Store</h1>
                <p>Your trusted partner for quality products and exceptional service</p>
                <a href="#about" class="hero-btn">Discover Our Story</a>
            </div>
        </section>';
    }
    
    public function renderAboutSection() {
        echo '
        <section class="about" id="about">
            <div class="row">
                <div class="image animate__animated animate__fadeInLeft">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80" alt="About Us">
                </div>
                <div class="content animate__animated animate__fadeInRight">
                    <h3>Why Choose Us?</h3>
                    <p>At Nepal~Store, we\'re committed to providing the best shopping experience with high-quality products, competitive prices, and exceptional customer service. Our team works tirelessly to ensure your complete satisfaction.</p>
                    <p>We source products directly from trusted manufacturers to guarantee authenticity and offer a 30-day money-back guarantee on all purchases. Our mission is to bring you the best products while supporting local communities and sustainable practices.</p>
                    <a href="contact.php" class="btn">Contact Us</a>
                </div>
            </div>
        </section>';
    }
    
    public function renderStatsSection() {
        echo '
        <section class="stats">
            <div class="box-container">
                <div class="box animate__animated" data-animate="fadeInUp">
                    <i class="fas fa-users"></i>
                    <h3 class="count" data-count="' . $this->getStat('happy_customers') . '">0</h3>
                    <p>Happy Customers</p>
                </div>
                
                <div class="box animate__animated" data-animate="fadeInUp" data-delay="0.2s">
                    <i class="fas fa-box-open"></i>
                    <h3 class="count" data-count="' . $this->getStat('products_available') . '">0</h3>
                    <p>Products Available</p>
                </div>
                
                <div class="box animate__animated" data-animate="fadeInUp" data-delay="0.4s">
                    <i class="fas fa-truck"></i>
                    <h3 class="count" data-count="' . $this->getStat('orders_delivered') . '">0</h3>
                    <p>Orders Delivered</p>
                </div>
                
                <div class="box animate__animated" data-animate="fadeInUp" data-delay="0.6s">
                    <i class="fas fa-award"></i>
                    <h3 class="count" data-count="15">0</h3>
                    <p>Awards Won</p>
                </div>
            </div>
        </section>';
    }
    
    public function renderTeamSection() {
        echo '
        <section class="team">
            <div class="heading animate__animated animate__fadeIn">
                <h1>Meet Our Team</h1>
                <p>Our dedicated team of professionals works around the clock to ensure you get the best shopping experience</p>
            </div>
            
            <div class="box-container">
                <div class="box animate__animated" data-animate="fadeInUp" data-delay="0.1s">
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
                
                <div class="box animate__animated" data-animate="fadeInUp" data-delay="0.3s">
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
        </section>';
    }
    
    public function renderReviewsSection() {
        echo '
        <section class="reviews">
            <div class="heading animate__animated animate__fadeIn">
                <h1>Client\'s Reviews</h1>
                <p>What our customers say about us</p>
            </div>

            <div class="swiper reviews-slider">
                <div class="swiper-wrapper">
                    <div class="swiper-slide slide animate__animated" data-animate="fadeInUp" data-delay="0.1s">
                        <img src="images/pic-1.png" alt="Customer Review">
                        <p>"The best online shopping experience I\'ve ever had! Fast delivery and excellent customer service. Will definitely shop here again!"</p>
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>John Deo</h3>
                        <span>Regular Customer</span>
                    </div>

                    <div class="swiper-slide slide animate__animated" data-animate="fadeInUp" data-delay="0.2s">
                        <img src="images/pic-2.png" alt="Customer Review">
                        <p>"Amazing product quality and the prices are very competitive. I\'m impressed with their return policy too!"</p>
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <h3>Sarah Smith</h3>
                        <span>New Customer</span>
                    </div>

                    <div class="swiper-slide slide animate__animated" data-animate="fadeInUp" data-delay="0.3s">
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
                        <span>Premium Member</span>
                    </div>

                    <div class="swiper-slide slide animate__animated" data-animate="fadeInUp" data-delay="0.4s">
                        <img src="images/pic-4.png" alt="Customer Review">
                        <p>"I\'ve ordered multiple times and never been disappointed. The products always match the descriptions perfectly."</p>
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>Emily Brown</h3>
                        <span>VIP Customer</span>
                    </div>

                    <div class="swiper-slide slide animate__animated" data-animate="fadeInUp" data-delay="0.5s">
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
                        <span>First-time Buyer</span>
                    </div>

                    <div class="swiper-slide slide animate__animated" data-animate="fadeInUp" data-delay="0.6s">
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
                        <span>Loyal Customer</span>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </section>';
    }
    
    public function renderJavaScript() {
        echo '
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
        function animateCounter() {
            const counters = document.querySelectorAll(\'.count\');
            const speed = 200;
            
            counters.forEach(counter => {
                const target = +counter.getAttribute(\'data-count\');
                const count = +counter.innerText;
                const increment = Math.ceil(target / speed);
                
                if (count < target) {
                    counter.innerText = count + increment;
                    setTimeout(() => animateCounter(counter), 1);
                } else {
                    counter.innerText = target;
                }
            });
        }

        // Intersection Observer for animations
        document.addEventListener(\'DOMContentLoaded\', function() {
            // Scroll animations
            const animateElements = document.querySelectorAll(\'.animate__animated\');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        const animation = element.getAttribute(\'data-animate\');
                        const delay = element.getAttribute(\'data-delay\') || 0;
                        
                        setTimeout(() => {
                            element.classList.add(animation);
                            
                            // If element is a counter, animate it
                            if (element.querySelector(\'.count\')) {
                                animateCounter();
                            }
                        }, delay * 1000);
                        
                        observer.unobserve(element);
                    }
                });
            }, { threshold: 0.1 });
            
            animateElements.forEach(element => {
                observer.observe(element);
            });
        });

        // Video fallback in case of error
        document.getElementById(\'bgVideo\').addEventListener(\'error\', function() {
            // If video fails to load, set a background image instead
            document.querySelector(\'.video-background\').style.backgroundImage = \'url(https://images.pexels.com/photos/3184454/pexels-photo-3184454.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1)\';
            document.querySelector(\'.video-background\').style.backgroundSize = \'cover\';
            document.querySelector(\'.video-background\').style.backgroundPosition = \'center\';
            this.style.display = \'none\';
        });
        </script>';
    }
    
    public function renderFullPage() {
        // Fetch statistics first
        $this->fetchStatistics();
        
        // Then render the HTML
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
           <meta charset="UTF-8">
           <meta http-equiv="X-UA-Compatible" content="IE=edge">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>About Us | Nepal~Store</title>

           <!-- Swiper CSS -->
           <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
           
           <!-- Font Awesome -->
           <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
           
           <!-- Animate.css -->
           <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
           
           <!-- Custom CSS -->
           <link rel="stylesheet" href="css/style.css" />

           <style>
              :root {
                 --primary: #4361ee;
                 --secondary: #3a0ca3;
                 --accent: #f72585;
                 --light: #f8f9fa;
                 --dark: #212529;
              }
              
              /* Video Background */
              .video-background {
                 position: relative;
                 width: 100%;
                 height: 100vh;
                 overflow: hidden;
                 display: flex;
                 align-items: center;
                 justify-content: center;
              }
              
              .video-background video {
                 position: absolute;
                 top: 50%;
                 left: 50%;
                 min-width: 100%;
                 min-height: 100%;
                 width: auto;
                 height: auto;
                 transform: translateX(-50%) translateY(-50%);
                 z-index: -1;
                 object-fit: cover;
              }
              
              .video-overlay {
                 position: absolute;
                 top: 0;
                 left: 0;
                 width: 100%;
                 height: 100%;
                 background: rgba(0, 0, 0, 0.6);
                 z-index: 0;
              }
              
              .hero-content {
                 position: relative;
                 z-index: 1;
                 text-align: center;
                 color: white;
                 padding: 0 2rem;
                 max-width: 800px;
              }
              
              .hero-content h1 {
                 font-size: 4.5rem;
                 margin-bottom: 1.5rem;
                 text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
              }
              
              .hero-content p {
                 font-size: 2rem;
                 margin-bottom: 2.5rem;
                 text-shadow: 1px 1px 4px rgba(0,0,0,0.5);
              }
              
              .hero-btn {
                 display: inline-block;
                 background: linear-gradient(to right, var(--primary), var(--secondary));
                 color: white;
                 padding: 1.2rem 3.5rem;
                 border-radius: 50px;
                 font-size: 1.8rem;
                 transition: all 0.3s ease;
                 box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
                 text-decoration: none;
              }
              
              .hero-btn:hover {
                 transform: translateY(-5px);
                 box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
              }
              
              /* About Section */
              .about {
                 padding: 8rem 0;
                 background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
                 position: relative;
              }
              
              .about .row {
                 display: flex;
                 align-items: center;
                 flex-wrap: wrap;
                 gap: 5rem;
              }
              
              .about .row .image {
                 flex: 1 1 40rem;
                 overflow: hidden;
                 border-radius: 15px;
                 box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                 animation: fadeInLeft 1s ease;
                 position: relative;
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
                 font-size: 3.5rem;
                 color: var(--dark);
                 margin-bottom: 2rem;
                 position: relative;
              }
              
              .about .row .content h3:after {
                 content: \'\';
                 position: absolute;
                 left: 0;
                 bottom: -10px;
                 width: 80px;
                 height: 4px;
                 background: linear-gradient(to right, var(--primary), var(--secondary));
                 border-radius: 2px;
              }
              
              .about .row .content p {
                 font-size: 1.7rem;
                 line-height: 1.9;
                 color: #555;
                 padding: 1.5rem 0;
              }
              
              .about .row .content .btn {
                 display: inline-block;
                 margin-top: 2rem;
                 background: linear-gradient(to right, var(--primary), var(--secondary));
                 color: white;
                 padding: 1.2rem 3.5rem;
                 border-radius: 50px;
                 transition: all 0.3s ease;
                 box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
                 text-decoration: none;
                 font-size: 1.6rem;
              }
              
              .about .row .content .btn:hover {
                 transform: translateY(-5px);
                 box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
              }
              
              /* Stats Section */
              .stats {
                 padding: 8rem 0;
                 background: linear-gradient(135deg, var(--primary), var(--secondary));
                 color: white;
                 text-align: center;
                 position: relative;
                 overflow: hidden;
              }
              
              .stats:before {
                 content: \'\';
                 position: absolute;
                 top: 0;
                 left: 0;
                 width: 100%;
                 height: 100%;
                 background: url(\'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,128L48,117.3C96,107,192,85,288,112C384,139,480,213,576,218.7C672,224,768,160,864,138.7C960,117,1056,139,1152,149.3C1248,160,1344,160,1392,160L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>\');
                 background-size: cover;
                 background-position: bottom;
                 opacity: 0.1;
              }
              
              .stats .box-container {
                 display: grid;
                 grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr));
                 gap: 3rem;
                 position: relative;
              }
              
              .stats .box {
                 padding: 4rem 2rem;
                 background: rgba(255,255,255,0.1);
                 border-radius: 15px;
                 backdrop-filter: blur(5px);
                 border: 1px solid rgba(255,255,255,0.2);
                 transition: all 0.4s ease;
                 position: relative;
                 overflow: hidden;
              }
              
              .stats .box:before {
                 content: \'\';
                 position: absolute;
                 top: -50%;
                 left: -50%;
                 width: 200%;
                 height: 200%;
                 background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
                 transform: rotate(30deg);
                 opacity: 0;
                 transition: opacity 0.3s ease;
              }
              
              .stats .box:hover {
                 transform: translateY(-10px) scale(1.03);
                 background: rgba(255,255,255,0.15);
                 box-shadow: 0 10px 30px rgba(0,0,0,0.2);
              }
              
              .stats .box:hover:before {
                 opacity: 1;
              }
              
              .stats .box i {
                 font-size: 5rem;
                 margin-bottom: 2rem;
                 color: white;
                 background: linear-gradient(to right, #fff, #e0e0e0);
                 -webkit-background-clip: text;
                 -webkit-text-fill-color: transparent;
              }
              
              .stats .box h3 {
                 font-size: 4.5rem;
                 margin-bottom: 1rem;
                 font-weight: 700;
              }
              
              .stats .box p {
                 font-size: 1.8rem;
                 opacity: 0.9;
                 font-weight: 500;
              }
              
              /* Team Section */
              .team {
                 padding: 8rem 0;
                 background: var(--light);
                 position: relative;
              }
              
              .team .heading {
                 text-align: center;
                 margin-bottom: 6rem;
              }
              
              .team .heading h1 {
                 font-size: 4rem;
                 color: var(--dark);
                 margin-bottom: 1.5rem;
                 position: relative;
                 display: inline-block;
              }
              
              .team .heading h1:after {
                 content: \'\';
                 position: absolute;
                 bottom: -15px;
                 left: 50%;
                 transform: translateX(-50%);
                 width: 100px;
                 height: 4px;
                 background: linear-gradient(to right, var(--primary), var(--secondary));
                 border-radius: 2px;
              }
              
              .team .heading p {
                 font-size: 1.8rem;
                 color: #666;
                 max-width: 700px;
                 margin: 2rem auto 0;
                 line-height: 1.7;
              }
              
              .team .box-container {
                 display: grid;
                 grid-template-columns: repeat(auto-fit, minmax(28rem, 1fr));
                 gap: 4rem;
              }
              
              .team .box {
                 background: white;
                 border-radius: 20px;
                 overflow: hidden;
                 box-shadow: 0 10px 25px rgba(0,0,0,0.08);
                 transition: all 0.4s ease;
                 text-align: center;
                 padding: 3rem;
                 position: relative;
              }
              
              .team .box:before {
                 content: \'\';
                 position: absolute;
                 top: 0;
                 left: 0;
                 width: 100%;
                 height: 5px;
                 background: linear-gradient(to right, var(--primary), var(--secondary));
                 transform: scaleX(0);
                 transform-origin: left;
                 transition: transform 0.4s ease;
              }
              
              .team .box:hover {
                 transform: translateY(-15px);
                 box-shadow: 0 15px 35px rgba(0,0,0,0.15);
              }
              
              .team .box:hover:before {
                 transform: scaleX(1);
              }
              
              .team .box img {
                 width: 16rem;
                 height: 16rem;
                 object-fit: cover;
                 border-radius: 50%;
                 margin-bottom: 2rem;
                 border: 5px solid #f0f0f0;
                 transition: all 0.4s ease;
              }
              
              .team .box:hover img {
                 border-color: var(--primary);
                 transform: scale(1.05);
              }
              
              .team .box h3 {
                 font-size: 2.4rem;
                 color: var(--dark);
                 margin-bottom: 0.8rem;
                 transition: color 0.3s ease;
              }
              
              .team .box:hover h3 {
                 color: var(--primary);
              }
              
              .team .box span {
                 font-size: 1.6rem;
                 color: var(--accent);
                 display: block;
                 margin-bottom: 2rem;
                 font-weight: 500;
              }
              
              .team .box .share {
                 display: flex;
                 justify-content: center;
                 gap: 1.2rem;
              }
              
              .team .box .share a {
                 width: 4.5rem;
                 height: 4.5rem;
                 line-height: 4.5rem;
                 text-align: center;
                 background: #f5f5f5;
                 border-radius: 50%;
                 font-size: 1.8rem;
                 color: var(--dark);
                 transition: all 0.3s ease;
              }
              
              .team .box .share a:hover {
                 background: linear-gradient(to right, var(--primary), var(--secondary));
                 color: white;
                 transform: translateY(-5px);
              }
              
              /* Reviews Section */
              .reviews {
                 padding: 8rem 0;
                 background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
                 position: relative;
              }
              
              .reviews .heading {
                 text-align: center;
                 margin-bottom: 6rem;
              }
              
              .reviews .heading h1 {
                 font-size: 4rem;
                 color: var(--dark);
                 margin-bottom: 1.5rem;
                 position: relative;
                 display: inline-block;
              }
              
              .reviews .heading h1:after {
                 content: \'\';
                 position: absolute;
                 bottom: -15px;
                 left: 50%;
                 transform: translateX(-50%);
                 width: 100px;
                 height: 4px;
                 background: linear-gradient(to right, var(--primary), var(--secondary));
                 border-radius: 2px;
              }
              
              .reviews .heading p {
                 font-size: 1.8rem;
                 color: #666;
                 max-width: 700px;
                 margin: 2rem auto 0;
                 line-height: 1.7;
              }
              
              .reviews .slide {
                 background: white;
                 border-radius: 20px;
                 padding: 4rem 3rem;
                 box-shadow: 0 10px 25px rgba(0,0,0,0.08);
                 transition: all 0.4s ease;
                 text-align: center;
                 margin-bottom: 3rem;
                 position: relative;
                 margin: 1rem;
              }
              
              .reviews .slide:before {
                 content: \'"\';
                 position: absolute;
                 top: 20px;
                 left: 30px;
                 font-size: 8rem;
                 color: rgba(67, 97, 238, 0.1);
                 font-family: Arial, sans-serif;
                 line-height: 1;
              }
              
              .reviews .slide:hover {
                 transform: translateY(-10px);
                 box-shadow: 0 15px 35px rgba(0,0,0,0.15);
              }
              
              .reviews .slide img {
                 width: 11rem;
                 height: 11rem;
                 object-fit: cover;
                 border-radius: 50%;
                 margin-bottom: 2rem;
                 border: 5px solid #f0f0f0;
                 transition: all 0.4s ease;
                 position: relative;
              }
              
              .reviews .slide:hover img {
                 border-color: var(--primary);
                 transform: scale(1.05);
              }
              
              .reviews .slide p {
                 font-size: 1.7rem;
                 line-height: 1.9;
                 color: #555;
                 margin-bottom: 2rem;
                 position: relative;
              }
              
              .reviews .slide .stars {
                 margin-bottom: 2rem;
              }
              
              .reviews .slide .stars i {
                 font-size: 2rem;
                 color: #ffc107;
                 margin: 0 0.2rem;
              }
              
              .reviews .slide h3 {
                 font-size: 2.2rem;
                 color: var(--dark);
                 margin-bottom: 0.5rem;
              }
              
              .reviews .slide span {
                 font-size: 1.5rem;
                 color: #777;
              }
              
              /* Responsive */
              @media (max-width: 991px) {
                 .hero-content h1 {
                    font-size: 3.8rem;
                 }
                 
                 .hero-content p {
                    font-size: 1.8rem;
                 }
              }
              
              @media (max-width: 768px) {
                 .video-background {
                    height: 80vh;
                 }
                 
                 .hero-content h1 {
                    font-size: 3.2rem;
                 }
                 
                 .hero-content p {
                    font-size: 1.6rem;
                 }
                 
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
                 
                 .about .row .content h3:after {
                    left: 50%;
                    transform: translateX(-50%);
                 }
                 
                 .stats .box-container {
                    grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));
                 }
                 
                 .team .box-container {
                    grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr));
                    gap: 3rem;
                 }
              }
              
              @media (max-width: 450px) {
                 .hero-content h1 {
                    font-size: 2.8rem;
                 }
                 
                 .hero-content p {
                    font-size: 1.5rem;
                 }
                 
                 .hero-btn {
                    padding: 1rem 2.5rem;
                    font-size: 1.6rem;
                 }
                 
                 .stats .box {
                    padding: 3rem 1.5rem;
                 }
                 
                 .stats .box h3 {
                    font-size: 3.8rem;
                 }
              }
              
              /* Animations */
              @keyframes fadeInLeft {
                 from {
                    opacity: 0;
                    transform: translateX(-50px);
                 }
                 to {
                    opacity: 1;
                    transform: translateX(0);
                 }
              }
              
              @keyframes fadeInRight {
                 from {
                    opacity: 0;
                    transform: translateX(50px);
                 }
                 to {
                    opacity: 1;
                    transform: translateX(0);
                 }
              }
              
              @keyframes fadeInUp {
                 from {
                    opacity: 0;
                    transform: translateY(50px);
                 }
                 to {
                    opacity: 1;
                    transform: translateY(0);
                 }
              }
           </style>
        </head>
        <body>';
        
        // Render the page content
        $this->renderHeader();
        $this->renderHeroSection();
        $this->renderAboutSection();
        $this->renderStatsSection();
        $this->renderTeamSection();
        $this->renderReviewsSection();
        $this->renderFooter();
        $this->renderJavaScript();
        
        echo '</body></html>';
    }
}

// Create and use the AboutPage class
$aboutPage = new AboutPage($conn);
$aboutPage->renderFullPage();
?>