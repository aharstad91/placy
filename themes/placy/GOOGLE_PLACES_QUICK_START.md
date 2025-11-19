# Google Places API Integration - Quick Start Guide

## Setup (5 minutes)

### 1. Get Google Places API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable these APIs:
   - Places API (New)
   - Places API
   - Maps JavaScript API
4. Go to Credentials → Create Credentials → API Key
5. Copy the API key

### 2. Configure WordPress

Add to `wp-config.php`:

```php
define('GOOGLE_PLACES_API_KEY', 'YOUR_API_KEY_HERE');
```

### 3. Test the Integration

Visit: http://localhost:8888/placy/tema-historie/trondheims-kulinariske-kart/

**Expected behavior:**
- Page loads normally
- After 2 seconds, red button appears below POI list
- Button text: "Se alle [X] restauranter i området"
- Clicking button shows Google Places results on map and in list
- Red markers appear on map (smaller than featured markers)
- List items have gray background and "Fra Google" badge

---

## How It Works

### User Flow

```
1. User loads tema story page
   ↓
2. Page renders normally with featured POIs
   ↓
3. After 2 seconds, API fetches nearby places count
   ↓
4. If places found, button appears below POI list
   ↓
5. User clicks "Se alle X restauranter i området"
   ↓
6. Button shows "Laster..." state
   ↓
7. JavaScript fetches full place details from API
   ↓
8. Red markers added to map (z-index 5, below featured POIs)
   ↓
9. Gray list cards added below featured POIs
   ↓
10. Map bounds adjust to show new places
    ↓
11. Button changes to "Skjul ekstra restauranter"
    ↓
12. User can toggle on/off (instant, cached)
```

### Architecture

```
┌─────────────────────────────────────────────┐
│  WordPress Backend                          │
│  ┌─────────────────────────────────────┐   │
│  │ REST API Endpoints                  │   │
│  │ /placy/v1/places/search            │   │
│  │ /placy/v1/places/photo/:ref        │   │
│  └─────────────────────────────────────┘   │
│           ↓                                 │
│  ┌─────────────────────────────────────┐   │
│  │ Google Places API Integration       │   │
│  │ - Nearby Search                     │   │
│  │ - Photo URLs                        │   │
│  │ - Filtering & caching (30 min)     │   │
│  └─────────────────────────────────────┘   │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│  Frontend JavaScript                        │
│  ┌─────────────────────────────────────┐   │
│  │ State Management                    │   │
│  │ - placesApiResults (Map)           │   │
│  │ - showingApiResults (Map)          │   │
│  │ - placesMarkers (Array)            │   │
│  └─────────────────────────────────────┘   │
│           ↓                                 │
│  ┌─────────────────────────────────────┐   │
│  │ UI Components                       │   │
│  │ - "Show All" button                │   │
│  │ - Map markers (red, smaller)       │   │
│  │ - List cards (gray background)     │   │
│  └─────────────────────────────────────┘   │
└─────────────────────────────────────────────┘
```

---

## Customization

### Change Search Parameters

**File:** `js/tema-story-map-multi.js`  
**Function:** `initPlacesApiIntegration()`

```javascript
// Default (fine dining focus)
const apiData = await fetchNearbyPlaces(
    chapterId, 
    lat, 
    lng, 
    'restaurant',  // Category
    1500,          // Radius (meters)
    4.3,           // Min rating
    50             // Min reviews
);

// Example: Cafés with lower threshold
const apiData = await fetchNearbyPlaces(
    chapterId, 
    lat, 
    lng, 
    'cafe',        // Show cafés instead
    1000,          // Smaller radius
    4.0,           // Lower rating OK
    20             // Fewer reviews OK
);

// Example: All restaurants, less strict
const apiData = await fetchNearbyPlaces(
    chapterId, 
    lat, 
    lng, 
    'restaurant',
    2000,          // Larger radius
    3.5,           // More inclusive
    10             // Include newer places
);
```

### Adjust Cache Duration

**File:** `inc/google-places.php`  
**Function:** `placy_places_nearby_search()`

```php
// Default: 30 minutes
set_transient($cache_key, $response_data, 30 * MINUTE_IN_SECONDS);

// Change to 1 hour
set_transient($cache_key, $response_data, HOUR_IN_SECONDS);

// Change to 2 hours
set_transient($cache_key, $response_data, 2 * HOUR_IN_SECONDS);
```

### Change Marker Styling

**File:** `js/tema-story-map-multi.js`  
**Function:** `addPlacesMarkersToMap()`

```javascript
// Default: Red circular markers
circleContainer.style.backgroundColor = '#EF4444';

// Change to green
circleContainer.style.backgroundColor = '#10B981';

// Change to blue
circleContainer.style.backgroundColor = '#3B82F6';

// Change size multiplier (default 0.6 of POI markers)
const initialSize = Math.max(24, getMarkerSize(mapInstance.getZoom()) * 0.7);
```

### Change Button Text

**File:** `js/tema-story-map-multi.js`  
**Function:** `addShowAllButton()`

```javascript
// Default
button.textContent = 'Se alle ' + count + ' restauranter i området';

// Custom
button.textContent = 'Vis ' + count + ' flere restauranter';
button.textContent = 'Utforsk ' + count + ' steder i nærheten';
button.textContent = count + ' restauranter fra Google Maps →';
```

