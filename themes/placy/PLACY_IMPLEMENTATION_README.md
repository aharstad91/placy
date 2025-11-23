# Placy Point System Implementation

## ✅ Installation Complete

The new Placy point system has been implemented with the following components:

### 📁 Files Created

1. **`/inc/placy-acf-fields.php`** - ACF field groups for Native and Google Points
2. **`/inc/placy-google-api.php`** - Google Places API integration with rate limiting
3. **`/inc/placy-graphql.php`** - Unified GraphQL schema and resolvers
4. **`/inc/placy-cron.php`** - Scheduled refresh jobs (daily/weekly)
5. **`/inc/placy-admin.php`** - Admin dashboard, status page, and utilities
6. **`placy-cleanup.php`** - One-time cleanup script for old CPT

### 📝 Files Modified

- **`/inc/post-types.php`** - Replaced old `point` CPT with `placy_native_point` and `placy_google_point`
- **`functions.php`** - Added includes for new Placy files

---

## 🚀 Next Steps

### Phase 0: Cleanup (IMPORTANT - Do this first!)

**⚠️ Backup your database before proceeding!**

```bash
# Option 1: Via WP-CLI (Recommended)
cd /Applications/MAMP/htdocs/placy/wp-content/themes/placy
wp eval-file placy-cleanup.php

# Option 2: Via Browser
# Visit: http://yourdomain.local/wp-admin/?placy_cleanup=1
```

This will:
- Delete all old `point` posts
- Remove `point_type` taxonomy and terms
- Clean up orphaned data
- Flush rewrite rules

**After cleanup:**
1. Delete `placy-cleanup.php` file
2. Verify old "Points" menu is gone from admin
3. Check new "Native Points" and "Google Points" menus appear

---

### Phase 1: Configure API Keys

Add to `wp-config.php`:

```php
// Google Places API Key
define( 'GOOGLE_PLACES_API_KEY', 'your-api-key-here' );

// Already configured:
// define( 'MAPBOX_ACCESS_TOKEN', '...' );
```

Get API key: https://console.cloud.google.com/apis/credentials

**Enable APIs:**
- Places API (New)
- Maps JavaScript API

---

### Phase 2: Flush Permalinks

```bash
wp rewrite flush

# Or via WP Admin:
# Settings → Permalinks → Save Changes
```

---

### Phase 3: Verify Installation

**Check Admin:**
1. Go to WP Admin
2. Look for "Placy" menu in sidebar
3. Click "Placy → Status"
4. Verify:
   - ✓ Google Places API Key configured
   - ✓ Mapbox Token configured
   - ✓ Cron jobs scheduled

**Check CPTs:**
- "Native Points" menu should be visible
- "Google Points" menu should be visible
- Categories, Tags, Lifestyle Segments taxonomies available

---

## 📖 Usage Guide

### Creating a Native Point

1. **WP Admin → Native Points → Add New**

2. **Fill in fields:**
   - **Location Tab:**
     - Name (required)
     - Address
     - Coordinates: Latitude, Longitude (required)
   
   - **Content Tab:**
     - Description (WYSIWYG)
     - Images (gallery)
   
   - **Contact Tab:**
     - Website
     - Phone
   
   - **Display Tab:**
     - Featured (checkbox)
     - Display Priority (1-10 slider)
     - Hide from Display (checkbox)
   
   - **Business Tab:**
     - Is Sponsored
     - Sponsor Info (conditional)
     - Seasonal Active Period
     - Internal Notes

3. **Assign Taxonomies:**
   - Categories (hierarchical)
   - Tags
   - Lifestyle Segments

4. **Publish**

---

### Creating a Google Point

1. **Get Google Place ID:**
   - Visit: https://developers.google.com/maps/documentation/places/web-service/place-id
   - Search for location
   - Copy Place ID

2. **WP Admin → Google Points → Add New**

3. **Fill in fields:**
   - **Google Data Tab:**
     - Google Place ID (required) - paste Place ID
     - Save post → Data fetches automatically
     - Click "🔄 Refresh Google Data Now" to update manually
   
   - **Editorial Tab:**
     - Editorial Text (custom content to supplement Google data)
     - Featured checkbox
     - Display Priority
   
   - **Business Tab:**
     - Same as Native Points

4. **Assign Taxonomies** (same as Native)

5. **Publish**

**Data Fetched from Google:**
- Name
- Rating & review count
- Address
- Coordinates
- Photos
- Website (Place Details)
- Phone number (Place Details)
- Opening hours

---

### Assigning Points to Stories

Add to Story or Theme Story ACF fields:

```php
// Field: points
// Type: Relationship
// Post Types: placy_native_point, placy_google_point
```

Points will be available in GraphQL queries automatically.

---

## 🔄 Automatic Refresh Schedule

**Daily Refresh (Featured Points):**
- Runs at 2:00 AM daily
- Refreshes all points with `featured = true`
- Uses 2 API requests per point (Nearby Search + Place Details)

**Weekly Refresh (Regular Points):**
- Runs at 3:00 AM every Sunday
- Refreshes non-featured points
- Only refreshes if data is older than 7 days

**Manual Refresh:**
- Edit any Google Point
- Click "🔄 Refresh Google Data Now" button
- Uses 2 API requests

**Rate Limiting:**
- Default: 100 requests/day
- Customize with filter: `placy_google_api_daily_limit`
- Current usage visible in: Placy → Status

---

## 📊 Admin Dashboard

**Placy → Status**

View:
- Total points count (Native + Google)
- Featured points count
- API usage meter (requests used today)
- Recent Google Points with sync status
- Cron schedule

---

## 🔍 GraphQL Schema

### Query Example

