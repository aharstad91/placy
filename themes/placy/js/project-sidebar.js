/**
 * Project Sidebar - Dropdown & Global State Manager
 * 
 * Handles:
 * - Dropdown toggling
 * - Travel Mode state (global sync)
 * - Time Budget state (global sync)
 * - Navigation scroll
 */

(function() {
    'use strict';

    // =========================================================================
    // GLOBAL STATE
    // =========================================================================
    
    window.PlacyGlobalState = window.PlacyGlobalState || {
        travelMode: 'walk',
        timeBudget: 15
    };

    const state = window.PlacyGlobalState;

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    document.addEventListener('DOMContentLoaded', function() {
        initDropdowns();
        initTravelMode();
        initTimeBudget();
        initNavigation();
        initOpenFullMap();
        initGlobalEventListeners();
        restoreFromLocalStorage();
    });

    // =========================================================================
    // GLOBAL EVENT LISTENERS (for modal sync)
    // =========================================================================

    function initGlobalEventListeners() {
        // Listen for travel mode changes from modals
        document.addEventListener('placy:travelModeChange', function(e) {
            if (e.detail && e.detail.source !== 'sidebar') {
                const mode = e.detail.mode || e.detail.travelMode;
                if (mode) {
                    // Always update UI (global state is already updated by the emitter)
                    state.travelMode = mode;
                    updateTravelModeUI(mode);
                    saveToLocalStorage();
                }
            }
        });

        // Listen for time budget changes from modals
        document.addEventListener('placy:timeBudgetChange', function(e) {
            if (e.detail && e.detail.source !== 'sidebar') {
                const budget = e.detail.budget || e.detail.timeBudget;
                if (budget) {
                    // Always update UI (global state is already updated by the emitter)
                    state.timeBudget = budget;
                    updateTimeBudgetUI(budget);
                    saveToLocalStorage();
                }
            }
        });
    }

    // =========================================================================
    // DROPDOWNS
    // =========================================================================

    function initDropdowns() {
        // Toggle dropdown on trigger click
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('.project-sidebar__dropdown-trigger');
            
            if (trigger) {
                e.preventDefault();
                const dropdown = trigger.closest('.project-sidebar__dropdown');
                
                // Close other dropdowns
                document.querySelectorAll('.project-sidebar__dropdown.is-open').forEach(function(d) {
                    if (d !== dropdown) d.classList.remove('is-open');
                });
                
                // Toggle this dropdown
                dropdown.classList.toggle('is-open');
                trigger.setAttribute('aria-expanded', dropdown.classList.contains('is-open'));
                return;
            }

            // Close dropdowns when clicking outside
            if (!e.target.closest('.project-sidebar__dropdown')) {
                document.querySelectorAll('.project-sidebar__dropdown.is-open').forEach(function(d) {
                    d.classList.remove('is-open');
                });
            }
        });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.project-sidebar__dropdown.is-open').forEach(function(d) {
                    d.classList.remove('is-open');
                });
            }
        });
    }

    // =========================================================================
    // TRAVEL MODE
    // =========================================================================

    function initTravelMode() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-travel-mode]');
            if (!btn) return;
            
            // Only handle clicks within the project sidebar
            if (!btn.closest('.project-sidebar')) return;
            
            e.preventDefault();
            const mode = btn.dataset.travelMode;
            setTravelMode(mode);
            
            // Close dropdown
            const dropdown = btn.closest('.project-sidebar__dropdown');
            if (dropdown) dropdown.classList.remove('is-open');
        });
    }

    function setTravelMode(mode) {
        if (!['walk', 'bike', 'car'].includes(mode)) return;
        
        state.travelMode = mode;
        saveToLocalStorage();
        
        // Update sidebar UI
        updateTravelModeUI(mode);
        
        // Emit global event for Story Chapter blocks to listen to
        document.dispatchEvent(new CustomEvent('placy:travelModeChange', {
            detail: { mode: mode, travelMode: mode, source: 'sidebar' }
        }));
        
        // Note: window.setTravelMode (from chapter-mega-modal) is called via
        // the placy:travelModeChange event handler, so no need to call directly
        
    }

    function updateTravelModeUI(mode) {
        // Update toggle buttons (new ns-toggle-group)
        document.querySelectorAll('.project-sidebar [data-travel-mode]').forEach(function(btn) {
            const isActive = btn.dataset.travelMode === mode;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });
    }

    // =========================================================================
    // TIME BUDGET
    // =========================================================================

    function initTimeBudget() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-time-budget]');
            if (!btn) return;
            
            // Only handle clicks within the project sidebar
            if (!btn.closest('.project-sidebar')) return;
            
            e.preventDefault();
            const budget = parseInt(btn.dataset.timeBudget, 10);
            setTimeBudget(budget);
            
            // Close dropdown
            const dropdown = btn.closest('.project-sidebar__dropdown');
            if (dropdown) dropdown.classList.remove('is-open');
        });
    }

    function setTimeBudget(budget) {
        if (![5, 10, 15, 20, 30].includes(budget)) return;
        
        state.timeBudget = budget;
        saveToLocalStorage();
        
        // Update sidebar UI
        updateTimeBudgetUI(budget);
        
        // Emit global event for Story Chapter blocks to listen to
        document.dispatchEvent(new CustomEvent('placy:timeBudgetChange', {
            detail: { budget: budget, timeBudget: budget, source: 'sidebar' }
        }));
        
        // Note: window.setTimeBudget (from chapter-mega-modal) is called via
        // the placy:timeBudgetChange event handler, so no need to call directly
        
    }

    function updateTimeBudgetUI(budget) {
        // Update toggle buttons (new ns-toggle-group)
        document.querySelectorAll('.project-sidebar [data-time-budget]').forEach(function(btn) {
            const isActive = parseInt(btn.dataset.timeBudget, 10) === budget;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });
    }

    // =========================================================================
    // NAVIGATION
    // =========================================================================

    function initOpenFullMap() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-open-global-map]');
            if (!btn) return;
            
            e.preventDefault();
            openFullMap();
        });
    }

    function openFullMap() {
        
        // Option 1: Use Master Map Modal (preferred)
        if (window.openMasterMap) {
            window.openMasterMap();
            return;
        }
        
        // Option 2: Try to open a global map modal if it exists (legacy)
        const globalMap = document.querySelector('.ns-global-map');
        if (globalMap) {
            globalMap.classList.add('ns-modal-open');
            globalMap.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ns-modal-active');
            return;
        }
        
        // Option 3: Find first story chapter and open its modal with all places
        const firstChapter = document.querySelector('[data-chapter-id]');
        if (firstChapter && window.openChapterMegaModal) {
            const chapterId = firstChapter.dataset.chapterId;
            window.openChapterMegaModal(chapterId);
            return;
        }
        
        // Option 4: Find first "See all places" button and click it
        const seeAllBtn = document.querySelector('.story-chapter-card__see-all');
        if (seeAllBtn) {
            seeAllBtn.click();
            return;
        }
        
    }

    function initNavigation() {
        document.addEventListener('click', function(e) {
            const navItem = e.target.closest('[data-nav-anchor]');
            if (!navItem) return;
            
            e.preventDefault();
            const anchor = navItem.dataset.navAnchor;
            const type = navItem.dataset.navType || 'scroll';
            
            if (type === 'modal') {
                // Open modal - trigger event for chapter modals
                document.dispatchEvent(new CustomEvent('placy:openChapter', {
                    detail: { chapterId: anchor }
                }));
            } else {
                // Scroll to anchor
                scrollToAnchor(anchor);
            }
        });
    }

    function scrollToAnchor(anchor) {
        const element = document.getElementById(anchor) || 
                       document.querySelector('[data-anchor="' + anchor + '"]') ||
                       document.querySelector('[data-chapter-id="' + anchor + '"]');
        
        if (!element) {
            return;
        }

        element.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });

        // Update URL hash without triggering scroll
        history.pushState(null, '', '#' + anchor);
    }

    // =========================================================================
    // LOCAL STORAGE
    // =========================================================================

    function saveToLocalStorage() {
        try {
            localStorage.setItem('placy_travel_mode', state.travelMode);
            localStorage.setItem('placy_time_budget', state.timeBudget.toString());
        } catch (e) {
        }
    }

    function restoreFromLocalStorage() {
        try {
            const savedMode = localStorage.getItem('placy_travel_mode');
            const savedBudget = localStorage.getItem('placy_time_budget');
            
            if (savedMode && ['walk', 'bike', 'car'].includes(savedMode)) {
                state.travelMode = savedMode;
                updateTravelModeUI(savedMode);
            }
            
            if (savedBudget) {
                const budget = parseInt(savedBudget, 10);
                if ([5, 10, 15, 20, 30].includes(budget)) {
                    state.timeBudget = budget;
                    updateTimeBudgetUI(budget);
                }
            }
        } catch (e) {
        }
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    window.PlacySidebar = {
        setTravelMode: setTravelMode,
        setTimeBudget: setTimeBudget,
        openFullMap: openFullMap,
        getState: function() { return state; }
    };

})();
