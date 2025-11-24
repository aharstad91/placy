<?php
/**
 * Google Points Descriptions API
 * 
 * REST API endpoints for automated description management
 * 
 * Endpoints:
 * - GET  /wp-json/placy/v1/google-points/descriptions - List all points with description status
 * - POST /wp-json/placy/v1/google-points/descriptions - Bulk update descriptions
 * 
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API routes
 */
add_action( 'rest_api_init', 'placy_register_descriptions_routes' );
function placy_register_descriptions_routes() {
    // GET: List all Google Points with description status
    register_rest_route( 'placy/v1', '/google-points/descriptions', array(
        'methods'             => 'GET',
        'callback'            => 'placy_api_get_points_descriptions',
        'permission_callback' => '__return_true', // Public endpoint
    ) );
    
    // POST: Bulk update descriptions
    register_rest_route( 'placy/v1', '/google-points/descriptions', array(
        'methods'             => 'POST',
        'callback'            => 'placy_api_update_descriptions',
        'permission_callback' => 'placy_api_auth_check',
        'args'                => array(
            'descriptions' => array(
                'required'          => true,
                'type'              => 'object',
                'description'       => 'Object with place_id as keys and description text as values',
                'validate_callback' => function( $param ) {
                    return is_array( $param ) || is_object( $param );
                },
            ),
        ),
    ) );
}

/**
 * Authentication check for protected endpoints
 * 
 * @param WP_REST_Request $request
 * @return bool|WP_Error
 */
function placy_api_auth_check( $request ) {
    // Check if user is authenticated and has edit_posts capability
    if ( ! current_user_can( 'edit_posts' ) ) {
        return new WP_Error(
            'rest_forbidden',
            'You do not have permission to update descriptions.',
            array( 'status' => 403 )
        );
    }
    
    return true;
}

/**
 * GET: List all Google Points with description status
 * 
 * Returns all Google Points with their current description status,
 * allowing Claude to identify which points need descriptions.
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function placy_api_get_points_descriptions( $request ) {
    $posts = get_posts( array(
        'post_type'      => 'placy_google_point',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    
    $points = array();
    
    foreach ( $posts as $post ) {
        $place_id = get_field( 'google_place_id', $post->ID );
        $editorial_text = get_field( 'editorial_text', $post->ID );
        $rating = get_field( 'google_rating', $post->ID );
        $reviews = get_field( 'google_user_ratings_total', $post->ID );
        
        // Parse cached Google data for additional context
        $nearby_cache = get_field( 'nearby_search_cache', $post->ID );
        $nearby_data = ! empty( $nearby_cache ) ? json_decode( $nearby_cache, true ) : array();
        
        $place_details_cache = get_field( 'place_details_cache', $post->ID );
        $place_details = ! empty( $place_details_cache ) ? json_decode( $place_details_cache, true ) : array();
        
        // Extract types/categories
        $types = isset( $nearby_data['types'] ) ? $nearby_data['types'] : array();
        
        // Get WordPress categories
        $categories = wp_get_post_terms( $post->ID, 'placy_categories', array( 'fields' => 'names' ) );
        
        // Get address
        $address = isset( $nearby_data['formattedAddress'] ) ? $nearby_data['formattedAddress'] : '';
        if ( empty( $address ) && isset( $nearby_data['vicinity'] ) ) {
            $address = $nearby_data['vicinity'];
        }
        
        // Get website
        $website = isset( $place_details['website'] ) ? $place_details['website'] : '';
        
        // Determine description status
        $has_description = ! empty( $editorial_text ) && strlen( strip_tags( $editorial_text ) ) > 20;
        
        $points[] = array(
            'post_id'         => $post->ID,
            'place_id'        => $place_id,
            'name'            => $post->post_title,
            'description'     => $editorial_text ? strip_tags( $editorial_text ) : '',
            'has_description' => $has_description,
            'rating'          => $rating ? floatval( $rating ) : null,
            'review_count'    => $reviews ? intval( $reviews ) : null,
            'address'         => $address,
            'types'           => $types,
            'categories'      => is_array( $categories ) ? $categories : array(),
            'website'         => $website,
            'edit_url'        => get_edit_post_link( $post->ID, 'raw' ),
        );
    }
    
    // Calculate statistics
    $total = count( $points );
    $with_description = count( array_filter( $points, function( $p ) {
        return $p['has_description'];
    } ) );
    $without_description = $total - $with_description;
    
    return new WP_REST_Response( array(
        'success' => true,
        'stats'   => array(
            'total'               => $total,
            'with_description'    => $with_description,
            'without_description' => $without_description,
            'percentage_complete' => $total > 0 ? round( ( $with_description / $total ) * 100, 1 ) : 0,
        ),
        'points'  => $points,
    ), 200 );
}

/**
 * POST: Bulk update descriptions
 * 
 * Updates editorial_text field for multiple Google Points based on place_id.
 * Expects JSON with place_id as keys and description text as values.
 * 
 * Example POST body:
 * {
 *   "descriptions": {
 *     "ChIJab_zEdAxbUYRiMCFnG3IS34": "Speilsalen er et elegant konserthus...",
 *     "ChIJN1t_tDeuEmsRUsoyG83frY4": "En moderne kafé med spesialkaffi..."
 *   }
 * }
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function placy_api_update_descriptions( $request ) {
    $descriptions = $request->get_param( 'descriptions' );
    
    if ( empty( $descriptions ) || ! is_array( $descriptions ) ) {
        return new WP_Error(
            'invalid_data',
            'Descriptions parameter must be a non-empty object.',
            array( 'status' => 400 )
        );
    }
    
    $updated = array();
    $failed = array();
    $skipped = array();
    
    foreach ( $descriptions as $place_id => $description ) {
        // Sanitize inputs
        $place_id = sanitize_text_field( $place_id );
        $description = wp_kses_post( $description ); // Allow basic HTML
        
        // Skip empty descriptions
        if ( empty( trim( strip_tags( $description ) ) ) ) {
            $skipped[] = array(
                'place_id' => $place_id,
                'reason'   => 'Empty description',
            );
            continue;
        }
        
        // Find post by place_id
        $posts = get_posts( array(
            'post_type'      => 'placy_google_point',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => 'google_place_id',
                    'value'   => $place_id,
                    'compare' => '=',
                ),
            ),
        ) );
        
        if ( empty( $posts ) ) {
            $failed[] = array(
                'place_id' => $place_id,
                'reason'   => 'Google Point not found',
            );
            continue;
        }
        
        $post_id = $posts[0]->ID;
        
        // Update editorial_text field
        $result = update_field( 'editorial_text', $description, $post_id );
        
        if ( $result ) {
            $updated[] = array(
                'post_id'  => $post_id,
                'place_id' => $place_id,
                'name'     => get_the_title( $post_id ),
                'edit_url' => get_edit_post_link( $post_id, 'raw' ),
            );
        } else {
            $failed[] = array(
                'place_id' => $place_id,
                'post_id'  => $post_id,
                'reason'   => 'Failed to update field',
            );
        }
    }
    
    $response = array(
        'success'  => true,
        'stats'    => array(
            'total'   => count( $descriptions ),
            'updated' => count( $updated ),
            'failed'  => count( $failed ),
            'skipped' => count( $skipped ),
        ),
        'updated'  => $updated,
        'failed'   => $failed,
        'skipped'  => $skipped,
        'message'  => sprintf(
            'Updated %d of %d descriptions.',
            count( $updated ),
            count( $descriptions )
        ),
    );
    
    return new WP_REST_Response( $response, 200 );
}
