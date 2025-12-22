/**
 * Chip Scrollytelling - TRUE scroll-progress driven state
 *
 * THIS IS NOT A TAB COMPONENT.
 * 
 * Primary driver: SCROLL POSITION
 * Secondary driver: Click (just jumps to scroll position)
 *
 * JS responsibilities (minimal):
 * 1. Calculate scroll progress (0→1) through the section
 * 2. Map progress to step index (0, 1, 2, ...)
 * 3. Set data-active-step="N" on root element
 * 4. Update ARIA attributes for accessibility
 *
 * CSS does everything else via [data-active-step="N"] selectors.
 * NO class toggling on individual elements. NO innerHTML swapping.
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize all Chip Scrollytelling blocks
     */
    function init() {
        const blocks = document.querySelectorAll('.chip-scrolly[data-steps-count]');
        blocks.forEach(initBlock);
    }

    /**
     * Initialize a single block instance
     * @param {HTMLElement} section - The .chip-scrolly element
     */
    function initBlock(section) {
        // Parse config
        const stepsCount = parseInt(section.dataset.stepsCount, 10);
        if (!stepsCount || stepsCount < 1) return;

        let props = {};
        try {
            props = JSON.parse(section.dataset.props || '{}');
        } catch (e) {
            // Fallback defaults
        }

        const enableClickScroll = props.enableClickScroll !== false;

        // DOM refs
        const chips = section.querySelectorAll('.chip-scrolly__chip[data-step]');
        const copyPanels = section.querySelectorAll('.chip-scrolly__step-copy[data-step]');
        const visualItems = section.querySelectorAll('.chip-scrolly__visual-item[data-step]');

        // State
        let currentStep = 0;
        let ticking = false;

        /**
         * Calculate scroll progress through this section (0 to 1)
         * 
         * Progress = 0 when top of section hits top of viewport
         * Progress = 1 when bottom of section hits bottom of viewport
         */
        function getProgress() {
            const rect = section.getBoundingClientRect();
            const sectionHeight = section.offsetHeight;
            const viewportHeight = window.innerHeight;
            
            // How far we've scrolled into the section
            const scrolled = -rect.top;
            
            // Total scrollable distance within the section
            const scrollableDistance = sectionHeight - viewportHeight;
            
            if (scrollableDistance <= 0) return 0;
            
            // Clamp between 0 and 1
            return Math.max(0, Math.min(1, scrolled / scrollableDistance));
        }

        /**
         * Map progress (0→1) to step index
         * 
         * For 3 steps:
         *   0.00 - 0.33 → step 0
         *   0.33 - 0.66 → step 1
         *   0.66 - 1.00 → step 2
         */
        function getStepFromProgress(progress) {
            const index = Math.floor(progress * stepsCount);
            return Math.max(0, Math.min(stepsCount - 1, index));
        }

        /**
         * Update the active step
         * This is the ONLY place that changes DOM
         */
        function setActiveStep(newStep) {
            if (newStep === currentStep) return;
            
            currentStep = newStep;
            
            // Set data attribute on root - CSS handles everything else
            section.dataset.activeStep = newStep;
            
            // Update ARIA for accessibility
            chips.forEach((chip, i) => {
                const isActive = i === newStep;
                chip.setAttribute('aria-selected', isActive ? 'true' : 'false');
                chip.setAttribute('tabindex', isActive ? '0' : '-1');
            });
            
            copyPanels.forEach((panel, i) => {
                panel.setAttribute('aria-hidden', i === newStep ? 'false' : 'true');
            });
            
            visualItems.forEach((item, i) => {
                item.setAttribute('aria-hidden', i === newStep ? 'false' : 'true');
            });
        }

        /**
         * Handle scroll - primary driver of state
         */
        function onScroll() {
            if (ticking) return;
            
            ticking = true;
            requestAnimationFrame(() => {
                ticking = false;
                
                const progress = getProgress();
                const step = getStepFromProgress(progress);
                setActiveStep(step);
            });
        }

        /**
         * Scroll to a specific step position
         * Used by chip clicks (secondary interaction)
         */
        function scrollToStep(stepIndex) {
            const sectionRect = section.getBoundingClientRect();
            const sectionTop = sectionRect.top + window.scrollY;
            const sectionHeight = section.offsetHeight;
            const viewportHeight = window.innerHeight;
            const scrollableDistance = sectionHeight - viewportHeight;
            
            // Calculate target progress for this step (center of step's range)
            // For step 0 of 3: target = 0.167 (middle of 0-0.33)
            // For step 1 of 3: target = 0.5 (middle of 0.33-0.66)
            // For step 2 of 3: target = 0.833 (middle of 0.66-1.0)
            const targetProgress = (stepIndex + 0.5) / stepsCount;
            const targetScrollY = sectionTop + (scrollableDistance * targetProgress);
            
            window.scrollTo({
                top: targetScrollY,
                behavior: 'smooth'
            });
        }

        /**
         * Handle chip click - scroll to step position
         */
        function onChipClick(e) {
            const chip = e.currentTarget;
            const stepIndex = parseInt(chip.dataset.step, 10);
            
            if (isNaN(stepIndex)) return;
            
            if (enableClickScroll) {
                scrollToStep(stepIndex);
            } else {
                // If click-to-scroll disabled, just update state directly
                setActiveStep(stepIndex);
            }
        }

        /**
         * Handle keyboard navigation on chips
         */
        function onChipKeydown(e) {
            let newStep = currentStep;
            
            switch (e.key) {
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    newStep = Math.max(0, currentStep - 1);
                    break;
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    newStep = Math.min(stepsCount - 1, currentStep + 1);
                    break;
                case 'Home':
                    e.preventDefault();
                    newStep = 0;
                    break;
                case 'End':
                    e.preventDefault();
                    newStep = stepsCount - 1;
                    break;
                default:
                    return;
            }
            
            if (newStep !== currentStep) {
                if (enableClickScroll) {
                    scrollToStep(newStep);
                } else {
                    setActiveStep(newStep);
                }
                chips[newStep]?.focus();
            }
        }

        /**
         * Bind event listeners
         */
        function bindEvents() {
            // Scroll is the PRIMARY driver
            window.addEventListener('scroll', onScroll, { passive: true });
            
            // Chip clicks are SECONDARY (just scroll to position)
            chips.forEach(chip => {
                chip.addEventListener('click', onChipClick);
                chip.addEventListener('keydown', onChipKeydown);
            });
            
            // Recalculate on resize
            window.addEventListener('resize', onScroll, { passive: true });
        }

        /**
         * Set initial state based on current scroll position
         */
        function setInitialState() {
            const progress = getProgress();
            const step = getStepFromProgress(progress);
            currentStep = -1; // Force update
            setActiveStep(step);
        }

        // Initialize
        bindEvents();
        requestAnimationFrame(setInitialState);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-initialize on Gutenberg block preview updates
    if (window.acf) {
        window.acf.addAction('render_block_preview/type=chip-scrollytelling', function($block) {
            const section = $block[0]?.querySelector('.chip-scrolly[data-steps-count]');
            if (section) {
                initBlock(section);
            }
        });
    }

})();
