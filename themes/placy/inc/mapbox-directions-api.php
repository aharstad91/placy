<?php
/**
 * Mapbox Directions API Proxy
 * 
 * Handles geocoding and directions requests while keeping API keys server-side.
 * 
 * @package Placy
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Register REST API routes for Mapbox proxy
 */
function placy_register_mapbox_routes() {
    register_rest_route('placy/v1', '/travel-calc/geocode', [
        'methods' => 'GET',
        'callback' => 'placy_geocode_proxy',
        'permission_callback' => '__return_true',
        'args' => [
            'query' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'proximity' => [
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'country' => [
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'NO',
            ],
        ],
    ]);

    register_rest_route('placy/v1', '/travel-calc/directions', [
        'methods' => 'GET',
        'callback' => 'placy_directions_proxy',
        'permission_callback' => '__return_true',
        'args' => [
            'origin_lng' => [
                'required' => true,
                'type' => 'string',
                'validate_callback' => function($value) {
                    return is_numeric($value);
                },
            ],
            'origin_lat' => [
                'required' => true,
                'type' => 'string',
                'validate_callback' => function($value) {
                    return is_numeric($value);
                },
            ],
            'dest_lng' => [
                'required' => true,
                'type' => 'string',
                'validate_callback' => function($value) {
                    return is_numeric($value);
                },
            ],
            'dest_lat' => [
                'required' => true,
                'type' => 'string',
                'validate_callback' => function($value) {
                    return is_numeric($value);
                },
            ],
            'mode' => [
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'cycling',
            ],
        ],
    ]);
}
add_action('rest_api_init', 'placy_register_mapbox_routes');

/**
 * Geocoding proxy - search for addresses
 * Uses Nominatim (OpenStreetMap) - free and no API key required
 */
function placy_geocode_proxy(WP_REST_Request $request) {
    $query = $request->get_param('query');
    $proximity = $request->get_param('proximity');
    $country = $request->get_param('country');

    // Build Nominatim Geocoding API URL
    $url = 'https://nominatim.openstreetmap.org/search';
    $params = [
        'q' => $query,
        'format' => 'json',
        'addressdetails' => 1,
        'limit' => 5,
        'accept-language' => 'nb,no,en',
    ];

    // Add country code filter
    if ($country) {
        $params['countrycodes'] = strtolower($country);
    }

    // Add viewbox for proximity bias (convert lng,lat to viewbox)
    if ($proximity) {
        $coords = explode(',', $proximity);
        if (count($coords) === 2) {
            $lng = floatval($coords[0]);
            $lat = floatval($coords[1]);
            // Create a viewbox around the proximity point (roughly 50km)
            $params['viewbox'] = sprintf('%f,%f,%f,%f', $lng - 0.5, $lat + 0.5, $lng + 0.5, $lat - 0.5);
            $params['bounded'] = 0; // Prefer but don't limit to viewbox
        }
    }

    $url .= '?' . http_build_query($params);

    // Make request to Nominatim (requires User-Agent header)
    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'User-Agent' => 'Placy WordPress Theme (contact@placy.no)',
        ],
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('geocode_failed', 'Geocoding request failed', ['status' => 500]);
    }

    $body = wp_remote_retrieve_body($response);
    $nominatim_results = json_decode($body, true);

    if (!is_array($nominatim_results)) {
        return new WP_Error('geocode_error', 'Invalid response from geocoding service', ['status' => 500]);
    }

    // Convert Nominatim response to Mapbox-compatible format
    $features = [];
    foreach ($nominatim_results as $result) {
        $features[] = [
            'id' => $result['place_id'],
            'type' => 'Feature',
            'place_name' => $result['display_name'],
            'center' => [floatval($result['lon']), floatval($result['lat'])],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [floatval($result['lon']), floatval($result['lat'])],
            ],
            'properties' => [
                'address' => $result['address'] ?? [],
            ],
        ];
    }

    return rest_ensure_response([
        'type' => 'FeatureCollection',
        'features' => $features,
    ]);
}

/**
 * Directions proxy - calculate routes
 * Priority: Google Routes API (best accuracy) → Mapbox → OpenRouteService → OSRM
 */
