# Proximity Filter Refactor - Implementation Plan

## Problem Summary

**Current Issue:**
- Proximity filter operates per-chapter with snapshot-based POI detection
- Google Places load dynamically after page load
- Filter doesn't detect new Google Places POIs
- Chapter 1 (with filter) shows no Google Places, Chapter 2 (no filter) works fine

**Root Cause:**
```javascript
// Current: Takes snapshot at page load
getChapterPOIs() {
    const chapterWrapper = this.element.closest('.chapter');
    const poiCards = chapterWrapper.querySelectorAll('[data-poi-id]');
    // Only finds POIs that existed at init time
}
```

## Solution: Shared State, Multiple Views

**Concept:**
- ONE global filter state (singleton)
- MULTIPLE UI instances (one per chapter)
- Each chapter shows the filter component
- Clicking any instance updates ALL instances
- Filters ALL POIs across ALL chapters globally

**Benefits:**
- ✅ No timing issues with Google Places (global scope catches all POIs)
- ✅ Consistent UX (filter follows user down the page)
- ✅ Synchronized state (change in chapter 2 = updates in chapters 1 & 3)
- ✅ Simple architecture (no events, observers, or polling needed)

---

## Implementation Steps

### Step 1: Create Global State Manager (Singleton)

**Location:** Top of `proximity-filter.js` (before ProximityFilter class)

