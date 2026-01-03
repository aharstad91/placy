# Placy v3 - AI Coding Instructions

> **Last Updated**: December 31, 2025  
> **Architecture Version**: v3 (Modal-based system)  
> **Tech Audit Status**: ✅ Verified via frontend + codebase exploration

---

## Communication Style

Act as a calm advisor.

**Do NOT:**
- Oversell solutions
- Sugarcoat problems

**DO:**
1. Identify weak assumptions
2. Point out logical gaps
3. Say clearly what is unclear or unsupported

If something is risky or wrong, say it directly. No hedging.

**The developer is a "vibe coder" - prioritize clarity over code:**
- **Be concise**: Short, clear responses
- **No code examples in explanations**: Don't answer questions by showing code blocks
- **Ask questions**: When unclear, ask clarifying questions before implementing
- **Plain language**: Explain concepts without technical jargon when possible
- **Collaborate**: Work through problems together via Q&A, not code dumps

---

## Workflow & Preferences

**Development Flow:**
1. **Small changes** (CSS, minor tweaks) - just do it
2. **New features** - ask clarifying questions first
3. **Breaking changes** - inform and fix immediately
4. **Validate programmatically** - check functionality works via WP-CLI/terminal
5. **Chrome MCP** - always use and setup tests, we have access to several Chrome MCP Servers

**Code Standards:**
- All code/comments in **English**
- UI labels/content in **Norwegian**
- Use WordPress/PHP standard conventions
- Standard naming (no special preferences)
- Normal error handling (not paranoid, not reckless)

**Git & Commits:**
- AI can commit changes directly
- Use clear, descriptive commit messages

**File Management:**
- Create temporary/experimental files freely during development
- **Clean up** after validation - delete unused/temporary files
- Keep codebase tidy once features are confirmed working

**Priority:**
1. Get functionality validated first
2. Organize and clean up after
3. "Pent og ryddig" comes after "it works"

---

## Project Overview

Placy v3 is a **modal-based WordPress platform** for location-based storytelling. Properties (projects) showcase their neighborhoods through interactive POI cards and maps.

**Content Hierarchy**: `Customer → Project → Story Chapter → POIs`

**User Flow (v3):**
```
Project Page (single-project.php)
    ├── Sidebar (property info, CTA, travel times)
    ├── Story Chapters (scrollable content blocks)
    │   └── POI Cards (click to open...)
    │       └── Chapter Mega-Modal (full story about one chapter)
    │           └── POI Details (within modal)
    └── Master Map Button ("Alle steder")
        └── Master Map Modal (all POIs from all chapters)
```

---

## v3 Architecture Overview

### The Modal System (Core of v3)

Placy v3 uses a **modal-based architecture** where clicking any POI card opens a full-screen modal experience:

1. **Chapter Mega-Modal** (`chapter-mega-modal.js` - 2307 lines)
   - Opens when clicking any POI card on the project page
   - Shows full chapter story with map + scrollable content
   - Left side: Mapbox map with all chapter POIs
   - Right side: Scrollable content with POI details
   - Navigation between POIs within the modal
   - Close button returns to project page

2. **Master Map Modal** (`master-map-modal.js` - 565 lines)
   - Opens via "Alle steder" button
   - Shows ALL POIs from ALL chapters on one map
   - Filter/search capabilities
   - Click POI → opens that POI's chapter modal

3. **Neighborhood Story State** (`neighborhood-story.js` - 676 lines)
   - Global state manager for the modal system
   - Tracks open/closed modals, current POI, chapter data
   - Handles data passing between components

### Key Templates

| Template | Purpose | Status |
|----------|---------|--------|
| `single-project.php` | Main project page (105 lines) | **v3 CORE** |
| `sidebar.php` | Project sidebar with travel times | **v3 CORE** |
| `single-theme-story.php` | Full-screen scroll-sync story | **LEGACY v2** |
| `single-story.php` | Story template | Check usage |

### Key JavaScript Files

| File | Size | Status | Purpose |
|------|------|--------|---------|
| `chapter-mega-modal.js` | 99KB | **v3 CORE** | Main modal logic |
| `master-map-modal.js` | 18KB | **v3 CORE** | Full map modal |
| `neighborhood-story.js` | 26KB | **v3 CORE** | Global state manager |
| `project-sidebar.js` | 13KB | **v3 CORE** | Sidebar interactions |
| `entur-live-departures.js` | 17KB | **v3 CORE** | Public transport API |
| `bysykkel-live-availability.js` | 12KB | **v3 CORE** | City bikes API |
| `hyre-live-availability.js` | 13KB | **v3 CORE** | Car sharing API |
| `tema-story-map-multi.js` | **168KB** | **LEGACY v2** | Old scroll-sync map |
| `chapter-nav.js` | - | Check if used | Chapter navigation |

