# TODO: Implementer `poi-list-dynamic` blokk for Google Places API

## 📋 Oversikt
Duplikere `poi-list` blokk og lage `poi-list-dynamic` som kun viser Google Places API-resultater med egne søkeparametere per blokk.

---

## ✅ Steg 1: Opprett ny blokk
- [ ] Kopier `/blocks/poi-list/` til `/blocks/poi-list-dynamic/`
- [ ] Rename filer og referanser fra `poi-list` til `poi-list-dynamic`
- [ ] Oppdater `block.json`:
  - `name`: `"acf/poi-list-dynamic"`
  - `title`: `"POI List Dynamic"`
  - `description`: `"Dynamisk POI-liste fra Google Places API"`

---

## ✅ Steg 2: Legg til Google Places API attributes i `poi-list-dynamic/block.json`

```json
{
  "name": "acf/poi-list-dynamic",
  "title": "POI List Dynamic",
  "description": "Dynamisk POI-liste fra Google Places API",
  "category": "placy-blocks",
  "icon": "location-alt",
  "keywords": ["poi", "point", "interest", "google", "places", "dynamic"],
  "acf": {
    "mode": "preview",
    "renderTemplate": "render.php"
  },
  "supports": {
    "align": false,
    "mode": true,
    "jsx": true
  },
  "attributes": {
    "placesEnabled": {
      "type": "boolean",
      "default": true
    },
    "placesCategory": {
      "type": "string",
      "default": "restaurant"
    },
    "placesKeyword": {
      "type": "string",
      "default": ""
    },
    "placesRadius": {
      "type": "number",
      "default": 1500
    },
    "placesMinRating": {
      "type": "number",
      "default": 4.3
    },
    "placesMinReviews": {
      "type": "number",
      "default": 50
    },
    "placesExcludeTypes": {
      "type": "array",
      "default": ["lodging"]
    }
  }
}
```

---

## ✅ Steg 3: Oppdater `poi-list-dynamic/block.js`

### 3a. Fjern POI-selector UI
- [ ] Fjern `InspectorControls` for POI-valg (siden denne blokken ikke har manuelle POIs)
- [ ] Kun behov for block wrapper i editor

### 3b. Legg til Google Places API panel

