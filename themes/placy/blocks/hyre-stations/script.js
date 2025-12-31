/**
 * Hyre Stations Block Script
 * 
 * Handles accordion functionality and live availability fetching for Hyre car sharing.
 */
(function() {
    'use strict';

    function initHyreStations() {
        const blocks = document.querySelectorAll('.hyre-stations-block');
        
        blocks.forEach(block => {
            // Accordion functionality
            const headers = block.querySelectorAll('.hyre-station-header');
            
            headers.forEach(header => {
                // Click handler
                const handleToggle = function(e) {
                    // Don't toggle if clicking the map button
                    if (e.target.closest('.hyre-map-btn')) {
                        return;
                    }
                    
                    const expanded = this.getAttribute('aria-expanded') === 'true';
                    const contentId = this.getAttribute('aria-controls');
                    const content = document.getElementById(contentId);
                    
                    if (expanded) {
                        // Close
                        this.setAttribute('aria-expanded', 'false');
                        content.hidden = true;
                    } else {
                        // Open
                        this.setAttribute('aria-expanded', 'true');
                        content.hidden = false;
                        
                        // Fetch availability
                        const stationItem = this.closest('.hyre-station-item');
                        const stationId = stationItem?.dataset.hyreStationId;
                        if (stationId) {
                            fetchHyreAvailability(stationId, content.querySelector('.hyre-availability'));
                        }
                    }
                };

                header.addEventListener('click', handleToggle);
                
                // Keyboard support for div with role="button"
                header.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        handleToggle.call(this, e);
                    }
                });
            });
        });
    }

    async function fetchHyreAvailability(stationId, container) {
        if (!container) return;
        
        const loadingEl = container.querySelector('.hyre-availability-loading');
        const dataEl = container.querySelector('.hyre-availability-data');
        const errorEl = container.querySelector('.hyre-availability-error');
        
        // Check if already loaded
        if (dataEl && dataEl.dataset.loaded === 'true') {
            return;
        }
        
        if (loadingEl) loadingEl.style.display = 'flex';
        if (dataEl) dataEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        
        try {
            const response = await fetch(`/wp-json/placy/v1/hyre/availability/${stationId}`);
            
            if (!response.ok) {
                throw new Error('API error');
            }
            
            const data = await response.json();
            
            if (data.success) {
                const carsEl = dataEl.querySelector('.hyre-cars');
                
                if (carsEl) {
                    const availableCars = data.data.num_vehicles_available || 0;
                    carsEl.textContent = availableCars;
                    carsEl.classList.toggle('low', availableCars < 2 && availableCars > 0);
                    carsEl.classList.toggle('empty', availableCars === 0);
                }
                
                dataEl.dataset.loaded = 'true';
                if (loadingEl) loadingEl.style.display = 'none';
                if (dataEl) dataEl.style.display = 'flex';
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Hyre availability error:', error);
            if (loadingEl) loadingEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'block';
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHyreStations);
    } else {
        initHyreStations();
    }
    
    // Re-initialize if blocks are added dynamically (e.g., in editor)
    if (window.wp && window.wp.data) {
        const { subscribe } = window.wp.data;
        let lastBlockCount = 0;
        
        subscribe(() => {
            const blocks = document.querySelectorAll('.hyre-stations-block');
            if (blocks.length !== lastBlockCount) {
                lastBlockCount = blocks.length;
                setTimeout(initHyreStations, 100);
            }
        });
    }
})();
