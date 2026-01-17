/**
 * Entur Live Departures - Frontend Integration
 *
 * Fetches and displays real-time departure information from Entur API
 * Supports grouped departures by direction for multi-platform stops.
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
        RETRY_DELAY: 1000
    };

    // Cache for departure data
    const departureCache = new Map();

    /**
     * Initialize Entur integration
     */
    function init() {

        // Load all departures on page load
        loadAllDepartures();
    }

    /**
     * Load all departures for all POI cards on the page
     */
    async function loadAllDepartures() {
        // Find all POI cards with Entur data (exclude new block structure which has its own script)
        const allElements = document.querySelectorAll('[data-entur-stopplace-id][data-show-live-departures="1"]');
        
        // Filter out elements that are handled by api-accordion.js or bus-stops-block
        const poiCards = Array.from(allElements).filter(function(el) {
            return !el.closest('.bus-stops-block') && !el.classList.contains('ns-api-card');
        });

        if (poiCards.length === 0) {
            return;
        }


        // Group POIs by stopplace+quay+mode to minimize API requests
        const uniqueRequests = new Map();
        const poisByRequest = new Map();

        poiCards.forEach(function(poiCard) {
            const stopplaceId = poiCard.getAttribute('data-entur-stopplace-id');
            const quayId = poiCard.getAttribute('data-entur-quay-id');
            const transportMode = poiCard.getAttribute('data-entur-transport-mode');
            const lineFilter = poiCard.getAttribute('data-entur-line-filter');
            const groupByDirection = poiCard.getAttribute('data-entur-group-by-direction') !== '0';

            // Build cache key
            let cacheKey = stopplaceId;
            if (quayId) cacheKey += `-${quayId}`;
            if (transportMode) cacheKey += `-${transportMode}`;
            if (lineFilter) cacheKey += `-${lineFilter}`;

            if (!uniqueRequests.has(cacheKey)) {
                uniqueRequests.set(cacheKey, { stopplaceId, quayId, transportMode, lineFilter, groupByDirection });
                poisByRequest.set(cacheKey, []);
            }
            poisByRequest.get(cacheKey).push(poiCard);
        });


        // Fetch all unique requests in parallel
        const fetchPromises = Array.from(uniqueRequests.entries()).map(async function([cacheKey, params]) {
            try {
                const result = await fetchDepartures(params.stopplaceId, params.quayId, params.transportMode, params.lineFilter, params.groupByDirection);

                if (result && (result.departures?.length > 0 || result.grouped?.length > 0)) {
                    // Display departures on all POI cards that use this request
                    const cards = poisByRequest.get(cacheKey);
                    cards.forEach(function(poiCard) {
                        displayDepartures(poiCard, result, params);
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
     * Fetch departures from API with retry logic
     * @param {string} stopplaceId - StopPlace ID
     * @param {string|null} quayId - Optional Quay ID
     * @param {string|null} transportMode - Optional transport mode filter
     * @param {string|null} lineFilter - Optional comma-separated line filter
     * @param {boolean} groupByDirection - Whether to group by direction
     * @returns {Promise<Object>} Departure data
     */
    async function fetchDepartures(stopplaceId, quayId = null, transportMode = null, lineFilter = null, groupByDirection = true) {
        // Build cache key
        let cacheKey = stopplaceId;
        if (quayId) cacheKey += `-${quayId}`;
        if (transportMode) cacheKey += `-${transportMode}`;
        if (lineFilter) cacheKey += `-${lineFilter}`;
        const cached = departureCache.get(cacheKey);

        if (cached && (Date.now() - cached.timestamp) < CONFIG.REFRESH_INTERVAL) {
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
        if (lineFilter) {
            params.push(`line_filter=${encodeURIComponent(lineFilter)}`);
        }
        params.push(`group_by_direction=${groupByDirection ? '1' : '0'}`);
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }

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
                        return { success: false, departures: [], grouped: [] };
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                // Cache the result
                departureCache.set(cacheKey, {
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
                    return { success: false, departures: [], grouped: [] };
                }
            }
        }

        return { success: false, departures: [], grouped: [] };
    }

    /**
     * Display departures in POI card
     * @param {HTMLElement} poiCard - The POI card element
     * @param {Object} result - API response with departures and grouped data
     * @param {Object} params - Request parameters
     */
    function displayDepartures(poiCard, result, params) {
        // Check if there's an accordion structure - if so, insert into accordion content
        const accordionContent = poiCard.querySelector('.poi-api-accordion-content');
        
        // Find where to insert departures
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

        // Remove any existing departures section
        const existingSection = poiCard.querySelector('.entur-departures-section');
        if (existingSection) {
            existingSection.remove();
        }

        // Create departures section
        const departuresSection = document.createElement('div');
        departuresSection.className = 'entur-departures-section mt-4 pt-4 border-t border-gray-200';

        // Determine if we should show grouped or flat view
        const hasGrouped = result.grouped && result.grouped.length > 1;
        const useGrouped = hasGrouped && params.groupByDirection;

        let html = '';

        if (useGrouped) {
            // Grouped view - show by direction
            html = buildGroupedDeparturesHTML(result.grouped, result.stopplace_name);
        } else {
            // Flat view - show all departures in order
            html = buildFlatDeparturesHTML(result.departures, result.stopplace_name);
        }

        departuresSection.innerHTML = html;
        contentDiv.appendChild(departuresSection);

    }

    /**
     * Build HTML for grouped departures (by direction)
     * @param {Array} grouped - Grouped departure data
     * @param {string} stopplaceName - Stop place name
     * @returns {string} HTML string
     */
    function buildGroupedDeparturesHTML(grouped, stopplaceName) {
        let html = `
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Avganger fra ${escapeHtml(stopplaceName)}
                </h4>
            </div>
        `;

        grouped.forEach(function(group, index) {
            const directionLabel = group.direction || 'Retning ukjent';
            
            html += `
                <div class="entur-direction-group ${index > 0 ? 'mt-4 pt-3 border-t border-gray-100' : ''}">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2 flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                        ${escapeHtml(directionLabel)}
                    </div>
                    <div class="space-y-1">
            `;

            group.departures.forEach(function(departure) {
                html += buildDepartureRowHTML(departure);
            });

            html += `
                    </div>
                </div>
            `;
        });

        html += buildFooterHTML();

        return html;
    }

    /**
     * Build HTML for flat departures list
     * @param {Array} departures - Departure data
     * @param {string} stopplaceName - Stop place name
     * @returns {string} HTML string
     */
    function buildFlatDeparturesHTML(departures, stopplaceName) {
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
            html += buildDepartureRowHTML(departure);
        });

        html += '</div>';
        html += buildFooterHTML();

        return html;
    }

    /**
     * Build HTML for a single departure row
     * @param {Object} departure - Departure data
     * @returns {string} HTML string
     */
    function buildDepartureRowHTML(departure) {
        const realtimeIndicator = departure.realtime
            ? '<span class="text-emerald-600 text-xs">●</span>'
            : '<span class="text-gray-400 text-xs" title="Rutetid (ikke sanntid)">○</span>';

        const relativeTime = formatRelativeTime(departure.relative_time);
        
        // Show line number badge if available
        const lineBadge = departure.line_number 
            ? `<span class="inline-flex items-center justify-center min-w-[24px] h-5 px-1.5 text-xs font-bold bg-blue-600 text-white rounded">${escapeHtml(departure.line_number)}</span>`
            : '';

        return `
            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    ${lineBadge}
                    <span class="text-sm text-gray-700 truncate">${escapeHtml(departure.destination)}</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-sm font-semibold text-gray-900 tabular-nums">${departure.time}</span>
                    <span class="text-xs text-gray-500">${relativeTime}</span>
                    ${realtimeIndicator}
                </div>
            </div>
        `;
    }

    /**
     * Build footer HTML with Entur attribution
     * @returns {string} HTML string
     */
    function buildFooterHTML() {
        return `
            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                <a href="https://entur.no" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="text-xs text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1">
                    <span>Data fra Entur</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
                <span class="text-xs text-gray-400">
                    Oppdatert ${getCurrentTime()}
                </span>
            </div>
        `;
    }

    /**
     * Format relative time
     * @param {number} minutes - Minutes until departure
     * @returns {string} Formatted time
     */
    function formatRelativeTime(minutes) {
        if (minutes === 0) {
            return 'nå';
        } else if (minutes < 60) {
            return `${minutes} min`;
        } else {
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            if (remainingMinutes === 0) {
                return `${hours} t`;
            }
            return `${hours}t ${remainingMinutes}m`;
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
        if (!text) return '';
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