### Key PHP Include Files

| File | Lines | Purpose |
|------|-------|---------|
| `inc/acf-story-chapter.php` | 484 | Story Chapter block registration + render |
| `inc/mapbox-directions-api.php` | 382 | Travel time calculation (Mapbox + Nominatim) |
| `inc/entur-integration.php` | 482 | Entur public transport API |
| `inc/bysykkel-integration.php` | 133 | Trondheim city bikes API |
| `inc/hyre-integration.php` | 334 | Hyre car sharing API |
| `inc/post-types.php` | 469 | All CPT definitions |
| `inc/neighborhood-story.php` | - | Asset loader for NS system |
| `inc/google-places.php` | - | Google Places data fetching |

---

## Custom Post Types

| CPT Slug | Admin Label | Purpose |
|----------|-------------|---------|
| `customer` | Kunder | Client companies |
| `project` | Prosjekter | Properties/developments (main v3 content) |
| `placy_google_point` | Google Points | POIs synced from Google Places |
| `placy_native_point` | Native Points | Manually created POIs |
| `theme-story` | Tema Historier | **LEGACY** - v2 scroll-sync stories |
| `detail` | Details | Supporting content |
| `area` | Areas | Geographic areas |

### Taxonomies (shared by point CPTs)
- `placy_categories`: Hierarchical POI categories
- `placy_tags`: Non-hierarchical POI tags
- `lifestyle_segments`: Target audience segments

---

## Blocks

### Story Chapter Block (Main v3 Block)

The `story-chapter` block is the primary content unit in v3:

- **Registration**: `inc/acf-story-chapter.php` via `acf_register_block_type()`
- **Render**: `blocks/story-chapter/render.php`
- **Data**: ACF fields define chapter title, description, POI selection

### Other Active Blocks

| Block | Type | Path |
|-------|------|------|
| `poi-list-dynamic` | Native | `blocks/poi-list-dynamic/` |
| `poi-entur` | ACF | `blocks/poi-entur/` |
| `poi-bysykkel` | ACF | `blocks/poi-bysykkel/` |
| `poi-hyre` | ACF | `blocks/poi-hyre/` |
| `poi-map-card` | ACF | `blocks/poi-map-card/` |

### Potentially Legacy Blocks (verify usage)

- `chapter-wrapper/` - Native Gutenberg, may be v2
- `poi-list/` - Check if superseded by poi-list-dynamic
- `poi-gallery/` - Check current usage
- `poi-highlight/` - Check current usage
- `proximity-filter/` - Check current usage
- `image-column/` - Check current usage

---

## API Integrations

### External APIs

| API | Purpose | File | Rate Limiting |
|-----|---------|------|---------------|
| **Mapbox Directions** | Travel time/distance | `mapbox-directions-api.php` | Via transient cache |
| **Nominatim** | Geocoding (fallback) | `mapbox-directions-api.php` | 1 req/sec |
| **Entur** | Public transport times | `entur-integration.php` | Cached |
| **Bysykkel** | Bike availability | `bysykkel-integration.php` | Cached |
| **Hyre** | Car sharing availability | `hyre-integration.php` | Cached |
| **Google Places** | POI data sync | `google-places.php` | Cached |

### REST API Endpoints

All under `placy/v1/`:

- `entur/departures` - Real-time departures
- `bysykkel/availability` - Bike station status
- `hyre/availability` - Car availability
- `directions/travel-time` - Travel time calculation
- Points query endpoints

---

## Development Environment

### Local Environment
- **Server**: MAMP at `/Applications/MAMP/htdocs/placy/`
- **Theme**: `/Applications/MAMP/htdocs/placy/wp-content/themes/placy/`
- **Site URL**: `http://localhost:8888/placy/`
- **Admin URL**: `http://localhost:8888/placy/wp-admin/`

### WordPress Admin Access (AI Agents with Chrome MCP)
- **Username**: `Claude-Agent`
- **Password**: `EfKgP7O85PwnlCey5&L7uMb2`
- **Login URL**: `http://localhost:8888/placy/wp-login.php`

### API Keys (Local Dev)
- **Mapbox**: `pk.eyJ1IjoiYW5kcmVhc2hhcnN0YWQiLCJhIjoiY21keXQ3Y3EwMDVlejJucjF0dzhuc24zNSJ9.73a_RLe-4_6O3-6ubAS94g`
- **Google Places**: Set in `wp-config.php` as `GOOGLE_PLACES_API_KEY`

