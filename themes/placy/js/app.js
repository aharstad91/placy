/**
 * Main application class for Placy Prototype
 * Orchestrates all functionality with better error handling and performance
 */

class PlacyApp {
    constructor() {
        this.modalManager = null;
        this.stickyNav = null;
        this.isInitialized = false;
        this.errorHandler = new ErrorHandler();
        
        // Bind methods to preserve context
        this.init = this.init.bind(this);
        this.handleError = this.handleError.bind(this);
    }
    
    /**
     * Initialize the application
     */
    async init() {
        try {
            console.log('Initializing Placy Prototype...');
            
            // Set up error handling
            this.setupErrorHandling();
            
            // Preload critical resources
            this.preloadCriticalResources();
            
            // Initialize performance utilities
            if (typeof performanceUtils !== 'undefined') {
                // Already initialized in performance.js
            }
            
            // Initialize modal manager
            this.modalManager = new ModalManager();
            
            // Initialize sticky navigation
            this.stickyNav = new StickyNavigation();
            
            // Initialize touch interactions for mobile
            this.initMobileInteractions();
            
            // Initialize keyboard shortcuts
            this.initKeyboardShortcuts();
            
            // Set up intersection observers for performance
            this.setupIntersectionObservers();
            
            this.isInitialized = true;
            console.log('Placy Prototype initialized successfully');
            
        } catch (error) {
            this.handleError(error, 'Application initialization failed');
        }
    }
    
    /**
     * Set up global error handling
     */
    setupErrorHandling() {
        window.addEventListener('error', (event) => {
            this.handleError(event.error, 'Global error');
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError(event.reason, 'Unhandled promise rejection');
        });
    }
    
    /**
     * Preload critical resources for better performance
     */
    preloadCriticalResources() {
        const criticalResources = [
            { href: 'overvik-hero.jpg', as: 'image' },
            { href: 'overvik-logo.svg', as: 'image' }
        ];
        
        if (typeof performanceUtils !== 'undefined') {
            performanceUtils.preloadResources(criticalResources);
        }
    }
    
    /**
     * Initialize mobile-specific interactions
     */
    initMobileInteractions() {
        const cards = document.querySelectorAll('.bg-white, .clickable');
        cards.forEach(card => {
            card.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            card.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        });
    }
    
    /**
     * Handle touch start for mobile feedback
     */
    handleTouchStart(event) {
        try {
            const element = event.currentTarget;
            element.style.transform = 'scale(0.98)';
            element.style.transition = 'transform 0.1s ease';
        } catch (error) {
            this.handleError(error, 'Touch start handler');
        }
    }
    
    /**
     * Handle touch end for mobile feedback
     */
    handleTouchEnd(event) {
        try {
            const element = event.currentTarget;
            setTimeout(() => {
                element.style.transform = '';
                element.style.transition = '';
            }, 100);
        } catch (error) {
            this.handleError(error, 'Touch end handler');
        }
    }
    
    /**
     * Initialize keyboard shortcuts
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            try {
                switch (event.key) {
                    case 'Escape':
                        this.handleEscapeKey();
                        break;
                    case 'Home':
                        if (event.ctrlKey || event.metaKey) {
                            event.preventDefault();
                            this.scrollToTop();
                        }
                        break;
                    case 'End':
                        if (event.ctrlKey || event.metaKey) {
                            event.preventDefault();
                            this.scrollToBottom();
                        }
                        break;
                }
            } catch (error) {
                this.handleError(error, 'Keyboard shortcut handler');
            }
        });
    }
    
    /**
     * Handle escape key press
     */
    handleEscapeKey() {
        if (this.modalManager && this.modalManager.isModalOpen()) {
            this.modalManager.closeModal();
        } else if (this.stickyNav && this.stickyNav.isPopupOpen()) {
            this.stickyNav.closePopup();
        }
    }
    
    /**
     * Set up intersection observers for performance
     */
    setupIntersectionObservers() {
        // Lazy load section images
        const sectionImages = document.querySelectorAll('.section .bg-gray-200');
        
        if (typeof performanceUtils !== 'undefined' && sectionImages.length > 0) {
            performanceUtils.addIntersectionObserver(
                Array.from(sectionImages),
                this.handleSectionImageIntersection.bind(this),
                { rootMargin: '50px' }
            );
        }
    }
    
