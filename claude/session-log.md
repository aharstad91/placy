# Session Log

> **Shared context file between Claude Chat and Claude Code**
> Updated continuously by both parties to stay in sync.

---

## Last Updated
- **When:** 2025-01-19 11:35
- **By:** Claude Code
- **What:** Git-struktur ryddet opp - symlink løsning + oppdatert .gitignore

---

## Active Context

### Current Focus
✅ Git-struktur er nå korrekt satt opp og dokumentert.

### ⚠️ VIKTIG: Git-struktur for Placy

**Problem som ble løst:** Det var to `themes/placy/` mapper - én i repo-roten og én i `wp-content/`. Dette skapte forvirring om hvilken som var "riktig".

**Løsning: Symlink-struktur**
```
/placy (repo root - git tracks this)
├── themes/placy/              ← KILDEKODE (git sporer)
├── plugins/.gitkeep
├── claude/
├── .gitignore
│
├── wp-content/
│   └── themes/
│       └── placy → ../../themes/placy  ← SYMLINK (WordPress bruker)
│
└── wp-admin/, wp-includes/, etc.  ← IGNORERT av git
```

**Hvordan det fungerer:**
1. Git sporer `themes/placy/` (kildekoden)
2. WordPress finner theme via symlink `wp-content/themes/placy`
3. Når du redigerer filer, endrer du `themes/placy/` som git sporer
4. WordPress ser endringene umiddelbart via symlinken

**Regler:**
- ALDRI rediger filer i `wp-content/themes/placy/` direkte (det er bare en symlink)
- ALL kode-redigering skjer i `themes/placy/`
- `wp-content/`, `wp-admin/`, `wp-includes/` er IGNORERT av git

---

### Markør floating-bug (LØST)
✅ Markør floating-bug er fikset!

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

#### [Code] 11:35 - Git-struktur ryddet opp
- **Problem:** To `themes/placy/` mapper skapte forvirring
- **Løsning:** Symlink-struktur
  - `themes/placy/` = kildekode (git sporer)
  - `wp-content/themes/placy` = symlink til `../../themes/placy`
- **Oppdatert .gitignore:** WordPress core ignoreres (wp-admin, wp-includes, wp-*.php)
- **Dokumentert:** Struktur beskrevet i session-log "Active Context"
- **Commits:**
  - `fix(map): resolve marker floating/drifting bug on zoom`
  - `chore: sync accumulated theme improvements`

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

1. ✅ ~~FIX MARKØR FLOATING BUG~~ - LØST
2. ✅ ~~Git-struktur ryddet opp~~ - LØST
3. [ ] Test alle markør-states visuelt i browser
4. [ ] Utvid markør-forbedringer til poi-map-modal.js og master-map-modal.js

---

## Relevant Files

| File | Description |
|------|-------------|
| `themes/placy/css/chapter-mega-modal.css` | Markør CSS (seksjon 1015-1350) |
| `themes/placy/js/chapter-mega-modal.js` | Markør JS (createMapMarker rundt linje 1720) |
| `themes/placy/js/mapbox-utils.js` | Delte Mapbox utilities |
| `claude/PRD-marker-visibility.md` | Opprinnelig PRD for markør-visibility |

> **Merk:** Bruk alltid `themes/placy/` paths, IKKE `wp-content/themes/placy/`

---

## Notes

- **Tech stack:** WordPress, ACF Pro, Tailwind CSS, Mapbox, Google Places API
- **Lokal utvikling:** MAMP på macOS
- **Database:** MySQL via WP-CLI (se instructions.md for kommandoer)
- **Cache busting:** functions.php bruker `time()` for CSS/JS versjonering under utvikling