```javascript
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { 
    PanelBody, 
    ToggleControl, 
    SelectControl, 
    TextControl, 
    RangeControl,
    CheckboxControl 
} from '@wordpress/components';

registerBlockType('acf/poi-list-dynamic', {
    edit: (props) => {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();

        // Handle exclude types array
        const toggleExcludeType = (type) => {
            const currentTypes = attributes.placesExcludeTypes || [];
            const newTypes = currentTypes.includes(type)
                ? currentTypes.filter(t => t !== type)
                : [...currentTypes, type];
            setAttributes({ placesExcludeTypes: newTypes });
        };

        const isTypeExcluded = (type) => {
            return (attributes.placesExcludeTypes || []).includes(type);
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Google Places API - Søkeparametere" initialOpen={true}>
                        <ToggleControl
                            label="Aktiver Google Places API"
                            checked={attributes.placesEnabled}
                            onChange={(value) => setAttributes({ placesEnabled: value })}
                            help="Vis dynamiske steder fra Google Places API"
                        />

                        {attributes.placesEnabled && (
                            <>
                                <SelectControl
                                    label="Kategori"
                                    value={attributes.placesCategory}
                                    options={[
                                        { label: 'Restaurant', value: 'restaurant' },
                                        { label: 'Cafe', value: 'cafe' },
                                        { label: 'Bar', value: 'bar' },
                                        { label: 'Bakery', value: 'bakery' },
                                        { label: 'Takeaway', value: 'meal_takeaway' },
                                        { label: 'Food (generelt)', value: 'food' }
                                    ]}
                                    onChange={(value) => setAttributes({ placesCategory: value })}
                                    help="Velg type sted å søke etter"
                                />

                                <TextControl
                                    label="Søkeord (valgfritt)"
                                    value={attributes.placesKeyword}
                                    onChange={(value) => setAttributes({ placesKeyword: value })}
                                    placeholder="F.eks. 'pizza', 'sushi', 'fine dining'"
                                    help="Type sted å søke etter"
                                />

                                <RangeControl
                                    label="Søkeradius (meter)"
                                    value={attributes.placesRadius}
                                    onChange={(value) => setAttributes({ placesRadius: value })}
                                    min={500}
                                    max={3000}
                                    step={100}
                                    help="Hvor langt fra sentrum skal vi søke?"
                                />

                                <RangeControl
                                    label="Minimum rating"
                                    value={attributes.placesMinRating}
                                    onChange={(value) => setAttributes({ placesMinRating: value })}
                                    min={3.0}
                                    max={5.0}
                                    step={0.1}
                                    help="Laveste godkjente rating (0-5)"
                                />

                                <RangeControl
                                    label="Minimum antall anmeldelser"
                                    value={attributes.placesMinReviews}
                                    onChange={(value) => setAttributes({ placesMinReviews: value })}
                                    min={0}
                                    max={200}
                                    step={10}
                                    help="Minimum antall Google-anmeldelser"
                                />

                                <hr />
                                <p><strong>Ekskluder typer (valgfritt)</strong></p>
                                <p style={{ fontSize: '12px', color: '#666', marginTop: '-8px' }}>
                                    Velg hvilke type steder som skal filtreres bort fra resultatene
                                </p>

                                <CheckboxControl
                                    label="Hoteller (lodging)"
                                    checked={isTypeExcluded('lodging')}
                                    onChange={() => toggleExcludeType('lodging')}
                                />

                                <CheckboxControl
                                    label="Sykehus/Apotek (hospital, pharmacy)"
                                    checked={isTypeExcluded('hospital') || isTypeExcluded('pharmacy')}
                                    onChange={() => {
                                        toggleExcludeType('hospital');
                                        toggleExcludeType('pharmacy');
                                    }}
                                />

                                <CheckboxControl
                                    label="Transport (gas_station, car_rental, parking)"
                                    checked={isTypeExcluded('gas_station') || isTypeExcluded('car_rental') || isTypeExcluded('parking')}
                                    onChange={() => {
                                        toggleExcludeType('gas_station');
                                        toggleExcludeType('car_rental');
                                        toggleExcludeType('parking');
                                    }}
                                />
                            </>
                        )}
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{
                        padding: '20px',
                        backgroundColor: '#f0f0f0',
                        border: '2px dashed #ccc',
                        borderRadius: '4px',
                        textAlign: 'center'
                    }}>
                        <p style={{ margin: 0, fontWeight: 'bold' }}>
                            POI List Dynamic (Google Places API)
                        </p>
                        <p style={{ margin: '8px 0 0', fontSize: '14px', color: '#666' }}>
                            Kategori: {attributes.placesCategory}
                            {attributes.placesKeyword && ` | Søkeord: "${attributes.placesKeyword}"`}
                        </p>
                        <p style={{ margin: '4px 0 0', fontSize: '12px', color: '#999' }}>
                            Dynamiske resultater vises på frontend
                        </p>
                    </div>
                </div>
            </>
        );
    },

    save: () => {
        return null; // Dynamic block rendered via PHP
    }
});
```

---

## ✅ Steg 4: Oppdater `poi-list-dynamic/render.php`

### 4a. Fjern manuell POI-rendering
- [ ] Fjern WP_Query for å hente POI posts
- [ ] Fjern loop som renderer manuelt kuraterte POIs

### 4b. Output Google Places data-attributes

