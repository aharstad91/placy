/**
 * Intro Section Parallax Effect
 *
 * Creates a smooth parallax scroll effect for the intro section text
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize parallax effect
     */
    function initParallax() {
        const introSection = document.querySelector('.intro-section');
        const introContent = document.querySelector('.intro-content');

        if (!introSection || !introContent) {
            return;
        }

        /**
         * Update parallax position on scroll
         */
        function updateParallax() {
            const scrollY = window.pageYOffset || window.scrollY;
            const sectionHeight = introSection.offsetHeight;

            // Only apply parallax while intro section is visible
            if (scrollY <= sectionHeight) {
                // Text parallax - moves slower than scroll (negative = moves up)
                const textOffset = scrollY * -0.3;
                introContent.style.transform = `translateY(${textOffset}px)`;

                // Background parallax - moves slower upward (positive = background moves up slower)
                // This creates the effect where background moves slower than scroll
                const bgOffset = scrollY * 0.5;
                introSection.style.backgroundPosition = `center ${bgOffset}px`;
            }
        }

        // Use requestAnimationFrame for smooth performance
        let ticking = false;

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    updateParallax();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Initial call
        updateParallax();

    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initParallax);
    } else {
        initParallax();
    }

})();
