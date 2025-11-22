# Proximity Filter - Arkitektur og Implementasjon

## Oversikt

Proximity-blokken er et avansert filtreringssystem som lar brukere filtrere POIs (Points of Interest) basert på reisetid og transportmiddel. Systemet bruker Mapbox Directions API, localStorage-caching, og en global state manager for å gi rask og effektiv filtrering på tvers av hele siden.

---

## Tre-lags Arkitektur

### 1. PHP Template Layer (`template.php`)

**Ansvar**: Rendrer HTML-strukturen og injiserer initialdata

**ACF-felter**:
- `default_time`: Standard reisetid (10/20/30 min)
- `default_mode`: Standard transportmiddel (walk/bike/drive)

**Prosjektkoordinater**:
```php
$project = get_field('project', $post_id);
$start_lat = get_field('start_latitude', $project->ID);
$start_lng = get_field('start_longitude', $project->ID);
```

**Data-attributter på HTML**:
- `data-default-time`: Valgt tid ved sidelast
- `data-default-mode`: Valgt modus ved sidelast
- `data-project-coords`: JSON med startkoordinater
- `data-project-id`: WordPress prosjekt-ID

**HTML-struktur**:
```html
<div class="proximity-filter-block" data-default-time="10" data-default-mode="walk">
    <div class="proximity-time-selector">
        <button class="proximity-time-btn" data-time="10">10 min</button>
        <button class="proximity-time-btn" data-time="20">20 min</button>
        <button class="proximity-time-btn" data-time="30">30 min</button>
    </div>
    <div class="proximity-mode-selector">
        <button class="proximity-mode-btn" data-mode="walk">🚶 Gange</button>
        <button class="proximity-mode-btn" data-mode="bike">🚴 Sykkel</button>
        <button class="proximity-mode-btn" data-mode="drive">🚗 Bil</button>
    </div>
    <div class="proximity-result-counter">
        Viser <strong class="result-count">0</strong> steder
    </div>
</div>
```

---

### 2. Global State Manager (Singleton Pattern)

**`ProximityFilterState`** - Delt tilstand mellom alle filter-instanser på siden

#### Private State Variables:
```javascript
let selectedTime = 10;           // Valgt tid (min)
let selectedMode = 'walk';       // Valgt modus
let projectCoords = null;        // Startkoordinater
let projectId = '';              // Prosjekt-ID
let instances = [];              // Alle UI-instanser
let filteredPOIs = [];           // Filtrerte POIs
let isLoading = false;           // Loading state
```

#### Public Methods:

**Getters**:
- `getTime()` - Hent valgt tid
- `getMode()` - Hent valgt modus
- `getProjectCoords()` - Hent prosjektkoordinater
- `getFilteredPOIs()` - Hent filtrerte POIs
- `isFiltering()` - Sjekk om filtrering pågår

**Setters** (trigger filtrering):
- `setTime(time)` - Endre tid og filtrer
- `setMode(mode)` - Endre modus og filtrer
- `setProjectData(coords, id, defaultTime, defaultMode)` - Initialiser

**Core Methods**:
- `filterAllPOIs()` - Hovedlogikk for filtrering
- `getAllPOIsFromDOM()` - Finn alle POIs i DOM
- `calculateTravelTimes(pois)` - Beregn reisetider med cache
- `updateUI()` - Oppdater UI og POI-visibilitet
- `notifyAllInstances(type, data)` - Kommuniser med UI-instanser

---

### 3. UI Instance Layer

**`ProximityFilter`** klasse - Én instans per filter-blokk

**Ansvar**:
- Håndtere knappeklikk (tid/modus)
- Synkronisere UI med global tilstand
- Vise antall filtrerte POIs
- Lytte på `placesLoaded` events

**Livssyklus**:
```javascript
constructor(element) {
    // Les config fra data-attributes
    // Registrer med global state
    this.init();
}

init() {
    this.bindEvents();              // Knytt event listeners
    this.setupPlacesLoadedListener(); // Lytt på Google Places
    this.syncUIState();             // Synkroniser knapper
    ProximityFilterState.filterAllPOIs(); // Kjør første filtrering
}
```

---

## Filtreringsprosessen (Step-by-Step)

### Steg 1: Brukerinteraksjon
```javascript
// UI Instance fanger knappeklikk
btn.addEventListener('click', (e) => {
    e.preventDefault();
    ProximityFilterState.setTime(parseInt(btn.dataset.time));
});
```