    /**
     * Handle section image intersection for lazy loading
     */
    handleSectionImageIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Add actual image loading logic here
                entry.target.classList.add('loaded');
            }
        });
    }
    
    /**
     * Navigate to section with error handling
     */
    async navigateToSection(sectionId) {
        try {
            // Close navigation if open
            if (this.stickyNav) {
                this.stickyNav.closePopup();
            }
            
            // Use performance utils for smooth scrolling
            if (typeof performanceUtils !== 'undefined') {
                await performanceUtils.scrollToElement(sectionId);
            } else {
                // Fallback
                const element = document.getElementById(sectionId);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth' });
                }
            }
        } catch (error) {
            this.handleError(error, `Navigation to section ${sectionId}`);
        }
    }
    
    /**
     * Scroll to top with animation
     */
    async scrollToTop() {
        try {
            if (typeof performanceUtils !== 'undefined') {
                await performanceUtils.smoothScrollTo(0, 800);
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        } catch (error) {
            this.handleError(error, 'Scroll to top');
        }
    }
    
    /**
     * Scroll to bottom with animation
     */
    async scrollToBottom() {
        try {
            const documentHeight = Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.clientHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight
            );
            
            if (typeof performanceUtils !== 'undefined') {
                await performanceUtils.smoothScrollTo(documentHeight, 800);
            } else {
                window.scrollTo({ top: documentHeight, behavior: 'smooth' });
            }
        } catch (error) {
            this.handleError(error, 'Scroll to bottom');
        }
    }
    
    /**
     * Handle errors with context
     */
    handleError(error, context = 'Unknown') {
        console.error(`Error in ${context}:`, error);
        
        // You could send errors to an analytics service here
        // analytics.trackError(error, context);
        
        // Show user-friendly error message if needed
        if (this.shouldShowErrorToUser(error)) {
            this.showUserError(`Noe gikk galt. Vennligst prøv igjen.`);
        }
    }
    
    /**
     * Determine if error should be shown to user
     */
    shouldShowErrorToUser(error) {
        // Only show critical errors to users
        return error instanceof TypeError || 
               error.message.includes('network') ||
               error.message.includes('fetch');
    }
    
    /**
     * Show user-friendly error message
     */
    showUserError(message) {
        // Create a non-intrusive error notification
        const notification = document.createElement('div');
        notification.className = 'error-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #fee;
            border: 1px solid #fcc;
            padding: 12px 16px;
            border-radius: 6px;
            color: #c33;
            z-index: 10000;
            font-size: 14px;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
    
    /**
     * Cleanup when app is destroyed
     */
    destroy() {
        try {
            if (this.modalManager) {
                this.modalManager.destroy();
            }
            
            if (this.stickyNav) {
                this.stickyNav.destroy();
            }
            
            if (typeof performanceUtils !== 'undefined') {
                performanceUtils.cleanup();
            }
            
            this.isInitialized = false;
            console.log('Placy Prototype destroyed');
            
        } catch (error) {
            this.handleError(error, 'Application destruction');
        }
    }
}

/**
 * Simple error handler class
 */
class ErrorHandler {
    constructor() {
        this.errors = [];
        this.maxErrors = 50; // Limit stored errors
    }
    
    logError(error, context) {
        const errorEntry = {
            error: error.message || error,
            context,
            timestamp: new Date().toISOString(),
            stack: error.stack
        };
        
        this.errors.push(errorEntry);
        
        // Keep only recent errors
        if (this.errors.length > this.maxErrors) {
            this.errors.shift();
        }
        
        console.error(`[${context}]`, error);
    }
    
    getErrors() {
        return [...this.errors];
    }
    
    clearErrors() {
        this.errors = [];
    }
}

// Global functions for backward compatibility

function scrollToTop() {
    if (window.placyApp) {
        window.placyApp.scrollToTop();
    }
}

function openContact() {
    alert('Kontakt-funksjonalitet kommer snart!');
}

// Initialize app when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.placyApp = new PlacyApp();
        window.placyApp.init();
    });
} else {
    window.placyApp = new PlacyApp();
    window.placyApp.init();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PlacyApp, ErrorHandler };
}
