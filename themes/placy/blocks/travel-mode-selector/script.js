/**
 * Travel Mode Selector Block - JavaScript
 * 
 * Handles mode button clicks and broadcasts changes to all timelines and POI cards.
 */

(function() {
    'use strict';

    // Mode labels for display
    const MODE_LABEL = {
        walk: 'GANGTID',
        bike: 'SYKKELTID',
        drive: 'KJØRETID'
    };

    // Global current mode (shared across all selectors)
    let globalMode = 'walk';

    /**
     * Initialize a single mode selector block
     */
    function initModeSelector(block) {
        const modeButtons = block.querySelectorAll('.travel-mode-btn');
        const modeLabel = block.querySelector('.travel-mode-label .mode-text');

        /**
         * Update display based on current mode
         */
        function updateModeDisplay() {
            // Update mode label text
            if (modeLabel) {
                modeLabel.textContent = MODE_LABEL[globalMode];
            }
            
            // Update button states
            modeButtons.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.mode === globalMode);
            });
        }

        /**
         * Handle mode button click - broadcast to entire page
         */
        modeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                const newMode = btn.dataset.mode;
                if (newMode === globalMode) return;
                
                globalMode = newMode;
                
                // Update this block
                updateModeDisplay();
                
                console.log('[TravelModeSelector] Mode changed to:', newMode);
                
                // Dispatch global event for all timelines, POI cards, and maps
                document.dispatchEvent(new CustomEvent('travelModeChanged', { 
                    detail: { mode: newMode } 
                }));
            });
        });

        // Listen for global mode changes (from other selectors or timelines)
        document.addEventListener('travelModeChanged', (e) => {
            const newMode = e.detail?.mode;
            if (newMode && MODE_LABEL[newMode]) {
                globalMode = newMode;
                updateModeDisplay();
            }
        });

        // Initial display update
        updateModeDisplay();
    }

    /**
     * Initialize all mode selector blocks on the page
     */
    function init() {
        const blocks = document.querySelectorAll('.travel-mode-selector-block');
        blocks.forEach(initModeSelector);
        
        if (blocks.length > 0) {
            console.log('[TravelModeSelector] Initialized', blocks.length, 'selector(s)');
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-initialize on Gutenberg preview update (for editor)
    if (window.acf) {
        window.acf.addAction('render_block_preview', function() {
            setTimeout(init, 100);
        });
    }

})();
