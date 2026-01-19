/**
 * Admin POI Map
 *
 * Displays all POIs from the library on a Mapbox map
 * with filtering by category and search functionality.
 *
 * @package Placy
 * @since 2.0.0
 */

(function() {
    'use strict';

    // State
    let mapInstance = null;
    let markers = [];
    let poisData = [];
    let categoriesData = [];
    let currentFilter = 'all';
    let currentSearch = '';

    // DOM Elements
    let mapContainer = null;
    let searchInput = null;
    let countDisplay = null;

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initAdminPOIMap();
    });

    /**
     * Initialize the admin POI map
     */
    function initAdminPOIMap() {
        mapContainer = document.getElementById('placy-poi-map-container');
        searchInput = document.getElementById('placy-poi-search');
        countDisplay = document.getElementById('placy-poi-count');

        if (!mapContainer) return;

        // Parse data from JSON
        const dataElement = document.getElementById('placy-poi-map-data');
        if (dataElement) {
            try {
                const data = JSON.parse(dataElement.textContent);
                poisData = data.pois || [];
                categoriesData = data.categories || [];

                // Set Mapbox token
                if (data.mapboxToken) {
                    mapboxgl.accessToken = data.mapboxToken;
                }

                initMap(data.center);
            } catch (e) {
                console.error('Failed to parse POI map data:', e);
            }
        }

        // Bind events
        bindEvents();
    }

    /**
     * Initialize Mapbox map
     */
    function initMap(center) {
        if (!mapContainer || typeof mapboxgl === 'undefined') {
            mapContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#666;">Map loading failed</div>';
            return;
        }

        mapInstance = new mapboxgl.Map({
            container: mapContainer,
            style: 'mapbox://styles/mapbox/streets-v12',
            center: center || [10.3951, 63.4305],
            zoom: 13,
            attributionControl: true
        });

        mapInstance.addControl(new mapboxgl.NavigationControl(), 'bottom-right');

        mapInstance.on('load', function() {
            // Hide Mapbox POI labels
            if (window.PlacyMapUtils && window.PlacyMapUtils.hideMapboxPOILayers) {
                window.PlacyMapUtils.hideMapboxPOILayers(mapInstance);
            }

            addMarkers();
            fitMapToBounds();
        });
    }

    /**
     * Add markers to map
     */
    function addMarkers() {
        if (!mapInstance) return;

        // Clear existing
        markers.forEach(m => m.marker.remove());
        markers = [];

        poisData.forEach(function(poi) {
            if (!poi.lat || !poi.lng) return;

            // Create marker element
            const el = document.createElement('div');
            el.className = 'placy-admin-marker';
            el.dataset.poiId = poi.id;
            el.dataset.category = poi.category;
            el.style.setProperty('--marker-color', poi.categoryColor || '#3B82F6');

            // Inner dot with icon
            el.innerHTML =
                '<div class="placy-admin-marker-dot">' +
                    '<i class="fa-solid ' + escapeHtml(poi.categoryIcon || 'fa-map-marker-alt') + '"></i>' +
                '</div>' +
                '<div class="placy-admin-marker-label">' + escapeHtml(poi.title) + '</div>';

            // Add hover events
            el.addEventListener('mouseenter', function() {
                el.classList.add('is-hover');
            });
            el.addEventListener('mouseleave', function() {
                el.classList.remove('is-hover');
            });

            // Add click handler
            el.addEventListener('click', function() {
                showMarkerPopup(poi, el);
            });

            const marker = new mapboxgl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([poi.lng, poi.lat])
                .addTo(mapInstance);

            markers.push({
                marker: marker,
                element: el,
                poi: poi
            });
        });
    }

    /**
     * Show popup when marker is clicked
     */
    function showMarkerPopup(poi, el) {
        // Remove any existing popups
        const existingPopups = document.querySelectorAll('.mapboxgl-popup');
        existingPopups.forEach(p => p.remove());

        const popup = new mapboxgl.Popup({
            offset: [0, -35],
            closeButton: true,
            className: 'placy-admin-popup'
        })
        .setLngLat([poi.lng, poi.lat])
        .setHTML(
            '<div class="placy-admin-popup-content">' +
                '<div class="placy-admin-popup-title">' + escapeHtml(poi.title) + '</div>' +
                '<div class="placy-admin-popup-meta">' +
                    '<span class="placy-admin-popup-type">' + (poi.type === 'native' ? 'Native' : 'Google') + '</span>' +
                    (poi.categoryName ? ' · <span class="placy-admin-popup-category">' + escapeHtml(poi.categoryName) + '</span>' : '') +
                '</div>' +
                '<div class="placy-admin-popup-coords">' + poi.lat.toFixed(5) + ', ' + poi.lng.toFixed(5) + '</div>' +
                '<a href="' + escapeHtml(poi.editUrl) + '" class="placy-admin-popup-edit" target="_blank">Edit POI →</a>' +
            '</div>'
        )
        .addTo(mapInstance);
    }

    /**
     * Fit map to show all visible markers
     */
    function fitMapToBounds() {
        if (!mapInstance) return;

        const visibleMarkers = markers.filter(m => m.element.style.display !== 'none');
        if (visibleMarkers.length === 0) return;

        const bounds = new mapboxgl.LngLatBounds();
        visibleMarkers.forEach(m => {
            bounds.extend([m.poi.lng, m.poi.lat]);
        });

        mapInstance.fitBounds(bounds, {
            padding: 50,
            maxZoom: 15
        });
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Filter chips
        document.querySelectorAll('.placy-filter-chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                setFilter(this.dataset.filter);
            });
        });

        // Search input
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function() {
                currentSearch = this.value.toLowerCase().trim();
                filterMarkers();
            }, 200));
        }
    }

    /**
     * Set category filter
     */
    function setFilter(filter) {
        currentFilter = filter;

        // Update active states
        document.querySelectorAll('.placy-filter-chip').forEach(function(chip) {
            chip.classList.toggle('active', chip.dataset.filter === filter);
        });

        filterMarkers();
    }

    /**
     * Filter markers based on category and search
     */
    function filterMarkers() {
        let visibleCount = 0;

        markers.forEach(function(m) {
            const matchesFilter = currentFilter === 'all' || m.poi.category === currentFilter;
            const matchesSearch = !currentSearch || m.poi.title.toLowerCase().includes(currentSearch);
            const isVisible = matchesFilter && matchesSearch;

            m.element.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        // Update count
        if (countDisplay) {
            countDisplay.textContent = 'Showing ' + visibleCount + ' POIs';
        }

        // Fit bounds to visible markers
        if (visibleCount > 0 && visibleCount < markers.length) {
            fitMapToBounds();
        }
    }

    /**
     * Debounce helper
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