```php
<?php
/**
 * POI List Dynamic Block Template
 * Viser kun dynamiske POIs fra Google Places API
 */

// Get block attributes
$places_enabled = isset($attributes['placesEnabled']) ? $attributes['placesEnabled'] : true;
$places_category = isset($attributes['placesCategory']) ? $attributes['placesCategory'] : 'restaurant';
$places_keyword = isset($attributes['placesKeyword']) ? $attributes['placesKeyword'] : '';
$places_radius = isset($attributes['placesRadius']) ? $attributes['placesRadius'] : 1500;
$places_min_rating = isset($attributes['placesMinRating']) ? $attributes['placesMinRating'] : 4.3;
$places_min_reviews = isset($attributes['placesMinReviews']) ? $attributes['placesMinReviews'] : 50;
$places_exclude_types = isset($attributes['placesExcludeTypes']) ? $attributes['placesExcludeTypes'] : array('lodging');

// Build data attributes
$data_attributes = array(
    'data-places-enabled' => $places_enabled ? 'true' : 'false',
    'data-places-category' => esc_attr($places_category),
    'data-places-keyword' => esc_attr($places_keyword),
    'data-places-radius' => esc_attr($places_radius),
    'data-places-min-rating' => esc_attr($places_min_rating),
    'data-places-min-reviews' => esc_attr($places_min_reviews),
    'data-places-exclude-types' => esc_attr(json_encode($places_exclude_types)),
);

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => 'poi-list-block poi-list-dynamic-block',
));

// Merge data attributes
foreach ($data_attributes as $key => $value) {
    $wrapper_attributes .= ' ' . $key . '="' . $value . '"';
}
?>

<div <?php echo $wrapper_attributes; ?>>
    <div class="flex flex-col">
        <!-- Google Places results will be inserted here by JavaScript -->
        <div class="poi-list-dynamic-placeholder"></div>
    </div>
</div>
```

---

## ✅ Steg 5: Oppdater JavaScript (`tema-story-map-multi.js`)

### 5a. Ny funksjon: `initDynamicPoiLists()`

```javascript
/**
 * Initialize dynamic POI lists (poi-list-dynamic blocks)
 * Each block can have its own Google Places API configuration
 */
function initDynamicPoiLists() {
    const dynamicBlocks = document.querySelectorAll('.poi-list-dynamic-block');
    
    dynamicBlocks.forEach(function(block) {
        const placesEnabled = block.getAttribute('data-places-enabled') !== 'false';
        if (!placesEnabled) return;
        
        // Get chapter ID from parent
        const chapter = block.closest('[data-chapter-id]');
        if (!chapter) {
            console.warn('Dynamic POI block found outside chapter:', block);
            return;
        }
        const chapterId = chapter.getAttribute('data-chapter-id');
        
        // Get configuration from block attributes
        const category = block.getAttribute('data-places-category') || 'restaurant';
        const keyword = block.getAttribute('data-places-keyword') || '';
        
        // Map category to Norwegian plural
        const categoryMap = {
            'restaurant': 'restauranter',
            'cafe': 'kafeer',
            'bar': 'barer',
            'bakery': 'bakerier',
            'meal_takeaway': 'takeaway-steder',
            'food': 'spisesteder'
        };
        const categoryNorwegian = categoryMap[category] || 'steder';
        
        // Add button to this specific block
        addDynamicBlockButton(block, chapterId, categoryNorwegian);
    });
}
```

### 5b. Ny funksjon: `addDynamicBlockButton()`

```javascript
/**
 * Add "Show more" button to a dynamic POI block
 * @param {HTMLElement} block - The poi-list-dynamic block element
 * @param {string} chapterId - Chapter ID for map integration
 * @param {string} categoryNorwegian - Norwegian category name for button text
 */
function addDynamicBlockButton(block, chapterId, categoryNorwegian) {
    const placeholder = block.querySelector('.poi-list-dynamic-placeholder');
    if (!placeholder) return;
    
    // Check if button already exists
    if (placeholder.querySelector('.places-api-show-all-button')) return;
    
    // Create button container
    const buttonContainer = document.createElement('div');
    buttonContainer.className = 'places-api-button-container';
    buttonContainer.style.marginTop = '24px';
    buttonContainer.style.textAlign = 'center';
    
    // Create button
    const button = document.createElement('button');
    button.className = 'places-api-show-all-button';
    button.textContent = 'Se flere ' + categoryNorwegian + ' i området';
    button.setAttribute('data-category-norwegian', categoryNorwegian);
    button.style.padding = '12px 24px';
    button.style.backgroundColor = '#EF4444';
    button.style.color = 'white';
    button.style.border = 'none';
    button.style.borderRadius = '8px';
    button.style.fontSize = '14px';
    button.style.fontWeight = '600';
    button.style.cursor = 'pointer';
    button.style.transition = 'background-color 0.2s';
    
    button.addEventListener('mouseenter', function() {
        button.style.backgroundColor = '#DC2626';
    });
    
    button.addEventListener('mouseleave', function() {
        button.style.backgroundColor = '#EF4444';
    });
    
    button.addEventListener('click', function() {
        toggleDynamicBlockResults(block, chapterId, button);
    });
    
    buttonContainer.appendChild(button);
    placeholder.appendChild(buttonContainer);
}
```

