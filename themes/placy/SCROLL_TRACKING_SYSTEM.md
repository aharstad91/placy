# Scroll-tracking System - Semantisk Oversikt

## Systemarkitektur

Scroll-tracking systemet kobler innhold i venstre kolonne med markører i høyre kart. Når brukeren scroller gjennom POI-er i venstre kolonne, highlightes den tilsvarende markøren på kartet.

---

## Hovedkomponenter

### 1. **POI Content Element** (Venstre kolonne)
**Semantisk navn**: `ScrollableContentCard`

**DOM-struktur**:
```html
<article class="poi-list-item poi-active-scroll">
  data-poi-id="42"
  data-poi-title="Fagn"
  data-poi-coords="[63.4305, 10.3951]"
  data-poi-image="url"
</article>
```

**Oppførsel**:
- Får klassen `poi-active-scroll` når den er i viewport
- Styling: grønn border, shadow, translateX(4px)

---

### 2. **Map Marker Element** (Høyre kart)
**Semantisk navn**: `InteractiveMapMarker`

**DOM-struktur**:
```html
<div class="tema-story-marker-container" data-poi-id="42">
  <div class="tema-story-marker"><!-- POI image dot --></div>
  <div class="tema-story-marker-label"><!-- Label with title + walking time --></div>
</div>
```

**Oppførsel**:
- Inner elements får `transform: scale(1.15)` når aktiv
- Får `box-shadow` glow effect
- `z-index: 1000` når aktiv

---

## Kjernefunksjoner

### 1. **ScrollViewportDetector**
**Funksjon**: `initPOIScrollTracking()`
**Plassering**: `/js/tema-story-map.js` linje ~515

**Ansvar**:
- Initialiserer Intersection Observer
- Lytter på når POI-kort kommer inn/ut av viewport
- Callback: `handlePOIIntersection()`

**Konfigurasjon**:
```javascript
{
  root: contentColumn,
  rootMargin: '-40% 0px -40% 0px',  // Midt-viewport focus
  threshold: [0, 0.25, 0.5, 0.75, 1.0]
}
```

---

### 2. **POIActivationHandler**
**Funksjon**: `handlePOIIntersection(entries, observer)`
**Plassering**: `/js/tema-story-map.js` linje ~525

**Ansvar**:
- Prosesserer Intersection Observer events
- Finner hvilket POI som er mest synlig
- Aktiverer kun ÉN POI om gangen
- Kaller: `highlightActivePOI()`

**Logikk**:
- Sjekker `entry.isIntersecting` og `entry.intersectionRatio`
- Velger POI med høyest intersection ratio
- Deaktiverer alle andre POI-er

---

### 3. **ContentCardHighlighter**
**Funksjon**: `highlightActivePOI(poiElement)`
**Plassering**: `/js/tema-story-map.js` linje ~545

**Ansvar**:
- Fjerner `poi-active-scroll` fra alle POI-kort
- Legger til `poi-active-scroll` på aktivt kort
- Henter POI ID og kaller: `highlightMarkerOnMap(poiId)`

**CSS-effekt**:
```css
.poi-active-scroll {
  border-color: #76908D;
  box-shadow: 0 8px 24px rgba(118, 144, 141, 0.15);
  transform: translateX(4px);
  transition: all 300ms ease-out;
}
```

---

### 4. **MapMarkerHighlighter**
**Funksjon**: `highlightMarkerOnMap(poiId)`
**Plassering**: `/js/tema-story-map.js` linje ~570

**Ansvar**:
- Resetter alle markører til default state
- Finner markør med matching `data-poi-id`
- Appliserer highlight-effekt på inner elements

**Highlight-effekt**:
```javascript
// Marker dot (hvis synlig)
markerDot.style.transform = 'scale(1.15)';
markerDot.style.boxShadow = '0 0 0 4px rgba(118, 144, 141, 0.3), 0 4px 12px rgba(0,0,0,0.3)';

// Marker label
markerLabel.style.transform = 'scale(1.05)';
markerLabel.style.boxShadow = '0 4px 16px rgba(118, 144, 141, 0.3)';
```

