/**
 * POI API Accordion Toggle Functionality
 *
 * Handles expand/collapse of API data accordions in POI cards.
 * Multiple accordions can be open simultaneously.
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize accordion functionality
     */
    function init() {
        // Attach click handlers to all accordion triggers
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('.poi-api-accordion-trigger');
            if (trigger) {
                e.preventDefault();
                toggleAccordion(trigger);
            }
        });

        // Also handle keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                const trigger = e.target.closest('.poi-api-accordion-trigger');
                if (trigger) {
                    e.preventDefault();
                    toggleAccordion(trigger);
                }
            }
        });

        console.log('POI API Accordion: Initialized');
    }

    /**
     * Toggle accordion open/closed state
     * @param {HTMLElement} trigger - The accordion trigger button
     */
    function toggleAccordion(trigger) {
        const accordion = trigger.closest('.poi-api-accordion');
        if (!accordion) return;

        const isExpanded = accordion.getAttribute('data-expanded') === 'true';
        const body = accordion.querySelector('.poi-api-accordion-body');

        if (isExpanded) {
            // Close accordion
            accordion.setAttribute('data-expanded', 'false');
            trigger.setAttribute('aria-expanded', 'false');
            if (body) {
                body.setAttribute('aria-hidden', 'true');
            }
        } else {
            // Open accordion
            accordion.setAttribute('data-expanded', 'true');
            trigger.setAttribute('aria-expanded', 'true');
            if (body) {
                body.setAttribute('aria-hidden', 'false');
            }
        }
    }

    /**
     * Open a specific accordion by POI ID
     * @param {string|number} poiId - The POI ID
     */
    function openAccordionByPoiId(poiId) {
        const poiCard = document.querySelector(`[data-poi-id="${poiId}"]`);
        if (!poiCard) return;

        const accordion = poiCard.querySelector('.poi-api-accordion');
        if (!accordion) return;

        const trigger = accordion.querySelector('.poi-api-accordion-trigger');
        if (trigger && accordion.getAttribute('data-expanded') !== 'true') {
            toggleAccordion(trigger);
        }
    }

    /**
     * Close all accordions
     */
    function closeAllAccordions() {
        const accordions = document.querySelectorAll('.poi-api-accordion[data-expanded="true"]');
        accordions.forEach(function(accordion) {
            const trigger = accordion.querySelector('.poi-api-accordion-trigger');
            if (trigger) {
                toggleAccordion(trigger);
            }
        });
    }

    // Export functions for external use
    window.PoiApiAccordion = {
        toggle: toggleAccordion,
        openByPoiId: openAccordionByPoiId,
        closeAll: closeAllAccordions
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
