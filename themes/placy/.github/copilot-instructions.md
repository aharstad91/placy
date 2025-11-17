# Placy WordPress Theme - AI Coding Agent Instructions

## Developer Profile

**Vibe Coder**: The developer prefers high-level architectural guidance and direct implementation over code examples. Skip boilerplate examples and focus on actionable patterns, file locations, and gotchas.

## Project Architecture

**WordPress Theme** with custom post types (CPTs), ACF Pro field groups, and Gutenberg blocks.

### Key Custom Post Types
- `theme-story`: Immersive scroll-synchronized stories with chapters
- `point`: POI locations with lat/lng coordinates
- `customer`, `project`, `detail`, `area`: Supporting content types

### Critical Files
- `/functions.php`: Theme setup, asset enqueuing, block registration
- `/inc/post-types.php`: CPT definitions
- `/inc/acf-fields.php`: ACF field group registration (programmatic via `acf_add_local_field_group`)
- `/inc/mapbox-config.php`: Mapbox token localization
- `/inc/rewrites.php`: Custom URL structures (`/{customer}/{project}/{story}/`)
- `/inc/tema-story-patterns.php`: Gutenberg block patterns for chapters

## Tema Story Feature (Scroll-Synchronized Map)

**Architecture**: Scroll-based narrative with fixed map column showing location markers.

### Key Components
1. **template**: `single-theme-story.php` (full-screen, no header/footer)
2. **JavaScript**: `/js/tema-story-map.js` (Intersection Observer + Mapbox GL JS)
3. **Blocks**:
   - `placy/chapter-wrapper`: Native Gutenberg block with InnerBlocks (defined in `/blocks/chapter-wrapper/`)
   - `acf/poi-list`: ACF block showing POI grid (3 columns: lg, 2: md, 1: mobile) with data attributes

### How It Works
- **Scroll tracking**: Intersection Observer with `rootMargin: '-50% 0px -50% 0px'` triggers at viewport midpoint
- **Marker management**: Each chapter-wrapper has data-chapter-id, poi-list items have data-poi-coords + data-poi-image
- **Map updates**: On chapter enter, `updateMapForChapter()` clears old markers, creates new ones, calls `fitBounds()`
- **Zoom-based labels**: Labels hidden at zoom ≤15, fade in with `opacity` + `visibility` transitions (NOT display:none to avoid layout jumps)
- **Featured images**: 48px circular markers with `backgroundImage` from POI featured image

### Technical Details
```javascript
// Marker structure (tema-story-map.js)
const el = document.createElement('div');
el.className = 'tema-story-marker';
if (poi.image) {
  el.style.backgroundImage = `url(${poi.image})`;
}
// Label element with opacity transitions
const label = document.createElement('div');
label.className = 'tema-story-marker-label';
label.style.transition = 'opacity 300ms ease';
```

## Tailwind CSS Workflow

**CRITICAL**: Always run `npm run watch:css` when developing. Tailwind processes:
- Input: `/css/tailwind-input.css`
- Output: `/css/tailwind-output.css`
- Config: `/tailwind.config.js`

### Font Setup
- **Primary**: Figtree (Google Fonts) via `functions.php`
- **Secondary**: campaign, campaign-serif (Adobe Typekit)
- Tailwind default sans: `['Figtree', 'sans-serif']`

### Utility Classes
Use Tailwind-first approach. Custom CSS in `/css/styles.css` only for non-utility styles (animations, complex layouts).

## ACF Block Development Pattern

### Structure
```
/blocks/{block-name}/
  ├── block.json (for native Gutenberg blocks with InnerBlocks)
  ├── block.js (editor script)
  ├── template.php (render template for ACF blocks)
  ├── style.css (frontend + editor styles)
  └── render.php (for native blocks)
```

### Registration
- **ACF blocks**: Use `acf_register_block_type()` in `functions.php`
- **Native blocks**: Use `register_block_type()` with block.json
- **Fields**: Define in `/inc/acf-fields.php` with location rules `'param' => 'block', 'value' => 'acf/block-name'`

### Example: poi-list Block
```php
// In template.php
$poi_items = get_field('poi_items'); // ACF relationship field
foreach ($poi_items as $poi) {
  $lat = get_field('latitude', $poi->ID);
  $lng = get_field('longitude', $poi->ID);
  // Output with data attributes for JS consumption
}
```

## Mapbox Integration

### Token Setup
- Token stored in `/inc/mapbox-config.php`
- Localized via `wp_localize_script('placy-tema-story-map', 'placyMapConfig', ['mapboxToken' => ...])`
- Accessed in JS: `placyMapConfig.mapboxToken`

### Map Style
- Using `mapbox://styles/mapbox/light-v11`
- Default POI/label layers hidden on load:
  ```javascript
  layers.forEach(function(layer) {
    if (layer.id.includes('poi') || layer.id.includes('label')) {
      map.setLayoutProperty(layer.id, 'visibility', 'none');
    }
  });
  ```

## Development Commands

```bash
# Start Tailwind watch (REQUIRED for CSS changes)
npm run watch:css

# Build for production
npm run build:css

# WordPress debugging (wp-config.php)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true); // Logs to /wp-content/debug.log
```

## Common Pitfalls

1. **Tailwind not updating**: Ensure watch:css is running and file paths in tailwind.config.js match
2. **ACF fields not showing**: Check location rules in `/inc/acf-fields.php` match block names exactly
3. **Map markers jumping**: Use opacity/visibility transitions, never display:none on positioned elements
4. **Scroll tracking inaccurate**: Adjust `rootMargin` in Intersection Observer config, not threshold
5. **Mapbox token errors**: Verify token in mapbox-config.php and check browser console for 401s

## Code Style Conventions

- **PHP**: WordPress Coding Standards, `esc_attr()`, `esc_html()`, `esc_url()` for all output
- **JavaScript**: ES5+ vanilla JS (no jQuery), strict mode, documented with JSDoc-style comments
- **CSS**: Tailwind utilities first, custom CSS only when necessary, BEM naming for custom classes
- **Spacing**: Tabs for PHP/CSS, 4 spaces for JavaScript

## Important Notes

- **ACF Pro required**: All ACF blocks depend on ACF Pro 6.0+
- **Node version**: Project tested with Node 18+, npm 9+
- **Browser support**: Modern evergreen browsers (Chrome, Firefox, Safari, Edge)
- **Mapbox GL JS**: v2.15.0 loaded via CDN in `single-theme-story.php`
