document.addEventListener('DOMContentLoaded', function() {
    // Thumbnail image switching
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.querySelector('.main-image img');
    const zoomableImage = document.getElementById('zoomable-image');
    const zoomResult = document.querySelector('.zoom-result');
    
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            // Remove active class from all thumbnails
            thumbnails.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked thumbnail
            this.classList.add('active');
            
            // Change main image
            const newImage = this.getAttribute('data-image');
            mainImage.src = `uploaded_img/${newImage}`;
            zoomableImage.src = `uploaded_img/${newImage}`;
        });
    });
    
    // Quantity selector buttons
    const qtyInput = document.querySelector('.qty');
    const qtyPlus = document.querySelector('.qty-plus');
    const qtyMinus = document.querySelector('.qty-minus');
    
    qtyPlus.addEventListener('click', function() {
        let currentVal = parseInt(qtyInput.value);
        if (currentVal < 99) {
            qtyInput.value = currentVal + 1;
        }
    });
    
    qtyMinus.addEventListener('click', function() {
        let currentVal = parseInt(qtyInput.value);
        if (currentVal > 1) {
            qtyInput.value = currentVal - 1;
        }
    });
    
    // Image zoom functionality
    if (zoomableImage && zoomResult) {
        zoomableImage.addEventListener('mousemove', function(e) {
            if (!this.classList.contains('zoomed')) {
                this.classList.add('zoomed');
                zoomResult.style.display = 'block';
            }
            
            // Get the position of the image
            const imgRect = this.getBoundingClientRect();
            
            // Calculate the position of the cursor inside the image
            const x = e.clientX - imgRect.left;
            const y = e.clientY - imgRect.top;
            
            // Calculate the percentage position
            const xPercent = (x / imgRect.width) * 100;
            const yPercent = (y / imgRect.height) * 100;
            
            // Update the background position of the zoom result
            zoomResult.style.backgroundImage = `url(${this.src})`;
            zoomResult.style.backgroundSize = `${imgRect.width * 2}px ${imgRect.height * 2}px`;
            zoomResult.style.backgroundPosition = `${xPercent}% ${yPercent}%`;
        });
        
        zoomableImage.addEventListener('mouseleave', function() {
            this.classList.remove('zoomed');
            zoomResult.style.display = 'none';
        });
    }
    
    // Smooth scroll for recommendations
    const recSlider = document.querySelector('.recommendation-slider');
    if (recSlider) {
        let isDown = false;
        let startX;
        let scrollLeft;
        
        recSlider.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - recSlider.offsetLeft;
            scrollLeft = recSlider.scrollLeft;
        });
        
        recSlider.addEventListener('mouseleave', () => {
            isDown = false;
        });
        
        recSlider.addEventListener('mouseup', () => {
            isDown = false;
        });
        
        recSlider.addEventListener('mousemove', (e) => {
            if(!isDown) return;
            e.preventDefault();
            const x = e.pageX - recSlider.offsetLeft;
            const walk = (x - startX) * 2;
            recSlider.scrollLeft = scrollLeft - walk;
        });
    }
});