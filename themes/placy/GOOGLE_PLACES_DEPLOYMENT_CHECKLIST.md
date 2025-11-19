# Google Places API Integration - Deployment Checklist

## Pre-Deployment

### 1. Google Cloud Setup
- [ ] Google Cloud project created
- [ ] Billing enabled on project
- [ ] **Places API (New)** enabled
- [ ] **Places API** (Legacy) enabled
- [ ] **Maps JavaScript API** enabled
- [ ] API key created
- [ ] API key restrictions configured (optional but recommended)
- [ ] Billing alerts set ($100, $500, $1000)
- [ ] Quota alerts configured

### 2. WordPress Configuration
- [ ] API key added to `wp-config.php`:
  ```php
  define('GOOGLE_PLACES_API_KEY', 'your-key-here');
  ```
- [ ] File permissions correct (wp-config.php should be 600 or 400)
- [ ] WordPress debug mode enabled for testing:
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  ```

### 3. Code Deployment
- [ ] `inc/google-places.php` updated with new endpoints
- [ ] `js/tema-story-map-multi.js` updated with Places integration
- [ ] JavaScript minified for production (optional)
- [ ] Cache cleared (WP cache, CDN cache, browser cache)
- [ ] Files uploaded to server via SFTP/Git

### 4. Testing on Staging

#### API Endpoints
- [ ] Test search endpoint: `/wp-json/placy/v1/places/search?lat=63.4305&lng=10.3951&category=restaurant&radius=1500`
- [ ] Verify JSON response structure
- [ ] Check cache headers (should cache for 30 min)
- [ ] Test photo endpoint: `/wp-json/placy/v1/places/photo/[photo_ref]`

#### Frontend Functionality
- [ ] Page loads without errors
- [ ] Button appears after 2 seconds
- [ ] Button shows correct count
- [ ] Clicking button shows loading state
- [ ] Markers appear on map (red, smaller than POI markers)
- [ ] List cards appear (gray background, "Fra Google" badge)
- [ ] Clicking marker shows popup
- [ ] Toggle button works (show/hide)
- [ ] Second toggle is instant (cached)

#### Visual Quality
- [ ] Featured POI markers remain dominant (larger, higher z-index)
- [ ] Property marker always on top (z-index 1000)
- [ ] Google Places markers clearly secondary (red, smaller, z-index 5)
- [ ] Labels appear/hide based on zoom level
- [ ] Map doesn't zoom out excessively
- [ ] No layout shifts or visual glitches

#### Mobile Testing
- [ ] Button visible and clickable on mobile
- [ ] Markers interactive on touch
- [ ] Popups render correctly
- [ ] List cards readable on small screens

#### Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

#### Performance Testing
- [ ] Page load time not affected (check Network tab)
- [ ] API response < 1 second
- [ ] No JavaScript errors in console
- [ ] No memory leaks (test with repeated toggles)
- [ ] Lighthouse score not significantly reduced

### 5. Error Handling Tests

#### API Failures
- [ ] Test with invalid API key (should fail silently, no button)
- [ ] Test with disabled APIs (should fail silently)
- [ ] Test with quota exceeded (should fail silently)
- [ ] Check error logs for proper logging

#### Edge Cases
- [ ] Test chapter with no POI lists (no button should appear)
- [ ] Test with no results from API (no button should appear)
- [ ] Test with strict filters returning zero results
- [ ] Test multiple chapters on same page (all work independently)

---

## Deployment to Production

### 1. Pre-Deployment Backup
- [ ] Database backup created
- [ ] File backup created
- [ ] Backup stored off-site

### 2. Deployment Steps
- [ ] Put site in maintenance mode (optional)
- [ ] Upload `inc/google-places.php`
- [ ] Upload `js/tema-story-map-multi.js`
- [ ] Clear all caches:
  ```bash
  # WordPress cache
  wp cache flush
  
  # PHP OpCache
  sudo service php-fpm reload
  
  # CDN cache (if applicable)
  # Purge CDN cache through provider dashboard
  ```
- [ ] Test API endpoint on production
- [ ] Test frontend on production
- [ ] Remove maintenance mode

### 3. Post-Deployment Verification
- [ ] Test page load on production URL
- [ ] Verify button appears
- [ ] Click button and verify results
- [ ] Check browser console for errors
- [ ] Test on mobile device
- [ ] Verify analytics tracking (if implemented)

---

## Monitoring (First Week)

### Daily Checks
- [ ] Check API usage in Google Cloud Console
- [ ] Review WordPress error logs
- [ ] Monitor page load times
- [ ] Check for JavaScript errors in monitoring tool

### Metrics to Track
- [ ] Number of button clicks
- [ ] API requests per day
- [ ] API cost per day
- [ ] Cache hit rate (transient queries vs API calls)
- [ ] Average response time

### Issues to Watch For
- [ ] Quota warnings from Google
- [ ] Unexpected API costs
- [ ] High API failure rate
- [ ] Slow response times
- [ ] User reports of missing button

---

## Rollback Plan

If critical issues occur:

### Step 1: Immediate Rollback
```bash
# Restore previous version of files
git checkout HEAD~1 inc/google-places.php
git checkout HEAD~1 js/tema-story-map-multi.js

