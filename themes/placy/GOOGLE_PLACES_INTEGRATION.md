# Google Places API Integration - Technical Documentation

## Overview

This document describes the Google Places API integration for the Placy theme, which allows dynamic fetching and display of nearby restaurants and points of interest on tema story maps.

**Implementation Date:** November 19, 2025  
**Test URL:** http://localhost:8888/placy/tema-historie/trondheims-kulinariske-kart/

---

## Architecture

### Backend (WordPress REST API)

#### 1. Places Search Endpoint

**Endpoint:** `/wp-json/placy/v1/places/search`  
**Method:** GET  
**File:** `inc/google-places.php`

**Parameters:**
- `lat` (required, number): Latitude coordinate
- `lng` (required, number): Longitude coordinate
- `category` (optional, string, default: 'restaurant'): Place type to search for
- `radius` (optional, integer, default: 1500): Search radius in meters
- `minRating` (optional, number, default: 4.0): Minimum rating filter (0-5)
- `minReviews` (optional, integer, default: 50): Minimum number of reviews

**Response Format:**
```json
{
  "success": true,
  "count": 15,
  "places": [
    {
      "name": "Restaurant Name",
      "placeId": "ChIJ...",
      "rating": 4.5,
      "userRatingsTotal": 234,
      "vicinity": "Street Address, City",
      "coordinates": {
        "lat": 63.4305,
        "lng": 10.3951
      },
      "priceLevel": 2,
      "openNow": true,
      "photoReference": "Aaw_E...",
      "types": ["restaurant", "food"]
    }
  ],
  "filters": {
    "category": "restaurant",
    "minRating": 4.3,
    "minReviews": 50,
    "radius": 1500
  }
}
```

**Caching:**
- Cache duration: 30 minutes
- Cache key format: `placy_places_search_{category}_{lat}_{lng}_{radius}`
- Cache type: WordPress transients

**API Cost:**
- Nearby Search: $0.032 per request
- Cached results minimize repeat API calls

---

#### 2. Places Photo Endpoint

**Endpoint:** `/wp-json/placy/v1/places/photo/{photo_reference}`  
**Method:** GET  
**File:** `inc/google-places.php`

**Parameters:**
- `photo_reference` (required, string): Photo reference from Places API
- `maxwidth` (optional, integer, default: 400): Maximum photo width

**Response Format:**
```json
{
  "success": true,
  "photoUrl": "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photo_reference=Aaw_E...&key=..."
}
```

**Notes:**
- Returns Google CDN URL for frontend to fetch directly
- No image proxying or storage required
- Photo API cost: $0.007 per request

---

### Frontend (JavaScript)

**File:** `js/tema-story-map-multi.js`

#### Key Functions

1. **`fetchNearbyPlaces(chapterId, lat, lng, category, radius, minRating, minReviews)`**
   - Fetches places from REST API
   - Caches results per chapter in JavaScript
   - Returns Promise with API response

2. **`addPlacesMarkersToMap(mapInstance, chapterId, places)`**
   - Creates smaller red circular markers for Google Places
   - Z-index: 5 (lower than featured POI markers at 10)
   - Hover effects and popup on click

3. **`showPlacePopup(mapInstance, place, markerElement)`**
   - Displays Mapbox popup with place details
   - Includes rating, address, open status, link to Google Maps

4. **`addShowAllButton(chapterId, count)`**
   - Adds red button below POI list: "Se alle [X] restauranter i området"
   - Dynamically shows count from API response

5. **`toggleApiResults(chapterId, button)`**
   - Shows/hides Google Places results
   - Toggles between "Se alle" and "Skjul ekstra restauranter"

6. **`addPlacesListItems(chapterId, places)`**
   - Adds list cards for Google Places
   - Light gray background to differentiate from featured POIs
   - "Fra Google" badge
   - Link to Google Maps instead of map interaction

7. **`adjustMapBounds(mapInstance, places)`**
   - Fits map to show both featured POIs and sample of Google Places
   - Prevents excessive zoom out by only including 5 sample places

---

## UI/UX Design

### Map Markers

#### Featured POI Markers
- Size: Dynamic based on zoom (24-48px)
- Style: Circular with image thumbnail
- Border: 3px solid white
- Z-index: 10 (default), 50 (hover), 100 (active)
- Label: Always visible at zoom ≥15

#### Property Marker
- Size: Same as POI markers
- Style: Property logo/background
- Z-index: 1000 (always on top)
- Label: Always visible ("Punkt A")

