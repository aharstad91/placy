<?php
/**
 * Google Places API Integration
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get place details from Google Places API with caching
 *
 * @param string $place_id Google Place ID
 * @return array|null Place data or null if failed
 */
function placy_get_place_details( $place_id ) {
    if ( empty( $place_id ) ) {
        return null;
    }

    // Check cache first (24 hour expiration)
    $cache_key = 'placy_place_' . md5( $place_id );
    $cached_data = get_transient( $cache_key );
    
    if ( false !== $cached_data ) {
        return $cached_data;
    }

    // Get API key
    $api_key = defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '';
    
    if ( empty( $api_key ) ) {
        error_log( 'Placy: Google Places API key not configured' );
        return null;
    }

    // Make API request using Places API (New)
    $url = 'https://places.googleapis.com/v1/places/' . $place_id;
    
    $response = wp_remote_get( $url, array(
        'headers' => array(
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,googleMapsUri',
        ),
        'timeout' => 10,
    ) );

    // Check for errors
    if ( is_wp_error( $response ) ) {
        error_log( 'Placy Google Places API Error: ' . $response->get_error_message() );
        return null;
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        error_log( 'Placy Google Places API Error: HTTP ' . $response_code );
        return null;
    }

    // Parse response
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! $data ) {
        error_log( 'Placy Google Places API Error: Invalid JSON response' );
        return null;
    }

    // Extract relevant data
    $place_data = array(
        'name' => isset( $data['displayName']['text'] ) ? $data['displayName']['text'] : '',
        'rating' => isset( $data['rating'] ) ? floatval( $data['rating'] ) : null,
        'review_count' => isset( $data['userRatingCount'] ) ? intval( $data['userRatingCount'] ) : null,
        'google_maps_url' => isset( $data['googleMapsUri'] ) ? $data['googleMapsUri'] : '',
    );

    // Cache for 24 hours
    set_transient( $cache_key, $place_data, DAY_IN_SECONDS );

    return $place_data;
}

/**
 * Get cached place data for a POI post
 *
 * @param int $post_id Post ID
 * @return array|null Place data or null
 */
function placy_get_poi_place_data( $post_id ) {
    // Try get_field first (ACF)
    $place_id = get_field( 'google_place_id', $post_id );
    
    // Fallback to get_post_meta if ACF returns null
    if ( empty( $place_id ) ) {
        $place_id = get_post_meta( $post_id, 'google_place_id', true );
    }
    
    if ( empty( $place_id ) ) {
        return null;
    }

    return placy_get_place_details( $place_id );
}

/**
 * Clear place data cache for a specific place ID
 *
 * @param string $place_id Google Place ID
 */
function placy_clear_place_cache( $place_id ) {
    if ( empty( $place_id ) ) {
        return;
    }
    
    $cache_key = 'placy_place_' . md5( $place_id );
    delete_transient( $cache_key );
}

/**
 * Clear all place data caches
 * Useful for manual refresh or debugging
 */
function placy_clear_all_place_caches() {
    global $wpdb;
    
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_placy_place_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_placy_place_%'" );
}

/**
 * Admin action to clear place caches
 */
add_action( 'admin_post_placy_clear_place_caches', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    placy_clear_all_place_caches();
    
    wp_redirect( add_query_arg( 'cache_cleared', '1', wp_get_referer() ) );
    exit;
} );

/**
 * Format rating as stars (for display)
 *
 * @param float $rating Rating value (0-5)
 * @return string Star icons HTML
 */
function placy_format_rating_stars( $rating ) {
    if ( ! $rating ) {
        return '';
    }
    
    $full_stars = floor( $rating );
    $half_star = ( $rating - $full_stars ) >= 0.5;
    $empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );
    
    $output = '<span class="poi-rating-stars" aria-label="Rating: ' . esc_attr( $rating ) . ' av 5">';
    
    // Full stars
    for ( $i = 0; $i < $full_stars; $i++ ) {
        $output .= '<span class="star star-full">★</span>';
    }
    
    // Half star
    if ( $half_star ) {
        $output .= '<span class="star star-half">★</span>';
    }
    
    // Empty stars
    for ( $i = 0; $i < $empty_stars; $i++ ) {
        $output .= '<span class="star star-empty">☆</span>';
    }
    
    $output .= '</span>';
    
    return $output;
}

/**
 * Admin notice for cache clearing
 */
add_action( 'admin_notices', function() {
    if ( isset( $_GET['cache_cleared'] ) && $_GET['cache_cleared'] === '1' ) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>Google Places cache cleared successfully!</p>';
        echo '</div>';
    }
} );

/**
 * Register REST API endpoints for Google Places Nearby Search
 */
