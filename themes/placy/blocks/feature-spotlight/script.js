/**
 * Feature Spotlight - Apple-style expandable feature cards
 * 
 * Provides smooth animations for expanding/collapsing items
 * with background image transitions.
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    // Animation config (Apple-snappy spring approximation with CSS)
    const ANIMATION_DURATION = prefersReducedMotion ? 0 : 350;
    const EASING = 'cubic-bezier(0.2, 0.8, 0.2, 1)'; // Approximates spring

    /**
     * Initialize all Feature Spotlight blocks on the page
     */
    function initFeatureSpotlights() {
        const spotlights = document.querySelectorAll('.feature-spotlight[data-props]');
        spotlights.forEach(initSpotlight);
    }

    /**
     * Initialize a single Feature Spotlight instance
     * @param {HTMLElement} container 
     */
    function initSpotlight(container) {
        // Parse props from data attribute
        let props;
        try {
            props = JSON.parse(container.dataset.props);
        } catch (e) {
            console.error('Feature Spotlight: Invalid props JSON', e);
            return;
        }

        const items = container.querySelectorAll('.feature-spotlight__item');
        const bgImages = container.querySelectorAll('.feature-spotlight__bg-image');
        const arrowsContainer = container.querySelector('.feature-spotlight__arrows');
        const arrowUp = container.querySelector('.feature-spotlight__arrow--up');
        const arrowDown = container.querySelector('.feature-spotlight__arrow--down');
        
        let activeIndex = props.initialOpenIndex !== null ? props.initialOpenIndex : null;
        
        // State
        const state = {
            activeIndex: activeIndex,
            isAnimating: false
        };

        /**
         * Set active item by index (or null to close all)
         * @param {number|null} index 
         */
        function setActiveItem(index) {
            if (state.isAnimating) return;
            
            const previousIndex = state.activeIndex;
            
            // If clicking same item, close it
            if (index === previousIndex) {
                index = null;
            }
            
            state.activeIndex = index;
            state.isAnimating = true;

            // Update items
            items.forEach((item, i) => {
                const btn = item.querySelector('.feature-spotlight__item-btn');
                const panel = item.querySelector('.feature-spotlight__item-panel');
                const isActive = i === index;
                
                // Update classes with transition
                if (isActive) {
                    item.classList.add('is-active');
                    btn.setAttribute('aria-selected', 'true');
                    btn.setAttribute('aria-expanded', 'true');
                    panel.hidden = false;
                    
                    // Animate panel height
                    animatePanelOpen(panel);
                } else {
                    if (item.classList.contains('is-active')) {
                        // Animate panel close
                        animatePanelClose(panel, () => {
                            panel.hidden = true;
                        });
                    }
                    item.classList.remove('is-active');
                    btn.setAttribute('aria-selected', 'false');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });

            // Update background images
            bgImages.forEach((bg, i) => {
                const bgIndex = parseInt(bg.dataset.index, 10);
                const shouldShow = index !== null ? bgIndex === index : bgIndex === 0;
                
                if (shouldShow) {
                    bg.classList.add('is-active');
                } else {
                    bg.classList.remove('is-active');
                }
            });

            // Update arrows state (arrows are always visible via CSS)
            if (arrowsContainer) {
                updateArrowsState(index !== null ? index : 0);
            }

            // Reset animation lock
            setTimeout(() => {
                state.isAnimating = false;
            }, ANIMATION_DURATION);
        }

        /**
         * Animate panel opening
         * @param {HTMLElement} panel 
         */
        function animatePanelOpen(panel) {
            if (prefersReducedMotion) {
                panel.style.height = 'auto';
                panel.style.opacity = '1';
                return;
            }

            // Get natural height
            panel.style.height = 'auto';
            panel.style.opacity = '0';
            const height = panel.scrollHeight;
            
            // Set starting state
            panel.style.height = '0px';
            panel.style.overflow = 'hidden';
            
            // Force reflow
            panel.offsetHeight;
            
            // Animate
            panel.style.transition = `height ${ANIMATION_DURATION}ms ${EASING}, opacity ${ANIMATION_DURATION}ms ${EASING}`;
            panel.style.height = height + 'px';
            panel.style.opacity = '1';
            
            // Clean up after animation
            setTimeout(() => {
                panel.style.height = 'auto';
                panel.style.overflow = '';
                panel.style.transition = '';
            }, ANIMATION_DURATION);
        }

        /**
         * Animate panel closing
         * @param {HTMLElement} panel 
         * @param {Function} callback 
         */
        function animatePanelClose(panel, callback) {
            if (prefersReducedMotion) {
                panel.style.height = '0';
                panel.style.opacity = '0';
                if (callback) callback();
                return;
            }

            // Get current height
            const height = panel.scrollHeight;
            panel.style.height = height + 'px';
            panel.style.overflow = 'hidden';
            
            // Force reflow
            panel.offsetHeight;
            
            // Animate
            panel.style.transition = `height ${ANIMATION_DURATION}ms ${EASING}, opacity ${ANIMATION_DURATION}ms ${EASING}`;
            panel.style.height = '0px';
            panel.style.opacity = '0';
            
            // Clean up after animation
            setTimeout(() => {
                panel.style.transition = '';
                panel.style.overflow = '';
                if (callback) callback();
            }, ANIMATION_DURATION);
        }

        /**
         * Update arrow button states (disabled/enabled)
         * @param {number} index 
         */
        function updateArrowsState(index) {
            if (!arrowUp || !arrowDown) return;
            
            arrowUp.disabled = index <= 0;
            arrowDown.disabled = index >= items.length - 1;
            
            arrowUp.classList.toggle('is-disabled', index <= 0);
            arrowDown.classList.toggle('is-disabled', index >= items.length - 1);
        }

        /**
         * Position arrows - now handled purely by CSS centering
         * This function is kept for backwards compatibility but does nothing
         * @param {number} index 
         */
        function positionArrows(index) {
            // Arrows are now centered via CSS (top: 50%; transform: translateY(-50%))
            // No JS positioning needed
        }

        /**
         * Navigate to previous item
         */
        function goToPrevious() {
            if (state.activeIndex === null || state.activeIndex <= 0) return;
            setActiveItem(state.activeIndex - 1);
        }

        /**
         * Navigate to next item
         */
        function goToNext() {
            if (state.activeIndex === null || state.activeIndex >= items.length - 1) return;
            setActiveItem(state.activeIndex + 1);
        }

        /**
         * Handle item button click
         * @param {Event} e 
         */
        function handleItemClick(e) {
            const btn = e.target.closest('.feature-spotlight__item-btn');
            if (!btn) return;
            
            const item = btn.closest('.feature-spotlight__item');
            const index = parseInt(item.dataset.index, 10);
            
            // Check if clicking the close button
            const closeBtn = e.target.closest('.feature-spotlight__item-close');
            if (closeBtn) {
                e.stopPropagation();
                setActiveItem(null);
                return;
            }
            
            setActiveItem(index);
        }

        /**
         * Handle keyboard navigation
         * @param {KeyboardEvent} e 
         */
        function handleKeydown(e) {
            const isInList = e.target.closest('.feature-spotlight__list');
            if (!isInList) return;
            
            switch (e.key) {
                case 'ArrowUp':
                    e.preventDefault();
                    if (state.activeIndex !== null) {
                        goToPrevious();
                    } else {
                        // Focus previous button
                        const focusedBtn = document.activeElement;
                        const focusedItem = focusedBtn?.closest('.feature-spotlight__item');
                        if (focusedItem) {
                            const prevItem = focusedItem.previousElementSibling;
                            if (prevItem) {
                                prevItem.querySelector('.feature-spotlight__item-btn')?.focus();
                            }
                        }
                    }
                    break;
                    
                case 'ArrowDown':
                    e.preventDefault();
                    if (state.activeIndex !== null) {
                        goToNext();
                    } else {
                        // Focus next button
                        const focusedBtn = document.activeElement;
                        const focusedItem = focusedBtn?.closest('.feature-spotlight__item');
                        if (focusedItem) {
                            const nextItem = focusedItem.nextElementSibling;
                            if (nextItem) {
                                nextItem.querySelector('.feature-spotlight__item-btn')?.focus();
                            }
                        }
                    }
                    break;
                    
                case 'Escape':
                    e.preventDefault();
                    setActiveItem(null);
                    break;
                    
                case 'Enter':
                case ' ':
                    // Let native click handle this
                    break;
            }
        }

        // Event listeners
        container.addEventListener('click', handleItemClick);
        container.addEventListener('keydown', handleKeydown);
        
        if (arrowUp) {
            arrowUp.addEventListener('click', (e) => {
                e.preventDefault();
                goToPrevious();
            });
        }
        
        if (arrowDown) {
            arrowDown.addEventListener('click', (e) => {
                e.preventDefault();
                goToNext();
            });
        }

        // Initialize state if there's an initial open index
        if (state.activeIndex !== null) {
            // Wait for DOM to settle, then update arrow states
            requestAnimationFrame(() => {
                if (arrowsContainer) {
                    updateArrowsState(state.activeIndex);
                }
            });
        } else {
            // Initialize arrow states even when no item is active
            if (arrowsContainer) {
                updateArrowsState(0);
            }
        }

        // Resize handler removed - arrows are now positioned via CSS
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFeatureSpotlights);
    } else {
        initFeatureSpotlights();
    }

    // Also initialize when new blocks are added (for Gutenberg editor)
    if (window.acf) {
        window.acf.addAction('render_block_preview/type=feature-spotlight', function($el) {
            const container = $el[0].querySelector('.feature-spotlight[data-props]');
            if (container) {
                initSpotlight(container);
            }
        });
    }

})();