#### Google Places Markers
- Size: 60% of POI markers (smaller)
- Style: Red circular (#EF4444)
- Icon: 📍 emoji
- Border: 2px solid white
- Z-index: 5 (lower than featured POIs)
- Label: Hidden at zoom <15, name + rating + "Google" badge

### Visual Hierarchy

```
Z-index layers (top to bottom):
1000: Property marker (always on top)
100:  Active POI marker
50:   Hovered POI marker
45:   Hovered Google Places marker
10:   Default POI markers
5:    Default Google Places markers
```

### List Items

#### Featured POI Cards
- Background: White
- Border: Gray
- Image: Featured image thumbnail
- Rating: From Google Places (if available)
- Button: "Se på kart" (interacts with map)

#### Google Places Cards
- Background: Light gray (#F9FAFB)
- Border: Gray
- Badge: "Fra Google" label
- Image: Placeholder with 📍 icon
- Rating: From Google API
- Status: "Åpent nå" / "Stengt"
- Button: "Se på Google Maps" (external link)

---

## Implementation Details

### Test Case Configuration

For "Trondheims kulinariske kart" test page:

```javascript
// Search parameters
{
  category: 'restaurant',  // or 'fine_dining'
  lat: 63.4305,           // Stasjonskvartalet/Nidarosdomen area
  lng: 10.3951,
  radius: 1500,           // 1.5 km
  minRating: 4.3,
  minReviews: 50
}
```

### Category Options

Common Google Places types:
- `restaurant`: All restaurants
- `fine_dining`: Upscale restaurants (not a standard type, filter manually)
- `cafe`: Cafés
- `bar`: Bars
- `bakery`: Bakeries
- `meal_takeaway`: Takeaway restaurants

For fine dining, use `restaurant` + filter by `priceLevel >= 3`.

---

## API Configuration

### Required API Key

Set in `wp-config.php`:

```php
define('GOOGLE_PLACES_API_KEY', 'your-api-key-here');
```

### Required Google APIs

Enable these in Google Cloud Console:
1. Places API (New)
2. Places API (Legacy) - for Nearby Search
3. Maps JavaScript API - for photo URLs

### API Quota & Cost Estimates

**Per page load (estimated):**
- 1 Nearby Search request: $0.032
- 10 Photo URL requests: $0.070
- **Total per load:** ~$0.10

**With caching (30 min):**
- Average cost per unique visitor: $0.10
- With 100 visitors/day: ~$10/day = $300/month
- With 1000 visitors/day: ~$100/day = $3000/month

**Recommendations:**
- Monitor usage in Google Cloud Console
- Set billing alerts at $100, $500, $1000
- Consider increasing cache duration to 1-2 hours if content doesn't change frequently
- Implement rate limiting per user IP if needed

---

## Error Handling

### Backend Errors
- Missing API key: Returns 500 error
- API request failure: Logs error, returns empty results
- Invalid response: Logs error, returns empty results

### Frontend Errors
- API fetch failure: Console error, no UI change
- No results: Button not displayed
- Network timeout: Shows loading state indefinitely (consider adding timeout)

### Cache Management

Clear cache manually:
```php
// In WordPress admin or via WP-CLI
do_action('admin_post_placy_clear_place_caches');
```

Or programmatically:
```php
placy_clear_all_place_caches();
```

---

## Performance Considerations

### Optimization Strategies

1. **Lazy Loading**: API integration initializes 2 seconds after page load
2. **Caching**: 30-minute server-side cache reduces API calls
3. **Client-side Cache**: Results stored in JavaScript Map per chapter
4. **Debounced Requests**: Only fetch when "Show All" button is clicked
5. **Sample Bounds**: Only include 5 places when adjusting map bounds

### Load Time Impact

- Initial page load: No impact (lazy initialization)
- Button click: ~300-800ms (API call + rendering)
- Subsequent toggles: Instant (cached in JavaScript)

---

## Security Considerations

1. **API Key Protection**:
   - Key stored in wp-config.php (not version controlled)
   - Server-side proxy prevents client exposure
   - Consider HTTP Referrer restrictions in Google Cloud

2. **Rate Limiting**:
   - WordPress handles request throttling
   - Consider implementing IP-based rate limiting if abuse occurs

3. **Input Validation**:
   - All parameters validated in REST endpoint
   - Coordinates sanitized as floats
   - Category whitelist recommended

---

## Testing Checklist

### Functional Testing
- [ ] API endpoint returns valid data
- [ ] Button displays with correct count
- [ ] Clicking button shows/hides results
- [ ] Map markers appear in correct locations
- [ ] Marker popups display place details
- [ ] List cards render with correct data
- [ ] "Fra Google" badge is visible
- [ ] External Google Maps links work

### UI/UX Testing
- [ ] Featured markers visually dominant over Google Places markers
- [ ] Z-index layering correct (Property > Active POI > Google Places)
- [ ] Map doesn't zoom out excessively with many results
- [ ] List items clearly differentiated (gray background)
- [ ] Button states clear (loading, active, inactive)

### Edge Cases
- [ ] Zero results from API (button not shown)
- [ ] API error (silent failure, no UI disruption)
- [ ] No coordinates available (integration disabled)
- [ ] Multiple chapters on same page (each independent)
- [ ] Toggle multiple chapters simultaneously

### Performance Testing
- [ ] Initial page load not delayed by integration
- [ ] API call completes within 1 second
- [ ] No memory leaks with repeated toggles
- [ ] Cache reduces subsequent requests

---

## Future Enhancements

### Recommended Improvements

1. **Advanced Filtering**:
   - Add UI controls for radius, rating, review count
   - Category dropdown (restaurants, cafes, bars)
   - Price level filter

2. **Better Image Handling**:
   - Fetch and display actual place photos
   - Cache photo URLs in transients
   - Lazy load images on scroll

3. **Enhanced Popups**:
   - Show full description if available
   - Display photos in popup
   - Add "Get Directions" functionality

4. **Walking Distances**:
   - Calculate walking time to Google Places
   - Use same Mapbox Directions API as featured POIs
   - Display in list and on markers

5. **Sorting & Filtering**:
   - Sort by distance, rating, or reviews
   - Filter by open now, price level
   - Search within results

6. **Analytics**:
   - Track button clicks
   - Monitor which places users click
   - Measure API cost per session

---

## Troubleshooting

### Button Not Appearing

**Possible causes:**
1. No POI list blocks in chapter
2. API key not configured
3. API returned no results (filters too strict)
4. JavaScript error preventing initialization

**Debug steps:**
```javascript
// In browser console
console.log(placesApiResults);  // Check cached results
console.log(showingApiResults); // Check toggle state
```

### Markers Not Showing

**Possible causes:**
1. Map not initialized when function called
2. Invalid coordinates in API response
3. Markers removed by another function
4. Z-index conflict with other elements

**Debug steps:**
```javascript
// Check marker count
console.log(placesMarkers.length);

// Check map instance
const mapContainer = document.querySelector('.chapter-map');
console.log(mapContainer._mapboxInstance);
```

### API Errors

**Check API key:**
```php
// In wp-config.php
var_dump(GOOGLE_PLACES_API_KEY);
```

**Check API response:**
```javascript
// In browser Network tab
// Look for requests to /wp-json/placy/v1/places/search
// Check response status and body
```

**Common API errors:**
- `REQUEST_DENIED`: API key not valid or APIs not enabled
- `ZERO_RESULTS`: No places match criteria
- `OVER_QUERY_LIMIT`: Exceeded API quota

---

## Code Examples

### Manually Trigger API Call

```javascript
// In browser console
fetchNearbyPlaces('chapter-1', 63.4305, 10.3951, 'restaurant', 1500, 4.3, 50)
  .then(data => console.log(data));
```

### Clear Cache Programmatically

```php
// In theme functions or plugin
add_action('init', function() {
    if (current_user_can('manage_options') && isset($_GET['clear_places_cache'])) {
        placy_clear_all_place_caches();
        wp_redirect(remove_query_arg('clear_places_cache'));
        exit;
    }
});
```

### Customize Search Parameters

```javascript
// Edit in tema-story-map-multi.js, initPlacesApiIntegration function
const apiData = await fetchNearbyPlaces(
    chapterId, 
    lat, 
    lng, 
    'cafe',      // Change category
    2000,        // Increase radius to 2km
    4.5,         // Higher rating threshold
    100          // More reviews required
);
```

---

## Maintenance

### Regular Tasks

1. **Monitor API Usage** (weekly):
   - Check Google Cloud Console for quota usage
   - Review costs against budget
   - Adjust cache duration if needed

2. **Update API Key** (as needed):
   - Rotate keys annually for security
   - Update in wp-config.php
   - Test all functionality after rotation

3. **Review Results Quality** (monthly):
   - Check if returned places are relevant
   - Adjust filters (rating, reviews) if needed
   - Consider manual curation for important pages

4. **Performance Monitoring** (monthly):
   - Check page load times
   - Monitor JavaScript errors in console
   - Review cache hit rates

---

## Version History

- **v1.0.0** (2025-11-19): Initial implementation
  - REST API endpoints for search and photos
  - Frontend integration with tema story maps
  - Button UI and toggle functionality
  - Secondary marker layer with red styling
  - List items with gray background differentiation

---

## Support & Contact

For issues or questions about this integration:
1. Check this documentation first
2. Review code comments in `inc/google-places.php` and `js/tema-story-map-multi.js`
3. Test with browser console open to see errors
4. Check WordPress debug logs for backend errors

**Files Modified:**
- `inc/google-places.php` - Backend API endpoints
- `js/tema-story-map-multi.js` - Frontend integration

**Dependencies:**
- WordPress REST API
- Mapbox GL JS
- Google Places API (Legacy)
- Google Places API (New)
