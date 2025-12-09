/**
 * Hyre Live Availability - Frontend Integration
 *
 * Fetches and displays real-time car availability from Hyre stations
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        API_ENDPOINT: (typeof hyreSettings !== 'undefined' && hyreSettings.restUrl)
            ? hyreSettings.restUrl
            : window.location.origin + '/wp-json/placy/v1/hyre/availability',
        REFRESH_INTERVAL: 120000, // 2 minutes (cars don't change as frequently as transit)
        MAX_RETRIES: 2,
        RETRY_DELAY: 1000
    };

    // Cache for availability data
    const availabilityCache = new Map();

    /**
     * Initialize Hyre integration
     */
    function init() {
        console.log('Hyre Live Availability: Initializing...');
        loadAllAvailability();
    }

    /**
     * Load availability for all POI cards on the page
     */
    async function loadAllAvailability() {
        const poiCards = document.querySelectorAll('[data-hyre-station-id][data-show-hyre-availability="1"]');

        if (poiCards.length === 0) {
            console.log('Hyre: No POIs with car availability enabled');
            return;
        }

        console.log(`Hyre: Loading availability for ${poiCards.length} POIs...`);

        // Group by station ID to minimize API requests
        const uniqueStations = new Map();
        const poisByStation = new Map();

        poiCards.forEach(function(poiCard) {
            const stationId = poiCard.getAttribute('data-hyre-station-id');
            
            if (!uniqueStations.has(stationId)) {
                uniqueStations.set(stationId, stationId);
                poisByStation.set(stationId, []);
            }
            poisByStation.get(stationId).push(poiCard);
            
            // Show loading state
            showLoadingState(poiCard);
        });

        console.log(`Hyre: Making ${uniqueStations.size} unique API requests`);

        // Fetch all unique stations
        const fetchPromises = Array.from(uniqueStations.keys()).map(async function(stationId) {
            try {
                const result = await fetchAvailability(stationId);
                
                if (result && result.success) {
                    const cards = poisByStation.get(stationId);
                    cards.forEach(function(poiCard) {
                        displayAvailability(poiCard, result);
                    });
                    console.log(`Hyre: Loaded availability for station ${result.station_name}`);
                } else {
                    console.log(`Hyre: No availability data for ${stationId}`);
                }
            } catch (error) {
                console.error(`Hyre: Failed to fetch ${stationId}`, error);
            }
        });

        await Promise.all(fetchPromises);
        console.log('Hyre: All availability loaded');
    }

    /**
     * Show loading state
     */
    function showLoadingState(poiCard) {
        const contentDiv = poiCard.querySelector('.poi-card-content, .poi-content, .poi-highlight-content');
        if (!contentDiv) return;

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'hyre-loading mt-3 pt-3 border-t border-gray-200';
        loadingDiv.innerHTML = `
            <div class="flex items-center gap-2 text-gray-500">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium">Henter bilstatus...</span>
            </div>
        `;
        contentDiv.appendChild(loadingDiv);
    }

    /**
     * Hide loading state
     */
    function hideLoadingState(poiCard) {
        const loadingDiv = poiCard.querySelector('.hyre-loading');
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }

    /**
     * Fetch availability from API
     */
    async function fetchAvailability(stationId) {
        // Check cache
        const cached = availabilityCache.get(stationId);
        if (cached && (Date.now() - cached.timestamp) < CONFIG.REFRESH_INTERVAL) {
            console.log('Hyre: Using cached data');
            return cached.data;
        }

        const url = `${CONFIG.API_ENDPOINT}/${encodeURIComponent(stationId)}`;

        for (let attempt = 0; attempt <= CONFIG.MAX_RETRIES; attempt++) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);

                const response = await fetch(url, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' },
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                availabilityCache.set(stationId, {
                    data: data,
                    timestamp: Date.now()
                });

                return data;

            } catch (error) {
                if (error.name === 'AbortError') {
                    console.warn(`Hyre: Request timeout on attempt ${attempt + 1}`);
                } else {
                    console.error(`Hyre: Fetch attempt ${attempt + 1} failed:`, error);
                }

                if (attempt < CONFIG.MAX_RETRIES) {
                    await new Promise(resolve => setTimeout(resolve, CONFIG.RETRY_DELAY));
                }
            }
        }

        return { success: false };
    }

    /**
     * Display availability in POI card
     */
    function displayAvailability(poiCard, data) {
        hideLoadingState(poiCard);

        const contentDiv = poiCard.querySelector('.poi-card-content, .poi-content, .poi-highlight-content');
        if (!contentDiv) {
            console.warn('Hyre: Could not find content div');
            return;
        }

        const availabilitySection = document.createElement('div');
        availabilitySection.className = 'hyre-availability-section mt-4 pt-4 border-t border-gray-200';

        const vehiclesAvailable = data.vehicles_available || 0;
        const capacity = data.capacity || 0;
        const isCharging = data.is_charging_station;

        // Status color
        let statusColor = 'text-gray-500';
        let statusBg = 'bg-gray-100';
        if (vehiclesAvailable > 0) {
            statusColor = 'text-emerald-600';
            statusBg = 'bg-emerald-50';
        } else if (vehiclesAvailable === 0) {
            statusColor = 'text-amber-600';
            statusBg = 'bg-amber-50';
        }

        // Build vehicle list if available
        let vehicleListHtml = '';
        if (data.available_vehicles && data.available_vehicles.length > 0) {
            vehicleListHtml = '<div class="mt-2 space-y-1">';
            data.available_vehicles.forEach(function(v) {
                vehicleListHtml += `
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-700">${escapeHtml(v.name)}</span>
                        <span class="font-medium text-gray-900">${v.count} ledig</span>
                    </div>
                `;
            });
            vehicleListHtml += '</div>';
        }

        // Charging indicator
        const chargingHtml = isCharging ? `
            <span class="inline-flex items-center gap-1 text-xs text-blue-600" title="Ladestasjon">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11 21h-1l1-7H7.5c-.58 0-.57-.32-.38-.66l4.14-7.59c.2-.37.78-.24.78.24V13h3.5c.49 0 .56.33.38.66l-4.14 7.59c-.2.37-.78.24-.78-.24V21z"/>
                </svg>
                Lading
            </span>
        ` : '';

        availabilitySection.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    Hyre Bildeling
                </h4>
                ${chargingHtml}
            </div>
            
            <div class="flex items-center gap-4 ${statusBg} rounded-lg p-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 ${statusColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    <span class="text-lg font-bold ${statusColor}">${vehiclesAvailable}</span>
                    <span class="text-sm text-gray-600">ledige biler</span>
                </div>
                <div class="text-xs text-gray-500">
                    av ${capacity} plasser
                </div>
            </div>
            
            ${vehicleListHtml}
            
            <div class="mt-3 flex items-center justify-between">
                ${data.rental_url ? `
                    <a href="${escapeHtml(data.rental_url)}" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Book bil på Hyre
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                ` : '<span></span>'}
                <span class="text-xs text-gray-400">
                    Oppdatert kl ${data.timestamp || getCurrentTime()}
                </span>
            </div>
        `;

        contentDiv.appendChild(availabilitySection);
        console.log('Hyre: Availability displayed successfully');
    }

    /**
     * Get current time string
     */
    function getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('nb-NO', { hour: '2-digit', minute: '2-digit' });
    }

    /**
     * Escape HTML
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
