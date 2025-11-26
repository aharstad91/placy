# Placy WordPress Theme - AI Coding Agent Instructions

> **Last Updated**: November 2025  
> **Tech Audit Status**: ✅ Verified

## Developer Profile

**Vibe Coder**: The developer prefers high-level architectural guidance and direct implementation over code examples. Skip boilerplate examples and focus on actionable patterns, file locations, and gotchas.

## Project Architecture

**WordPress Theme** with custom post types (CPTs), ACF Pro field groups, and Gutenberg blocks.

### Key Custom Post Types
- `theme-story`: Immersive scroll-synchronized stories with chapters
- `placy_google_point`: POI locations synced from Google Places API
- `placy_native_point`: Manually created POI locations
- `customer`, `project`, `story`, `detail`, `area`: Supporting content types

### Taxonomies (shared by point CPTs)
- `placy_categories`: Hierarchical POI categories
- `placy_tags`: Non-hierarchical POI tags
- `lifestyle_segments`: Target audience segments

### Critical Files
- `/functions.php`: Theme setup, asset enqueuing, block registration
- `/inc/post-types.php`: All CPT definitions + taxonomies
- `/inc/acf-fields.php`: Legacy ACF field groups
- `/inc/placy-acf-fields.php`: New Placy Point System ACF fields
- `/inc/mapbox-config.php`: Mapbox token localization
- `/inc/rewrites.php`: Custom URL structures
- `/inc/tema-story-patterns.php`: Gutenberg block patterns for chapters

### API Integrations (inc/ folder)
- `google-places.php`: POI place data fetching + caching (`placy_get_poi_place_data()`) + REST endpoints
- `placy-google-api.php`: Google Point sync, coordinates, rate limiting, auto-refresh
- `google-points-query.php`: REST API for querying points
- `google-points-descriptions-api.php`: AI-generated descriptions
- `entur-integration.php`: Norwegian public transport departures
- `bysykkel-integration.php`: Trondheim city bikes availability
- `placy-graphql.php`: WPGraphQL integration
- `placy-cron.php`: Scheduled tasks for data sync
- `placy-bulk-import.php`: Bulk POI import functionality
- `placy-admin.php`: Admin UI and settings pages

## Tema Story Feature (Scroll-Synchronized Map)

**Architecture**: Scroll-based narrative with fixed map column showing location markers.

### Key Components
1. **Template**: `single-theme-story.php` (full-screen, no header/footer)
2. **Main JavaScript**: `/js/tema-story-map-multi.js` (v2.3.4) - Intersection Observer + Mapbox GL JS
   - Source: 160KB (development, WP_DEBUG=true)
   - Minified: 52KB (`tema-story-map-multi.min.js`, production)
3. **Supporting Scripts** (all theme-story specific):
   - `chapter-nav.js`: Chapter navigation
   - `chapter-header.js`: Sticky chapter headers
   - `intro-parallax.js`: Hero parallax effect
   - `container-gradient.js`: Gradient overlays
   - `scroll-indicator.js`: Scroll progress indicator
   - `proximity-filter.js` (v2.0.0): Travel time/mode filtering
   - `entur-live-departures.js`: Real-time public transport
   - `bysykkel-live-availability.js`: Real-time bike availability

### Blocks
- `placy/chapter-wrapper`: Native Gutenberg block with InnerBlocks + Google Places integration
- `acf/poi-list`: Static POI grid (3 cols lg, 2 md, 1 mobile)
- `acf/poi-list-dynamic`: Dynamic POI list from Google Points
- `acf/poi-highlight`: Featured POI hero layout
- `acf/poi-gallery`: POI grid gallery
- `acf/poi-map-card`: Interactive map card
- `acf/proximity-filter`: Travel time filter UI
- `acf/image-column`: 60/40 image layout

### How It Works
- **Scroll tracking**: Intersection Observer with `rootMargin: '-50% 0px -50% 0px'` triggers at viewport midpoint
- **Marker management**: Each chapter-wrapper has data-chapter-id, poi-list items have data-poi-coords + data-poi-image
- **Map updates**: On chapter enter, clears old markers, creates new ones, calls `fitBounds()`
- **Zoom-based labels**: Labels hidden at zoom ≤15, fade in with `opacity` + `visibility` transitions
- **Featured images**: 48px circular markers with `backgroundImage` from POI featured image

## Tailwind CSS Workflow

**CRITICAL**: Always run `npm run watch:css` when developing. Tailwind processes:
- Input: `/css/tailwind-input.css`
- Output: `/css/tailwind-output.css`
- Config: `/tailwind.config.js`

### Dependencies (package.json)
```json
"devDependencies": {
  "@tailwindcss/typography": "^0.5.19",
  "autoprefixer": "^10.4.22",
  "postcss": "^8.5.6",
  "tailwindcss": "^3.4.18"
}
```

### Font Setup
- **Primary**: Figtree (Google Fonts) via `functions.php`
- **Secondary**: campaign, campaign-serif (Adobe Typekit - jlp3dzl)
- Tailwind default sans: `['Figtree', 'sans-serif']`

### Custom Colors (tailwind.config.js)
- `overvik-green`: #78908E
- `overvik-light`: #D1E5E6
- `overvik-dark`: #1a1a1a

