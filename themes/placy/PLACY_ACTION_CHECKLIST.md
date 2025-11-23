# Placy Implementation - Action Checklist

## ⚠️ CRITICAL: Do These First!

### 1. Backup Database
```bash
cd /Applications/MAMP/htdocs/placy
wp db export backup_before_placy_$(date +%Y%m%d_%H%M%S).sql
```

**Expected output:**
```
Success: Exported to 'backup_before_placy_20251122_143000.sql'.
```

✅ **Verify backup exists before proceeding!**

---

## 🚀 Implementation Steps

### Step 1: Run Cleanup Script

```bash
cd /Applications/MAMP/htdocs/placy/wp-content/themes/placy

# Run cleanup
wp eval-file placy-cleanup.php
```

**What it does:**
- Deletes old `point` posts
- Removes `point_type` taxonomy
- Cleans database relationships
- Flushes rewrite rules

**Expected output:**
```
Starting Placy cleanup...

✓ Deleted X point posts
✓ Deleted X point_type terms
✓ Cleaned term relationships
✓ Cleaned term taxonomy
✓ Cleaned orphaned terms
✓ Cleaned orphaned postmeta
✓ Flushed rewrite rules

✅ Cleanup complete!
```

**After cleanup:**
```bash
# Delete the cleanup file
rm placy-cleanup.php
```

---

### Step 2: Configure API Key

Edit: `/Applications/MAMP/htdocs/placy/wp-config.php`

Add before `/* That's all, stop editing! */`:

```php
/**
 * Google Places API Key
 */
define( 'GOOGLE_PLACES_API_KEY', 'YOUR_KEY_HERE' );
```

**Get API key:**
1. Go to: https://console.cloud.google.com/apis/credentials
2. Create new API key (or use existing)
3. Enable APIs:
   - Places API (New)
   - Maps JavaScript API
4. Copy key and paste in wp-config.php

**Verify:**
```bash
wp eval "echo defined('GOOGLE_PLACES_API_KEY') ? 'API Key: Configured' : 'API Key: NOT SET';"
```

---

### Step 3: Flush Permalinks

```bash
wp rewrite flush
```

**Or via browser:**
1. Go to WP Admin
2. Settings → Permalinks
3. Click "Save Changes"

---

### Step 4: Verify Installation

```bash
# Check CPTs are registered
wp post-type list --fields=name | grep placy

# Expected output:
# placy_native_point
# placy_google_point
```

**Via WP Admin:**
1. Go to WP Admin
2. Look for these new menu items:
   - ✅ "Native Points"
   - ✅ "Google Points"
   - ✅ "Placy" (admin menu)

3. Click "Placy → Status"
4. Verify:
   - ✅ Google Places API Key: Configured
   - ✅ Mapbox Token: Configured
   - ✅ Daily cron scheduled
   - ✅ Weekly cron scheduled

---

### Step 5: Test Creating Points

**Test Native Point:**
```
WP Admin → Native Points → Add New

Fill in:
- Title: "Test Native Point"
- Location tab:
  - Name: "Test Native Point"
  - Latitude: 63.4378
  - Longitude: 10.3982
  - Address: "Trondheim"
- Categories: Add/select one
- Publish

✅ Should save without errors
```

**Test Google Point:**

First, get a Place ID:
1. Go to: https://developers.google.com/maps/documentation/places/web-service/place-id
2. Search: "Bakklandet Skydsstation, Trondheim"
3. Copy Place ID (starts with "ChIJ...")

Then:
```
WP Admin → Google Points → Add New

Fill in:
- Title: "Test Google Point"
- Google Data tab:
  - Google Place ID: [paste Place ID]
- Save Draft

✅ Should auto-fetch data after save
✅ nearby_search_cache should populate with JSON
✅ last_synced should show current timestamp

Click "🔄 Refresh Google Data Now" button
✅ Should show success message
```

---

## ✅ Verification Checklist

After completing all steps:

- [ ] Database backed up
- [ ] Cleanup script run successfully
- [ ] placy-cleanup.php deleted
- [ ] API key configured in wp-config.php
- [ ] Permalinks flushed
- [ ] Native Points menu visible
- [ ] Google Points menu visible
- [ ] Placy admin menu visible
- [ ] Placy → Status shows green checkmarks
- [ ] Test Native Point created successfully
- [ ] Test Google Point created successfully
- [ ] Google data auto-fetched
- [ ] Refresh button works

---

## 🧪 Test GraphQL (Optional)

If WPGraphQL is active:

```bash
wp graphql execute "
query {
  nativePoints {
    nodes {
      id
      title
    }
  }
  googlePoints {
    nodes {
      id
      title
    }
  }
}
"
```

---

## 🔧 Troubleshooting

### "Old Points menu still showing"
```bash
# Re-run cleanup
wp eval-file placy-cleanup.php

# Force flush
wp rewrite flush --hard
```

### "API key not working"
```bash
# Test API key directly
curl "https://maps.googleapis.com/maps/api/place/details/json?place_id=ChIJN1t_tDeeuEAR1fh2i14vBm8&key=YOUR_KEY"

# Should return JSON, not error
```

### "Cron not scheduled"
```bash
# List cron jobs
wp cron event list | grep placy

# Force schedule
wp eval "placy_schedule_daily_refresh(); placy_schedule_weekly_refresh();"

# Verify
wp cron event list | grep placy
```

### "No Google data fetching"
```bash
# Check error log
tail -f /Applications/MAMP/logs/php_error.log

# Test manual refresh
wp eval "placy_refresh_google_point(POST_ID);"
```

---

## 📚 Next Steps

After successful verification:

1. **Read Documentation:**
   - `PLACY_QUICK_START.md` - Quick reference
   - `PLACY_IMPLEMENTATION_README.md` - Full manual
   - `PLACY_IMPLEMENTATION_SUMMARY.md` - Technical details

2. **Update Story Fields:**
   - Add "points" relationship field to Story CPT
   - Post types: placy_native_point, placy_google_point
   - Return format: Post Object

3. **Update Frontend:**
   - Update GraphQL queries to use new schema
   - Test unified point rendering
   - Add Google attribution logo

4. **Monitor:**
   - Check Placy → Status daily
   - Monitor API usage
   - Watch error logs

---

## 🆘 Need Help?

**Check these in order:**
1. This checklist
2. Error logs: `tail -f /Applications/MAMP/logs/php_error.log`
3. Placy → Status page
4. `PLACY_IMPLEMENTATION_README.md`
5. Code comments in `/inc/placy-*.php` files

**Common issues:**
- API key → Check wp-config.php syntax
- Cleanup → Re-run script, check database
- Cron → Check DISABLE_WP_CRON not set
- GraphQL → Ensure WPGraphQL plugin active

---

**Status:** Ready to Execute  
**Estimated Time:** 5-10 minutes  
**Risk Level:** Low (with backup)  
**Rollback:** Restore from backup SQL file