### Steg 2: Global State Oppdatering
```javascript
setTime(time) {
    if (selectedTime === time) return; // Ingen endring
    selectedTime = time;
    console.log('[State] Time changed to:', time);
    this.filterAllPOIs(); // ← Trigger filtrering
}
```

### Steg 3: Finn Alle POIs i DOM
```javascript
getAllPOIsFromDOM() {
    const poiCards = document.querySelectorAll('[data-poi-id]');
    return Array.from(poiCards).map(card => {
        const poiId = card.dataset.poiId;
        const coords = JSON.parse(card.dataset.poiCoords);
        return {
            id: poiId,
            element: card,
            coords: { lat, lng }
        };
    }).filter(Boolean);
}
```
**Viktig**: Henter POIs fra **hele siden**, ikke bare én chapter.

### Steg 4: Beregn Reisetider (med Cache)
```javascript
async calculateTravelTimes(pois) {
    const poisWithTimes = [];
    
    for (const poi of pois) {
        // 1. Sjekk cache først
        const cached = this.getCachedTime(poi.id, selectedMode);
        
        if (cached !== null) {
            // ✅ Cache hit - bruk lagret tid
            poisWithTimes.push({
                ...poi,
                travelTime: cached,
                cached: true
            });
        } else {
            // ❌ Cache miss - hent fra API
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
}
```

### Steg 5: Mapbox Directions API
```javascript
async fetchTravelTime(coords) {
    const mapboxToken = placyMapConfig.mapboxToken;
    const profile = selectedMode === 'walk' ? 'walking' : 
                    selectedMode === 'bike' ? 'cycling' : 'driving';
    
    const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/` +
        `${projectCoords.lng},${projectCoords.lat};` +
        `${coords.lng},${coords.lat}` +
        `?access_token=${mapboxToken}&geometries=geojson`;
    
    const response = await fetch(url, { 
        signal: AbortSignal.timeout(10000) // 10s timeout
    });
    
    const data = await response.json();
    
    if (data.routes && data.routes.length > 0) {
        // Konverter sekunder til minutter
        return Math.ceil(data.routes[0].duration / 60);
    }
    
    throw new Error('No route found');
}
```

**Fallback ved API-feil**:
```javascript
calculateFallbackTime(coords) {
    // Haversine formula - luftlinje-distanse
    const R = 6371; // Jordens radius i km
    const dLat = (coords.lat - projectCoords.lat) * Math.PI / 180;
    const dLon = (coords.lng - projectCoords.lng) * Math.PI / 180;
    
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(projectCoords.lat * Math.PI/180) * 
        Math.cos(coords.lat * Math.PI/180) *
        Math.sin(dLon/2) * Math.sin(dLon/2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R * c; // km
    
    // Beregn tid basert på gjennomsnittsfart
    const speed = CONFIG.FALLBACK_SPEEDS[selectedMode]; // 5/15/40 km/h
    return Math.ceil((distance / speed) * 60); // minutter
}
```

### Steg 6: Filtrering
```javascript
// Filtrer POIs basert på valgt tid
filteredPOIs = poisWithTimes.filter(poi => poi.travelTime <= selectedTime);

console.log(`Filtered: ${filteredPOIs.length} of ${allPOIs.length}`);
```

### Steg 7: Oppdater POI Visibilitet
```javascript
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
        
        if (isVisible) {
            // ✅ Vis POI-kort
            poiCard.classList.remove('hidden', 'proximity-hidden');
            poiCard.style.display = '';
            
            // Oppdater reisetid-tekst
            const travelTime = travelTimeMap.get(poiId);
            const walkingTimeText = poiCard.querySelector('.poi-walking-time');
            if (walkingTimeText) {
                walkingTimeText.textContent = `${travelTime} min ${modeText}`;
            }
        } else {
            // ❌ Skjul POI-kort
            poiCard.classList.add('hidden', 'proximity-hidden');
            poiCard.style.display = 'none';
        }
    });
    
    // Oppdater kartmarkører
    this.updateMapMarkers(filteredIds);
}
```

### Steg 8: Oppdater Kartmarkører
```javascript
updateMapMarkers(filteredIds) {
    const markers = document.querySelectorAll('.tema-story-marker-wrapper');
    
    markers.forEach(marker => {
        const poiId = marker.dataset.poiId;
        
        // Skip property markers (alltid synlige)
        if (marker.dataset.markerType === 'property') return;
        
        // Vis/skjul markør basert på filtrering
        if (poiId && filteredIds.has(poiId)) {
            marker.style.display = 'flex'; // ✅ Vis
        } else if (poiId) {
            marker.style.display = 'none';  // ❌ Skjul
        }
    });
}
```

### Steg 9: Oppdater Alle UI-instanser
```javascript
notifyAllInstances(type, data) {
    instances.forEach(instance => {
        if (type === 'update') {
            instance.updateDisplay(data);  // Oppdater teller
            instance.syncUIState();        // Synkroniser knapper
        }
        if (type === 'loading') {
            instance.setLoading(data);     // Vis/skjul spinner
        }
        if (type === 'error') {
            instance.showError();          // Vis feilmelding
        }
    });
}
```

---

## Cache-systemet (localStorage)

### Cache-nøkkel Format
```javascript
placy_proximity_{projectId}_{poiId}_{mode}

