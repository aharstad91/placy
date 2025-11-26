/**
 * POI Map Modal Functionality
 *
 * @package Placy
 * @since 1.0.0
 */

// Global storage for POI map data and Mapbox instances
window.placyPOIMaps = window.placyPOIMaps || {};
window.placyMapboxInstance = null;

/**
 * Initialize all POI map blocks on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find all POI map data blocks
    const mapDataElements = document.querySelectorAll('.poi-map-data');

    mapDataElements.forEach(function(element) {
        const data = JSON.parse(element.textContent);
        window.placyPOIMaps[data.blockId] = data;

        // Only initialize if there are points
        if (data.points && data.points.length > 0) {
            // Auto-initialize inline maps on desktop
            if (window.innerWidth >= 1024) {
                initializeMapInline(data.blockId, data, null);
            }
        }
    });

    // Set Mapbox access token
    if (typeof mapboxgl !== 'undefined' && typeof placyMapbox !== 'undefined') {
        mapboxgl.accessToken = placyMapbox.accessToken;
    }
});

/**
 * Open POI Map Modal or Initialize Inline (Desktop vs Mobile)
 *
 * @param {string} blockId - Unique block ID
 * @param {string} poiSlug - Optional Point slug to highlight
 */
function openPOIMapModal(blockId, poiSlug) {
    const mapData = window.placyPOIMaps[blockId];

    if (!mapData || !mapData.points || mapData.points.length === 0) {
        console.error('No Point data found for block:', blockId);
        return;
    }

    // Check if desktop (≥1024px)
    const isDesktop = window.innerWidth >= 1024;

    if (isDesktop) {
        // Desktop: Initialize inline map (but keep it blurred/inactive)
        initializeMapInline(blockId, mapData, poiSlug);
    } else {
        // Mobile: Open fullscreen modal
        openMapModal(mapData, poiSlug);
    }

}

/**
 * Initialize Map Inline (Desktop) - Show blurred map with CTA
 *
 * @param {string} blockId - Unique block ID
 * @param {Object} mapData - Map data with Points
 * @param {string} poiSlug - Optional Point slug to highlight
 */