function placy_directions_proxy(WP_REST_Request $request) {
    $origin_lng = floatval($request->get_param('origin_lng'));
    $origin_lat = floatval($request->get_param('origin_lat'));
    $dest_lng = floatval($request->get_param('dest_lng'));
    $dest_lat = floatval($request->get_param('dest_lat'));
    $mode = $request->get_param('mode');

    // Try Google Routes API first (best accuracy, matches Google Maps)
    $google_api_key = defined('GOOGLE_PLACES_API_KEY') ? GOOGLE_PLACES_API_KEY : get_option('placy_google_api_key', '');

    if (!empty($google_api_key)) {
        $result = placy_get_google_routes_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode, $google_api_key);
        if ($result) {
            return rest_ensure_response($result);
        }
    }

    // Fallback to Mapbox Directions API
    $mapbox_token = defined('MAPBOX_ACCESS_TOKEN') ? MAPBOX_ACCESS_TOKEN : get_option('placy_mapbox_access_token', '');

    if (!empty($mapbox_token)) {
        $result = placy_get_mapbox_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode, $mapbox_token);
        if ($result) {
            return rest_ensure_response($result);
        }
    }

    // Try OpenRouteService as fallback for cycling/walking
    $ors_api_key = defined('OPENROUTESERVICE_API_KEY') ? OPENROUTESERVICE_API_KEY : get_option('placy_ors_api_key', '');

    if (!empty($ors_api_key) && in_array($mode, ['cycling', 'walking'])) {
        $result = placy_get_ors_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode, $ors_api_key);
        if ($result) {
            return rest_ensure_response($result);
        }
    }

    // Final fallback to OSRM
    return placy_get_osrm_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode);
}

/**
 * Get directions from Google Routes API (best accuracy)
 * Returns routes in GeoJSON format for Mapbox display
 *
 * @param float $origin_lng Origin longitude
 * @param float $origin_lat Origin latitude
 * @param float $dest_lng Destination longitude
 * @param float $dest_lat Destination latitude
 * @param string $mode Travel mode (walking, cycling, driving)
 * @param string $api_key Google API key
 * @return array|null Standardized route response or null on failure
 */
function placy_get_google_routes_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode, $api_key) {
    // Map our mode names to Google Routes API travel modes
    // Support both short (walk/bike/car) and long (walking/cycling/driving) names
    $travel_modes = [
        'walking' => 'WALK',
        'walk' => 'WALK',
        'cycling' => 'BICYCLE',
        'bike' => 'BICYCLE',
        'driving' => 'DRIVE',
        'car' => 'DRIVE',
        'drive' => 'DRIVE',
    ];
    $travel_mode = $travel_modes[$mode] ?? 'WALK';

    // Round coordinates to 5 decimal places (~1m precision) for better cache hit rate
    $origin_lng_r = round($origin_lng, 5);
    $origin_lat_r = round($origin_lat, 5);
    $dest_lng_r = round($dest_lng, 5);
    $dest_lat_r = round($dest_lat, 5);

    // Check cache first (1 week expiry - routes don't change often)
    $cache_key = 'placy_route_' . md5("{$origin_lng_r},{$origin_lat_r},{$dest_lng_r},{$dest_lat_r},{$travel_mode}");
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $url = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    $body = [
        'origin' => [
            'location' => [
                'latLng' => [
                    'latitude' => $origin_lat,
                    'longitude' => $origin_lng,
                ],
            ],
        ],
        'destination' => [
            'location' => [
                'latLng' => [
                    'latitude' => $dest_lat,
                    'longitude' => $dest_lng,
                ],
            ],
        ],
        'travelMode' => $travel_mode,
        'polylineEncoding' => 'GEO_JSON_LINESTRING',
        'computeAlternativeRoutes' => false,
        'languageCode' => 'no',
        'units' => 'METRIC',
    ];

    $response = wp_remote_post($url, [
        'timeout' => 10,
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters,routes.polyline',
        ],
        'body' => wp_json_encode($body),
    ]);

    if (is_wp_error($response)) {
        error_log('Google Routes API error: ' . $response->get_error_message());
        return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log('Google Routes API HTTP error: ' . $status_code . ' - ' . wp_remote_retrieve_body($response));
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!$data || empty($data['routes'])) {
        error_log('Google Routes API: No routes returned');
        return null;
    }

    $route = $data['routes'][0];

    // Parse duration (format: "123s" for seconds)
    $duration_seconds = 0;
    if (isset($route['duration'])) {
        $duration_seconds = intval(str_replace('s', '', $route['duration']));
    }

    // Google returns polyline as GeoJSON LineString
    $geometry = null;
    if (isset($route['polyline']['geoJsonLinestring'])) {
        $geometry = $route['polyline']['geoJsonLinestring'];
    }

    $result = [
        'code' => 'Ok',
        'routes' => [[
            'distance' => $route['distanceMeters'] ?? 0, // meters
            'duration' => $duration_seconds, // seconds
            'geometry' => $geometry,
        ]],
    ];

    // Cache for 1 week
    set_transient($cache_key, $result, WEEK_IN_SECONDS);

    return $result;
}

/**
 * Get directions from Mapbox Directions API (best quality)
 */
