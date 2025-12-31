/**
 * Chapter Mega Modal / Drawer
 * 
 * Handles the mega-modal/drawer functionality for chapter sections
 * with POI lists, travel time calculations, and map integration.
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Global state
    window.placyChapterModals = window.placyChapterModals || {};
    
    // Current modal state
    let currentModal = null;
    let currentTravelMode = 'walk';
    let currentTimeBudget = 15;
    let travelTimeCache = {};
    
    // Map interaction state
    let poiCardToMarkerMap = new Map(); // Maps POI card element -> marker data
    let poiScrollObserver = null; // IntersectionObserver for scroll-based marker activation

    /**
     * Initialize chapter modals on page load
     */
    document.addEventListener('DOMContentLoaded', function() {
        initializeChapterData();
        createMegaDrawerElement();
        calculateInitialTravelTimes();
        initGlobalEventListeners();
        syncWithGlobalState();
    });

    /**
     * Listen for global state changes from sidebar
     */
    function initGlobalEventListeners() {
        // Listen for travel mode changes from sidebar or master map
        document.addEventListener('placy:travelModeChange', function(e) {
            if (e.detail && e.detail.source !== 'chapterModal') {
                const mode = e.detail.mode || e.detail.travelMode;
                if (mode && mode !== currentTravelMode) {
                    currentTravelMode = mode;
                    updateControlButtons();
                    if (currentModal) {
                        populatePOIList(currentModal.allPoints);
                        calculateTravelTimes(currentModal);
                        updateDrawerMapMarkers();
                    }
                    console.log('[ChapterModal] Synced travel mode from', e.detail.source, ':', mode);
                }
            }
        });

        // Listen for time budget changes from sidebar or master map
        document.addEventListener('placy:timeBudgetChange', function(e) {
            if (e.detail && e.detail.source !== 'chapterModal') {
                const budget = e.detail.budget || e.detail.timeBudget;
                if (budget && budget !== currentTimeBudget) {
                    currentTimeBudget = parseInt(budget, 10);
                    updateControlButtons();
                    updateHighlightCount();
                    updatePOIListStyling();
                    updateDrawerMapMarkers();
                    console.log('[ChapterModal] Synced time budget from', e.detail.source, ':', budget);
                }
            }
        });
        
        // Add click handlers for toggle buttons (delegated)
        document.addEventListener('click', function(e) {
            // Travel mode button
            const travelBtn = e.target.closest('#travel-mode-btns [data-travel-mode]');
            if (travelBtn) {
                e.preventDefault();
                window.setTravelMode(travelBtn.dataset.travelMode);
                return;
            }
            
            // Time budget button
            const timeBtn = e.target.closest('#time-budget-btns [data-time-budget]');
            if (timeBtn) {
                e.preventDefault();
                window.setTimeBudget(parseInt(timeBtn.dataset.timeBudget, 10));
                return;
            }
            
            // "Se på kart" button (all variants: poi-show-on-map, data-show-on-map, etc.)
            const mapBtn = e.target.closest('.poi-show-on-map, [data-show-on-map]');
            if (mapBtn) {
                e.preventDefault();
                e.stopPropagation();
                showPOIOnMap(mapBtn);
                return;
            }
        });
    }

    /**
     * Sync with global state from sidebar on init
     */
    function syncWithGlobalState() {
        if (window.PlacyGlobalState) {
            // Sync travel mode (now using same values: walk/bike/car)
            if (window.PlacyGlobalState.travelMode) {
                currentTravelMode = window.PlacyGlobalState.travelMode;
            }
            // Sync time budget
            if (window.PlacyGlobalState.timeBudget) {
                currentTimeBudget = window.PlacyGlobalState.timeBudget;
            }
        }
        // Update UI to reflect synced state
        updateControlButtons();
    }

    /**
     * Parse chapter modal data from JSON script tags
     */
    function initializeChapterData() {
        const dataElements = document.querySelectorAll('.pl-chapter-modal-data');
        
        dataElements.forEach(function(element) {
            try {
                const data = JSON.parse(element.textContent);
                window.placyChapterModals[data.chapterId] = data;
                
                // Set initial travel mode and time budget from data
                if (data.defaultTravelMode) {
                    currentTravelMode = data.defaultTravelMode;
                }
                if (data.defaultTimeBudget) {
                    currentTimeBudget = data.defaultTimeBudget;
                }
            } catch (e) {
                console.error('Failed to parse chapter modal data:', e);
            }
        });
    }

    /**
     * Create the mega drawer element (inserted once, reused for all chapters)
     */
    function createMegaDrawerElement() {
        if (document.getElementById('pl-mega-drawer')) {
            return;
        }

        const drawer = document.createElement('div');
        drawer.id = 'pl-mega-drawer';
        drawer.className = 'pl-mega-drawer';
        drawer.innerHTML = `
            <div class="pl-mega-drawer__backdrop" onclick="closeChapterMegaModal()"></div>
            <div class="pl-mega-drawer__topbar">
                <span class="pl-mega-drawer__label">Neighborhood Story</span>
                <button class="pl-mega-drawer__close" onclick="closeChapterMegaModal()" aria-label="Close">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="pl-mega-drawer__panel">
                <div class="pl-mega-drawer__content">
                    <div class="pl-mega-drawer__scroll">
                        <!-- Header -->
                        <div class="pl-mega-drawer__header">
                            <h2 class="pl-mega-drawer__title" id="drawer-title"></h2>
                            <div class="pl-mega-drawer__stats">
                                <span class="pl-mega-drawer__total" id="drawer-total"></span>
                                <span class="pl-mega-drawer__highlight" id="drawer-highlight"></span>
                            </div>
                        </div>
                        
                        <!-- Controls -->
                        <div class="pl-mega-drawer__controls">
                            <div class="ns-travel-controls ns-travel-controls--modal" data-travel-controls>
                                <!-- Travel Mode -->
                                <div class="ns-tc-group">
                                    <div class="ns-tc-header">
                                        <span class="ns-tc-label">Travel Mode</span>
                                        <button type="button" class="ns-tc-info" aria-label="Travel mode info" title="Choose how you travel to calculate distance times">
                                            <svg class="ns-tc-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                                                <path stroke-linecap="round" stroke-width="1.5" d="M12 16v-4m0-4h.01"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="ns-tc-toggle-group" id="travel-mode-btns" role="radiogroup" aria-label="Travel mode">
                                        <button type="button" class="ns-tc-toggle-btn" data-travel-mode="walk" role="radio" aria-checked="false" aria-label="Til fots">
                                            <span class="ns-tc-toggle-icon icon-walk" aria-hidden="true"></span>
                                            <span class="ns-tc-toggle-text">Til fots</span>
                                        </button>
                                        <button type="button" class="ns-tc-toggle-btn" data-travel-mode="bike" role="radio" aria-checked="false" aria-label="Sykkel">
                                            <span class="ns-tc-toggle-icon icon-bike" aria-hidden="true"></span>
                                            <span class="ns-tc-toggle-text">Sykkel</span>
                                        </button>
                                        <button type="button" class="ns-tc-toggle-btn" data-travel-mode="car" role="radio" aria-checked="false" aria-label="Bil">
                                            <span class="ns-tc-toggle-icon icon-car" aria-hidden="true"></span>
                                            <span class="ns-tc-toggle-text">Bil</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Time Budget -->
                                <div class="ns-tc-group">
                                    <div class="ns-tc-header">
                                        <span class="ns-tc-label">Time Budget</span>
                                        <button type="button" class="ns-tc-info" aria-label="Time budget info" title="Highlights places within the selected time budget. Nothing is hidden.">
                                            <svg class="ns-tc-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                                                <path stroke-linecap="round" stroke-width="1.5" d="M12 16v-4m0-4h.01"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="ns-tc-toggle-group" id="time-budget-btns" role="radiogroup" aria-label="Time budget">
                                        <button type="button" class="ns-tc-toggle-btn" data-time-budget="5" role="radio" aria-checked="false">≤ 5 min</button>
                                        <button type="button" class="ns-tc-toggle-btn" data-time-budget="10" role="radio" aria-checked="false">10 min</button>
                                        <button type="button" class="ns-tc-toggle-btn" data-time-budget="15" role="radio" aria-checked="false">15 min</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pl-mega-drawer__search">
                                <div class="pl-mega-drawer__control-label">
                                    <span>Search</span>
                                </div>
                                <input type="text" class="pl-mega-drawer__search-input" id="drawer-search" placeholder="Search places..." oninput="filterPOIList()">
                            </div>
                        </div>
                        
                        <!-- Intro & Takeaways -->
                        <div class="pl-mega-drawer__intro">
                            <div class="pl-mega-drawer__section-label">Theme Intro</div>
                            <p class="pl-mega-drawer__intro-text" id="drawer-ingress"></p>
                            
                            <div class="pl-mega-drawer__takeaways" id="drawer-takeaways">
                                <button class="pl-mega-drawer__takeaways-trigger" onclick="toggleTakeaways()">
                                    <span class="pl-mega-drawer__takeaways-title" id="drawer-takeaways-title">Key Takeaways (0)</span>
                                    <svg class="pl-mega-drawer__takeaways-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div class="pl-mega-drawer__takeaways-content">
                                    <ul class="pl-mega-drawer__takeaways-list" id="drawer-takeaways-list"></ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- POI List -->
                        <div class="pl-mega-drawer__poi-list">
                            <div class="pl-mega-drawer__poi-list-header">
                                <span class="pl-mega-drawer__poi-list-title">All Locations</span>
                                <span class="pl-mega-drawer__poi-list-divider"></span>
                            </div>
                            <div id="drawer-poi-list"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Map -->
                <div class="pl-mega-drawer__map">
                    <div class="pl-mega-drawer__map-container" id="drawer-map-container"></div>
                    <div class="pl-mega-drawer__map-watermark">MAP</div>
                </div>
            </div>
        `;
        
        document.body.appendChild(drawer);
    }

    /**
     * Open the mega modal for a specific chapter
     * @param {string} chapterId - The chapter ID
     */
    window.openChapterMegaModal = function(chapterId, targetPoiId) {
        const data = window.placyChapterModals[chapterId];
        if (!data) {
            console.error('No modal data found for chapter:', chapterId);
            return;
        }

        currentModal = data;
        
        // Sync with global state BEFORE populating (in case sidebar changed values)
        if (window.PlacyGlobalState) {
            if (window.PlacyGlobalState.travelMode) {
                currentTravelMode = window.PlacyGlobalState.travelMode;
            }
            if (window.PlacyGlobalState.timeBudget) {
                currentTimeBudget = window.PlacyGlobalState.timeBudget;
            }
            console.log('[ChapterModal] Synced from global state:', currentTravelMode, currentTimeBudget);
        }
        
        // Lock body scroll
        document.body.style.overflow = 'hidden';
        
        // Populate drawer content
        populateDrawer(data);
        
        // Show drawer
        const drawer = document.getElementById('pl-mega-drawer');
        if (drawer) {
            drawer.classList.add('is-open');
        }
        
        // Initialize map in drawer
        initializeDrawerMap(data);
        
        // Calculate travel times for all points
        calculateTravelTimes(data);
        
        // Initialize scroll-based marker activation (for both theme-story and legacy)
        // Small delay to ensure markers are added to DOM
        setTimeout(function() {
            initScrollMarkerActivation();
            
            // If targetPoiId provided, scroll to and activate that POI
            if (targetPoiId) {
                scrollToAndActivatePOI(targetPoiId);
            }
        }, 100);
    };

    /**
     * Close the mega modal
     */
    window.closeChapterMegaModal = function() {
        const drawer = document.getElementById('pl-mega-drawer');
        if (drawer) {
            drawer.classList.remove('is-open');
        }
        
        // Restore body scroll
        document.body.style.overflow = '';
        
        currentModal = null;
    };

    /**
     * Populate drawer with chapter data
     * @param {Object} data - Chapter modal data
     */
    function populateDrawer(data) {
        // Title
        const titleEl = document.getElementById('drawer-title');
        if (titleEl) {
            titleEl.textContent = data.title || data.categoryName || 'Chapter';
        }
        
        // Check if using theme-story content
        if (data.useThemeStory && data.themeStoryHtml) {
            // Use theme-story Gutenberg content for the left column
            populateWithThemeStory(data);
        } else {
            // Use legacy allPoints array
            populateWithLegacyPOIList(data);
        }
        
        // Reset travel mode and time budget buttons
        updateControlButtons();
    }
    
    /**
     * Populate drawer with theme-story Gutenberg content
     * Extracts POI data from the rendered HTML blocks
     * @param {Object} data - Chapter modal data with themeStoryHtml
     */
    function populateWithThemeStory(data) {
        const scrollContainer = document.querySelector('.pl-mega-drawer__scroll');
        if (!scrollContainer) return;
        
        // Find the intro section and POI list section
        const introSection = scrollContainer.querySelector('.pl-mega-drawer__intro');
        const poiListSection = scrollContainer.querySelector('.pl-mega-drawer__poi-list');
        
        // Update stats (we'll count POIs after parsing)
        const totalEl = document.getElementById('drawer-total');
        
        // Hide intro section as theme-story handles its own intro
        if (introSection) {
            introSection.style.display = 'none';
        }
        
        // Replace POI list with theme-story content
        if (poiListSection) {
            // Create a wrapper for theme-story content
            poiListSection.innerHTML = `
                <div class="pl-mega-drawer__theme-story-content">
                    ${data.themeStoryHtml}
                </div>
            `;
            
            // Now extract POI data from the rendered blocks
            const extractedPoints = extractPOIDataFromDOM(poiListSection);
            
            // Store extracted points for map and filtering
            data.extractedPoints = extractedPoints;
            currentModal.extractedPoints = extractedPoints;
            
            // Update total count
            if (totalEl) {
                totalEl.textContent = extractedPoints.length + ' places found';
            }
            
            // Update highlight count
            updateHighlightCountForExtractedPoints(extractedPoints);
            
            // Add click/hover handlers to POI cards in theme-story content
            attachPOICardHandlers(poiListSection);
            
            // NOTE: Accordion functionality and API data loading for .ns-api-card elements
            // is handled by api-accordion.js which uses event delegation on document level
        }
    }
    
    /**
     * Display Entur departures on a POI card (Figma design)
     */
    function displayEnturDeparturesOnCard(card, departures) {
        // First check if there's an accordion structure (new poi-list blocks)
        const accordionContent = card.querySelector('.poi-api-accordion-content');
        
        // Find or create departures container
        let departuresEl = card.querySelector('.poi-live-departures');
        if (!departuresEl) {
            departuresEl = document.createElement('div');
            departuresEl.className = 'poi-live-departures entur-section';
            
            if (accordionContent) {
                // Insert into accordion content area
                accordionContent.innerHTML = ''; // Clear loading spinner
                accordionContent.appendChild(departuresEl);
            } else {
                // Fallback: Find a good place to insert
                const descEl = card.querySelector('.poi-description, .poi-gallery-text');
                if (descEl) {
                    descEl.after(departuresEl);
                } else {
                    card.appendChild(departuresEl);
                }
            }
        }
        
        if (!departures || departures.length === 0) {
            departuresEl.innerHTML = '<p class="api-empty-state">Ingen avganger funnet</p>';
            return;
        }
        
        const now = new Date();
        const formattedTime = now.toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
        
        let html = `
            <div class="api-section-header">
                <span class="api-status-dot api-status-dot--active"></span>
                <span class="api-section-title">Neste avganger</span>
            </div>
            <div class="entur-departures-list">
        `;
        
        departures.slice(0, 5).forEach(dep => {
            const lineNumber = dep.line_number || dep.line || '';
            const relativeTime = dep.relative_time !== undefined ? `${dep.relative_time} min` : '';
            const realtimeClass = dep.realtime ? 'api-status-dot--active' : 'api-status-dot--inactive';
            html += `
                <div class="entur-departure-row">
                    <div class="entur-departure-info">
                        <span class="entur-line-badge">${escapeHtml(lineNumber)}</span>
                        <span class="entur-destination">${escapeHtml(dep.destination || '')}</span>
                    </div>
                    <div class="entur-departure-time">
                        <span class="entur-time-value">${escapeHtml(dep.time || '')}</span>
                        <span class="entur-time-relative">${relativeTime}</span>
                        <span class="api-status-dot ${realtimeClass}"></span>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        html += `
            <div class="api-section-footer">
                <a href="https://entur.no" target="_blank" rel="noopener noreferrer" class="api-attribution-link">
                    Data fra Entur
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <span class="api-timestamp">Oppdatert ${formattedTime}</span>
            </div>
        `;
        
        departuresEl.innerHTML = html;
    }
    
    /**
     * Display Bysykkel availability on a POI card (Figma design)
     */
    function displayBysykkelAvailabilityOnCard(card, data) {
        // First check if there's an accordion structure (new poi-list blocks)
        const accordionContent = card.querySelector('.poi-api-accordion-content');
        
        // Find or create availability container
        let availEl = card.querySelector('.poi-bysykkel-availability');
        if (!availEl) {
            availEl = document.createElement('div');
            availEl.className = 'poi-bysykkel-availability bysykkel-section';
            
            if (accordionContent) {
                // Insert into accordion content area
                accordionContent.innerHTML = ''; // Clear loading spinner
                accordionContent.appendChild(availEl);
            } else {
                // Fallback: Find a good place to insert
                const descEl = card.querySelector('.poi-description, .poi-gallery-text');
                if (descEl) {
                    descEl.after(availEl);
                } else {
                    card.appendChild(availEl);
                }
            }
        }
        
        const bikes = data.bikes_available !== undefined ? data.bikes_available : 0;
        const docks = data.docks_available !== undefined ? data.docks_available : 0;
        
        const now = new Date();
        const formattedTime = now.toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
        
        const bikeStatusClass = bikes === 0 ? 'api-value--empty' : bikes < 3 ? 'api-value--low' : '';
        
        availEl.innerHTML = `
            <div class="api-section-header">
                <span class="api-status-dot api-status-dot--active"></span>
                <span class="api-section-title">BYSYKKEL TILGJENGELIGHET</span>
            </div>
            <div class="bysykkel-stats-grid">
                <div class="bysykkel-stat-card ${bikeStatusClass}">
                    <span class="bysykkel-stat-value">${bikes}</span>
                    <span class="bysykkel-stat-label">LEDIGE SYKLER</span>
                </div>
                <div class="bysykkel-stat-card">
                    <span class="bysykkel-stat-value">${docks}</span>
                    <span class="bysykkel-stat-label">LEDIGE LÅSER</span>
                </div>
            </div>
            <div class="api-section-footer">
                <a href="https://trfrby.no" target="_blank" rel="noopener noreferrer" class="api-attribution-link">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Data fra Trondheim Bysykkel
                </a>
                <span class="api-timestamp">Hentet kl ${formattedTime}</span>
            </div>
        `;
    }
    
    /**
     * Display Hyre availability on a POI card (Figma design)
     */
    function displayHyreAvailabilityOnCard(card, data) {
        // First check if there's an accordion structure (new poi-list blocks)
        const accordionContent = card.querySelector('.poi-api-accordion-content');
        
        // Find or create availability container
        let availEl = card.querySelector('.poi-hyre-availability');
        if (!availEl) {
            availEl = document.createElement('div');
            availEl.className = 'poi-hyre-availability hyre-section';
            
            if (accordionContent) {
                // Insert into accordion content area
                accordionContent.innerHTML = ''; // Clear loading spinner
                accordionContent.appendChild(availEl);
            } else {
                // Fallback: Find a good place to insert
                const descEl = card.querySelector('.poi-description, .poi-gallery-text');
                if (descEl) {
                    descEl.after(availEl);
                } else {
                    card.appendChild(availEl);
                }
            }
        }
        
        const carsAvailable = data.vehicles_available || 0;
        const capacity = data.capacity || 0;
        
        const now = new Date();
        const formattedTime = now.toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });
        
        const carStatusClass = carsAvailable === 0 ? 'api-value--empty' : carsAvailable < 2 ? 'api-value--low' : 'api-value--available';
        
        let html = `
            <div class="hyre-header-row">
                <div class="hyre-brand">
                    <svg class="hyre-car-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 17h14v-5l-2-4H7l-2 4v5z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/>
                    </svg>
                    <span class="hyre-brand-name">Hyre Bildeling</span>
                </div>
                <div class="hyre-charging-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Lading
                </div>
            </div>
            <div class="hyre-availability-row">
                <div class="hyre-cars-info">
                    <svg class="hyre-mini-car ${carStatusClass}" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 17h14v-5l-2-4H7l-2 4v5z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/>
                    </svg>
                    <span class="hyre-cars-count ${carStatusClass}">${carsAvailable} ledige biler</span>
                    <span class="hyre-capacity">av ${capacity} plasser</span>
                </div>
            </div>
        `;
        
        // Show vehicle types if available
        if (data.vehicles && data.vehicles.length > 0) {
            html += '<div class="hyre-vehicles-list">';
            data.vehicles.forEach(v => {
                const vehicleCount = v.count || 1;
                html += `
                    <div class="hyre-vehicle-row">
                        <span class="hyre-vehicle-model">${escapeHtml(v.type || v.model || 'Bil')}</span>
                        <span class="hyre-vehicle-count">${vehicleCount} ledig</span>
                    </div>
                `;
            });
            html += '</div>';
        }
        
        html += `
            <div class="api-section-footer">
                <a href="https://hyre.no" target="_blank" rel="noopener noreferrer" class="hyre-book-link">
                    Book bil på Hyre
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <span class="api-timestamp">Oppdatert kl ${formattedTime}</span>
            </div>
        `;
        
        availEl.innerHTML = html;
    }

    /**
     * Fetch Hyre availability for modal accordion
     */
    async function fetchHyreAvailabilityInModal(stationId, container) {
        if (!container) return;
        
        const loadingEl = container.querySelector('.hyre-availability-loading');
        const dataEl = container.querySelector('.hyre-availability-data');
        const errorEl = container.querySelector('.hyre-availability-error');
        
        if (loadingEl) loadingEl.style.display = 'flex';
        if (dataEl) dataEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        
        // Use WordPress REST API base URL
        const baseUrl = (typeof placyMapbox !== 'undefined' && placyMapbox.restBaseUrl) 
            ? placyMapbox.restBaseUrl 
            : '/wp-json/placy/v1';
        
        try {
            const response = await fetch(`${baseUrl}/hyre/availability/${stationId}`);
            if (!response.ok) throw new Error('API error');
            
            const data = await response.json();
            
            if (data.success) {
                const carsEl = dataEl?.querySelector('.hyre-cars');
                if (carsEl) {
                    // API returns vehicles_available directly, not nested in data
                    const availableCars = data.vehicles_available || 0;
                    carsEl.textContent = availableCars;
                    carsEl.classList.toggle('low', availableCars < 2 && availableCars > 0);
                    carsEl.classList.toggle('empty', availableCars === 0);
                }
                if (loadingEl) loadingEl.style.display = 'none';
                if (dataEl) dataEl.style.display = 'flex';
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('[ChapterModal] Hyre availability error:', error);
            if (loadingEl) loadingEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'block';
        }
    }
    
    /**
     * Fetch bus departures for modal accordion
     */
    async function fetchBusDeparturesInModal(stopplaceId, quayId, container) {
        if (!container) return;
        
        const loadingEl = container.querySelector('.bus-departures-loading');
        const dataEl = container.querySelector('.bus-departures-data');
        const errorEl = container.querySelector('.bus-departures-error');
        const listEl = container.querySelector('.bus-departures-list');
        
        if (loadingEl) loadingEl.style.display = 'flex';
        if (dataEl) dataEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        
        // Use WordPress REST API base URL
        const baseUrl = (typeof placyMapbox !== 'undefined' && placyMapbox.restBaseUrl) 
            ? placyMapbox.restBaseUrl 
            : '/wp-json/placy/v1';
        
        try {
            let url = `${baseUrl}/entur/departures/${stopplaceId}`;
            if (quayId) url += `?quay_id=${quayId}`;
            
            const response = await fetch(url);
            if (!response.ok) throw new Error('API error');
            
            const data = await response.json();
            
            if (data.success && data.data && data.data.length > 0) {
                if (listEl) {
                    listEl.innerHTML = '';
                    const departures = data.data.slice(0, 5);
                    
                    departures.forEach(dep => {
                        const li = document.createElement('li');
                        li.className = 'bus-departure-item';
                        
                        const lineEl = document.createElement('span');
                        lineEl.className = 'bus-departure-line';
                        if (dep.line && (dep.line.includes('FB') || dep.line.includes('Værnes'))) {
                            lineEl.classList.add('airport');
                        }
                        lineEl.textContent = dep.line || '?';
                        
                        const destEl = document.createElement('span');
                        destEl.className = 'bus-departure-destination';
                        destEl.textContent = dep.destination || 'Ukjent';
                        
                        const timeEl = document.createElement('span');
                        timeEl.className = 'bus-departure-time';
                        timeEl.textContent = formatDepartureTime(dep.expected_departure || dep.aimed_departure);
                        
                        li.appendChild(lineEl);
                        li.appendChild(destEl);
                        li.appendChild(timeEl);
                        listEl.appendChild(li);
                    });
                }
                if (loadingEl) loadingEl.style.display = 'none';
                if (dataEl) dataEl.style.display = 'block';
            } else if (data.success && (!data.data || data.data.length === 0)) {
                if (listEl) listEl.innerHTML = '<li class="bus-no-departures">Ingen planlagte avganger</li>';
                if (loadingEl) loadingEl.style.display = 'none';
                if (dataEl) dataEl.style.display = 'block';
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('[ChapterModal] Bus departures error:', error);
            if (loadingEl) loadingEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'block';
        }
    }
    
    /**
     * Format departure time for bus display
     */
    function formatDepartureTime(isoString) {
        if (!isoString) return '--';
        try {
            const date = new Date(isoString);
            return date.toLocaleTimeString('nb-NO', { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return '--';
        }
    }
    
    /**
     * Fetch bysykkel availability for modal accordion
     */
    async function fetchBysykkelAvailabilityInModal(stationId, container) {
        if (!container) return;
        
        const loadingEl = container.querySelector('.bysykkel-availability-loading');
        const dataEl = container.querySelector('.bysykkel-availability-data');
        const errorEl = container.querySelector('.bysykkel-availability-error');
        
        if (loadingEl) loadingEl.style.display = 'flex';
        if (dataEl) dataEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        
        // Use WordPress REST API base URL
        const baseUrl = (typeof placyMapbox !== 'undefined' && placyMapbox.restBaseUrl) 
            ? placyMapbox.restBaseUrl 
            : '/wp-json/placy/v1';
        
        try {
            const response = await fetch(`${baseUrl}/bysykkel/availability/${stationId}`);
            if (!response.ok) throw new Error('API error');
            
            const data = await response.json();
            
            if (data.success) {
                const bikesEl = dataEl?.querySelector('.bysykkel-bikes');
                const docksEl = dataEl?.querySelector('.bysykkel-docks');
                
                // API returns bikes_available/docks_available directly, not nested in data
                if (bikesEl) {
                    const bikes = data.bikes_available || 0;
                    bikesEl.textContent = bikes;
                    bikesEl.classList.toggle('low', bikes < 3 && bikes > 0);
                    bikesEl.classList.toggle('empty', bikes === 0);
                }
                if (docksEl) {
                    const docks = data.docks_available || 0;
                    docksEl.textContent = docks;
                }
                
                if (loadingEl) loadingEl.style.display = 'none';
                if (dataEl) dataEl.style.display = 'flex';
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('[ChapterModal] Bysykkel availability error:', error);
            if (loadingEl) loadingEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'block';
        }
    }
    
    /**
     * Populate drawer with legacy allPoints array (fallback)
     * @param {Object} data - Chapter modal data
     */
    function populateWithLegacyPOIList(data) {
        // Show intro section
        const introSection = document.querySelector('.pl-mega-drawer__intro');
        if (introSection) {
            introSection.style.display = 'block';
        }
        
        // Stats
        const totalEl = document.getElementById('drawer-total');
        if (totalEl) {
            totalEl.textContent = (data.totalCount || 0) + ' places found';
        }
        
        updateHighlightCount();
        
        // Ingress
        const ingressEl = document.getElementById('drawer-ingress');
        if (ingressEl) {
            ingressEl.textContent = data.ingress || '';
        }
        
        // Key Takeaways
        populateTakeaways(data.keyTakeaways);
        
        // POI List
        populatePOIList(data.allPoints);
    }
    
    /**
     * Extract POI data from rendered DOM elements
     * Looks for data-poi-* attributes on article/card elements
     * @param {HTMLElement} container - The container with POI blocks
     * @returns {Array} Array of POI objects with coordinates and metadata
     */
    function extractPOIDataFromDOM(container) {
        const points = [];
        
        // Find all POI cards/articles with coordinate data
        const poiElements = container.querySelectorAll('[data-poi-coords], [data-poi-id]');
        
        poiElements.forEach(function(el, index) {
            const coordsAttr = el.getAttribute('data-poi-coords');
            let lat = null, lng = null;
            
            if (coordsAttr) {
                try {
                    const coords = JSON.parse(coordsAttr);
                    if (Array.isArray(coords) && coords.length >= 2) {
                        lat = coords[0];
                        lng = coords[1];
                    }
                } catch (e) {
                    console.warn('Failed to parse POI coords:', coordsAttr);
                }
            }
            
            // Parse pre-calculated travel times (from server-side)
            const travelTimesAttr = el.getAttribute('data-travel-times');
            let travelTimes = null;
            if (travelTimesAttr) {
                try {
                    travelTimes = JSON.parse(travelTimesAttr);
                } catch (e) {
                    console.warn('Failed to parse travel times:', travelTimesAttr);
                }
            }
            
            const point = {
                id: el.getAttribute('data-poi-id') || ('extracted-' + index),
                title: el.getAttribute('data-poi-title') || el.querySelector('h3, h4')?.textContent?.trim() || 'Unknown',
                lat: lat,
                lng: lng,
                icon: el.getAttribute('data-poi-icon') || 'fa-map-marker-alt',
                iconColor: el.getAttribute('data-poi-icon-color') || '#3B82F6',
                image: el.getAttribute('data-poi-image') || '',
                googlePlaceId: el.getAttribute('data-google-place-id') || null,
                travelTimes: travelTimes, // Pre-calculated travel times for walk/bike/car
                element: el // Reference to DOM element for hover/click sync
            };
            
            // Only add points with valid coordinates
            if (lat !== null && lng !== null) {
                points.push(point);
                
                // Store mapping for card-to-marker sync
                poiCardToMarkerMap.set(point.id, el);
                
                // Cache pre-calculated travel times
                if (travelTimes) {
                    travelTimeCache[`${point.id}-walk`] = travelTimes.walk;
                    travelTimeCache[`${point.id}-bike`] = travelTimes.bike;
                    travelTimeCache[`${point.id}-car`] = travelTimes.car;
                }
            }
        });
        
        return points;
    }
    
    /**
     * Attach click/hover handlers to POI cards in theme-story content
     * @param {HTMLElement} container - The container with POI blocks
     */
    function attachPOICardHandlers(container) {
        const poiCards = container.querySelectorAll('[data-poi-id]');
        
        poiCards.forEach(function(card) {
            const pointId = card.getAttribute('data-poi-id');
            
            // Don't add click handler if this is an API accordion card
            // Those are handled by api-accordion.js
            if (card.classList.contains('ns-api-card')) {
                // Only add hover handlers for marker highlighting
                card.addEventListener('mouseenter', function() {
                    highlightMarkerOnHover(pointId, true);
                });
                
                card.addEventListener('mouseleave', function() {
                    highlightMarkerOnHover(pointId, false);
                });
                return;
            }
            
            // Click handler for regular POI cards - show route and highlight marker
            card.addEventListener('click', function(e) {
                // Don't intercept clicks on links inside the card
                if (e.target.tagName === 'A') return;
                
                e.stopPropagation();
                handlePOICardClick(pointId);
            });
            
            // Hover handlers - highlight corresponding marker
            card.addEventListener('mouseenter', function() {
                highlightMarkerOnHover(pointId, true);
            });
            
            card.addEventListener('mouseleave', function() {
                highlightMarkerOnHover(pointId, false);
            });
        });
    }
    
    /**
     * Update highlight count for extracted points
     * @param {Array} points - Array of extracted POI data
     */
    function updateHighlightCountForExtractedPoints(points) {
        const highlightEl = document.getElementById('drawer-highlight');
        if (!highlightEl) return;
        
        let withinBudget = 0;
        
        points.forEach(function(point) {
            if (point.lat && point.lng && currentModal) {
                const distance = calculateDistance(
                    currentModal.originLat,
                    currentModal.originLng,
                    point.lat,
                    point.lng
                );
                
                const speeds = { walk: 5, bike: 15, car: 30 };
                const speed = speeds[currentTravelMode] || 5;
                const timeMinutes = Math.ceil((distance / speed) * 60);
                
                if (timeMinutes <= currentTimeBudget) {
                    withinBudget++;
                }
            }
        });
        
        highlightEl.textContent = withinBudget + ' highlighted within ≤' + currentTimeBudget + ' min';
    }

    /**
     * Populate takeaways from WYSIWYG content
     * @param {string} html - HTML content from WYSIWYG
     */
    function populateTakeaways(html) {
        const listEl = document.getElementById('drawer-takeaways-list');
        const titleEl = document.getElementById('drawer-takeaways-title');
        
        if (!listEl) return;
        
        listEl.innerHTML = '';
        
        if (!html) {
            const takeawaysEl = document.getElementById('drawer-takeaways');
            if (takeawaysEl) {
                takeawaysEl.style.display = 'none';
            }
            return;
        }
        
        // Parse HTML and extract list items
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const items = doc.querySelectorAll('li');
        
        if (items.length === 0) {
            const takeawaysEl = document.getElementById('drawer-takeaways');
            if (takeawaysEl) {
                takeawaysEl.style.display = 'none';
            }
            return;
        }
        
        items.forEach(function(item) {
            const li = document.createElement('li');
            li.innerHTML = `
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
                <span>${item.textContent}</span>
            `;
            listEl.appendChild(li);
        });
        
        if (titleEl) {
            titleEl.textContent = 'Key Takeaways (' + items.length + ')';
        }
        
        const takeawaysEl = document.getElementById('drawer-takeaways');
        if (takeawaysEl) {
            takeawaysEl.style.display = 'block';
            // Open accordion by default
            takeawaysEl.classList.add('is-open');
        }
    }

    /**
     * Populate the POI list
     * @param {Array} points - Array of point data
     */
    function populatePOIList(points) {
        const listEl = document.getElementById('drawer-poi-list');
        if (!listEl || !points) return;
        
        listEl.innerHTML = '';
        
        // Clear card-to-marker mapping
        poiCardToMarkerMap.clear();
        
        points.forEach(function(point, index) {
            const travelTime = getTravelTime(point);
            const isWithinBudget = travelTime <= currentTimeBudget;
            
            const itemEl = document.createElement('div');
            itemEl.className = 'pl-mega-drawer__poi-item' + (isWithinBudget ? '' : ' pl-mega-drawer__poi-item--dimmed');
            itemEl.setAttribute('data-point-id', point.id);
            itemEl.setAttribute('data-point-index', index);
            
            // Build rating stars HTML
            const rating = point.rating || 0;
            const ratingHtml = rating > 0 ? `
                <div class="pl-mega-drawer__poi-rating">
                    <svg class="pl-mega-drawer__poi-star" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="pl-mega-drawer__poi-rating-value">${rating.toFixed(1)}</span>
                </div>
            ` : '';
            
            // Build image HTML - use point image or placeholder
            const imageUrl = point.image || '';
            const imageHtml = imageUrl ? `
                <div class="pl-mega-drawer__poi-image">
                    <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(point.title)}" loading="lazy">
                </div>
            ` : `
                <div class="pl-mega-drawer__poi-image pl-mega-drawer__poi-image--placeholder">
                    ${getCategoryIcon(point.category)}
                </div>
            `;
            
            itemEl.innerHTML = `
                ${imageHtml}
                <div class="pl-mega-drawer__poi-info">
                    <div class="pl-mega-drawer__poi-name-row">
                        <h4 class="pl-mega-drawer__poi-name">${escapeHtml(point.title)}</h4>
                        <span class="pl-mega-drawer__poi-category">${escapeHtml((point.category || 'POI').toUpperCase().replace(/_/g, ' '))}</span>
                        ${ratingHtml}
                    </div>
                    <p class="pl-mega-drawer__poi-desc">${escapeHtml(point.description || '')}</p>
                </div>
                <div class="pl-mega-drawer__poi-time">
                    <div class="pl-mega-drawer__poi-time-chip" data-point-id="${point.id}">
                        ${getTravelModeIcon(currentTravelMode)}
                        <span class="travel-time-value">${travelTime} min</span>
                    </div>
                </div>
            `;
            
            // Add click handler - activate marker and draw route
            itemEl.addEventListener('click', function(e) {
                e.stopPropagation();
                handlePOICardClick(point.id);
            });
            
            // Add hover handlers - highlight corresponding marker
            itemEl.addEventListener('mouseenter', function() {
                highlightMarkerOnHover(point.id, true);
            });
            
            itemEl.addEventListener('mouseleave', function() {
                highlightMarkerOnHover(point.id, false);
            });
            
            listEl.appendChild(itemEl);
        });
        
        // Initialize scroll-based progressive marker activation
        initScrollMarkerActivation();
    }

    /**
     * Get travel time for a point
     * @param {Object} point - Point data
     * @returns {number} Travel time in minutes
     */
    function getTravelTime(point) {
        const cacheKey = `${point.id}-${currentTravelMode}`;
        if (travelTimeCache[cacheKey] !== undefined) {
            return travelTimeCache[cacheKey];
        }
        
        // Fallback: estimate based on distance (if we have coordinates)
        if (currentModal && point.lat && point.lng) {
            const distance = calculateDistance(
                currentModal.originLat,
                currentModal.originLng,
                point.lat,
                point.lng
            );
            
            // Rough estimates: walk 5km/h, bike 15km/h, car 30km/h
            const speeds = {
                walk: 5,
                bike: 15,
                car: 30
            };
            
            const speed = speeds[currentTravelMode] || 5;
            const timeHours = distance / speed;
            const timeMinutes = Math.ceil(timeHours * 60);
            
            return Math.max(1, timeMinutes);
        }
        
        return 5; // Default fallback
    }

    /**
     * Calculate distance between two points using Haversine formula
     * @param {number} lat1 - Latitude 1
     * @param {number} lng1 - Longitude 1
     * @param {number} lat2 - Latitude 2
     * @param {number} lng2 - Longitude 2
     * @returns {number} Distance in kilometers
     */
    function calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng / 2) * Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    /**
     * Calculate travel times using Mapbox Directions API
     * First checks for pre-calculated values from server, then falls back to API
     * @param {Object} data - Chapter modal data
     */
    async function calculateTravelTimes(data) {
        // Support both theme-story extracted points and legacy allPoints
        const pointsToUse = data.extractedPoints || data.allPoints || [];
        if (!data || !pointsToUse.length) return;
        
        const origin = { lat: data.originLat, lng: data.originLng };
        
        // First pass: use pre-calculated values from cache (populated from data-travel-times)
        const pointsNeedingAPI = [];
        
        pointsToUse.forEach(function(point) {
            if (!point.lat || !point.lng) return;
            
            const cacheKey = `${point.id}-${currentTravelMode}`;
            
            // Check if we have pre-calculated values
            if (travelTimeCache[cacheKey] !== undefined) {
                // Update UI immediately with cached value
                updatePointTravelTime(point.id, travelTimeCache[cacheKey]);
            } else {
                // Queue for API fetch
                pointsNeedingAPI.push(point);
            }
        });
        
        // If all points have pre-calculated values, we're done
        if (pointsNeedingAPI.length === 0) {
            console.log('[ChapterModal] All travel times from cache, no API calls needed');
            return;
        }
        
        console.log('[ChapterModal] Fetching travel times for', pointsNeedingAPI.length, 'points via API');
        
        // Second pass: fetch from API for points without cached values
        const batchSize = 5;
        
        for (let i = 0; i < pointsNeedingAPI.length; i += batchSize) {
            const batch = pointsNeedingAPI.slice(i, i + batchSize);
            
            await Promise.all(batch.map(async function(point) {
                try {
                    const result = await fetchDirections(
                        origin.lng, origin.lat,
                        point.lng, point.lat,
                        currentTravelMode
                    );
                    
                    if (result && result.duration) {
                        const minutes = Math.ceil(result.duration / 60);
                        const cacheKey = `${point.id}-${currentTravelMode}`;
                        travelTimeCache[cacheKey] = minutes;
                        
                        // Update UI
                        updatePointTravelTime(point.id, minutes);
                    }
                } catch (error) {
                    console.warn('Failed to fetch directions for point:', point.id, error);
                }
            }));
            
            // Small delay between batches
            if (i + batchSize < pointsNeedingAPI.length) {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        }
        
        // Update highlight count after all times calculated
        updateHighlightCount();
        updatePOIListStyling();
    }

    /**
     * Fetch directions from Mapbox API via proxy
     * @param {number} originLng - Origin longitude
     * @param {number} originLat - Origin latitude
     * @param {number} destLng - Destination longitude
     * @param {number} destLat - Destination latitude
     * @param {string} mode - Travel mode (walking, cycling, driving)
     * @returns {Promise<Object>} Directions result
     */
    async function fetchDirections(originLng, originLat, destLng, destLat, mode) {
        const params = new URLSearchParams({
            origin_lng: originLng,
            origin_lat: originLat,
            dest_lng: destLng,
            dest_lat: destLat,
            mode: mode
        });
        
        // Use placyMapbox.restUrl for correct WordPress installation path
        const baseUrl = (typeof placyMapbox !== 'undefined' && placyMapbox.restUrl) 
            ? placyMapbox.restUrl 
            : '/wp-json/placy/v1/travel-calc';
        
        const response = await fetch(baseUrl + '/directions?' + params.toString());
        
        if (!response.ok) {
            throw new Error('Directions request failed');
        }
        
        const data = await response.json();
        
        // Extract the first route from the Mapbox response format
        if (data.routes && data.routes.length > 0) {
            return {
                geometry: data.routes[0].geometry,
                duration: data.routes[0].duration,
                distance: data.routes[0].distance
            };
        }
        
        return null;
    }

    /**
     * Update travel time display for a specific point
     * @param {number} pointId - Point ID
     * @param {number} minutes - Travel time in minutes
     */
    function updatePointTravelTime(pointId, minutes) {
        // Update in modal POI list (new drawer structure)
        const timeChip = document.querySelector(`.pl-mega-drawer__poi-time-chip[data-point-id="${pointId}"] .travel-time-value`);
        if (timeChip) {
            timeChip.textContent = minutes + ' min';
        }
        
        // Update in chapter POI cards (legacy structure)
        const poiCard = document.querySelector(`.pl-poi-card[data-point-id="${pointId}"] .pl-poi-card__travel-time`);
        if (poiCard) {
            poiCard.textContent = minutes + ' min';
        }
        
        // Update in theme-story injected blocks (poi-list, poi-gallery, etc.)
        const drawer = document.getElementById('pl-mega-drawer');
        if (drawer) {
            // Find the POI card by data-poi-id and update .poi-walking-time
            const themeStoryCard = drawer.querySelector(`[data-poi-id="${pointId}"] .poi-walking-time`);
            if (themeStoryCard) {
                themeStoryCard.textContent = minutes + ' min';
            }
            
            // Also handle poi-highlight and poi-list-item structures
            const highlightCard = drawer.querySelector(`.poi-highlight[data-poi-id="${pointId}"] .poi-walking-time`);
            if (highlightCard) {
                highlightCard.textContent = minutes + ' min';
            }
        }
    }

    /**
     * Calculate initial travel times for POI cards in chapters
     */
    function calculateInitialTravelTimes() {
        // For each chapter with modal data
        Object.keys(window.placyChapterModals).forEach(function(chapterId) {
            const data = window.placyChapterModals[chapterId];
            if (!data || !data.highlightedPoints) return;
            
            const origin = { lat: data.originLat, lng: data.originLng };
            
            data.highlightedPoints.forEach(async function(point) {
                if (!point.lat || !point.lng) return;
                
                try {
                    const result = await fetchDirections(
                        origin.lng, origin.lat,
                        point.lng, point.lat,
                        'walk' // Default to walk for initial load
                    );
                    
                    if (result && result.duration) {
                        const minutes = Math.ceil(result.duration / 60);
                        travelTimeCache[`${point.id}-walk`] = minutes;
                        updatePointTravelTime(point.id, minutes);
                    }
                } catch (error) {
                    console.warn('Failed to fetch initial travel time:', error);
                }
            });
        });
    }

    /**
     * Update highlight count in drawer
     */
    function updateHighlightCount() {
        if (!currentModal) return;
        
        // Guard against missing allPoints
        if (!currentModal.allPoints || !Array.isArray(currentModal.allPoints)) {
            const highlightEl = document.getElementById('drawer-highlight');
            if (highlightEl) {
                highlightEl.textContent = '0 highlighted within ≤' + currentTimeBudget + ' min';
            }
            return;
        }
        
        let count = 0;
        currentModal.allPoints.forEach(function(point) {
            const travelTime = getTravelTime(point);
            if (travelTime <= currentTimeBudget) {
                count++;
            }
        });
        
        const highlightEl = document.getElementById('drawer-highlight');
        if (highlightEl) {
            highlightEl.textContent = count + ' highlighted within ≤' + currentTimeBudget + ' min';
        }
    }

    /**
     * Update POI list styling based on time budget
     */
    function updatePOIListStyling() {
        if (!currentModal) return;
        
        const items = document.querySelectorAll('.pl-mega-drawer__poi-item');
        items.forEach(function(item) {
            const pointId = item.getAttribute('data-point-id');
            const point = currentModal.allPoints.find(p => p.id == pointId);
            if (!point) return;
            
            const travelTime = getTravelTime(point);
            const isWithinBudget = travelTime <= currentTimeBudget;
            
            item.classList.toggle('pl-mega-drawer__poi-item--dimmed', !isWithinBudget);
        });
    }

    /**
     * Set travel mode
     * @param {string} mode - Travel mode (walk, bike, car)
     */
    window.setTravelMode = function(mode) {
        currentTravelMode = mode;
        updateControlButtons();
        
        if (currentModal) {
            populatePOIList(currentModal.allPoints);
            
            // Update all POI cards with pre-calculated travel times
            updateAllPOICardTravelTimes(mode);
            
            calculateTravelTimes(currentModal);
            updateDrawerMapMarkers();
            clearDrawerRoute(); // Clear route when mode changes
        }
        
        // Update global state
        if (window.PlacyGlobalState) {
            window.PlacyGlobalState.travelMode = mode;
        }
        
        // Emit global event for other components
        document.dispatchEvent(new CustomEvent('placy:travelModeChange', {
            detail: { mode: mode, travelMode: mode, source: 'chapterModal' }
        }));
        
        console.log('[ChapterModal] Travel mode set to:', mode);
    };
    
    /**
     * Update all POI cards' travel times and icons based on travel mode
     * Uses pre-calculated values from data-travel-times attribute
     * @param {string} mode - Travel mode (walk, bike, car)
     */
    function updateAllPOICardTravelTimes(mode) {
        // Icons for each travel mode
        const icons = {
            walk: '<i class="fas fa-walking"></i>',
            bike: '<i class="fas fa-bicycle"></i>',
            car: '<i class="fas fa-car"></i>'
        };
        
        // Mode labels for display
        const labels = {
            walk: 'walk',
            bike: 'bike',
            car: 'car'
        };
        
        // Find all POI cards with pre-calculated travel times
        const drawer = document.getElementById('pl-mega-drawer');
        if (!drawer) return;
        
        const poiCards = drawer.querySelectorAll('[data-travel-times]');
        
        poiCards.forEach(function(card) {
            try {
                const travelTimes = JSON.parse(card.getAttribute('data-travel-times'));
                const time = travelTimes[mode];
                const pointId = card.getAttribute('data-poi-id');
                
                if (time !== undefined) {
                    // Update the travel time text
                    const timeEl = card.querySelector('.poi-walking-time');
                    if (timeEl) {
                        timeEl.textContent = time + ' min';
                    }
                    
                    // Update the travel icon
                    const iconEl = card.querySelector('.poi-travel-icon');
                    if (iconEl) {
                        iconEl.innerHTML = icons[mode] || icons.walk;
                    }
                    
                    // Update cache for this point
                    if (pointId) {
                        travelTimeCache[`${pointId}-${mode}`] = time;
                    }
                }
            } catch (e) {
                // Ignore parse errors
            }
        });
    }

    /**
     * Set time budget
     * @param {number} budget - Time budget in minutes
     */
    window.setTimeBudget = function(budget) {
        currentTimeBudget = budget;
        updateControlButtons();
        updateHighlightCount();
        updatePOIListStyling();
        updateDrawerMapMarkers();
        
        // Update global state
        if (window.PlacyGlobalState) {
            window.PlacyGlobalState.timeBudget = budget;
        }
        
        // Emit global event for other components
        document.dispatchEvent(new CustomEvent('placy:timeBudgetChange', {
            detail: { budget: budget, timeBudget: budget, source: 'chapterModal' }
        }));
        
        console.log('[ChapterModal] Time budget set to:', budget);
    };

    /**
     * Update control button states
     */
    function updateControlButtons() {
        // Travel mode buttons
        document.querySelectorAll('#travel-mode-btns [data-travel-mode]').forEach(function(btn) {
            const isActive = btn.dataset.travelMode === currentTravelMode;
            btn.classList.toggle('is-active', isActive);
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });
        
        // Time budget buttons
        document.querySelectorAll('#time-budget-btns [data-time-budget]').forEach(function(btn) {
            const isActive = parseInt(btn.dataset.timeBudget, 10) === currentTimeBudget;
            btn.classList.toggle('is-active', isActive);
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });
    }

    /**
     * Toggle takeaways accordion
     */
    window.toggleTakeaways = function() {
        const takeawaysEl = document.getElementById('drawer-takeaways');
        if (takeawaysEl) {
            takeawaysEl.classList.toggle('is-open');
        }
    };

    /**
     * Filter POI list by search query
     */
    window.filterPOIList = function() {
        const searchInput = document.getElementById('drawer-search');
        const query = searchInput ? searchInput.value.toLowerCase() : '';
        
        const items = document.querySelectorAll('.pl-mega-drawer__poi-item');
        items.forEach(function(item) {
            const name = item.querySelector('.pl-mega-drawer__poi-name');
            const category = item.querySelector('.pl-mega-drawer__poi-category');
            const desc = item.querySelector('.pl-mega-drawer__poi-desc');
            
            const searchText = [
                name ? name.textContent : '',
                category ? category.textContent : '',
                desc ? desc.textContent : ''
            ].join(' ').toLowerCase();
            
            const matches = !query || searchText.includes(query);
            item.style.display = matches ? '' : 'none';
        });
    };

    // Store drawer map markers for route drawing
    let drawerMapMarkers = [];
    let drawerActiveMarker = null;

    /**
     * Initialize map in drawer
     * @param {Object} data - Chapter modal data
     */
    function initializeDrawerMap(data) {
        const mapContainer = document.getElementById('drawer-map-container');
        if (!mapContainer) return;
        
        // Reset markers array
        drawerMapMarkers = [];
        drawerActiveMarker = null;
        
        // Determine which points to use - extractedPoints from theme-story or allPoints
        const pointsToUse = data.extractedPoints || data.allPoints || [];
        
        // Check if Mapbox is available
        if (typeof mapboxgl !== 'undefined' && typeof placyMapbox !== 'undefined') {
            mapboxgl.accessToken = placyMapbox.accessToken;
            
            // Remove existing map if any
            if (window.placyDrawerMap) {
                window.placyDrawerMap.remove();
            }
            
            // Hide map container until fitBounds is complete to prevent visual jump
            mapContainer.style.opacity = '0';
            
            // Pre-calculate bounds to set initial view correctly
            const bounds = new mapboxgl.LngLatBounds();
            bounds.extend([data.originLng, data.originLat]);
            pointsToUse.forEach(function(point) {
                if (point.lat && point.lng) {
                    bounds.extend([point.lng, point.lat]);
                }
            });
            
            // Calculate center from bounds
            const boundsCenter = bounds.getCenter();
            
            // Create new map with bird's eye view (top-down, no 3D tilt)
            // Use bounds center as initial center for smoother load
            window.placyDrawerMap = new mapboxgl.Map({
                container: mapContainer,
                style: 'mapbox://styles/mapbox/light-v11',
                center: [boundsCenter.lng, boundsCenter.lat],
                zoom: 14,        // Start with reasonable zoom, will be adjusted by fitBounds
                minZoom: 12,
                maxZoom: 18,
                pitch: 0,        // Bird's eye view - no tilt
                bearing: 0,      // North up
                antialias: true
            });
            
            // Add navigation controls
            window.placyDrawerMap.addControl(new mapboxgl.NavigationControl({ showCompass: true }), 'top-right');
            
            // Wait for map to load before adding markers
            window.placyDrawerMap.on('load', function() {
                // Note: 3D buildings removed - using bird's eye view now

                // Add origin marker (property/start point)
                const originEl = document.createElement('div');
                originEl.className = 'pl-mega-drawer__origin-marker';
                
                // Build origin marker content - use property logo if available, otherwise fallback to icon
                let originContent = '';
                if (data.propertyLogo) {
                    // Property has a logo - show it with optional label
                    originContent = `
                        <div class="pl-mega-drawer__origin-dot pl-mega-drawer__origin-dot--with-logo">
                            <img src="${data.propertyLogo}" alt="${data.propertyLabel || 'Eiendom'}" class="pl-mega-drawer__origin-logo" />
                        </div>
                        ${data.propertyLabel ? `<div class="pl-mega-drawer__origin-label">${data.propertyLabel}</div>` : ''}
                    `;
                } else {
                    // No logo - use default location icon
                    originContent = `
                        <div class="pl-mega-drawer__origin-dot">
                            <svg fill="currentColor" viewBox="0 0 24 24" width="16" height="16">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        </div>
                    `;
                }
                originEl.innerHTML = originContent;
                
                new mapboxgl.Marker({ element: originEl, anchor: 'bottom' })
                    .setLngLat([data.originLng, data.originLat])
                    .addTo(window.placyDrawerMap);
                
                // Clear previous card-to-marker mapping
                poiCardToMarkerMap.clear();
                
                // Add POI markers (start in compact/minimized state)
                pointsToUse.forEach(function(point) {
                    if (!point.lat || !point.lng) return;
                    
                    const travelTime = getTravelTime(point);
                    const isWithinBudget = travelTime <= currentTimeBudget;
                    
                    const el = document.createElement('div');
                    // Start in compact state - only show dot, hide label and icon
                    el.className = 'pl-mega-drawer__map-marker pl-mega-drawer__map-marker--compact' + (isWithinBudget ? '' : ' pl-mega-drawer__map-marker--dimmed');
                    el.setAttribute('data-point-id', point.id);
                    
                    // Use category icon from data or fallback
                    const iconHtml = point.icon ? `<i class="fa-solid ${point.icon}" style="color: white;"></i>` : getCategoryIcon(point.category);
                    
                    // Get point name for tooltip label
                    const pointName = point.name || point.title || 'Sted';
                    
                    el.innerHTML = `
                        <div class="pl-mega-drawer__map-marker-dot" style="${point.iconColor ? 'background-color:' + point.iconColor : ''}">
                            <span class="pl-mega-drawer__map-marker-icon">${iconHtml}</span>
                        </div>
                        <div class="pl-mega-drawer__map-marker-label">${pointName}</div>
                    `;
                    
                    const marker = new mapboxgl.Marker({ element: el, anchor: 'bottom' })
                        .setLngLat([point.lng, point.lat])
                        .addTo(window.placyDrawerMap);
                    
                    // Store marker reference with point data
                    const markerData = {
                        marker: marker,
                        element: el,
                        point: point,
                        lng: point.lng,
                        lat: point.lat
                    };
                    drawerMapMarkers.push(markerData);
                    
                    // Add click handler for route drawing
                    el.addEventListener('click', function(e) {
                        e.stopPropagation();
                        handleMarkerClick(point, el, data);
                    });
                    
                    // Hover effects - use classes instead of inline transform to not interfere with Mapbox positioning
                    el.addEventListener('mouseenter', function() {
                        if (el !== drawerActiveMarker) {
                            el.classList.add('pl-mega-drawer__map-marker--hover');
                            // Also highlight corresponding POI card
                            highlightPOICardOnHover(point.id, true);
                        }
                    });
                    
                    el.addEventListener('mouseleave', function() {
                        if (el !== drawerActiveMarker) {
                            el.classList.remove('pl-mega-drawer__map-marker--hover');
                            // Remove highlight from POI card
                            highlightPOICardOnHover(point.id, false);
                        }
                    });
                });
                
                // Fit bounds using pre-calculated bounds (already computed before map creation)
                window.placyDrawerMap.fitBounds(bounds, { 
                    padding: 80,
                    pitch: 0,        // Bird's eye view
                    bearing: 0,      // North up
                    maxZoom: 17,
                    animate: false   // No zoom animation on load - reduce UI noise
                });
                
                // Show map now that it's properly positioned
                mapContainer.style.opacity = '1';
                mapContainer.style.transition = 'opacity 0.2s ease-out';
            });
        } else {
            // Fallback: show placeholder map with markers
            mapContainer.innerHTML = createPlaceholderMap(data);
        }
    }
    
    /**
     * Handle marker click - show route and highlight
     * @param {Object} point - Point data
     * @param {HTMLElement} markerEl - Marker element
     * @param {Object} data - Chapter modal data
     */
    function handleMarkerClick(point, markerEl, data) {
        // Deactivate previous marker
        if (drawerActiveMarker) {
            drawerActiveMarker.classList.remove('pl-mega-drawer__map-marker--active');
            drawerActiveMarker.classList.remove('pl-mega-drawer__map-marker--hover');
        }
        
        // Activate this marker
        drawerActiveMarker = markerEl;
        markerEl.classList.add('pl-mega-drawer__map-marker--active');
        markerEl.classList.remove('pl-mega-drawer__map-marker--hover');
        
        // Fit bounds to show origin and clicked point while preserving 3D perspective
        const bounds = new mapboxgl.LngLatBounds();
        bounds.extend([data.originLng, data.originLat]);
        bounds.extend([point.lng, point.lat]);
        
        window.placyDrawerMap.fitBounds(bounds, {
            padding: 80,
            duration: 500,   // Calmer, shorter animation
            maxZoom: 17,
            pitch: 0,        // Bird's eye view
            bearing: 0       // North up
        });
        
        // Draw route from origin to point
        drawRouteToPoint(data.originLng, data.originLat, point.lng, point.lat, point);
        
        // Highlight corresponding item in list AFTER map animation completes
        // Map animation duration is 500ms, add small buffer for smoother UX
        setTimeout(function() {
            highlightPOIInList(point.id);
        }, 550);
    }
    
    /**
     * Draw walking route from origin to point
     * @param {number} originLng - Origin longitude
     * @param {number} originLat - Origin latitude
     * @param {number} destLng - Destination longitude
     * @param {number} destLat - Destination latitude
     * @param {Object} point - Point data
     */
    async function drawRouteToPoint(originLng, originLat, destLng, destLat, point) {
        // Clear existing route
        clearDrawerRoute();
        
        try {
            const result = await fetchDirections(originLng, originLat, destLng, destLat, currentTravelMode);
            
            if (result && result.geometry) {
                // Add route source
                window.placyDrawerMap.addSource('drawer-route', {
                    type: 'geojson',
                    data: {
                        type: 'Feature',
                        properties: {},
                        geometry: result.geometry
                    }
                });
                
                // Add route layer
                window.placyDrawerMap.addLayer({
                    id: 'drawer-route',
                    type: 'line',
                    source: 'drawer-route',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    paint: {
                        'line-color': '#2563eb',
                        'line-width': 4,
                        'line-opacity': 0.8,
                        'line-dasharray': [0.001, 2]
                    }
                });
                
                // Add duration label at midpoint
                if (result.duration && result.geometry.coordinates && result.geometry.coordinates.length > 1) {
                    const coords = result.geometry.coordinates;
                    const midIndex = Math.floor(coords.length / 2);
                    const midPoint = coords[midIndex];
                    const minutes = Math.ceil(result.duration / 60);
                    
                    // Create duration marker
                    const durationEl = document.createElement('div');
                    durationEl.className = 'pl-mega-drawer__route-duration';
                    durationEl.innerHTML = `
                        ${getTravelModeIcon(currentTravelMode)}
                        <span>${minutes} min</span>
                    `;
                    
                    new mapboxgl.Marker({ element: durationEl, anchor: 'center' })
                        .setLngLat(midPoint)
                        .addTo(window.placyDrawerMap);
                }
            }
        } catch (error) {
            console.warn('Failed to draw route:', error);
        }
    }
    
    /**
     * Clear existing route from drawer map
     */
    function clearDrawerRoute() {
        if (!window.placyDrawerMap) return;
        
        // Remove route layer and source if they exist
        if (window.placyDrawerMap.getLayer('drawer-route')) {
            window.placyDrawerMap.removeLayer('drawer-route');
        }
        if (window.placyDrawerMap.getSource('drawer-route')) {
            window.placyDrawerMap.removeSource('drawer-route');
        }
        
        // Remove duration markers
        document.querySelectorAll('.pl-mega-drawer__route-duration').forEach(function(el) {
            el.remove();
        });
    }
    
    /**
     * Highlight POI in list when marker is clicked
     * @param {string} pointId - Point ID
     */
    function highlightPOIInList(pointId) {
        // Remove highlight from all items (all card variants)
        const highlightClasses = ['pl-mega-drawer__poi-item--highlighted', 'ns-api-card--highlighted', 'poi-card--highlighted'];
        document.querySelectorAll('.pl-mega-drawer__poi-item, .ns-api-card, .poi-list-card, .poi-list-item, [data-poi-id]').forEach(function(item) {
            highlightClasses.forEach(function(cls) {
                item.classList.remove(cls);
            });
        });
        
        // Try to find matching item - check all variants of POI cards
        let item = document.querySelector(`.pl-mega-drawer__poi-item[data-point-id="${pointId}"]`);
        if (!item) {
            item = document.querySelector(`.ns-api-card[data-poi-id="${pointId}"]`);
        }
        if (!item) {
            // Theme-story blocks use poi-list-card or poi-list-item
            item = document.querySelector(`.poi-list-card[data-poi-id="${pointId}"]`);
        }
        if (!item) {
            item = document.querySelector(`.poi-list-item[data-poi-id="${pointId}"]`);
        }
        if (!item) {
            // Generic fallback - any element with data-poi-id
            item = document.querySelector(`[data-poi-id="${pointId}"]`);
        }
        
        if (item) {
            // Add highlight class
            item.classList.add('poi-card--highlighted');
            
            // Scroll the container to show the item - but only if it's not already fully visible
            const scrollContainer = document.querySelector('.pl-mega-drawer__scroll');
            if (scrollContainer) {
                const containerRect = scrollContainer.getBoundingClientRect();
                const itemRect = item.getBoundingClientRect();
                
                // Check if item is already fully visible in the viewport
                const isFullyVisible = itemRect.top >= containerRect.top && 
                                      itemRect.bottom <= containerRect.bottom;
                
                // Only scroll if item is not fully visible
                if (!isFullyVisible) {
                    const scrollTop = scrollContainer.scrollTop;
                    
                    // Calculate target scroll position (center the item in view)
                    const targetScroll = scrollTop + itemRect.top - containerRect.top - (containerRect.height / 2) + (itemRect.height / 2);
                    
                    scrollContainer.scrollTo({
                        top: targetScroll,
                        behavior: 'smooth'
                    });
                }
            }
        } else {
            console.warn('[ChapterModal] highlightPOIInList: Could not find element for pointId:', pointId);
        }
    }
    
    /**
     * Initialize scroll-based progressive marker activation
     * Markers activate (expand from compact) as their corresponding cards scroll into view
     */
    function initScrollMarkerActivation() {
        // Disconnect previous observer if exists
        if (poiScrollObserver) {
            poiScrollObserver.disconnect();
        }
        
        const scrollContainer = document.querySelector('.pl-mega-drawer__scroll');
        if (!scrollContainer) return;
        
        // Observer options - trigger when card is in center 40% of scroll area
        const observerOptions = {
            root: scrollContainer,
            rootMargin: '-30% 0px -30% 0px',
            threshold: 0.1
        };
        
        poiScrollObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                // Support both data-point-id and data-poi-id attributes
                const pointId = entry.target.getAttribute('data-point-id') || entry.target.getAttribute('data-poi-id');
                if (!pointId) return;
                
                // Find corresponding marker
                const markerData = drawerMapMarkers.find(m => m.point.id == pointId);
                if (!markerData) return;
                
                if (entry.isIntersecting) {
                    // Card is in center zone - expand marker permanently (once activated, stays activated)
                    markerData.element.classList.remove('pl-mega-drawer__map-marker--compact');
                    markerData.element.classList.add('pl-mega-drawer__map-marker--activated');
                }
                // Note: We intentionally do NOT collapse markers back to compact
                // Once a marker is activated, it stays that way to reduce UI noise
            });
        }, observerOptions);
        
        // Observe all POI card types (legacy, theme-story, API cards)
        const poiSelectors = '.pl-mega-drawer__poi-item, .poi-list-card[data-poi-id], .poi-list-item[data-poi-id], .ns-api-card[data-poi-id], [data-poi-id]';
        document.querySelectorAll(poiSelectors).forEach(function(item) {
            poiScrollObserver.observe(item);
        });
    }
    
    /**
     * Handle POI card click - activate marker, draw route, zoom map
     * @param {string} pointId - Point ID
     */
    function handlePOICardClick(pointId) {
        if (!currentModal) return;
        
        // Find the point data - use extractedPoints for theme-story modals or allPoints for legacy
        const points = currentModal.extractedPoints || currentModal.allPoints || [];
        const point = points.find(p => p.id == pointId);
        if (!point) return;
        
        // Find the marker data
        const markerData = drawerMapMarkers.find(m => m.point.id == pointId);
        if (!markerData) return;
        
        // Trigger the marker click handler (reuse existing logic)
        handleMarkerClick(point, markerData.element, currentModal);
    }
    
    /**
     * Highlight marker on map when hovering over POI card
     * @param {string} pointId - Point ID
     * @param {boolean} highlight - Whether to highlight or remove highlight
     */
    function highlightMarkerOnHover(pointId, highlight) {
        const markerData = drawerMapMarkers.find(m => m.point.id == pointId);
        if (!markerData) return;
        
        if (highlight) {
            // Don't override active marker
            if (markerData.element !== drawerActiveMarker) {
                markerData.element.classList.add('pl-mega-drawer__map-marker--hover');
                // Expand marker temporarily
                markerData.element.classList.remove('pl-mega-drawer__map-marker--compact');
            }
        } else {
            // Only remove hover if not active
            if (markerData.element !== drawerActiveMarker) {
                markerData.element.classList.remove('pl-mega-drawer__map-marker--hover');
                // Don't re-compact if already activated by scroll
                if (!markerData.element.classList.contains('pl-mega-drawer__map-marker--activated')) {
                    markerData.element.classList.add('pl-mega-drawer__map-marker--compact');
                }
            }
        }
    }
    
    /**
     * Highlight POI card when hovering over marker
     * @param {string} pointId - Point ID
     * @param {boolean} highlight - Whether to highlight or remove highlight
     */
    function highlightPOICardOnHover(pointId, highlight) {
        const card = document.querySelector(`.pl-mega-drawer__poi-item[data-point-id="${pointId}"]`);
        if (!card) return;
        
        if (highlight) {
            card.classList.add('pl-mega-drawer__poi-item--hover');
        } else {
            card.classList.remove('pl-mega-drawer__poi-item--hover');
        }
    }
    
    /**
     * Update drawer map markers based on time budget
     */
    function updateDrawerMapMarkers() {
        drawerMapMarkers.forEach(function(markerData) {
            const travelTime = getTravelTime(markerData.point);
            const isWithinBudget = travelTime <= currentTimeBudget;
            
            if (isWithinBudget) {
                markerData.element.classList.remove('pl-mega-drawer__map-marker--dimmed');
            } else {
                markerData.element.classList.add('pl-mega-drawer__map-marker--dimmed');
            }
            
            // Update label
            const label = markerData.element.querySelector('.pl-mega-drawer__map-marker-label');
            if (label) {
                label.textContent = travelTime + ' min';
            }
        });
    }

    /**
     * Create placeholder map HTML when Mapbox is not available
     * @param {Object} data - Chapter modal data
     * @returns {string} HTML string
     */
    function createPlaceholderMap(data) {
        let markersHtml = '';
        const pointsToUse = data.extractedPoints || data.allPoints || [];
        
        pointsToUse.slice(0, 8).forEach(function(point, i) {
            const travelTime = getTravelTime(point);
            const isWithinBudget = travelTime <= currentTimeBudget;
            const top = 30 + (i * 10) + (Math.sin(i) * 15);
            const left = 20 + (i * 12) + (Math.cos(i) * 12);
            
            markersHtml += `
                <div class="pl-mega-drawer__map-marker${isWithinBudget ? '' : ' pl-mega-drawer__map-marker--dimmed'}"
                     style="top: ${top}%; left: ${left}%;">
                    <div class="pl-mega-drawer__map-marker-dot">
                        ${getCategoryIcon(point.category)}
                    </div>
                    <div class="pl-mega-drawer__map-marker-label">${travelTime} min</div>
                </div>
            `;
        });
        
        return `
            <div style="position: absolute; inset: 0; background: #e5e7eb;">
                <div style="width: 100%; height: 100%; opacity: 0.4; background-image: radial-gradient(#9ca3af 1px, transparent 1px); background-size: 20px 20px;"></div>
                ${markersHtml}
            </div>
        `;
    }

    /**
     * Get SVG icon for category
     * @param {string} category - Category name
     * @returns {string} SVG HTML
     */
    function getCategoryIcon(category) {
        const icons = {
            train: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 15.5C4 17.985 6.015 20 8.5 20L7 22m9-2l-1.5-2M12 4v0a3 3 0 013 3v9a3 3 0 01-3 3v0"/></svg>',
            bus: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 18v2a1 1 0 001 1h2a1 1 0 001-1v-2m8 0v2a1 1 0 001 1h2a1 1 0 001-1v-2M7 8h10"/></svg>',
            bike: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/></svg>',
            restaurant: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/></svg>',
            cafe: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 8h1a4 4 0 010 8h-1M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z"/></svg>',
            hotel: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>',
            shopping: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
            nature: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22V8m0 0l-3 3m3-3l3 3"/></svg>',
            gym: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6.5 6.5L17.5 17.5M6.5 17.5L17.5 6.5"/></svg>',
            default: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
        };
        
        const cat = (category || '').toLowerCase();
        return icons[cat] || icons.default;
    }

    /**
     * Get travel mode icon SVG
     * @param {string} mode - Travel mode
     * @returns {string} SVG HTML
     */
    function getTravelModeIcon(mode) {
        const icons = {
            walking: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 6a2 2 0 100-4 2 2 0 000 4zm-1 2L8 14l-2-1m6-5l4 4m-4-4l-4 4m0 6l2-2m8-4l-4 4-2-2"/></svg>',
            cycling: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M15 6a1 1 0 100-2 1 1 0 000 2zm-3 11.5V14l-3-3 4-4 2 2h3"/></svg>',
            driving: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m-4 0H3v-4l2-4h10l2 4v4h-2m-8 0h8m0 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>'
        };
        
        return icons[mode] || icons.walking;
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show POI on map - activates marker, draws route, scrolls to card
     * Called from "Se på kart" buttons throughout the app
     * @param {HTMLElement} button - The button element that was clicked
     */
    function showPOIOnMap(button) {
        if (!currentModal) {
            console.warn('[ChapterModal] showPOIOnMap called but no modal is active');
            return;
        }

        // Try to get point ID from button itself first, then from closest parent
        let pointId = button.getAttribute('data-poi-id');
        
        if (!pointId) {
            const poiElement = button.closest('[data-poi-id]');
            if (poiElement) {
                pointId = poiElement.getAttribute('data-poi-id');
            }
        }

        if (!pointId) {
            console.warn('[ChapterModal] No data-poi-id found on button or parent');
            return;
        }

        console.log('[ChapterModal] showPOIOnMap triggered for point:', pointId);

        // Use existing handlePOICardClick function to activate marker and draw route
        handlePOICardClick(pointId);
    }

    // Expose showPOIOnMap globally for inline onclick handlers
    window.showPOIOnMap = showPOIOnMap;

    /**
     * Scroll to and activate a specific POI in the modal by ID
     * Finds matching POI card, scrolls to it, and activates map marker
     * @param {string|number} poiId - The POI ID to find and activate
     */
    function scrollToAndActivatePOI(poiId) {
        if (!poiId) return;
        
        const drawer = document.getElementById('pl-mega-drawer');
        if (!drawer) return;
        
        // Convert to string for comparison
        const targetId = String(poiId);
        
        // Try to find POI element with matching data-poi-id
        // Support multiple selectors for different POI card types
        const selectors = [
            `[data-poi-id="${targetId}"]`,
            `.poi-list-card[data-poi-id="${targetId}"]`,
            `.poi-list-item[data-poi-id="${targetId}"]`,
            `.ns-api-card[data-poi-id="${targetId}"]`,
            `.poi-api-accordion[data-poi-id="${targetId}"]`,
            `.poi-highlight[data-poi-id="${targetId}"]`
        ];
        
        let targetElement = null;
        for (const selector of selectors) {
            targetElement = drawer.querySelector(selector);
            if (targetElement) break;
        }
        
        if (!targetElement) {
            console.warn('[ChapterModal] Could not find POI element with ID:', targetId);
            return;
        }
        
        console.log('[ChapterModal] Scrolling to and activating POI:', targetId);
        
        // Scroll to the element
        const scrollContainer = drawer.querySelector('.pl-mega-drawer__scroll');
        if (scrollContainer) {
            // Calculate offset to center the element
            const elementTop = targetElement.offsetTop;
            const containerHeight = scrollContainer.clientHeight;
            const elementHeight = targetElement.offsetHeight;
            const scrollTop = elementTop - (containerHeight / 2) + (elementHeight / 2);
            
            scrollContainer.scrollTo({
                top: Math.max(0, scrollTop),
                behavior: 'smooth'
            });
        }
        
        // Activate the POI on the map
        setTimeout(function() {
            handlePOICardClick(targetId);
        }, 300);
        
        // Add visual highlight to the card
        targetElement.classList.add('poi-card-highlighted');
        setTimeout(function() {
            targetElement.classList.remove('poi-card-highlighted');
        }, 2000);
    }

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeChapterMegaModal();
        }
    });

})();
