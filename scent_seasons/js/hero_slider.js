// js/hero-slider.js - Hero Slideshow (Updated for <img> tags)

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        autoplayInterval: 5000,  // 5 seconds per slide
        transitionDuration: 1000 // 1 second fade transition
    };

    // State
    let currentSlide = 0;
    let autoplayTimer = null;
    let isTransitioning = false;

    // DOM Elements - 等待 DOM 完全加载
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.querySelector('.hero-arrow-left');
        const nextBtn = document.querySelector('.hero-arrow-right');
        const heroSection = document.querySelector('.hero-section');

        // Debug: Check if elements exist
        console.log('Hero slider initializing...');
        console.log('Found slides:', slides.length);
        console.log('Found dots:', dots.length);
        console.log('Found prev button:', prevBtn ? 'Yes' : 'No');
        console.log('Found next button:', nextBtn ? 'Yes' : 'No');

        // Check if elements exist
        if (!slides.length) {
            console.error('No hero slides found!');
            return;
        }

        /**
         * Show specific slide with animation
         */
        function showSlide(index) {
            if (isTransitioning) {
                console.log('Still transitioning, please wait...');
                return;
            }
            
            // Validate index
            if (index < 0) {
                currentSlide = slides.length - 1;
            } else if (index >= slides.length) {
                currentSlide = 0;
            } else {
                currentSlide = index;
            }

            console.log('Showing slide:', currentSlide);
            isTransitioning = true;

            // Update slides
            slides.forEach((slide, i) => {
                if (i === currentSlide) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });

            // Update dots
            if (dots.length > 0) {
                dots.forEach((dot, i) => {
                    if (i === currentSlide) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }

            // Reset transition lock after animation completes
            setTimeout(() => {
                isTransitioning = false;
            }, CONFIG.transitionDuration);
        }

        /**
         * Go to next slide
         */
        function nextSlide() {
            console.log('Next button clicked');
            showSlide(currentSlide + 1);
            resetAutoplay();
        }

        /**
         * Go to previous slide
         */
        function prevSlide() {
            console.log('Previous button clicked');
            showSlide(currentSlide - 1);
            resetAutoplay();
        }

        /**
         * Start autoplay
         */
        function startAutoplay() {
            console.log('Starting autoplay...');
            autoplayTimer = setInterval(() => {
                nextSlide();
            }, CONFIG.autoplayInterval);
        }

        /**
         * Stop autoplay
         */
        function stopAutoplay() {
            if (autoplayTimer) {
                console.log('Stopping autoplay...');
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        /**
         * Reset autoplay timer
         */
        function resetAutoplay() {
            stopAutoplay();
            startAutoplay();
        }

        /**
         * Handle keyboard navigation
         */
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

        /**
         * Handle touch swipe
         */
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
                    // Swipe left - next slide
                    nextSlide();
                } else {
                    // Swipe right - previous slide
                    prevSlide();
                }
            }
        }

        /**
         * Pause autoplay on hover (desktop only)
         */
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

        /**
         * Initialize slider
         */
        function init() {
            console.log('Initializing hero slider...');
            
            // Set initial slide
            showSlide(0);

            // Arrow button events
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

            // Dot navigation events
            dots.forEach((dot, index) => {
                dot.addEventListener('click', function() {
                    console.log('Dot clicked:', index);
                    showSlide(index);
                    resetAutoplay();
                });
            });

            // Keyboard navigation
            document.addEventListener('keydown', handleKeyboard);

            // Touch events for mobile
            if (heroSection) {
                heroSection.addEventListener('touchstart', handleTouchStart);
                heroSection.addEventListener('touchend', handleTouchEnd);

                // Pause on hover (desktop)
                heroSection.addEventListener('mouseenter', handleMouseEnter);
                heroSection.addEventListener('mouseleave', handleMouseLeave);
            }

            // Start autoplay
            startAutoplay();

            // Pause autoplay when tab is not visible
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopAutoplay();
                } else {
                    startAutoplay();
                }
            });

            console.log('Hero slider initialized successfully with', slides.length, 'slides');
        }

        // Start initialization
        init();

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            stopAutoplay();
            document.removeEventListener('keydown', handleKeyboard);
        });
    });

})();