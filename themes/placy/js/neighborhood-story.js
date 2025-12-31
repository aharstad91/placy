/**
 * Neighborhood Story - Global State Manager
 * 
 * Manages global state for:
 * - Travel Mode (walk, bike, car)
 * - Time Budget (5, 10, 15 min)
 * 
 * Syncs state across all modals and components using custom events.
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // =========================================================================
    // GLOBAL STATE
    // =========================================================================
    
    const NeighborhoodStory = {
        // State
        state: {
            travelMode: 'walk',
            timeBudget: 10,
            activeModal: null,
            allPoints: [],
        },

        // Cached DOM elements
        elements: {
            sidebar: null,
            modals: [],
            globalMap: null,
        },

        // =====================================================================
        // INITIALIZATION
        // =====================================================================
        
        init: function(config = {}) {
            // Set initial state from config (passed from PHP)
            this.state.travelMode = config.defaultTravelMode || 'walk';
            this.state.timeBudget = parseInt(config.defaultTimeBudget) || 10;
            this.state.allPoints = config.allPoints || [];

            // Sync with PlacyGlobalState if available
            if (window.PlacyGlobalState) {
                if (window.PlacyGlobalState.travelMode) {
                    this.state.travelMode = window.PlacyGlobalState.travelMode;
                }
                if (window.PlacyGlobalState.timeBudget) {
                    this.state.timeBudget = window.PlacyGlobalState.timeBudget;
                }
            }

            // Cache DOM elements
            this.elements.sidebar = document.querySelector('.ns-sidebar');
            this.elements.modals = document.querySelectorAll('.ns-modal');
            this.elements.globalMap = document.querySelector('.ns-global-map');

            // Bind event handlers
            this.bindEvents();
            
            // Restore state from localStorage if available
            this.restoreState();

            // Sync PlacyGlobalState with restored state (ensure consistency)
            if (window.PlacyGlobalState) {
                window.PlacyGlobalState.travelMode = this.state.travelMode;
                window.PlacyGlobalState.timeBudget = this.state.timeBudget;
            }

            // Initial UI sync
            this.syncAllUI();

            console.log('[NeighborhoodStory] Initialized', this.state);
        },

        // =====================================================================
        // EVENT BINDING
        // =====================================================================

        bindEvents: function() {
            // Travel Mode controls (all instances)
            document.addEventListener('click', (e) => {
                const travelBtn = e.target.closest('[data-travel-mode]');
                if (travelBtn) {
                    e.preventDefault();
                    this.setTravelMode(travelBtn.dataset.travelMode);
                }
            });

            // Time Budget controls (all instances)
            document.addEventListener('click', (e) => {
                const timeBtn = e.target.closest('[data-time-budget]');
                if (timeBtn) {
                    e.preventDefault();
                    this.setTimeBudget(parseInt(timeBtn.dataset.timeBudget));
                }
            });

            // Sidebar navigation
            document.addEventListener('click', (e) => {
                const navItem = e.target.closest('[data-nav-anchor]');
                if (navItem) {
                    e.preventDefault();
                    const anchor = navItem.dataset.navAnchor;
                    const type = navItem.dataset.navType || 'scroll';
                    
                    if (type === 'chapter') {
                        this.openChapterModal(anchor);
                    } else {
                        this.scrollToAnchor(anchor);
                    }
                }
            });

            // Open chapter modal
            document.addEventListener('click', (e) => {
                const openBtn = e.target.closest('[data-open-chapter]');
                if (openBtn) {
                    e.preventDefault();
                    this.openChapterModal(openBtn.dataset.openChapter);
                }
            });

            // Close modal
            document.addEventListener('click', (e) => {
                const closeBtn = e.target.closest('[data-close-modal]');
                if (closeBtn) {
                    e.preventDefault();
                    this.closeModal();
                }

                // Click on backdrop
                if (e.target.classList.contains('ns-modal-backdrop')) {
                    this.closeModal();
                }
            });

            // Open global map
            document.addEventListener('click', (e) => {
                const mapBtn = e.target.closest('[data-open-global-map]');
                if (mapBtn) {
                    e.preventDefault();
                    this.openGlobalMap();
                }
            });

            // Keyboard: Escape to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.state.activeModal) {
                    this.closeModal();
                }
            });

            // Listen for custom state change events (for external integrations)
            document.addEventListener('ns:stateChange', (e) => {
                if (e.detail.travelMode) this.setTravelMode(e.detail.travelMode, false);
                if (e.detail.timeBudget) this.setTimeBudget(e.detail.timeBudget, false);
            });

            // Listen for global placy events from sidebar/modals
            document.addEventListener('placy:travelModeChange', (e) => {
                if (e.detail && e.detail.source !== 'neighborhoodStory') {
                    const mode = e.detail.mode || e.detail.travelMode;
                    if (mode) this.setTravelMode(mode, false);
                }
            });

            document.addEventListener('placy:timeBudgetChange', (e) => {
                if (e.detail && e.detail.source !== 'neighborhoodStory') {
                    const budget = e.detail.budget || e.detail.timeBudget;
                    if (budget) this.setTimeBudget(parseInt(budget, 10), false);
                }
            });

            // Sidebar dropdown toggles
            document.addEventListener('click', (e) => {
                const dropdownHeader = e.target.closest('.ns-dropdown-header');
                if (dropdownHeader) {
                    e.preventDefault();
                    const dropdown = dropdownHeader.closest('.ns-dropdown');
                    if (dropdown) {
                        dropdown.classList.toggle('is-open');
                        
                        // Update aria-expanded
                        const isOpen = dropdown.classList.contains('is-open');
                        dropdownHeader.setAttribute('aria-expanded', isOpen);
                    }
                }
            });

            // Category filter in global map modal
            document.addEventListener('click', (e) => {
                const categoryBtn = e.target.closest('[data-filter-category]');
                if (categoryBtn) {
                    e.preventDefault();
                    const category = categoryBtn.dataset.filterCategory;
                    this.filterByCategory(category);
                    
                    // Update active state
                    document.querySelectorAll('[data-filter-category]').forEach(btn => {
                        btn.classList.toggle('is-active', btn.dataset.filterCategory === category);
                    });
                }
            });

            // Search input in modals
            document.addEventListener('input', (e) => {
                const searchInput = e.target.closest('.ns-search-input');
                if (searchInput) {
                    this.filterBySearch(searchInput.value);
                }
            });
        },

        // =====================================================================
        // STATE MANAGEMENT
        // =====================================================================

        setTravelMode: function(mode, emit = true) {
            if (!['walk', 'bike', 'car'].includes(mode)) return;
            
            this.state.travelMode = mode;
            this.persistState();
            this.syncAllUI();
            
            if (emit) {
                this.emit('travelModeChange', { travelMode: mode });
            }
        },

        setTimeBudget: function(minutes, emit = true) {
            if (![5, 10, 15, 20, 30].includes(minutes)) return;
            
            this.state.timeBudget = minutes;
            this.persistState();
            this.syncAllUI();
            
            if (emit) {
                this.emit('timeBudgetChange', { timeBudget: minutes });
            }
        },

        persistState: function() {
            try {
                // Use the same localStorage keys as project-sidebar.js for consistency
                localStorage.setItem('placy_travel_mode', this.state.travelMode);
                localStorage.setItem('placy_time_budget', this.state.timeBudget.toString());
            } catch (e) {
                console.warn('[NeighborhoodStory] Could not persist state', e);
            }
        },

        restoreState: function() {
            try {
                // Use the same localStorage keys as project-sidebar.js for consistency
                const savedMode = localStorage.getItem('placy_travel_mode');
                const savedBudget = localStorage.getItem('placy_time_budget');
                
                if (savedMode && ['walk', 'bike', 'car'].includes(savedMode)) {
                    this.state.travelMode = savedMode;
                }
                if (savedBudget) {
                    const budget = parseInt(savedBudget);
                    if ([5, 10, 15, 20, 30].includes(budget)) {
                        this.state.timeBudget = budget;
                    }
                }
            } catch (e) {
                console.warn('[NeighborhoodStory] Could not restore state', e);
            }
        },

        emit: function(eventName, detail) {
            document.dispatchEvent(new CustomEvent(`ns:${eventName}`, { detail }));
        },

        // =====================================================================
        // UI SYNC
        // =====================================================================

        syncAllUI: function() {
            this.syncTravelModeUI();
            this.syncTimeBudgetUI();
            this.syncPointsHighlighting();
        },

        syncTravelModeUI: function() {
            // Update all travel mode toggle buttons (only buttons, not section elements)
            document.querySelectorAll('button[data-travel-mode]').forEach(btn => {
                const isActive = btn.dataset.travelMode === this.state.travelMode;
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('ns-btn-active', isActive);
                btn.setAttribute('aria-pressed', isActive.toString());
                btn.setAttribute('aria-checked', isActive.toString());
            });

            // Update dropdown displays
            document.querySelectorAll('.ns-travel-mode-display').forEach(display => {
                display.textContent = this.getTravelModeLabel(this.state.travelMode);
            });

            // Update icons
            document.querySelectorAll('.ns-travel-mode-icon').forEach(icon => {
                icon.className = `ns-travel-mode-icon ${this.getTravelModeIconClass(this.state.travelMode)}`;
            });
        },

        syncTimeBudgetUI: function() {
            // Update all time budget toggle buttons (only buttons, not section elements)
            document.querySelectorAll('button[data-time-budget]').forEach(btn => {
                const isActive = parseInt(btn.dataset.timeBudget) === this.state.timeBudget;
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('ns-btn-active', isActive);
                btn.setAttribute('aria-pressed', isActive.toString());
                btn.setAttribute('aria-checked', isActive.toString());
            });

            // Update displays
            document.querySelectorAll('.ns-time-budget-display').forEach(display => {
                display.textContent = `≤ ${this.state.timeBudget} min`;
            });
        },

        syncPointsHighlighting: function() {
            const modeLabels = { walk: 'walk', bike: 'bike', car: 'drive' };
            const modeLabel = modeLabels[this.state.travelMode] || 'walk';
            
            // Update POI cards based on travel time
            document.querySelectorAll('[data-poi-times]').forEach(card => {
                try {
                    const times = JSON.parse(card.dataset.poiTimes);
                    const travelTime = times[this.state.travelMode] || times.walk || 999;
                    const isWithinBudget = travelTime <= this.state.timeBudget;
                    
                    card.classList.toggle('ns-poi-highlighted', isWithinBudget);
                    card.classList.toggle('ns-poi-dimmed', !isWithinBudget);
                    
                    // Update time display - support both class naming conventions
                    const timeValue = card.querySelector('.poi-travel-time, .ns-poi-time');
                    const timeLabel = card.querySelector('.poi-travel-label');
                    
                    if (timeValue && timeLabel) {
                        // Separate spans for value and label
                        timeValue.textContent = travelTime;
                        timeLabel.textContent = `min ${modeLabel}`;
                    } else if (timeValue) {
                        // Single span with full text (ns-poi-time style)
                        timeValue.textContent = `${travelTime} min ${modeLabel}`;
                    }
                } catch (e) {
                    console.warn('[NeighborhoodStory] Invalid POI times data', e);
                }
            });

            // Update highlight counts
            this.updateHighlightCounts();
        },

        updateHighlightCounts: function() {
            document.querySelectorAll('[data-highlight-count]').forEach(counter => {
                const container = counter.closest('[data-poi-container]') || document;
                const highlighted = container.querySelectorAll('.ns-poi-highlighted').length;
                const total = container.querySelectorAll('[data-poi-times]').length;
                
                counter.textContent = highlighted;
                
                const totalEl = counter.parentElement?.querySelector('[data-total-count]');
                if (totalEl) totalEl.textContent = total;
            });
        },

        // =====================================================================
        // MODAL MANAGEMENT
        // =====================================================================

        openChapterModal: function(chapterId) {
            const modal = document.querySelector(`[data-chapter-modal="${chapterId}"]`);
            if (!modal) {
                console.warn(`[NeighborhoodStory] Modal not found: ${chapterId}`);
                return;
            }

            // Close any existing modal first
            this.closeModal();

            // Open the modal
            this.state.activeModal = chapterId;
            modal.classList.add('ns-modal-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ns-modal-active');

            // Sync UI in modal
            this.syncAllUI();

            // Initialize map if present
            this.initModalMap(modal);

            // Focus management
            const firstFocusable = modal.querySelector('button, [href], input, select, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) firstFocusable.focus();

            this.emit('modalOpen', { chapterId });
        },

        closeModal: function() {
            if (!this.state.activeModal) return;

            const modal = document.querySelector(`[data-chapter-modal="${this.state.activeModal}"]`);
            if (modal) {
                modal.classList.remove('ns-modal-open');
                modal.setAttribute('aria-hidden', 'true');
            }

            // Also close global map if open
            const globalMap = document.querySelector('.ns-global-map');
            if (globalMap) {
                globalMap.classList.remove('ns-modal-open');
                globalMap.setAttribute('aria-hidden', 'true');
            }

            document.body.classList.remove('ns-modal-active');
            
            const closedModal = this.state.activeModal;
            this.state.activeModal = null;

            this.emit('modalClose', { chapterId: closedModal });
        },

        openGlobalMap: function() {
            const globalMap = document.querySelector('.ns-global-map');
            if (!globalMap) {
                console.warn('[NeighborhoodStory] Global map not found');
                return;
            }

            this.closeModal();
            
            this.state.activeModal = 'global-map';
            globalMap.classList.add('ns-modal-open');
            globalMap.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ns-modal-active');

            this.syncAllUI();
            this.initGlobalMap();

            this.emit('globalMapOpen', {});
        },

        // =====================================================================
        // MAP INTEGRATION
        // =====================================================================

        initModalMap: function(modal) {
            const mapContainer = modal.querySelector('.ns-modal-map');
            if (!mapContainer || mapContainer.dataset.mapInitialized) return;

            // Get points for this chapter
            const chapterId = modal.dataset.chapterModal;
            const points = this.getChapterPoints(chapterId);

            // Initialize map (using existing Mapbox setup or create new)
            this.createMap(mapContainer, points);
            mapContainer.dataset.mapInitialized = 'true';
        },

        initGlobalMap: function() {
            const mapContainer = document.querySelector('.ns-global-map-container');
            if (!mapContainer || mapContainer.dataset.mapInitialized) return;

            // Use all aggregated points
            this.createMap(mapContainer, this.state.allPoints);
            mapContainer.dataset.mapInitialized = 'true';
        },

        createMap: function(container, points) {
            // Check if Mapbox is available
            if (typeof mapboxgl === 'undefined') {
                console.warn('[NeighborhoodStory] Mapbox GL not loaded');
                container.innerHTML = '<div class="ns-map-placeholder">Map loading...</div>';
                return;
            }

            // Get center from first point or config
            const center = points.length > 0 
                ? [points[0].longitude, points[0].latitude]
                : [10.3951, 63.4305]; // Default: Trondheim

            const map = new mapboxgl.Map({
                container: container,
                style: 'mapbox://styles/mapbox/light-v11',
                center: center,
                zoom: 14,
            });

            // Add markers for each point
            points.forEach(point => {
                if (!point.latitude || !point.longitude) return;

                const el = document.createElement('div');
                el.className = 'ns-map-marker';
                el.innerHTML = this.getMarkerIcon(point.category);

                new mapboxgl.Marker(el)
                    .setLngLat([point.longitude, point.latitude])
                    .setPopup(new mapboxgl.Popup().setHTML(this.getPopupContent(point)))
                    .addTo(map);
            });

            // Store reference
            container._nsMap = map;
        },

        getChapterPoints: function(chapterId) {
            // Filter points by chapter
            return this.state.allPoints.filter(p => p.chapterId === chapterId);
        },

        getMarkerIcon: function(category) {
            // Return SVG icon based on category
            const icons = {
                train: '<svg>...</svg>',
                bus: '<svg>...</svg>',
                default: '<div class="ns-marker-dot"></div>'
            };
            return icons[category] || icons.default;
        },

        getPopupContent: function(point) {
            return `
                <div class="ns-popup">
                    <h4 class="ns-popup-title">${point.name || 'Unnamed'}</h4>
                    <p class="ns-popup-category">${point.category || ''}</p>
                </div>
            `;
        },

        // =====================================================================
        // FILTERING
        // =====================================================================

        filterByCategory: function(category) {
            const modal = this.state.activeModal;
            if (!modal) return;

            const locationItems = modal.querySelectorAll('.ns-location-item');
            
            locationItems.forEach(item => {
                if (category === 'all') {
                    item.style.display = '';
                } else {
                    const itemCategory = item.dataset.category || '';
                    item.style.display = itemCategory === category ? '' : 'none';
                }
            });

            // Also filter markers on map if applicable
            this.filterMarkersOnMap(category);
        },

        filterBySearch: function(query) {
            const modal = this.state.activeModal;
            if (!modal) return;

            const lowerQuery = query.toLowerCase().trim();
            const locationItems = modal.querySelectorAll('.ns-location-item');
            
            locationItems.forEach(item => {
                if (!lowerQuery) {
                    item.style.display = '';
                } else {
                    const name = (item.dataset.name || '').toLowerCase();
                    const category = (item.dataset.category || '').toLowerCase();
                    const matches = name.includes(lowerQuery) || category.includes(lowerQuery);
                    item.style.display = matches ? '' : 'none';
                }
            });
        },

        filterMarkersOnMap: function(category) {
            // Find map container in active modal
            const modal = this.state.activeModal;
            if (!modal) return;

            const mapContainer = modal.querySelector('.ns-map-container');
            if (!mapContainer || !mapContainer._nsMap) return;

            const markers = mapContainer.querySelectorAll('.ns-map-marker');
            markers.forEach(marker => {
                if (category === 'all') {
                    marker.style.display = '';
                } else {
                    const markerCategory = marker.dataset.category || '';
                    marker.style.display = markerCategory === category ? '' : 'none';
                }
            });
        },

        // =====================================================================
        // NAVIGATION
        // =====================================================================

        scrollToAnchor: function(anchor) {
            const element = document.getElementById(anchor) || document.querySelector(`[data-anchor="${anchor}"]`);
            if (!element) {
                console.warn(`[NeighborhoodStory] Anchor not found: ${anchor}`);
                return;
            }

            element.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });

            // Update URL hash without triggering scroll
            history.pushState(null, '', `#${anchor}`);
        },

        // =====================================================================
        // HELPERS
        // =====================================================================

        getTravelModeLabel: function(mode) {
            const labels = {
                walk: 'Til fots',
                bike: 'Sykkel',
                car: 'Bil'
            };
            return labels[mode] || labels.walk;
        },

        getTravelModeIconClass: function(mode) {
            const icons = {
                walk: 'icon-walk',
                bike: 'icon-bike',
                car: 'icon-car'
            };
            return icons[mode] || icons.walk;
        },

        // Public API for external access
        getState: function() {
            return { ...this.state };
        },

        getAllPoints: function() {
            return this.state.allPoints;
        }
    };

    // =========================================================================
    // EXPOSE GLOBALLY
    // =========================================================================
    
    window.NeighborhoodStory = NeighborhoodStory;

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Look for config in data attribute
            const configEl = document.querySelector('[data-ns-config]');
            const config = configEl ? JSON.parse(configEl.dataset.nsConfig) : {};
            NeighborhoodStory.init(config);
        });
    } else {
        // DOM already loaded
        const configEl = document.querySelector('[data-ns-config]');
        const config = configEl ? JSON.parse(configEl.dataset.nsConfig) : {};
        NeighborhoodStory.init(config);
    }

})();