// Eksempel:
"placy_proximity_123_poi-456_walk"
"placy_proximity_123_poi-456_bike"
"placy_proximity_123_poi-789_drive"
```

### Cache-struktur
```json
{
    "travelTime": 15,
    "timestamp": 1732276800000
}
```

### Cache-metoder
```javascript
// Hent fra cache
getCachedTime(poiId, mode) {
    const cacheKey = `${CONFIG.CACHE_KEY_PREFIX}${projectId}_${poiId}_${mode}`;
    const cached = localStorage.getItem(cacheKey);
    
    if (!cached) return null;
    
    try {
        const data = JSON.parse(cached);
        const age = Date.now() - data.timestamp;
        const maxAge = CONFIG.CACHE_VALIDITY_DAYS * 24 * 60 * 60 * 1000;
        
        if (age > maxAge) {
            // Cache utløpt
            localStorage.removeItem(cacheKey);
            return null;
        }
        
        return data.travelTime;
    } catch (e) {
        return null;
    }
}

// Lagre i cache
cacheTime(poiId, mode, travelTime) {
    const cacheKey = `${CONFIG.CACHE_KEY_PREFIX}${projectId}_${poiId}_${mode}`;
    const data = {
        travelTime,
        timestamp: Date.now()
    };
    
    try {
        localStorage.setItem(cacheKey, JSON.stringify(data));
    } catch (e) {
        console.warn('Cache write failed:', e);
    }
}

// Rydd gamle cache-entries
clearOldCache() {
    const keys = Object.keys(localStorage);
    const maxAge = CONFIG.CACHE_VALIDITY_DAYS * 24 * 60 * 60 * 1000;
    
    keys.forEach(key => {
        if (key.startsWith(CONFIG.CACHE_KEY_PREFIX)) {
            try {
                const data = JSON.parse(localStorage.getItem(key));
                const age = Date.now() - data.timestamp;
                
                if (age > maxAge) {
                    localStorage.removeItem(key);
                }
            } catch (e) {
                localStorage.removeItem(key);
            }
        }
    });
}
```

**Cache-fordeler**:
- ✅ Unngår duplikate API-kall
- ✅ Rask re-filtrering (ingen ventetid)
- ✅ Fungerer offline for cached POIs
- ✅ Automatisk rydding av gamle entries
- ✅ 30-dagers validitet

---

## Multi-Instance Synkronisering

### Problem
Flere filter-blokker på samme side skal dele tilstand:
```html
<div class="chapter">
    <div class="proximity-filter-block">...</div> <!-- Instance 1 -->
</div>

<div class="chapter">
    <div class="proximity-filter-block">...</div> <!-- Instance 2 -->
</div>
```

### Løsning: Singleton State Manager

```javascript
// Instance 1 klikker "20 min"
ProximityFilterState.setTime(20);

// Global state oppdaterer ALLE instanser
notifyAllInstances('update', {
    count: 12,
    time: 20,
    mode: 'walk'
});

// Resultat:
// Instance 1: "Viser 12 steder innen 20 min gange"
// Instance 2: "Viser 12 steder innen 20 min gange"
// ✅ Begge instanser synkronisert
```

### Synkroniseringsflyt
```
Bruker klikker Instance 1 → "20 min"
    ↓
ProximityFilterState.setTime(20)
    ↓
filterAllPOIs() kjøres (én gang)
    ↓
notifyAllInstances('update', data)
    ↓
    ├─ Instance 1.updateDisplay()
    │   ├─ Oppdater knapper: 20 min = active
    │   └─ Oppdater teller: "12 steder"
    │
    └─ Instance 2.updateDisplay()
        ├─ Oppdater knapper: 20 min = active
        └─ Oppdater teller: "12 steder"