function initializeMapInline(blockId, mapData, poiSlug) {
    // Find the block element
    const blockElement = document.querySelector(`[data-block-id="${blockId}"]`);
    if (!blockElement) {
        console.error('Block element not found:', blockId);
        return;
    }

    // Check if already initialized
    const existingContainer = blockElement.querySelector('.inline-map-container');
    if (existingContainer) {
        return; // Already initialized
    }

    // Add active class (shows the map container)
    blockElement.classList.add('map-active');

    // Get the preview element
    const previewElement = blockElement.querySelector('.poi-map-preview');
    if (!previewElement) {
        console.error('Preview element not found');
        return;
    }

    // Create inline map container
    const mapContainer = document.createElement('div');
    mapContainer.className = 'inline-map-container';
    mapContainer.innerHTML = `
        <div id="mapbox-container-inline" style="width: 100%; height: 100%;"></div>
        <div class="map-blur-overlay"></div>
        <div class="expansion-overlay"></div>
        <button class="map-cta-button" onclick="activateInlineMap('${blockId}')">
            <span class="map-cta-text">Klikk for å se ${mapData.points.length} punkt${mapData.points.length !== 1 ? 'er' : ''}</span>
        </button>
        <button class="map-close-button" onclick="closeInlineMap('${blockId}')" aria-label="Lukk kart">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    previewElement.parentNode.insertBefore(mapContainer, previewElement.nextSibling);

    // Initialize map immediately but keep it blurred/disabled
    setTimeout(function() {
        initializeMapboxMapInline(mapData, poiSlug, true); // true = keep markers hidden initially
    }, 50);
}

/**
 * Activate Inline Map (Remove blur, show markers, enable interaction)
 *
 * @param {string} blockId - Unique block ID
 */
function activateInlineMap(blockId) {
    const blockElement = document.querySelector(`[data-block-id="${blockId}"]`);
    if (!blockElement) return;

    const mapContainer = blockElement.querySelector('.inline-map-container');
    if (!mapContainer) return;

    // Show expansion overlay immediately to hide scaling artifacts
    const expansionOverlay = mapContainer.querySelector('.expansion-overlay');
    if (expansionOverlay) {
        expansionOverlay.classList.add('active');
    }

    // Activate the map (starts expansion animation)
    mapContainer.classList.add('map-activated');

    // Enable map interaction and resize after expansion completes
    if (window.placyMapboxInstance) {
        setTimeout(function() {
            // Resize map to fill expanded container
            window.placyMapboxInstance.resize();

            // Enable all map interactions
            window.placyMapboxInstance.scrollZoom.enable();
            window.placyMapboxInstance.boxZoom.enable();
            window.placyMapboxInstance.dragPan.enable();
            window.placyMapboxInstance.dragRotate.enable();
            window.placyMapboxInstance.keyboard.enable();
            window.placyMapboxInstance.doubleClickZoom.enable();
            window.placyMapboxInstance.touchZoomRotate.enable();

            // Hide expansion overlay after resize
            if (expansionOverlay) {
                setTimeout(function() {
                    expansionOverlay.classList.remove('active');
                }, 50);
            }
        }, 400); // Match the CSS transition duration
    }

    // Show markers after blur transition and expansion
    setTimeout(function() {
        const markers = mapContainer.querySelectorAll('.mapbox-poi-marker');
        markers.forEach(function(marker) {
            marker.style.opacity = '1';
        });
    }, 300);

}

/**
 * Close Inline Map (Desktop)
 *
 * @param {string} blockId - Unique block ID
 */
function closeInlineMap(blockId) {
    const blockElement = document.querySelector(`[data-block-id="${blockId}"]`);
    if (!blockElement) return;

    // Remove active class
    blockElement.classList.remove('map-active');

    // Destroy Mapbox instance
    if (window.placyMapboxInstance) {
        window.placyMapboxInstance.remove();
        window.placyMapboxInstance = null;
    }

}

/**
 * Open Map Modal (Mobile)
 *
 * @param {Object} mapData - Map data with Points
 * @param {string} poiSlug - Optional Point slug to highlight
 */
function openMapModal(mapData, poiSlug) {
    // Get or create modal
    let modal = document.getElementById('poi-map-modal');
    if (!modal) {
        modal = createPOIMapModal();
        document.body.appendChild(modal);
    }

    // Store current scroll position
    const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;

    // Clear previous content
    const mapCanvas = modal.querySelector('.poi-map-canvas');
    mapCanvas.innerHTML = '<div id="mapbox-container" style="width: 100%; height: 100%;"></div>';

    // Disable body scroll
    document.body.classList.add('modal-open');
    document.body.style.top = `-${scrollPosition}px`;

    // Show modal
    modal.classList.add('active');

    // Initialize Mapbox map after modal is visible
    setTimeout(function() {
        initializeMapboxMap(mapData, poiSlug, 'mapbox-container');
    }, 100);
}

/**
 * Initialize Mapbox Map (Generic)
 *
 * @param {Object} mapData - Map data with Points
 * @param {string} poiSlug - Optional Point slug to highlight
 * @param {string} containerId - Container element ID
 */
function initializeMapboxMap(mapData, poiSlug, containerId) {
    if (typeof mapboxgl === 'undefined') {
        console.error('Mapbox GL JS is not loaded');
        return;
    }

    // Destroy previous map instance if exists
    if (window.placyMapboxInstance) {
        window.placyMapboxInstance.remove();
        window.placyMapboxInstance = null;
    }

    // Calculate bounds to fit all Points
    const bounds = new mapboxgl.LngLatBounds();
    mapData.points.forEach(function(poi) {
        bounds.extend([poi.longitude, poi.latitude]);
    });

    // Create map
    const map = new mapboxgl.Map({
        container: containerId || 'mapbox-container',
        style: placyMapbox.style || 'mapbox://styles/mapbox/streets-v12',
        bounds: bounds,
        padding: { top: 80, bottom: 200, left: 50, right: 50 },
        fitBoundsOptions: {
            maxZoom: 14
        }
    });

    window.placyMapboxInstance = map;

    // Add navigation controls
    map.addControl(new mapboxgl.NavigationControl(), 'top-right');

    // Add Point markers after map loads
    map.on('load', function() {
        mapData.points.forEach(function(poi) {
            addMapboxMarker(map, poi);
        });

        // If specific Point is requested, show it
        if (poiSlug) {
            const poi = mapData.points.find(p => p.slug === poiSlug);
            if (poi) {
                setTimeout(function() {
                    showPOISheet(poi);
                }, 300);
            }
        }
    });
}

/**
 * Initialize Mapbox Map Inline (Desktop)
 *
 * @param {Object} mapData - Map data with Points
 * @param {string} poiSlug - Optional Point slug to highlight
 * @param {boolean} hideMarkers - Hide markers initially
 */
function initializeMapboxMapInline(mapData, poiSlug, hideMarkers) {
    if (typeof mapboxgl === 'undefined') {
        console.error('Mapbox GL JS is not loaded');
        return;
    }

    // Validate points exist
    if (!mapData.points || mapData.points.length === 0) {
        console.error('No points to display on map');
        return;
    }

    // Destroy previous map instance if exists
    if (window.placyMapboxInstance) {
        window.placyMapboxInstance.remove();
        window.placyMapboxInstance = null;
    }

    // Calculate center from points
    let centerLat = 0, centerLng = 0;
    mapData.points.forEach(function(poi) {
        centerLat += poi.latitude;
        centerLng += poi.longitude;
    });
    centerLat /= mapData.points.length;
    centerLng /= mapData.points.length;

    // Validate coordinates
    if (isNaN(centerLat) || isNaN(centerLng)) {
        console.error('Invalid coordinates calculated:', centerLat, centerLng);
        return;
    }

    // Create map centered on points
    const map = new mapboxgl.Map({
        container: 'mapbox-container-inline',
        style: placyMapbox.style || 'mapbox://styles/mapbox/streets-v12',
        center: [centerLng, centerLat],
        zoom: 13,
        interactive: true // Always interactive, but disable handlers if hideMarkers
    });

    // Disable interaction initially if hideMarkers is true
    if (hideMarkers) {
        map.scrollZoom.disable();
        map.boxZoom.disable();
        map.dragPan.disable();
        map.dragRotate.disable();
        map.keyboard.disable();
        map.doubleClickZoom.disable();
        map.touchZoomRotate.disable();
    }

    window.placyMapboxInstance = map;

    // Add navigation controls
    map.addControl(new mapboxgl.NavigationControl(), 'top-right');

    // Add Point markers after map loads
    map.on('load', function() {
        mapData.points.forEach(function(poi) {
            const markerEl = addMapboxMarker(map, poi);
            if (hideMarkers && markerEl) {
                markerEl.style.opacity = '0';
                markerEl.style.transition = 'opacity 0.3s ease';
            }
        });

        // If specific Point is requested, show it (only if not hiding markers)
        if (poiSlug && !hideMarkers) {
            const poi = mapData.points.find(p => p.slug === poiSlug);
            if (poi) {
                setTimeout(function() {
                    showPOISheet(poi);
                }, 300);
            }
        }
    });
}

/**
 * Add Mapbox Marker for POI
 *
 * @param {Object} map - Mapbox map instance
 * @param {Object} poi - POI data
 * @returns {HTMLElement} The marker element
 */
function addMapboxMarker(map, poi) {
    // Create custom marker element
    const el = document.createElement('div');
    el.className = 'mapbox-poi-marker';
    el.innerHTML = `
        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 0C7.31 0 3.5 3.81 3.5 8.5c0 6.13 7.33 14.46 8.02 15.2a1 1 0 001.46 0c.69-.74 8.02-9.07 8.02-15.2C20.5 3.81 16.69 0 12 0zm0 12a3.5 3.5 0 110-7 3.5 3.5 0 010 7z"/>
        </svg>
        <div class="mapbox-poi-label">${poi.title}</div>
    `;

    if (!poi.clickable) {
        el.classList.add('coming-soon');
    }

    // Create marker
    const marker = new mapboxgl.Marker(el)
        .setLngLat([poi.longitude, poi.latitude])
        .addTo(map);

    // Add click handler
    if (poi.clickable) {
        el.addEventListener('click', function(e) {
            e.stopPropagation();
            showPOISheet(poi);
        });
    } else {
        el.addEventListener('click', function(e) {
            e.stopPropagation();
            alert('Denne POI-en er ikke tilgjengelig ennå.');
        });
    }

    return el;
}

/**
 * Create POI Map Modal Structure
 */
function createPOIMapModal() {
    const modal = document.createElement('div');
    modal.id = 'poi-map-modal';
    modal.className = 'poi-map-modal';

    modal.innerHTML = `
        <div class="poi-modal-header">
            <button class="poi-modal-close" onclick="closePOIMapModal()">&times;</button>
        </div>
        
        <div class="poi-map-canvas">
            <!-- POI Markers will be inserted here -->
        </div>
        
        <div id="poi-sheet" class="poi-sheet closed" role="dialog" aria-labelledby="poiSheetTitle">
            <div class="poi-sheet-header">
                <h3 id="poiSheetTitle" class="poi-sheet-title">POI Title</h3>
                <button class="poi-sheet-close" onclick="hidePOISheet()">&times;</button>
            </div>
            <div class="poi-sheet-content">
                <div id="poiSheetBody">
                    <!-- POI content will be inserted here -->
                </div>
            </div>
        </div>
    `;

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closePOIMapModal();
        }
    });

    // Prevent clicks on map canvas from closing
    modal.querySelector('.poi-map-canvas').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Prevent clicks on POI sheet from closing
    modal.querySelector('#poi-sheet').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    return modal;
}

/**
 * Create POI Marker Element
 *
 * @param {Object} poi - POI data object
 * @returns {HTMLElement}
 */


/**
 * Show POI Bottom Sheet
 *
 * @param {Object} poi - POI data object
 */
function showPOISheet(poi) {
    const sheet = document.getElementById('poi-sheet');
    const title = document.getElementById('poiSheetTitle');
    const body = document.getElementById('poiSheetBody');

    if (!sheet || !title || !body) return;

    // Update content
    title.textContent = poi.title;

    body.innerHTML = `
        <div class="poi-sheet-location">
            <svg class="w-4 h-4 inline mr-1" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 0C7.31 0 3.5 3.81 3.5 8.5c0 6.13 7.33 14.46 8.02 15.2a1 1 0 001.46 0c.69-.74 8.02-9.07 8.02-15.2C20.5 3.81 16.69 0 12 0zm0 12a3.5 3.5 0 110-7 3.5 3.5 0 010 7z"/>
            </svg>
            ${poi.title}
        </div>
        ${poi.thumbnail ? `<img src="${poi.thumbnail}" alt="${poi.title}" class="poi-sheet-image">` : ''}
        <p class="poi-sheet-description">${poi.description}</p>
        <div class="poi-sheet-actions">
            <button class="poi-sheet-button primary" onclick="window.location.href='/poi/${poi.slug}'">
                Besøk
            </button>
            <button class="poi-sheet-button secondary" onclick="alert('Del funksjonalitet kommer')">
                Del
            </button>
            <button class="poi-sheet-button secondary" onclick="alert('Favoritt funksjonalitet kommer')">
                Favoritt
            </button>
        </div>
    `;

    // Show sheet
    sheet.classList.remove('closed');
    sheet.classList.add('peek');

    // Expand to full after a moment
    setTimeout(function() {
        sheet.classList.remove('peek');
        sheet.classList.add('open');
    }, 100);
}

/**
 * Hide POI Bottom Sheet
 */
function hidePOISheet() {
    const sheet = document.getElementById('poi-sheet');
    if (sheet) {
        sheet.classList.remove('open', 'peek');
        sheet.classList.add('closed');
    }
}

/**
 * Close POI Map Modal
 */
function closePOIMapModal() {
    const modal = document.getElementById('poi-map-modal');
    if (!modal) return;

    // Hide sheet first
    hidePOISheet();

    // Get stored scroll position
    const scrollPosition = Math.abs(parseInt(document.body.style.top || '0'));

    // Enable body scroll
    document.body.classList.remove('modal-open');
    document.body.style.top = '';

    // Restore scroll position
    window.scrollTo(0, scrollPosition);

    // Hide modal
    modal.classList.remove('active');

    // Destroy Mapbox instance
    if (window.placyMapboxInstance) {
        window.placyMapboxInstance.remove();
        window.placyMapboxInstance = null;
    }

}

/**
 * Keyboard handling
 */
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('poi-map-modal');
    if (modal && modal.classList.contains('active')) {
        if (e.key === 'Escape') {
            const sheet = document.getElementById('poi-sheet');
            if (sheet && !sheet.classList.contains('closed')) {
                hidePOISheet();
            } else {
                closePOIMapModal();
            }
        }
    }
});