### 5c. Ny funksjon: `toggleDynamicBlockResults()`

```javascript
/**
 * Toggle showing API results for a dynamic POI block
 * @param {HTMLElement} block - The poi-list-dynamic block element
 * @param {string} chapterId - Chapter ID for map integration
 * @param {HTMLElement} button - Button element
 */
async function toggleDynamicBlockResults(block, chapterId, button) {
    const isShowing = button.hasAttribute('data-results-shown');
    
    if (isShowing) {
        // Already showing - do nothing (button will be hidden)
        return;
    }
    
    // Show loading state with spinner animation
    const categoryNorwegian = button.getAttribute('data-category-norwegian') || 'steder';
    
    // Create spinner element
    const spinner = document.createElement('span');
    spinner.style.display = 'inline-block';
    spinner.style.width = '14px';
    spinner.style.height = '14px';
    spinner.style.border = '2px solid rgba(255, 255, 255, 0.3)';
    spinner.style.borderTopColor = '#fff';
    spinner.style.borderRadius = '50%';
    spinner.style.marginRight = '8px';
    spinner.style.animation = 'spin 0.8s linear infinite';
    
    // Add keyframes for spinner if not already added
    if (!document.getElementById('spinner-keyframes')) {
        const style = document.createElement('style');
        style.id = 'spinner-keyframes';
        style.textContent = `
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    button.innerHTML = '';
    button.appendChild(spinner);
    button.appendChild(document.createTextNode('Henter lignende ' + categoryNorwegian + ' fra Google...'));
    button.disabled = true;
    button.style.opacity = '0.9';
    
    // Fetch API data but don't display yet
    const apiData = await fetchDynamicBlockData(block, chapterId);
    
    // Wait for minimum 2 seconds before displaying
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Now display the results after 2 seconds
    if (apiData && apiData.success && apiData.places.length > 0) {
        displayDynamicBlockResults(block, chapterId, apiData);
    }
    
    button.setAttribute('data-results-shown', 'true');
    
    // Hide button after results are shown
    button.style.display = 'none';
}
```

### 5d. Ny funksjon: `fetchDynamicBlockData()`

```javascript
/**
 * Fetch API data for a dynamic POI block (without displaying)
 * @param {HTMLElement} block - The poi-list-dynamic block element
 * @param {string} chapterId - Chapter ID
 * @returns {Promise<Object>} API data
 */
