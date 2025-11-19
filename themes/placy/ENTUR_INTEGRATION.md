# Entur Live Departures - Technical Documentation

## Overview
Complete integration of Entur's Journey Planner API to display real-time departure information in transport POI cards. API calls are triggered ONLY when users click on POI markers, ensuring minimal API usage and optimal performance.

## Architecture

### Backend (WordPress/PHP)
```
/inc/entur-integration.php
├── REST API endpoint: /wp-json/placy/v1/entur/departures/{stopplace_id}
├── GraphQL query builder for Entur API
├── API client with timeout and error handling
├── Data transformation and filtering
└── ACF field integration in REST API
```

### Frontend (JavaScript)
```
/js/entur-live-departures.js
├── Event listeners for POI activation
├── API client with retry logic
├── Response caching (60s TTL)
├── Dynamic UI rendering
└── Graceful error handling
```

### Database (ACF Fields)
```
ACF Field Group: "API Integrations"
├── entur_stopplace_id (text)
├── entur_quay_id (text, optional)
└── show_live_departures (true/false)

Location: Point CPT where point_type = "Transport"
```

## API Flow

### 1. User Interaction
```
User clicks POI marker
  ↓
handleMarkerClick() or "Se på kart" button
  ↓
Wait 500-900ms for card scroll animation
  ↓
handlePOIActivation(poiId)
```

### 2. Data Check
```javascript
Check data attributes:
- data-entur-stopplace-id
- data-show-live-departures="1"
- data-entur-quay-id (optional)

If missing → Skip API call
If exists → Continue
```

### 3. API Request
```
Frontend: fetchDepartures(stopplaceId, quayId?)
  ↓
GET /wp-json/placy/v1/entur/departures/{stopplace_id}?quay_id={quay_id}
  ↓
Backend: placy_get_entur_departures()
  ↓
Build GraphQL query
  ↓
POST https://api.entur.io/journey-planner/v3/graphql
  Headers:
    - ET-Client-Name: placy-stasjonskvartalet
    - Content-Type: application/json
  Timeout: 5 seconds
```

### 4. Data Transformation
```php
Entur GraphQL Response
  ↓
placy_transform_entur_response()
  ↓
Filter:
  - Remove cancelled departures
  - Remove past departures
  - Limit to 5 departures
  ↓
Simplify:
  {
    time: "14:32",              // HH:MM
    relative_time: 8,           // minutes
    destination: "Kristiansund",
    line_name: "Kystekspressen",
    line_number: "1",
    transport_mode: "water",
    realtime: true,
    quay: "Kai A",
    quay_code: "A"
  }
```

### 5. UI Rendering
```javascript
displayDepartures(poiCard, departures, timestamp)
  ↓
Build HTML with:
  - Animated live indicator
  - Departure list (time + destination + relative time)
  - Realtime/scheduled indicators
  - Entur attribution
  - Timestamp
  ↓
Inject into .poi-card-content
```

## GraphQL Query Structure

```graphql
{
  stopPlace(id: "NSR:StopPlace:74006") {
    id
    name
    estimatedCalls(
      numberOfDepartures: 10
      timeRange: 10800        # 3 hours
      startTime: "2025-11-19T14:30:00+01:00"
    ) {
      expectedDepartureTime
      realtime
      cancellation
      destinationDisplay {
        frontText
      }
      serviceJourney {
        line {
          publicCode
          name
          transportMode
        }
      }
      quay {
        id
        name
        publicCode
      }
    }
  }
}
```

## Error Handling

### Backend Errors
| Error Type | Handling | User Impact |
|------------|----------|-------------|
| Network timeout (>5s) | Log + return empty | No departures shown |
| Invalid StopPlace ID | Validate + 400 | No API call made |
| HTTP error (500, 503) | Log + return empty | No departures shown |
| GraphQL error | Log + return empty | No departures shown |
| No departures | Return success with empty array | No departures shown |

### Frontend Errors
| Error Type | Handling | User Impact |
|------------|----------|-------------|
| Fetch timeout | Retry 2x with 1s delay | Auto-retry, silent fail |
| 4xx client error | No retry, return empty | No departures shown |
| Network error | Retry 2x with 1s delay | Auto-retry, silent fail |
| JSON parse error | Log + hide loading | No departures shown |

**Critical Principle**: Never show error messages to end users. Departures are supplemental content - if unavailable, show only curated POI description.

## Caching Strategy

### Frontend Cache
```javascript
const departureCache = new Map();

Cache key: "{stopplaceId}:{quayId}"
TTL: 60 seconds
Storage: In-memory (Map)
Invalidation: Time-based only
```

