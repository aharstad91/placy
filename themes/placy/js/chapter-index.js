/**
 * Chapter Index - Smooth Scroll Navigation
 * 
 * Handles smooth scrolling to anchors within chapters
 * and updates active state based on scroll position.
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize chapter index navigation
     */
    function initChapterIndex() {
        const indexBlocks = document.querySelectorAll('.chapter-index');
        
        if (indexBlocks.length === 0) {
            return;
        }

        indexBlocks.forEach(function(indexBlock) {
            const pills = indexBlock.querySelectorAll('.chapter-index-pill');
            
            if (pills.length === 0) {
                return;
            }

            // Click handlers for smooth scroll
            pills.forEach(function(pill) {
                pill.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const anchor = pill.getAttribute('data-anchor');
                    if (!anchor) return;
                    
                    const target = document.getElementById(anchor);
                    if (!target) {
                        console.warn('Chapter Index: Target not found for anchor:', anchor);
                        return;
                    }
                    
                    // Smooth scroll to target
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update active state
                    pills.forEach(function(p) {
                        p.classList.remove('active');
                    });
                    pill.classList.add('active');
                });
            });

            // Set up intersection observer for active state tracking
            setupActiveStateTracking(indexBlock, pills);
        });
    }

    /**
     * Track scroll position to update active pill state
     */
    function setupActiveStateTracking(indexBlock, pills) {
        // Collect all target elements
        const targets = [];
        pills.forEach(function(pill) {
            const anchor = pill.getAttribute('data-anchor');
            if (anchor) {
                const target = document.getElementById(anchor);
                if (target) {
                    targets.push({ pill: pill, target: target });
                }
            }
        });

        if (targets.length === 0) {
            return;
        }

        // Observer options - trigger when target enters top portion of viewport
        const observerOptions = {
            root: null,
            rootMargin: '-10% 0px -70% 0px', // Top 20% of viewport
            threshold: 0
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    // Find matching pill and activate it
                    const matchingTarget = targets.find(function(t) {
                        return t.target === entry.target;
                    });
                    
                    if (matchingTarget) {
                        pills.forEach(function(p) {
                            p.classList.remove('active');
                        });
                        matchingTarget.pill.classList.add('active');
                    }
                }
            });
        }, observerOptions);

        // Observe all targets
        targets.forEach(function(t) {
            observer.observe(t.target);
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChapterIndex);
    } else {
        initChapterIndex();
    }

})();