```javascript
/**
 * Global Proximity Filter State Manager (Singleton)
 * Manages shared state across all filter instances
 */
const ProximityFilterState = (function() {
    // Private state
    let selectedTime = 10;
    let selectedMode = 'walk';
    let projectCoords = null;
    let projectId = '';
    let instances = [];
    let filteredPOIs = [];
    let isLoading = false;

    return {
        // Register a new filter instance
        register(instance) {
            instances.push(instance);
            console.log('[Proximity Filter State] Registered instance, total:', instances.length);
        },

        // Unregister an instance
        unregister(instance) {
            instances = instances.filter(i => i !== instance);
        },

        // Getters
        getTime() { return selectedTime; },
        getMode() { return selectedMode; },
        getProjectCoords() { return projectCoords; },
        getProjectId() { return projectId; },
        getFilteredPOIs() { return filteredPOIs; },
        isFiltering() { return isLoading; },

        // Setters (trigger updates)
        setTime(time) {
            if (selectedTime === time) return;
            selectedTime = time;
            console.log('[Proximity Filter State] Time changed to:', time);
            this.filterAllPOIs();
        },

        setMode(mode) {
            if (selectedMode === mode) return;
            selectedMode = mode;
            console.log('[Proximity Filter State] Mode changed to:', mode);
            this.filterAllPOIs();
        },

        setProjectData(coords, id) {
            projectCoords = coords;
            projectId = id;
        },

        // Main filtering logic
        async filterAllPOIs() {
            if (!projectCoords) {
                console.warn('[Proximity Filter State] No project coordinates');
                return;
            }

            isLoading = true;
            this.notifyAllInstances('loading', true);

            try {
                // Get ALL POIs from ALL chapters
                const allPOIs = this.getAllPOIsFromDOM();
                console.log('[Proximity Filter State] Found', allPOIs.length, 'POIs across all chapters');

                if (allPOIs.length === 0) {
                    filteredPOIs = [];
                    this.updateUI();
                    return;
                }

                // Calculate travel times (with caching)
                const poisWithTimes = await this.calculateTravelTimes(allPOIs);

                // Filter by selected time
                filteredPOIs = poisWithTimes.filter(poi => poi.travelTime <= selectedTime);

                console.log('[Proximity Filter State] Filtered:', filteredPOIs.length, 'of', allPOIs.length);

                // Update all instances
                this.updateUI();

            } catch (error) {
                console.error('[Proximity Filter State] Error:', error);
                this.notifyAllInstances('error', error);
            } finally {
                isLoading = false;
                this.notifyAllInstances('loading', false);
            }
        },

        // Get all POIs from entire page (not just one chapter)
        getAllPOIsFromDOM() {
            const poiCards = document.querySelectorAll('[data-poi-id]');
            return Array.from(poiCards).map(card => {
                const poiId = card.dataset.poiId;
                const coords = card.dataset.poiCoords;

                if (!coords) return null;

                try {
                    const [lat, lng] = JSON.parse(coords);
                    return {
                        id: poiId,
                        element: card,
                        coords: { lat, lng }
                    };
                } catch (e) {
                    console.warn('[Proximity Filter State] Invalid coords for POI:', poiId);
                    return null;
                }
            }).filter(Boolean);
        },

        // Calculate travel times with caching (same logic as before)
        async calculateTravelTimes(pois) {
            const poisWithTimes = [];

            for (const poi of pois) {
                const cached = this.getCachedTime(poi.id, selectedMode);

                if (cached !== null) {
                    poisWithTimes.push({
                        ...poi,
                        travelTime: cached,
                        cached: true
                    });
                } else {
                    const travelTime = await this.fetchTravelTime(poi.coords);
                    this.cacheTime(poi.id, selectedMode, travelTime);
                    poisWithTimes.push({
                        ...poi,
                        travelTime,
                        cached: false
                    });
                }
            }

            return poisWithTimes;
        },

        // Cache methods (same as before)
        getCachedTime(poiId, mode) {
            const cacheKey = `${CONFIG.CACHE_KEY_PREFIX}${projectId}_${poiId}_${mode}`;
            const cached = localStorage.getItem(cacheKey);
            if (!cached) return null;

            try {
                const data = JSON.parse(cached);
                const age = Date.now() - data.timestamp;
                const maxAge = CONFIG.CACHE_VALIDITY_DAYS * 24 * 60 * 60 * 1000;

                if (age > maxAge) {
                    localStorage.removeItem(cacheKey);
                    return null;
                }

                return data.travelTime;
            } catch (e) {
                return null;
            }
        },

        cacheTime(poiId, mode, travelTime) {
            const cacheKey = `${CONFIG.CACHE_KEY_PREFIX}${projectId}_${poiId}_${mode}`;
            const data = {
                travelTime,
                timestamp: Date.now()
            };
            localStorage.setItem(cacheKey, JSON.stringify(data));
        },

        // Mapbox API call (same as before)
        async fetchTravelTime(coords) {
            const mapboxToken = 'pk.eyJ1IjoiYWhhcnN0YWQ5MSIsImEiOiJjbTI1N2N5bzQwOWdtMmtzYzA2Y2c3YmhhIn0.RZAr1m0W-lWX02jXZr1vBw';
            const profile = selectedMode === 'walk' ? 'walking' : selectedMode === 'bike' ? 'cycling' : 'driving';

            const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/` +
                `${projectCoords[0]},${projectCoords[1]};${coords.lng},${coords.lat}` +
                `?access_token=${mapboxToken}&geometries=geojson`;

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Mapbox API error');

                const data = await response.json();
                if (data.routes && data.routes.length > 0) {
                    return Math.round(data.routes[0].duration / 60);
                }
                throw new Error('No route found');
            } catch (error) {
                console.warn('[Proximity Filter State] Mapbox error, using fallback:', error);
                return this.calculateFallbackTime(coords);
            }
        },

        calculateFallbackTime(coords) {
            const R = 6371;
            const dLat = (coords.lat - projectCoords[1]) * Math.PI / 180;
            const dLon = (coords.lng - projectCoords[0]) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(projectCoords[1] * Math.PI / 180) * Math.cos(coords.lat * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c;

            const speed = CONFIG.FALLBACK_SPEEDS[selectedMode];
            return Math.round((distance / speed) * 60);
        },

        // Update all UI instances and POI visibility
        updateUI() {
            this.updatePOIVisibility();
            this.notifyAllInstances('update', {
                count: filteredPOIs.length,
                time: selectedTime,
                mode: selectedMode
            });
        },

        // Update POI card visibility
        updatePOIVisibility() {
            const allPOIs = document.querySelectorAll('[data-poi-id]');
            const filteredIds = new Set(filteredPOIs.map(poi => poi.id));
            const travelTimeMap = new Map(filteredPOIs.map(poi => [poi.id, poi.travelTime]));

            const modeText = {
                walk: 'gange',
                bike: 'sykkel',
                drive: 'bil'
            }[selectedMode];

            allPOIs.forEach(poiCard => {
                const poiId = poiCard.dataset.poiId;
                const isVisible = filteredIds.has(poiId);

                // Show/hide POI
                if (isVisible) {
                    poiCard.classList.remove('hidden');
                    poiCard.style.display = '';
                } else {
                    poiCard.classList.add('hidden');
                    poiCard.style.display = 'none';
                }

                // Update travel time text
                const travelTimeEl = poiCard.querySelector('.poi-travel-time');
                if (travelTimeEl && isVisible) {
                    const travelTime = travelTimeMap.get(poiId);
                    if (travelTime !== undefined) {
                        travelTimeEl.textContent = `${travelTime} min ${modeText}`;
                    }
                }
            });
        },

        // Notify all registered instances
        notifyAllInstances(type, data) {
            instances.forEach(instance => {
                if (type === 'update') instance.updateDisplay(data);
                if (type === 'loading') instance.setLoading(data);
                if (type === 'error') instance.showError();
            });
        },

        // Initialize on first use
        init() {
            console.log('[Proximity Filter State] Initialized');
        }
    };
})();
```

---

### Step 2: Refactor ProximityFilter Class

**Changes to existing class:**

```javascript
/**
 * Proximity Filter UI Instance
 * Each chapter can have its own UI, but all share the same state
 */