### Benefits
- Reduces API calls if user clicks same POI multiple times
- Fresh data every minute
- No server-side caching complexity
- Cleared on page refresh

## Performance Considerations

### API Call Timing
- **NO calls on**: Page load, hover, zoom, pan
- **Calls ONLY on**: POI marker click + card activation
- **Estimated volume**: 20 requests/day per transport POI @ 100 visitors/day with 20% interaction rate

### Timeout Handling
- Backend: 5s timeout on HTTP request
- Frontend: 5s timeout on fetch with AbortController
- Prevents slow API from blocking UI

### Lazy Loading
- Departures loaded only when needed
- No pre-fetching or eager loading
- Minimal impact on page load performance

## Data Attribution

Per Entur's terms of service (NLOD license):
- Display "Sanntidsdata fra Entur.no" 
- Link to entur.no
- Include ET-Client-Name header: "placy-stasjonskvartalet"

## Testing Checklist

### Functional Tests
- [ ] POI with live departures enabled shows data on click
- [ ] POI without StopPlace ID doesn't make API call
- [ ] Multiple POIs work independently
- [ ] Cache prevents duplicate requests within 60s
- [ ] Clicking marker multiple times doesn't spam API
- [ ] "Se på kart" button triggers loading

### Error Scenarios
- [ ] Invalid StopPlace ID returns empty gracefully
- [ ] API timeout (>5s) hides loading without error
- [ ] No departures available shows nothing
- [ ] Network offline fails silently
- [ ] Cancelled departures are filtered out

### UI/UX Tests
- [ ] Loading spinner shows while fetching
- [ ] Smooth transition from loading to data
- [ ] Live indicator animates
- [ ] Realtime vs scheduled indicators correct
- [ ] Responsive on mobile and desktop
- [ ] Multiple departures display correctly

### Performance Tests
- [ ] No API calls on page load
- [ ] Timeout prevents hanging
- [ ] Cache reduces redundant requests
- [ ] UI remains responsive during fetch

## Maintenance

### Monitoring
Key metrics to track:
- API error rate
- Average response time
- Cache hit rate
- User interaction rate

Logs to check:
```bash
# WordPress debug.log
grep "Entur API" /path/to/wp-content/debug.log

# Browser console
Filter: "Entur:"
```

### Updating Entur API
If Entur changes their API:

1. Update GraphQL query in `placy_build_entur_graphql_query()`
2. Update response parsing in `placy_transform_entur_response()`
3. Test with sample StopPlace IDs
4. Update documentation

### Adding New Features

#### Filter by transport mode
```php
// In placy_build_entur_graphql_query()
estimatedCalls(
  numberOfDepartures: 10
  timeRange: 10800
  // Add this:
  whiteListed: {
    transportModes: [bus, rail, water]
  }
)
```

#### Show delays
```javascript
// In displayDepartures()
if (departure.delay) {
  html += `<span class="text-red-600 text-xs">+${departure.delay} min</span>`;
}
```

## Security Considerations

### Input Validation
- StopPlace ID validated with regex: `/^NSR:StopPlace:\d+$/`
- Quay ID sanitized with `esc_attr()`
- All API responses sanitized before display

### XSS Prevention
- Use `escapeHtml()` for all user-facing text
- No `innerHTML` with raw API data
- All URLs validated before display

### Rate Limiting
- Implicit: Only triggered by user action
- No automated polling
- Client-side caching reduces requests

### API Key Security
- Entur API requires no authentication
- ET-Client-Name is public identifier, not secret
- No sensitive data transmitted

## Resources

### Entur API Documentation
- [Developer Portal](https://developer.entur.org/)
- [GraphQL Playground](https://api.entur.io/journey-planner/v3/graphql)
- [StopPlace Lookup](https://stoppested.entur.org)

### Testing Tools
- [GraphiQL Interface](https://api.entur.io/journey-planner/v3/graphiql)
- [Postman Collection](https://developer.entur.org/pages-graphql-journeyplanner)

### Code Files
```
Backend:
- /inc/entur-integration.php
- /inc/acf-fields.php (API Integrations group)

Frontend:
- /js/entur-live-departures.js

Templates:
- /blocks/poi-list/template.php
- /blocks/poi-gallery/template.php
- /blocks/poi-highlight/template.php

Functions:
- /functions.php (script enqueue)

Documentation:
- /ENTUR_BRUKERVEILEDNING.md (user guide)
- /ENTUR_INTEGRATION.md (this file)
```

## Support

For technical issues:
1. Check browser console for errors
2. Check WordPress debug.log
3. Test StopPlace ID on stoppested.entur.org
4. Verify ACF fields are filled correctly

For Entur API issues:
- Contact: developer@entur.org
- Status: [status.entur.org](https://status.entur.org)
