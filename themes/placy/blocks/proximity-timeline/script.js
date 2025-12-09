/**
 * Proximity Timeline Block - JavaScript
 * 
 * Listens for travel mode changes and calculates distances for timeline cards.
 */

(function() {
    'use strict';

    console.log('[ProximityTimeline] Block loaded');

    // Travel mode configuration
    const speeds = { walk: 5, bike: 15, drive: 40 }; // km/h
    const modeText = { walk: 'til fots', bike: 'sykkel', drive: 'bil' };
    const modeIcon = { 
        walk: '<i class="fas fa-shoe-prints"></i>', 
        bike: '<i class="fas fa-bicycle"></i>', 
        drive: '<i class="fas fa-car"></i>' 
    };
    
    let currentMode = 'walk';

    /**
     * Get project coordinates from global config or story meta
     */
    function getProjectCoords() {
        // Try placyMapConfig (set by story map)
        if (typeof placyMapConfig !== 'undefined' && placyMapConfig.startLocation) {
            return {
                lng: placyMapConfig.startLocation[0],
                lat: placyMapConfig.startLocation[1]
            };
        }

        // Try proximity filter state
        if (typeof ProximityFilterState !== 'undefined') {
            const coords = ProximityFilterState.getProjectCoords();
            if (coords) return coords;
        }

        // Fallback: Try to get from story's project field (if available in DOM)
        const storyMeta = document.querySelector('[data-project-coords]');
        if (storyMeta) {
            try {
                const coords = JSON.parse(storyMeta.dataset.projectCoords);
                return coords;
            } catch (e) {
                console.warn('[ProximityTimeline] Failed to parse project coords from DOM');
            }
        }

        console.warn('[ProximityTimeline] No project coordinates available');
        return null;
    }

    /**
     * Calculate travel time using Haversine formula
     */
    function calculateTravelTime(poiCoords, projectCoords, mode) {
        const R = 6371; // Earth radius in km
        const dLat = (poiCoords.lat - projectCoords.lat) * Math.PI / 180;
        const dLon = (poiCoords.lng - projectCoords.lng) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(projectCoords.lat * Math.PI / 180) * Math.cos(poiCoords.lat * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distance = R * c;
        return Math.ceil((distance / speeds[mode]) * 60);
    }

    /**
     * Update all timeline cards with current travel mode
     */
    function updateTimelineCards(mode) {
        const projectCoords = getProjectCoords();
        if (!projectCoords) {
            console.warn('[ProximityTimeline] Cannot update cards - no project coordinates');
            return;
        }

        // Find all timeline cards with coordinates
        const cards = document.querySelectorAll('.proximity-timeline-card[data-poi-coords]');
        console.log('[ProximityTimeline] Updating', cards.length, 'cards to mode:', mode);

        cards.forEach(card => {
            const coordsAttr = card.dataset.poiCoords;
            if (!coordsAttr) return;

            try {
                const coords = JSON.parse(coordsAttr);
                const poiCoords = Array.isArray(coords) 
                    ? { lat: coords[0], lng: coords[1] }
                    : coords;

                const travelTime = calculateTravelTime(poiCoords, projectCoords, mode);

                // Update badge content
                const badgeContainer = card.querySelector('.timeline-travel-time');
                const textEl = card.querySelector('.poi-travel-time-text');
                const iconEl = card.querySelector('.poi-travel-icon');

                if (badgeContainer && textEl && iconEl) {
                    textEl.textContent = `${travelTime} min ${modeText[mode]}`;
                    iconEl.innerHTML = modeIcon[mode];
                    badgeContainer.style.display = 'flex';
                }
            } catch (e) {
                console.warn('[ProximityTimeline] Error updating card:', e);
            }
        });
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        // Initial update with walk mode
        setTimeout(() => {
            updateTimelineCards(currentMode);
        }, 100);

        // Listen for travel mode changes
        document.addEventListener('travelModeChanged', (e) => {
            if (e.detail && e.detail.mode) {
                currentMode = e.detail.mode;
                console.log('[ProximityTimeline] Travel mode changed to:', currentMode);
                updateTimelineCards(currentMode);
            }
        });

        console.log('[ProximityTimeline] Initialized with mode:', currentMode);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
