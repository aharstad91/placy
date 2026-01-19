# Session Log

> **Shared context file between Claude Chat and Claude Code**
> Updated continuously by both parties to stay in sync.

---

## Last Updated
- **When:** 2025-01-19 04:30
- **By:** Claude Code
- **What:** ✅ LØST markør floating-bug - labels påvirket Mapbox størrelses-beregning

---

## Active Context

### Current Focus
✅ **LØST:** Markør floating-bug er fikset!

### Løsning på floating-bug
**Root cause:** Labelen med `opacity: 0` tok fortsatt opp plass i document flow, noe som gjorde at Mapbox beregnet feil elementstørrelse for anchor-posisjonering.

**Fix:**
1. Endret label fra `opacity: 0` til `display: none` som default
2. Satt label til `position: absolute` så den ikke påvirker markør-dimensjoner
3. La til `position: relative` på inner wrapper som referanse
4. Endret alle states fra `opacity: 1` til `display: block` for å vise label

### Nåværende markør-struktur (HTML)
```html
<div class="pl-mega-drawer__map-marker">  <!-- Mapbox kontrollerer denne med inline transform -->
  <div class="pl-mega-drawer__map-marker-inner" style="position: relative;">
    <div class="pl-mega-drawer__map-marker-dot">
      <span class="pl-mega-drawer__map-marker-icon">...</span>
    </div>
    <div class="pl-mega-drawer__map-marker-label" style="display: none; position: absolute;">Navn</div>
  </div>
</div>
```

### Fungerende states
- ✅ Default: Label skjult med `display: none`
- ✅ Hover: Label vises med `display: block`
- ✅ Active: Label vises med blå bakgrunn
- ✅ Highlighted: Puls-animasjon
- ✅ Route-dimmed: Nedtonet med label skjult

### Important to Remember
- **WP-CLI:** Bruk alltid `PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp [command]`
- **Chrome DevTools MCP:** Tilgjengelig for å teste frontend live
- Placy er en stedbasert storytelling-plattform (WordPress)
- POI-bibliotek med Google POIs og Native POIs

---

## Markør-states (fungerer visuelt, men floating-bug)

| State | Trigger | CSS-klasse | Visuelt |
|-------|---------|------------|---------|
| Default | Ingen | (ingen) | 24px dot, label skjult |
| Base | Zoom >= 16 | `--base` | scale(1.15) på inner |
| Highlighted | Scroll til kort | `--highlighted` | Blå + puls-animasjon |
| Hover | Mus over | `--hover` | Label synlig, skygge |
| Active | Klikk | `--active` | scale(1.25), blå, label, rute |
| Route-dimmed | Annen aktiv | `--route-dimmed` | scale(0.8), grå, opacity 0.25 |

---

## Prototype-innsikter (Ferjemannsveien 10)

### UI-struktur
| Komponent | Beskrivelse |
|-----------|-------------|
| **Venstre sidebar** | Story Index, kapittel-navigasjon, Global Settings |
| **Hovedinnhold** | Hero-bilder, tittel, brotekst, kapitler med POI-kort |
| **Global Settings** | Travel Mode (fots/sykkel/bil), Time Budget (5/10/15 min) |
| **Mega Drawer** | Fullskjerm kategorivisning med kart og søk |

### URL-struktur
```
/[customer-slug]/[project-slug]/
Eksempel: /klp-eiendom-trondheim/ferjemannsveien-10/
```

---

## Changelog

### 2025-01-19

#### [Code] 04:30 - ✅ Markør floating-bug LØST
- **Root cause:** Label med `opacity: 0` tok opp plass i layout, påvirket Mapbox anchor-beregning
- **Løsning:**
  - Label: `display: none` (ikke `opacity: 0`) + `position: absolute`
  - Inner wrapper: `position: relative`
  - Alle states: `display: block` i stedet for `opacity: 1`
- **Debug-metode:** Strippet all CSS, la til klasser én for én, identifiserte label som skyldige
- **Files modified:**
  - `css/chapter-mega-modal.css` (label CSS endret til display none/block)
  - `js/chapter-mega-modal.js` (markør-struktur uendret)

#### [Code] 03:00 - Markør floating-bug debug (ULØST)
- **Problem:** Markører drifter fra korrekt posisjon ved zoom
- **Forsøk 1:** Inner wrapper for transforms → Ingen effekt
- **Forsøk 2:** Fjernet alle transitions → Ingen effekt
- **Forsøk 3:** Kun scale() på inner, konstant dot-størrelse → Ingen effekt
- **Status:** 🔴 ULØST - trenger ny tilnærming i neste session
- **Files modified:**
  - `css/chapter-mega-modal.css` (refaktorert markør-CSS)
  - `js/chapter-mega-modal.js` (la til inner wrapper i HTML)

#### [Code] 02:30 - Ny markør-modell implementert
- **Implementert:** Mini/Base størrelser + Highlighted/Hover/Active states
- **CSS:** Ryddet opp og forenklet markør-states
- **JS:** Zoom-basert størrelses-switching ved terskel 16
- **Slettet:** Forvirrende `/themes/placy/` mappe (kun `/wp-content/themes/placy/` brukes)

### 2025-01-18

#### [Code] Natt - Zoom-basert marker/label visibility
- **Problem:** Ved utzooming overlapper markører og labels hverandre
- **Løsning:** `PlacyMarkerVisibility` modul (fjernet senere pga kompleksitet)

#### [Code] Kveld - Mapbox POI-label hiding + Session hook
- **Løsning:** `PlacyMapUtils.hideMapboxPOILayers()` funksjon
- **Session-log hook:** Automatisk lesing av context-filer

---

### 2025-01-16

#### [Code] Database access setup
- Konfigurert WP-CLI til å fungere med MAMP MySQL
- Opprettet wrapper-script: `.wp-cli-wrapper.sh`

---

## Next Steps (Prioritized)

1. 🔴 **FIX MARKØR FLOATING BUG** - Høyeste prioritet
   - Test med helt ren markør (ingen custom CSS) for å isolere problemet
   - Undersøk Mapbox Marker anchor-options
   - Sjekk om `anchor: 'center'` i stedet for `'bottom'` hjelper
   - Vurder å bruke Mapbox symbol layers i stedet for HTML markers
2. [ ] Når floating er fikset: Test alle states visuelt
3. [ ] Utvid til poi-map-modal.js og master-map-modal.js (senere)

---

## Relevant Files

| File | Description |
|------|-------------|
| `wp-content/themes/placy/css/chapter-mega-modal.css` | Markør CSS (seksjon 1015-1350) |
| `wp-content/themes/placy/js/chapter-mega-modal.js` | Markør JS (createMapMarker rundt linje 1720) |
| `wp-content/themes/placy/js/mapbox-utils.js` | Delte Mapbox utilities |
| `claude/PRD-marker-visibility.md` | Opprinnelig PRD for markør-visibility |

---

## Notes

- **Tech stack:** WordPress, ACF Pro, Tailwind CSS, Mapbox, Google Places API
- **Lokal utvikling:** MAMP på macOS
- **Database:** MySQL via WP-CLI (se instructions.md for kommandoer)
- **Cache busting:** functions.php bruker `time()` for CSS/JS versjonering under utvikling