```graphql
query GetStoryPoints($storyId: ID!) {
  story(id: $storyId, idType: DATABASE_ID) {
    title
    placyPoints {
      id
      type
      name
      location {
        latitude
        longitude
        address
      }
      description
      images {
        url
        alt
        width
        height
      }
      rating
      reviewCount
      website
      phone
      categories
      tags
      featured
      displayPriority
      attribution {
        source
        logo
      }
    }
  }
}
```

### Unified PlacyPoint Type

All points (Native and Google) return the same structure:

- `id` - Post ID
- `type` - "native" or "google"
- `name` - Point name
- `location` - { latitude, longitude, address }
- `description` - Content
- `images` - Array of images
- `rating` - Rating (null for Native)
- `reviewCount` - Review count (null for Native)
- `website` - Website URL
- `phone` - Phone number
- `categories` - Array of category names
- `tags` - Array of tag names
- `featured` - Boolean
- `displayPriority` - 1-10
- `attribution` - { source, logo }

---

## 🎨 Frontend Integration

### React Component Example

```jsx
function PlacyPoint({ point }) {
  return (
    <div 
      className="placy-point" 
      data-poi-id={point.id}
      data-poi-coords={JSON.stringify(point.location)}
    >
      {point.images[0] && (
        <img src={point.images[0].url} alt={point.name} />
      )}
      
      <h3>{point.name}</h3>
      
      {point.rating && (
        <div className="rating">
          ⭐ {point.rating} ({point.reviewCount})
        </div>
      )}
      
      <p className="address">{point.location.address}</p>
      
      {point.description && (
        <div dangerouslySetInnerHTML={{ __html: point.description }} />
      )}
      
      <div className="contact">
        {point.website && (
          <a href={point.website} target="_blank" rel="noopener">
            Website
          </a>
        )}
        {point.phone && (
          <a href={`tel:${point.phone}`}>{point.phone}</a>
        )}
      </div>
      
      {point.attribution && point.attribution.source === 'Google' && (
        <footer className="attribution">
          <img src="/google-logo.svg" alt="Powered by Google" />
        </footer>
      )}
    </div>
  );
}
```

---

## 🧪 Testing Checklist

### CPTs & Taxonomies
- [ ] Native Points menu visible
- [ ] Google Points menu visible
- [ ] Can create Native Point
- [ ] Can create Google Point
- [ ] Categories work on both types
- [ ] Tags work on both types
- [ ] Same taxonomy terms shared

### Google API
- [ ] API key configured
- [ ] Google Point auto-fetches on first save
- [ ] Manual refresh button works
- [ ] Data cached in ACF fields
- [ ] Rate limiting works
- [ ] Error handling works

### GraphQL
- [ ] Query returns Native Points
- [ ] Query returns Google Points
- [ ] Unified structure correct
- [ ] Website/phone fields populated
- [ ] Images array correct

### Frontend
- [ ] POIs render correctly
- [ ] Google attribution shows
- [ ] Native and Google look identical
- [ ] Links work (website, phone)
- [ ] Images display

### Proximity Filter
- [ ] Filter works with both types
- [ ] Coordinates parsed correctly
- [ ] Distance calculations correct

### Cron
- [ ] Daily cron scheduled
- [ ] Weekly cron scheduled
- [ ] Manual WP-CLI run works
- [ ] Refresh respects tiers (featured vs regular)

---

## 🛠️ Troubleshooting

### "Google data not showing"
1. Check API key is correct in wp-config.php
2. Verify Place ID is valid
3. Check postmeta: `nearby_search_cache`, `place_details_cache`
4. Look at error logs: `tail -f /path/to/error.log`

### "Cron not running"
```bash
# List scheduled events
wp cron event list

# Run manually
wp cron event run placy_daily_refresh

# Check if DISABLE_WP_CRON is set
grep DISABLE_WP_CRON wp-config.php
```

### "Rate limit reached"
- Check: Placy → Status → API Usage
- Wait until tomorrow (resets at midnight)
- Or increase limit with filter:
  ```php
  add_filter( 'placy_google_api_daily_limit', function() {
      return 200; // Increase to 200
  });
  ```

### "Proximity filter not working"
1. Verify `data-poi-id` attribute on cards
2. Check `data-poi-coords` format: `{"latitude":63.44,"longitude":10.40}`
3. Test Mapbox API key is valid
4. Check browser console for errors

---

## 📚 Documentation

- **Architecture:** See attached `placy_implementation.md`
- **Code Comments:** All functions documented inline
- **WP-CLI Commands:**
  ```bash
  # List points
  wp post list --post_type=placy_native_point
  wp post list --post_type=placy_google_point
  
  # Refresh specific point
  wp eval "placy_refresh_google_point(123);"
  
  # Check cron
  wp cron event list
  wp cron event run placy_daily_refresh
  ```

---

## 🎯 What's Working

✅ Two separate CPTs (Native & Google)  
✅ Shared taxonomies (Categories, Tags, Lifestyle)  
✅ ACF fields for both types  
✅ Google Places API integration  
✅ Automatic data fetch on save  
✅ Manual refresh button  
✅ Rate limiting (100/day)  
✅ Unified GraphQL schema  
✅ Cron jobs (daily/weekly)  
✅ Admin dashboard & status page  
✅ Custom admin columns  

---

## 🔮 Future Enhancements

- [ ] Google Search Modal in Story Editor
- [ ] Bulk import from Google Places
- [ ] Point analytics dashboard
- [ ] Custom map markers per category
- [ ] Point relationships graph
- [ ] Export/import functionality

---

## 📧 Support

For questions or issues, check:
1. This README
2. Code comments in `/inc/placy-*.php` files
3. Error logs
4. Placy → Status page

---

**Installation Date:** November 22, 2025  
**Version:** 2.0.0  
**Status:** ✅ Ready for Testing
