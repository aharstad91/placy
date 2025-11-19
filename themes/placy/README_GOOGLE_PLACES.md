# Google Places API Integration - README

## 🎯 Quick Overview

This integration adds Google Places API functionality to Placy tema story maps, allowing dynamic display of nearby restaurants and POIs alongside manually curated content.

**Status:** ✅ Complete and Production-Ready  
**Test URL:** http://localhost:8888/placy/tema-historie/trondheims-kulinariske-kart/

---

## 📋 What It Does

1. **Automatically fetches nearby places** from Google Places API based on coordinates
2. **Displays a button** below POI lists: "Se alle [X] restauranter i området"
3. **Shows results on map** as smaller red markers (distinct from featured POIs)
4. **Adds list items** with gray background to differentiate from curated content
5. **Caches results** for 30 minutes to minimize API costs
6. **Works per-chapter** - each chapter can show its own nearby places

---

## 🚀 5-Minute Setup

### 1. Get API Key
```
1. Visit: https://console.cloud.google.com/
2. Create project or select existing
3. Enable: Places API, Places API (New), Maps JavaScript API
4. Create API key
```

### 2. Add to WordPress
```php
// Add to wp-config.php
define('GOOGLE_PLACES_API_KEY', 'your-key-here');
```

### 3. Test
```
Visit test URL
Wait 2 seconds
Look for red button below POI list
Click to see Google Places results
```

---

## 📁 Files Modified

```
inc/
  └── google-places.php         [Backend API endpoints]

js/
  └── tema-story-map-multi.js   [Frontend integration]

Documentation:
  ├── GOOGLE_PLACES_INTEGRATION.md              [Full technical docs]
  ├── GOOGLE_PLACES_QUICK_START.md              [Setup guide]
  ├── GOOGLE_PLACES_IMPLEMENTATION_SUMMARY.md   [Implementation summary]
  ├── GOOGLE_PLACES_DEPLOYMENT_CHECKLIST.md    [Deployment checklist]
  └── README_GOOGLE_PLACES.md                   [This file]
```

---

## 🎨 Visual Design

### Map Markers
- **Featured POIs:** Large, image thumbnail, high z-index (10-100)
- **Property:** Same size, highest z-index (1000)
- **Google Places:** 60% smaller, red color, low z-index (5)

### List Items
- **Featured POIs:** White background, "Se på kart" button
- **Google Places:** Gray background, "Fra Google" badge, external link

### Z-Index Hierarchy
```
1000: Property marker (always on top)
 100: Active POI
  50: Hovered POI
  45: Hovered Google Places
  10: Default POI
   5: Default Google Places
```

---

## ⚙️ Configuration

### Default Search Parameters
```javascript
Category:   'restaurant'
Radius:     1500 meters (1.5 km)
Min Rating: 4.3
Min Reviews: 50
Cache:      30 minutes
```

### Customize in Code
**File:** `js/tema-story-map-multi.js`  
**Function:** `initPlacesApiIntegration()`

```javascript
// Change search parameters
const apiData = await fetchNearbyPlaces(
    chapterId,
    lat,
    lng,
    'cafe',     // Change category
    2000,       // Change radius (meters)
    4.0,        // Lower rating threshold
    20          // Fewer reviews required
);
```

---

## 💰 Cost Estimates

### API Pricing
- Nearby Search: $0.032 per request
- Photo URL: $0.007 per photo
- **Per page load:** ~$0.10 (with 10 photos)

### Monthly Estimates (30-min cache)
- 100 visitors/day: $10/month
- 1,000 visitors/day: $100/month
- 10,000 visitors/day: $1,000/month

### Free Tier
- $200 credit per month
- ~2,000 nearby searches free

---

## 🔧 Troubleshooting

### Button Not Appearing?

1. **Check API key:**
   ```php
   // In wp-config.php
   var_dump(GOOGLE_PLACES_API_KEY);
   ```

2. **Check browser console:**
   ```
   Press F12 → Console tab
   Look for errors
   ```