function placy_get_mapbox_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode, $access_token) {
    // Map mode to Mapbox profile
    // Support both short (walk/bike/car) and long (walking/cycling/driving) names
    $profiles = [
        'cycling' => 'cycling',
        'bike' => 'cycling',
        'walking' => 'walking',
        'walk' => 'walking',
        'driving' => 'driving',
        'car' => 'driving',
        'drive' => 'driving',
    ];
    $profile = $profiles[$mode] ?? 'walking';

    $coordinates = sprintf('%f,%f;%f,%f', $origin_lng, $origin_lat, $dest_lng, $dest_lat);
    $url = 'https://api.mapbox.com/directions/v5/mapbox/' . $profile . '/' . $coordinates;
    
    $params = [
        'access_token' => $access_token,
        'geometries' => 'geojson',
        'overview' => 'full',
    ];

    $url .= '?' . http_build_query($params);

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'User-Agent' => 'Placy WordPress Theme',
        ],
    ]);

    if (is_wp_error($response)) {
        error_log('Mapbox Directions error: ' . $response->get_error_message());
        return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log('Mapbox Directions HTTP error: ' . $status_code . ' - ' . wp_remote_retrieve_body($response));
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!$data || empty($data['routes']) || $data['code'] !== 'Ok') {
        return null;
    }

    $route = $data['routes'][0];
    
    return [
        'code' => 'Ok',
        'routes' => [[
            'distance' => $route['distance'], // meters
            'duration' => $route['duration'], // seconds
            'geometry' => $route['geometry'],
        ]],
    ];
}

/**
 * Get directions from OpenRouteService (better cycling/walking routes)
 */
function placy_get_ors_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode, $api_key) {
    // Map mode to ORS profile
    // Support both short (walk/bike/car) and long (walking/cycling/driving) names
    $profiles = [
        'cycling' => 'cycling-regular',
        'bike' => 'cycling-regular',
        'walking' => 'foot-walking',
        'walk' => 'foot-walking',
        'driving' => 'driving-car',
        'car' => 'driving-car',
        'drive' => 'driving-car',
    ];
    $profile = $profiles[$mode] ?? 'cycling-regular';

    $url = 'https://api.openrouteservice.org/v2/directions/' . $profile;
    
    $body = json_encode([
        'coordinates' => [
            [$origin_lng, $origin_lat],
            [$dest_lng, $dest_lat]
        ],
        'geometry' => true,
        'instructions' => false,
    ]);

    $response = wp_remote_post($url, [
        'timeout' => 15,
        'headers' => [
            'Authorization' => $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log('ORS API error: ' . wp_remote_retrieve_body($response));
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!$data || empty($data['routes'])) {
        return null;
    }

    $route = $data['routes'][0];
    $summary = $route['summary'];
    
    return [
        'code' => 'Ok',
        'routes' => [[
            'distance' => $summary['distance'], // meters
            'duration' => $summary['duration'], // seconds (ORS gives realistic times!)
            'geometry' => $route['geometry'],
        ]],
    ];
}

/**
 * Get directions from OSRM (fallback)
 */
function placy_get_osrm_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode) {
    // Map our mode names to OSRM profiles
    // Support both short (walk/bike/car) and long (walking/cycling/driving) names
    $profiles = [
        'cycling' => 'bike',
        'bike' => 'bike',
        'walking' => 'foot',
        'walk' => 'foot',
        'driving' => 'driving',
        'car' => 'driving',
        'drive' => 'driving',
    ];

    $profile = $profiles[$mode] ?? 'bike';

    // Build OSRM Directions API URL
    $coordinates = sprintf('%f,%f;%f,%f', $origin_lng, $origin_lat, $dest_lng, $dest_lat);
    $url = 'https://router.project-osrm.org/route/v1/' . $profile . '/' . $coordinates;
    
    $params = [
        'overview' => 'full',
        'geometries' => 'geojson',
        'steps' => 'false',
    ];

    $url .= '?' . http_build_query($params);

    // Make request to OSRM
    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'User-Agent' => 'Placy WordPress Theme (contact@placy.no)',
        ],
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('directions_failed', 'Directions request failed', ['status' => 500]);
    }

    $body = wp_remote_retrieve_body($response);
    $osrm_data = json_decode($body, true);

    if (!$osrm_data || $osrm_data['code'] !== 'Ok') {
        return new WP_Error('directions_error', $osrm_data['message'] ?? 'Unknown error', ['status' => 500]);
    }

    // Convert OSRM response to Mapbox-compatible format
    // Use OSRM's duration directly - it provides accurate routing-based travel times
    $routes = [];
    foreach ($osrm_data['routes'] as $route) {
        $routes[] = [
            'distance' => $route['distance'], // meters
            'duration' => $route['duration'], // seconds - use OSRM's calculated duration
            'geometry' => $route['geometry'] ?? null,
        ];
    }

    return rest_ensure_response([
        'code' => 'Ok',
        'routes' => $routes,
    ]);
}