async function fetchDynamicBlockData(block, chapterId) {
    // Get chapter element
    const chapter = document.querySelector('[data-chapter-id="' + chapterId + '"]');
    if (!chapter) return null;
    
    // Get chapter map
    const mapContainer = document.querySelector('.chapter-map[data-chapter-id="' + chapterId + '"]');
    if (!mapContainer) return null;
    
    const mapInstance = mapContainer._mapboxInstance;
    if (!mapInstance) return null;
    
    // Get center coordinates (use start location or map center)
    let lat, lng;
    if (startLocation) {
        lng = startLocation[0];
        lat = startLocation[1];
    } else {
        const center = mapInstance.getCenter();
        lat = center.lat;
        lng = center.lng;
    }
    
    // Read configuration from block data attributes
    const category = block.getAttribute('data-places-category') || 'restaurant';
    const radius = parseInt(block.getAttribute('data-places-radius')) || 1500;
    const minRating = parseFloat(block.getAttribute('data-places-min-rating')) || 4.3;
    const minReviews = parseInt(block.getAttribute('data-places-min-reviews')) || 50;
    const keyword = block.getAttribute('data-places-keyword') || '';
    
    // Get exclude types (JSON array)
    let excludeTypes = ['lodging']; // Default
    const excludeTypesAttr = block.getAttribute('data-places-exclude-types');
    if (excludeTypesAttr) {
        try {
            excludeTypes = JSON.parse(excludeTypesAttr);
        } catch (e) {
            console.warn('Failed to parse exclude types:', e);
        }
    }
    
    // Collect Google Place IDs from existing POIs in this chapter to exclude them
    const excludePlaceIds = [];
    const poiItems = chapter.querySelectorAll('[data-google-place-id]');
    poiItems.forEach(function(poiItem) {
        const placeId = poiItem.getAttribute('data-google-place-id');
        if (placeId && placeId.trim()) {
            excludePlaceIds.push(placeId.trim());
        }
    });
    
    // Generate unique cache key for this block
    const blockId = chapterId + '-' + category + '-' + keyword;
    
    // Fetch places
    const apiData = await fetchNearbyPlaces(blockId, lat, lng, category, radius, minRating, minReviews, keyword, excludeTypes, excludePlaceIds);
    
    if (!apiData.success || apiData.places.length === 0) {
        console.warn('No places found for dynamic block:', block);
        return null;
    }
    
    return apiData;
}
```

### 5e. Ny funksjon: `displayDynamicBlockResults()`

```javascript
/**
 * Display API results for a dynamic POI block
 * @param {HTMLElement} block - The poi-list-dynamic block element
 * @param {string} chapterId - Chapter ID for map integration
 * @param {Object} apiData - API data with places
 */
function displayDynamicBlockResults(block, chapterId, apiData) {
    // Get chapter map
    const mapContainer = document.querySelector('.chapter-map[data-chapter-id="' + chapterId + '"]');
    if (!mapContainer) return;
    
    const mapInstance = mapContainer._mapboxInstance;
    if (!mapInstance) return;
    
    // Get configuration for disclaimer
    const category = block.getAttribute('data-places-category') || 'restaurant';
    const keyword = block.getAttribute('data-places-keyword') || '';
    
    // Map category to Norwegian plural
    const categoryMap = {
        'restaurant': 'restauranter',
        'cafe': 'kafeer',
        'bar': 'barer',
        'bakery': 'bakerier',
        'meal_takeaway': 'takeaway-steder',
        'food': 'spisesteder'
    };
    const categoryNorwegian = categoryMap[category] || 'steder';
    
    // Find placeholder in this block
    const placeholder = block.querySelector('.poi-list-dynamic-placeholder');
    if (!placeholder) return;
    
    // Create container for API results
    const apiContainer = document.createElement('div');
    apiContainer.className = 'places-api-results';
    apiContainer.style.marginTop = '24px';
    apiContainer.style.paddingTop = '0';
    
    // Add disclaimer header
    const disclaimerHeader = document.createElement('div');
    disclaimerHeader.className = 'google-places-disclaimer';
    disclaimerHeader.style.marginBottom = '16px';
    disclaimerHeader.style.padding = '12px 16px';
    disclaimerHeader.style.backgroundColor = '#F3F4F6';
    disclaimerHeader.style.borderLeft = '4px solid #9CA3AF';
    disclaimerHeader.style.borderRadius = '4px';
    
    let disclaimerText = '<p style="margin: 0; font-size: 14px; color: #4B5563; line-height: 1.5;">';
    disclaimerText += '<strong style="color: #1F2937;">Google-søk:</strong> ';
    
    if (keyword && keyword.trim()) {
        disclaimerText += 'Viser lignende <strong>' + categoryNorwegian + '</strong> tagget med <em>"' + keyword.trim() + '"</em>';
    } else {
        disclaimerText += 'Viser lignende <strong>' + categoryNorwegian + '</strong> i området';
    }
    
    disclaimerText += ' <span style="color: #6B7280;">— hentet fra Google Places</span>';
    disclaimerText += '</p>';
    
    disclaimerHeader.innerHTML = disclaimerText;
    apiContainer.appendChild(disclaimerHeader);
    
    // Add place cards
    apiData.places.forEach(function(place) {
        const card = createPlaceListCard(place);
        apiContainer.appendChild(card);
    });
    
    placeholder.appendChild(apiContainer);
    
    // Add markers to chapter map (shared map for all blocks in chapter)
    addPlacesMarkersToMap(mapInstance, chapterId, apiData.places);
    
    // Adjust map bounds to include new markers
    adjustMapBounds(mapInstance, apiData.places);
}
```

### 5f. Kall `initDynamicPoiLists()` i `initMap()`

```javascript
// Inside initMap() function, after chapter map loads:

