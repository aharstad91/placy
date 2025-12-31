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
        DEFAULT_ZOOM: 15,               // Default zoom - shows 3D buildings
        MIN_ZOOM: 15,                   // Minimum zoom level - keeps 3D buildings visible
        DEFAULT_CENTER: [10.3951, 63.4305], // Trondheim center as fallback
        // Marker size based on zoom level
        MARKER_SIZES: {
            SMALL: { size: 24, minZoom: 12, maxZoom: 14 },
            MEDIUM: { size: 48, minZoom: 15, maxZoom: 22 }
        },
        // Label collision detection - dynamic based on zoom
        LABEL_COLLISION_ZOOM_START: 15,  // Start applying collision detection (synced with marker size change)
        LABEL_COLLISION_ZOOM_END: 18,    // Full visibility at this zoom
        LABEL_MIN_DISTANCE: 140,         // Minimum pixel distance between labels
        LABEL_PRIORITY_DISTANCE: 100,    // Distance to check for nearby labels
        LABEL_MAX_VISIBLE: 8             // Max number of labels visible at once at low zoom
    };

    // State
    let map = null;
    let markers = [];
    const chapterData = new Map();
    let activeChapterId = null;
    const observer = null;
    let debounceTimer = null;
    let startLocation = null; // Property/start location from ACF fields
    const walkingDistances = new Map(); // Cache for walking distances
    const travelDistances = new Map(); // Cache for all travel modes: mode-lng,lat -> result
    let currentRoute = null; // Currently displayed route
    let currentDurationMarkers = []; // Store duration markers for cleanup
    let currentTravelMode = 'walk'; // Current travel mode: walk, bike, drive

    // Google Places API state
    const placesApiResults = new Map(); // Store API results per chapter

    // Progressive marker activation state
    const poiMarkerMap = new Map(); // Maps POI element -> marker wrapper element
    const chapterObservers = new Map(); // Store IntersectionObservers per chapter

    /**
     * Get photo URL from Google Places API
     * @param {string} photoReference - Photo reference from Places API (old or new format)
     * @param {number} maxWidth - Maximum width in pixels
     * @returns {string} Photo URL
     */
    function getPhotoUrl(photoReference, maxWidth) {
        if (!photoReference) {
            return null;
        }

        // Build photo URL using Places Photo API
        const apiKey = typeof placyMapConfig !== 'undefined' && placyMapConfig.googlePlacesApiKey ? placyMapConfig.googlePlacesApiKey : '';
        if (!apiKey) {
            console.warn('Google Places API key not configured');
            return null;
        }

        // Check if this is the new API format (starts with "places/")
        if (photoReference.startsWith('places/')) {
            // New Places API (New) format: use the resource name directly
            return `https://places.googleapis.com/v1/${photoReference}/media?maxWidthPx=${maxWidth}&key=${apiKey}`;
        } else {
            // Old API format: use photo_reference parameter
            const params = new URLSearchParams({
                maxwidth: maxWidth.toString(),
                photo_reference: photoReference,
                key: apiKey
            });

            return 'https://maps.googleapis.com/maps/api/place/photo?' + params.toString();
        }
    }
    let placesMarkers = []; // Store Google Places markers separately
    const showingApiResults = new Map(); // Track which chapters are showing API results

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
        const iconFontSize = (size >= 48) ? '18px' : '12px';
        console.log('Current zoom level:', zoom, '| Marker size:', size + 'px', '| Icon size:', iconFontSize);

        // Update all marker images (both old circular and new horizontal markers)
        const allMarkers = document.querySelectorAll('.marker-circle-container, .marker-image-container');
        allMarkers.forEach(function(imageEl) {
            // Only update if not currently scaled by hover/active
            const wrapper = imageEl.closest('.tema-story-marker-wrapper');
            const isActive = wrapper && wrapper.classList.contains('marker-active');
            const currentTransform = imageEl.style.transform;

            imageEl.style.width = size + 'px';
            imageEl.style.height = size + 'px';

            // Update Font Awesome icon size inside the marker
            const iconEl = imageEl.querySelector('i.fa-solid');
            if (iconEl) {
                iconEl.style.fontSize = iconFontSize;
            }

            // Preserve any scale transform
            if (currentTransform && currentTransform.includes('scale')) {
                // Keep existing scale
            } else {
                imageEl.style.transform = 'scale(1)';
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
        // Get all POI info cards (not Property labels)
        const poiLabels = document.querySelectorAll('.marker-info-card, .marker-label-poi');

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
        // Try both .chapter and .chapter-wrapper-acf selectors
        const chapter = document.querySelector(`.chapter[data-chapter-id="${chapterId}"], .chapter-wrapper-acf[data-chapter-id="${chapterId}"]`);
        if (!chapter) {
            console.warn(`Multi Map: Chapter ${chapterId} not found in DOM`);
            return;
        }

        const poiItems = chapter.querySelectorAll('.poi-list-item[data-poi-coords], .poi-list-card[data-poi-coords]');
        const pois = [];
        let markerIndex = 0;

        for (const item of poiItems) {
            // Skip POI cards that are inside hidden dynamic results containers
            const parentResults = item.closest('.poi-list-dynamic-results');
            if (parentResults && parentResults.style.display === 'none') {
                continue;
            }

            const coordsAttr = item.getAttribute('data-poi-coords');
            const poiId = item.getAttribute('data-poi-id');
            const title = item.getAttribute('data-poi-title');
            let image = item.getAttribute('data-poi-image');
            
            // Get category icon for marker (Font Awesome)
            const poiIcon = item.getAttribute('data-poi-icon') || 'fa-location-dot';
            const poiIconColor = item.getAttribute('data-poi-icon-color') || '#6366F1';

            // Check if this is a Google Point and get photo from photo reference
            const googlePlaceId = item.getAttribute('data-google-place-id');
            if (googlePlaceId && !image) {
                // Get photo reference directly from the item element
                const photoRef = item.getAttribute('data-google-photo-reference');
                if (photoRef) {
                    image = getPhotoUrl(photoRef, 200);
                }
            }

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
                        icon: poiIcon,
                        iconColor: poiIconColor,
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
     * Uses Font Awesome icon based on category
     */
    function addMarkerForPOI(mapInstance, poi, chapterId) {
        // Create wrapper container - horizontal layout like Google Maps
        const wrapper = document.createElement('div');
        wrapper.className = 'tema-story-marker-wrapper';
        wrapper.style.display = 'flex';
        wrapper.style.flexDirection = 'row';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '10px';
        wrapper.style.cursor = 'pointer';
        wrapper.style.zIndex = '10';
        wrapper.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
        wrapper.style.transition = 'filter 0.2s ease';

        // Create circular icon container with dynamic size
        const iconSize = getMarkerSize(mapInstance.getZoom());
        const iconContainer = document.createElement('div');
        iconContainer.className = 'marker-image-container marker-icon-container';
        iconContainer.style.width = iconSize + 'px';
        iconContainer.style.height = iconSize + 'px';
        iconContainer.style.borderRadius = '50%';
        iconContainer.style.overflow = 'hidden';
        iconContainer.style.border = '2px solid white';
        iconContainer.style.flexShrink = '0';
        iconContainer.style.transition = 'transform 0.2s ease, width 0.3s ease, height 0.3s ease';
        iconContainer.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
        iconContainer.style.display = 'flex';
        iconContainer.style.alignItems = 'center';
        iconContainer.style.justifyContent = 'center';
        iconContainer.style.backgroundColor = poi.iconColor || '#6366F1';

        // Create Font Awesome icon element
        const iconEl = document.createElement('i');
        const iconClass = poi.icon || 'fa-location-dot';
        iconEl.className = `fa-solid ${iconClass}`;
        iconEl.style.color = 'white';
        // Dynamic icon size: 18px for large markers (48px), 12px for small markers (24px)
        iconEl.style.fontSize = (iconSize >= 48) ? '18px' : '12px';
        iconEl.style.transition = 'font-size 0.3s ease';
        iconContainer.appendChild(iconEl);

        // Create info card (transparent background, two rows)
        const infoCard = document.createElement('div');
        infoCard.className = 'marker-info-card';
        infoCard.style.display = 'flex';
        infoCard.style.flexDirection = 'column';
        infoCard.style.minWidth = '0';
        infoCard.style.maxWidth = '140px';
        infoCard.style.transition = 'opacity 0.4s ease, visibility 0.4s ease, transform 0.3s ease';

        // Initial state based on zoom level
        const currentZoom = mapInstance.getZoom();
        if (currentZoom < 15) {
            infoCard.style.opacity = '0';
            infoCard.style.visibility = 'hidden';
            infoCard.style.pointerEvents = 'none';
        } else {
            infoCard.style.opacity = '1';
            infoCard.style.visibility = 'visible';
            infoCard.style.pointerEvents = 'auto';
        }

        // Row 1: POI name
        const nameRow = document.createElement('div');
        nameRow.className = 'marker-name-label';
        nameRow.textContent = poi.title;
        nameRow.style.fontSize = '13px';
        nameRow.style.fontWeight = '600';
        nameRow.style.color = '#1a202c';
        nameRow.style.lineHeight = '1.3';
        nameRow.style.whiteSpace = 'nowrap';
        nameRow.style.overflow = 'hidden';
        nameRow.style.textOverflow = 'ellipsis';
        nameRow.style.marginBottom = '2px';
        nameRow.style.textShadow = 'rgba(255, 255, 255, 0.9) 1px 1px 1px, rgba(255, 255, 255, 0.8) 0px 0px 8px';
        infoCard.appendChild(nameRow);

        // Row 2: Rating + Distance
        const metaRow = document.createElement('div');
        metaRow.style.display = 'flex';
        metaRow.style.alignItems = 'center';
        metaRow.style.gap = '10px';
        metaRow.style.fontSize = '11px';
        metaRow.style.color = '#6B7280';

        // Rating (if available)
        if (poi.rating) {
            const ratingSpan = document.createElement('span');
            ratingSpan.style.display = 'flex';
            ratingSpan.style.alignItems = 'center';
            ratingSpan.style.gap = '2px';

            const starSpan = document.createElement('span');
            starSpan.textContent = '★';
            starSpan.style.color = '#FBBC05';
            starSpan.style.fontSize = '12px';
            ratingSpan.appendChild(starSpan);

            const ratingValue = document.createElement('span');
            ratingValue.textContent = poi.rating.value.toFixed(1);
            ratingValue.style.fontWeight = '500';
            ratingValue.style.color = '#374151';
            ratingValue.style.textShadow = '0 1px 3px rgba(255,255,255,0.9), 0 0 8px rgba(255,255,255,0.8)';
            ratingSpan.appendChild(ratingValue);

            metaRow.appendChild(ratingSpan);
        }

        // Distance (if available)
        if (poi.walking) {
            const distanceSpan = document.createElement('span');
            distanceSpan.textContent = formatDuration(poi.walking.duration);
            distanceSpan.style.fontWeight = '500';
            distanceSpan.style.textShadow = '0 1px 3px rgba(255,255,255,0.9), 0 0 8px rgba(255,255,255,0.8)';
            metaRow.appendChild(distanceSpan);
        }

        infoCard.appendChild(metaRow);

        // Append icon and info card to wrapper
        wrapper.appendChild(iconContainer);
        wrapper.appendChild(infoCard);

        // Store POI ID on wrapper
        wrapper.setAttribute('data-poi-id', poi.id);
        wrapper.setAttribute('data-chapter-id', chapterId);
        wrapper.setAttribute('data-marker-type', 'poi');

        // Start in compact state for progressive activation
        wrapper.classList.add('marker-compact');

        // Store POI element to marker wrapper mapping for scroll activation
        if (poi.element) {
            poiMarkerMap.set(poi.element, wrapper);
        }

        // Hover effect - scale icon and show info card
        wrapper.addEventListener('mouseenter', function() {
            if (!wrapper.classList.contains('marker-active')) {
                iconContainer.style.transform = 'scale(1.1)';
                wrapper.style.zIndex = '50';
                wrapper.style.filter = 'drop-shadow(0 4px 8px rgba(0,0,0,0.4))';
                // Show info card on hover regardless of zoom
                infoCard.style.opacity = '1';
                infoCard.style.visibility = 'visible';
                infoCard.style.pointerEvents = 'auto';
                infoCard.setAttribute('data-force-visible', 'true');
            }
        });

        wrapper.addEventListener('mouseleave', function() {
            if (!wrapper.classList.contains('marker-active')) {
                iconContainer.style.transform = 'scale(1)';
                wrapper.style.zIndex = '10';
                wrapper.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
                // Hide info card if zoom < 15 and not force visible
                infoCard.removeAttribute('data-force-visible');
                const currentZoom = mapInstance.getZoom();
                if (currentZoom < 15) {
                    infoCard.style.opacity = '0';
                    infoCard.style.visibility = 'hidden';
                    infoCard.style.pointerEvents = 'none';
                }
            }
        });

        // Click handler
        wrapper.addEventListener('click', function(e) {
            e.stopPropagation();

            // Remove active state from all markers
            document.querySelectorAll('.tema-story-marker-wrapper[data-marker-type="poi"]').forEach(function(m) {
                m.classList.remove('marker-active');
                m.style.zIndex = '10';
                m.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
                const img = m.querySelector('.marker-image-container');
                if (img) {
                    img.style.transform = 'scale(1)';
                }
                // Reset image container style if present
                const imgContainer = m.querySelector('.marker-image-container');
                if (imgContainer) {
                    imgContainer.style.transform = 'scale(1)';
                }

                // Reset filter for new style markers
                if (m.querySelector('.marker-info-card')) {
                    m.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
                }

                // Hide info card if zoom < 15 when deactivating
                const info = m.querySelector('.marker-info-card');
                if (info && !info.hasAttribute('data-force-visible')) {
                    const currentZoom = mapInstance.getZoom();
                    if (currentZoom < 15) {
                        info.style.opacity = '0';
                        info.style.visibility = 'hidden';
                        info.style.pointerEvents = 'none';
                    }
                }
            });

            // Set this marker as active
            wrapper.classList.add('marker-active');
            wrapper.style.zIndex = '100';
            wrapper.style.filter = 'drop-shadow(0 6px 16px rgba(0,0,0,0.5))';
            iconContainer.style.transform = 'scale(1.15)';
            // Force info card visible when active
            infoCard.style.opacity = '1';
            infoCard.style.visibility = 'visible';
            infoCard.style.pointerEvents = 'auto';
            infoCard.setAttribute('data-force-visible', 'true');

            // Fit bounds to show both start and POI
            if (startLocation) {
                const bounds = new mapboxgl.LngLatBounds();
                bounds.extend(startLocation);
                bounds.extend(poi.coords);

                mapInstance.fitBounds(bounds, {
                    padding: 120,
                    duration: 1200,
                    maxZoom: 16,
                    pitch: 45,
                    bearing: -30
                });
            } else {
                mapInstance.flyTo({
                    center: poi.coords,
                    zoom: 16,
                    duration: 1200,
                    pitch: 45,
                    bearing: -30
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

        // Create Mapbox marker with left anchor (image left edge on coordinate)
        const marker = new mapboxgl.Marker({
            element: wrapper,
            anchor: 'left',
            offset: [0, -25] // Offset up to center vertically on coordinate
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
     * Fetch travel distance and time from start location to POI using Mapbox Directions API
     * @param {Array} destination - [lng, lat] coordinates of destination
     * @param {string} mode - Travel mode: 'walk', 'bike', or 'drive'
     * @returns {Promise<Object>} Object with distance (meters) and duration (seconds)
     */
    async function getTravelDistance(destination, mode = 'walk') {
        if (!startLocation) {
            return null;
        }

        // Map our modes to Mapbox profiles
        const modeProfiles = {
            walk: 'walking',
            bike: 'cycling',
            drive: 'driving'
        };
        const profile = modeProfiles[mode] || 'walking';

        // Check cache first
        const cacheKey = `${mode}-${destination[0]},${destination[1]}`;
        if (travelDistances.has(cacheKey)) {
            return travelDistances.get(cacheKey);
        }

        try {
            const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/${startLocation[0]},${startLocation[1]};${destination[0]},${destination[1]}?geometries=geojson&access_token=${mapboxgl.accessToken}`;

            const response = await fetch(url);
            const data = await response.json();

            if (data.routes && data.routes.length > 0) {
                const route = data.routes[0];
                const result = {
                    distance: route.distance, // meters
                    duration: route.duration, // seconds
                    geometry: route.geometry, // GeoJSON for route drawing
                    mode: mode
                };

                // Cache the result
                travelDistances.set(cacheKey, result);

                return result;
            }
        } catch (error) {
            console.error('Tema Story Map: Error fetching travel distance:', error);
        }

        return null;
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
     * Fetch nearby places from Google Points CPT via WordPress REST endpoint
     * Uses CPT query instead of live Google Places API for better performance and control
     * @param {string} chapterId - Chapter ID
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {string} category - Place category/type
     * @param {number} radius - Search radius in meters
     * @param {number} minRating - Minimum rating filter
     * @param {number} minReviews - Minimum reviews filter
     * @returns {Promise<Object>} API response
     */
    async function fetchNearbyPlaces(chapterId, lat, lng, category = 'restaurant', radius = 1500, minRating = 4.3, minReviews = 20, keyword = '', excludeTypes = ['lodging'], excludePlaceIds = []) {
        // Check if we already have results for this chapter
        if (placesApiResults.has(chapterId)) {
            return placesApiResults.get(chapterId);
        }

        try {
            // Get WordPress REST API root from the link tag
            const restApiRoot = document.querySelector('link[rel="https://api.w.org/"]')?.href || '/wp-json/';

            // Use new CPT-based endpoint instead of live API
            const url = new URL(restApiRoot + 'placy/v1/google-points/query', window.location.origin);
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

            console.log('[fetchNearbyPlaces] Querying Google Points CPT:', url.toString());

            const response = await fetch(url.toString());

            if (!response.ok) {
                throw new Error('API request failed: ' + response.status);
            }

            const data = await response.json();

            console.log('[fetchNearbyPlaces] CPT Query result:', {
                success: data.success,
                count: data.count,
                source: data.source
            });

            // Cache the results
            placesApiResults.set(chapterId, data);

            return data;
        } catch (error) {
            console.error('Error fetching nearby places from CPT:', error);
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
    /**
     * Add Google Places markers to map
     * Uses Font Awesome icon for consistency with curated POIs
     * @param {Object} mapInstance - Map instance
     * @param {string} chapterId - Chapter ID
     * @param {Array} places - Array of place objects from API
     */
    function addPlacesMarkersToMap(mapInstance, chapterId, places) {
        places.forEach(function(place) {
            // Create marker wrapper - horizontal layout
            const wrapper = document.createElement('div');
            wrapper.className = 'tema-story-marker-wrapper places-api-marker';
            wrapper.style.display = 'flex';
            wrapper.style.flexDirection = 'row';
            wrapper.style.alignItems = 'center';
            wrapper.style.gap = '10px';
            wrapper.style.cursor = 'pointer';
            wrapper.style.zIndex = '5';
            wrapper.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
            wrapper.style.transition = 'filter 0.2s ease';
            wrapper.setAttribute('data-marker-type', 'places-api');
            wrapper.setAttribute('data-chapter-id', chapterId);
            wrapper.setAttribute('data-place-id', place.placeId);

            // Create circular icon container with dynamic size (slightly smaller for Google Places)
            const iconSize = Math.max(30, getMarkerSize(mapInstance.getZoom()) * 0.85);
            const iconContainer = document.createElement('div');
            iconContainer.className = 'marker-image-container marker-icon-container';
            iconContainer.style.width = iconSize + 'px';
            iconContainer.style.height = iconSize + 'px';
            iconContainer.style.borderRadius = '50%';
            iconContainer.style.overflow = 'hidden';
            iconContainer.style.border = '2px solid white';
            iconContainer.style.flexShrink = '0';
            iconContainer.style.transition = 'transform 0.2s ease, width 0.3s ease, height 0.3s ease';
            iconContainer.style.backgroundColor = '#9CA3AF'; // Gray for Google Places (to distinguish from curated)
            iconContainer.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
            iconContainer.style.display = 'flex';
            iconContainer.style.alignItems = 'center';
            iconContainer.style.justifyContent = 'center';

            // Create Font Awesome icon element - use location-dot for Google Places
            const iconEl = document.createElement('i');
            iconEl.className = 'fa-solid fa-location-dot';
            iconEl.style.color = 'white';
            // Dynamic icon size: 18px for large markers (48px), 12px for small markers (24px)
            iconEl.style.fontSize = (iconSize >= 48) ? '18px' : '12px';
            iconEl.style.transition = 'font-size 0.3s ease';
            iconContainer.appendChild(iconEl);

            // Create info card
            const infoCard = document.createElement('div');
            infoCard.className = 'marker-info-card';
            infoCard.style.display = 'flex';
            infoCard.style.flexDirection = 'column';
            infoCard.style.minWidth = '0';
            infoCard.style.maxWidth = '140px';
            infoCard.style.transition = 'opacity 0.4s ease, visibility 0.4s ease, transform 0.3s ease';

            const currentZoom = mapInstance.getZoom();
            if (currentZoom < 15) {
                infoCard.style.opacity = '0';
                infoCard.style.visibility = 'hidden';
                infoCard.style.pointerEvents = 'none';
            }

            // Row 1: Name
            const nameRow = document.createElement('div');
            nameRow.className = 'marker-name-label';
            nameRow.textContent = place.name;
            nameRow.style.fontSize = '12px';
            nameRow.style.fontWeight = '600';
            nameRow.style.color = '#1a202c';
            nameRow.style.lineHeight = '1.3';
            nameRow.style.whiteSpace = 'nowrap';
            nameRow.style.overflow = 'hidden';
            nameRow.style.textOverflow = 'ellipsis';
            nameRow.style.marginBottom = '2px';
            nameRow.style.textShadow = 'rgba(255, 255, 255, 0.9) 1px 1px 1px, rgba(255, 255, 255, 0.8) 0px 0px 8px';
            infoCard.appendChild(nameRow);

            // Row 2: Rating + Google badge
            const metaRow = document.createElement('div');
            metaRow.style.display = 'flex';
            metaRow.style.alignItems = 'center';
            metaRow.style.gap = '10px';
            metaRow.style.fontSize = '10px';
            metaRow.style.color = '#6B7280';

            // Rating
            if (place.rating) {
                const ratingSpan = document.createElement('span');
                ratingSpan.style.display = 'flex';
                ratingSpan.style.alignItems = 'center';
                ratingSpan.style.gap = '2px';

                const starSpan = document.createElement('span');
                starSpan.textContent = '★';
                starSpan.style.color = '#FBBC05';
                starSpan.style.fontSize = '11px';
                ratingSpan.appendChild(starSpan);

                const ratingValue = document.createElement('span');
                ratingValue.textContent = place.rating.toFixed(1);
                ratingValue.style.fontWeight = '500';
                ratingValue.style.color = '#374151';
                ratingValue.style.textShadow = '0 1px 3px rgba(255,255,255,0.9), 0 0 8px rgba(255,255,255,0.8)';
                ratingSpan.appendChild(ratingValue);

                metaRow.appendChild(ratingSpan);
            }

            // "Google" badge
            const googleBadge = document.createElement('span');
            googleBadge.textContent = 'Google';
            googleBadge.style.fontWeight = '500';
            googleBadge.style.color = '#9CA3AF';
            googleBadge.style.textShadow = '0 1px 3px rgba(255,255,255,0.9), 0 0 8px rgba(255,255,255,0.8)';
            metaRow.appendChild(googleBadge);

            infoCard.appendChild(metaRow);

            wrapper.appendChild(iconContainer);
            wrapper.appendChild(infoCard);

            // Hover effects
            wrapper.addEventListener('mouseenter', function() {
                iconContainer.style.transform = 'scale(1.1)';
                wrapper.style.zIndex = '45';
                wrapper.style.filter = 'drop-shadow(0 4px 8px rgba(0,0,0,0.4))';
                infoCard.style.opacity = '1';
                infoCard.style.visibility = 'visible';
                infoCard.style.pointerEvents = 'auto';
                infoCard.setAttribute('data-force-visible', 'true');
            });

            wrapper.addEventListener('mouseleave', function() {
                iconContainer.style.transform = 'scale(1)';
                wrapper.style.zIndex = '5';
                wrapper.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
                infoCard.removeAttribute('data-force-visible');
                const currentZoom = mapInstance.getZoom();
                if (currentZoom < 15) {
                    infoCard.style.opacity = '0';
                    infoCard.style.visibility = 'hidden';
                    infoCard.style.pointerEvents = 'none';
                }
            });

            // Click handler - same behavior as WP POIs, scroll card into view
            wrapper.addEventListener('click', function(e) {
                e.stopPropagation();

                // Find corresponding card and scroll to it
                const card = document.querySelector('[data-place-id="' + place.placeId + '"]');
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                    // Highlight the card briefly
                    card.style.boxShadow = '0 0 0 3px #10B981';
                    setTimeout(function() {
                        card.style.boxShadow = '';
                    }, 2000);
                }
            });

            // Create Mapbox marker
            const lngLat = [place.coordinates.lng, place.coordinates.lat];
            const marker = new mapboxgl.Marker({
                element: wrapper,
                anchor: 'left',
                offset: [0, -22]
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

            // Dispatch event to notify proximity filter that new POIs are loaded
            const chapter = document.querySelector('[data-chapter-id="' + chapterId + '"]');
            if (chapter) {
                const event = new CustomEvent('placesLoaded', {
                    detail: {
                        chapterId: chapterId,
                        placesCount: apiData.places.length
                    },
                    bubbles: true
                });
                chapter.dispatchEvent(event);
                console.log('[Google Places] Dispatched placesLoaded event for chapter:', chapterId, 'places:', apiData.places.length);
            }
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

        // Collect Google Place IDs from existing POIs in the chapter-wrapper to exclude them
        const excludePlaceIds = [];
        const chapterWrapper = chapter.closest('.wp-block-placy-chapter-wrapper');
        if (chapterWrapper) {
            const poiItems = chapterWrapper.querySelectorAll('[data-google-place-id]');
            poiItems.forEach(function(poiItem) {
                const placeId = poiItem.getAttribute('data-google-place-id');
                if (placeId && placeId.trim()) {
                    excludePlaceIds.push(placeId.trim());
                }
            });
            console.log('[fetchApiData] Excluding ' + excludePlaceIds.length + ' manually curated POIs from API search');
        }

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

        // Re-initialize hover tracking for newly added cards
        setTimeout(function() {
            initPOIHoverTracking();
        }, 500);
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
        card.setAttribute('data-poi-id', 'gplace-' + place.placeId); // Unique ID for proximity filter
        card.setAttribute('data-google-place-id', place.placeId);
        card.setAttribute('data-marker-type', 'places-api');
        // Store coords as [lat, lng] to match POI format (proximity filter expects this)
        card.setAttribute('data-poi-coords', '[' + place.coordinates.lat + ',' + place.coordinates.lng + ']');

        let html = '<div class="poi-card-content flex gap-4 p-4">';

        // Image - use photo from API if available
        if (place.photoReference) {
            html += '<div class="flex-shrink-0">';
            const photoUrl = getPhotoUrl(place.photoReference, 200);
            html += '<img src="' + photoUrl + '" alt="' + place.name + '" class="w-24 h-24 rounded-lg object-cover">';
            html += '</div>';
        }

        html += '<div class="flex-1 min-w-0 flex flex-col">';
        html += '<div class="flex items-start justify-between">';

        // Left column: Title, badge, rating
        html += '<div class="flex flex-col mb-2">';
        html += '<h3 class="text-lg font-semibold text-gray-900">' + place.name + '</h3>';

        // Google badge
        html += '<div class="flex items-center gap-2 mt-1 mb-2">';
        html += '<span class="inline-block px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded">Fra Google</span>';
        html += '</div>';

        // Rating row (including travel time placeholder)
        html += '<div class="flex items-center gap-4">';
        if (place.rating) {
            html += '<div class="poi-rating flex items-center gap-2">';
            if (place.url) {
                html += '<a href="' + place.url + '" target="_blank" rel="noopener noreferrer" ';
                html += 'class="flex items-center gap-2 hover:opacity-80 transition-opacity" ';
                html += 'title="Se anmeldelser på Google" aria-label="Se anmeldelser på Google">';
                html += '<span class="flex items-center gap-1 text-sm font-medium text-gray-900">';
                html += '<span class="text-yellow-500">★</span>';
                html += place.rating.toFixed(1);
                html += '</span>';
                if (place.userRatingsTotal) {
                    html += '<span class="text-xs text-gray-500">(' + place.userRatingsTotal + ')</span>';
                }
                html += '<span class="flex items-center gap-1 text-xs text-gray-400">';
                html += '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">';
                html += '<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>';
                html += '<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>';
                html += '<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>';
                html += '<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>';
                html += '</svg>';
                html += '<span class="text-[10px]">Google</span>';
                html += '</span>';
                html += '</a>';
            } else {
                html += '<span class="flex items-center gap-1 text-sm font-medium text-gray-900">';
                html += '<span class="text-yellow-500">★</span>';
                html += place.rating.toFixed(1);
                html += '</span>';
                if (place.userRatingsTotal) {
                    html += '<span class="text-xs text-gray-500">(' + place.userRatingsTotal + ')</span>';
                }
            }
            html += '</div>';
        }

        // Add travel time placeholder (will be populated by proximity-filter.js)
        html += '<div class="poi-travel-time flex items-center gap-2 text-gray-600" style="display: none;">';
        html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        html += '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>';
        html += '</svg>';
        html += '<span class="poi-travel-time-text text-sm font-medium"></span>';
        html += '</div>';

        html += '</div>'; // End rating row
        html += '</div>'; // End left column

        // Right column: Button (aligned with title)
        html += '<div class="poi-button-container flex-shrink-0">';
        html += '<button onclick="showPlaceOnMap(this)" ';
        html += 'class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-lg transition-colors duration-200 whitespace-nowrap" ';
        html += 'data-place-coords="[' + place.coordinates.lng + ',' + place.coordinates.lat + ']">';
        html += 'Se på kart';
        html += '</button>';
        html += '</div>'; // End button container

        html += '</div>'; // End flex container (title row)

        // Description at bottom
        if (place.vicinity) {
            html += '<div class="text-sm text-gray-600 line-clamp-2">' + place.vicinity + '</div>';
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
            maxZoom: 14,
            pitch: 45,
            bearing: -30
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

            // Initialize Mapbox map for this chapter with 3D buildings
            const chapterMap = new mapboxgl.Map({
                container: mapContainer.id,
                style: 'mapbox://styles/mapbox/light-v11',
                center: CONFIG.DEFAULT_CENTER,
                zoom: CONFIG.DEFAULT_ZOOM,
                minZoom: CONFIG.MIN_ZOOM,
                maxZoom: 18,
                pitch: 45,           // Tilt angle for 3D view
                bearing: -30,        // Rotation angle
                antialias: true      // Smoother 3D rendering
            });

            // Add navigation controls
            chapterMap.addControl(new mapboxgl.NavigationControl({ showCompass: true }), 'top-right');

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

                // Add 3D building layer
                const labelLayerId = layers.find(
                    (layer) => layer.type === 'symbol' && layer.layout['text-field']
                );

                chapterMap.addLayer(
                    {
                        'id': '3d-buildings',
                        'source': 'composite',
                        'source-layer': 'building',
                        'filter': ['==', 'extrude', 'true'],
                        'type': 'fill-extrusion',
                        'minzoom': 14,
                        'paint': {
                            'fill-extrusion-color': '#aaa',
                            'fill-extrusion-height': [
                                'interpolate',
                                ['linear'],
                                ['zoom'],
                                14, 0,
                                14.5, ['get', 'height']
                            ],
                            'fill-extrusion-base': [
                                'interpolate',
                                ['linear'],
                                ['zoom'],
                                14, 0,
                                14.5, ['get', 'min_height']
                            ],
                            'fill-extrusion-opacity': 0.33
                        }
                    },
                    labelLayerId ? labelLayerId.id : undefined
                );

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

        // After all maps initialized, setup hover tracking and progressive activation
        setTimeout(function() {
            initPOIHoverTracking();
            initProgressiveMarkerActivation();
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
                        maxZoom: 16,
                        pitch: 45,
                        bearing: -30
                    });
                } else {
                    // Fallback: just fly to POI if no start location
                    mapInstance.flyTo({
                        center: lngLat,
                        zoom: 16,
                        duration: 1200,
                        pitch: 45,
                        bearing: -30,
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
                    getTravelDistance(lngLat, currentTravelMode).then(function(travelData) {
                        if (travelData && travelData.geometry) {
                            setTimeout(function() {
                                drawRouteOnMap(mapInstance, travelData.geometry, travelData.duration);
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
     * Show a Google Places API place on map (similar to showPOIOnMap)
     * @param {HTMLElement} button - The button element that was clicked
     */
    window.showPlaceOnMap = function(button) {
        const poiItem = button.closest('.poi-list-card');
        if (!poiItem) return;

        const coordsAttr = button.getAttribute('data-place-coords');
        if (!coordsAttr) return;

        // Find which chapter this place belongs to
        const chapterElement = button.closest('.chapter');
        if (!chapterElement) {
            console.warn('Could not find parent .chapter element for Place');
            return;
        }

        const chapterId = chapterElement.getAttribute('data-chapter-id');
        if (!chapterId) {
            console.warn('Chapter element missing data-chapter-id attribute');
            return;
        }

        // Find the map container by chapter ID
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
                const lngLat = [parseFloat(coords[0]), parseFloat(coords[1])]; // [lng, lat] for Mapbox

                // Get Place ID for marker activation
                const placeId = poiItem.getAttribute('data-place-id');

                // Fit bounds to show both start location and place
                if (startLocation) {
                    const bounds = new mapboxgl.LngLatBounds();
                    bounds.extend(startLocation);
                    bounds.extend(lngLat);

                    mapInstance.fitBounds(bounds, {
                        padding: 120,
                        duration: 1200,
                        maxZoom: 16,
                        pitch: 45,
                        bearing: -30
                    });
                } else {
                    // Fallback: just fly to place if no start location
                    mapInstance.flyTo({
                        center: lngLat,
                        zoom: 16,
                        duration: 1200,
                        pitch: 45,
                        bearing: -30,
                        essential: true
                    });
                }

                // Draw route on this map if we have start location
                if (startLocation) {
                    getTravelDistance(lngLat, currentTravelMode).then(function(travelData) {
                        if (travelData && travelData.geometry) {
                            setTimeout(function() {
                                drawRouteOnMap(mapInstance, travelData.geometry, travelData.duration);
                            }, 400);
                        }
                    });
                }

                // Activate the marker (highlight it)
                if (placeId) {
                    setTimeout(function() {
                        // Find the marker wrapper for this place
                        const markerWrapper = mapContainer.querySelector('.marker-wrapper-places[data-place-id="' + placeId + '"]');
                        if (markerWrapper) {
                            // Pulse animation
                            markerWrapper.style.animation = 'marker-pulse 1s ease-in-out 3';
                            setTimeout(function() {
                                markerWrapper.style.animation = '';
                            }, 3000);
                        }
                    }, 1200);
                }
            }
        } catch (e) {
            console.warn('Error showing Place on map:', e);
        }
    };

    /**
     * Initialize Progressive Marker Activation
     * Markers start in compact state and activate permanently when their
     * corresponding POI content scrolls into the center of the viewport.
     * Property markers are always fully visible as reference points.
     */
    function initProgressiveMarkerActivation() {
        const chapters = document.querySelectorAll('.chapter[data-chapter-id]');

        if (chapters.length === 0) {
            return;
        }

        // Observer options: trigger when POI enters center ~30% of viewport
        const observerOptions = {
            root: null, // viewport
            rootMargin: '-35% 0px -35% 0px', // Center 30% triggers activation
            threshold: 0.1 // 10% of element visible in center zone
        };

        chapters.forEach(function(chapter) {
            const chapterId = chapter.getAttribute('data-chapter-id');
            
            // Find all POI items in this chapter
            const poiItems = chapter.querySelectorAll('.poi-list-item[data-poi-coords], .poi-list-card[data-poi-coords]');
            
            if (poiItems.length === 0) {
                return;
            }

            // Create observer for this chapter
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        // POI element is in center zone - activate its marker
                        const poiElement = entry.target;
                        const markerWrapper = poiMarkerMap.get(poiElement);
                        
                        if (markerWrapper && markerWrapper.classList.contains('marker-compact')) {
                            // Activate marker permanently
                            markerWrapper.classList.remove('marker-compact');
                            markerWrapper.classList.add('marker-activated');
                            
                            // Unobserve since activation is permanent
                            observer.unobserve(poiElement);
                        }
                    }
                });
            }, observerOptions);

            // Observe all POI items in this chapter
            poiItems.forEach(function(item) {
                observer.observe(item);
            });

            // Store observer reference for cleanup if needed
            chapterObservers.set(chapterId, observer);
        });
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
        const placeId = poiElement.getAttribute('data-place-id');

        // Support both native POIs (data-poi-id) and Google Places (data-place-id)
        if (!poiId && !placeId) return;

        // First clear any existing hover effects to ensure only one label is visible
        clearActivePOI();

        // Add visual highlight to card
        poiElement.classList.add('poi-active-hover');

        // Highlight corresponding marker on map
        if (poiId) {
            highlightMarkerOnMap(poiId);
        } else if (placeId) {
            highlightPlacesMarkerOnMap(placeId);
        }
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

            // Reset old marker style (circular)
            const circle = wrapper.querySelector('.marker-circle-container');
            if (circle) {
                circle.style.transform = 'scale(1)';
                circle.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
            }

            // Reset new marker style (image)
            const imageContainer = wrapper.querySelector('.marker-image-container');
            if (imageContainer) {
                imageContainer.style.transform = 'scale(1)';
                wrapper.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
            }

            // TASK 3: Reset label visibility based on zoom
            // Support both native POI labels (.marker-label-poi) and Google Places labels (.marker-label-container)
            const label = wrapper.querySelector('.marker-label-poi, .marker-label-container');
            if (label) {
                // Remove force visible attribute first
                label.removeAttribute('data-force-visible');

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

            // Reset info card visibility based on zoom (new style markers)
            const infoCard = wrapper.querySelector('.marker-info-card');
            if (infoCard) {
                // Remove force visible attribute first
                infoCard.removeAttribute('data-force-visible');

                const mapContainer = wrapper.closest('.chapter-map');
                if (mapContainer && mapContainer._mapboxInstance) {
                    const currentZoom = mapContainer._mapboxInstance.getZoom();
                    if (currentZoom < 15) {
                        infoCard.style.opacity = '0';
                        infoCard.style.visibility = 'hidden';
                        infoCard.style.pointerEvents = 'none';
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

                // Handle old marker style (circular)
                const circle = wrapper.querySelector('.marker-circle-container');
                if (circle) {
                    circle.style.transform = 'scale(1.15)';
                    circle.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
                }

                // Handle new marker style (image)
                const imageContainer = wrapper.querySelector('.marker-image-container');
                if (imageContainer) {
                    imageContainer.style.transform = 'scale(1.1)';
                    wrapper.style.filter = 'drop-shadow(0 4px 8px rgba(0,0,0,0.4))';
                }

                // TASK 3: Show label on hover regardless of zoom (old style)
                const label = wrapper.querySelector('.marker-label-poi');
                if (label) {
                    label.style.opacity = '1';
                    label.style.visibility = 'visible';
                    label.style.pointerEvents = 'auto';
                    label.setAttribute('data-force-visible', 'true');
                }

                // Show info card on hover regardless of zoom (new style)
                const infoCard = wrapper.querySelector('.marker-info-card');
                if (infoCard) {
                    infoCard.style.opacity = '1';
                    infoCard.style.visibility = 'visible';
                    infoCard.style.pointerEvents = 'auto';
                    infoCard.setAttribute('data-force-visible', 'true');
                }
            }
        });
    }

    /**
     * Highlight Google Places marker on map (hover effect)
     * @param {string} placeId - The Google Places ID to highlight
     */
    function highlightPlacesMarkerOnMap(placeId) {
        // Reset all POI markers first (not property markers)
        resetAllMarkers();

        // Find and highlight the specific Google Places marker
        markers.forEach(function(marker) {
            const wrapper = marker.getElement();
            if (!wrapper) return;

            // Skip property markers
            if (wrapper.getAttribute('data-marker-type') === 'property') return;

            // Get place ID from data attribute
            const markerPlaceId = wrapper.getAttribute('data-place-id');
            const isActive = markerPlaceId === placeId;

            if (isActive) {
                // Increase z-index for layering
                wrapper.style.zIndex = '50';

                // Handle old marker style (circular)
                const circle = wrapper.querySelector('.marker-circle-container');
                if (circle) {
                    circle.style.transform = 'scale(1.15)';
                    circle.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
                }

                // Handle new marker style (image)
                const imageContainer = wrapper.querySelector('.marker-image-container');
                if (imageContainer) {
                    imageContainer.style.transform = 'scale(1.1)';
                    wrapper.style.filter = 'drop-shadow(0 4px 8px rgba(0,0,0,0.4))';
                }

                // Show label on hover regardless of zoom (old style)
                const label = wrapper.querySelector('.marker-label-container');
                if (label) {
                    label.style.opacity = '1';
                    label.style.visibility = 'visible';
                    label.style.pointerEvents = 'auto';
                    label.setAttribute('data-force-visible', 'true');
                }

                // Show info card on hover regardless of zoom (new style)
                const infoCard = wrapper.querySelector('.marker-info-card');
                if (infoCard) {
                    infoCard.style.opacity = '1';
                    infoCard.style.visibility = 'visible';
                    infoCard.style.pointerEvents = 'auto';
                    infoCard.setAttribute('data-force-visible', 'true');
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
                        maxZoom: 16,
                        pitch: 45,
                        bearing: -30
                    });
                } else {
                    // Fallback: just fly to POI if no start location
                    map.flyTo({
                        center: poi.coords,
                        zoom: 16,
                        duration: 1200,
                        pitch: 45,
                        bearing: -30,
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
                duration: 1000,
                pitch: 45,
                bearing: -30
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
            console.log('[Google Places] Successfully fetched ' + apiData.places.length + ' places');
            displayDynamicBlockResults(block, chapterId, apiData);

            // Only set results-shown and hide button if display was successful
            button.setAttribute('data-results-shown', 'true');
            button.style.display = 'none';
        } else {
            // No results found - reset button to original state
            console.warn('[Google Places] No places found, resetting button');
            button.innerHTML = '';
            button.textContent = 'Se flere ' + categoryNorwegian + ' i området';
            button.disabled = false;
            button.style.opacity = '1';
        }
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

        // Collect Google Place IDs from existing POIs in the chapter-wrapper to exclude them
        const excludePlaceIds = [];
        const chapterWrapper = chapter.closest('.wp-block-placy-chapter-wrapper');
        if (chapterWrapper) {
            const poiItems = chapterWrapper.querySelectorAll('[data-google-place-id]');
            poiItems.forEach(function(poiItem) {
                const placeId = poiItem.getAttribute('data-google-place-id');
                if (placeId && placeId.trim()) {
                    excludePlaceIds.push(placeId.trim());
                }
            });
            console.log('[fetchDynamicBlockData] Excluding ' + excludePlaceIds.length + ' manually curated POIs from API search');
        }

        // Generate unique cache key for this block
        const blockId = chapterId + '-' + category + '-' + keyword;

        // Fetch places
        console.log('[fetchDynamicBlockData] Fetching places:', { blockId, lat, lng, category, radius, minRating, minReviews, keyword });
        const apiData = await fetchNearbyPlaces(blockId, lat, lng, category, radius, minRating, minReviews, keyword, excludeTypes, excludePlaceIds);

        console.log('[fetchDynamicBlockData] API Response:', { success: apiData.success, count: apiData.places?.length || 0 });

        if (!apiData.success || apiData.places.length === 0) {
            console.warn('[fetchDynamicBlockData] No places found for dynamic block');
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
        console.log('[displayDynamicBlockResults] Starting display with ' + apiData.places.length + ' places');

        // Get chapter map
        const mapContainer = document.querySelector('.chapter-map[data-chapter-id="' + chapterId + '"]');
        if (!mapContainer) {
            console.error('[displayDynamicBlockResults] No map container found for chapter:', chapterId);
            return;
        }

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
     * Reveal serverside-rendered POI results with loading animation
     * @param {HTMLElement} button - The button element
     */
    async function revealServersideResults(button) {
        // Get the block container
        const block = button.closest('.poi-list-dynamic-block');
        if (!block) return;

        // Get the results container
        const resultsContainer = block.querySelector('.poi-list-dynamic-results');
        if (!resultsContainer) return;

        // Get category text from button
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

        // Show loading state
        button.innerHTML = '';
        button.appendChild(spinner);
        button.appendChild(document.createTextNode('Henter lignende ' + categoryNorwegian + ' fra Google...'));
        button.disabled = true;
        button.style.opacity = '0.9';

        // Wait for 2 seconds to simulate loading
        await new Promise(resolve => setTimeout(resolve, 2000));

        // Reveal the results
        resultsContainer.style.display = 'flex';

        // Find the chapter and map for this block
        const chapter = block.closest('.chapter');
        const chapterId = chapter ? chapter.getAttribute('data-chapter-id') : null;

        if (chapterId) {
            const mapContainer = document.querySelector(`.chapter-map[data-chapter-id="${chapterId}"]`);
            const mapInstance = mapContainer ? mapContainer._mapboxInstance : null;

            if (mapInstance) {
                // Add markers for the newly revealed POI cards from THIS block only
                const newPoiCards = resultsContainer.querySelectorAll('.poi-list-card[data-poi-coords]');

                // Filter to only POI cards that don't already have markers
                const existingMarkerIds = new Set();
                if (mapInstance._poiMarkers) {
                    mapInstance._poiMarkers.forEach(marker => {
                        const element = marker.getElement();
                        if (element) {
                            const poiId = element.getAttribute('data-poi-id');
                            if (poiId) existingMarkerIds.add(poiId);
                        }
                    });
                }

                for (const item of newPoiCards) {
                    const coordsAttr = item.getAttribute('data-poi-coords');
                    const poiId = item.getAttribute('data-poi-id');
                    const title = item.getAttribute('data-poi-title');

                    // Skip if marker already exists for this POI
                    if (existingMarkerIds.has(poiId)) {
                        continue;
                    }
                    let image = item.getAttribute('data-poi-image');

                    // Check if this is a Google Point and get photo from photo reference
                    const googlePlaceId = item.getAttribute('data-google-place-id');
                    if (googlePlaceId && !image) {
                        const photoRef = item.getAttribute('data-google-photo-reference');
                        if (photoRef) {
                            image = getPhotoUrl(photoRef, 200);
                        }
                    }

                    if (!coordsAttr) continue;

                    try {
                        const coords = JSON.parse(coordsAttr);
                        if (Array.isArray(coords) && coords.length === 2) {
                            const lngLat = [parseFloat(coords[1]), parseFloat(coords[0])];

                            // Get walking distance if start location exists
                            let walking = null;
                            if (startLocation) {
                                walking = await getWalkingDistance(lngLat);

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
                                walking: walking,
                                rating: rating
                            };

                            // Add marker to map
                            addMarkerForPOI(mapInstance, poi, chapterId);
                        }
                    } catch (e) {
                        console.warn('Error parsing revealed POI coords:', e);
                    }
                }
            }
        }

        // Hide the button
        const buttonContainer = button.closest('.places-api-button-container');
        if (buttonContainer) {
            buttonContainer.style.display = 'none';
        }

        // Re-initialize POI hover tracking for the newly revealed cards
        initPOIHoverTracking();
    }

    /**
     * Initialize serverside POI reveal buttons
     */
    function initServersidePoiButtons() {
        // Find all POI list dynamic blocks with serverside-rendered results
        const buttons = document.querySelectorAll('.poi-list-dynamic-block .places-api-show-all-button');

        buttons.forEach(function(button) {
            // Remove any existing event listeners by cloning
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            // Add hover effects
            newButton.addEventListener('mouseenter', function() {
                if (!newButton.disabled) {
                    newButton.style.backgroundColor = '#DC2626';
                }
            });

            newButton.addEventListener('mouseleave', function() {
                if (!newButton.disabled) {
                    newButton.style.backgroundColor = '#EF4444';
                }
            });

            // Add click handler
            newButton.addEventListener('click', function() {
                revealServersideResults(newButton);
            });
        });
    }

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            initServersidePoiButtons();
            initTravelModeListener();
            // Initialize Places API integration after a short delay
            setTimeout(function() {
                initPlacesApiIntegration();
            }, 2000);
        });
    } else {
        initMap();
        initServersidePoiButtons();
        initTravelModeListener();
        // Initialize Places API integration after a short delay
        setTimeout(function() {
            initPlacesApiIntegration();
        }, 2000);
    }

    /**
     * Listen for travel mode changes from sticky nav
     */
    function initTravelModeListener() {
        document.addEventListener('travelModeChanged', async function(event) {
            const newMode = event.detail.mode;
            console.log('[Map] Travel mode changed to:', newMode);
            currentTravelMode = newMode;

            // Find the currently active/hovered POI and update its route
            const activePOI = document.querySelector('.poi-list-item.active, .poi-list-item:hover');
            if (activePOI) {
                const coordsAttr = activePOI.dataset.poiCoords;
                if (coordsAttr) {
                    try {
                        const coords = JSON.parse(coordsAttr);
                        const destination = Array.isArray(coords) ? [coords[1], coords[0]] : [coords.lng, coords.lat];
                        
                        // Find the map for this POI
                        const chapter = activePOI.closest('.chapter');
                        if (chapter) {
                            const mapContainer = chapter.querySelector('.chapter-map');
                            if (mapContainer && mapContainer._mapboxInstance) {
                                const mapInstance = mapContainer._mapboxInstance;
                                
                                // Fetch new route with selected mode
                                const travelData = await getTravelDistance(destination, newMode);
                                if (travelData && travelData.geometry) {
                                    drawRouteOnMap(mapInstance, travelData.geometry, travelData.duration);
                                }
                            }
                        }
                    } catch (e) {
                        console.warn('[Map] Error updating route for mode change:', e);
                    }
                }
            }
        });
    }

})();
