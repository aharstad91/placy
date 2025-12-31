/**
 * Bysykkel Stations Block Script
 * 
 * Handles accordion functionality and live availability fetching.
 */
(function() {
    'use strict';

    function initBysykkelStations() {
        const blocks = document.querySelectorAll('.bysykkel-stations-block');
        
        blocks.forEach(block => {
            // Accordion functionality
            const headers = block.querySelectorAll('.bysykkel-station-header');
            
            headers.forEach(header => {
                // Click handler
                const handleToggle = function(e) {
                    // Don't toggle if clicking the map button
                    if (e.target.closest('.bysykkel-map-btn')) {
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
                        const stationItem = this.closest('.bysykkel-station-item');
                        const stationId = stationItem?.dataset.bysykkelStationId;
                        if (stationId) {
                            fetchBysykkelAvailability(stationId, content.querySelector('.bysykkel-availability'));
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

    async function fetchBysykkelAvailability(stationId, container) {
        if (!container) return;
        
        const loadingEl = container.querySelector('.bysykkel-availability-loading');
        const dataEl = container.querySelector('.bysykkel-availability-data');
        const errorEl = container.querySelector('.bysykkel-availability-error');
        
        // Check if already loaded
        if (dataEl && dataEl.dataset.loaded === 'true') {
            return;
        }
        
        if (loadingEl) loadingEl.style.display = 'flex';
        if (dataEl) dataEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        
        try {
            const response = await fetch(`/wp-json/placy/v1/bysykkel/availability/${stationId}`);
            
            if (!response.ok) {
                throw new Error('API error');
            }
            
            const data = await response.json();
            
            if (data.success) {
                const bikesEl = dataEl.querySelector('.bysykkel-bikes');
                const docksEl = dataEl.querySelector('.bysykkel-docks');
                
                if (bikesEl) {
                    bikesEl.textContent = data.data.num_bikes_available;
                    bikesEl.classList.toggle('low', data.data.num_bikes_available < 3);
                    bikesEl.classList.toggle('empty', data.data.num_bikes_available === 0);
                }
                
                if (docksEl) {
                    docksEl.textContent = data.data.num_docks_available;
                    docksEl.classList.toggle('low', data.data.num_docks_available < 3);
                    docksEl.classList.toggle('empty', data.data.num_docks_available === 0);
                }
                
                dataEl.dataset.loaded = 'true';
                if (loadingEl) loadingEl.style.display = 'none';
                if (dataEl) dataEl.style.display = 'flex';
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Bysykkel availability error:', error);
            if (loadingEl) loadingEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'block';
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBysykkelStations);
    } else {
        initBysykkelStations();
    }
    
    // Re-initialize if blocks are added dynamically (e.g., in editor)
    if (window.wp && window.wp.data) {
        const { subscribe } = window.wp.data;
        let lastBlockCount = 0;
        
        subscribe(() => {
            const blocks = document.querySelectorAll('.bysykkel-stations-block');
            if (blocks.length !== lastBlockCount) {
                lastBlockCount = blocks.length;
                setTimeout(initBysykkelStations, 100);
            }
        });
    }
})();