```

---

## Integrasjon med Google Places

### Problem
Google Places laster POIs dynamisk etter sidelast. Proximity-filter må re-kjøre filtrering når nye POIs legges til.

### Løsning: Event Listener

```javascript
setupPlacesLoadedListener() {
    const chapterWrapper = this.element.closest('.chapter');
    if (!chapterWrapper) return;
    
    // Lytt på custom event fra Google Places
    chapterWrapper.addEventListener('placesLoaded', (event) => {
        console.log('Google Places loaded, re-filtering...', event.detail);
        
        // Re-kjør filtrering med nye POIs
        ProximityFilterState.filterAllPOIs();
    });
}
```

### Event Dispatch (fra tema-story-map-multi.js)
```javascript
// Etter Google Places POIs er lagt til i DOM
const chapter = document.querySelector('.chapter[data-chapter-id="' + chapterId + '"]');
const event = new CustomEvent('placesLoaded', {
    detail: {
        count: places.length,
        category: category
    }
});
chapter.dispatchEvent(event);
```

---

## Konfigurasjon

```javascript
const CONFIG = {
    CACHE_VALIDITY_DAYS: 30,        // Cache-levetid
    CACHE_KEY_PREFIX: 'placy_proximity_',
    API_BATCH_LIMIT: 5,             // Maks samtidige API-kall
    API_TIMEOUT: 10000,             // 10 sekunders timeout
    FALLBACK_SPEEDS: {
        walk: 5,   // km/h
        bike: 15,  // km/h
        drive: 40  // km/h
    }
};
```

---

## Performance-optimalisering

### 1. Cache-first Strategi
- Sjekk localStorage før API-kall
- Kun nye POIs trigger API-kall
- Validitet: 30 dager

### 2. AbortController
```javascript
const controller = new AbortController();
const timeoutId = setTimeout(() => controller.abort(), 10000);

const response = await fetch(url, { signal: controller.signal });
clearTimeout(timeoutId);
```

### 3. Fallback til Luftlinje
- API-feil → Haversine-formel
- Garantert resultat selv uten API

### 4. Batch-prosessering
- Håndterer mange POIs effektivt
- Ingen blocking av UI

### 5. Single Filtering Pass
- Kun én filtrering per state-endring
- Alle instanser oppdateres samtidig

---

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ USER: Klikker "20 min sykkel"                               │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ UI Instance: btn.addEventListener('click')                  │
│ → ProximityFilterState.setMode('bike')                      │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ Global State: setMode('bike')                               │
│ → this.filterAllPOIs()                                      │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ getAllPOIsFromDOM()                                         │
│ → Find all [data-poi-id] elements                           │
│ → Parse coordinates from data-poi-coords                    │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ calculateTravelTimes(pois)                                  │
│ For each POI:                                               │
│   ├─ Cache hit? → Use cached time ⚡                        │
│   └─ Cache miss? → fetchTravelTime() → Cache result 💾     │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ fetchTravelTime(coords)                                     │
│ → Mapbox Directions API call                                │
│ → Profile: 'cycling'                                        │
│ → Timeout: 10s                                              │
│ → Fallback: Haversine formula if API fails                  │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ Filter: poi.travelTime <= 20                                │
│ → filteredPOIs = [poi1, poi3, poi5, ...]                    │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ updatePOIVisibility()                                       │
│ For each POI card:                                          │
│   ├─ Visible? → Remove 'hidden', update text ✅            │
│   └─ Hidden? → Add 'hidden', hide element ❌               │
│ For each map marker:                                        │
│   ├─ Visible? → display: 'flex' ✅                         │
│   └─ Hidden? → display: 'none' ❌                          │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ notifyAllInstances('update', data)                          │
│ → Instance 1: "Viser 12 steder innen 20 min sykkel"        │
│ → Instance 2: "Viser 12 steder innen 20 min sykkel"        │
│ ✅ All instances synchronized                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Eksempel: Full Brukerflyt

### Scenario
Bruker besøker side med 50 POIs. Klikker "20 min" → "sykkel".

### 1. Første besøk (ingen cache)
```
Klikk "20 min gange"
    ↓
Finn 50 POIs i DOM
    ↓
Cache miss for alle → 50 API-kall
    ├─ POI 1: 8 min ✅
    ├─ POI 2: 15 min ✅
    ├─ POI 3: 25 min ❌
    └─ ...
    ↓
