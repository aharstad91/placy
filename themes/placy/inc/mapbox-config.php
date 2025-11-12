<?php
/**
 * Mapbox Configuration
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Mapbox Access Token
 * 
 * For production, this should be stored in wp-config.php:
 * define('MAPBOX_ACCESS_TOKEN', 'your-token-here');
 */
function placy_get_mapbox_token() {
    // Check if token is defined in wp-config.php
    if ( defined( 'MAPBOX_ACCESS_TOKEN' ) ) {
        return MAPBOX_ACCESS_TOKEN;
    }
    
    // Fallback to a public token (for development only)
    // Replace this with your actual Mapbox token
    return 'pk.eyJ1IjoicGxhY3kiLCJhIjoiY20zdnRxNGFoMDJuNjJxcHVxcDVsYjk2YyJ9.example';
}

/**
 * Localize Mapbox settings for JavaScript
 */
function placy_localize_mapbox_settings() {
    wp_localize_script( 'placy-poi-map-modal', 'placyMapbox', array(
        'accessToken' => placy_get_mapbox_token(),
        'defaultCenter' => array( 6.1326, 62.3113 ), // Ranheim coordinates [lng, lat]
        'defaultZoom' => 13,
        'style' => 'mapbox://styles/mapbox/streets-v12'
    ) );
}
add_action( 'wp_enqueue_scripts', 'placy_localize_mapbox_settings' );
