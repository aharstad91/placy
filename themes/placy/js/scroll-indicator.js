/**
 * Scroll Indicator - Animated arrow that encourages scrolling
 * 
 * Features:
 * - Smooth scroll to content on click
 * - Fades out when user scrolls
 * - Bouncing animation
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize scroll indicator
     */
    function initScrollIndicator() {
        const indicator = document.querySelector('.scroll-indicator');
        const introSection = document.querySelector('.intro-section');
        
        if (!indicator || !introSection) {
            return;
        }

        // Click to scroll to content
        indicator.addEventListener('click', function() {
            const contentContainer = document.querySelector('.tema-story-container');
            if (contentContainer) {
                contentContainer.scrollIntoView({ behavior: 'smooth' });
            }
        });

        // Fade out indicator on scroll
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const introHeight = introSection.offsetHeight;
                    
                    // Calculate opacity based on scroll (fade out in first 20% of intro)
                    const fadeDistance = introHeight * 0.2;
                    const opacity = Math.max(0, 1 - (scrollTop / fadeDistance));
                    
                    indicator.style.opacity = opacity;
                    
                    // Hide completely when scrolled past fade distance
                    if (scrollTop > fadeDistance) {
                        indicator.style.pointerEvents = 'none';
                    } else {
                        indicator.style.pointerEvents = 'auto';
                    }
                    
                    ticking = false;
                });
                ticking = true;
            }
        });

        console.log('Scroll Indicator: Initialized');
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScrollIndicator);
    } else {
        initScrollIndicator();
    }

})();
