/**
 * Focus Panel Block Script
 * 
 * Handles opening/closing panels, focus management, keyboard navigation,
 * and ensures only one panel is open at a time.
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Track currently open panel
    let activePanel = null;
    let lastActiveElement = null;
    let scrollPosition = 0;

    // Focusable element selector
    const FOCUSABLE_SELECTORS = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(', ');

    /**
     * Initialize Focus Panel functionality
     */
    function init() {
        // Use event delegation for better performance with multiple instances
        document.addEventListener('click', handleClick);
        document.addEventListener('keydown', handleKeydown);
    }

    /**
     * Handle click events
     * @param {Event} event 
     */
    function handleClick(event) {
        const target = event.target;

        // Check for open trigger
        const openTrigger = target.closest('[data-focuspanel-open]');
        if (openTrigger) {
            event.preventDefault();
            const panelId = openTrigger.getAttribute('data-focuspanel-open');
            openPanel(panelId, openTrigger);
            return;
        }

        // Check for close button
        const closeButton = target.closest('[data-focuspanel-close]');
        if (closeButton) {
            event.preventDefault();
            const panelId = closeButton.getAttribute('data-focuspanel-close');
            closePanel(panelId);
            return;
        }

        // Check for overlay click (close on click outside panel)
        const overlay = target.closest('[data-focuspanel-overlay]');
        if (overlay && !target.closest('[data-focuspanel-panel]')) {
            const closeOnClick = overlay.getAttribute('data-close-on-click') !== 'false';
            if (closeOnClick) {
                const panelId = overlay.getAttribute('data-focuspanel-overlay');
                closePanel(panelId);
            }
        }
    }

    /**
     * Handle keyboard events
     * @param {KeyboardEvent} event 
     */
    function handleKeydown(event) {
        // Only handle when a panel is open
        if (!activePanel) return;

        // ESC to close
        if (event.key === 'Escape') {
            event.preventDefault();
            closePanel(activePanel.id);
            return;
        }

        // Tab key for focus trap
        if (event.key === 'Tab') {
            trapFocus(event);
        }
    }

    /**
     * Trap focus within the panel
     * @param {KeyboardEvent} event 
     */
    function trapFocus(event) {
        if (!activePanel) return;

        const panel = activePanel.panelElement;
        const focusableElements = panel.querySelectorAll(FOCUSABLE_SELECTORS);
        
        if (focusableElements.length === 0) return;

        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        if (event.shiftKey) {
            // Shift + Tab: going backward
            if (document.activeElement === firstFocusable || document.activeElement === panel) {
                event.preventDefault();
                lastFocusable.focus();
            }
        } else {
            // Tab: going forward
            if (document.activeElement === lastFocusable) {
                event.preventDefault();
                firstFocusable.focus();
            }
        }
    }

    /**
     * Open a panel
     * @param {string} panelId 
     * @param {HTMLElement} trigger 
     */
    function openPanel(panelId, trigger) {
        const overlay = document.querySelector(`[data-focuspanel-overlay="${panelId}"]`);
        const panel = overlay?.querySelector('[data-focuspanel-panel]');

        if (!overlay || !panel) {
            console.warn(`FocusPanel: Could not find overlay or panel for ID "${panelId}"`);
            return;
        }

        // Close any currently open panel first
        if (activePanel && activePanel.id !== panelId) {
            closePanel(activePanel.id, false); // Don't restore focus when switching
        }

        // Store last active element (trigger)
        lastActiveElement = trigger;

        // Update trigger aria-expanded
        trigger.setAttribute('aria-expanded', 'true');

        // Save scroll position for reference (no body scroll lock needed - overlay handles its own scroll)
        scrollPosition = window.pageYOffset;
        
        // Add class to html for styling hooks (e.g. hiding other fixed elements)
        document.documentElement.classList.add('focuspanel-open');

        // Show overlay
        overlay.removeAttribute('hidden');
        
        // Show sticky close button (which is now outside overlay)
        const stickyCloseBtn = document.querySelector(`[data-focuspanel-close="${panelId}"].focuspanel__close--sticky`);
        if (stickyCloseBtn) {
            stickyCloseBtn.removeAttribute('hidden');
        }

        // Use requestAnimationFrame to ensure the hidden attribute is removed before adding class
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                overlay.classList.add('is-open');
                if (stickyCloseBtn) {
                    stickyCloseBtn.classList.add('is-visible');
                }
            });
        });

        // Focus the panel
        panel.focus();

        // Store active panel reference
        activePanel = {
            id: panelId,
            overlayElement: overlay,
            panelElement: panel,
            triggerElement: trigger
        };

        // Set data attribute on document for external reference
        // Using 'focuspanelActive' to avoid conflict with 'data-focuspanel-open' selector
        document.documentElement.dataset.focuspanelActive = panelId;

        // Fire analytics event
        fireAnalyticsEvent(overlay, 'open');
    }

    /**
     * Close a panel
     * @param {string} panelId 
     * @param {boolean} restoreFocus - Whether to restore focus to trigger (default: true)
     */
    function closePanel(panelId, restoreFocus = true) {
        const overlay = document.querySelector(`[data-focuspanel-overlay="${panelId}"]`);
        
        if (!overlay) return;

        const trigger = document.querySelector(`[data-focuspanel-open="${panelId}"]`);
        const stickyCloseBtn = document.querySelector(`[data-focuspanel-close="${panelId}"].focuspanel__close--sticky`);

        // Update trigger aria-expanded
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }

        // Remove open class (triggers CSS transition)
        overlay.classList.remove('is-open');
        
        // Hide sticky close button
        if (stickyCloseBtn) {
            stickyCloseBtn.classList.remove('is-visible');
        }

        // Wait for animation to complete, then hide
        const duration = getComputedStyle(overlay).getPropertyValue('--focuspanel-duration') || '550ms';
        const durationMs = parseFloat(duration) * (duration.includes('ms') ? 1 : 1000);

        setTimeout(() => {
            overlay.setAttribute('hidden', '');
            
            // Hide sticky close button completely
            if (stickyCloseBtn) {
                stickyCloseBtn.setAttribute('hidden', '');
            }
            
            // Remove the class
            document.documentElement.classList.remove('focuspanel-open');

            // Restore focus to trigger
            if (restoreFocus && lastActiveElement) {
                lastActiveElement.focus();
            }

            // Clear active panel if this was it
            if (activePanel && activePanel.id === panelId) {
                activePanel = null;
            }

            // Clear document data attribute
            delete document.documentElement.dataset.focuspanelActive;

        }, durationMs);

        // Fire analytics event
        fireAnalyticsEvent(overlay, 'close');
    }

    /**
     * Fire analytics event (if gtag or dataLayer is available)
     * @param {HTMLElement} overlay 
     * @param {string} action - 'open' or 'close'
     */
    function fireAnalyticsEvent(overlay, action) {
        const eventName = action === 'open' 
            ? overlay.getAttribute('data-event-open') || 'focuspanel_open'
            : overlay.getAttribute('data-event-close') || 'focuspanel_close';

        const panelId = overlay.getAttribute('data-focuspanel-overlay');

        // Try gtag (Google Analytics 4)
        if (typeof gtag === 'function') {
            gtag('event', eventName, {
                'panel_id': panelId
            });
        }

        // Try dataLayer (Google Tag Manager)
        if (typeof dataLayer !== 'undefined' && Array.isArray(dataLayer)) {
            dataLayer.push({
                'event': eventName,
                'panelId': panelId
            });
        }

        // Custom event for other tracking systems
        const customEvent = new CustomEvent('focuspanel:' + action, {
            detail: {
                panelId: panelId,
                eventName: eventName
            },
            bubbles: true
        });
        overlay.dispatchEvent(customEvent);
    }

    /**
     * Expose public API for programmatic control
     */
    window.FocusPanel = {
        open: function(panelId) {
            const trigger = document.querySelector(`[data-focuspanel-open="${panelId}"]`);
            if (trigger) {
                openPanel(panelId, trigger);
            }
        },
        close: function(panelId) {
            closePanel(panelId);
        },
        isOpen: function(panelId) {
            return activePanel && activePanel.id === panelId;
        },
        getActivePanel: function() {
            return activePanel ? activePanel.id : null;
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