### Git Repository
- **Location**: Theme folder (`/themes/placy/`)
- **Backup Branch**: `backup/pre-v3-cleanup-2025-12-31`

---

## Development Commands

```bash
# Start Tailwind watch (REQUIRED for CSS changes)
npm run watch:css

# Build for production
npm run build:css

# Build blocks
npm run build:blocks

# Minify JS
npm run build:js

# Full build
npm run build

# WordPress debugging (wp-config.php)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);  # Logs to /wp-content/debug.log
```

---

## Tailwind CSS

- **Input**: `/css/tailwind-input.css`
- **Output**: `/css/tailwind-output.css`
- **Config**: `/tailwind.config.js`

**CRITICAL**: Always run `npm run watch:css` when developing.

### Fonts
- **Primary**: Figtree (Google Fonts)
- **Secondary**: Campaign, Campaign-serif (Adobe Typekit - jlp3dzl)

---

## Mapbox Integration

- **GL JS Version**: 2.15.0 (CDN)
- **Style**: `mapbox://styles/mapbox/light-v11`
- **Token Config**: `inc/mapbox-config.php`

Localized via:
```php
wp_localize_script('placy-tema-story-map', 'placyMapConfig', [...])
```

---

## Legacy Code (v1/v2) - Candidates for Removal

### High Confidence Legacy

| File | Size | Reason |
|------|------|--------|
| `js/tema-story-map-multi.js` | 168KB | v2 scroll-sync system, replaced by modal system |
| `single-theme-story.php` | - | v2 template for scroll-sync stories |
| `theme-story` CPT | - | v2 content type, check if still used |

### Needs Verification

| File | Notes |
|------|-------|
| `inc/acf-chip-scrollytelling.php` | May be v2 scroll system |
| `inc/acf-focus-panel.php` | Check if used in v3 |
| `blocks/chapter-wrapper/` | May be v2 component |
| `js/chapter-nav.js` | Check if used in v3 modals |
| `js/intro-parallax.js` | Check current usage |
| `js/scroll-indicator.js` | May be v2 only |
| `js/container-gradient.js` | Check current usage |

### Safe to Keep

All files in "v3 CORE" status above, plus:
- All `inc/` files for API integrations
- All `blocks/poi-*` blocks
- `blocks/story-chapter/`

---

## Common Pitfalls

1. **Tailwind not updating**: Ensure `watch:css` is running
2. **Modal not opening**: Check `chapter-mega-modal.js` console errors
3. **Map not loading**: Verify Mapbox token in `mapbox-config.php`
4. **Travel times failing**: Check Nominatim rate limiting (1 req/sec)
5. **ACF fields not showing**: Verify location rules in ACF admin
6. **JS errors**: Check browser console, main files are unminified in dev
7. **POIs not appearing**: Check `placy_google_point` and `placy_native_point` posts

---

## File Structure Reference

```
wp-content/themes/placy/
├── functions.php              # Theme setup, asset enqueuing
├── style.css                  # Theme metadata
├── header.php, footer.php, sidebar.php
├── single-project.php         # v3 main template
├── single-theme-story.php     # LEGACY v2 template
│
├── inc/
│   ├── post-types.php         # CPT definitions
│   ├── acf-story-chapter.php  # Story Chapter block
│   ├── neighborhood-story.php # NS asset loader
│   ├── mapbox-directions-api.php
│   ├── entur-integration.php
│   ├── bysykkel-integration.php
│   ├── hyre-integration.php
│   ├── google-places.php
│   └── ...
│
├── js/
│   ├── chapter-mega-modal.js  # v3 CORE
│   ├── master-map-modal.js    # v3 CORE
│   ├── neighborhood-story.js  # v3 CORE
│   ├── project-sidebar.js     # v3 CORE
│   ├── entur-live-departures.js
│   ├── bysykkel-live-availability.js
│   ├── hyre-live-availability.js
│   ├── tema-story-map-multi.js # LEGACY v2
│   └── ...
│
├── css/
│   ├── tailwind-input.css
│   ├── tailwind-output.css
│   └── styles.css
│
├── blocks/
│   ├── story-chapter/         # v3 main block
│   ├── poi-list-dynamic/
│   ├── poi-entur/
│   ├── poi-bysykkel/
│   ├── poi-hyre/
│   └── ...
│
└── template-parts/
```

---

## Important Notes

- **ACF Pro 6.6.2** required - all blocks depend on it
- **WordPress 6.9** current version
- **Node 18+** for build tools
- **Mapbox GL JS 2.15.0** via CDN
- **UI Language**: Norwegian
- **Code Language**: English