chapterMap.on('load', function() {
    // Remove POI labels
    const layers = chapterMap.getStyle().layers;
    layers.forEach(function(layer) {
        if (layer.id.includes('poi') || layer.id.includes('label')) {
            chapterMap.setLayoutProperty(layer.id, 'visibility', 'none');
        }
    });
    
    // Load POI markers for this chapter
    loadChapterPOIs(chapterId, chapterMap);
    
    // Initialize dynamic POI lists for this chapter
    initDynamicPoiLists(); // ADD THIS LINE
});
```

---

## ✅ Steg 6: Fjern Google Places fra `chapter-wrapper`

### 6a. Fjern fra `blocks/chapter-wrapper/block.json`
```json
// DELETE these attributes:
"placesEnabled": { ... },
"placesCategory": { ... },
"placesKeyword": { ... },
"placesRadius": { ... },
"placesMinRating": { ... },
"placesMinReviews": { ... },
"placesExcludeTypes": { ... }
```

### 6b. Fjern fra `blocks/chapter-wrapper/block.js`
```javascript
// DELETE entire PanelBody:
<PanelBody title="Google Places API - Søkeparametere" initialOpen={false}>
    // ... all Google Places controls
</PanelBody>
```

### 6c. Fjern fra `blocks/chapter-wrapper/render.php`
```php
// DELETE these lines:
'data-places-enabled' => ...,
'data-places-category' => ...,
'data-places-keyword' => ...,
'data-places-radius' => ...,
'data-places-min-rating' => ...,
'data-places-min-reviews' => ...,
'data-places-exclude-types' => ...,
```

### 6d. Fjern fra `tema-story-map-multi.js`
```javascript
// DELETE these functions:
// - initPlacesApiIntegration() 
// - addShowAllButton() (if not used elsewhere)
// - toggleApiResults() (replaced by toggleDynamicBlockResults)

