// js/hero-slider.js - Hero Slideshow (Updated for <img> tags)

(function() {
    'use strict';


    const CONFIG = {
        autoplayInterval: 5000, 
        transitionDuration: 1000 
    };

 
    let currentSlide = 0;
    let autoplayTimer = null;
    let isTransitioning = false;

  
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.querySelector('.hero-arrow-left');
        const nextBtn = document.querySelector('.hero-arrow-right');
        const heroSection = document.querySelector('.hero-section');

       
        console.log('Hero slider initializing...');
        console.log('Found slides:', slides.length);
        console.log('Found dots:', dots.length);
        console.log('Found prev button:', prevBtn ? 'Yes' : 'No');
        console.log('Found next button:', nextBtn ? 'Yes' : 'No');

      
        if (!slides.length) {
            console.error('No hero slides found!');
            return;
        }

      
        function showSlide(index) {
            if (isTransitioning) {
                console.log('Still transitioning, please wait...');
                return;
            }
            
           
            if (index < 0) {
                currentSlide = slides.length - 1;
            } else if (index >= slides.length) {
                currentSlide = 0;
            } else {
                currentSlide = index;
            }

            console.log('Showing slide:', currentSlide);
            isTransitioning = true;

          
            slides.forEach((slide, i) => {
                if (i === currentSlide) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });

           
            if (dots.length > 0) {
                dots.forEach((dot, i) => {
                    if (i === currentSlide) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }

           
            setTimeout(() => {
                isTransitioning = false;
            }, CONFIG.transitionDuration);
        }

        
        function nextSlide() {
            console.log('Next button clicked');
            showSlide(currentSlide + 1);
            resetAutoplay();
        }

       
        function prevSlide() {
            console.log('Previous button clicked');
            showSlide(currentSlide - 1);
            resetAutoplay();
        }

      
        function startAutoplay() {
            console.log('Starting autoplay...');
            autoplayTimer = setInterval(() => {
                nextSlide();
            }, CONFIG.autoplayInterval);
        }

        
        function stopAutoplay() {
            if (autoplayTimer) {
                console.log('Stopping autoplay...');
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

     
        function resetAutoplay() {
            stopAutoplay();
            startAutoplay();
        }

      
        function handleKeyboard(e) {
            switch(e.key) {
                case 'ArrowLeft':
                    prevSlide();
                    break;
                case 'ArrowRight':
                    nextSlide();
                    break;
            }
        }

   
        let touchStartX = 0;
        let touchEndX = 0;

        function handleTouchStart(e) {
            touchStartX = e.changedTouches[0].screenX;
        }

        function handleTouchEnd(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }

        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                 
                    nextSlide();
                } else {
                 
                    prevSlide();
                }
            }
        }

      
        function handleMouseEnter() {
            if (window.innerWidth > 768) {
                stopAutoplay();
            }
        }

        function handleMouseLeave() {
            if (window.innerWidth > 768) {
                startAutoplay();
            }
        }

  
        function init() {
            console.log('Initializing hero slider...');
            
      
            showSlide(0);

 
            if (prevBtn) {
                prevBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    prevSlide();
                });
                console.log('Previous button event attached');
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    nextSlide();
                });
                console.log('Next button event attached');
            }

     
            dots.forEach((dot, index) => {
                dot.addEventListener('click', function() {
                    console.log('Dot clicked:', index);
                    showSlide(index);
                    resetAutoplay();
                });
            });

         
            document.addEventListener('keydown', handleKeyboard);

           
            if (heroSection) {
                heroSection.addEventListener('touchstart', handleTouchStart);
                heroSection.addEventListener('touchend', handleTouchEnd);

           
                heroSection.addEventListener('mouseenter', handleMouseEnter);
                heroSection.addEventListener('mouseleave', handleMouseLeave);
            }

            startAutoplay();

  
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopAutoplay();
                } else {
                    startAutoplay();
                }
            });

            console.log('Hero slider initialized successfully with', slides.length, 'slides');
        }


        init();

       
        window.addEventListener('beforeunload', () => {
            stopAutoplay();
            document.removeEventListener('keydown', handleKeyboard);
        });
    });

})();