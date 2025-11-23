<?php
/**
 * Google Points CPT Query API
 * 
 * Provides REST endpoint to query Google Points CPT with same filtering
 * capabilities as the original Google Places API integration.
 * 
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API endpoint for Google Points query
 */
function placy_register_google_points_query_endpoint() {
    register_rest_route( 'placy/v1', '/google-points/query', array(
        'methods' => 'GET',
        'callback' => 'placy_query_google_points',
        'permission_callback' => '__return_true',
        'args' => array(
            'category' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Category taxonomy slug to filter by',
                'default' => '',
            ),
            'keyword' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Keyword to search in title and content',
                'default' => '',
            ),
            'lat' => array(
                'required' => true,
                'type' => 'number',
                'description' => 'Latitude coordinate for distance calculation',
            ),
            'lng' => array(
                'required' => true,
                'type' => 'number',
                'description' => 'Longitude coordinate for distance calculation',
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
                'default' => 20,
            ),
            'excludeTypes' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'JSON array of place types to exclude',
                'default' => '["lodging"]',
            ),
            'excludePlaceIds' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'JSON array of Google Place IDs to exclude',
                'default' => '[]',
            ),
            'projectId' => array(
                'required' => false,
                'type' => 'integer',
                'description' => 'Project ID to filter by (optional)',
                'default' => 0,
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'placy_register_google_points_query_endpoint' );

/**
 * Query Google Points CPT with filtering
 *
 * @param WP_REST_Request $request Request object
 * @return WP_REST_Response Response object
 */
function placy_query_google_points( $request ) {
    $category = $request->get_param( 'category' );
    $keyword = $request->get_param( 'keyword' );
    $lat = floatval( $request->get_param( 'lat' ) );
    $lng = floatval( $request->get_param( 'lng' ) );
    $radius = intval( $request->get_param( 'radius' ) );
    $min_rating = floatval( $request->get_param( 'minRating' ) );
    $min_reviews = intval( $request->get_param( 'minReviews' ) );
    $project_id = intval( $request->get_param( 'projectId' ) );
    
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
    
    // Build WP_Query args
    $query_args = array(
        'post_type' => 'placy_google_point',
        'post_status' => 'publish',
        'posts_per_page' => 100, // Fetch more than needed, filter by distance later
        'orderby' => 'title',
        'order' => 'ASC',
    );
    
    // Add category filter if specified
    if ( ! empty( $category ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'placy_categories',
                'field' => 'slug',
                'terms' => $category,
            ),
        );
    }
    
    // Add keyword search if specified
    // Note: This will be handled via custom filtering in the loop
    // to include taxonomy terms in search
    
    // Add project filter if specified
    if ( $project_id > 0 ) {
        $query_args['meta_query'] = array(
            array(
                'key' => 'project',
                'value' => $project_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        );
    }
    
    // Execute query
    $query = new WP_Query( $query_args );
    
    $places = array();
    
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Get Google Place ID
            $google_place_id = get_field( 'google_place_id', $post_id );
            
            // Skip if in excluded place IDs list
            if ( ! empty( $google_place_id ) && in_array( $google_place_id, $exclude_place_ids ) ) {
                continue;
            }
            
            // Get cached nearby search data
            $cache_json = get_field( 'nearby_search_cache', $post_id );
            
            if ( empty( $cache_json ) ) {
                continue;
            }
            
            // Parse cache data
            $cache_data = json_decode( $cache_json, true );
            
            if ( ! is_array( $cache_data ) ) {
                continue;
            }
            
            // Extract coordinates
            $poi_lat = isset( $cache_data['coordinates']['lat'] ) ? floatval( $cache_data['coordinates']['lat'] ) : null;
            $poi_lng = isset( $cache_data['coordinates']['lng'] ) ? floatval( $cache_data['coordinates']['lng'] ) : null;
            
            if ( is_null( $poi_lat ) || is_null( $poi_lng ) ) {
                continue;
            }
            
            // Calculate distance
            $distance = placy_calculate_distance( $lat, $lng, $poi_lat, $poi_lng );
            
            // Skip if outside radius
            if ( $distance > $radius ) {
                continue;
            }
            
            // Extract rating and reviews
            $rating = isset( $cache_data['rating'] ) ? floatval( $cache_data['rating'] ) : 0;
            $review_count = isset( $cache_data['user_ratings_total'] ) ? intval( $cache_data['user_ratings_total'] ) : 0;
            
            // Skip if doesn't meet minimum requirements
            if ( $rating < $min_rating || $review_count < $min_reviews ) {
                continue;
            }
            
            // Get place types from cache
            $place_types = isset( $cache_data['types'] ) ? $cache_data['types'] : array();
            
            // Filter by keyword if specified
            // Mimics Google Places API keyword search: matches against name, types, and address
            if ( ! empty( $keyword ) ) {
                $keyword_lower = strtolower( $keyword );
                $name_lower = strtolower( isset( $cache_data['name'] ) ? $cache_data['name'] : get_the_title( $post_id ) );
                $address_lower = strtolower( isset( $cache_data['vicinity'] ) ? $cache_data['vicinity'] : '' );
                
                // Check if keyword matches name, address, or any type
                $matches_name = strpos( $name_lower, $keyword_lower ) !== false;
                $matches_address = strpos( $address_lower, $keyword_lower ) !== false;
                $matches_types = false;
                
                foreach ( $place_types as $type ) {
                    if ( strpos( strtolower( $type ), $keyword_lower ) !== false ) {
                        $matches_types = true;
                        break;
                    }
                }
                
                // Skip if keyword doesn't match any field
                if ( ! $matches_name && ! $matches_address && ! $matches_types ) {
                    continue;
                }
            }
            
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
            
            // Build place data in same format as Google Places API
            $places[] = array(
                'name' => isset( $cache_data['name'] ) ? $cache_data['name'] : get_the_title( $post_id ),
                'placeId' => $google_place_id,
                'rating' => $rating,
                'userRatingsTotal' => $review_count,
                'url' => 'https://www.google.com/maps/place/?q=place_id:' . $google_place_id,
                'vicinity' => isset( $cache_data['vicinity'] ) ? $cache_data['vicinity'] : '',
                'coordinates' => array(
                    'lat' => $poi_lat,
                    'lng' => $poi_lng,
                ),
                'priceLevel' => isset( $cache_data['price_level'] ) ? intval( $cache_data['price_level'] ) : null,
                'openNow' => isset( $cache_data['open_now'] ) ? $cache_data['open_now'] : null,
                'photoReference' => isset( $cache_data['photo_reference'] ) ? $cache_data['photo_reference'] : null,
                'types' => $place_types,
                'distance' => round( $distance ), // Add distance for sorting
                'postId' => $post_id, // Add WordPress post ID for reference
            );
        }
        
        wp_reset_postdata();
    }
    
    // Sort by distance (closest first)
    usort( $places, function( $a, $b ) {
        return $a['distance'] - $b['distance'];
    });
    
    // Build response in same format as original API
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
        'source' => 'cpt', // Indicate this is from CPT, not live API
    );
    
    return rest_ensure_response( $response_data );
}

// Note: placy_calculate_distance() function already exists in inc/placy-bulk-import.php
