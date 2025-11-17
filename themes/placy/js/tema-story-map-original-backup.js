/**
 * Tema Story Map - Scroll-synchronized map with POI markers
 * 
 * Features:
 * - Intersection Observer for chapter tracking
 * - Dynamic marker updates based on scroll position
 * - Smooth map transitions with fitBounds
 * - Configurable chapter activation threshold
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        CHAPTER_THRESHOLD: 0.5,        // 50% visibility = midpoint, adjustable 0-1
        DEBOUNCE_DELAY: 100,            // Debounce delay for map updates (ms)
        MARKER_FADE_DURATION: 200,      // Marker transition duration (ms)
        FIT_BOUNDS_PADDING: 80,         // Padding around markers in fitBounds
        DEFAULT_ZOOM: 13,               // Default zoom if single marker
        DEFAULT_CENTER: [10.3951, 63.4305], // Trondheim center as fallback
    };

    // State
    let map = null;
    let markers = [];
    let chapterData = new Map();
    let activeChapterId = null;
    let observer = null;
    let debounceTimer = null;
    let startLocation = null; // Property/start location from ACF fields
    let walkingDistances = new Map(); // Cache for walking distances
    let currentRoute = null; // Currently displayed route

    /**
     * Get start location from page meta data
     */
    function getStartLocation() {
        // Try to get from placyMapConfig if passed from PHP
        if (placyMapConfig && placyMapConfig.startLocation) {
            startLocation = placyMapConfig.startLocation;
            console.log('Tema Story Map: Start location loaded:', startLocation);
            return;
        }
        
        // Fallback: Try to get from data attributes on body or container
        const container = document.querySelector('.tema-story-container');
        if (container) {
            const lat = container.getAttribute('data-start-lat');
            const lng = container.getAttribute('data-start-lng');
            
            if (lat && lng) {
                startLocation = [parseFloat(lng), parseFloat(lat)];
                console.log('Tema Story Map: Start location loaded from container:', startLocation);
            }
        }
    }

    /**
     * Add a permanent marker for the property/start location
     */
    function addPropertyMarker() {
        if (!startLocation) {
            return;
        }

        // Create custom marker element
        const el = document.createElement('div');
        el.className = 'property-marker';
        el.style.width = '32px';
        el.style.height = '32px';
        el.style.borderRadius = '50% 50% 50% 0';
        el.style.backgroundColor = '#e74c3c';
        el.style.border = '3px solid white';
        el.style.boxShadow = '0 3px 8px rgba(0,0,0,0.3)';
        el.style.transform = 'rotate(-45deg)';
        el.style.cursor = 'pointer';

        // Inner dot
        const innerDot = document.createElement('div');
        innerDot.style.width = '12px';
        innerDot.style.height = '12px';
        innerDot.style.backgroundColor = 'white';
        innerDot.style.borderRadius = '50%';
        innerDot.style.position = 'absolute';
        innerDot.style.top = '50%';
        innerDot.style.left = '50%';
        innerDot.style.transform = 'translate(-50%, -50%)';
        el.appendChild(innerDot);

        // Add marker to map
        const propertyMarker = new mapboxgl.Marker(el)
            .setLngLat(startLocation)
            .setPopup(
                new mapboxgl.Popup({ offset: 25 })
                    .setHTML('<h3 style="margin: 0; font-size: 1rem; font-weight: 600;">Eiendommen</h3><p style="margin: 4px 0 0 0; font-size: 0.875rem; color: #666;">Startpunkt for gangavstand</p>')
            )
            .addTo(map);

        console.log('Tema Story Map: Property marker added at', startLocation);
    }

    /**
     * Fetch walking distance and time from start location to POI using Mapbox Directions API
     * @param {Array} destination - [lng, lat] coordinates of destination
     * @returns {Promise<Object>} Object with distance (meters) and duration (seconds)
     */
    async function getWalkingDistance(destination) {
        if (!startLocation) {
            return null;
        }

        // Check cache first
        const cacheKey = `${destination[0]},${destination[1]}`;
        if (walkingDistances.has(cacheKey)) {
            return walkingDistances.get(cacheKey);
        }

        try {
            const url = `https://api.mapbox.com/directions/v5/mapbox/walking/${startLocation[0]},${startLocation[1]};${destination[0]},${destination[1]}?geometries=geojson&access_token=${mapboxgl.accessToken}`;
            
            const response = await fetch(url);
            const data = await response.json();

            if (data.routes && data.routes.length > 0) {
                const route = data.routes[0];
                const result = {
                    distance: route.distance, // meters
                    duration: route.duration, // seconds
                    geometry: route.geometry  // GeoJSON for route drawing
                };

                // Cache the result
                walkingDistances.set(cacheKey, result);
                
                return result;
            }
        } catch (error) {
            console.error('Tema Story Map: Error fetching walking distance:', error);
        }

        return null;
    }

    /**
     * Format duration in seconds to human-readable format
     * @param {number} seconds - Duration in seconds
     * @returns {string} Formatted duration like "8 min" or "1 t 15 min"
     */
    function formatDuration(seconds) {
        const minutes = Math.round(seconds / 60);
        
        if (minutes < 60) {
            return `${minutes} min`;
        }
        
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;
        
        if (remainingMinutes === 0) {
            return `${hours} t`;
        }
        
        return `${hours} t ${remainingMinutes} min`;
    }

    /**
     * Format distance in meters to human-readable format
     * @param {number} meters - Distance in meters
     * @returns {string} Formatted distance like "650m" or "1.2km"
     */
    function formatDistance(meters) {
        if (meters < 1000) {
            return `${Math.round(meters)}m`;
        }
        
        return `${(meters / 1000).toFixed(1)}km`;
    }

    /**
     * Draw walking route on the map with duration label
     * @param {Object} geometry - GeoJSON geometry from Directions API
     * @param {number} duration - Duration in seconds
     */
    function drawRoute(geometry, duration) {
        // Remove existing route if any
        if (currentRoute) {
            if (map.getLayer('route')) {
                map.removeLayer('route');
            }
            if (map.getSource('route')) {
                map.removeSource('route');
            }
            if (map.getLayer('route-label')) {
                map.removeLayer('route-label');
            }
            if (map.getSource('route-label')) {
                map.removeSource('route-label');
            }
            currentRoute = null;
        }

        // Add new route
        if (geometry) {
            map.addSource('route', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: geometry
                }
            });

            map.addLayer({
                id: 'route',
                type: 'line',
                source: 'route',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#76908D',
                    'line-width': 6,
                    'line-opacity': 0.9,
                    'line-dasharray': [0, 1.5, 3]  // Dotted line: gap, shorter dot, longer gap
                }
            });

            // Add duration label at midpoint of route (50% of total length)
            if (duration && geometry.coordinates && geometry.coordinates.length > 0) {
                const coords = geometry.coordinates;
                
                // Calculate the actual midpoint by measuring 50% of total distance
                let totalDistance = 0;
                const distances = [0];
                
                // Calculate cumulative distances
                for (let i = 1; i < coords.length; i++) {
                    const dx = coords[i][0] - coords[i-1][0];
                    const dy = coords[i][1] - coords[i-1][1];
                    const segmentDistance = Math.sqrt(dx * dx + dy * dy);
                    totalDistance += segmentDistance;
                    distances.push(totalDistance);
                }
                
                // Find the coordinate at 50% of total distance
                const halfDistance = totalDistance / 2;
                let midPoint = coords[Math.floor(coords.length / 2)]; // fallback
                
                for (let i = 1; i < distances.length; i++) {
                    if (distances[i] >= halfDistance) {
                        // Interpolate between this point and the previous
                        const prevDist = distances[i - 1];
                        const nextDist = distances[i];
                        const ratio = (halfDistance - prevDist) / (nextDist - prevDist);
                        
                        midPoint = [
                            coords[i - 1][0] + (coords[i][0] - coords[i - 1][0]) * ratio,
                            coords[i - 1][1] + (coords[i][1] - coords[i - 1][1]) * ratio
                        ];
                        break;
                    }
                }
                
                map.addSource('route-label', {
                    type: 'geojson',
                    data: {
                        type: 'Feature',
                        properties: {
                            duration: formatDuration(duration)
                        },
                        geometry: {
                            type: 'Point',
                            coordinates: midPoint
                        }
                    }
                });

                map.addLayer({
                    id: 'route-label',
                    type: 'symbol',
                    source: 'route-label',
                    layout: {
                        'text-field': ['concat', '  ', ['get', 'duration'], '  '],
                        'text-font': ['DIN Pro Medium', 'Arial Unicode MS Regular'],
                        'text-size': 13,
                        'text-offset': [0, 0],
                        'text-anchor': 'center',
                        'text-padding': 4
                    },
                    paint: {
                        'text-color': '#ffffff',
                        'text-halo-color': '#76908D',
                        'text-halo-width': 10,
                        'text-halo-blur': 0
                    }
                });
            }

            currentRoute = geometry;
        }
    }

    /**
     * Clear the route from the map
     */
    function clearRoute() {
        if (map.getLayer('route')) {
            map.removeLayer('route');
        }
        if (map.getSource('route')) {
            map.removeSource('route');
        }
        currentRoute = null;
    }

    /**
     * Initialize the map
     */
    function initMap() {
        const mapContainer = document.getElementById('tema-story-map');
        
        if (!mapContainer) {
            console.warn('Tema Story Map: Map container not found');
            return;
        }

        // Check if Mapbox token is available
        if (!placyMapConfig || !placyMapConfig.mapboxToken) {
            console.error('Tema Story Map: Mapbox token not found');
            mapContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">Mapbox token not configured</div>';
            return;
        }

        // Initialize Mapbox map
        mapboxgl.accessToken = placyMapConfig.mapboxToken;
        
        map = new mapboxgl.Map({
            container: 'tema-story-map',
            style: 'mapbox://styles/mapbox/light-v11',
            center: CONFIG.DEFAULT_CENTER,
            zoom: CONFIG.DEFAULT_ZOOM,
        });

        // Add navigation controls
        map.addControl(new mapboxgl.NavigationControl(), 'top-right');

        // Wait for map to load before parsing data
        map.on('load', function() {
            // Remove POI labels and icons from the map
            const layers = map.getStyle().layers;
            layers.forEach(function(layer) {
                if (layer.id.includes('poi') || layer.id.includes('label')) {
                    map.setLayoutProperty(layer.id, 'visibility', 'none');
                }
            });
            
            // Get start location from page
            getStartLocation();
            
            // Add property marker if start location exists
            if (startLocation) {
                addPropertyMarker();
            }
            
            parseChapterData();
            initScrollTracking();
            
            // Show markers for first chapter on load
            const firstChapterId = getFirstChapterId();
            if (firstChapterId) {
                updateMapForChapter(firstChapterId);
            }
        });
    }

    /**
     * Parse POI data from chapter sections in the DOM
     */
    function parseChapterData() {
        const chapters = document.querySelectorAll('.chapter');
        
        if (chapters.length === 0) {
            console.warn('Tema Story Map: No chapters found. Make sure to wrap content in <section class="chapter" data-chapter-id="...">');
            return;
        }

        chapters.forEach(function(chapter, index) {
            const chapterId = chapter.getAttribute('data-chapter-id');
            
            if (!chapterId) {
                console.warn('Tema Story Map: Chapter missing data-chapter-id attribute', chapter);
                return;
            }

            // Find all POI list items within this chapter
            let poiItems = chapter.querySelectorAll('.poi-list-item');
            
            // FALLBACK: If this chapter has no POIs, look for POIs between this chapter and the next
            if (poiItems.length === 0 && index < chapters.length - 1) {
                const nextChapter = chapters[index + 1];
                const allPoisOnPage = document.querySelectorAll('.poi-list-item');
                const chapterRect = chapter.getBoundingClientRect();
                const nextChapterRect = nextChapter.getBoundingClientRect();
                
                // Find POIs that are positioned between this chapter and the next
                const poisBetweenChapters = Array.from(allPoisOnPage).filter(function(poi) {
                    const poiRect = poi.getBoundingClientRect();
                    // Check if POI is not in any chapter but appears after this one
                    let inChapter = false;
                    chapters.forEach(function(ch) {
                        if (ch.contains(poi)) inChapter = true;
                    });
                    return !inChapter && poi.offsetTop > chapter.offsetTop && poi.offsetTop < nextChapter.offsetTop;
                });
                
                poiItems = poisBetweenChapters;
                if (poisBetweenChapters.length > 0) {
                    console.log(`Tema Story Map: Found ${poisBetweenChapters.length} POIs between chapters for "${chapterId}"`);
                }
            }
            // FALLBACK: If this is the last chapter and has no POIs, grab all remaining POIs
            else if (poiItems.length === 0 && index === chapters.length - 1) {
                const allPoisOnPage = document.querySelectorAll('.poi-list-item');
                const poisAfterChapter = Array.from(allPoisOnPage).filter(function(poi) {
                    let inChapter = false;
                    chapters.forEach(function(ch) {
                        if (ch.contains(poi)) inChapter = true;
                    });
                    return !inChapter && poi.offsetTop > chapter.offsetTop;
                });
                
                poiItems = poisAfterChapter;
                if (poisAfterChapter.length > 0) {
                    console.log(`Tema Story Map: Found ${poisAfterChapter.length} POIs after last chapter "${chapterId}"`);
                }
            }

            const pois = [];

            poiItems.forEach(function(item) {
                const poiId = item.getAttribute('data-poi-id');
                const coordsAttr = item.getAttribute('data-poi-coords');
                const imageUrl = item.getAttribute('data-poi-image');
                const title = item.getAttribute('data-poi-title') || 'Untitled POI';

                if (coordsAttr) {
                    try {
                        const coords = JSON.parse(coordsAttr);
                        if (Array.isArray(coords) && coords.length === 2) {
                            pois.push({
                                id: poiId,
                                coords: [parseFloat(coords[1]), parseFloat(coords[0])], // [lng, lat] for Mapbox
                                title: title.trim(),
                                image: imageUrl || null,
                                element: item // Store reference to DOM element
                            });
                        }
                    } catch (e) {
                        console.warn('Tema Story Map: Invalid coords format for POI', item, e);
                    }
                }
            });

            if (pois.length > 0) {
                chapterData.set(chapterId, pois);
                console.log(`Tema Story Map: Loaded ${pois.length} POIs for chapter "${chapterId}"`);
            }
        });

        console.log(`Tema Story Map: Parsed ${chapterData.size} chapters with POIs`);
        
        // Add walking times to POI list items
        addWalkingTimesToPOIList();
        
        // Initialize POI hover tracking after parsing is done
        setTimeout(function() {
            initPOIHoverTracking();
        }, 500);
    }

    /**
     * Add walking times to POI list items
     */
    async function addWalkingTimesToPOIList() {
        if (!startLocation) {
            // Hide walking time elements if no start location
            document.querySelectorAll('.poi-walking-time').forEach(function(el) {
                const parent = el.closest('.flex');
                if (parent) {
                    parent.style.display = 'none';
                }
            });
            return;
        }

        // Get all POIs from all chapters
        const allPois = [];
        chapterData.forEach(function(pois) {
            allPois.push(...pois);
        });

        // Fetch walking distances for all POIs
        for (const poi of allPois) {
            if (poi.element) {
                const walking = await getWalkingDistance(poi.coords);
                
                if (walking) {
                    // Find walking time element
                    const walkTimeEl = poi.element.querySelector('.poi-walking-time');
                    
                    if (walkTimeEl) {
                        walkTimeEl.textContent = formatDuration(walking.duration) + ' gange';
                    }
                }
            }
        }
    }

    /**
     * Show POI on map - called from "Se på kart" button
     * @param {HTMLElement} button - The button element that was clicked
     */
    window.showPOIOnMap = function(button) {
        const poiItem = button.closest('.poi-list-item');
        if (!poiItem) return;

        const coordsAttr = poiItem.getAttribute('data-poi-coords');
        if (!coordsAttr) return;

        try {
            const coords = JSON.parse(coordsAttr);
            if (Array.isArray(coords) && coords.length === 2) {
                const lngLat = [parseFloat(coords[1]), parseFloat(coords[0])]; // [lng, lat] for Mapbox
                
                // Clear any active click state from previous marker clicks
                document.querySelectorAll('.poi-list-item').forEach(function(item) {
                    item.classList.remove('poi-active-click');
                });
                
                // Fit bounds to show both start location and POI
                if (startLocation) {
                    const bounds = new mapboxgl.LngLatBounds();
                    bounds.extend(startLocation);
                    bounds.extend(lngLat);
                    
                    map.fitBounds(bounds, {
                        padding: 120,
                        duration: 1500,
                        maxZoom: 16
                    });
                } else {
                    // Fallback: just fly to POI if no start location
                    map.flyTo({
                        center: lngLat,
                        zoom: 16,
                        duration: 1500,
                        essential: true
                    });
                }

                // Scroll map into view on mobile
                const mapColumn = document.querySelector('.map-column');
                if (mapColumn && window.innerWidth < 1024) {
                    mapColumn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }

                // Draw route if we have start location
                if (startLocation) {
                    getWalkingDistance(lngLat).then(function(walking) {
                        if (walking && walking.geometry) {
                            setTimeout(function() {
                                drawRoute(walking.geometry, walking.duration);
                            }, 1000); // Wait for fly animation
                        }
                    });
                }
            }
        } catch (e) {
            console.warn('Error showing POI on map:', e);
        }
    };

    /**
     * Initialize Intersection Observer for scroll tracking
     */
    function initScrollTracking() {
        const chapters = document.querySelectorAll('.chapter');
        
        if (chapters.length === 0) {
            return;
        }

        // Chapter observer for map updates - using viewport as root (null)
        const chapterObserverOptions = {
            root: null, // null means viewport
            rootMargin: '-40% 0px -40% 0px', // Trigger when chapter crosses center of viewport
            threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0] // Multiple thresholds for better tracking
        };

        observer = new IntersectionObserver(handleIntersection, chapterObserverOptions);

        chapters.forEach(function(chapter) {
            observer.observe(chapter);
        });

    }

    /**
     * Initialize hover tracking for individual POI items
     */
    function initPOIHoverTracking() {
        const poiItems = document.querySelectorAll('.poi-list-item');
        
        if (poiItems.length === 0) {
            return;
        }

        poiItems.forEach(function(item) {
            // Activate on hover
            item.addEventListener('mouseenter', function() {
                highlightActivePOI(this);
            });
            
            // Deactivate on mouse leave
            item.addEventListener('mouseleave', function() {
                clearActivePOI();
            });
        });
        
        console.log('Tema Story Map: Hover tracking initialized for', poiItems.length, 'POI items');
    }

    /**
     * Clear all active POI states (cards and routes)
     * @param {boolean} keepRoute - If true, don't clear the route (useful when switching between POIs)
     */
    function clearActivePOIState(keepRoute) {
        // Clear route from map unless keepRoute is true
        if (!keepRoute) {
            clearRoute();
        }
        
        // Clear card highlights and reset buttons
        document.querySelectorAll('.poi-list-item').forEach(function(item) {
            item.classList.remove('poi-active-click');
            
            // Reset button container and button
            const buttonContainer = item.querySelector('.poi-button-container');
            const button = item.querySelector('.poi-show-on-map, button[onclick*="showPOIOnMap"]');
            
            // Remove clear button if it exists
            if (buttonContainer) {
                const clearBtn = buttonContainer.querySelector('.poi-clear-button');
                if (clearBtn) {
                    clearBtn.remove();
                }
            }
            
            if (button) {
                button.textContent = 'Se på kart';
                
                // Determine button type to restore correct style
                const isSmallButton = button.classList.contains('poi-show-on-map'); // poi-list
                const parentCard = button.closest('.poi-list-item');
                const isMediumButton = parentCard && parentCard.classList.contains('poi-gallery-item'); // poi-gallery
                
                // Remove color classes
                button.className = button.className.replace(/bg-\S+/g, '').replace(/text-\S+/g, '').replace(/hover:bg-\S+/g, '');
                
                if (isSmallButton) {
                    // Small button style (poi-list) - restore original
                    button.className = 'poi-show-on-map px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded transition-colors duration-200 whitespace-nowrap';
                } else if (isMediumButton) {
                    // Medium button style (poi-gallery) - restore original
                    button.className = 'inline-flex items-center gap-2 px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors';
                } else {
                    // Large button style (poi-highlight) - restore original
                    button.className = 'inline-flex items-center gap-2 px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors';
                }
            }
        });
        
        console.log('Tema Story Map: Cleared all active POI states');
    }

    /**
     * Highlight card in left column when clicking marker
     * @param {string} poiId - The POI ID to highlight
     * @returns {Promise} Resolves when scroll animation completes
     */
    function highlightCardInColumn(poiId) {
        return new Promise(function(resolve) {
            // First, clear any existing highlights and reset buttons (but keep route)
            clearActivePOIState(true); // Don't clear route - it will be updated separately
            
            // Find and highlight the corresponding card
            const targetCard = document.querySelector(`.poi-list-item[data-poi-id="${poiId}"]`);
            
            if (targetCard) {
                // Add visual highlight class
                targetCard.classList.add('poi-active-click');
                
                // Update button to show active state and add clear button
                const button = targetCard.querySelector('.poi-show-on-map, button[onclick*="showPOIOnMap"]');
                if (button) {
                    // Determine button size based on context
                    const isSmallButton = button.classList.contains('poi-show-on-map'); // poi-list
                    const isMediumButton = button.classList.contains('text-sm'); // poi-gallery
                    const isLargeButton = !isSmallButton && !isMediumButton; // poi-highlight
                    
                    button.textContent = '✓ Aktivt i kartet';
                    
                    // Remove old color classes
                    button.className = button.className.replace(/bg-\S+/g, '').replace(/text-\S+/g, '').replace(/hover:bg-\S+/g, '');
                    
                    // Add active state colors
                    button.classList.add('bg-emerald-600', 'hover:bg-emerald-700', 'text-white');
                    
                    // Ensure proper styling is maintained based on size
                    if (isSmallButton) {
                        button.classList.add('px-3', 'py-1.5', 'font-medium', 'text-xs', 'rounded', 'transition-colors', 'duration-200', 'whitespace-nowrap');
                    } else if (isMediumButton) {
                        button.classList.add('inline-flex', 'items-center', 'gap-2', 'px-4', 'py-2', 'text-sm', 'rounded-lg', 'transition-colors');
                    } else {
                        button.classList.add('inline-flex', 'items-center', 'gap-2', 'px-6', 'py-2', 'rounded-lg', 'transition-colors');
                    }
                    
                    // Find button container (should exist from PHP template)
                    const buttonContainer = targetCard.querySelector('.poi-button-container');
                    
                    // Add clear button if container exists and clear button doesn't exist
                    if (buttonContainer && !buttonContainer.querySelector('.poi-clear-button')) {
                        const clearButton = document.createElement('button');
                        
                        if (isSmallButton) {
                            // Small button style (poi-list)
                            clearButton.className = 'poi-clear-button px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded transition-colors duration-200 whitespace-nowrap';
                        } else if (isMediumButton) {
                            // Medium button style (poi-gallery)
                            clearButton.className = 'poi-clear-button inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors';
                        } else {
                            // Large button style (poi-highlight)
                            clearButton.className = 'poi-clear-button inline-flex items-center gap-2 px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors font-medium';
                        }
                        
                        clearButton.textContent = 'Fjern markering';
                        clearButton.onclick = function(e) {
                            e.stopPropagation();
                            clearActivePOIState();
                        };
                        buttonContainer.appendChild(clearButton);
                    }
                }
                
                // Scroll card into view smoothly
                targetCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                
                console.log('Tema Story Map: Highlighted card for POI:', poiId);
                
                // Wait for scroll animation to complete (~600ms for smooth scroll)
                setTimeout(resolve, 600);
            } else {
                resolve();
            }
        });
    }

    /**
     * Highlight active POI on map when hovering over card
     * @param {HTMLElement} poiElement - The POI list item element
     */
    function highlightActivePOI(poiElement) {
        const poiId = poiElement.getAttribute('data-poi-id');
        
        if (!poiId) return;

        // Add visual highlight to card
        poiElement.classList.add('poi-active-hover');
        
        // Highlight corresponding marker on map using POI ID
        highlightMarkerOnMap(poiId);
    }

    /**
     * Clear active POI highlight
     */
    function clearActivePOI() {
        // Remove highlight from all cards
        document.querySelectorAll('.poi-list-item').forEach(function(item) {
            item.classList.remove('poi-active-hover');
        });
        
        // Reset all markers
        resetAllMarkers();
    }

    /**
     * Reset all markers to default state
     */
    function resetAllMarkers() {
        markers.forEach(function(marker) {
            const markerContainer = marker.getElement();
            if (!markerContainer) return;
            
            // Don't touch the container's transform - Mapbox uses it for positioning
            markerContainer.style.zIndex = '1';
            
            const markerDot = markerContainer.querySelector('.tema-story-marker');
            const markerLabel = markerContainer.querySelector('.tema-story-marker-label');
            
            // Reset inner elements
            if (markerDot) {
                markerDot.style.transform = 'scale(1)';
                markerDot.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
                markerDot.style.transition = 'transform 300ms ease-out, box-shadow 300ms ease-out';
            }
            
            if (markerLabel) {
                markerLabel.style.backgroundColor = 'white';
                markerLabel.style.transform = 'scale(1)';
                markerLabel.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
                markerLabel.style.transition = 'transform 300ms ease-out, box-shadow 300ms ease-out, background-color 300ms ease-out';
            }
        });
    }

    /**
     * Highlight marker on map (hover effect)
     * @param {string} poiId - The POI ID to highlight
     */
    function highlightMarkerOnMap(poiId) {
        // Reset all markers first
        resetAllMarkers();
        
        // Find and highlight the specific POI marker using direct POI ID matching
        markers.forEach(function(marker) {
            const markerContainer = marker.getElement();
            if (!markerContainer) return;
            
            // Get POI ID from data attribute stored on marker container
            const markerPoiId = markerContainer.getAttribute('data-poi-id');
            const isActive = markerPoiId === poiId;
            
            if (isActive) {
                // Increase z-index for layering
                markerContainer.style.zIndex = '1000';
                
                // Add hover effect to inner elements (not container!)
                const markerDot = markerContainer.querySelector('.tema-story-marker');
                const markerLabel = markerContainer.querySelector('.tema-story-marker-label');
                
                if (markerDot) {
                    markerDot.style.transform = 'scale(1.15)';
                    markerDot.style.boxShadow = '0 0 0 4px rgba(118, 144, 141, 0.3), 0 4px 12px rgba(0,0,0,0.3)';
                }
                
                if (markerLabel) {
                    markerLabel.style.backgroundColor = '#fffae1';
                    markerLabel.style.transform = 'scale(1.05)';
                    markerLabel.style.boxShadow = '0 4px 16px rgba(118, 144, 141, 0.3)';
                }
            }
        });
    }

    /**
     * Handle intersection observer callback
     * @param {Array} entries - Intersection observer entries
     */
    function handleIntersection(entries) {
        // Always check ALL chapters to find the most visible one
        // Don't rely only on entries - they might not include all intersecting chapters
        const allChapters = document.querySelectorAll('.chapter');
        let mostVisibleChapter = null;
        let highestRatio = 0;
        
        allChapters.forEach(function(chapter) {
            // Get the chapter's position in viewport
            const rect = chapter.getBoundingClientRect();
            // Use viewport dimensions
            const viewportHeight = window.innerHeight;
            
            // Calculate how much of the chapter is visible in the center zone (-40% to +40%)
            const centerTop = viewportHeight * 0.4;
            const centerBottom = viewportHeight * 0.6;
            
            // Calculate intersection with center zone
            const visibleTop = Math.max(rect.top, centerTop);
            const visibleBottom = Math.min(rect.bottom, centerBottom);
            const visibleHeight = Math.max(0, visibleBottom - visibleTop);
            const centerZoneHeight = centerBottom - centerTop;
            const ratio = visibleHeight / centerZoneHeight;
            
            if (ratio > highestRatio) {
                highestRatio = ratio;
                mostVisibleChapter = chapter;
            }
        });
        
        // If we found a visible chapter, update to it
        if (mostVisibleChapter && highestRatio > 0) {
            const chapterId = mostVisibleChapter.getAttribute('data-chapter-id');
            
            if (chapterId && chapterId !== activeChapterId) {
                console.log('Tema Story Map: Active chapter changed to:', chapterId, 'with ratio:', highestRatio);
                activeChapterId = chapterId;
                
                // Debounce map updates to prevent rapid firing during scroll
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    updateMapForChapter(chapterId);
                }, CONFIG.DEBOUNCE_DELAY);
            }
        }
    }

    /**
     * Update map markers and bounds for a specific chapter
     * @param {string} chapterId - The chapter ID to display
     */
    function updateMapForChapter(chapterId) {
        const pois = chapterData.get(chapterId);
        
        if (!pois || pois.length === 0) {
            console.warn('Tema Story Map: No POIs found for chapter:', chapterId, '- keeping previous markers visible');
            // Don't update map if no POIs - keep showing previous chapter's markers
            return;
        }

        // Clear any existing route
        clearRoute();

        // Remove existing markers immediately
        removeMarkers();

        // Add new markers
        addMarkers(pois);
        fitMapToBounds(pois);
    }

    /**
     * Remove all markers from the map
     */
    function removeMarkers() {
        // Immediately remove all markers without animation
        markers.forEach(function(marker) {
            marker.remove();
        });
        markers = [];
    }

    /**
     * Add markers to the map
     * @param {Array} pois - Array of POI objects with coords and title
     */
    async function addMarkers(pois) {
        // First, fetch all walking distances in parallel
        const walkingPromises = pois.map(poi => getWalkingDistance(poi.coords));
        const walkingData = await Promise.all(walkingPromises);

        pois.forEach(function(poi, index) {
            const walking = walkingData[index];
            
            // Create marker container with label
            const markerContainer = document.createElement('div');
            markerContainer.className = 'tema-story-marker-container';
            markerContainer.style.display = 'flex';
            markerContainer.style.alignItems = 'center';
            markerContainer.style.gap = '4px';
            markerContainer.style.opacity = '0';
            markerContainer.style.transition = `opacity ${CONFIG.MARKER_FADE_DURATION}ms ease`;

            // Create marker dot
            const el = document.createElement('div');
            el.className = 'tema-story-marker';
            el.style.width = '48px';
            el.style.height = '48px';
            el.style.borderRadius = '10px';
            el.style.border = '3px solid white';
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            el.style.cursor = 'pointer';
            el.style.flexShrink = '0';
            el.style.overflow = 'hidden';
            
            // Use featured image if available, otherwise solid color
            if (poi.image) {
                el.style.backgroundImage = `url(${poi.image})`;
                el.style.backgroundSize = 'cover';
                el.style.backgroundPosition = 'center';
            } else {
                el.style.backgroundColor = '#76908D';
            }

            // Create label with walking time/distance
            const label = document.createElement('div');
            label.className = 'tema-story-marker-label';
            label.style.backgroundColor = 'white';
            label.style.padding = '8px';
            label.style.borderRadius = '8px';
            label.style.fontSize = '14px';
            label.style.fontWeight = '600';
            label.style.color = '#1a202c';
            label.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
            label.style.cursor = 'pointer';
            label.style.transition = 'opacity 300ms ease, visibility 300ms ease';
            label.style.display = 'flex';
            label.style.alignItems = 'center';
            label.style.gap = '8px';
            
            // Image in label (if available)
            if (poi.image) {
                const labelImage = document.createElement('div');
                labelImage.style.width = '40px';
                labelImage.style.height = '40px';
                labelImage.style.borderRadius = '6px';
                labelImage.style.backgroundImage = `url(${poi.image})`;
                labelImage.style.backgroundSize = 'cover';
                labelImage.style.backgroundPosition = 'center';
                labelImage.style.flexShrink = '0';
                label.appendChild(labelImage);
            }
            
            // Text container
            const textContainer = document.createElement('div');
            textContainer.style.display = 'flex';
            textContainer.style.flexDirection = 'column';
            textContainer.style.gap = '2px';
            
            // Title
            const titleSpan = document.createElement('span');
            titleSpan.textContent = poi.title;
            titleSpan.style.fontSize = '14px';
            titleSpan.style.fontWeight = '600';
            titleSpan.style.whiteSpace = 'nowrap';
            textContainer.appendChild(titleSpan);
            
            // Walking time (if available)
            if (walking) {
                const walkTimeSpan = document.createElement('span');
                walkTimeSpan.textContent = formatDuration(walking.duration);
                walkTimeSpan.style.fontSize = '12px';
                walkTimeSpan.style.fontWeight = '400';
                walkTimeSpan.style.color = '#666';
                walkTimeSpan.style.whiteSpace = 'nowrap';
                textContainer.appendChild(walkTimeSpan);
            }
            
            label.appendChild(textContainer);

            // Append dot and label to container
            markerContainer.appendChild(el);
            markerContainer.appendChild(label);

            // Always show labels
            label.style.opacity = '1';
            label.style.visibility = 'visible';
            label.style.pointerEvents = 'auto';
            
            // Always hide marker dot (we only show labels now)
            el.style.opacity = '0';
            el.style.visibility = 'hidden';
            el.style.pointerEvents = 'none';

            // Store POI ID on marker container for easy lookup
            markerContainer.setAttribute('data-poi-id', poi.id);
            
            // Create marker with container (no popup)
            const marker = new mapboxgl.Marker(markerContainer)
                .setLngLat(poi.coords)
                .addTo(map);

            // Add click handler to center map, draw route, and highlight card
            markerContainer.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Step 1: Fit bounds to show both start location and POI
                if (startLocation) {
                    const bounds = new mapboxgl.LngLatBounds();
                    bounds.extend(startLocation);
                    bounds.extend(poi.coords);
                    
                    map.fitBounds(bounds, {
                        padding: 120,
                        duration: 1200,
                        maxZoom: 16
                    });
                } else {
                    // Fallback: just fly to POI if no start location
                    map.flyTo({
                        center: poi.coords,
                        zoom: 16,
                        duration: 1200,
                        essential: true
                    });
                }
                
                // Step 2: Draw walking route shortly after map starts moving
                if (walking && walking.geometry) {
                    setTimeout(function() {
                        drawRoute(walking.geometry, walking.duration);
                    }, 400);
                }
                
                // Step 3: After map animation is well underway, scroll to card
                setTimeout(function() {
                    highlightCardInColumn(poi.id);
                }, 800); // Start card scroll after map is halfway through
            });

            markers.push(marker);

            // Fade in marker
            setTimeout(function() {
                markerContainer.style.opacity = '1';
            }, 10);
        });
    }

    /**
     * Fit map bounds to show all markers
     * @param {Array} pois - Array of POI objects with coords
     */
    function fitMapToBounds(pois) {
        if (pois.length === 0) {
            return;
        }

        if (pois.length === 1) {
            // Single marker: center and zoom
            map.flyTo({
                center: pois[0].coords,
                zoom: CONFIG.DEFAULT_ZOOM,
                duration: 1000
            });
            console.log('Tema Story Map: Zoom level after flyTo:', CONFIG.DEFAULT_ZOOM);
        } else {
            // Multiple markers: fit bounds
            const bounds = new mapboxgl.LngLatBounds();

            pois.forEach(function(poi) {
                bounds.extend(poi.coords);
            });

            map.fitBounds(bounds, {
                padding: CONFIG.FIT_BOUNDS_PADDING,
                duration: 1000
            });
            
            // Log zoom level after fitBounds completes
            map.once('moveend', function() {
                console.log('Tema Story Map: Zoom level after fitBounds:', map.getZoom());
            });
        }
    }

    /**
     * Get the ID of the first chapter
     * @returns {string|null} First chapter ID or null
     */
    function getFirstChapterId() {
        const firstChapter = document.querySelector('.chapter[data-chapter-id]');
        return firstChapter ? firstChapter.getAttribute('data-chapter-id') : null;
    }

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }

})();
