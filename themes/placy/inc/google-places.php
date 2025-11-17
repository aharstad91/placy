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
