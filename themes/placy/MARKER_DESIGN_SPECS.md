# Markør Design Spesifikasjoner

## Snapchat-stil Sirkulære Markører

### Enkelt-markør (Individual POI)

```
┌─────────────────────────┐
│   ╭───────────╮         │
│   │  ┌─────┐  │         │  ← 50px diameter sirkel
│   │  │     │  │         │  ← 3px hvit border
│   │  │ IMG │  │         │  ← POI bilde (cover fit)
│   │  │     │  │         │
│   │  └─────┘  │         │
│   ╰───────────╯         │
│                         │
│   ┌─────────────┐       │
│   │  POI Navn   │       │  ← Hvit label under
│   │  ★ 4.5 (23) │       │  ← Rating (hvis tilgjengelig)
│   │  5 min gange │       │  ← Gangtid
│   └─────────────┘       │
└─────────────────────────┘

States:
• Default:  scale(1.0)  shadow: 0 2px 8px rgba(0,0,0,0.2)
• Hover:    scale(1.15) shadow: 0 4px 12px rgba(0,0,0,0.3) 
• Active:   scale(1.33) shadow: 0 6px 16px rgba(0,0,0,0.4)
```

### Cluster-markør (2-4 POIs)

```
┌─────────────────────────────┐
│         ┌──┐                │
│         │4 │  ← Badge (antall)
│      ╭──┴──┴───╮            │
│      │ ┌──┬──┐ │            │  ← 70px diameter
│      │ │▓▓│▓▓│ │            │  ← 2x2 grid med POI-bilder
│      │ ├──┼──┤ │            │  ← Hvit border
│      │ │▓▓│▓▓│ │            │
│      │ └──┴──┘ │            │
│      ╰─────────╯            │
└─────────────────────────────┘

Click: Zoom inn til de-cluster nivå
Hover: scale(1.1)
```

### Punkt A (Eiendom/Start)

```
┌─────────────────────────┐
│      ╱╲                 │
│     ╱  ╲                │  ← 38px teardrop
│    │ ●  │               │  ← Hvit inner dot
│    │    │               │  ← Rød bakgrunn (#e74c3c)
│     ╲  ╱                │  ← 3px hvit border
│      ╲╱                 │  ← rotate(-45deg)
│       │                 │
│                         │
│  z-index: 1000          │  ← Alltid på toppen
└─────────────────────────┘
```

## Zoom-nivå Oppførsel

```
Zoom 17+     →  [●] [●] [●] [●] [●]
                Individuelle sirkulære markører
                Max 8-10 synlige samtidig

Zoom 15-16   →  [(●●)] [●] [(●●)]
                Noen clusters (2-4 POIs)
                + enkelte individuelle

Zoom 12-14   →  [(●●●●)]  [(●●●●)]
                Hovedsakelig clusters
                Større grupper samlet

Zoom < 12    →  [(●●●●)] 
                Få store clusters
                Mange POIs per cluster
```

## Farge-palette

```css
/* Markør */
Border:           #ffffff (white)
Missing image:    #D1D5DB (gray-300)
Shadow default:   rgba(0,0,0,0.2)
Shadow hover:     rgba(0,0,0,0.3)
Shadow active:    rgba(0,0,0,0.4)

/* Label */
Background:       #ffffff (white)
Text:             #1a202c (gray-900)
Rating star:      #FBBC05 (Google yellow)
Walking time:     #666666 (gray-600)

/* Cluster badge */
Background:       #76908D (Overvik green)
Text:             #ffffff (white)

/* Property marker */
Background:       #e74c3c (red)
Inner dot:        #ffffff (white)
```

## Layout-struktur

```
HTML Structure (Enkelt-markør):

<div class="tema-story-marker-container" data-marker-state="default">
  <div class="tema-story-marker" 
       style="background-image: url(...)">
    <!-- Sirkulær bilde -->
  </div>
  <div class="tema-story-marker-label">
    <span>POI Navn</span>
    <span>★ 4.5 (23)</span>
    <span>5 min gange</span>
  </div>
</div>

HTML Structure (Cluster):

<div class="cluster-marker-container">
  <div style="width: 70px; height: 70px; display: grid;">
    <div style="background-image: url(img1)"></div>
    <div style="background-image: url(img2)"></div>
    <div style="background-image: url(img3)"></div>
    <div style="background-image: url(img4)"></div>
  </div>
  <div class="cluster-badge">4</div>
</div>
```

## Interaksjoner

### Enkelt-markør:
1. **Hover**: Cursor → pointer, Scale → 1.15, Shadow → dypere
2. **Click**: 
   - Scale → 1.33 (active state)
   - Fit bounds til Punkt A + POI
   - Tegn gangvei-rute (dotted line)
   - Scroll til POI-kort i sidebar
3. **Leave**: Tilbake til default state

### Cluster:
1. **Hover**: Cursor → pointer, Scale → 1.1
2. **Click**:
   - Mapbox getClusterExpansionZoom()
   - easeTo() expansion zoom + 0.5
   - Duration: 500ms
3. **Result**: Viser underliggende POIs eller mindre clusters

### Punkt A:
1. **Hover**: Cursor → pointer
2. **Click**: Åpne popup med "Eiendommen" info
3. **Always visible**: Aldri skjult av andre markører

## Responsiveness

```
Mobile (<768px):
- Markør: 45px (litt mindre)
- Cluster: 65px
- Font: 12px i labels

Tablet (768px-1024px):
- Markør: 50px (standard)
- Cluster: 70px
- Font: 13px i labels

Desktop (>1024px):
- Markør: 50px (standard)
- Cluster: 70px
- Font: 13px i labels
```

## Performance-optimalisering

1. **CSS transforms**: Bruker GPU-akselerering for smooth animasjoner
2. **Mapbox clustering**: Native implementation, rask rendering
3. **Lazy loading**: Cluster-leaves hentes kun når nødvendig
4. **Debouncing**: Map updates debounced ved scroll (100ms)
5. **Max markers**: Automatisk clustering begrenser til ~10 synlige markører

## Browser support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

Krever Mapbox GL JS v1.0+ for clustering.