---

## Troubleshooting

### Button Not Appearing

**Check 1: Is API key configured?**
```php
// In wp-config.php
var_dump(GOOGLE_PLACES_API_KEY); // Should output your key
```

**Check 2: Are there POI lists in chapter?**
```html
<!-- Look for this in page source -->
<div class="poi-list-block">
```

**Check 3: Any JavaScript errors?**
```
Open browser console (F12)
Look for red errors
Check Network tab for failed API requests
```

**Check 4: Did API return results?**
```javascript
// In browser console
console.log(placesApiResults);
// Should show Map with chapter IDs as keys
```

### API Key Errors

**Error: "REQUEST_DENIED"**
- API key is invalid
- APIs not enabled in Google Cloud
- API key restrictions blocking requests

**Solution:**
1. Go to Google Cloud Console
2. Check API key is active
3. Enable all required APIs
4. Remove restrictions temporarily to test

### No Results Returned

**Possible causes:**
- Filters too strict (minRating, minReviews)
- No places in area matching category
- Radius too small

**Solution:**
```javascript
// Try more lenient search
const apiData = await fetchNearbyPlaces(
    chapterId, 
    lat, 
    lng, 
    'restaurant',
    3000,    // Increase radius
    3.0,     // Lower rating threshold
    5        // Fewer reviews required
);
```

### Performance Issues

**Symptom:** Page loads slowly

**Check 1: Is initialization delayed?**
```javascript
// Should be 2000ms delay
setTimeout(function() {
    initPlacesApiIntegration();
}, 2000);
```

**Check 2: Is caching working?**
```
First click: ~500ms (API call)
Second click: Instant (cached)
```

**Check 3: Too many markers?**
```javascript
// Limit results in API
// Edit inc/google-places.php
// Add after line where $places array is built:
$places = array_slice($places, 0, 20); // Max 20 results
```

---

## Testing Checklist

### Basic Functionality
- [ ] Button appears after page load
- [ ] Button shows correct count
- [ ] Clicking button shows results
- [ ] Map markers appear
- [ ] List items appear
- [ ] Toggle hides results
- [ ] Second toggle shows cached results instantly

### Visual Quality
- [ ] Markers smaller than featured POIs
- [ ] Markers red colored
- [ ] List items gray background
- [ ] "Fra Google" badge visible
- [ ] No layout shifts

### Edge Cases
- [ ] Works with no start location
- [ ] Works with multiple chapters
- [ ] Works with no featured POIs
- [ ] Handles API errors gracefully

---

## Cost Management

### Monitor Usage

**Google Cloud Console:**
1. Go to APIs & Services → Dashboard
2. Select Places API
3. View quota usage and costs

### Set Billing Alerts

1. Go to Billing → Budgets & Alerts
2. Create budget: $100/month
3. Set alert thresholds: 50%, 80%, 100%

### Reduce Costs

**Strategy 1: Increase cache**
```php
// 30 min → 2 hours = 75% cost reduction
set_transient($cache_key, $response_data, 2 * HOUR_IN_SECONDS);
```

**Strategy 2: Limit results**
```php
// Reduce from 20 to 10 results
$places = array_slice($places, 0, 10);
```

**Strategy 3: Per-page configuration**
```php
// Only enable on specific pages
if (is_singular('theme-story') && get_field('enable_places_api')) {
    // Initialize integration
}
```

---

## API Limits & Quotas

### Free Tier
- $200 credit per month
- ~2000 nearby searches
- ~28,000 photo URL requests

### Paid Tier
- $0.032 per Nearby Search
- $0.007 per Photo URL
- No hard limits, but set budgets to prevent overages

### Recommended Quotas
- Development: 1000 requests/day
- Production: 10,000 requests/day
- Set alerts at 80% of quota

---

## Next Steps

1. **Test on staging:**
   - Verify all functionality
   - Check performance
   - Review costs after 1 week

2. **Adjust parameters:**
   - Fine-tune search radius
   - Adjust rating/review thresholds
   - Customize visual styling

3. **Monitor & optimize:**
   - Track API usage weekly
   - Adjust cache duration based on update frequency
   - Consider manual curation for popular pages

4. **Enhance UX:**
   - Add loading skeleton for list items
   - Implement photo fetching for place cards
   - Add walking distance calculations
   - Enable sorting/filtering UI

---

## Files Reference

**Backend:**
- `inc/google-places.php` - API integration and endpoints

**Frontend:**
- `js/tema-story-map-multi.js` - Map integration and UI

**Documentation:**
- `GOOGLE_PLACES_INTEGRATION.md` - Full technical documentation
- `GOOGLE_PLACES_QUICK_START.md` - This guide

**Configuration:**
- `wp-config.php` - API key storage

---

## Support

If issues persist:
1. Check full documentation: `GOOGLE_PLACES_INTEGRATION.md`
2. Review code comments in modified files
3. Test with browser console open
4. Check WordPress debug logs

**Common mistakes:**
- Forgot to add API key to wp-config.php
- APIs not enabled in Google Cloud Console
- Cache not cleared after code changes
- JavaScript errors preventing initialization
