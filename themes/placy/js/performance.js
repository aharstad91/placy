/**
 * Performance utilities for Placy Prototype
 * Optimized event handling and smooth animations
 */

class PerformanceUtils {
    constructor() {
        this.scrollPosition = 0;
        this.isScrolling = false;
        this.resizeTimeout = null;
        this.activeListeners = new Map();
        
        // Stable viewport height for mobile
        this.initStableViewport();
    }
    
    /**
     * Improved throttle function with better performance
     */
    throttle(func, limit) {
        let lastFunc;
        let lastRan;
        return function(...args) {
            if (!lastRan) {
                func.apply(this, args);
                lastRan = Date.now();
            } else {
                clearTimeout(lastFunc);
                lastFunc = setTimeout(() => {
                    if ((Date.now() - lastRan) >= limit) {
                        func.apply(this, args);
                        lastRan = Date.now();
                    }
                }, limit - (Date.now() - lastRan));
            }
        };
    }
    
    /**
     * Improved debounce function
     */
    debounce(func, wait, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(this, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(this, args);
        };
    }
    
    /**
     * Optimized smooth scroll with requestAnimationFrame
     */
    smoothScrollTo(targetPosition, duration = 1200, offset = 80) {
        return new Promise((resolve) => {
            const startPosition = window.pageYOffset;
            const finalPosition = Math.max(0, targetPosition - offset);
            const distance = finalPosition - startPosition;
            
            // If distance is very small, just jump
            if (Math.abs(distance) < 10) {
                window.scrollTo(0, finalPosition);
                resolve();
                return;
            }
            
            let startTime = null;
            
            const animation = (currentTime) => {
                if (startTime === null) startTime = currentTime;
                const timeElapsed = currentTime - startTime;
                const progress = Math.min(timeElapsed / duration, 1);
                
                // Easing function (ease-out cubic)
                const ease = 1 - Math.pow(1 - progress, 3);
                
                const currentPosition = startPosition + distance * ease;
                window.scrollTo(0, currentPosition);
                
                if (timeElapsed < duration) {
                    requestAnimationFrame(animation);
                } else {
                    resolve();
                }
            };
            
            requestAnimationFrame(animation);
        });
    }
    
    /**
     * Scroll to element with better error handling
     */
    async scrollToElement(elementOrId, duration = 1200, offset = 80) {
        try {
            let element;
            if (typeof elementOrId === 'string') {
                element = document.getElementById(elementOrId);
                if (!element) {
                    throw new Error(`Element with id "${elementOrId}" not found`);
                }
            } else {
                element = elementOrId;
            }
            
            const targetPosition = element.getBoundingClientRect().top + window.pageYOffset;
            await this.smoothScrollTo(targetPosition, duration, offset);
            
        } catch (error) {
            console.error('Scroll error, falling back to native:', error);
            // Fallback to native smooth scroll
            if (typeof elementOrId === 'string') {
                const element = document.getElementById(elementOrId);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                elementOrId.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }
    
    /**
     * Stable viewport height for mobile browsers
     */
    initStableViewport() {
        const setStableHeight = () => {
            const vh = window.innerHeight;
            document.documentElement.style.setProperty('--viewport-height', `${vh}px`);
        };
        
        // Set initial height
        setStableHeight();
        
        // Update on orientation change with debouncing
        const handleResize = this.debounce(setStableHeight, PERFORMANCE_CONFIG.resizeDebounce);
        window.addEventListener('resize', handleResize);
        window.addEventListener('orientationchange', handleResize);
        
        // Store listener for cleanup
        this.activeListeners.set('viewport', handleResize);
    }
    
    /**
     * Optimized scroll listener with better performance
     */
    addScrollListener(callback, throttleMs = PERFORMANCE_CONFIG.scrollThrottle) {
        const throttledCallback = this.throttle(callback, throttleMs);
        
        const scrollHandler = () => {
            this.scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            throttledCallback(this.scrollPosition);
        };
        
        window.addEventListener('scroll', scrollHandler, { passive: true });
        return scrollHandler; // Return for cleanup
    }
    
    /**
     * Add intersection observer for better performance
     */
    addIntersectionObserver(elements, callback, options = {}) {
        const defaultOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1,
            ...options
        };
        
        const observer = new IntersectionObserver(callback, defaultOptions);
        
        elements.forEach(element => {
            if (element) observer.observe(element);
        });
        
        return observer;
    }
    
    /**
     * Preload critical resources
     */
    preloadResources(resources) {
        resources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource.href;
            link.as = resource.as || 'image';
            if (resource.type) link.type = resource.type;
            if (resource.crossorigin) link.crossOrigin = resource.crossorigin;
            document.head.appendChild(link);
        });
    }
    
    /**
     * Cleanup function to remove listeners
     */
    cleanup() {
        this.activeListeners.forEach((listener, key) => {
            if (key === 'viewport') {
                window.removeEventListener('resize', listener);
                window.removeEventListener('orientationchange', listener);
            }
        });
        this.activeListeners.clear();
    }
}

// Create global instance
const performanceUtils = new PerformanceUtils();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PerformanceUtils, performanceUtils };
}
