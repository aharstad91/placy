/**
 * Travel Calculator - Address autocomplete and route calculation
 * 
 * Uses Mapbox Geocoding API for address search and
 * Mapbox Directions API for travel time calculation.
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Track initialized blocks to avoid double-initialization
    const initializedBlocks = new WeakSet();

    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize all travel calculators on the page
    function initTravelCalculators() {
        const calculators = document.querySelectorAll('.travel-calculator-block');
        calculators.forEach(function(calc) {
            if (!initializedBlocks.has(calc)) {
                initCalculator(calc);
                initializedBlocks.add(calc);
            }
        });
    }

    function initCalculator(container) {
        const input = container.querySelector('.travel-calc-input');
        const clearBtn = container.querySelector('.travel-calc-clear');
        const suggestions = container.querySelector('.travel-calc-suggestions');
        const resultDiv = container.querySelector('.travel-calc-result');
        const resultText = container.querySelector('.travel-calc-result-text');
        const mapContainer = container.querySelector('.travel-calc-map');
        const googleLink = container.querySelector('.travel-calc-google-link');
        const loadingDiv = container.querySelector('.travel-calc-loading');
        const errorDiv = container.querySelector('.travel-calc-error');
        const errorText = container.querySelector('.travel-calc-error-text');
        const quickButtons = container.querySelectorAll('.travel-calc-quick-btn');

        const destLat = parseFloat(container.dataset.destLat);
        const destLng = parseFloat(container.dataset.destLng);
        const destName = container.dataset.destName;
        const transportMode = container.dataset.transportMode;
        const transportVerb = container.dataset.transportVerb;

        if (!destLat || !destLng) {
            showError('Destinasjon ikke konfigurert.');
            return;
        }

        let selectedCoords = null;
        let selectedAreaName = null;
        let activeSuggestionIndex = -1;
        let routeMap = null;

        // Geocoding search with debounce
        const searchAddresses = debounce(async (query) => {
            if (query.length < 3) {
                hideSuggestions();
                return;
            }

            try {
                // Use Mapbox Geocoding API via our proxy
                const response = await fetch(
                    `${travelCalcSettings.restUrl}/geocode?` + new URLSearchParams({
                        query: query,
                        proximity: `${destLng},${destLat}`,
                        country: 'NO',
                    })
                );

                if (!response.ok) throw new Error('Geocoding failed');

                const data = await response.json();
                
                if (data.features && data.features.length > 0) {
                    showSuggestions(data.features);
                } else {
                    hideSuggestions();
                }
            } catch (error) {
                hideSuggestions();
            }
        }, 300);

        // Show suggestions dropdown
        function showSuggestions(features) {
            suggestions.innerHTML = '';
            activeSuggestionIndex = -1;

            features.slice(0, 5).forEach((feature, index) => {
                const li = document.createElement('li');
                li.className = 'travel-calc-suggestion';
                li.dataset.index = index;
                li.dataset.lng = feature.center[0];
                li.dataset.lat = feature.center[1];

                const nameParts = feature.place_name.split(',');
                const name = nameParts[0];
                const address = nameParts.slice(1).join(',').trim();

                li.innerHTML = `
                    <div class="travel-calc-suggestion-name">${name}</div>
                    ${address ? `<div class="travel-calc-suggestion-address">${address}</div>` : ''}
                `;

                li.addEventListener('click', () => selectSuggestion(feature));
                suggestions.appendChild(li);
            });

            suggestions.style.display = 'block';
        }

        function hideSuggestions() {
            suggestions.style.display = 'none';
            activeSuggestionIndex = -1;
        }

        // Select a suggestion
        function selectSuggestion(feature) {
            const nameParts = feature.place_name.split(',');
            input.value = nameParts[0];
            selectedCoords = {
                lng: feature.center[0],
                lat: feature.center[1]
            };
            selectedAreaName = null;
            clearQuickButtonActive();
            hideSuggestions();
            clearBtn.style.display = 'flex';
            calculateRoute();
        }

        // Clear active state from quick buttons
        function clearQuickButtonActive() {
            quickButtons.forEach(btn => btn.classList.remove('active'));
        }

        // Select quick area
        function selectQuickArea(button) {
            const lat = parseFloat(button.dataset.lat);
            const lng = parseFloat(button.dataset.lng);
            const name = button.dataset.name;

            selectedCoords = { lat, lng };
            selectedAreaName = name;
            
            clearQuickButtonActive();
            button.classList.add('active');
            
            input.value = '';
            clearBtn.style.display = 'none';
            hideSuggestions();
            
            calculateRoute(name);
        }

        // Calculate route using Mapbox Directions API
        async function calculateRoute(fromAreaName = null) {
            if (!selectedCoords) return;

            showLoading();
            hideError();
            hideResult();

            try {
                const response = await fetch(
                    `${travelCalcSettings.restUrl}/directions?` + new URLSearchParams({
                        origin_lng: selectedCoords.lng,
                        origin_lat: selectedCoords.lat,
                        dest_lng: destLng,
                        dest_lat: destLat,
                        mode: transportMode,
                    })
                );

                if (!response.ok) throw new Error('Directions failed');

                const data = await response.json();

                if (data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const durationMinutes = Math.round(route.duration / 60);
                    const distanceKm = (route.distance / 1000).toFixed(1);

                    showResult(durationMinutes, distanceKm, fromAreaName, route.geometry);
                } else {
                    showError('Kunne ikke beregne rute.');
                }
            } catch (error) {
                showError('Noe gikk galt. Prøv igjen.');
            }

            hideLoading();
        }

        function showResult(minutes, km, fromAreaName = null, routeGeometry = null) {
            let fromText = fromAreaName ? `fra ${fromAreaName}` : '';
            resultText.innerHTML = `Det tar <strong>${minutes} minutter</strong> å ${transportVerb} <strong>${km} km</strong> ${fromText} til ${destName}`;
            resultDiv.style.display = 'block';

            // Update Google Maps link
            if (googleLink && selectedCoords) {
                const travelModes = {
                    'cycling': 'bicycling',
                    'walking': 'walking',
                    'driving': 'driving'
                };
                const gmapsMode = travelModes[transportMode] || 'bicycling';
                googleLink.href = `https://www.google.com/maps/dir/?api=1&origin=${selectedCoords.lat},${selectedCoords.lng}&destination=${destLat},${destLng}&travelmode=${gmapsMode}`;
            }

            // Show route on map if geometry available
            if (routeGeometry && mapContainer && typeof mapboxgl !== 'undefined') {
                showRouteMap(routeGeometry);
            }
        }

        // Initialize or update the route map
        function showRouteMap(routeGeometry) {
            // Set Mapbox token
            if (typeof placyMapbox !== 'undefined' && placyMapbox.accessToken) {
                mapboxgl.accessToken = placyMapbox.accessToken;
            } else if (typeof travelCalcSettings !== 'undefined' && travelCalcSettings.mapboxToken) {
                mapboxgl.accessToken = travelCalcSettings.mapboxToken;
            }

            if (!mapboxgl.accessToken) {
                mapContainer.style.display = 'none';
                return;
            }

            // Remove existing map if present
            if (routeMap) {
                routeMap.remove();
                routeMap = null;
            }

            // Show map container
            mapContainer.style.display = 'block';

            // Create map
            routeMap = new mapboxgl.Map({
                container: mapContainer,
                style: 'mapbox://styles/mapbox/streets-v12',
                interactive: true,
                attributionControl: false
            });

            routeMap.on('load', () => {
                // Hide Mapbox's built-in POI labels to reduce clutter
                if (window.PlacyMapUtils && window.PlacyMapUtils.hideMapboxPOILayers) {
                    window.PlacyMapUtils.hideMapboxPOILayers(routeMap);
                }

                // Add route line
                routeMap.addSource('route', {
                    type: 'geojson',
                    data: {
                        type: 'Feature',
                        properties: {},
                        geometry: routeGeometry
                    }
                });

                // Route line style
                routeMap.addLayer({
                    id: 'route',
                    type: 'line',
                    source: 'route',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    paint: {
                        'line-color': '#6366f1',
                        'line-width': 4,
                        'line-opacity': 0.8
                    }
                });

                // Add start marker
                const startEl = document.createElement('div');
                startEl.className = 'travel-calc-marker travel-calc-marker-start';
                startEl.innerHTML = '<i class="fa-solid fa-circle"></i>';
                
                new mapboxgl.Marker(startEl)
                    .setLngLat([selectedCoords.lng, selectedCoords.lat])
                    .addTo(routeMap);

                // Add destination marker
                const destEl = document.createElement('div');
                destEl.className = 'travel-calc-marker travel-calc-marker-dest';
                destEl.innerHTML = '<i class="fa-solid fa-location-dot"></i>';
                
                new mapboxgl.Marker(destEl)
                    .setLngLat([destLng, destLat])
                    .addTo(routeMap);

                // Fit map to route bounds
                const bounds = new mapboxgl.LngLatBounds();
                bounds.extend([selectedCoords.lng, selectedCoords.lat]);
                bounds.extend([destLng, destLat]);
                
                // Also include all points from the route geometry
                if (routeGeometry.coordinates) {
                    routeGeometry.coordinates.forEach(coord => {
                        bounds.extend(coord);
                    });
                }

                routeMap.fitBounds(bounds, {
                    padding: 40,
                    maxZoom: 14
                });
            });

            // Add navigation control
            routeMap.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');
        }

        function hideResult() {
            resultDiv.style.display = 'none';
        }

        function showLoading() {
            loadingDiv.style.display = 'flex';
        }

        function hideLoading() {
            loadingDiv.style.display = 'none';
        }

        function showError(message) {
            errorText.textContent = message;
            errorDiv.style.display = 'flex';
        }

        function hideError() {
            errorDiv.style.display = 'none';
        }

        // Event listeners
        input.addEventListener('input', (e) => {
            const value = e.target.value.trim();
            
            if (value.length > 0) {
                clearBtn.style.display = 'flex';
                clearQuickButtonActive();
                selectedAreaName = null;
                searchAddresses(value);
            } else {
                clearBtn.style.display = 'none';
                hideSuggestions();
                hideResult();
                selectedCoords = null;
                selectedAreaName = null;
            }
        });

        // Quick area button listeners
        quickButtons.forEach(btn => {
            btn.addEventListener('click', () => selectQuickArea(btn));
        });

        input.addEventListener('keydown', (e) => {
            const suggestionItems = suggestions.querySelectorAll('.travel-calc-suggestion');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeSuggestionIndex = Math.min(activeSuggestionIndex + 1, suggestionItems.length - 1);
                updateActiveSuggestion(suggestionItems);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeSuggestionIndex = Math.max(activeSuggestionIndex - 1, 0);
                updateActiveSuggestion(suggestionItems);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeSuggestionIndex >= 0 && suggestionItems[activeSuggestionIndex]) {
                    suggestionItems[activeSuggestionIndex].click();
                }
            } else if (e.key === 'Escape') {
                hideSuggestions();
            }
        });

        function updateActiveSuggestion(items) {
            items.forEach((item, index) => {
                item.classList.toggle('active', index === activeSuggestionIndex);
            });
        }

        clearBtn.addEventListener('click', () => {
            input.value = '';
            clearBtn.style.display = 'none';
            hideSuggestions();
            hideResult();
            hideError();
            selectedCoords = null;
            selectedAreaName = null;
            clearQuickButtonActive();
            input.focus();
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                hideSuggestions();
            }
        });
        
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTravelCalculators);
    } else {
        initTravelCalculators();
    }

    // Watch for dynamically added travel calculator blocks (e.g., in modals)
    const observer = new MutationObserver(function(mutations) {
        let shouldInit = false;
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) {
                    // Check if node is or contains a travel calculator block
                    if (node.classList && node.classList.contains('travel-calculator-block')) {
                        shouldInit = true;
                    } else if (node.querySelectorAll) {
                        const blocks = node.querySelectorAll('.travel-calculator-block');
                        if (blocks.length > 0) {
                            shouldInit = true;
                        }
                    }
                }
            });
        });
        if (shouldInit) {
            initTravelCalculators();
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Also re-initialize when modal opens (listen for specific events)
    document.addEventListener('placy:modalOpen', initTravelCalculators);
    document.addEventListener('placy:drawerOpen', initTravelCalculators);

    // Expose globally for manual re-initialization
    window.initTravelCalculators = initTravelCalculators;
})();