### Utility Classes
Use Tailwind-first approach. Custom CSS in `/css/styles.css` only for non-utility styles.

## ACF Block Development Pattern

### Block Types
1. **Native Gutenberg blocks** (with InnerBlocks): Uses `block.json` + `render.php`
2. **ACF blocks**: Uses `acf_register_block_type()` + `template.php`

### Structure
```
/blocks/{block-name}/
  ├── block.json        # Native blocks only
  ├── block.js          # Editor script
  ├── template.php      # ACF blocks render template
  ├── render.php        # Native blocks render
  ├── style.css         # Frontend + editor styles
  ├── input.css         # Tailwind input (poi-list-dynamic only)
  └── tailwind.config.js # Block-specific config (poi-list-dynamic only)
```

### Current Blocks
| Block | Type | Registration |
|-------|------|-------------|
| `chapter-wrapper` | Native | `register_block_type()` |
| `poi-list-dynamic` | Native | `register_block_type()` |
| `poi-list` | ACF | `acf_register_block_type()` |
| `poi-map-card` | ACF | `acf_register_block_type()` |
| `poi-highlight` | ACF | `acf_register_block_type()` |
| `poi-gallery` | ACF | `acf_register_block_type()` |
| `image-column` | ACF | `acf_register_block_type()` |
| `proximity-filter` | ACF | `acf_register_block_type()` |

### Registration
- **ACF blocks**: Registered in `placy_register_acf_blocks()` via `acf/init` hook
- **Native blocks**: Registered in separate `placy_register_*_block()` functions via `init` hook
- **Block category**: `placy-content` with location-alt icon

## Mapbox Integration

### Token Setup
- Token stored in `/inc/mapbox-config.php`
- Localized via `wp_localize_script('placy-tema-story-map', 'placyMapConfig', [...])`
- Config includes: `mapboxToken`, `googlePlacesApiKey`, `startLocation`, `propertyLogo`, `propertyBackground`, `propertyLabel`

### Map Style
- Using `mapbox://styles/mapbox/light-v11`
- Mapbox GL JS v2.15.0 loaded via CDN
- Default POI/label layers hidden on load

## REST API Endpoints

The theme registers custom REST endpoints under `placy/v1/`:
- `entur/departures`: Real-time public transport departures
- `bysykkel/availability`: City bike station availability
- Points query API (via `google-points-query.php`)
- Descriptions API (via `google-points-descriptions-api.php`)

## Development Commands

```bash
# Start Tailwind watch (REQUIRED for CSS changes)
npm run watch:css

# Build for production
npm run build:css

# Build blocks (runs build-blocks.sh)
npm run build:blocks

# Minify main JS (tema-story-map-multi.js → .min.js)
npm run build:js

# Lint JavaScript files
npm run lint

# Lint and auto-fix
npm run lint:fix

# Full build
npm run build

# WordPress debugging (wp-config.php)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true); # Logs to /wp-content/debug.log
```

## Common Pitfalls

1. **Tailwind not updating**: Ensure `watch:css` is running and file paths in `tailwind.config.js` match
2. **ACF fields not showing**: Check location rules in `/inc/acf-fields.php` or `/inc/placy-acf-fields.php`
3. **Map markers jumping**: Use opacity/visibility transitions, never display:none on positioned elements
4. **Scroll tracking inaccurate**: Adjust `rootMargin` in Intersection Observer config, not threshold
5. **Mapbox token errors**: Verify token in `mapbox-config.php` and check browser console for 401s
6. **Google Places API errors**: Check `GOOGLE_PLACES_API_KEY` constant in wp-config.php
7. **Block styles not loading**: Verify file exists and is registered in `placy_enqueue_block_assets()`
8. **Multiple JS files**: Main map script is `tema-story-map-multi.js` (v2.3.4), NOT `tema-story-map.js`
9. **JS not minified**: Run `npm run build:js` after changes to regenerate `.min.js`

## JavaScript Files Overview

### Active (enqueued in functions.php)
- `poi-map-modal.js`: POI map modal functionality (global)
- `tema-story-map-multi.js`: Main scroll-sync map (theme-story only)
- `chapter-nav.js`, `chapter-header.js`, `intro-parallax.js`, `container-gradient.js`, `scroll-indicator.js`
- `proximity-filter.js`, `entur-live-departures.js`, `bysykkel-live-availability.js`

## Code Style Conventions

- **PHP**: WordPress Coding Standards, escape all output (`esc_attr()`, `esc_html()`, `esc_url()`)
- **JavaScript**: ES5+ vanilla JS (no jQuery), strict mode, JSDoc comments
- **CSS**: Tailwind utilities first, BEM naming for custom classes
- **Spacing**: Tabs for PHP/CSS, 4 spaces for JavaScript

## Important Notes

- **ACF Pro required**: All ACF blocks depend on ACF Pro 6.0+
- **Node version**: Tested with Node 18+, npm 9+
- **Browser support**: Modern evergreen browsers (Chrome, Firefox, Safari, Edge)
- **Mapbox GL JS**: v2.15.0 loaded via CDN
- **WPGraphQL**: Optional integration for headless queries (`placy-graphql.php`)
