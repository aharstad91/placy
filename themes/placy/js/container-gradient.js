/**
 * Container Background Gradient Transition
 * 
 * Creates a smooth gradient transition from custom color to white
 * as the tema-story-container comes into view
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Convert hex color to RGB components
     * @param {string} hex - Hex color code (e.g., '#383d46')
     * @returns {Object} RGB components {r, g, b}
     */
    function hexToRgb(hex) {
        // Remove # if present
        hex = hex.replace('#', '');
        
        // Parse RGB values
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        
        return { r, g, b };
    }

    /**
     * Convert RGB components to hex color
     * @param {number} r - Red value (0-255)
     * @param {number} g - Green value (0-255)
     * @param {number} b - Blue value (0-255)
     * @returns {string} Hex color string (e.g., '#383d46')
     */
    function rgbToHex(r, g, b) {
        const toHex = (n) => {
            const hex = Math.max(0, Math.min(255, Math.round(n))).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        };
        return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
    }

    /**
     * Interpolate between two RGB colors and return hex
     * @param {Object} color1 - Starting RGB color {r, g, b}
     * @param {Object} color2 - Ending RGB color {r, g, b}
     * @param {number} factor - Interpolation factor (0-1)
     * @returns {string} Hex color string
     */
    function interpolateColor(color1, color2, factor) {
        const r = color1.r + (color2.r - color1.r) * factor;
        const g = color1.g + (color2.g - color1.g) * factor;
        const b = color1.b + (color2.b - color1.b) * factor;
        
        return rgbToHex(r, g, b);
    }

    /**
     * Initialize gradient transition
     */
    function initGradientTransition() {
        const container = document.querySelector('.tema-story-container');
        const mainWrapper = document.querySelector('.main-content-wrapper');
        
        if (!container) {
            return;
        }

        // Get custom background color from data attribute
        let customColor = container.getAttribute('data-bg-color') || '#f5f5f5';
        
        // Ensure color is in hex format
        if (customColor.startsWith('rgb')) {
            // Convert rgb(r, g, b) to hex
            const rgbMatch = customColor.match(/\d+/g);
            if (rgbMatch && rgbMatch.length >= 3) {
                customColor = rgbToHex(parseInt(rgbMatch[0]), parseInt(rgbMatch[1]), parseInt(rgbMatch[2]));
            }
        }
        
        const whiteColor = '#ffffff';
        
        // Convert to RGB for interpolation
        const startRgb = hexToRgb(customColor);
        const endRgb = hexToRgb(whiteColor);
        
        // Set initial background to custom color (hex format)
        container.style.backgroundColor = customColor;

        /**
         * Update gradient and padding based on scroll position
         */
        function updateGradient() {
            const rect = container.getBoundingClientRect();
            const containerHeight = container.offsetHeight;
            const viewportHeight = window.innerHeight;
            
            // Calculate how much of the container is visible
            const containerTop = rect.top;
            
            // Start transition earlier - when container is about to enter viewport
            // Transition distance: from when top of container is at bottom of viewport
            // to when container has scrolled up by 50% of viewport height
            const transitionStart = viewportHeight;
            const transitionEnd = viewportHeight * 0.5;
            
            // Calculate scroll progress
            // When containerTop >= transitionStart (not yet visible), progress = 0
            // When containerTop <= transitionEnd (50% of viewport scrolled), progress = 1
            const scrollDistance = transitionStart - containerTop;
            const transitionDistance = transitionStart - transitionEnd;
            const progress = Math.min(Math.max(scrollDistance / transitionDistance, 0), 1);
            
            // Interpolate between custom color and white (returns hex)
            const currentColor = interpolateColor(startRgb, endRgb, progress);
            
            // Apply the color using backgroundColor (hex format)
            container.style.backgroundColor = currentColor;
            
            // Morph padding from "0 6rem" to "0 0" on wrapper
            if (mainWrapper) {
                // Start at 6rem (96px), end at 0
                const startWrapperPadding = 96;
                const currentWrapperPadding = startWrapperPadding * (1 - progress);
                mainWrapper.style.padding = `0 ${currentWrapperPadding}px`;
            }
        }

        // Use requestAnimationFrame for smooth performance
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    updateGradient();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Initial call
        updateGradient();
        
        console.log('Container Gradient: Initialized with color', customColor);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGradientTransition);
    } else {
        initGradientTransition();
    }

})();
