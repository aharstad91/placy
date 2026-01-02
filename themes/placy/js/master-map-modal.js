/**
 * Master Map Modal
 * 
 * Handles the "Open full map" functionality showing all places
 * from all Story Chapters. Uses standardized map initialization
 * and syncs with PlacyGlobalState for travel mode/time budget.
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Ensure a sane default style is available if not localized by PHP
    if (typeof window !== 'undefined' && window.placyMapbox && !window.placyMapbox.style) {
        window.placyMapbox.style = 'mapbox://styles/mapbox/streets-v12';
    }

    // State
    let isOpen = false;
    let mapInstance = null;
    let markers = [];
    let placesData = [];
    let categoriesData = [];
    let projectLat = 63.4305;
    let projectLng = 10.3951;
    let currentFilter = 'all';
    let currentSearchQuery = '';
    let currentTravelMode = 'walk';
    let currentTimeBudget = 10;

    // DOM Elements
    let modal = null;
    let mapContainer = null;
    let placesList = null;
    let searchInput = null;

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initMasterMap();
    });

    /**
     * Initialize Master Map
     */
    function initMasterMap() {
        modal = document.getElementById('master-map-modal');
        if (!modal) return;

        // Parse data from JSON script tag
        const dataElement = document.getElementById('master-map-data');
        if (dataElement) {
            try {
                const data = JSON.parse(dataElement.textContent);
                placesData = data.places || [];
                categoriesData = data.categories || [];
                projectLat = data.projectLat || 63.4305;
                projectLng = data.projectLng || 10.3951;
                currentTravelMode = data.defaultTravelMode || 'walk';
                currentTimeBudget = data.defaultTimeBudget || 10;
            } catch (e) {
                console.error('[MasterMap] Failed to parse data:', e);
            }
        }

        // Cache DOM elements
        mapContainer = document.getElementById('master-map-container');
        placesList = modal.querySelector('.ns-master-map-locations');
        searchInput = modal.querySelector('.ns-search-input');

        // Sync with global state
        syncWithGlobalState();

        // Bind events
        bindEvents();

        console.log('[MasterMap] Initialized with', placesData.length, 'places');
    }

    /**
     * Sync with PlacyGlobalState
     */
    function syncWithGlobalState() {
        if (window.PlacyGlobalState) {
            if (window.PlacyGlobalState.travelMode) {
                currentTravelMode = window.PlacyGlobalState.travelMode;
            }
            if (window.PlacyGlobalState.timeBudget) {
                currentTimeBudget = window.PlacyGlobalState.timeBudget;
            }
        }
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Close button - use event delegation on modal for reliability
        modal.addEventListener('click', function(e) {
            // Check if clicked element is close button or inside it
            const closeBtn = e.target.closest('.ns-modal-close');
            if (closeBtn) {
                e.preventDefault();
                e.stopPropagation();
                closeMasterMap();
                return;
            }
            
            // Check if clicked on backdrop
            if (e.target.classList.contains('ns-modal-backdrop')) {
                closeMasterMap();
                return;
            }
        });

        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen) {
                closeMasterMap();
            }
        });

        // Filter chips
        const filterChips = modal.querySelectorAll('.ns-filter-chip');
        filterChips.forEach(chip => {
            chip.addEventListener('click', function() {
                setFilter(this.dataset.filter);
            });
        });

        // Travel mode buttons
        const travelBtns = modal.querySelectorAll('[data-travel-mode]');
        travelBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                setTravelMode(this.dataset.travelMode);
            });
        });

        // Time budget buttons
        const timeBtns = modal.querySelectorAll('[data-time-budget]');
        timeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                setTimeBudget(parseInt(this.dataset.timeBudget, 10));
            });
        });

        // Search input
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function() {
                currentSearchQuery = this.value.toLowerCase().trim();
                filterPlaces();
            }, 300));
        }

        // View in story buttons
        modal.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('[data-view-in-story]');
            if (viewBtn) {
                const chapterId = viewBtn.dataset.viewInStory;
                viewInStory(chapterId);
            }
        });

        // Listen for global state changes
        document.addEventListener('placy:travelModeChange', function(e) {
            if (e.detail && e.detail.mode && e.detail.source !== 'masterMap') {
                setTravelMode(e.detail.mode, true);
            }
        });

        document.addEventListener('placy:timeBudgetChange', function(e) {
            if (e.detail && e.detail.budget && e.detail.source !== 'masterMap') {
                setTimeBudget(e.detail.budget, true);
            }
        });
    }

    /**
     * Open Master Map modal
     */
    function openMasterMap() {
        if (!modal) return;

        // Sync with global state before opening
        syncWithGlobalState();

        isOpen = true;
        modal.classList.add('ns-modal-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        // Update toggle states to match current values
        updateToggleStates();

        // Initialize map if not already done
        setTimeout(() => {
            initMap();
            calculateTravelTimes();
            updateHighlighting();
        }, 100);

        console.log('[MasterMap] Opened with', placesData.length, 'places');
    }

    /**
     * Close Master Map modal
     */
    function closeMasterMap() {
        if (!modal) return;

        isOpen = false;
        modal.classList.remove('ns-modal-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        console.log('[MasterMap] Closed');
    }

    /**
     * Initialize Mapbox map with standardized settings
     */
    function initMap() {
        if (!mapContainer || mapInstance) return;
        if (typeof mapboxgl === 'undefined') {
            console.warn('[MasterMap] Mapbox GL not loaded');
            mapContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#737373;">Map loading...</div>';
            return;
        }

        // Standard map configuration (shared across all modals)
        // Allow overriding style via localized `window.placyMapbox.style` (set in PHP)
        var mapStyle = (window.placyMapbox && window.placyMapbox.style) ? window.placyMapbox.style : 'mapbox://styles/mapbox/light-v11';

        mapInstance = new mapboxgl.Map({
            container: mapContainer,
            style: mapStyle,
            center: [projectLng, projectLat],
            zoom: 14,
            attributionControl: true
        });

        mapInstance.addControl(new mapboxgl.NavigationControl(), 'bottom-right');

        mapInstance.on('load', () => {
            addMarkers();
            fitMapToBounds();
        });
    }

    /**
     * Add markers to map using standardized marker style
     */
    function addMarkers() {
        if (!mapInstance) return;

        // Clear existing markers
        markers.forEach(m => m.marker.remove());
        markers = [];

        placesData.forEach((place, index) => {
            if (!place.lat || !place.lng) return;

            // Create standardized marker element
            const el = document.createElement('div');
            el.className = 'ns-map-marker';
            el.dataset.placeId = place.id || index;
            el.dataset.category = place.category_id || '';

            // Create popup with standardized styling
            const popup = new mapboxgl.Popup({
                offset: 25,
                closeButton: false,
                className: 'ns-map-popup-container'
            }).setHTML(
                '<div class="ns-map-popup">' +
                    '<div class="ns-map-popup-name">' + escapeHtml(place.name) + '</div>' +
                    '<div class="ns-map-popup-category">' + escapeHtml(place.category) + '</div>' +
                '</div>'
            );

            const marker = new mapboxgl.Marker(el)
                .setLngLat([place.lng, place.lat])
                .setPopup(popup)
                .addTo(mapInstance);

            markers.push({
                marker: marker,
                element: el,
                place: place
            });
        });
    }

    /**
     * Fit map bounds to show all markers
     */
    function fitMapToBounds() {
        if (!mapInstance || markers.length === 0) return;

        const bounds = new mapboxgl.LngLatBounds();

        // Add project center
        bounds.extend([projectLng, projectLat]);

        // Add all markers
        markers.forEach(m => {
            if (m.place.lng && m.place.lat) {
                bounds.extend([m.place.lng, m.place.lat]);
            }
        });

        mapInstance.fitBounds(bounds, {
            padding: { top: 50, bottom: 50, left: 50, right: 50 },
            maxZoom: 15
        });
    }

    /**
     * Set category filter
     */
    function setFilter(filter) {
        currentFilter = filter;

        // Update filter chip active states
        modal.querySelectorAll('.ns-filter-chip').forEach(chip => {
            chip.classList.toggle('active', chip.dataset.filter === filter);
        });

        filterPlaces();
    }

    /**
     * Filter places based on category and search query
     */
    function filterPlaces() {
        const items = modal.querySelectorAll('.ns-location-item');
        let visibleCount = 0;

        items.forEach(item => {
            const category = item.dataset.category;
            const name = item.querySelector('.ns-location-name')?.textContent.toLowerCase() || '';
            const matchesFilter = currentFilter === 'all' || category === currentFilter;
            const matchesSearch = !currentSearchQuery || name.includes(currentSearchQuery);
            const isVisible = matchesFilter && matchesSearch;

            item.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        // Update markers visibility
        markers.forEach(m => {
            const matchesFilter = currentFilter === 'all' || m.place.category_id === currentFilter;
            const matchesSearch = !currentSearchQuery || m.place.name.toLowerCase().includes(currentSearchQuery);
            const isVisible = matchesFilter && matchesSearch;

            m.element.style.display = isVisible ? '' : 'none';
        });

        // Update result count
        const countEl = modal.querySelector('.ns-result-count');
        if (countEl) {
            countEl.textContent = visibleCount + ' results';
        }
    }

    /**
     * Set travel mode and sync globally
     */
    function setTravelMode(mode, isExternalUpdate = false) {
        currentTravelMode = mode;

        // Update UI
        modal.querySelectorAll('[data-travel-mode]').forEach(btn => {
            const isActive = btn.dataset.travelMode === mode;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });

        // Recalculate travel times
        calculateTravelTimes();
        updateHighlighting();

        // Emit global event if not external update
        if (!isExternalUpdate) {
            // Update global state
            if (window.PlacyGlobalState) {
                window.PlacyGlobalState.travelMode = mode;
            }

            // Dispatch event for other components
            document.dispatchEvent(new CustomEvent('placy:travelModeChange', {
                detail: { mode: mode, source: 'masterMap' }
            }));
        }
    }

    /**
     * Set time budget and sync globally
     */
    function setTimeBudget(budget, isExternalUpdate = false) {
        currentTimeBudget = budget;

        // Update UI
        modal.querySelectorAll('[data-time-budget]').forEach(btn => {
            const isActive = parseInt(btn.dataset.timeBudget, 10) === budget;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });

        // Update highlighting
        updateHighlighting();

        // Emit global event if not external update
        if (!isExternalUpdate) {
            // Update global state
            if (window.PlacyGlobalState) {
                window.PlacyGlobalState.timeBudget = budget;
            }

            // Dispatch event for other components
            document.dispatchEvent(new CustomEvent('placy:timeBudgetChange', {
                detail: { budget: budget, source: 'masterMap' }
            }));
        }
    }

    /**
     * Update toggle button states to match current values
     */
    function updateToggleStates() {
        // Travel mode
        modal.querySelectorAll('[data-travel-mode]').forEach(btn => {
            const isActive = btn.dataset.travelMode === currentTravelMode;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });

        // Time budget
        modal.querySelectorAll('[data-time-budget]').forEach(btn => {
            const isActive = parseInt(btn.dataset.timeBudget, 10) === currentTimeBudget;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });
    }

    /**
     * Calculate travel times for all places
     */
    function calculateTravelTimes() {
        // Speed in km/h based on mode
        const speeds = {
            walk: 5,
            bike: 15,
            car: 40
        };
        const speed = speeds[currentTravelMode] || 5;

        modal.querySelectorAll('.ns-location-item').forEach(item => {
            const lat = parseFloat(item.dataset.lat);
            const lng = parseFloat(item.dataset.lng);

            if (!lat || !lng) {
                item.querySelector('[data-place-duration]').textContent = '--';
                return;
            }

            // Calculate distance using Haversine formula
            const distance = haversineDistance(projectLat, projectLng, lat, lng);

            // Calculate time in minutes
            const timeMinutes = Math.round((distance / speed) * 60);
            item.querySelector('[data-place-duration]').textContent = timeMinutes;
            item.dataset.travelTime = timeMinutes;
        });
    }

    /**
     * Update highlighting based on time budget
     */
    function updateHighlighting() {
        modal.querySelectorAll('.ns-location-item').forEach(item => {
            const time = parseInt(item.dataset.travelTime, 10);
            const isWithinBudget = !isNaN(time) && time <= currentTimeBudget;

            item.classList.toggle('is-highlighted', isWithinBudget);
            item.classList.toggle('is-dimmed', !isWithinBudget && !isNaN(time));
        });

        // Update markers
        markers.forEach(m => {
            const item = modal.querySelector(`[data-place-id="${m.place.id || ''}"]`);
            if (item) {
                const time = parseInt(item.dataset.travelTime, 10);
                const isWithinBudget = !isNaN(time) && time <= currentTimeBudget;

                m.element.classList.toggle('is-highlighted', isWithinBudget);
                m.element.classList.toggle('is-dimmed', !isWithinBudget && !isNaN(time));
            }
        });
    }

    /**
     * Navigate to chapter in story
     */
    function viewInStory(chapterId) {
        closeMasterMap();

        // Find the chapter section and scroll to it
        setTimeout(() => {
            const section = document.getElementById(chapterId) || 
                           document.querySelector(`[data-chapter="${chapterId}"]`);
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 300);
    }

    /**
     * Haversine distance calculation
     */
    function haversineDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // km
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function toRad(deg) {
        return deg * (Math.PI / 180);
    }

    /**
     * Debounce helper
     */
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
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

    // Expose globally
    window.openMasterMap = openMasterMap;
    window.closeMasterMap = closeMasterMap;

    window.MasterMap = {
        open: openMasterMap,
        close: closeMasterMap,
        setFilter: setFilter,
        setTravelMode: setTravelMode,
        setTimeBudget: setTimeBudget
    };

})();