# Or via SFTP: restore from backup
# Upload backed up files

# Clear caches
wp cache flush
```

### Step 2: Disable API Calls
Temporary fix without full rollback:

**Option A: Comment out initialization**
```javascript
// In tema-story-map-multi.js
// setTimeout(function() {
//     initPlacesApiIntegration();
// }, 2000);
```

**Option B: Remove API key**
```php
// In wp-config.php
// define('GOOGLE_PLACES_API_KEY', 'your-key-here');
```

### Step 3: Investigate & Fix
- Review error logs
- Test on staging
- Identify root cause
- Apply fix
- Test thoroughly
- Re-deploy

---

## Cost Management

### Set Budget Alerts
- [ ] $50 threshold alert (warning)
- [ ] $100 threshold alert (action required)
- [ ] $200 threshold alert (critical)
- [ ] $300 hard limit (optional)

### Monitor Usage
```
Google Cloud Console → Billing → Reports
- Filter by: Places API
- Time range: Last 7 days
- Breakdown by: SKU (Nearby Search, Photo, etc.)
```

### Cost Optimization Actions
If costs exceed budget:

1. **Immediate (< 1 hour):**
   - Increase cache duration to 2 hours
   - Reduce max results to 10

2. **Short-term (< 1 day):**
   - Disable on low-traffic pages
   - Implement rate limiting per user

3. **Long-term (< 1 week):**
   - Add user preference to opt-out
   - Implement manual curation
   - Consider alternative data sources

---

## Security Checklist

### API Key Security
- [ ] API key not in version control
- [ ] API key not in client-side JavaScript
- [ ] API key stored in wp-config.php only
- [ ] File permissions secure (600 or 400)
- [ ] HTTP Referrer restrictions enabled (optional)
- [ ] IP restrictions enabled (if static IP available)

### REST API Security
- [ ] Endpoints use permission callbacks
- [ ] Input sanitization in place
- [ ] Output escaping in place
- [ ] Rate limiting considered (via plugin or custom)

### WordPress Security
- [ ] WordPress core updated
- [ ] Plugins updated
- [ ] Theme updated
- [ ] SSL certificate valid
- [ ] Security headers configured

---

## Documentation Checklist

### For Developers
- [ ] `GOOGLE_PLACES_INTEGRATION.md` reviewed
- [ ] Code comments clear and accurate
- [ ] API endpoints documented
- [ ] Function signatures documented

### For Clients/Users
- [ ] `GOOGLE_PLACES_QUICK_START.md` provided
- [ ] Training session scheduled (if needed)
- [ ] Support contact information provided

### For Operations
- [ ] Monitoring dashboard configured
- [ ] Alert recipients configured
- [ ] Runbook created for common issues
- [ ] Rollback procedure documented

---

## Success Criteria

Deployment is successful if:

- [x] All frontend functionality works
- [x] No JavaScript errors in console
- [x] No PHP errors in logs
- [x] API costs within budget
- [x] Page load time not significantly impacted
- [x] User experience positive
- [x] Visual hierarchy maintained

---

## Post-Deployment Tasks

### Within 24 Hours
- [ ] Monitor API usage
- [ ] Check error logs
- [ ] Verify cache is working
- [ ] Test on multiple devices

### Within 1 Week
- [ ] Review API costs
- [ ] Gather user feedback
- [ ] Analyze usage metrics
- [ ] Adjust parameters if needed

### Within 1 Month
- [ ] Comprehensive cost review
- [ ] Performance optimization review
- [ ] Plan future enhancements
- [ ] Update documentation based on learnings

---

## Support Contacts

### Google Cloud
- Console: https://console.cloud.google.com/
- Support: https://cloud.google.com/support
- Billing: https://console.cloud.google.com/billing

### WordPress
- Debug logs: `wp-content/debug.log`
- WP-CLI commands: `wp help cache flush`

### Documentation
- Full docs: `GOOGLE_PLACES_INTEGRATION.md`
- Quick start: `GOOGLE_PLACES_QUICK_START.md`
- Summary: `GOOGLE_PLACES_IMPLEMENTATION_SUMMARY.md`

---

## Sign-Off

### Pre-Deployment Sign-Off
- [ ] Developer: Code reviewed and tested on staging
- [ ] QA: All tests passed
- [ ] Project Manager: Approved for deployment
- [ ] Client: Aware of deployment schedule

### Post-Deployment Sign-Off
- [ ] Developer: Verified functionality on production
- [ ] QA: Smoke tests passed
- [ ] Operations: Monitoring configured
- [ ] Client: Informed of successful deployment

---

**Deployment Date:** _________________  
**Deployed By:** _________________  
**Sign-Off:** _________________
