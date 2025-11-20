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
        // Marker size based on zoom level
        MARKER_SIZES: {
            SMALL: { size: 24, minZoom: 12, maxZoom: 14 },
            MEDIUM: { size: 48, minZoom: 15, maxZoom: 22 }
        },
        // Label collision detection - dynamic based on zoom
        LABEL_COLLISION_ZOOM_START: 16,  // Start applying collision detection
        LABEL_COLLISION_ZOOM_END: 18,    // Full visibility at this zoom
        LABEL_MIN_DISTANCE: 140,         // Minimum pixel distance between labels
        LABEL_PRIORITY_DISTANCE: 100,    // Distance to check for nearby labels
        LABEL_MAX_VISIBLE: 8             // Max number of labels visible at once at low zoom
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
    let currentDurationMarkers = []; // Store duration markers for cleanup
    
    // Google Places API state
    let placesApiResults = new Map(); // Store API results per chapter
    let placesMarkers = []; // Store Google Places markers separately
    let showingApiResults = new Map(); // Track which chapters are showing API results

    /**
     * Get marker size based on current zoom level
     * @param {number} zoom - Current zoom level
     * @returns {number} Marker size in pixels
     */
    function getMarkerSize(zoom) {
        if (zoom >= CONFIG.MARKER_SIZES.MEDIUM.minZoom) {
            return CONFIG.MARKER_SIZES.MEDIUM.size;
        } else {
            return CONFIG.MARKER_SIZES.SMALL.size;
        }
    }

    /**
     * Update all marker sizes based on zoom level
     * TASK 3: Also handle label visibility based on zoom
     * @param {number} zoom - Current zoom level
     */
    function updateMarkerSizes(zoom) {
        const size = getMarkerSize(zoom);
        console.log('Current zoom level:', zoom, '| Marker size:', size + 'px');
        
        // Update all POI markers
        const allMarkers = document.querySelectorAll('.marker-circle-container');
        allMarkers.forEach(function(circle) {
            // Only update if not currently scaled by hover/active
            const wrapper = circle.closest('.tema-story-marker-wrapper');
            const isActive = wrapper && wrapper.classList.contains('marker-active');
            const currentTransform = circle.style.transform;
            
            circle.style.width = size + 'px';
            circle.style.height = size + 'px';
            
            // Preserve any scale transform
            if (currentTransform && currentTransform.includes('scale')) {
                // Keep existing scale
            } else {
                circle.style.transform = 'scale(1)';
            }
        });
        
        // TASK 3: Update label visibility based on zoom level
        updateLabelVisibility(zoom);
    }
    
    /**
     * Update POI label visibility based on zoom level
     * TASK 3: Hide labels at zoom < 15, show at zoom >= 15
     * Property labels always visible, hover/active labels always visible
     * IMPROVED: Dynamic collision detection that intensifies at lower zoom levels
     * @param {number} zoom - Current zoom level
     */
    function updateLabelVisibility(zoom) {
        // Get all POI labels (not Property labels)
        const poiLabels = document.querySelectorAll('.marker-label-poi');
        
        // Calculate collision detection intensity based on zoom
        // Lower zoom = stricter spacing, higher zoom = more relaxed
        const zoomFactor = Math.max(0, Math.min(1, 
            (zoom - CONFIG.LABEL_COLLISION_ZOOM_START) / 
            (CONFIG.LABEL_COLLISION_ZOOM_END - CONFIG.LABEL_COLLISION_ZOOM_START)
        ));
        
        // Dynamic spacing: 180px at zoom 13, gradually reducing to 80px at zoom 17+
        const dynamicMinDistance = CONFIG.LABEL_MIN_DISTANCE + (40 * (1 - zoomFactor));
        
        // Dynamic max visible: 6 at low zoom, unlimited at high zoom
        const dynamicMaxVisible = zoom < 16 ? 
            Math.floor(CONFIG.LABEL_MAX_VISIBLE * (0.75 + 0.25 * zoomFactor)) : 
            Infinity;
        
        // First pass: determine which labels should be visible
        const labelsData = [];
        poiLabels.forEach(function(label) {
            const wrapper = label.closest('.tema-story-marker-wrapper');
            if (!wrapper) return;
            
            // Check if this marker is hovered or active (force visible)
            const isHovered = label.hasAttribute('data-force-visible');
            const isActive = wrapper.classList.contains('marker-active');
            const isProperty = wrapper.getAttribute('data-marker-type') === 'property';
            
            // Get screen position
            const rect = wrapper.getBoundingClientRect();
            
            labelsData.push({
                label: label,
                wrapper: wrapper,
                isHovered: isHovered,
                isActive: isActive,
                isProperty: isProperty,
                x: rect.left + rect.width / 2,
                y: rect.top + rect.height / 2,
                shouldShow: isHovered || isActive || zoom >= CONFIG.LABEL_COLLISION_ZOOM_START
            });
        });
        
        // Sort by priority: active > hovered > normal
        labelsData.sort(function(a, b) {
            if (a.isActive && !b.isActive) return -1;
            if (!a.isActive && b.isActive) return 1;
            if (a.isHovered && !b.isHovered) return -1;
            if (!a.isHovered && b.isHovered) return 1;
            return 0;
        });
        
        // Second pass: apply visibility with collision detection
        const visiblePositions = [];
        let visibleCount = 0;
        
        labelsData.forEach(function(data) {
            const { label, wrapper, isHovered, isActive, shouldShow, x, y } = data;
            
            if (isHovered || isActive) {
                // Always show if hovered or active
                label.style.opacity = '1';
                label.style.visibility = 'visible';
                label.style.pointerEvents = 'auto';
                label.style.transform = 'none';
                visiblePositions.push({ x, y });
                visibleCount++;
            } else if (zoom < CONFIG.LABEL_COLLISION_ZOOM_START) {
                // Below collision zoom threshold - hide all labels
                label.style.opacity = '0';
                label.style.visibility = 'hidden';
                label.style.pointerEvents = 'none';
            } else if (zoom < 16) {
                // Between collision start and full visibility - use dynamic spacing
                const hasNearbyLabel = visiblePositions.some(function(pos) {
                    const distance = Math.sqrt(Math.pow(pos.x - x, 2) + Math.pow(pos.y - y, 2));
                    return distance < dynamicMinDistance;
                });
                
                if (hasNearbyLabel || visibleCount >= dynamicMaxVisible) {
                    // Hide if too close to another label or too many visible
                    label.style.opacity = '0';
                    label.style.visibility = 'hidden';
                    label.style.pointerEvents = 'none';
                } else {
                    // Show this label with opacity based on zoom
                    const labelOpacity = 0.85 + (0.15 * zoomFactor);
                    label.style.opacity = labelOpacity.toString();
                    label.style.visibility = 'visible';
                    label.style.pointerEvents = 'auto';
                    label.style.transform = 'none';
                    visiblePositions.push({ x, y });
                    visibleCount++;
                }
            } else if (zoom < CONFIG.LABEL_COLLISION_ZOOM_END) {
                // Between 15 and 17 - gradually show more labels with slight offsets
                const hasNearbyLabel = visiblePositions.some(function(pos) {
                    const distance = Math.sqrt(Math.pow(pos.x - x, 2) + Math.pow(pos.y - y, 2));
                    return distance < CONFIG.LABEL_PRIORITY_DISTANCE;
                });
                
                if (hasNearbyLabel) {
                    // Offset label slightly to avoid overlap
                    label.style.transform = 'translateY(-8px)';
                    label.style.opacity = '0.9';
                } else {
                    label.style.transform = 'none';
                    label.style.opacity = '1';
                }
                
                label.style.visibility = 'visible';
                label.style.pointerEvents = 'auto';
                visiblePositions.push({ x, y });
            } else {
                // High zoom (>= 17) - show all labels with minimal collision detection
                const hasVeryCloseLabel = visiblePositions.some(function(pos) {
                    const distance = Math.sqrt(Math.pow(pos.x - x, 2) + Math.pow(pos.y - y, 2));
                    return distance < 60; // Very tight threshold
                });
                
                if (hasVeryCloseLabel) {
                    label.style.transform = 'translateY(-10px)';
                } else {
                    label.style.transform = 'none';
                }
                
                label.style.opacity = '1';
                label.style.visibility = 'visible';
                label.style.pointerEvents = 'auto';
                visiblePositions.push({ x, y });
            }
        });
        
        // Property labels always visible (no changes needed, but ensure they stay visible)
        const propertyLabels = document.querySelectorAll('.marker-label-property');
        propertyLabels.forEach(function(label) {
            label.style.opacity = '1';
            label.style.visibility = 'visible';
            label.style.pointerEvents = 'auto';
            label.style.transform = 'none';
        });
    }

    /**
     * Setup zoom listener for a map instance
     * @param {mapboxgl.Map} mapInstance - The map instance
     */
    function setupZoomListener(mapInstance) {
        if (!mapInstance) return;
        
        mapInstance.on('zoom', function() {
            const currentZoom = mapInstance.getZoom();
            updateMarkerSizes(currentZoom);
        });
        
        // Initial size update
        mapInstance.on('load', function() {
            const currentZoom = mapInstance.getZoom();
            updateMarkerSizes(currentZoom);
        });
    }

    /**
     * Get start location from page meta data
     */
    function getStartLocation() {
        // Try to get from placyMapConfig if passed from PHP
        if (placyMapConfig && placyMapConfig.startLocation) {
            startLocation = placyMapConfig.startLocation;
            return;
        }
        
        // Fallback: Try to get from data attributes on body or container
        const container = document.querySelector('.tema-story-container');
        if (container) {
            const lat = container.getAttribute('data-start-lat');
            const lng = container.getAttribute('data-start-lng');
            
            if (lat && lng) {
                startLocation = [parseFloat(lng), parseFloat(lat)];
            }
        }
    }

    /**
     * Add a permanent marker for the property/start location to a specific map
     */
    function addPropertyMarkerToMap(mapInstance) {
        if (!startLocation) {
            return;
        }

        const propertyLogo = placyMapConfig.propertyLogo;
        const propertyBackground = placyMapConfig.propertyBackground;
        const propertyLabel = placyMapConfig.propertyLabel || 'Eiendommen';

        // Create wrapper container (same as POI markers)
        const wrapper = document.createElement('div');
        wrapper.className = 'tema-story-marker-wrapper';
        wrapper.style.display = 'flex';
        wrapper.style.flexDirection = 'column';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '8px';
        wrapper.style.cursor = 'pointer';
        wrapper.style.zIndex = '1000'; // TASK 2: Highest z-index - Property always on top
        wrapper.setAttribute('data-marker-type', 'property');

        // Create circular image container with dynamic size (same as POI markers)
        const initialSize = getMarkerSize(mapInstance.getZoom());
        const circleContainer = document.createElement('div');
        circleContainer.className = 'marker-circle-container';
        circleContainer.style.width = initialSize + 'px';
        circleContainer.style.height = initialSize + 'px';
        circleContainer.style.borderRadius = '50%';
        circleContainer.style.border = '3px solid white';
        circleContainer.style.overflow = 'hidden';
        circleContainer.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
        circleContainer.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease, width 0.3s ease, height 0.3s ease';
        circleContainer.style.transformOrigin = 'center center';
        
        // Use property background or logo
        if (propertyBackground) {
            circleContainer.style.backgroundImage = `url(${propertyBackground})`;
            circleContainer.style.backgroundSize = 'cover';
            circleContainer.style.backgroundPosition = 'center';
        } else if (propertyLogo) {
            // If no background, use logo on white background
            circleContainer.style.backgroundColor = 'white';
            circleContainer.style.backgroundImage = `url(${propertyLogo})`;
            circleContainer.style.backgroundSize = '60%';
            circleContainer.style.backgroundPosition = 'center';
            circleContainer.style.backgroundRepeat = 'no-repeat';
        } else {
            // Fallback: red circle for property
            circleContainer.style.backgroundColor = '#e74c3c';
        }
        
        // Create label container under circle (same as POI markers)
        // TASK 2: Property label always visible
        const labelContainer = document.createElement('div');
        labelContainer.className = 'marker-label-container marker-label-property';
        labelContainer.style.display = 'flex';
        labelContainer.style.flexDirection = 'column';
        labelContainer.style.alignItems = 'center';
        labelContainer.style.gap = '2px';
        labelContainer.style.maxWidth = '120px';
        labelContainer.style.textAlign = 'center';
        labelContainer.style.opacity = '1'; // Always visible
        labelContainer.style.visibility = 'visible';
        labelContainer.style.pointerEvents = 'auto';
        
        // Property name
        const nameLabel = document.createElement('div');
        nameLabel.className = 'marker-name-label';
        nameLabel.textContent = propertyLabel;
        nameLabel.style.fontSize = '12px';
        nameLabel.style.fontWeight = '600';
        nameLabel.style.color = '#1a202c';
        nameLabel.style.lineHeight = '1.2';
        nameLabel.style.textShadow = '0 1px 2px rgba(255,255,255,0.8)';
        nameLabel.style.whiteSpace = 'nowrap';
        nameLabel.style.overflow = 'hidden';
        nameLabel.style.textOverflow = 'ellipsis';
        nameLabel.style.width = '100%';
        labelContainer.appendChild(nameLabel);
        
        // Subtitle "Punkt A"
        const subtitleLabel = document.createElement('div');
        subtitleLabel.textContent = 'Punkt A';
        subtitleLabel.style.fontSize = '10px';
        subtitleLabel.style.fontWeight = '500';
        subtitleLabel.style.color = '#6B7280';
        subtitleLabel.style.textShadow = '0 1px 2px rgba(255,255,255,0.8)';
        labelContainer.appendChild(subtitleLabel);
        
        // Append elements to wrapper
        wrapper.appendChild(circleContainer);
        wrapper.appendChild(labelContainer);

        // Hover effect - same as POI markers
        wrapper.addEventListener('mouseenter', function() {
            circleContainer.style.transform = 'scale(1.15)';
            circleContainer.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
        });
        
        wrapper.addEventListener('mouseleave', function() {
            circleContainer.style.transform = 'scale(1)';
            circleContainer.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
        });

        // Create Mapbox marker with center anchor (same as POI markers)
        const propertyMarker = new mapboxgl.Marker({
            element: wrapper,
            anchor: 'center',
            offset: [0, -10] // Offset up slightly so label doesn't overlap coordinate
        })
            .setLngLat(startLocation)
            .addTo(mapInstance);
        
        // Store marker reference
        markers.push(propertyMarker);
    }
    
    /**
     * Parse POI data and add markers for a specific chapter
     */
    async function parseAndAddMarkersForChapter(mapInstance, chapterId) {
        const chapter = document.querySelector(`.chapter[data-chapter-id="${chapterId}"]`);
        if (!chapter) {
            console.warn(`Multi Map: Chapter ${chapterId} not found in DOM`);
            return;
        }

        const poiItems = chapter.querySelectorAll('.poi-list-item[data-poi-coords], .poi-list-card[data-poi-coords]');
        const pois = [];
        let markerIndex = 0;

        for (const item of poiItems) {
            const coordsAttr = item.getAttribute('data-poi-coords');
            const poiId = item.getAttribute('data-poi-id');
            const title = item.getAttribute('data-poi-title');
            const image = item.getAttribute('data-poi-image');

            if (!coordsAttr) continue;

            try {
                const coords = JSON.parse(coordsAttr);
                if (Array.isArray(coords) && coords.length === 2) {
                    // coords from HTML: [lat, lng]
                    // Mapbox needs: [lng, lat]
                    const lngLat = [parseFloat(coords[1]), parseFloat(coords[0])];

                    // Get walking distance if start location exists
                    let walking = null;
                    if (startLocation) {
                        walking = await getWalkingDistance(lngLat);
                        
                        // Update POI element with walking time
                        if (walking) {
                            const walkTimeEl = item.querySelector('.poi-walking-time');
                            if (walkTimeEl) {
                                walkTimeEl.textContent = formatDuration(walking.duration) + ' gange';
                            }
                        }
                    }

                    // Get rating from DOM
                    const ratingEl = item.querySelector('.poi-rating-value');
                    const ratingCountEl = item.querySelector('.poi-rating-count');
                    let rating = null;
                    
                    if (ratingEl) {
                        const ratingText = ratingEl.textContent.replace(/\s+/g, ' ').trim();
                        const ratingMatch = ratingText.match(/(\d+\.?\d*)/);
                        if (ratingMatch) {
                            rating = {
                                value: parseFloat(ratingMatch[1]),
                                count: ratingCountEl ? ratingCountEl.textContent.trim() : null
                            };
                        }
                    }

                    const poi = {
                        id: poiId,
                        title: title || 'POI',
                        coords: lngLat,
                        image: image,
                        element: item,
                        index: markerIndex,
                        walking: walking,
                        rating: rating
                    };

                    pois.push(poi);

                    // Create and add marker
                    addMarkerForPOI(mapInstance, poi, chapterId);
                    markerIndex++;
                }
            } catch (e) {
                console.warn('Multi Map: Error parsing POI coords:', e);
            }
        }

        // Fit map to show all POIs in this chapter
        if (pois.length > 0) {
            fitMapToBounds(mapInstance, pois);
        }

    }
    
    /**
     * Add a marker for a POI to a specific map
     * Snapchat-style circular marker with image + label
     */
    function addMarkerForPOI(mapInstance, poi, chapterId) {
        // Create wrapper container (needed for proper Mapbox positioning)
        const wrapper = document.createElement('div');
        wrapper.className = 'tema-story-marker-wrapper';
        wrapper.style.display = 'flex';
        wrapper.style.flexDirection = 'column';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '8px';
        wrapper.style.cursor = 'pointer';
        wrapper.style.zIndex = '10'; // TASK 2: Default POI z-index
        
        // Create circular image container with dynamic size
        const initialSize = getMarkerSize(mapInstance.getZoom());
        const circleContainer = document.createElement('div');
        circleContainer.className = 'marker-circle-container';
        circleContainer.style.width = initialSize + 'px';
        circleContainer.style.height = initialSize + 'px';
        circleContainer.style.borderRadius = '50%';
        circleContainer.style.border = '3px solid white';
        circleContainer.style.overflow = 'hidden';
        circleContainer.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
        circleContainer.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease, width 0.3s ease, height 0.3s ease';
        circleContainer.style.transformOrigin = 'center center';
        
        // Set image or gray background
        if (poi.image) {
            circleContainer.style.backgroundImage = `url(${poi.image})`;
            circleContainer.style.backgroundSize = 'cover';
            circleContainer.style.backgroundPosition = 'center';
        } else {
            circleContainer.style.backgroundColor = '#9CA3AF'; // Gray background for missing images
        }
        
        // Create label container under circle
        // TASK 3: POI labels with zoom-based visibility
        const labelContainer = document.createElement('div');
        labelContainer.className = 'marker-label-container marker-label-poi';
        labelContainer.style.display = 'flex';
        labelContainer.style.flexDirection = 'column';
        labelContainer.style.alignItems = 'center';
        labelContainer.style.gap = '2px';
        labelContainer.style.maxWidth = '120px';
        labelContainer.style.textAlign = 'center';
        labelContainer.style.transition = 'opacity 0.3s ease, visibility 0.3s ease';
        // Initial state based on zoom level (will be updated by updateLabelVisibility)
        const currentZoom = mapInstance.getZoom();
        if (currentZoom < 15) {
            labelContainer.style.opacity = '0';
            labelContainer.style.visibility = 'hidden';
            labelContainer.style.pointerEvents = 'none';
        } else {
            labelContainer.style.opacity = '1';
            labelContainer.style.visibility = 'visible';
            labelContainer.style.pointerEvents = 'auto';
        }
        
        // POI name
        const nameLabel = document.createElement('div');
        nameLabel.className = 'marker-name-label';
        nameLabel.textContent = poi.title;
        nameLabel.style.fontSize = '12px';
        nameLabel.style.fontWeight = '600';
        nameLabel.style.color = '#1a202c';
        nameLabel.style.lineHeight = '1.2';
        nameLabel.style.textShadow = '0 1px 2px rgba(255,255,255,0.8)';
        nameLabel.style.whiteSpace = 'nowrap';
        nameLabel.style.overflow = 'hidden';
        nameLabel.style.textOverflow = 'ellipsis';
        nameLabel.style.width = '100%';
        labelContainer.appendChild(nameLabel);
        
        // Rating row (if available)
        if (poi.rating) {
            const ratingRow = document.createElement('div');
            ratingRow.style.display = 'flex';
            ratingRow.style.alignItems = 'center';
            ratingRow.style.gap = '3px';
            ratingRow.style.fontSize = '10px';
            
            // Star
            const starSpan = document.createElement('span');
            starSpan.textContent = '★';
            starSpan.style.color = '#FBBC05';
            starSpan.style.fontSize = '11px';
            ratingRow.appendChild(starSpan);
            
            // Rating value
            const ratingValue = document.createElement('span');
            ratingValue.textContent = poi.rating.value.toFixed(1);
            ratingValue.style.fontWeight = '500';
            ratingValue.style.color = '#374151';
            ratingValue.style.textShadow = '0 1px 2px rgba(255,255,255,0.8)';
            ratingRow.appendChild(ratingValue);
            
            labelContainer.appendChild(ratingRow);
        }
        
        // Walking time (if available)
        if (poi.walking) {
            const walkTimeLabel = document.createElement('div');
            walkTimeLabel.textContent = formatDuration(poi.walking.duration);
            walkTimeLabel.style.fontSize = '10px';
            walkTimeLabel.style.fontWeight = '500';
            walkTimeLabel.style.color = '#6B7280';
            walkTimeLabel.style.textShadow = '0 1px 2px rgba(255,255,255,0.8)';
            labelContainer.appendChild(walkTimeLabel);
        }
        
        // Append elements to wrapper
        wrapper.appendChild(circleContainer);
        wrapper.appendChild(labelContainer);

        // Store POI ID on wrapper
        wrapper.setAttribute('data-poi-id', poi.id);
        wrapper.setAttribute('data-chapter-id', chapterId);
        wrapper.setAttribute('data-marker-type', 'poi'); // Not the property marker

        // Hover effect - scale circle only, not wrapper
        // TASK 3: Show label on hover even at low zoom
        wrapper.addEventListener('mouseenter', function() {
            if (!wrapper.classList.contains('marker-active')) {
                circleContainer.style.transform = 'scale(1.15)';
                circleContainer.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
                wrapper.style.zIndex = '50'; // TASK 2: Hover POI z-index
                // Show label on hover regardless of zoom
                labelContainer.style.opacity = '1';
                labelContainer.style.visibility = 'visible';
                labelContainer.style.pointerEvents = 'auto';
                labelContainer.setAttribute('data-force-visible', 'true');
            }
        });
        
        wrapper.addEventListener('mouseleave', function() {
            if (!wrapper.classList.contains('marker-active')) {
                circleContainer.style.transform = 'scale(1)';
                circleContainer.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
                wrapper.style.zIndex = '10'; // TASK 2: Default POI z-index
                // Hide label if zoom < 15 and not force visible
                labelContainer.removeAttribute('data-force-visible');
                const currentZoom = mapInstance.getZoom();
                if (currentZoom < 15) {
                    labelContainer.style.opacity = '0';
                    labelContainer.style.visibility = 'hidden';
                    labelContainer.style.pointerEvents = 'none';
                }
            }
        });

        // Click handler
        wrapper.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Remove active state from all markers
            document.querySelectorAll('.tema-story-marker-wrapper[data-marker-type="poi"]').forEach(function(m) {
                m.classList.remove('marker-active');
                m.style.zIndex = '10'; // TASK 2: Default POI z-index
                const circle = m.querySelector('.marker-circle-container');
                if (circle) {
                    circle.style.transform = 'scale(1)';
                    circle.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
                }
                // Hide label if zoom < 15 when deactivating
                const label = m.querySelector('.marker-label-poi');
                if (label && !label.hasAttribute('data-force-visible')) {
                    const currentZoom = mapInstance.getZoom();
                    if (currentZoom < 15) {
                        label.style.opacity = '0';
                        label.style.visibility = 'hidden';
                        label.style.pointerEvents = 'none';
                    }
                }
            });
            
            // Set this marker as active
            // TASK 3: Active marker shows label and gets high z-index
            wrapper.classList.add('marker-active');
            wrapper.style.zIndex = '100'; // TASK 2: Active POI z-index
            circleContainer.style.transform = 'scale(1.25)';
            circleContainer.style.boxShadow = '0 6px 16px rgba(0,0,0,0.4)';
            // Force label visible when active
            labelContainer.style.opacity = '1';
            labelContainer.style.visibility = 'visible';
            labelContainer.style.pointerEvents = 'auto';
            labelContainer.setAttribute('data-force-visible', 'true');

            // Fit bounds to show both start and POI
            if (startLocation) {
                const bounds = new mapboxgl.LngLatBounds();
                bounds.extend(startLocation);
                bounds.extend(poi.coords);

                mapInstance.fitBounds(bounds, {
                    padding: 120,
                    duration: 1200,
                    maxZoom: 16
                });
            } else {
                mapInstance.flyTo({
                    center: poi.coords,
                    zoom: 16,
                    duration: 1200
                });
            }

            // Draw route if available
            if (poi.walking && poi.walking.geometry) {
                setTimeout(function() {
                    drawRouteOnMap(mapInstance, poi.walking.geometry, poi.walking.duration);
                }, 400);
            }

            // Highlight card
            setTimeout(function() {
                highlightCardInColumn(poi.id);
            }, 800);
        });

        // Create Mapbox marker with center anchor (marker centers on coordinate)
        const marker = new mapboxgl.Marker({
            element: wrapper,
            anchor: 'center',
            offset: [0, -10] // Offset up slightly so label doesn't overlap coordinate
        })
            .setLngLat(poi.coords)
            .addTo(mapInstance);

        // Store marker reference
        markers.push(marker);
    }
    
    /**
     * Draw walking route on a specific map
     * @param {Object} mapInstance - The map instance
     * @param {Object} geometry - GeoJSON geometry from Directions API
     * @param {number} duration - Duration in seconds
     */
    function drawRouteOnMap(mapInstance, geometry, duration) {
        // First, clear ALL existing routes from this map
        clearRouteFromMap(mapInstance);
        
        // Use fixed IDs for this map instance
        const routeId = 'walking-route';
        const labelId = 'walking-route-label';

        // Add new route
        if (geometry) {
            mapInstance.addSource(routeId, {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: geometry
                }
            });

            mapInstance.addLayer({
                id: routeId,
                type: 'line',
                source: routeId,
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#76908D',
                    'line-width': 3,
                    'line-opacity': 0.9,
                    'line-dasharray': [0.001, 2]
                }
            });

            // Add duration label at midpoint (50% of route distance)
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

                // Create custom HTML marker for duration label with card design
                const durationEl = document.createElement('div');
                durationEl.className = 'duration-label-card';
                durationEl.style.backgroundColor = '#ffffff';
                durationEl.style.color = '#1a1a1a';
                durationEl.style.padding = '6px 10px';
                durationEl.style.borderRadius = '8px';
                durationEl.style.fontSize = '13px';
                durationEl.style.fontWeight = '600';
                durationEl.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
                durationEl.style.whiteSpace = 'nowrap';
                durationEl.style.pointerEvents = 'none';
                durationEl.textContent = formatDuration(duration);
                
                const durationMarker = new mapboxgl.Marker({
                    element: durationEl,
                    anchor: 'center'
                })
                    .setLngLat(midPoint)
                    .addTo(mapInstance);
                
                // Store marker reference for cleanup
                currentDurationMarkers.push(durationMarker);
            }
        }
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
                    'line-width': 3,
                    'line-opacity': 0.9,
                    'line-dasharray': [0.001, 2]  // Dotted line: round dots with spacing
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

                // Create custom HTML marker for duration label with card design
                const durationEl = document.createElement('div');
                durationEl.className = 'duration-label-card';
                durationEl.style.backgroundColor = '#ffffff';
                durationEl.style.color = '#1a1a1a';
                durationEl.style.padding = '6px 10px';
                durationEl.style.borderRadius = '8px';
                durationEl.style.fontSize = '13px';
                durationEl.style.fontWeight = '600';
                durationEl.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
                durationEl.style.whiteSpace = 'nowrap';
                durationEl.style.pointerEvents = 'none';
                durationEl.textContent = formatDuration(duration);
                
                const durationMarker = new mapboxgl.Marker({
                    element: durationEl,
                    anchor: 'center'
                })
                    .setLngLat(midPoint)
                    .addTo(map);
                
                // Store marker reference for cleanup
                currentDurationMarkers.push(durationMarker);
            }

            currentRoute = geometry;
        }
    }

    /**
     * Clear the route from the map
     */
    /**
     * Clear route from specific map
     * @param {mapboxgl.Map} mapInstance - The map to clear route from
     */
    function clearRouteFromMap(mapInstance) {
        if (!mapInstance) return;
        
        // Remove all duration markers
        currentDurationMarkers.forEach(marker => marker.remove());
        currentDurationMarkers = [];
        
        // Get all layers and sources on this map
        const style = mapInstance.getStyle();
        if (!style || !style.layers) return;
        
        // Find and remove all route-related layers (both old random IDs and new fixed IDs)
        style.layers.forEach(function(layer) {
            if (layer.id && (layer.id.startsWith('route-') || layer.id === 'walking-route' || layer.id === 'walking-route-label')) {
                if (mapInstance.getLayer(layer.id)) {
                    mapInstance.removeLayer(layer.id);
                }
            }
        });
        
        // Find and remove all route-related sources
        if (style.sources) {
            Object.keys(style.sources).forEach(function(sourceId) {
                if (sourceId.startsWith('route-') || sourceId === 'walking-route' || sourceId === 'walking-route-label') {
                    if (mapInstance.getSource(sourceId)) {
                        mapInstance.removeSource(sourceId);
                    }
                }
            });
        }
    }

    /**
     * Clear routes from all maps (legacy function name for compatibility)
     */
    function clearRoute() {
        // Clear routes from all chapter maps
        const chapterMaps = document.querySelectorAll('.chapter-map');
        chapterMaps.forEach(function(mapContainer) {
            const mapInstance = mapContainer._mapboxInstance;
            if (mapInstance) {
                clearRouteFromMap(mapInstance);
            }
        });
    }

    /**
     * Fetch nearby places from Google Places API
     * @param {string} chapterId - Chapter ID
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {string} category - Place category/type
     * @param {number} radius - Search radius in meters
     * @param {number} minRating - Minimum rating filter
     * @param {number} minReviews - Minimum reviews filter
     * @returns {Promise<Object>} API response
     */
    async function fetchNearbyPlaces(chapterId, lat, lng, category = 'restaurant', radius = 1500, minRating = 4.3, minReviews = 50, keyword = '', excludeTypes = ['lodging'], excludePlaceIds = []) {
        // Check if we already have results for this chapter
        if (placesApiResults.has(chapterId)) {
            return placesApiResults.get(chapterId);
        }
        
        try {
            // Get WordPress REST API root from the link tag
            const restApiRoot = document.querySelector('link[rel="https://api.w.org/"]')?.href || '/wp-json/';
            const url = new URL(restApiRoot + 'placy/v1/places/search', window.location.origin);
            url.searchParams.append('lat', lat);
            url.searchParams.append('lng', lng);
            url.searchParams.append('category', category);
            url.searchParams.append('radius', radius);
            url.searchParams.append('minRating', minRating);
            url.searchParams.append('minReviews', minReviews);
            
            // Add keyword if provided
            if (keyword && keyword.trim()) {
                url.searchParams.append('keyword', keyword.trim());
            }
            
            // Add exclude types as JSON
            if (excludeTypes && excludeTypes.length > 0) {
                url.searchParams.append('excludeTypes', JSON.stringify(excludeTypes));
            }
            
            // Add exclude place IDs as JSON
            if (excludePlaceIds && excludePlaceIds.length > 0) {
                url.searchParams.append('excludePlaceIds', JSON.stringify(excludePlaceIds));
            }
            
            const response = await fetch(url.toString());
            
            if (!response.ok) {
                throw new Error('API request failed: ' + response.status);
            }
            
            const data = await response.json();
            
            // Cache the results
            placesApiResults.set(chapterId, data);
            
            return data;
        } catch (error) {
            console.error('Error fetching nearby places:', error);
            return { success: false, count: 0, places: [] };
        }
    }
    
    /**
     * Get photo URL from Google Places
     * @param {string} photoReference - Photo reference from Places API
     * @param {number} maxWidth - Maximum width for photo
     * @returns {Promise<string>} Photo URL
     */
    async function getPlacePhotoUrl(photoReference, maxWidth = 400) {
        try {
            // Get WordPress REST API root from the link tag
            const restApiRoot = document.querySelector('link[rel="https://api.w.org/"]')?.href || '/wp-json/';
            const url = new URL(restApiRoot + 'placy/v1/places/photo/' + photoReference, window.location.origin);
            url.searchParams.append('maxwidth', maxWidth);
            
            const response = await fetch(url.toString());
            const data = await response.json();
            
            if (data.success && data.photoUrl) {
                return data.photoUrl;
            }
        } catch (error) {
            console.error('Error fetching place photo:', error);
        }
        
        return null;
    }
    
    /**
     * Add Google Places markers to map
     * @param {Object} mapInstance - Map instance
     * @param {string} chapterId - Chapter ID
     * @param {Array} places - Array of place objects from API
     */
    function addPlacesMarkersToMap(mapInstance, chapterId, places) {
        places.forEach(function(place) {
            // Create marker wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'tema-story-marker-wrapper places-api-marker';
            wrapper.style.display = 'flex';
            wrapper.style.flexDirection = 'column';
            wrapper.style.alignItems = 'center';
            wrapper.style.gap = '8px';
            wrapper.style.cursor = 'pointer';
            wrapper.style.zIndex = '5'; // Lower than POI markers
            wrapper.setAttribute('data-marker-type', 'places-api');
            wrapper.setAttribute('data-chapter-id', chapterId);
            wrapper.setAttribute('data-place-id', place.placeId);
            
            // Create circular marker (smaller and red)
            const initialSize = Math.max(24, getMarkerSize(mapInstance.getZoom()) * 0.6);
            const circleContainer = document.createElement('div');
            circleContainer.className = 'marker-circle-container';
            circleContainer.style.width = initialSize + 'px';
            circleContainer.style.height = initialSize + 'px';
            circleContainer.style.borderRadius = '50%';
            circleContainer.style.border = '2px solid white';
            circleContainer.style.backgroundColor = '#EF4444'; // Red color
            circleContainer.style.overflow = 'hidden';
            circleContainer.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
            circleContainer.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
            
            // Add simple icon
            circleContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:white;font-size:14px;">📍</div>';
            
            // Create label
            const labelContainer = document.createElement('div');
            labelContainer.className = 'marker-label-container marker-label-poi';
            labelContainer.style.display = 'flex';
            labelContainer.style.flexDirection = 'column';
            labelContainer.style.alignItems = 'center';
            labelContainer.style.gap = '2px';
            labelContainer.style.maxWidth = '140px';
            labelContainer.style.textAlign = 'center';
            labelContainer.style.transition = 'opacity 0.3s ease';
            
            const currentZoom = mapInstance.getZoom();
            if (currentZoom < 15) {
                labelContainer.style.opacity = '0';
                labelContainer.style.visibility = 'hidden';
                labelContainer.style.pointerEvents = 'none';
            }
            
            // Name label
            const nameLabel = document.createElement('div');
            nameLabel.className = 'marker-name-label';
            nameLabel.textContent = place.name;
            nameLabel.style.fontSize = '11px';
            nameLabel.style.fontWeight = '600';
            nameLabel.style.color = '#1a202c';
            nameLabel.style.lineHeight = '1.2';
            nameLabel.style.textShadow = '0 1px 2px rgba(255,255,255,0.8)';
            nameLabel.style.whiteSpace = 'nowrap';
            nameLabel.style.overflow = 'hidden';
            nameLabel.style.textOverflow = 'ellipsis';
            nameLabel.style.width = '100%';
            labelContainer.appendChild(nameLabel);
            
            // Rating
            if (place.rating) {
                const ratingRow = document.createElement('div');
                ratingRow.style.display = 'flex';
                ratingRow.style.alignItems = 'center';
                ratingRow.style.gap = '3px';
                ratingRow.style.fontSize = '10px';
                
                const starSpan = document.createElement('span');
                starSpan.textContent = '★';
                starSpan.style.color = '#FBBC05';
                starSpan.style.fontSize = '10px';
                ratingRow.appendChild(starSpan);
                
                const ratingValue = document.createElement('span');
                ratingValue.textContent = place.rating.toFixed(1);
                ratingValue.style.fontWeight = '500';
                ratingValue.style.color = '#374151';
                ratingValue.style.textShadow = '0 1px 2px rgba(255,255,255,0.8)';
                ratingRow.appendChild(ratingValue);
                
                labelContainer.appendChild(ratingRow);
            }
            
            // "Fra Google" badge
            const googleBadge = document.createElement('div');
            googleBadge.textContent = 'Google';
            googleBadge.style.fontSize = '9px';
            googleBadge.style.fontWeight = '500';
            googleBadge.style.color = '#9CA3AF';
            googleBadge.style.textShadow = '0 1px 2px rgba(255,255,255,0.8)';
            labelContainer.appendChild(googleBadge);
            
            wrapper.appendChild(circleContainer);
            wrapper.appendChild(labelContainer);
            
            // Hover effects
            wrapper.addEventListener('mouseenter', function() {
                circleContainer.style.transform = 'scale(1.15)';
                circleContainer.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
                wrapper.style.zIndex = '45';
                labelContainer.style.opacity = '1';
                labelContainer.style.visibility = 'visible';
                labelContainer.style.pointerEvents = 'auto';
                labelContainer.setAttribute('data-force-visible', 'true');
            });
            
            wrapper.addEventListener('mouseleave', function() {
                circleContainer.style.transform = 'scale(1)';
                circleContainer.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
                wrapper.style.zIndex = '5';
                labelContainer.removeAttribute('data-force-visible');
                const currentZoom = mapInstance.getZoom();
                if (currentZoom < 15) {
                    labelContainer.style.opacity = '0';
                    labelContainer.style.visibility = 'hidden';
                    labelContainer.style.pointerEvents = 'none';
                }
            });
            
            // Click handler - show popup with details
            wrapper.addEventListener('click', function(e) {
                e.stopPropagation();
                showPlacePopup(mapInstance, place, wrapper);
            });
            
            // Create Mapbox marker
            const lngLat = [place.coordinates.lng, place.coordinates.lat];
            const marker = new mapboxgl.Marker({
                element: wrapper,
                anchor: 'center',
                offset: [0, -10]
            })
                .setLngLat(lngLat)
                .addTo(mapInstance);
            
            placesMarkers.push(marker);
        });
    }
    
    /**
     * Show popup for a Google Place
     * @param {Object} mapInstance - Map instance
     * @param {Object} place - Place data
     * @param {HTMLElement} markerElement - Marker element
     */
    function showPlacePopup(mapInstance, place, markerElement) {
        // Create popup content
        const popupContent = document.createElement('div');
        popupContent.style.minWidth = '200px';
        popupContent.style.maxWidth = '300px';
        
        let html = '<div style="padding: 8px;">';
        html += '<h3 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600;">' + place.name + '</h3>';
        
        // Rating
        if (place.rating) {
            html += '<div style="display: flex; align-items: center; gap: 4px; margin-bottom: 6px; font-size: 12px;">';
            html += '<span style="color: #FBBC05;">★</span>';
            html += '<span>' + place.rating.toFixed(1) + '</span>';
            if (place.userRatingsTotal) {
                html += '<span style="color: #666;">(' + place.userRatingsTotal + ')</span>';
            }
            html += '</div>';
        }
        
        // Address
        if (place.vicinity) {
            html += '<p style="margin: 0 0 6px 0; font-size: 12px; color: #666;">' + place.vicinity + '</p>';
        }
        
        // Open now status
        if (place.openNow !== null) {
            const status = place.openNow ? 'Åpent nå' : 'Stengt';
            const color = place.openNow ? '#10B981' : '#EF4444';
            html += '<p style="margin: 0 0 6px 0; font-size: 11px; color: ' + color + '; font-weight: 500;">' + status + '</p>';
        }
        
        // Price level
        if (place.priceLevel !== null) {
            const priceSymbols = '€'.repeat(place.priceLevel);
            html += '<p style="margin: 0 0 8px 0; font-size: 12px; color: #666;">' + priceSymbols + '</p>';
        }
        
        // Google Maps link
        html += '<a href="https://www.google.com/maps/place/?q=place_id:' + place.placeId + '" ';
        html += 'target="_blank" rel="noopener" ';
        html += 'style="display: inline-block; margin-top: 8px; padding: 6px 12px; background: #4285F4; color: white; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: 500;">';
        html += 'Se på Google Maps →</a>';
        
        html += '</div>';
        
        popupContent.innerHTML = html;
        
        // Create popup
        const popup = new mapboxgl.Popup({
            offset: 25,
            closeButton: true,
            closeOnClick: false
        })
            .setLngLat([place.coordinates.lng, place.coordinates.lat])
            .setDOMContent(popupContent)
            .addTo(mapInstance);
    }
    
    /**
     * Remove all Google Places markers from map
     * @param {string} chapterId - Optional chapter ID to remove markers for specific chapter
     */
    function removePlacesMarkers(chapterId = null) {
        placesMarkers = placesMarkers.filter(function(marker) {
            const markerElement = marker.getElement();
            const markerChapterId = markerElement ? markerElement.getAttribute('data-chapter-id') : null;
            
            if (chapterId === null || markerChapterId === chapterId) {
                marker.remove();
                return false;
            }
            return true;
        });
    }
    
    /**
     * Add "Show All" button to chapter
     * @param {string} chapterId - Chapter ID
     * @param {number} count - Number of places available
     */
    function addShowAllButton(chapterId, categoryNorwegian) {
        const chapter = document.querySelector('.chapter[data-chapter-id="' + chapterId + '"]');
        if (!chapter) return;
        
        // Find the last POI list section in this chapter
        const poiListSections = chapter.querySelectorAll('.poi-list-block');
        if (poiListSections.length === 0) return;
        
        const lastSection = poiListSections[poiListSections.length - 1];
        
        // Check if button already exists
        if (lastSection.querySelector('.places-api-show-all-button')) return;
        
        // Create button container
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'places-api-button-container';
        buttonContainer.style.marginTop = '24px';
        buttonContainer.style.textAlign = 'center';
        
        // Create button
        const button = document.createElement('button');
        button.className = 'places-api-show-all-button';
        button.textContent = 'Se flere ' + categoryNorwegian + ' i området';
        button.setAttribute('data-category-norwegian', categoryNorwegian);
        button.style.padding = '12px 24px';
        button.style.backgroundColor = '#EF4444';
        button.style.color = 'white';
        button.style.border = 'none';
        button.style.borderRadius = '8px';
        button.style.fontSize = '14px';
        button.style.fontWeight = '600';
        button.style.cursor = 'pointer';
        button.style.transition = 'background-color 0.2s';
        
        button.addEventListener('mouseenter', function() {
            button.style.backgroundColor = '#DC2626';
        });
        
        button.addEventListener('mouseleave', function() {
            button.style.backgroundColor = '#EF4444';
        });
        
        button.addEventListener('click', function() {
            toggleApiResults(chapterId, button);
        });
        
        buttonContainer.appendChild(button);
        lastSection.appendChild(buttonContainer);
    }
    
    /**
     * Toggle showing API results for a chapter
     * @param {string} chapterId - Chapter ID
     * @param {HTMLElement} button - Button element
     */
    async function toggleApiResults(chapterId, button) {
        const isShowing = showingApiResults.get(chapterId);
        
        if (isShowing) {
            // Already showing - do nothing (button will be hidden)
            return;
        }
        
        // Show loading state with spinner animation
        const categoryNorwegian = button.getAttribute('data-category-norwegian') || 'steder';
        
        // Create spinner element
        const spinner = document.createElement('span');
        spinner.style.display = 'inline-block';
        spinner.style.width = '14px';
        spinner.style.height = '14px';
        spinner.style.border = '2px solid rgba(255, 255, 255, 0.3)';
        spinner.style.borderTopColor = '#fff';
        spinner.style.borderRadius = '50%';
        spinner.style.marginRight = '8px';
        spinner.style.animation = 'spin 0.8s linear infinite';
        
        // Add keyframes for spinner if not already added
        if (!document.getElementById('spinner-keyframes')) {
            const style = document.createElement('style');
            style.id = 'spinner-keyframes';
            style.textContent = `
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        button.innerHTML = '';
        button.appendChild(spinner);
        button.appendChild(document.createTextNode('Henter lignende ' + categoryNorwegian + ' fra Google...'));
        button.disabled = true;
        button.style.opacity = '0.9';
        
        // Fetch API data but don't display yet
        const apiData = await fetchApiData(chapterId);
        
        // Wait for minimum 2 seconds before displaying
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Now display the results after 2 seconds
        if (apiData && apiData.success && apiData.places.length > 0) {
            displayApiResults(chapterId, apiData);
        }
        
        showingApiResults.set(chapterId, true);
        
        // Hide button after results are shown
        button.style.display = 'none';
    }
    
    /**
     * Fetch API data for a chapter (without displaying)
     * @param {string} chapterId - Chapter ID
     * @returns {Promise<Object>} API data
     */
    async function fetchApiData(chapterId) {
        // Get chapter element to read configuration
        const chapter = document.querySelector('[data-chapter-id="' + chapterId + '"]');
        if (!chapter) return null;
        
        // Get chapter map
        const mapContainer = document.querySelector('.chapter-map[data-chapter-id="' + chapterId + '"]');
        if (!mapContainer) return null;
        
        const mapInstance = mapContainer._mapboxInstance;
        if (!mapInstance) return null;
        
        // Get center coordinates (use start location or map center)
        let lat, lng;
        if (startLocation) {
            lng = startLocation[0];
            lat = startLocation[1];
        } else {
            const center = mapInstance.getCenter();
            lat = center.lat;
            lng = center.lng;
        }
        
        // Read configuration from chapter data attributes
        const category = chapter.getAttribute('data-places-category') || 'restaurant';
        const radius = parseInt(chapter.getAttribute('data-places-radius')) || 1500;
        const minRating = parseFloat(chapter.getAttribute('data-places-min-rating')) || 4.3;
        const minReviews = parseInt(chapter.getAttribute('data-places-min-reviews')) || 50;
        const keyword = chapter.getAttribute('data-places-keyword') || '';
        
        // Get exclude types (JSON array)
        let excludeTypes = ['lodging']; // Default
        const excludeTypesAttr = chapter.getAttribute('data-places-exclude-types');
        if (excludeTypesAttr) {
            try {
                excludeTypes = JSON.parse(excludeTypesAttr);
            } catch (e) {
                console.warn('Failed to parse exclude types:', e);
            }
        }
        
        // Collect Google Place IDs from existing POIs in this chapter to exclude them
        const excludePlaceIds = [];
        const poiItems = chapter.querySelectorAll('[data-google-place-id]');
        poiItems.forEach(function(poiItem) {
            const placeId = poiItem.getAttribute('data-google-place-id');
            if (placeId && placeId.trim()) {
                excludePlaceIds.push(placeId.trim());
            }
        });
        
        // Fetch places
        const apiData = await fetchNearbyPlaces(chapterId, lat, lng, category, radius, minRating, minReviews, keyword, excludeTypes, excludePlaceIds);
        
        if (!apiData.success || apiData.places.length === 0) {
            console.warn('No places found for chapter:', chapterId);
            return null;
        }
        
        return apiData;
    }
    
    /**
     * Display API results for a chapter
     * @param {string} chapterId - Chapter ID
     * @param {Object} apiData - API data with places
     */
    function displayApiResults(chapterId, apiData) {
        // Get chapter map
        const mapContainer = document.querySelector('.chapter-map[data-chapter-id="' + chapterId + '"]');
        if (!mapContainer) return;
        
        const mapInstance = mapContainer._mapboxInstance;
        if (!mapInstance) return;
        
        // Add markers to map
        addPlacesMarkersToMap(mapInstance, chapterId, apiData.places);
        
        // Add list items to chapter
        addPlacesListItems(chapterId, apiData.places);
        
        // Adjust map bounds to include new markers
        adjustMapBounds(mapInstance, apiData.places);
    }
    
    /**
     * Hide API results for a chapter
     * @param {string} chapterId - Chapter ID
     */
    function hideApiResults(chapterId) {
        // Remove markers
        removePlacesMarkers(chapterId);
        
        // Remove list items
        removePlacesListItems(chapterId);
    }
    
    /**
     * Add Google Places list items to chapter
     * @param {string} chapterId - Chapter ID
     * @param {Array} places - Array of places
     */
    function addPlacesListItems(chapterId, places) {
        const chapter = document.querySelector('.chapter[data-chapter-id="' + chapterId + '"]');
        if (!chapter) return;
        
        const poiListSections = chapter.querySelectorAll('.poi-list-block');
        if (poiListSections.length === 0) return;
        
        const lastSection = poiListSections[poiListSections.length - 1];
        const listContainer = lastSection.querySelector('.flex.flex-col');
        if (!listContainer) return;
        
        // Read configuration for disclaimer
        const category = chapter.getAttribute('data-places-category') || 'restaurant';
        const keyword = chapter.getAttribute('data-places-keyword') || '';
        
        // Map category to Norwegian plural
        const categoryMap = {
            'restaurant': 'restauranter',
            'cafe': 'kafeer',
            'bar': 'barer',
            'bakery': 'bakerier',
            'meal_takeaway': 'takeaway-steder',
            'food': 'spisesteder'
        };
        const categoryNorwegian = categoryMap[category] || 'steder';
        
        // Create container for API results
        const apiContainer = document.createElement('div');
        apiContainer.className = 'places-api-results';
        apiContainer.style.marginTop = '24px';
        apiContainer.style.paddingTop = '0';
        
        // Add disclaimer header
        const disclaimerHeader = document.createElement('div');
        disclaimerHeader.className = 'google-places-disclaimer';
        disclaimerHeader.style.marginBottom = '16px';
        disclaimerHeader.style.padding = '12px 16px';
        disclaimerHeader.style.backgroundColor = '#F3F4F6';
        disclaimerHeader.style.borderLeft = '4px solid #9CA3AF';
        disclaimerHeader.style.borderRadius = '4px';
        
        let disclaimerText = '<p style="margin: 0; font-size: 14px; color: #4B5563; line-height: 1.5;">';
        disclaimerText += '<strong style="color: #1F2937;">Google-søk:</strong> ';
        
        if (keyword && keyword.trim()) {
            disclaimerText += 'Viser lignende <strong>' + categoryNorwegian + '</strong> tagget med <em>"' + keyword.trim() + '"</em>';
        } else {
            disclaimerText += 'Viser lignende <strong>' + categoryNorwegian + '</strong> i området';
        }
        
        disclaimerText += ' <span style="color: #6B7280;">— hentet fra Google Places</span>';
        disclaimerText += '</p>';
        
        disclaimerHeader.innerHTML = disclaimerText;
        apiContainer.appendChild(disclaimerHeader);
        
        // Add place cards
        places.forEach(function(place) {
            const card = createPlaceListCard(place);
            apiContainer.appendChild(card);
        });
        
        listContainer.appendChild(apiContainer);
    }
    
    /**
     * Create list card for a Google Place
     * @param {Object} place - Place data
     * @returns {HTMLElement} Card element
     */
    function createPlaceListCard(place) {
        const card = document.createElement('article');
        card.className = 'poi-list-card bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:border-gray-300';
        card.style.backgroundColor = '#F9FAFB'; // Light gray background
        card.setAttribute('data-place-id', place.placeId);
        card.setAttribute('data-marker-type', 'places-api');
        
        let html = '<div class="poi-card-content flex gap-4 p-4">';
        
        // Image placeholder or actual image
        html += '<div class="flex-shrink-0">';
        html += '<div class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400">';
        html += '<span style="font-size: 32px;">📍</span>';
        html += '</div>';
        html += '</div>';
        
        html += '<div class="flex-1 min-w-0 flex flex-col">';
        html += '<div class="flex items-start justify-between">';
        html += '<div class="flex flex-col mb-2">';
        
        // Title
        html += '<h3 class="text-lg font-semibold text-gray-900">' + place.name + '</h3>';
        
        // Google badge
        html += '<div class="flex items-center gap-2 mt-1 mb-2">';
        html += '<span class="inline-block px-2 py-1 text-xs font-medium text-gray-600 bg-gray-200 rounded">Fra Google</span>';
        html += '</div>';
        
        // Rating and status
        html += '<div class="flex items-center gap-4">';
        
        if (place.rating) {
            html += '<div class="poi-rating flex items-center gap-2">';
            html += '<span class="flex items-center gap-1 text-sm font-medium text-gray-900">';
            html += '<span class="text-yellow-500">★</span>';
            html += place.rating.toFixed(1);
            html += '</span>';
            if (place.userRatingsTotal) {
                html += '<span class="text-xs text-gray-500">(' + place.userRatingsTotal + ')</span>';
            }
            html += '</div>';
        }
        
        if (place.openNow !== null) {
            const statusText = place.openNow ? 'Åpent nå' : 'Stengt';
            const statusColor = place.openNow ? 'text-green-600' : 'text-red-600';
            html += '<span class="text-sm font-medium ' + statusColor + '">' + statusText + '</span>';
        }
        
        html += '</div>'; // End rating row
        html += '</div>'; // End left column
        
        // Button
        html += '<div class="flex-shrink-0">';
        html += '<a href="https://www.google.com/maps/place/?q=place_id:' + place.placeId + '" ';
        html += 'target="_blank" rel="noopener" ';
        html += 'class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg transition-colors duration-200 whitespace-nowrap inline-block">';
        html += 'Se på Google Maps';
        html += '</a>';
        html += '</div>';
        
        html += '</div>'; // End flex container
        
        // Description
        if (place.vicinity) {
            html += '<div class="text-sm text-gray-600 mt-2">' + place.vicinity + '</div>';
        }
        
        html += '</div>'; // End content column
        html += '</div>'; // End card content
        
        card.innerHTML = html;
        
        return card;
    }
    
    /**
     * Remove Google Places list items
     * @param {string} chapterId - Chapter ID
     */
    function removePlacesListItems(chapterId) {
        const chapter = document.querySelector('.chapter[data-chapter-id="' + chapterId + '"]');
        if (!chapter) return;
        
        const apiContainers = chapter.querySelectorAll('.places-api-results');
        apiContainers.forEach(function(container) {
            container.remove();
        });
    }
    
    /**
     * Adjust map bounds to include new places
     * @param {Object} mapInstance - Map instance
     * @param {Array} places - Array of places
     */
    function adjustMapBounds(mapInstance, places) {
        if (places.length === 0) return;
        
        const bounds = new mapboxgl.LngLatBounds();
        
        // Include start location
        if (startLocation) {
            bounds.extend(startLocation);
        }
        
        // Include existing POIs
        markers.forEach(function(marker) {
            const lngLat = marker.getLngLat();
            bounds.extend([lngLat.lng, lngLat.lat]);
        });
        
        // Include new places (only add a sample to avoid zooming out too much)
        const sampleSize = Math.min(5, places.length);
        for (let i = 0; i < sampleSize; i++) {
            bounds.extend([places[i].coordinates.lng, places[i].coordinates.lat]);
        }
        
        mapInstance.fitBounds(bounds, {
            padding: 100,
            duration: 1000,
            maxZoom: 14
        });
    }
    
    /**
     * Initialize Places API integration for all chapters
     */
    function initPlacesApiIntegration() {
        // For each chapter with a map, check if it should have a "Show All" button
        const chapters = document.querySelectorAll('.chapter[data-chapter-id]');
        
        chapters.forEach(async function(chapter) {
            const chapterId = chapter.getAttribute('data-chapter-id');
            
            // Check if this chapter has POIs (if it has POIs, offer to show more)
            const poiListSections = chapter.querySelectorAll('.poi-list-block');
            if (poiListSections.length === 0) return;
            
            // Get coordinates for search
            let lat, lng;
            if (startLocation) {
                lng = startLocation[0];
                lat = startLocation[1];
            } else {
                // Use first POI coordinates as fallback
                const firstPoi = chapter.querySelector('[data-poi-coords]');
                if (!firstPoi) return;
                
                try {
                    const coords = JSON.parse(firstPoi.getAttribute('data-poi-coords'));
                    lat = coords[0];
                    lng = coords[1];
                } catch (e) {
                    return;
                }
            }
            
            // Read Places API configuration from data attributes on chapter element
            const placesEnabledAttr = chapter.getAttribute('data-places-enabled');
            const placesEnabled = placesEnabledAttr !== 'false'; // Default to true if not set
            
            // Skip if Places API is explicitly disabled for this chapter
            if (!placesEnabled) return;
            
            // Get search parameters from data attributes or use defaults
            const category = chapter.getAttribute('data-places-category') || 'restaurant';
            const keyword = chapter.getAttribute('data-places-keyword') || '';
            const radius = parseInt(chapter.getAttribute('data-places-radius')) || 1500;
            const minRating = parseFloat(chapter.getAttribute('data-places-min-rating')) || 4.3;
            const minReviews = parseInt(chapter.getAttribute('data-places-min-reviews')) || 50;
            
            // Get exclude types (JSON array)
            let excludeTypes = ['lodging']; // Default
            const excludeTypesAttr = chapter.getAttribute('data-places-exclude-types');
            if (excludeTypesAttr) {
                try {
                    excludeTypes = JSON.parse(excludeTypesAttr);
                } catch (e) {
                    console.warn('Failed to parse exclude types:', e);
                }
            }
            
            // Map category to Norwegian plural for button text
            const categoryMap = {
                'restaurant': 'restauranter',
                'cafe': 'kafeer',
                'bar': 'barer',
                'bakery': 'bakerier',
                'meal_takeaway': 'takeaway-steder',
                'food': 'spisesteder'
            };
            const categoryNorwegian = categoryMap[category] || 'steder';
            
            // Add button to show results (without fetching data yet)
            addShowAllButton(chapterId, categoryNorwegian);
        });
    }

    /**
     * Initialize multi-map system - one map per chapter
     */
    function initMap() {
        const chapterMaps = document.querySelectorAll('.chapter-map');
        
        if (chapterMaps.length === 0) {
            console.warn('Multi Map: No chapter maps found');
            return;
        }

        // Check if Mapbox token is available
        if (!placyMapConfig || !placyMapConfig.mapboxToken) {
            console.error('Multi Map: Mapbox token not found');
            return;
        }


        // Get start location first
        getStartLocation();

        mapboxgl.accessToken = placyMapConfig.mapboxToken;

        // Initialize each chapter map
        chapterMaps.forEach(function(mapContainer) {
            const chapterId = mapContainer.getAttribute('data-chapter-id');
            
            if (!chapterId) {
                console.warn('Multi Map: Map missing data-chapter-id');
                return;
            }

            // Initialize Mapbox map for this chapter
            const chapterMap = new mapboxgl.Map({
                container: mapContainer.id,
                style: 'mapbox://styles/mapbox/light-v11',
                center: CONFIG.DEFAULT_CENTER,
                zoom: CONFIG.DEFAULT_ZOOM,
            });

            // Add navigation controls
            chapterMap.addControl(new mapboxgl.NavigationControl(), 'top-right');

            // Store map instance on container
            mapContainer._mapboxInstance = chapterMap;

            // Setup zoom listener for dynamic marker sizing
            setupZoomListener(chapterMap);

            // Wait for map to load
            chapterMap.on('load', function() {
                // Remove POI labels
                const layers = chapterMap.getStyle().layers;
                layers.forEach(function(layer) {
                    if (layer.id.includes('poi') || layer.id.includes('label')) {
                        chapterMap.setLayoutProperty(layer.id, 'visibility', 'none');
                    }
                });

                // Add property marker if start location exists
                if (startLocation) {
                    addPropertyMarkerToMap(chapterMap);
                }

                // Parse and add markers for THIS chapter only
                parseAndAddMarkersForChapter(chapterMap, chapterId);
                
                // Initialize dynamic POI lists for this chapter
                initDynamicPoiLists();
            });

            // Keep last one as fallback
            map = chapterMap;
            
        });

        // After all maps initialized, setup hover tracking
        setTimeout(function() {
            initPOIHoverTracking();
        }, 1000);
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
            let poiItems = chapter.querySelectorAll('.poi-list-item, .poi-list-card');
            
            // FALLBACK: If this chapter has no POIs, look for POIs between this chapter and the next
            if (poiItems.length === 0 && index < chapters.length - 1) {
                const nextChapter = chapters[index + 1];
                const allPoisOnPage = document.querySelectorAll('.poi-list-item, .poi-list-card');
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
                }
            }
            // FALLBACK: If this is the last chapter and has no POIs, grab all remaining POIs
            else if (poiItems.length === 0 && index === chapters.length - 1) {
                const allPoisOnPage = document.querySelectorAll('.poi-list-item, .poi-list-card');
                const poisAfterChapter = Array.from(allPoisOnPage).filter(function(poi) {
                    let inChapter = false;
                    chapters.forEach(function(ch) {
                        if (ch.contains(poi)) inChapter = true;
                    });
                    return !inChapter && poi.offsetTop > chapter.offsetTop;
                });
                
                poiItems = poisAfterChapter;
                if (poisAfterChapter.length > 0) {
                }
            }

            const pois = [];

            poiItems.forEach(function(item) {
                const poiId = item.getAttribute('data-poi-id');
                const coordsAttr = item.getAttribute('data-poi-coords');
                const imageUrl = item.getAttribute('data-poi-image');
                const title = item.getAttribute('data-poi-title') || 'Untitled POI';
                
                // Get rating data from DOM
                const ratingEl = item.querySelector('.poi-rating-value');
                const ratingCountEl = item.querySelector('.poi-rating-count');
                let rating = null;
                
                if (ratingEl) {
                    const ratingText = ratingEl.textContent.replace(/\s+/g, ' ').trim();
                    const ratingMatch = ratingText.match(/(\d+\.?\d*)/);
                    if (ratingMatch) {
                        rating = {
                            value: parseFloat(ratingMatch[1]),
                            count: ratingCountEl ? ratingCountEl.textContent.trim() : null
                        };
                    }
                }

                if (coordsAttr) {
                    try {
                        const coords = JSON.parse(coordsAttr);
                        if (Array.isArray(coords) && coords.length === 2) {
                            pois.push({
                                id: poiId,
                                coords: [parseFloat(coords[1]), parseFloat(coords[0])], // [lng, lat] for Mapbox
                                title: title.trim(),
                                image: imageUrl || null,
                                rating: rating,
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
            }
        });

        
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
        const poiItem = button.closest('.poi-list-item, .poi-list-card');
        if (!poiItem) return;

        const coordsAttr = poiItem.getAttribute('data-poi-coords');
        if (!coordsAttr) return;

        // Find which chapter this POI belongs to
        const chapterElement = button.closest('.chapter');
        if (!chapterElement) {
            console.warn('Could not find parent .chapter element for POI');
            return;
        }

        const chapterId = chapterElement.getAttribute('data-chapter-id');
        if (!chapterId) {
            console.warn('Chapter element missing data-chapter-id attribute');
            return;
        }

        // Find the map container by chapter ID (map is NOT inside .chapter, it's in a separate column)
        const mapContainer = document.querySelector('.chapter-map[data-chapter-id="' + chapterId + '"]');
        if (!mapContainer) {
            console.warn('Could not find map container for chapter:', chapterId);
            return;
        }

        // Get the Mapbox instance for this map
        const mapInstance = mapContainer._mapboxInstance;
        if (!mapInstance) {
            console.warn('Map not initialized for chapter:', chapterId);
            return;
        }

        try {
            const coords = JSON.parse(coordsAttr);
            if (Array.isArray(coords) && coords.length === 2) {
                const lngLat = [parseFloat(coords[1]), parseFloat(coords[0])]; // [lng, lat] for Mapbox
                
                // Get POI ID for highlighting
                const poiId = poiItem.getAttribute('data-poi-id');
                
                // Fit bounds to show both start location and POI
                if (startLocation) {
                    const bounds = new mapboxgl.LngLatBounds();
                    bounds.extend(startLocation);
                    bounds.extend(lngLat);
                    
                    mapInstance.fitBounds(bounds, {
                        padding: 120,
                        duration: 1200,
                        maxZoom: 16
                    });
                } else {
                    // Fallback: just fly to POI if no start location
                    mapInstance.flyTo({
                        center: lngLat,
                        zoom: 16,
                        duration: 1200,
                        essential: true
                    });
                }

                // Scroll this specific map into view on mobile
                const mapColumn = mapContainer.closest('.chapter-map-column');
                if (mapColumn && window.innerWidth < 1024) {
                    mapColumn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }

                // Draw route on this map if we have start location
                if (startLocation) {
                    getWalkingDistance(lngLat).then(function(walking) {
                        if (walking && walking.geometry) {
                            setTimeout(function() {
                                drawRouteOnMap(mapInstance, walking.geometry, walking.duration);
                            }, 400);
                        }
                    });
                }
                
                // Highlight the card after map animation starts
                if (poiId) {
                    setTimeout(function() {
                        highlightCardInColumn(poiId);
                    }, 800);
                }
            }
        } catch (e) {
            console.warn('Error showing POI on map:', e);
        }
    };

    /**
     * Initialize Intersection Observer for scroll tracking
     * NOTE: With per-chapter maps, scroll tracking is not needed since each map
     * is independent and always shows its chapter's markers. This is kept for
     * potential future use but disabled.
     */
    function initScrollTracking() {
        // DISABLED: Not needed with per-chapter map architecture
        // Each chapter has its own map that shows its markers independently
        return;
        
        /* Original scroll tracking code preserved but disabled
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
        */
    }

    /**
     * Initialize hover tracking for individual POI items
     */
    function initPOIHoverTracking() {
        const poiItems = document.querySelectorAll('.poi-list-item, .poi-list-card');
        
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
        document.querySelectorAll('.poi-list-item, .poi-list-card').forEach(function(item) {
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
                const parentCard = button.closest('.poi-list-item, .poi-list-card');
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
            const targetCard = document.querySelector(`.poi-list-item[data-poi-id="${poiId}"], .poi-list-card[data-poi-id="${poiId}"]`);
            
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
                
                // Highlight corresponding marker on map
                markers.forEach(function(marker) {
                    const wrapper = marker.getElement();
                    if (!wrapper) return;
                    
                    const markerPoiId = wrapper.getAttribute('data-poi-id');
                    if (markerPoiId === poiId) {
                        wrapper.style.zIndex = '1000';
                        
                        const markerLabel = wrapper.querySelector('.tema-story-marker-label');
                        if (markerLabel) {
                            markerLabel.style.backgroundColor = '#EFE9DE';
                            markerLabel.style.border = '1px solid #cbbda4';
                            markerLabel.style.boxShadow = '0 4px 16px rgba(203, 189, 164, 0.3)';
                            markerLabel.style.fontWeight = '700';
                        }
                    }
                });
                
                
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
        document.querySelectorAll('.poi-list-item, .poi-list-card').forEach(function(item) {
            item.classList.remove('poi-active-hover');
        });
        
        // Reset all markers
        resetAllMarkers();
    }

    /**
     * Reset all POI markers to default state (not property markers)
     * TASK 2: Use proper z-index values
     */
    function resetAllMarkers() {
        markers.forEach(function(marker) {
            const wrapper = marker.getElement();
            if (!wrapper) return;
            
            // Skip property markers - they should maintain their z-index (1000)
            if (wrapper.getAttribute('data-marker-type') === 'property') return;
            
            // Reset POI markers only
            wrapper.classList.remove('marker-active');
            wrapper.style.zIndex = '10'; // TASK 2: Default POI z-index
            
            const circle = wrapper.querySelector('.marker-circle-container');
            if (circle) {
                circle.style.transform = 'scale(1)';
                circle.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
            }
            
            // TASK 3: Reset label visibility based on zoom (unless force visible)
            const label = wrapper.querySelector('.marker-label-poi');
            if (label && !label.hasAttribute('data-force-visible')) {
                // Find the map instance to get current zoom
                const mapContainer = wrapper.closest('.chapter-map');
                if (mapContainer && mapContainer._mapboxInstance) {
                    const currentZoom = mapContainer._mapboxInstance.getZoom();
                    if (currentZoom < 15) {
                        label.style.opacity = '0';
                        label.style.visibility = 'hidden';
                        label.style.pointerEvents = 'none';
                    }
                }
            }
        });
    }

    /**
     * Highlight marker on map (hover effect)
     * TASK 2: Use proper z-index, TASK 3: Show label on hover
     * @param {string} poiId - The POI ID to highlight
     */
    function highlightMarkerOnMap(poiId) {
        // Reset all POI markers first (not property markers)
        resetAllMarkers();
        
        // Find and highlight the specific POI marker using direct POI ID matching
        markers.forEach(function(marker) {
            const wrapper = marker.getElement();
            if (!wrapper) return;
            
            // Skip property markers - they should always stay on top (z-index 1000)
            if (wrapper.getAttribute('data-marker-type') === 'property') return;
            
            // Get POI ID from data attribute stored on wrapper
            const markerPoiId = wrapper.getAttribute('data-poi-id');
            const isActive = markerPoiId === poiId;
            
            if (isActive) {
                // TASK 2: Increase z-index for layering (but below property marker)
                wrapper.style.zIndex = '50'; // Hover POI z-index
                
                const circle = wrapper.querySelector('.marker-circle-container');
                if (circle) {
                    circle.style.transform = 'scale(1.15)';
                    circle.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
                }
                
                // TASK 3: Show label on hover regardless of zoom
                const label = wrapper.querySelector('.marker-label-poi');
                if (label) {
                    label.style.opacity = '1';
                    label.style.visibility = 'visible';
                    label.style.pointerEvents = 'auto';
                    label.setAttribute('data-force-visible', 'true');
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
            label.style.maxWidth = '200px';
            
            // Image in label (if available)
            if (poi.image) {
                const labelImage = document.createElement('div');
                labelImage.style.width = '48px';
                labelImage.style.height = '48px';
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
            titleSpan.style.lineHeight = '1.2';
            titleSpan.style.wordBreak = 'break-word';
            textContainer.appendChild(titleSpan);
            
            // Rating (if available)
            if (poi.rating) {
                const ratingContainer = document.createElement('span');
                ratingContainer.style.display = 'flex';
                ratingContainer.style.alignItems = 'center';
                ratingContainer.style.gap = '4px';
                ratingContainer.style.fontSize = '12px';
                ratingContainer.style.whiteSpace = 'nowrap';
                
                // Star
                const starSpan = document.createElement('span');
                starSpan.textContent = '★';
                starSpan.style.color = '#FBBC05';
                starSpan.style.fontSize = '12px';
                ratingContainer.appendChild(starSpan);
                
                // Rating value
                const ratingValueSpan = document.createElement('span');
                ratingValueSpan.textContent = poi.rating.value.toFixed(1);
                ratingValueSpan.style.fontWeight = '500';
                ratingValueSpan.style.color = '#1a202c';
                ratingContainer.appendChild(ratingValueSpan);
                
                // Review count (if available)
                if (poi.rating.count) {
                    const countSpan = document.createElement('span');
                    countSpan.textContent = poi.rating.count;
                    countSpan.style.color = '#666';
                    countSpan.style.fontSize = '11px';
                    ratingContainer.appendChild(countSpan);
                }
                
                textContainer.appendChild(ratingContainer);
            }
            
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
     * @param {Object} mapInstance - The map instance
     * @param {Array} pois - Array of POI objects with coords
     */
    function fitMapToBounds(mapInstance, pois) {
        if (!pois || pois.length === 0) {
            return;
        }

        if (pois.length === 1) {
            // Single marker: center and zoom
            mapInstance.flyTo({
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

            // Include start location if available
            if (startLocation) {
                bounds.extend(startLocation);
            }

            mapInstance.fitBounds(bounds, {
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
     * Initialize dynamic POI lists (poi-list-dynamic blocks)
     * Each block can have its own Google Places API configuration
     */
    function initDynamicPoiLists() {
        const dynamicBlocks = document.querySelectorAll('.poi-list-dynamic-block');
        
        dynamicBlocks.forEach(function(block) {
            const placesEnabled = block.getAttribute('data-places-enabled') !== 'false';
            if (!placesEnabled) return;
            
            // Get chapter ID from parent
            const chapter = block.closest('[data-chapter-id]');
            if (!chapter) {
                console.warn('Dynamic POI block found outside chapter:', block);
                return;
            }
            const chapterId = chapter.getAttribute('data-chapter-id');
            
            // Get configuration from block attributes
            const category = block.getAttribute('data-places-category') || 'restaurant';
            const keyword = block.getAttribute('data-places-keyword') || '';
            
            // Map category to Norwegian plural
            const categoryMap = {
                'restaurant': 'restauranter',
                'cafe': 'kafeer',
                'bar': 'barer',
                'bakery': 'bakerier',
                'meal_takeaway': 'takeaway-steder',
                'food': 'spisesteder',
                'pharmacy': 'apotek',
                'dentist': 'tannleger',
                'doctor': 'leger',
                'hospital': 'sykehus',
                'physiotherapist': 'fysioterapeuter',
                'store': 'butikker',
                'supermarket': 'supermarkeder',
                'gym': 'treningssentre',
                'spa': 'spa-steder',
                'beauty_salon': 'skjønnhetssalonger',
                'hair_care': 'frisører',
                'museum': 'museer',
                'art_gallery': 'kunstgallerier',
                'performing_arts_theater': 'teatre',
                'movie_theater': 'kinoer',
                'park': 'parker',
                'tourist_attraction': 'turistattraksjoner'
            };
            const categoryNorwegian = categoryMap[category] || 'steder';
            
            // Add button to this specific block
            addDynamicBlockButton(block, chapterId, categoryNorwegian);
        });
    }

    /**
     * Add "Show more" button to a dynamic POI block
     * @param {HTMLElement} block - The poi-list-dynamic block element
     * @param {string} chapterId - Chapter ID for map integration
     * @param {string} categoryNorwegian - Norwegian category name for button text
     */
    function addDynamicBlockButton(block, chapterId, categoryNorwegian) {
        const placeholder = block.querySelector('.poi-list-dynamic-placeholder');
        if (!placeholder) return;
        
        // Check if button already exists
        if (placeholder.querySelector('.places-api-show-all-button')) return;
        
        // Create button container
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'places-api-button-container';
        buttonContainer.style.marginTop = '24px';
        buttonContainer.style.textAlign = 'center';
        
        // Create button
        const button = document.createElement('button');
        button.className = 'places-api-show-all-button';
        button.textContent = 'Se flere ' + categoryNorwegian + ' i området';
        button.setAttribute('data-category-norwegian', categoryNorwegian);
        button.style.padding = '12px 24px';
        button.style.backgroundColor = '#EF4444';
        button.style.color = 'white';
        button.style.border = 'none';
        button.style.borderRadius = '8px';
        button.style.fontSize = '14px';
        button.style.fontWeight = '600';
        button.style.cursor = 'pointer';
        button.style.transition = 'background-color 0.2s';
        
        button.addEventListener('mouseenter', function() {
            button.style.backgroundColor = '#DC2626';
        });
        
        button.addEventListener('mouseleave', function() {
            button.style.backgroundColor = '#EF4444';
        });
        
        button.addEventListener('click', function() {
            toggleDynamicBlockResults(block, chapterId, button);
        });
        
        buttonContainer.appendChild(button);
        placeholder.appendChild(buttonContainer);
    }

    /**
     * Toggle showing API results for a dynamic POI block
     * @param {HTMLElement} block - The poi-list-dynamic block element
     * @param {string} chapterId - Chapter ID for map integration
     * @param {HTMLElement} button - Button element
     */
    async function toggleDynamicBlockResults(block, chapterId, button) {
        const isShowing = button.hasAttribute('data-results-shown');
        
        if (isShowing) {
            // Already showing - do nothing (button will be hidden)
            return;
        }
        
        // Show loading state with spinner animation
        const categoryNorwegian = button.getAttribute('data-category-norwegian') || 'steder';
        
        // Create spinner element
        const spinner = document.createElement('span');
        spinner.style.display = 'inline-block';
        spinner.style.width = '14px';
        spinner.style.height = '14px';
        spinner.style.border = '2px solid rgba(255, 255, 255, 0.3)';
        spinner.style.borderTopColor = '#fff';
        spinner.style.borderRadius = '50%';
        spinner.style.marginRight = '8px';
        spinner.style.animation = 'spin 0.8s linear infinite';
        
        // Add keyframes for spinner if not already added
        if (!document.getElementById('spinner-keyframes')) {
            const style = document.createElement('style');
            style.id = 'spinner-keyframes';
            style.textContent = `
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        button.innerHTML = '';
        button.appendChild(spinner);
        button.appendChild(document.createTextNode('Henter lignende ' + categoryNorwegian + ' fra Google...'));
        button.disabled = true;
        button.style.opacity = '0.9';
        
        // Fetch API data but don't display yet
        const apiData = await fetchDynamicBlockData(block, chapterId);
        
        // Wait for minimum 2 seconds before displaying
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Now display the results after 2 seconds
        if (apiData && apiData.success && apiData.places.length > 0) {
            displayDynamicBlockResults(block, chapterId, apiData);
        }
        
        button.setAttribute('data-results-shown', 'true');
        
        // Hide button after results are shown
        button.style.display = 'none';
    }

    /**
     * Fetch API data for a dynamic POI block (without displaying)
     * @param {HTMLElement} block - The poi-list-dynamic block element
     * @param {string} chapterId - Chapter ID
     * @returns {Promise<Object>} API data
     */
    async function fetchDynamicBlockData(block, chapterId) {
        // Get chapter element
        const chapter = document.querySelector('[data-chapter-id="' + chapterId + '"]');
        if (!chapter) return null;
        
        // Get chapter map
        const mapContainer = document.querySelector('.chapter-map[data-chapter-id="' + chapterId + '"]');
        if (!mapContainer) return null;
        
        const mapInstance = mapContainer._mapboxInstance;
        if (!mapInstance) return null;
        
        // Get center coordinates (use start location or map center)
        let lat, lng;
        if (startLocation) {
            lng = startLocation[0];
            lat = startLocation[1];
        } else {
            const center = mapInstance.getCenter();
            lat = center.lat;
            lng = center.lng;
        }
        
        // Read configuration from block data attributes
        const category = block.getAttribute('data-places-category') || 'restaurant';
        const radius = parseInt(block.getAttribute('data-places-radius')) || 1500;
        const minRating = parseFloat(block.getAttribute('data-places-min-rating')) || 4.3;
        const minReviews = parseInt(block.getAttribute('data-places-min-reviews')) || 50;
        const keyword = block.getAttribute('data-places-keyword') || '';
        
        // Get exclude types (JSON array)
        let excludeTypes = ['lodging']; // Default
        const excludeTypesAttr = block.getAttribute('data-places-exclude-types');
        if (excludeTypesAttr) {
            try {
                excludeTypes = JSON.parse(excludeTypesAttr);
            } catch (e) {
                console.warn('Failed to parse exclude types:', e);
            }
        }
        
        // Collect Google Place IDs from existing POIs in this chapter to exclude them
        const excludePlaceIds = [];
        const poiItems = chapter.querySelectorAll('[data-google-place-id]');
        poiItems.forEach(function(poiItem) {
            const placeId = poiItem.getAttribute('data-google-place-id');
            if (placeId && placeId.trim()) {
                excludePlaceIds.push(placeId.trim());
            }
        });
        
        // Generate unique cache key for this block
        const blockId = chapterId + '-' + category + '-' + keyword;
        
        // Fetch places
        const apiData = await fetchNearbyPlaces(blockId, lat, lng, category, radius, minRating, minReviews, keyword, excludeTypes, excludePlaceIds);
        
        if (!apiData.success || apiData.places.length === 0) {
            console.warn('No places found for dynamic block:', block);
            return null;
        }
        
        return apiData;
    }

    /**
     * Display API results for a dynamic POI block
     * @param {HTMLElement} block - The poi-list-dynamic block element
     * @param {string} chapterId - Chapter ID for map integration
     * @param {Object} apiData - API data with places
     */
    function displayDynamicBlockResults(block, chapterId, apiData) {
        // Get chapter map
        const mapContainer = document.querySelector('.chapter-map[data-chapter-id="' + chapterId + '"]');
        if (!mapContainer) return;
        
        const mapInstance = mapContainer._mapboxInstance;
        if (!mapInstance) return;
        
        // Get configuration for disclaimer
        const category = block.getAttribute('data-places-category') || 'restaurant';
        const keyword = block.getAttribute('data-places-keyword') || '';
        
        // Map category to Norwegian plural
        const categoryMap = {
            'restaurant': 'restauranter',
            'cafe': 'kafeer',
            'bar': 'barer',
            'bakery': 'bakerier',
            'meal_takeaway': 'takeaway-steder',
            'food': 'spisesteder',
            'pharmacy': 'apotek',
            'dentist': 'tannleger',
            'doctor': 'leger',
            'hospital': 'sykehus',
            'physiotherapist': 'fysioterapeuter',
            'store': 'butikker',
            'supermarket': 'supermarkeder',
            'gym': 'treningssentre',
            'spa': 'spa-steder',
            'beauty_salon': 'skjønnhetssalonger',
            'hair_care': 'frisører',
            'museum': 'museer',
            'art_gallery': 'kunstgallerier',
            'performing_arts_theater': 'teatre',
            'movie_theater': 'kinoer',
            'park': 'parker',
            'tourist_attraction': 'turistattraksjoner'
        };
        const categoryNorwegian = categoryMap[category] || 'steder';
        
        // Find placeholder in this block
        const placeholder = block.querySelector('.poi-list-dynamic-placeholder');
        if (!placeholder) return;
        
        // Create container for API results
        const apiContainer = document.createElement('div');
        apiContainer.className = 'places-api-results';
        apiContainer.style.marginTop = '24px';
        apiContainer.style.paddingTop = '0';
        
        // Add disclaimer header
        const disclaimerHeader = document.createElement('div');
        disclaimerHeader.className = 'google-places-disclaimer';
        disclaimerHeader.style.marginBottom = '16px';
        disclaimerHeader.style.padding = '12px 16px';
        disclaimerHeader.style.backgroundColor = '#F3F4F6';
        disclaimerHeader.style.borderLeft = '4px solid #9CA3AF';
        disclaimerHeader.style.borderRadius = '4px';
        
        let disclaimerText = '<p style="margin: 0; font-size: 14px; color: #4B5563; line-height: 1.5;">';
        disclaimerText += '<strong style="color: #1F2937;">Google-søk:</strong> ';
        
        if (keyword && keyword.trim()) {
            disclaimerText += 'Viser lignende <strong>' + categoryNorwegian + '</strong> tagget med <em>"' + keyword.trim() + '"</em>';
        } else {
            disclaimerText += 'Viser lignende <strong>' + categoryNorwegian + '</strong> i området';
        }
        
        disclaimerText += ' <span style="color: #6B7280;">— hentet fra Google Places</span>';
        disclaimerText += '</p>';
        
        disclaimerHeader.innerHTML = disclaimerText;
        apiContainer.appendChild(disclaimerHeader);
        
        // Add place cards
        apiData.places.forEach(function(place) {
            const card = createPlaceListCard(place);
            apiContainer.appendChild(card);
        });
        
        placeholder.appendChild(apiContainer);
        
        // Add markers to chapter map (shared map for all blocks in chapter)
        addPlacesMarkersToMap(mapInstance, chapterId, apiData.places);
        
        // Adjust map bounds to include new markers
        adjustMapBounds(mapInstance, apiData.places);
    }

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            // Initialize Places API integration after a short delay
            setTimeout(function() {
                initPlacesApiIntegration();
            }, 2000);
        });
    } else {
        initMap();
        // Initialize Places API integration after a short delay
        setTimeout(function() {
            initPlacesApiIntegration();
        }, 2000);
    }

})();