class ProximityFilter {
    constructor(element) {
        this.element = element;
        
        // Read configuration from this instance's data attributes
        const defaultTime = parseInt(element.dataset.defaultTime) || 10;
        const defaultMode = element.dataset.defaultMode || 'walk';
        const projectCoords = JSON.parse(element.dataset.projectCoords || 'null');
        const projectId = element.dataset.projectId || '';

        // Initialize global state (only first instance does this)
        if (!ProximityFilterState.getProjectCoords()) {
            ProximityFilterState.setProjectData(projectCoords, projectId);
        }

        // Register this instance with global state
        ProximityFilterState.register(this);
        
        this.init();
    }

    init() {
        if (!ProximityFilterState.getProjectCoords()) {
            console.error('[Proximity Filter] Project coordinates missing');
            return;
        }

        this.bindEvents();
        
        // Sync initial UI state
        this.syncUIState();
        
        // Run filter (only first instance triggers actual filtering)
        if (ProximityFilterState.getFilteredPOIs().length === 0) {
            ProximityFilterState.filterAllPOIs();
        }
    }

    bindEvents() {
        // Time buttons
        const timeBtns = this.element.querySelectorAll('.proximity-time-btn');
        timeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                ProximityFilterState.setTime(parseInt(btn.dataset.time));
            });
        });

        // Mode buttons
        const modeBtns = this.element.querySelectorAll('.proximity-mode-btn');
        modeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                ProximityFilterState.setMode(btn.dataset.mode);
            });
        });
    }

    // Sync UI buttons with global state
    syncUIState() {
        const currentTime = ProximityFilterState.getTime();
        const currentMode = ProximityFilterState.getMode();

        // Update time buttons
        this.element.querySelectorAll('.proximity-time-btn').forEach(btn => {
            if (parseInt(btn.dataset.time) === currentTime) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update mode buttons
        this.element.querySelectorAll('.proximity-mode-btn').forEach(btn => {
            if (btn.dataset.mode === currentMode) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // Called by global state when filter updates
    updateDisplay(data) {
        this.syncUIState();

        const resultCount = this.element.querySelector('.result-count');
        const resultTime = this.element.querySelector('.result-time');
        const resultMode = this.element.querySelector('.result-mode');
        const resultText = this.element.querySelector('.result-text');
        const emptyState = this.element.querySelector('.empty-state');

        const modeText = {
            walk: 'gange',
            bike: 'sykkel',
            drive: 'bil'
        }[data.mode];

        if (resultCount) resultCount.textContent = data.count;
        if (resultTime) resultTime.textContent = data.time;
        if (resultMode) resultMode.textContent = modeText;

        // Show/hide empty state
        if (data.count === 0) {
            if (resultText) resultText.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
        } else {
            if (resultText) resultText.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');
        }
    }

    setLoading(loading) {
        if (loading) {
            this.element.classList.add('loading');
        } else {
            this.element.classList.remove('loading');
        }
    }

    showError() {
        console.error('[Proximity Filter] Error in this instance');
        // Show error state in UI
    }
}
```

---

### Step 3: Update Initialization

**No changes needed!** The existing initialization will work:

```javascript
function init() {
    const filters = document.querySelectorAll('.proximity-filter');
    filters.forEach(element => {
        new ProximityFilter(element);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
```

---

## Testing Plan

### Test 1: Basic Filtering
1. Load page
2. All 8 curated POIs visible in Chapter 1
3. Click "20 min" in Chapter 1
4. Verify: POI count updates
5. Verify: Some POIs hidden based on distance

### Test 2: Synchronized State
1. Scroll to Chapter 2 (if it has a filter instance)
2. Click "30 min" in Chapter 2
3. Scroll back to Chapter 1
4. Verify: Chapter 1 shows "30 min" selected
5. Verify: Same POIs filtered globally

### Test 3: Google Places Integration
1. Scroll to Chapter 1
2. Click "Se flere restauranter i området"
3. Wait for Google Places to load (8 new POIs)
4. Total POIs should be 16
5. Click "10 min" in filter
6. Verify: Filter counts BOTH curated + Google Places POIs
7. Verify: Count shows correct number (e.g., "Viser 12 av 16 steder")

### Test 4: Mode Switching
1. Select "20 min gange"
2. Note POI count (e.g., 12)
3. Switch to "20 min sykkel"
4. Verify: More POIs visible (cycling is faster)
5. Switch to "20 min bil"
6. Verify: Even more POIs visible

### Test 5: Cache Persistence
1. Filter POIs (triggers Mapbox API calls)
2. Refresh page
3. Filter same POIs again
4. Check console: Should see "cached: true" logs
5. Should be instant (no API delay)

---

## Migration Notes

### Files to Modify
1. ✅ `proximity-filter.js` - Complete refactor (600+ lines)

### Files NOT Modified
- ACF block templates (render.php still outputs same HTML)
- CSS files (UI styling unchanged)
- WordPress functions.php (just bump version number)

### Breaking Changes
❌ None! Existing HTML structure is preserved.

### Version Changes
- `proximity-filter.js`: 1.0.6 → **2.0.0** (major refactor)

---

## Rollback Plan

If issues occur:

1. **Git revert:**
   ```bash
   git checkout HEAD~1 wp-content/themes/placy/js/proximity-filter.js
   ```

2. **Restore version in functions.php:**
   ```php
   wp_enqueue_script(..., '1.0.6', true);
   ```

3. **Clear cache:**
   ```bash
   wp cache flush
   ```

---

## Performance Considerations

### Before (Per-Chapter):
- 3 chapters × 1 filter = 3 separate filter operations
- 8 POIs × 3 filters = 24 Mapbox API calls (without cache)

### After (Global):
- 1 global filter for all POIs
- 16 POIs (8 curated + 8 Google Places) = 16 Mapbox API calls
- **66% fewer API calls!**

### Caching Strategy:
- localStorage cache (30 days)
- Cache key: `placy_proximity_{projectId}_{poiId}_{mode}`
- Google Places get same caching as curated POIs

---

## Implementation Size

**Estimated Changes:**
- Lines added: ~400
- Lines removed: ~100
- Net change: +300 lines
- Time estimate: **2-3 hours** (including testing)

---

## Next Steps

1. ✅ Review this implementation plan
2. ⏳ Implement ProximityFilterState singleton
3. ⏳ Refactor ProximityFilter class
4. ⏳ Test basic filtering
5. ⏳ Test Google Places integration
6. ⏳ Test synchronized state across chapters
7. ⏳ Bump version to 2.0.0
8. ⏳ Clear cache and deploy

**Ready to proceed?** 🚀
