/**
 * Proximity Filter - Filter POIs by travel time
 * 
 * Features:
 * - localStorage-based caching (30-day validity)
 * - Mapbox Directions API integration
 * - Support for walk/bike/drive modes
 * - Global state management with multiple UI instances
 * - Fallback to straight-line distance
 * 
 * @package Placy
 * @since 2.0.0
 */

(function() {
    'use strict';
    
    console.log('[Proximity Filter] Script loaded and executing - version 2.0.0');

    // Configuration
    const CONFIG = {
        CACHE_VALIDITY_DAYS: 30,
        CACHE_KEY_PREFIX: 'placy_proximity_',
        API_BATCH_LIMIT: 5,
        API_TIMEOUT: 10000,
        FALLBACK_SPEEDS: {
            walk: 5,  // km/h
            bike: 15, // km/h
            drive: 40 // km/h
        }
    };

    /**
     * Global Proximity Filter State Manager (Singleton)
     * Manages shared state across all filter instances
     */
    const ProximityFilterState = (function() {
        // Private state
        let selectedTime = 10;
        let selectedMode = 'walk';
        let projectCoords = null;
        let projectId = '';
        let instances = [];
        let filteredPOIs = [];
        let isLoading = false;

        return {
            // Register a new filter instance
            register(instance) {
                instances.push(instance);
                console.log('[Proximity Filter State] Registered instance, total:', instances.length);
            },

            // Unregister an instance
            unregister(instance) {
                instances = instances.filter(i => i !== instance);
            },

            // Getters
            getTime() { return selectedTime; },
            getMode() { return selectedMode; },
            getProjectCoords() { return projectCoords; },
            getProjectId() { return projectId; },
            getFilteredPOIs() { return filteredPOIs; },
            isFiltering() { return isLoading; },

            // Setters (trigger updates)
            setTime(time) {
                if (selectedTime === time) return;
                selectedTime = time;
                console.log('[Proximity Filter State] Time changed to:', time);
                this.filterAllPOIs();
            },

            setMode(mode) {
                if (selectedMode === mode) return;
                selectedMode = mode;
                console.log('[Proximity Filter State] Mode changed to:', mode);
                this.filterAllPOIs();
            },

            setProjectData(coords, id, defaultTime, defaultMode) {
                projectCoords = coords;
                projectId = id;
                selectedTime = defaultTime;
                selectedMode = defaultMode;
            },

            // Main filtering logic
            async filterAllPOIs() {
                if (!projectCoords) {
                    console.warn('[Proximity Filter State] No project coordinates');
                    return;
                }

                isLoading = true;
                this.notifyAllInstances('loading', true);

                try {
                    // Get ALL POIs from ALL chapters
                    const allPOIs = this.getAllPOIsFromDOM();
                    console.log('[Proximity Filter State] Found', allPOIs.length, 'POIs across all chapters');

                    if (allPOIs.length === 0) {
                        filteredPOIs = [];
                        this.updateUI();
                        return;
                    }

                    // Calculate travel times (with caching)
                    const poisWithTimes = await this.calculateTravelTimes(allPOIs);

                    // Filter by selected time
                    filteredPOIs = poisWithTimes.filter(poi => poi.travelTime <= selectedTime);

                    console.log('[Proximity Filter State] Filtered:', filteredPOIs.length, 'of', allPOIs.length);

                    // Update all instances
                    this.updateUI();

                } catch (error) {
                    console.error('[Proximity Filter State] Error:', error);
                    this.notifyAllInstances('error', error);
                } finally {
                    isLoading = false;
                    this.notifyAllInstances('loading', false);
                }
            },

            // Get all POIs from entire page (not just one chapter)
            getAllPOIsFromDOM() {
                const poiCards = document.querySelectorAll('[data-poi-id]');
                return Array.from(poiCards).map(card => {
                    const poiId = card.dataset.poiId;
                    const coords = card.dataset.poiCoords;

                    if (!coords) return null;

                    try {
                        const [lat, lng] = JSON.parse(coords);
                        return {
                            id: poiId,
                            element: card,
                            coords: { lat, lng }
                        };
                    } catch (e) {
                        console.warn('[Proximity Filter State] Invalid coords for POI:', poiId);
                        return null;
                    }
                }).filter(Boolean);
            },

            // Calculate travel times with caching
            async calculateTravelTimes(pois) {
                const poisWithTimes = [];

                for (const poi of pois) {
                    const cached = this.getCachedTime(poi.id, selectedMode);

                    if (cached !== null) {
                        poisWithTimes.push({
                            ...poi,
                            travelTime: cached,
                            cached: true
                        });
                    } else {
                        const travelTime = await this.fetchTravelTime(poi.coords);
                        this.cacheTime(poi.id, selectedMode, travelTime);
                        poisWithTimes.push({
                            ...poi,
                            travelTime,
                            cached: false
                        });
                    }
                }

                return poisWithTimes;
            },

            // Cache methods
            getCachedTime(poiId, mode) {
                const cacheKey = `${CONFIG.CACHE_KEY_PREFIX}${projectId}_${poiId}_${mode}`;
                const cached = localStorage.getItem(cacheKey);
                if (!cached) return null;

                try {
                    const data = JSON.parse(cached);
                    const age = Date.now() - data.timestamp;
                    const maxAge = CONFIG.CACHE_VALIDITY_DAYS * 24 * 60 * 60 * 1000;

                    if (age > maxAge) {
                        localStorage.removeItem(cacheKey);
                        return null;
                    }

                    return data.travelTime;
                } catch (e) {
                    return null;
                }
            },

            cacheTime(poiId, mode, travelTime) {
                const cacheKey = `${CONFIG.CACHE_KEY_PREFIX}${projectId}_${poiId}_${mode}`;
                const data = {
                    travelTime,
                    timestamp: Date.now()
                };
                try {
                    localStorage.setItem(cacheKey, JSON.stringify(data));
                } catch (e) {
                    console.warn('[Proximity Filter State] Failed to cache:', e);
                }
            },

            // Mapbox API call
            async fetchTravelTime(coords) {
                const mapboxToken = typeof placyMapConfig !== 'undefined' && placyMapConfig.mapboxToken 
                    ? placyMapConfig.mapboxToken 
                    : null;

                if (!mapboxToken) {
                    console.warn('[Proximity Filter State] Mapbox token not available, using fallback');
                    return this.calculateFallbackTime(coords);
                }

                const profile = selectedMode === 'walk' ? 'walking' : selectedMode === 'bike' ? 'cycling' : 'driving';

                const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/` +
                    `${projectCoords.lng},${projectCoords.lat};${coords.lng},${coords.lat}` +
                    `?access_token=${mapboxToken}&geometries=geojson&overview=simplified`;

                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), CONFIG.API_TIMEOUT);

                    const response = await fetch(url, { signal: controller.signal });
                    clearTimeout(timeoutId);

                    if (!response.ok) throw new Error('Mapbox API error');

                    const data = await response.json();
                    if (data.routes && data.routes.length > 0) {
                        return Math.ceil(data.routes[0].duration / 60);
                    }
                    throw new Error('No route found');
                } catch (error) {
                    console.warn('[Proximity Filter State] Mapbox error, using fallback:', error);
                    return this.calculateFallbackTime(coords);
                }
            },

            calculateFallbackTime(coords) {
                const R = 6371;
                const dLat = (coords.lat - projectCoords.lat) * Math.PI / 180;
                const dLon = (coords.lng - projectCoords.lng) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(projectCoords.lat * Math.PI / 180) * Math.cos(coords.lat * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                const distance = R * c;

                const speed = CONFIG.FALLBACK_SPEEDS[selectedMode];
                return Math.ceil((distance / speed) * 60);
            },

            // Update all UI instances and POI visibility
            updateUI() {
                this.updatePOIVisibility();
                this.notifyAllInstances('update', {
                    count: filteredPOIs.length,
                    time: selectedTime,
                    mode: selectedMode
                });
            },

            // Update POI card visibility
            updatePOIVisibility() {
                const allPOIs = document.querySelectorAll('[data-poi-id]');
                const filteredIds = new Set(filteredPOIs.map(poi => poi.id));
                const travelTimeMap = new Map(filteredPOIs.map(poi => [poi.id, poi.travelTime]));

                const modeText = {
                    walk: 'gange',
                    bike: 'sykkel',
                    drive: 'bil'
                }[selectedMode];

                allPOIs.forEach(poiCard => {
                    const poiId = poiCard.dataset.poiId;
                    const isVisible = filteredIds.has(poiId);

                    // Show/hide POI
                    if (isVisible) {
                        poiCard.classList.remove('hidden', 'proximity-hidden');
                        poiCard.style.display = '';
                    } else {
                        poiCard.classList.add('hidden', 'proximity-hidden');
                        poiCard.style.display = 'none';
                    }

                    // Update travel time text
                    const travelTimeEl = poiCard.querySelector('.poi-travel-time');
                    const travelTimeText = poiCard.querySelector('.poi-travel-time-text');
                    const walkingTimeText = poiCard.querySelector('.poi-walking-time');
                    
                    if (isVisible) {
                        const travelTime = travelTimeMap.get(poiId);
                        if (travelTime !== undefined) {
                            // Update hidden travel time element (poi-highlight blocks)
                            if (travelTimeEl) {
                                travelTimeEl.style.display = 'flex';
                                if (travelTimeText) {
                                    travelTimeText.textContent = `${travelTime} min ${modeText}`;
                                }
                            }
                            // Update static walking time display (poi-gallery blocks)
                            if (walkingTimeText) {
                                walkingTimeText.textContent = `${travelTime} min ${modeText}`;
                            }
                        }
                    } else {
                        // Hide travel time for non-visible POIs
                        if (travelTimeEl) {
                            travelTimeEl.style.display = 'none';
                        }
                    }
                });

                // Update map markers
                this.updateMapMarkers(filteredIds);
            },

            // Update map marker visibility
            updateMapMarkers(filteredIds) {
                const markers = document.querySelectorAll('.tema-story-marker-wrapper');
                
                markers.forEach(marker => {
                    const poiId = marker.dataset.poiId;
                    
                    // Skip property markers
                    if (marker.dataset.markerType === 'property') return;
                    
                    if (poiId && filteredIds.has(poiId)) {
                        marker.style.display = 'flex';
                    } else if (poiId) {
                        marker.style.display = 'none';
                    }
                });
            },

            // Notify all registered instances
            notifyAllInstances(type, data) {
                instances.forEach(instance => {
                    if (type === 'update') instance.updateDisplay(data);
                    if (type === 'loading') instance.setLoading(data);
                    if (type === 'error') instance.showError();
                });
            },

            // Clear old cache entries
            clearOldCache() {
                const keys = Object.keys(localStorage);
                const maxAge = CONFIG.CACHE_VALIDITY_DAYS * 24 * 60 * 60 * 1000;
                
                keys.forEach(key => {
                    if (key.startsWith(CONFIG.CACHE_KEY_PREFIX)) {
                        try {
                            const data = JSON.parse(localStorage.getItem(key));
                            const age = Date.now() - data.timestamp;
                            
                            if (age > maxAge) {
                                localStorage.removeItem(key);
                            }
                        } catch (e) {
                            localStorage.removeItem(key);
                        }
                    }
                });
            }
        };
    })();

    /**
     * Proximity Filter UI Instance
     * Each chapter can have its own UI, but all share the same state
     */
    class ProximityFilter {
        constructor(element) {
            this.element = element;
            
            // Read configuration from this instance's data attributes
            const defaultTime = parseInt(element.dataset.defaultTime) || 10;
            const defaultMode = element.dataset.defaultMode || 'walk';
            const projectCoords = JSON.parse(element.dataset.projectCoords || 'null');
            const projectId = element.dataset.projectId || '';

            // Initialize global state (only first instance does this)
            if (!ProximityFilterState.getProjectCoords()) {
                ProximityFilterState.setProjectData(projectCoords, projectId, defaultTime, defaultMode);
            }

            // Register this instance with global state
            ProximityFilterState.register(this);
            
            this.init();
        }

        init() {
            if (!ProximityFilterState.getProjectCoords()) {
                console.error('[Proximity Filter] Project coordinates missing');
                return;
            }

            this.bindEvents();
            this.setupPlacesLoadedListener();
            
            // Sync initial UI state
            this.syncUIState();
            
            // Run filter (only first instance triggers actual filtering)
            if (ProximityFilterState.getFilteredPOIs().length === 0) {
                ProximityFilterState.filterAllPOIs();
            }
        }

        bindEvents() {
            // Time buttons
            const timeBtns = this.element.querySelectorAll('.proximity-time-btn');
            timeBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    ProximityFilterState.setTime(parseInt(btn.dataset.time));
                });
            });

            // Mode buttons
            const modeBtns = this.element.querySelectorAll('.proximity-mode-btn');
            modeBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    ProximityFilterState.setMode(btn.dataset.mode);
                });
            });
        }

        setupPlacesLoadedListener() {
            // Listen for Google Places loaded events
            const chapterWrapper = this.element.closest('.chapter');
            if (!chapterWrapper) return;
            
            chapterWrapper.addEventListener('placesLoaded', (event) => {
                console.log('[Proximity Filter] Google Places loaded, re-filtering...', event.detail);
                // Re-run filter to include newly added Google Places POIs
                ProximityFilterState.filterAllPOIs();
            });
        }

        // Sync UI buttons with global state
        syncUIState() {
            const currentTime = ProximityFilterState.getTime();
            const currentMode = ProximityFilterState.getMode();

            // Update time buttons
            this.element.querySelectorAll('.proximity-time-btn').forEach(btn => {
                if (parseInt(btn.dataset.time) === currentTime) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            // Update mode buttons
            this.element.querySelectorAll('.proximity-mode-btn').forEach(btn => {
                if (btn.dataset.mode === currentMode) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        // Called by global state when filter updates
        updateDisplay(data) {
            this.syncUIState();

            const resultCount = this.element.querySelector('.result-count');
            const resultTime = this.element.querySelector('.result-time');
            const resultMode = this.element.querySelector('.result-mode');
            const resultText = this.element.querySelector('.result-text');
            const emptyState = this.element.querySelector('.empty-state');

            const modeText = {
                walk: 'gange',
                bike: 'sykkel',
                drive: 'bil'
            }[data.mode];

            if (resultCount) resultCount.textContent = data.count;
            if (resultTime) resultTime.textContent = data.time;
            if (resultMode) resultMode.textContent = modeText;

            // Show/hide empty state
            if (data.count === 0) {
                if (resultText) resultText.classList.add('hidden');
                if (emptyState) emptyState.classList.remove('hidden');
            } else {
                if (resultText) resultText.classList.remove('hidden');
                if (emptyState) emptyState.classList.add('hidden');
            }
        }

        setLoading(loading) {
            const loadingState = this.element.querySelector('.loading-state');
            const resultText = this.element.querySelector('.result-text');
            const emptyState = this.element.querySelector('.empty-state');

            if (loading) {
                if (loadingState) loadingState.classList.remove('hidden');
                if (resultText) resultText.classList.add('hidden');
                if (emptyState) emptyState.classList.add('hidden');
            } else {
                if (loadingState) loadingState.classList.add('hidden');
            }
        }

        showError() {
            const emptyState = this.element.querySelector('.empty-state');
            if (emptyState) {
                emptyState.textContent = 'Kunne ikke laste data. Prøv igjen senere.';
                emptyState.classList.remove('hidden');
            }
        }
    }

    /**
     * Initialize all proximity filters on page
     */
    function init() {
        const filters = document.querySelectorAll('.proximity-filter-block');
        
        // Clear old cache once
        ProximityFilterState.clearOldCache();
        
        // Create UI instances for each filter block
        filters.forEach(filter => {
            new ProximityFilter(filter);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
