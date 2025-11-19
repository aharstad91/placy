/**
 * Entur Live Departures - Frontend Integration
 * 
 * Fetches and displays real-time departure information from Entur API
 * Loads all departures on page load and displays them immediately.
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        API_ENDPOINT: (typeof enturSettings !== 'undefined' && enturSettings.restUrl) 
            ? enturSettings.restUrl 
            : window.location.origin + '/wp-json/placy/v1/entur/departures',
        REFRESH_INTERVAL: 60000, // 60 seconds
        MAX_RETRIES: 2,
        RETRY_DELAY: 1000,
    };

    // Cache for departure data
    const departureCache = new Map();

    /**
     * Initialize Entur integration
     */
    function init() {
        console.log('Entur Live Departures: Initializing...');
        
        // Load all departures on page load
        loadAllDepartures();
    }

    /**
     * Load all departures for all POI cards on the page
     */
    async function loadAllDepartures() {
        // Find all POI cards with Entur data
        const poiCards = document.querySelectorAll('[data-entur-stopplace-id][data-show-live-departures="1"]');
        
        if (poiCards.length === 0) {
            console.log('Entur: No POIs with live departures enabled');
            return;
        }

        console.log(`Entur: Loading departures for ${poiCards.length} POIs...`);

        // Group POIs by stopplace+quay+mode to minimize API requests
        const uniqueRequests = new Map();
        const poisByRequest = new Map();

        poiCards.forEach(function(poiCard) {
            const stopplaceId = poiCard.getAttribute('data-entur-stopplace-id');
            const quayId = poiCard.getAttribute('data-entur-quay-id');
            const transportMode = poiCard.getAttribute('data-entur-transport-mode');
            
            // Build cache key
            let cacheKey = stopplaceId;
            if (quayId) cacheKey += `-${quayId}`;
            if (transportMode) cacheKey += `-${transportMode}`;

            if (!uniqueRequests.has(cacheKey)) {
                uniqueRequests.set(cacheKey, { stopplaceId, quayId, transportMode });
                poisByRequest.set(cacheKey, []);
            }
            poisByRequest.get(cacheKey).push(poiCard);
        });

        console.log(`Entur: Making ${uniqueRequests.size} unique API requests for ${poiCards.length} POIs`);

        // Fetch all unique requests in parallel
        const fetchPromises = Array.from(uniqueRequests.entries()).map(async function([cacheKey, params]) {
            try {
                const result = await fetchDepartures(params.stopplaceId, params.quayId, params.transportMode);
                
                if (result && result.departures && result.departures.length > 0) {
                    // Display departures on all POI cards that use this request
                    const cards = poisByRequest.get(cacheKey);
                    cards.forEach(function(poiCard) {
                        displayDepartures(poiCard, result.departures, params.stopplaceId);
                    });
                    console.log(`Entur: Loaded ${result.departures.length} departures for ${cards.length} POI(s)`);
                } else {
                    console.log(`Entur: No departures for ${cacheKey}`);
                }
            } catch (error) {
                console.error(`Entur: Failed to fetch ${cacheKey}`, error);
            }
        });

        // Wait for all requests to complete
        await Promise.all(fetchPromises);
        console.log('Entur: All departures loaded');
    }

    /**
     * Show loading state
     * @param {HTMLElement} poiCard - The POI card element
     */
    function showLoadingState(poiCard) {
        const contentDiv = poiCard.querySelector('.poi-card-content, .poi-content, .poi-highlight-content, .poi-list-content, .poi-gallery-content');
        if (!contentDiv) return;

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'entur-loading-state mt-4 pt-4 border-t border-gray-200';
        loadingDiv.innerHTML = `
            <div class="flex items-center gap-3 text-gray-500">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium">Laster avganger...</span>
            </div>
        `;

        contentDiv.appendChild(loadingDiv);
    }

    /**
     * Hide loading state
     * @param {HTMLElement} poiCard - The POI card element
     */
    function hideLoadingState(poiCard) {
        const loadingDiv = poiCard.querySelector('.entur-loading-state');
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }

    /**
     * Fetch departures from API with retry logic
     * @param {string} stopplaceId - StopPlace ID
     * @param {string|null} quayId - Optional Quay ID
     * @param {string|null} transportMode - Optional transport mode filter
     * @returns {Promise<Object>} Departure data
     */
    async function fetchDepartures(stopplaceId, quayId = null, transportMode = null) {
        // Check cache first
        // Build cache key
        let cacheKey = stopplaceId;
        if (quayId) cacheKey += `-${quayId}`;
        if (transportMode) cacheKey += `-${transportMode}`;
        const cached = departureCache.get(cacheKey);
        
        if (cached && (Date.now() - cached.timestamp) < CONFIG.REFRESH_INTERVAL) {
            console.log('Entur: Using cached data');
            return cached.data;
        }

        // Build URL with query parameters
        let url = `${CONFIG.API_ENDPOINT}/${encodeURIComponent(stopplaceId)}`;
        const params = [];
        if (quayId) {
            params.push(`quay_id=${encodeURIComponent(quayId)}`);
        }
        if (transportMode) {
            params.push(`transport_mode=${encodeURIComponent(transportMode)}`);
        }
        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        // Fetch with retry logic
        for (let attempt = 0; attempt <= CONFIG.MAX_RETRIES; attempt++) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    signal: controller.signal,
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    console.warn(`Entur API returned status: ${response.status}`);
                    
                    // Don't retry on client errors (4xx)
                    if (response.status >= 400 && response.status < 500) {
                        return { success: false, departures: [] };
                    }
                    
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                // Cache the result (even if empty)
                departureCache.set(cacheKey, {
                    data: data,
                    timestamp: Date.now(),
                });

                return data;
                
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.warn(`Entur: Request timeout on attempt ${attempt + 1}`);
                } else {
                    console.error(`Entur: Fetch attempt ${attempt + 1} failed:`, error);
                }
                
                // Retry with delay if not last attempt
                if (attempt < CONFIG.MAX_RETRIES) {
                    await new Promise(resolve => setTimeout(resolve, CONFIG.RETRY_DELAY));
                } else {
                    // Last attempt failed - return empty result
                    console.warn('Entur: All retry attempts exhausted');
                    return { success: false, departures: [] };
                }
            }
        }

        // Should never reach here, but return empty as fallback
        return { success: false, departures: [] };
    }

    /**
     * Display departures in POI card
     * @param {HTMLElement} poiCard - The POI card element
     * @param {Array} departures - Departure data
     * @param {string} stopplaceId - StopPlace ID
     */
    function displayDepartures(poiCard, departures, stopplaceId) {
        // Remove loading state
        hideLoadingState(poiCard);

        // Find where to insert departures (after description)
        const contentDiv = poiCard.querySelector('.poi-card-content, .poi-content, .poi-highlight-content, .poi-list-content, .poi-gallery-content');
        if (!contentDiv) {
            console.warn('Entur: Could not find content div to insert departures');
            return;
        }

        // Create departures section
        const departuresSection = document.createElement('div');
        departuresSection.className = 'entur-departures-section mt-4 pt-4 border-t border-gray-200';

        // Build HTML
        let html = `
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Neste avganger
                </h4>
            </div>
            <div class="space-y-2">
        `;

        departures.forEach(function(departure) {
            const realtimeIndicator = departure.realtime 
                ? '<span class="text-emerald-600 text-xs ml-2">●</span>' 
                : '<span class="text-gray-400 text-xs ml-2" title="Rutetid (ikke sanntid)">?</span>';
            
            const relativeTime = formatRelativeTime(departure.relative_time);
            const quayInfo = departure.quay_code ? `<span class="text-gray-400 text-xs ml-2">${departure.quay_code}</span>` : '';

            html += `
                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="text-sm font-semibold text-gray-900 tabular-nums">${departure.time}</span>
                        <span class="text-sm text-gray-700 truncate">${escapeHtml(departure.destination)}</span>
                        ${quayInfo}
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <span class="text-sm font-medium text-gray-600">${relativeTime}</span>
                        ${realtimeIndicator}
                    </div>
                </div>
            `;
        });

        html += `
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                <a href="https://entur.no" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="text-xs text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1">
                    <span>Sanntidsdata fra Entur.no</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
                <span class="text-xs text-gray-400">
                    Hentet kl ${getCurrentTime()}
                </span>
            </div>
        `;

        departuresSection.innerHTML = html;
        contentDiv.appendChild(departuresSection);

        console.log('Entur: Departures displayed successfully');
    }

    /**
     * Format relative time
     * @param {number} minutes - Minutes until departure
     * @returns {string} Formatted time
     */
    function formatRelativeTime(minutes) {
        if (minutes === 0) {
            return 'Nå';
        } else if (minutes < 60) {
            return `${minutes} min`;
        } else {
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            if (remainingMinutes === 0) {
                return `${hours} t`;
            }
            return `${hours} t ${remainingMinutes} min`;
        }
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

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
