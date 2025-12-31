/**
 * Bus Stops Block Script
 * 
 * Handles accordion functionality and live departure fetching via Entur API.
 */
(function() {
    'use strict';

    function initBusStops() {
        const blocks = document.querySelectorAll('.bus-stops-block');
        
        blocks.forEach(block => {
            // Accordion functionality
            const headers = block.querySelectorAll('.bus-stop-header');
            
            headers.forEach(header => {
                // Click handler
                const handleToggle = function(e) {
                    // Don't toggle if clicking the map button
                    if (e.target.closest('.bus-map-btn')) {
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
                        
                        // Fetch departures
                        const stopItem = this.closest('.bus-stop-item');
                        const stopplaceId = stopItem?.dataset.enturStopplaceId;
                        const quayId = stopItem?.dataset.enturQuayId;
                        if (stopplaceId) {
                            fetchDepartures(stopplaceId, quayId, content.querySelector('.bus-departures'));
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

    async function fetchDepartures(stopplaceId, quayId, container) {
        if (!container) return;
        
        const loadingEl = container.querySelector('.bus-departures-loading');
        const dataEl = container.querySelector('.bus-departures-data');
        const errorEl = container.querySelector('.bus-departures-error');
        const listEl = container.querySelector('.bus-departures-list');
        
        // Always refetch for fresh data
        if (loadingEl) loadingEl.style.display = 'flex';
        if (dataEl) dataEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        
        try {
            let url = `/wp-json/placy/v1/entur/departures/${stopplaceId}`;
            if (quayId) {
                url += `?quay_id=${quayId}`;
            }
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error('API error');
            }
            
            const data = await response.json();
            
            if (data.success && data.data && data.data.length > 0) {
                // Clear and populate list
                listEl.innerHTML = '';
                
                const departures = data.data.slice(0, 5); // Show max 5 departures
                
                departures.forEach(dep => {
                    const li = document.createElement('li');
                    li.className = 'bus-departure-item';
                    
                    const lineEl = document.createElement('span');
                    lineEl.className = 'bus-departure-line';
                    if (dep.line && (dep.line.includes('FB') || dep.line.includes('Værnes'))) {
                        lineEl.classList.add('airport');
                    }
                    lineEl.textContent = dep.line || '?';
                    
                    const destEl = document.createElement('span');
                    destEl.className = 'bus-departure-destination';
                    destEl.textContent = dep.destination || 'Ukjent';
                    
                    const timeEl = document.createElement('span');
                    timeEl.className = 'bus-departure-time';
                    
                    // Format departure time
                    const depTime = formatDepartureTime(dep.expected_departure || dep.aimed_departure);
                    timeEl.textContent = depTime.text;
                    if (depTime.soon) timeEl.classList.add('soon');
                    if (depTime.now) timeEl.classList.add('now');
                    
                    li.appendChild(lineEl);
                    li.appendChild(destEl);
                    li.appendChild(timeEl);
                    listEl.appendChild(li);
                });
                
                if (loadingEl) loadingEl.style.display = 'none';
                if (dataEl) dataEl.style.display = 'block';
            } else if (data.success && (!data.data || data.data.length === 0)) {
                listEl.innerHTML = '<li class="bus-no-departures">Ingen planlagte avganger</li>';
                if (loadingEl) loadingEl.style.display = 'none';
                if (dataEl) dataEl.style.display = 'block';
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Bus departures error:', error);
            if (loadingEl) loadingEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'block';
        }
    }

    function formatDepartureTime(isoString) {
        if (!isoString) return { text: '-', soon: false, now: false };
        
        const depTime = new Date(isoString);
        const now = new Date();
        const diffMs = depTime - now;
        const diffMin = Math.round(diffMs / 60000);
        
        if (diffMin < 1) {
            return { text: 'Nå', soon: false, now: true };
        } else if (diffMin < 10) {
            return { text: `${diffMin} min`, soon: true, now: false };
        } else if (diffMin < 60) {
            return { text: `${diffMin} min`, soon: false, now: false };
        } else {
            // Show HH:MM format
            const hours = depTime.getHours().toString().padStart(2, '0');
            const minutes = depTime.getMinutes().toString().padStart(2, '0');
            return { text: `${hours}:${minutes}`, soon: false, now: false };
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBusStops);
    } else {
        initBusStops();
    }
    
    // Re-initialize if blocks are added dynamically (e.g., in editor)
    if (window.wp && window.wp.data) {
        const { subscribe } = window.wp.data;
        let lastBlockCount = 0;
        
        subscribe(() => {
            const blocks = document.querySelectorAll('.bus-stops-block');
            if (blocks.length !== lastBlockCount) {
                lastBlockCount = blocks.length;
                setTimeout(initBusStops, 100);
            }
        });
    }
})();