function placy_register_places_api_endpoints() {
    // Places Search endpoint
    register_rest_route( 'placy/v1', '/places/search', array(
        'methods' => 'GET',
        'callback' => 'placy_places_nearby_search',
        'permission_callback' => '__return_true',
        'args' => array(
            'category' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Place type/category to search for',
                'default' => 'restaurant',
            ),
            'keyword' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Keyword for text search (e.g., "pizza", "sushi", "fine dining")',
                'default' => '',
            ),
            'lat' => array(
                'required' => true,
                'type' => 'number',
                'description' => 'Latitude coordinate',
            ),
            'lng' => array(
                'required' => true,
                'type' => 'number',
                'description' => 'Longitude coordinate',
            ),
            'radius' => array(
                'required' => false,
                'type' => 'integer',
                'description' => 'Search radius in meters',
                'default' => 1500,
            ),
            'minRating' => array(
                'required' => false,
                'type' => 'number',
                'description' => 'Minimum rating filter (0-5)',
                'default' => 4.0,
            ),
            'minReviews' => array(
                'required' => false,
                'type' => 'integer',
                'description' => 'Minimum number of reviews',
                'default' => 50,
            ),
            'excludeTypes' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'JSON array of place types to exclude (e.g., ["lodging", "hospital"])',
                'default' => '["lodging"]',
            ),
            'excludePlaceIds' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'JSON array of Google Place IDs to exclude from results (to prevent duplicates with manually curated POIs)',
                'default' => '[]',
            ),
        ),
    ) );
    
    // Places Photo endpoint
    register_rest_route( 'placy/v1', '/places/photo/(?P<photo_reference>[a-zA-Z0-9_-]+)', array(
        'methods' => 'GET',
        'callback' => 'placy_places_photo',
        'permission_callback' => '__return_true',
        'args' => array(
            'photo_reference' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Google Places photo reference',
            ),
            'maxwidth' => array(
                'required' => false,
                'type' => 'integer',
                'description' => 'Maximum photo width',
                'default' => 400,
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'placy_register_places_api_endpoints' );

/**
 * Handle Places Nearby Search API request
 *
 * @param WP_REST_Request $request Request object
 * @return WP_REST_Response Response object
 */
function placy_places_nearby_search( $request ) {
    $category = $request->get_param( 'category' );
    $keyword = $request->get_param( 'keyword' );
    $lat = $request->get_param( 'lat' );
    $lng = $request->get_param( 'lng' );
    $radius = $request->get_param( 'radius' );
    $min_rating = $request->get_param( 'minRating' );
    $min_reviews = $request->get_param( 'minReviews' );
    
    // Parse exclude types from JSON string
    $exclude_types_param = $request->get_param( 'excludeTypes' );
    $exclude_types = array( 'lodging' ); // Default
    if ( ! empty( $exclude_types_param ) ) {
        $decoded = json_decode( $exclude_types_param, true );
        if ( is_array( $decoded ) ) {
            $exclude_types = $decoded;
        }
    }
    
    // Parse exclude place IDs from JSON string
    $exclude_place_ids_param = $request->get_param( 'excludePlaceIds' );
    $exclude_place_ids = array();
    if ( ! empty( $exclude_place_ids_param ) ) {
        $decoded = json_decode( $exclude_place_ids_param, true );
        if ( is_array( $decoded ) ) {
            $exclude_place_ids = $decoded;
        }
    }
    
    // Create cache key (include keyword, exclude types, and exclude place IDs)
    $cache_key = sprintf(
        'placy_places_search_%s_%s_%s_%s_%d_%s_%s',
        $category,
        $keyword,
        $lat,
        $lng,
        $radius,
        implode( '_', $exclude_types ),
        implode( '_', $exclude_place_ids )
    );
    $cache_key = md5( $cache_key );
    
    // Check cache (30 minutes)
    $cached_data = get_transient( $cache_key );
    if ( false !== $cached_data ) {
        return rest_ensure_response( $cached_data );
    }
    
    // Get API key
    $api_key = defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '';
    
    if ( empty( $api_key ) ) {
        return new WP_Error(
            'api_key_missing',
            'Google Places API key not configured',
            array( 'status' => 500 )
        );
    }
    
    // Build request URL for Nearby Search
    $query_args = array(
        'location' => $lat . ',' . $lng,
        'radius' => $radius,
        'type' => $category,
        'key' => $api_key,
    );
    
    // Add keyword if provided
    if ( ! empty( $keyword ) ) {
        $query_args['keyword'] = $keyword;
    }
    
    $url = add_query_arg( $query_args, 'https://maps.googleapis.com/maps/api/place/nearbysearch/json' );
    
    // Make API request
    $response = wp_remote_get( $url, array(
        'timeout' => 15,
    ) );
    
    if ( is_wp_error( $response ) ) {
        error_log( 'Google Places Nearby Search Error: ' . $response->get_error_message() );
        return new WP_Error(
            'api_error',
            'Failed to fetch from Google Places API',
            array( 'status' => 500 )
        );
    }
    
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        error_log( 'Google Places Nearby Search HTTP Error: ' . $response_code );
        return new WP_Error(
            'api_error',
            'Google Places API returned error: ' . $response_code,
            array( 'status' => $response_code )
        );
    }
    
    // Parse response
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! $data || ! isset( $data['results'] ) ) {
        error_log( 'Google Places Nearby Search: Invalid response format' );
        return new WP_Error(
            'invalid_response',
            'Invalid response from Google Places API',
            array( 'status' => 500 )
        );
    }
    
    // Filter and transform results
    $places = array();
    
    // Use the exclude types from request parameter
    foreach ( $data['results'] as $place ) {
        $place_id = isset( $place['place_id'] ) ? $place['place_id'] : '';
        
        // Skip if this place ID is in the excluded list (prevents duplicates with curated POIs)
        if ( ! empty( $place_id ) && in_array( $place_id, $exclude_place_ids ) ) {
            continue;
        }
        
        // Get place types
        $place_types = isset( $place['types'] ) ? $place['types'] : array();
        
        // Skip if place has any excluded types
        $has_excluded_type = false;
        foreach ( $exclude_types as $excluded_type ) {
            if ( in_array( $excluded_type, $place_types ) ) {
                $has_excluded_type = true;
                break;
            }
        }
        
        if ( $has_excluded_type ) {
            continue;
        }
        
        // Apply filters
        $rating = isset( $place['rating'] ) ? floatval( $place['rating'] ) : 0;
        $review_count = isset( $place['user_ratings_total'] ) ? intval( $place['user_ratings_total'] ) : 0;
        
        // Skip if doesn't meet minimum requirements
        if ( $rating < $min_rating || $review_count < $min_reviews ) {
            continue;
        }
        
        // Get photo reference if available
        $photo_reference = null;
        if ( isset( $place['photos'][0]['photo_reference'] ) ) {
            $photo_reference = $place['photos'][0]['photo_reference'];
        }
        
        // Get opening hours status
        $open_now = null;
        if ( isset( $place['opening_hours']['open_now'] ) ) {
            $open_now = $place['opening_hours']['open_now'];
        }
        
        // Get price level
        $price_level = isset( $place['price_level'] ) ? intval( $place['price_level'] ) : null;
        
        // Transform to structured format
        $places[] = array(
            'name' => isset( $place['name'] ) ? $place['name'] : '',
            'placeId' => isset( $place['place_id'] ) ? $place['place_id'] : '',
            'rating' => $rating,
            'userRatingsTotal' => $review_count,
            'vicinity' => isset( $place['vicinity'] ) ? $place['vicinity'] : '',
            'coordinates' => array(
                'lat' => isset( $place['geometry']['location']['lat'] ) ? $place['geometry']['location']['lat'] : 0,
                'lng' => isset( $place['geometry']['location']['lng'] ) ? $place['geometry']['location']['lng'] : 0,
            ),
            'priceLevel' => $price_level,
            'openNow' => $open_now,
            'photoReference' => $photo_reference,
            'types' => isset( $place['types'] ) ? $place['types'] : array(),
        );
    }
    
    // Build response
    $response_data = array(
        'success' => true,
        'count' => count( $places ),
        'places' => $places,
        'filters' => array(
            'category' => $category,
            'minRating' => $min_rating,
            'minReviews' => $min_reviews,
            'radius' => $radius,
        ),
    );
    
    // Cache for 30 minutes
    set_transient( $cache_key, $response_data, 30 * MINUTE_IN_SECONDS );
    
    return rest_ensure_response( $response_data );
}

/**
 * Handle Places Photo API request
 *
 * @param WP_REST_Request $request Request object
 * @return WP_HTTP_Response|WP_Error Response or error
 */
function placy_places_photo( $request ) {
    $photo_reference = $request->get_param( 'photo_reference' );
    $max_width = $request->get_param( 'maxwidth' );
    
    // Get API key
    $api_key = defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '';
    
    if ( empty( $api_key ) ) {
        return new WP_Error(
            'api_key_missing',
            'Google Places API key not configured',
            array( 'status' => 500 )
        );
    }
    
    // Build Google Places Photo URL
    $photo_url = add_query_arg( array(
        'maxwidth' => $max_width,
        'photo_reference' => $photo_reference,
        'key' => $api_key,
    ), 'https://maps.googleapis.com/maps/api/place/photo' );
    
    // Return the URL for frontend to fetch directly
    // This avoids storing/proxying images through WordPress
    return rest_ensure_response( array(
        'success' => true,
        'photoUrl' => $photo_url,
    ) );
}
