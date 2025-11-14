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
            style: 'mapbox://styles/mapbox/streets-v12',
            center: CONFIG.DEFAULT_CENTER,
            zoom: CONFIG.DEFAULT_ZOOM,
        });

        // Add navigation controls
        map.addControl(new mapboxgl.NavigationControl(), 'top-right');

        // Wait for map to load before parsing data
        map.on('load', function() {
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

        chapters.forEach(function(chapter) {
            const chapterId = chapter.getAttribute('data-chapter-id');
            
            if (!chapterId) {
                console.warn('Tema Story Map: Chapter missing data-chapter-id attribute', chapter);
                return;
            }

            // Find all POI list items within this chapter
            const poiItems = chapter.querySelectorAll('.poi-list-item');
            const pois = [];

            poiItems.forEach(function(item) {
                const poiId = item.getAttribute('data-poi-id');
                const coordsAttr = item.getAttribute('data-poi-coords');
                const title = item.querySelector('.poi-list-item-title')?.textContent || 'Untitled POI';

                if (coordsAttr) {
                    try {
                        const coords = JSON.parse(coordsAttr);
                        if (Array.isArray(coords) && coords.length === 2) {
                            pois.push({
                                id: poiId,
                                coords: [parseFloat(coords[1]), parseFloat(coords[0])], // [lng, lat] for Mapbox
                                title: title.trim()
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
    }

    /**
     * Initialize Intersection Observer for scroll tracking
     */
    function initScrollTracking() {
        const chapters = document.querySelectorAll('.chapter');
        
        if (chapters.length === 0) {
            return;
        }

        const observerOptions = {
            root: document.querySelector('.content-column'),
            rootMargin: '0px',
            threshold: CONFIG.CHAPTER_THRESHOLD
        };

        observer = new IntersectionObserver(handleIntersection, observerOptions);

        chapters.forEach(function(chapter) {
            observer.observe(chapter);
        });
    }

    /**
     * Handle intersection observer callback
     * @param {Array} entries - Intersection observer entries
     */
    function handleIntersection(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const chapterId = entry.target.getAttribute('data-chapter-id');
                
                if (chapterId && chapterId !== activeChapterId) {
                    console.log('Tema Story Map: Active chapter changed to:', chapterId);
                    activeChapterId = chapterId;
                    
                    // Debounce map updates to prevent rapid firing during scroll
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        updateMapForChapter(chapterId);
                    }, CONFIG.DEBOUNCE_DELAY);
                }
            }
        });
    }

    /**
     * Update map markers and bounds for a specific chapter
     * @param {string} chapterId - The chapter ID to display
     */
    function updateMapForChapter(chapterId) {
        const pois = chapterData.get(chapterId);
        
        if (!pois || pois.length === 0) {
            console.warn('Tema Story Map: No POIs found for chapter:', chapterId);
            return;
        }

        // Remove existing markers with fade effect
        removeMarkers();

        // Add new markers with fade-in effect
        setTimeout(function() {
            addMarkers(pois);
            fitMapToBounds(pois);
        }, CONFIG.MARKER_FADE_DURATION);
    }

    /**
     * Remove all markers from the map
     */
    function removeMarkers() {
        markers.forEach(function(marker) {
            // Add fade-out class if element exists
            const element = marker.getElement();
            if (element) {
                element.style.transition = `opacity ${CONFIG.MARKER_FADE_DURATION}ms ease`;
                element.style.opacity = '0';
                
                setTimeout(function() {
                    marker.remove();
                }, CONFIG.MARKER_FADE_DURATION);
            } else {
                marker.remove();
            }
        });
        markers = [];
    }

    /**
     * Add markers to the map
     * @param {Array} pois - Array of POI objects with coords and title
     */
    function addMarkers(pois) {
        pois.forEach(function(poi) {
            // Create marker element
            const el = document.createElement('div');
            el.className = 'tema-story-marker';
            el.style.backgroundColor = '#76908D';
            el.style.width = '30px';
            el.style.height = '30px';
            el.style.borderRadius = '50%';
            el.style.border = '3px solid white';
            el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
            el.style.cursor = 'pointer';
            el.style.opacity = '0';
            el.style.transition = `opacity ${CONFIG.MARKER_FADE_DURATION}ms ease`;

            // Create marker
            const marker = new mapboxgl.Marker(el)
                .setLngLat(poi.coords)
                .setPopup(
                    new mapboxgl.Popup({ offset: 25 })
                        .setHTML(`<h3 style="margin: 0; font-size: 1rem; font-weight: 600;">${poi.title}</h3>`)
                )
                .addTo(map);

            markers.push(marker);

            // Fade in marker
            setTimeout(function() {
                el.style.opacity = '1';
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
