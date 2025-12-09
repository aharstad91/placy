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
 * Uses OpenRouteService for proper cycling/walking routes
 * Falls back to OSRM for driving
 */
function placy_directions_proxy(WP_REST_Request $request) {
    $origin_lng = floatval($request->get_param('origin_lng'));
    $origin_lat = floatval($request->get_param('origin_lat'));
    $dest_lng = floatval($request->get_param('dest_lng'));
    $dest_lat = floatval($request->get_param('dest_lat'));
    $mode = $request->get_param('mode');

    // Try OpenRouteService first for cycling/walking (proper bike routes)
    // ORS has a free tier: 2000 requests/day
    $ors_api_key = defined('OPENROUTESERVICE_API_KEY') ? OPENROUTESERVICE_API_KEY : get_option('placy_ors_api_key', '');
    
    if (!empty($ors_api_key) && in_array($mode, ['cycling', 'walking'])) {
        $result = placy_get_ors_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode, $ors_api_key);
        if ($result) {
            return rest_ensure_response($result);
        }
    }

    // Fallback to OSRM (works for driving, less accurate for cycling)
    return placy_get_osrm_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode);
}

/**
 * Get directions from OpenRouteService (better cycling/walking routes)
 */
function placy_get_ors_directions($origin_lng, $origin_lat, $dest_lng, $dest_lat, $mode, $api_key) {
    // Map mode to ORS profile
    $profiles = [
        'cycling' => 'cycling-regular',
        'walking' => 'foot-walking',
        'driving' => 'driving-car',
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
    $profiles = [
        'cycling' => 'bike',
        'walking' => 'foot',
        'driving' => 'driving',
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
    // Note: OSRM's public demo server may not have accurate cycling routes
    // We calculate realistic travel times based on distance and typical speeds
    $routes = [];
    foreach ($osrm_data['routes'] as $route) {
        $distance = $route['distance']; // meters
        
        // Calculate realistic duration based on mode and typical speeds
        $speeds = [
            'bike' => 16,      // 16 km/h average cycling speed
            'foot' => 5,       // 5 km/h average walking speed
            'driving' => 35,   // 35 km/h average urban driving speed
        ];
        
        $speed_kmh = $speeds[$profile] ?? 16;
        $speed_ms = $speed_kmh / 3.6;
        $calculated_duration = $distance / $speed_ms;
        
        $routes[] = [
            'distance' => $distance,
            'duration' => $calculated_duration,
            'geometry' => $route['geometry'] ?? null,
        ];
    }

    return rest_ensure_response([
        'code' => 'Ok',
        'routes' => $routes,
    ]);
}
