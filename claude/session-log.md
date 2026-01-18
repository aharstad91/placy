# Session Log

> **Shared context file between Claude Chat and Claude Code**
> Updated continuously by both parties to stay in sync.

---

## Last Updated
- **When:** 2025-01-18 (kveld)
- **By:** Claude Code
- **What:** La til POI-label hiding for Mapbox-kart, satt opp session-log hook

---

## Active Context

### Current Focus
- Miljø-setup og dokumentasjon

### Latest Changes
- Satt opp WP-CLI tilgang til MAMP MySQL database
- Utforsket live prototype: `/klp-eiendom-trondheim/ferjemannsveien-10/`

### Open Questions / Blockers
- Ingen

### Important to Remember
- **WP-CLI:** Bruk alltid `PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp [command]`
- **Chrome DevTools MCP:** Tilgjengelig for å teste frontend live
- Placy er en stedbasert storytelling-plattform (WordPress)
- POI-bibliotek med Google POIs og Native POIs

---

## Prototype-innsikter (Ferjemannsveien 10)

### UI-struktur
| Komponent | Beskrivelse |
|-----------|-------------|
| **Venstre sidebar** | Story Index, kapittel-navigasjon, Global Settings |
| **Hovedinnhold** | Hero-bilder, tittel, brotekst, kapitler med POI-kort |
| **Global Settings** | Travel Mode (fots/sykkel/bil), Time Budget (5/10/15 min) |
| **Mega Drawer** | Fullskjerm kategorivisning med kart og søk |

### Funksjonalitet
- **Dynamiske reisetider:** Oppdateres basert på Travel Mode
- **Live API-data:** Bysykkel viser sanntid tilgjengelighet
- **Kategorivisning:** POIs gruppert (Sykkel, Buss, Bildeling)
- **Sykkeldistanse-kalkulator:** Velg område eller skriv adresse
- **Kart:** Mapbox med custom markers og popup

### URL-struktur
```
/[customer-slug]/[project-slug]/
Eksempel: /klp-eiendom-trondheim/ferjemannsveien-10/
```

---

## Changelog

### 2025-01-18

#### [Code] Kveld - Mapbox POI-label hiding + Session hook
- **Problem:** Mapbox streets-v12 viser masse irrelevante POI-labels (butikker, restauranter, etc.) som skaper visuelt rot
- **Løsning:** Opprettet `mapbox-utils.js` med `PlacyMapUtils.hideMapboxPOILayers()` funksjon
- **Implementert i alle kart-filer:**
  - `master-map-modal.js`
  - `poi-map-modal.js` (2 steder)
  - `chapter-mega-modal.js`
  - `neighborhood-story.js`
  - `travel-calculator.js`
- **Session-log hook:** La til `UserPromptSubmit` hook i `.claude/settings.local.json` som automatisk leser `session-log.md` og `instructions.md`
- **Files modified:**
  - `js/mapbox-utils.js` (ny)
  - `functions.php` (enqueue + dependencies)
  - Alle kart JS-filer (la til hideMapboxPOILayers-kall)
  - `.claude/settings.local.json` (la til hooks)
  - `claude/instructions.md` (korrigert stier fra `_claude/` til `claude/`)

---

### 2025-01-16

#### [Code] 22:00 - Prototype exploration
- Utforsket prototype via Chrome DevTools MCP
- Testet mega drawer, kartvisning, travel mode switching
- Dokumentert UI-struktur og funksjonalitet

#### [Code] 21:45 - Database access setup
- Konfigurert WP-CLI til å fungere med MAMP MySQL
- Opprettet wrapper-script: `.wp-cli-wrapper.sh`
- Dokumentert i `claude/instructions.md`
- **Files modified:**
  - `claude/instructions.md`
  - `claude/session-log.md`
  - `.wp-cli-wrapper.sh` (ny)

---

## Next Steps (Prioritized)

1. [ ] Fylle ut CLAUDE.md med prosjekt-spesifikk dokumentasjon
2. [ ] Utforske datastrukturen i databasen
3. [ ] Dokumentere ACF-felter og custom post types

---

## Relevant Files

| File | Description |
|------|-------------|
| `claude/CLAUDE.md` | Project documentation template |
| `claude/instructions.md` | Workflow instructions for Claude Code |
| `claude/feature-request-guide.md` | Feature request guide |
| `context-placy.md` | Placy konseptrapport |
| `context-poi-bibliotek-strategi.md` | POI-bibliotek strategi |
| `context-poi-gjenbruk-datahygiene.md` | POI gjenbruk og datahygiene |

---

## Notes

- **Tech stack:** WordPress, ACF Pro, Tailwind CSS, Mapbox, Google Places API
- **Lokal utvikling:** MAMP på macOS
- **Database:** MySQL via WP-CLI (se instructions.md for kommandoer)