**Viktig**: Manipulerer BARE inner elements (`.tema-story-marker` og `.tema-story-marker-label`), IKKE container, for å bevare Mapbox sin `translate()` posisjonering.

---

## Dataflyt

```
User scrolls content
        ↓
ScrollViewportDetector (Intersection Observer)
        ↓
POIActivationHandler (handlePOIIntersection)
        ↓
ContentCardHighlighter (highlightActivePOI)
        ↓
MapMarkerHighlighter (highlightMarkerOnMap)
        ↓
Visual feedback: Card border + Marker scale/glow
```

---

## Nøkkeldata

### POI Identification Chain
1. **DOM Element** → `data-poi-id="42"`
2. **JavaScript State** → `chapterData.get(chapterId)` → array of POIs
3. **Marker Matching** → `markerContainer.getAttribute('data-poi-id')`

### Synkroniseringsmekanisme
- **Source of Truth**: `data-poi-id` attributt på både kort og markør
- **Matching Strategy**: String equality (`markerPoiId === poiId`)
- **State Management**: Stateless - ingen global "active POI" variabel

---

## Styling-system

### Content Card States
```css
/* Default */
.poi-list-item {
  border: 1px solid #f3f4f6;
  transition: all 300ms ease-out;
}

/* Active (scroll-tracking) */
.poi-list-item.poi-active-scroll {
  border-color: #76908D;
  transform: translateX(4px);
}
```

### Map Marker States
```javascript
// Default
transform: scale(1)
boxShadow: '0 2px 4px rgba(0,0,0,0.3)'
zIndex: 1

// Active (scroll-tracking)
transform: scale(1.15)
boxShadow: '0 0 0 4px rgba(118, 144, 141, 0.3), 0 4px 12px rgba(0,0,0,0.3)'
zIndex: 1000
```

---

## Konfigurasjonsparametre

### Intersection Observer
```javascript
ROOT_MARGIN: '-40% 0px -40% 0px'  // Fokuserer på midten av viewport
THRESHOLD: [0, 0.25, 0.5, 0.75, 1.0]  // Flere trigger-punkter
```

### Timing
```javascript
TRANSITION_DURATION: 300  // CSS transition timing
INIT_DELAY: 500  // Delay før scroll tracking starter (ms)
```

### Farger
```javascript
ACTIVE_COLOR: '#76908D'  // Placy grønn
GLOW_ALPHA: 0.3  // Opacity for glow effects
SHADOW_ALPHA: 0.15  // Opacity for card shadow
```

---

## Viktige begrensninger

### ❌ Ikke gjør dette:
```javascript
// Vil ødelegge Mapbox posisjonering!
markerContainer.style.transform = 'scale(1.15)';
```

### ✅ Gjør dette:
```javascript
// Manipuler inner elements
markerDot.style.transform = 'scale(1.15)';
markerLabel.style.transform = 'scale(1.05)';
```

---

## Debugging-verktøy

### Console logs tilgjengelig:
```javascript
// Intersection Observer activity
console.log('POI intersection detected:', {poiId, intersectionRatio});

// Active POI changes
console.log('Active POI changed to:', poiId);

// Marker highlighting
console.log('Highlighting marker:', {poiId, foundMarker});
```

---

## Fremtidige forbedringer (potensielle)

1. **BidirectionalSync**: Synkroniser motsatt vei - klikk på markør → scroll til kort
2. **SmoothScrollAnimation**: Smooth scroll til aktivt kort når markør klikkes
3. **ProgressIndicator**: Visuell indikator for hvor langt brukeren har scrollet
4. **ActivePOIState**: Global state management for active POI
5. **PerformanceMonitor**: Track scroll performance metrics

---

## Referanse til filer

- **JavaScript**: `/themes/placy/js/tema-story-map.js`
- **CSS**: `/themes/placy/blocks/poi-list/style.css`
- **PHP Template**: `/themes/placy/blocks/poi-list/template.php`
- **ACF Fields**: `/themes/placy/inc/acf-fields.php`