Filter: 32 POIs innen 20 min
    ↓
Vis 32 kort, skjul 18
Cache alle resultater (30 dager)
```

**Tid**: ~15 sekunder (50 API-kall)

### 2. Bytt til sykkel (ingen cache for bike)
```
Klikk "sykkel"
    ↓
Finn 50 POIs i DOM
    ↓
Cache miss for bike → 50 API-kall
    ├─ POI 1: 4 min ✅
    ├─ POI 2: 7 min ✅
    ├─ POI 3: 12 min ✅
    └─ ...
    ↓
Filter: 45 POIs innen 20 min
    ↓
Vis 45 kort, skjul 5
Cache alle resultater
```

**Tid**: ~15 sekunder

### 3. Bytt tilbake til gange (cache hit!)
```
Klikk "gange"
    ↓
Finn 50 POIs i DOM
    ↓
Cache hit for alle → 0 API-kall ⚡
    ├─ POI 1: 8 min (cached) ✅
    ├─ POI 2: 15 min (cached) ✅
    ├─ POI 3: 25 min (cached) ❌
    └─ ...
    ↓
Filter: 32 POIs innen 20 min
    ↓
Vis 32 kort, skjul 18
```

**Tid**: ~0.1 sekunder! 🚀

---

## Feilhåndtering

### API Timeout
```javascript
try {
    const controller = new AbortController();
    setTimeout(() => controller.abort(), 10000);
    
    const response = await fetch(url, { signal: controller.signal });
    // ...
} catch (error) {
    if (error.name === 'AbortError') {
        console.warn('Mapbox API timeout, using fallback');
        return this.calculateFallbackTime(coords);
    }
    throw error;
}
```

### Ingen Rute Funnet
```javascript
if (data.routes && data.routes.length > 0) {
    return Math.ceil(data.routes[0].duration / 60);
}
throw new Error('No route found');
```
→ Faller tilbake til Haversine-formel

### Cache Write Feil
```javascript
try {
    localStorage.setItem(cacheKey, JSON.stringify(data));
} catch (e) {
    console.warn('Cache write failed:', e);
    // Fortsett uten cache
}
```

### Manglende Prosjektkoordinater
```php
// PHP template
if (!$project_coords) {
    if (is_admin()) {
        echo '<div class="notice notice-error">Project coordinates missing</div>';
    }
    return; // Ikke render på frontend
}
```

---

## Nøkkelstyrker

✅ **Global tilstand** - Ingen duplikate API-kall  
✅ **Cache-system** - Rask re-filtrering (0.1s vs 15s)  
✅ **Fallback-logikk** - Alltid fungerende  
✅ **Multi-instance support** - Skalerbar til mange blokker  
✅ **Google Places integration** - Dynamisk POI-liste  
✅ **Performance** - Optimalisert for store datasett  
✅ **UX** - Umiddelbar respons ved cache hit  

---

## Teknisk Stack

- **Frontend**: Vanilla JavaScript (ES6+)
- **API**: Mapbox Directions API
- **Storage**: localStorage (30-dager cache)
- **Backend**: WordPress + ACF
- **Pattern**: Singleton (Global State) + Instance (UI)
- **Events**: Custom Events for Google Places sync

---

## Versjon

**v2.0.0** - Global state manager med localStorage cache

### Endringer fra v1.x:
- ✅ Global state manager (tidligere: hver instans hadde egen state)
- ✅ localStorage cache (tidligere: ingen cache)
- ✅ Fallback til luftlinje (tidligere: feilet ved API-feil)
- ✅ Google Places event listener (tidligere: ingen sync)
- ✅ Multi-instance support (tidligere: kun én instans)

---

## Vedlikehold

### Øke cache-levetid
```javascript
const CONFIG = {
    CACHE_VALIDITY_DAYS: 60, // Fra 30 til 60 dager
    // ...
};
```

### Endre fallback-hastigheter
```javascript
const CONFIG = {
    FALLBACK_SPEEDS: {
        walk: 4,   // Saktere gange
        bike: 20,  // Raskere sykkel
        drive: 50  // Raskere bil
    }
};
```

### Rydd cache manuelt
```javascript
// Fra DevTools console:
Object.keys(localStorage)
    .filter(key => key.startsWith('placy_proximity_'))
    .forEach(key => localStorage.removeItem(key));
```

---

*Dokumentasjon sist oppdatert: 22. november 2024*
