/**
 * API Accordion Controller
 * 
 * Handles accordion expand/collapse and API data loading
 * for Entur, Bysykkel, and Hyre integrations.
 * 
 * Multiple accordions can be open simultaneously.
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Configuration - Get REST base URL from WordPress or fallback
    const restBase = (typeof placyMapbox !== 'undefined' && placyMapbox.restBaseUrl)
        ? placyMapbox.restBaseUrl
        : window.location.origin + '/placy/wp-json/placy/v1';
    
    const CONFIG = {
        ENTUR_ENDPOINT: restBase + '/entur/departures',
        BYSYKKEL_ENDPOINT: restBase + '/bysykkel/availability',
        HYRE_ENDPOINT: restBase + '/hyre/availability',
        CACHE_DURATION: 60000, // 1 minute
        MAX_DEPARTURES: 5
    };

    // Cache for API data
    const dataCache = new Map();

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', init);

    /**
     * Initialize accordion functionality
     */
    function init() {
        // Bind click handlers to all accordion headers
        document.addEventListener('click', handleAccordionClick);
        
        // Bind keyboard handlers for accessibility
        document.addEventListener('keydown', handleAccordionKeydown);

        console.log('[ApiAccordion] Initialized');
    }

    /**
     * Handle accordion header click
     */
    function handleAccordionClick(e) {
        // Don't toggle if clicking the map button
        if (e.target.closest('.ns-api-map-btn')) return;

        // Find the card - either from header or direct card click
        const header = e.target.closest('.ns-api-header');
        const card = header ? header.closest('.ns-api-card') : e.target.closest('.ns-api-card');
        
        if (!card) return;

        toggleAccordion(card);
    }

    /**
     * Handle keyboard navigation
     */
    function handleAccordionKeydown(e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;

        const header = e.target.closest('.ns-api-header');
        if (!header) return;

        e.preventDefault();
        const card = header.closest('.ns-api-card');
        if (card) {
            toggleAccordion(card);
        }
    }

    /**
     * Toggle accordion open/closed
     */
    function toggleAccordion(card) {
        const isOpen = card.classList.contains('is-open');
        const header = card.querySelector('.ns-api-header');

        if (isOpen) {
            // Close
            card.classList.remove('is-open');
            header.setAttribute('aria-expanded', 'false');
        } else {
            // Open
            card.classList.add('is-open');
            header.setAttribute('aria-expanded', 'true');
            
            // Load data if not already loaded
            loadApiData(card);
        }
    }

    /**
     * Load API data based on card type
     */
    async function loadApiData(card) {
        const contentWrapper = card.querySelector('[data-api-content]');
        const loadingEl = card.querySelector('.ns-api-loading');
        const apiType = card.dataset.apiType;

        // Check if already loaded
        if (contentWrapper.dataset.loaded === 'true') {
            return;
        }

        // Show loading
        if (loadingEl) {
            loadingEl.style.display = 'flex';
        }

        try {
            let html = '';

            switch (apiType) {
                case 'entur':
                    html = await loadEnturDepartures(card);
                    break;
                case 'bysykkel':
                    html = await loadBysykkelAvailability(card);
                    break;
                case 'hyre':
                    html = await loadHyreAvailability(card);
                    break;
                default:
                    html = '<div class="ns-api-error">Ukjent API-type</div>';
            }

            contentWrapper.innerHTML = html;
            contentWrapper.dataset.loaded = 'true';

        } catch (error) {
            console.error('[ApiAccordion] Error loading data:', error);
            contentWrapper.innerHTML = '<div class="ns-api-error">Kunne ikke laste data</div>';
        } finally {
            // Hide loading
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }
        }
    }

    /**
     * Load Entur departures
     */
    async function loadEnturDepartures(card) {
        const stopplaceId = card.dataset.enturStopplaceId;
        const quayId = card.dataset.enturQuayId || '';
        const transportMode = card.dataset.enturTransportMode || '';
        const lineFilter = card.dataset.enturLineFilter || '';
        const groupByDirection = card.dataset.enturGroupByDirection !== '0';

        // Build cache key
        const cacheKey = `entur-${stopplaceId}-${quayId}-${transportMode}-${lineFilter}`;
        
        // Check cache
        const cached = dataCache.get(cacheKey);
        if (cached && (Date.now() - cached.timestamp) < CONFIG.CACHE_DURATION) {
            return buildEnturHTML(cached.data, groupByDirection, stopplaceId);
        }

        // Build URL
        let url = `${CONFIG.ENTUR_ENDPOINT}/${encodeURIComponent(stopplaceId)}`;
        const params = [];
        if (quayId) params.push(`quay_id=${encodeURIComponent(quayId)}`);
        if (transportMode) params.push(`transport_mode=${encodeURIComponent(transportMode)}`);
        if (lineFilter) params.push(`line_filter=${encodeURIComponent(lineFilter)}`);
        params.push(`group_by_direction=${groupByDirection ? '1' : '0'}`);
        if (params.length) url += '?' + params.join('&');

        // Fetch
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch departures');
        
        const data = await response.json();
        
        // Cache
        dataCache.set(cacheKey, { data, timestamp: Date.now() });

        return buildEnturHTML(data, groupByDirection, stopplaceId);
    }

    /**
     * Build Entur departures HTML
     */
    function buildEnturHTML(data, groupByDirection, stopplaceId = '') {
        const departures = data.departures || [];
        const grouped = data.grouped || [];
        const allRoutes = data.all_routes || [];
        const stopplaceName = data.stopplace_name || '';
        const timestamp = new Date().toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });

        if (departures.length === 0 && grouped.length === 0) {
            return `
                <div class="ns-api-error" style="padding: 24px; text-align: center; color: #6b7280;">
                    Ingen avganger funnet
                </div>
            `;
        }

        let html = `
            <div class="ns-departures-header">
                <span class="ns-departures-status">Neste avganger</span>
            </div>
            <ul class="ns-departures-list">
        `;

        // Use grouped or flat departures
        const displayDepartures = (grouped.length > 1 && groupByDirection) 
            ? grouped.flatMap(g => g.departures.slice(0, 2)) 
            : departures;

        displayDepartures.slice(0, CONFIG.MAX_DEPARTURES).forEach(dep => {
            const realtimeClass = dep.realtime ? '' : 'ns-departure-realtime--scheduled';
            html += `
                <li class="ns-departure-row">
                    <span class="ns-departure-line">${escapeHtml(dep.line_number || '')}</span>
                    <span class="ns-departure-destination">${escapeHtml(dep.destination || '')}</span>
                    <div class="ns-departure-time">
                        <span class="ns-departure-clock">${escapeHtml(dep.time || '')}</span>
                        <span class="ns-departure-relative">${formatRelativeTime(dep.relative_time)}</span>
                        <span class="ns-departure-realtime ${realtimeClass}"></span>
                    </div>
                </li>
            `;
        });

        html += '</ul>';

        // All routes section
        if (allRoutes.length > 0) {
            html += '<div class="ns-all-routes">';
            html += '<div class="ns-routes-list">';
            
            allRoutes.forEach(route => {
                html += `<span class="ns-route-badge">${escapeHtml(route)}</span>`;
            });
            
            // Add ATB link if we have a stopplace ID
            if (stopplaceId) {
                // URL encode the stopplace ID (replace : with %3A)
                const encodedId = encodeURIComponent(stopplaceId);
                html += `
                    <a href="https://reise.atb.no/departures/${encodedId}?searchMode=now" 
                       target="_blank" 
                       rel="noopener" 
                       class="ns-atb-link">
                        Se rutetabell
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                        </svg>
                    </a>
                `;
            }
            
            html += '</div>';
            html += '</div>';
        }

        // Footer
        html += `
            <div class="ns-api-footer">
                <a href="https://entur.no" target="_blank" rel="noopener">
                    Data fra Entur
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                    </svg>
                </a>
                <span class="ns-api-updated">Oppdatert ${timestamp}</span>
            </div>
        `;

        return html;
    }

    /**
     * Load Bysykkel availability
     */
    async function loadBysykkelAvailability(card) {
        const stationId = card.dataset.bysykkelStationId;
        const cacheKey = `bysykkel-${stationId}`;

        // Check cache
        const cached = dataCache.get(cacheKey);
        if (cached && (Date.now() - cached.timestamp) < CONFIG.CACHE_DURATION) {
            return buildBysykkelHTML(cached.data);
        }

        // Fetch
        const url = `${CONFIG.BYSYKKEL_ENDPOINT}/${encodeURIComponent(stationId)}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch availability');
        
        const data = await response.json();
        
        // Cache
        dataCache.set(cacheKey, { data, timestamp: Date.now() });

        return buildBysykkelHTML(data);
    }

    /**
     * Build Bysykkel availability HTML
     */
    function buildBysykkelHTML(data) {
        const bikesAvailable = data.bikes_available ?? 0;
        const docksAvailable = data.docks_available ?? 0;
        const timestamp = new Date().toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });

        const bikesClass = bikesAvailable === 0 ? 'ns-availability-number--zero' : '';
        const docksClass = docksAvailable === 0 ? 'ns-availability-number--zero' : '';

        return `
            <div class="ns-availability-header">
                <span class="ns-availability-status">Bysykkel tilgjengelighet</span>
            </div>
            <div class="ns-availability-grid">
                <div class="ns-availability-stat">
                    <span class="ns-availability-number ${bikesClass}">${bikesAvailable}</span>
                    <span class="ns-availability-label">Ledige sykler</span>
                </div>
                <div class="ns-availability-stat">
                    <span class="ns-availability-number ${docksClass}">${docksAvailable}</span>
                    <span class="ns-availability-label">Ledige låser</span>
                </div>
            </div>
            <div class="ns-api-footer">
                <a href="https://trfrp.no/bysykkel" target="_blank" rel="noopener">
                    Data fra Trondheim Bysykkel
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                    </svg>
                </a>
                <span class="ns-api-updated">Hentet kl ${timestamp}</span>
            </div>
        `;
    }

    /**
     * Load Hyre availability
     */
    async function loadHyreAvailability(card) {
        const stationId = card.dataset.hyreStationId;
        const cacheKey = `hyre-${stationId}`;

        // Check cache
        const cached = dataCache.get(cacheKey);
        if (cached && (Date.now() - cached.timestamp) < CONFIG.CACHE_DURATION) {
            return buildHyreHTML(cached.data);
        }

        // Fetch
        const url = `${CONFIG.HYRE_ENDPOINT}/${encodeURIComponent(stationId)}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch availability');
        
        const data = await response.json();
        
        // Cache
        dataCache.set(cacheKey, { data, timestamp: Date.now() });

        return buildHyreHTML(data);
    }

    /**
     * Build Hyre availability HTML
     */
    function buildHyreHTML(data) {
        const vehiclesAvailable = data.vehicles_available ?? 0;
        const capacity = data.capacity ?? 0;
        const stationName = data.station_name || '';
        const timestamp = new Date().toLocaleTimeString('no-NO', { hour: '2-digit', minute: '2-digit' });

        const vehiclesClass = vehiclesAvailable === 0 ? 'ns-availability-number--zero' : '';

        return `
            <div class="ns-availability-header">
                <span class="ns-availability-status">Hyre tilgjengelighet</span>
            </div>
            <div class="ns-availability-grid">
                <div class="ns-availability-stat">
                    <span class="ns-availability-number ${vehiclesClass}">${vehiclesAvailable}</span>
                    <span class="ns-availability-label">Ledige biler</span>
                </div>
                <div class="ns-availability-stat">
                    <span class="ns-availability-number">${capacity}</span>
                    <span class="ns-availability-label">Total kapasitet</span>
                </div>
            </div>
            <div class="ns-api-footer">
                <a href="https://hyre.no" target="_blank" rel="noopener">
                    Data fra Hyre
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                    </svg>
                </a>
                <span class="ns-api-updated">Hentet kl ${timestamp}</span>
            </div>
        `;
    }

    /**
     * Format relative time (e.g., "5 min")
     */
    function formatRelativeTime(minutes) {
        if (typeof minutes !== 'number' || isNaN(minutes)) return '';
        if (minutes < 1) return 'nå';
        return `${Math.round(minutes)} min`;
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

    /**
     * Programmatically open an accordion
     */
    function openAccordion(card) {
        if (!card.classList.contains('is-open')) {
            toggleAccordion(card);
        }
    }

    /**
     * Programmatically close an accordion
     */
    function closeAccordion(card) {
        if (card.classList.contains('is-open')) {
            toggleAccordion(card);
        }
    }

    /**
     * Refresh data for a specific card
     */
    async function refreshCard(card) {
        const contentWrapper = card.querySelector('[data-api-content]');
        if (contentWrapper) {
            contentWrapper.dataset.loaded = 'false';
            await loadApiData(card);
        }
    }

    // Expose public API
    window.ApiAccordion = {
        open: openAccordion,
        close: closeAccordion,
        refresh: refreshCard,
        toggle: toggleAccordion
    };

})();
