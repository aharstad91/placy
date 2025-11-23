# Placy Quick Start Guide

## 🚀 5-Minute Setup

### Step 1: Cleanup Old Data (2 min)

```bash
cd /Applications/MAMP/htdocs/placy/wp-content/themes/placy
wp eval-file placy-cleanup.php
```

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

**Then delete the cleanup file:**
```bash
rm placy-cleanup.php
```

---

### Step 2: Configure API Key (1 min)

Edit `wp-config.php`:

```php
define( 'GOOGLE_PLACES_API_KEY', 'YOUR_KEY_HERE' );
```

Get key: https://console.cloud.google.com/apis/credentials

---

### Step 3: Flush Permalinks (30 sec)

```bash
wp rewrite flush
```

Or: WP Admin → Settings → Permalinks → Save

---

### Step 4: Verify Installation (1 min)

Go to: **WP Admin → Placy → Status**

Check:
- ✅ API Key configured
- ✅ Native Points menu visible
- ✅ Google Points menu visible
- ✅ Cron jobs scheduled

---

### Step 5: Create Your First Points (30 sec)

**Native Point:**
```
WP Admin → Native Points → Add New
- Name: "Solsiden Restaurant"
- Coordinates: 63.4378, 10.3982
- Address: "Beddingen 8, 7014 Trondheim"
- Save
```

**Google Point:**
```
WP Admin → Google Points → Add New
- Google Place ID: ChIJ... (get from Google Places Finder)
- Save (data fetches automatically)
```

---

## ✅ Done!

You're ready to use the Placy point system.

**Next:**
- Read `PLACY_IMPLEMENTATION_README.md` for full documentation
- Assign points to stories
- Query via GraphQL

---

## 🆘 Quick Troubleshooting

**No API data?**
```bash
# Check API key
wp eval "echo defined('GOOGLE_PLACES_API_KEY') ? 'OK' : 'NOT SET';"

# Test manual refresh
wp eval "placy_refresh_google_point(POST_ID);"
```

**Cron not scheduled?**
```bash
# List cron jobs
wp cron event list | grep placy

# Force schedule
wp eval "placy_schedule_daily_refresh();"
wp eval "placy_schedule_weekly_refresh();"
```

**Old points still showing?**
- Re-run cleanup script
- Clear WordPress cache
- Check database: `wp db query "SELECT * FROM wp_posts WHERE post_type='point'"`

---

**Time to complete:** ~5 minutes  
**Result:** ✅ Fully functional Placy point system