// KEEP these functions (they are reused):
// - fetchNearbyPlaces()
// - addPlacesMarkersToMap()
// - createPlaceListCard()
// - adjustMapBounds()
```

---

## ✅ Steg 7: Registrer ny blokk i WordPress

### 7a. Oppdater `functions.php`

```php
// Add to your block registration function
function placy_register_blocks() {
    // ... existing blocks
    
    // Register poi-list-dynamic block
    acf_register_block_type(array(
        'name'              => 'poi-list-dynamic',
        'title'             => __('POI List Dynamic'),
        'description'       => __('Dynamisk POI-liste fra Google Places API'),
        'render_template'   => get_template_directory() . '/blocks/poi-list-dynamic/render.php',
        'category'          => 'placy-blocks',
        'icon'              => 'location-alt',
        'keywords'          => array('poi', 'point', 'interest', 'google', 'places', 'dynamic'),
        'supports'          => array(
            'align' => false,
            'mode' => true,
            'jsx' => true,
        ),
    ));
}
add_action('acf/init', 'placy_register_blocks');
```

---

## ✅ Steg 8: Testing

### 8a. Use case 1: Restaurant chapter
- [ ] Opprett chapter med `poi-list-dynamic`
- [ ] Sett category: `restaurant`, keyword: `traditional norwegian seafood`
- [ ] Verifiser knapp vises: "Se flere restauranter i området"
- [ ] Klikk knapp → 2 sek spinner → resultater vises med disclaimer
- [ ] Markers vises på kart (røde)

### 8b. Use case 2: Hverdags-guide chapter (multi-dynamic)
- [ ] Opprett chapter med 3x `poi-list-dynamic`:
  1. Category: `pharmacy` → "Se flere apotek"
  2. Category: `store`, keyword: `optician` → "Se flere optikere"  
  3. Category: `dentist` → "Se flere tannleger"
- [ ] Verifiser hver blokk har egen knapp
- [ ] Klikk hver knapp separat → verifiser korrekte resultater
- [ ] Verifiser **alle markers** vises på **felles chapter-kart**

### 8c. Duplikat-test
- [ ] Legg til manuell `poi-list` med POI som har `google_place_id`
- [ ] Legg til `poi-list-dynamic` under
- [ ] Verifiser at manuell POI **ikke** dukker opp i dynamic-resultater

### 8d. Mixed blocks test
- [ ] Opprett chapter med:
  - `poi-highlight` (manuelt)
  - `poi-gallery` (manuelt)
  - `poi-list` (manuelt)
  - `poi-list-dynamic` #1 (restaurants)
  - `poi-list-dynamic` #2 (cafes)
- [ ] Verifiser alle vises korrekt
- [ ] Verifiser begge dynamic blocks legger markers på samme kart
- [ ] Verifiser ingen duplikater mellom manuelt og dynamic

---

## 📝 Notater

### Arkitektur
- Backend API (`google-places.php`) trenger **ingen endringer** - den er allerede klar
- CSS/styling kan gjenbrukes fra `poi-list` siden UI er identisk
- Disclaimer vises automatisk over Google Places-resultater (allerede implementert)
- Cache fungerer per API-kall, så hver `poi-list-dynamic` cacher sitt eget søk

### Caching strategi
Hver `poi-list-dynamic` får sitt eget cache-ID basert på:
```javascript
const blockId = chapterId + '-' + category + '-' + keyword;
```
Dette betyr at:
- Restaurant-blokk og cafe-blokk i samme chapter får separate caches
- Samme konfigurasjon i forskjellige chapters får separate caches
- Cache er 30 minutter (som før)

### Backward compatibility
- Eksisterende chapters med Google Places API på `chapter-wrapper` vil slutte å fungere
- Disse må migreres til å bruke `poi-list-dynamic` blokker i stedet
- Alternativt: behold chapter-wrapper logikk som fallback (men ikke anbefalt)

---

## 🎯 Resultat

### Fordeler med ny løsning:
✅ **Fleksibel arkitektur**: én eller flere `poi-list-dynamic` per chapter  
✅ **Semantisk korrekt**: hver blokk henter sin egen kategori  
✅ **Delt kart**: alle markers vises på chapter-kart  
✅ **Intuitivt**: blokk-plassering i Gutenberg gir mening visuelt  
✅ **Skalerbart**: enkelt å legge til nye kategorier og søk

### Visuell struktur i Gutenberg:
```
Chapter Wrapper
├── Heading: "Apotek"
├── POI List (manuelt kuraterte apotek)
├── POI List Dynamic (Google Places API: pharmacy)
├── Heading: "Optiker"
├── POI List (manuelt kuraterte optikere)
├── POI List Dynamic (Google Places API: store + "optician")
├── Heading: "Tannlege"
└── POI List Dynamic (Google Places API: dentist)
```

### Frontend resultat:
- Hver heading/seksjon har sine egne POIs (manuelt + dynamisk)
- Hver dynamic blokk har egen "Se flere X" knapp
- Alle markers (manuelt + alle dynamic blocks) vises på **ett felles chapter-kart**
- Tydelige disclaimers over hver dynamic seksjon