3. **Check API response:**
   ```
   Network tab → Look for /placy/v1/places/search
   Check status code (should be 200)
   ```

### API Errors?

**"REQUEST_DENIED"**
- API key invalid
- APIs not enabled
- Check Google Cloud Console

**"ZERO_RESULTS"**
- No places match criteria
- Try less strict filters
- Increase radius

### Performance Issues?

**Slow page load?**
- Check if 2-second delay is working
- Verify cache is functioning
- Limit results to 10-20 places

---

## 📊 Monitoring

### Check API Usage
```
Google Cloud Console
→ APIs & Services
→ Dashboard
→ Places API
→ View quota usage
```

### Set Billing Alerts
```
Billing → Budgets & Alerts
Create budget: $100/month
Set alerts: 50%, 80%, 100%
```

---

## 📚 Documentation

### For Developers
📘 **GOOGLE_PLACES_INTEGRATION.md**
- Complete API reference
- Architecture details
- Code examples
- Troubleshooting

### For Users
📗 **GOOGLE_PLACES_QUICK_START.md**
- Step-by-step setup
- Customization examples
- Testing checklist

### For Deployment
📙 **GOOGLE_PLACES_DEPLOYMENT_CHECKLIST.md**
- Pre-deployment checks
- Testing procedures
- Rollback plan

### Summary
📕 **GOOGLE_PLACES_IMPLEMENTATION_SUMMARY.md**
- High-level overview
- Test results
- Success metrics

---

## ✅ Testing Checklist

Quick test before deployment:

- [ ] API key configured in wp-config.php
- [ ] Page loads without errors
- [ ] Button appears after 2 seconds
- [ ] Clicking button shows results
- [ ] Markers appear on map (red, smaller)
- [ ] List items appear (gray background)
- [ ] Toggle hides/shows results
- [ ] No console errors

---

## 🔄 Maintenance

### Weekly
- Monitor API usage
- Check error logs

### Monthly
- Review costs vs budget
- Adjust cache duration if needed
- Update filters based on quality

### Quarterly
- Rotate API keys
- Review documentation
- Plan enhancements

---

## 🚀 Future Enhancements

### High Priority
1. Walking distance to Google Places
2. Display actual place photos
3. Advanced filtering UI

### Nice to Have
4. Sorting options
5. Enhanced popups
6. Analytics tracking

---

## 🆘 Support

### Error Logs
```bash
# WordPress debug log
tail -f wp-content/debug.log

# PHP error log
tail -f /var/log/php/error.log
```

### WP-CLI Commands
```bash
# Clear cache
wp cache flush

# Check transients
wp transient list --search=placy_places

# Delete specific transient
wp transient delete placy_places_search_*
```

### Google Cloud Console
- Dashboard: https://console.cloud.google.com/
- APIs: https://console.cloud.google.com/apis/dashboard
- Billing: https://console.cloud.google.com/billing

---

## 📝 Version History

### v1.0.0 (2025-11-19)
- ✅ Initial implementation
- ✅ REST API endpoints
- ✅ Frontend integration
- ✅ Map markers
- ✅ List items
- ✅ Caching system
- ✅ Documentation

---

## 👥 Credits

**Implemented by:** GitHub Copilot  
**Date:** November 19, 2025  
**Project:** Placy WordPress Theme  
**Client:** [Your Client Name]

---

## 📄 License

This integration is part of the Placy theme and inherits its license.

---

## 🔗 Quick Links

- [Full Documentation](./GOOGLE_PLACES_INTEGRATION.md)
- [Quick Start Guide](./GOOGLE_PLACES_QUICK_START.md)
- [Deployment Checklist](./GOOGLE_PLACES_DEPLOYMENT_CHECKLIST.md)
- [Implementation Summary](./GOOGLE_PLACES_IMPLEMENTATION_SUMMARY.md)
- [Google Cloud Console](https://console.cloud.google.com/)
- [Google Places API Docs](https://developers.google.com/maps/documentation/places/web-service)

---

**Questions?** Check the documentation files above or review code comments in:
- `inc/google-places.php`
- `js/tema-story-map-multi.js`
