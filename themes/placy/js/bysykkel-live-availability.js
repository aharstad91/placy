/**
 * Trondheim Bysykkel Live Availability - Frontend Integration
 *
 * Fetches and displays real-time bike availability from GBFS API
 * Loads all availability data on page load and displays immediately.
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        API_ENDPOINT: (typeof bysykkelSettings !== 'undefined' && bysykkelSettings.restUrl)
            ? bysykkelSettings.restUrl
            : window.location.origin + '/placy/wp-json/placy/v1/bysykkel/availability',
        REFRESH_INTERVAL: 60000, // 60 seconds
        MAX_RETRIES: 2,
        RETRY_DELAY: 1000
    };

    // Cache for availability data
    const availabilityCache = new Map();

    /**
     * Initialize Bysykkel integration
     */
    function init() {

        // Load all availability data on page load
        loadAllAvailability();

        // Refresh every minute
        setInterval(loadAllAvailability, CONFIG.REFRESH_INTERVAL);
    }

    /**
     * Load availability for all POI cards on the page
     */
    async function loadAllAvailability() {
        // Find all POI cards with Bysykkel data (exclude new block structure which has its own script)
        const allElements = document.querySelectorAll('[data-bysykkel-station-id][data-show-bike-availability="1"]');
        
        // Filter out elements that are handled by api-accordion.js or bysykkel-stations-block
        const poiCards = Array.from(allElements).filter(function(el) {
            return !el.closest('.bysykkel-stations-block') && !el.classList.contains('ns-api-card');
        });

        if (poiCards.length === 0) {
            return;
        }


        // Group POIs by station_id to minimize API requests
        const uniqueStations = new Map();
        const poisByStation = new Map();

        poiCards.forEach(function(poiCard) {
            const stationId = poiCard.getAttribute('data-bysykkel-station-id');

            if (!uniqueStations.has(stationId)) {
                uniqueStations.set(stationId, true);
                poisByStation.set(stationId, []);
            }
            poisByStation.get(stationId).push(poiCard);
        });


        // Fetch all unique stations in parallel
        const fetchPromises = Array.from(uniqueStations.keys()).map(async function(stationId) {
            try {
                const result = await fetchAvailability(stationId);

                if (result && result.bikes_available !== undefined) {
                    // Display availability on all POI cards that use this station
                    const cards = poisByStation.get(stationId);
                    cards.forEach(function(poiCard) {
                        displayAvailability(poiCard, result);
                    });
                } else {
                }
            } catch (error) {
            }
        });

        // Wait for all requests to complete
        await Promise.all(fetchPromises);
    }

    /**
     * Fetch availability from API with retry logic
     * @param {string} stationId - Station ID
     * @returns {Promise<Object>} Availability data
     */
    async function fetchAvailability(stationId) {
        // Check cache first
        const cached = availabilityCache.get(stationId);

        if (cached && (Date.now() - cached.timestamp) < CONFIG.REFRESH_INTERVAL) {
            return cached.data;
        }

        // Build URL
        const url = `${CONFIG.API_ENDPOINT}/${encodeURIComponent(stationId)}`;

        // Fetch with retry logic
        for (let attempt = 0; attempt <= CONFIG.MAX_RETRIES; attempt++) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) {

                    if (response.status >= 400 && response.status < 500) {
                        return { bikes_available: 0, docks_available: 0 };
                    }

                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                // Cache the result
                availabilityCache.set(stationId, {
                    data: data,
                    timestamp: Date.now()
                });

                return data;

            } catch (error) {
                if (error.name === 'AbortError') {
                } else {
                }

                if (attempt < CONFIG.MAX_RETRIES) {
                    await new Promise(resolve => setTimeout(resolve, CONFIG.RETRY_DELAY));
                } else {
                    return { bikes_available: 0, docks_available: 0 };
                }
            }
        }

        return { bikes_available: 0, docks_available: 0 };
    }

    /**
     * Display availability in POI card
     * @param {HTMLElement} poiCard - The POI card element
     * @param {Object} availability - Availability data
     */
    function displayAvailability(poiCard, availability) {
        // Remove any existing availability section
        const existingSection = poiCard.querySelector('.bysykkel-availability-section');
        if (existingSection) {
            existingSection.remove();
        }

        // Check if there's an accordion structure - if so, insert into accordion content
        const accordionContent = poiCard.querySelector('.poi-api-accordion-content');
        
        // Find where to insert availability
        let contentDiv;
        if (accordionContent) {
            // Insert into accordion content area
            contentDiv = accordionContent;
        } else {
            // Fallback: insert after description (old behavior)
            contentDiv = poiCard.querySelector('.poi-card-content, .poi-content, .poi-highlight-content, .poi-list-content, .poi-gallery-content');
        }
        
        if (!contentDiv) {
            return;
        }

        // Check if station is operational
        if (!availability.is_renting || !availability.is_installed) {
            return;
        }

        // Create availability section
        const availabilitySection = document.createElement('div');
        availabilitySection.className = 'bysykkel-availability-section mt-4 pt-4 border-t border-gray-200';

        // Determine availability status
        const bikesAvailable = availability.bikes_available || 0;
        const docksAvailable = availability.docks_available || 0;
        const statusColor = bikesAvailable > 0 ? 'text-emerald-600' : 'text-gray-400';
        const statusIcon = bikesAvailable > 0 ? '●' : '○';

        // Build HTML
        const html = `
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Bysykkel Tilgjengelighet
                </h4>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                        </svg>
                        <span class="text-sm text-gray-700">Ledige sykler</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-sm font-semibold text-gray-900">${bikesAvailable}</span>
                        <span class="${statusColor} text-sm ml-1">${statusIcon}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/>
                        </svg>
                        <span class="text-sm text-gray-700">Ledige låser</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-sm font-semibold text-gray-900">${docksAvailable}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                <a href="https://trondheimbysykkel.no" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="text-xs text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1">
                    <span>Data fra Trondheim Bysykkel</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
                <span class="text-xs text-gray-400">
                    Hentet kl ${getCurrentTime()}
                </span>
            </div>
        `;

        availabilitySection.innerHTML = html;
        contentDiv.appendChild(availabilitySection);

    }

    /**
     * Get current time in HH:MM format
     * @returns {string} Formatted time
     */
    function getCurrentTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
