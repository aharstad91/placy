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
 * IMPORTANT: Add your Mapbox token in one of these ways:
 * 
 * Method 1 (Recommended): In wp-config.php add:
 * define('MAPBOX_ACCESS_TOKEN', 'pk.eyJ1IjoieW91ci11c2VybmFtZSIsImEiOiJ5b3VyLXRva2VuIn0.your-signature');
 * 
 * Method 2: Replace the return value below with your actual token
 * 
 * Get your token from: https://account.mapbox.com/access-tokens/
 */
function placy_get_mapbox_token() {
    // Check if token is defined in wp-config.php
    if ( defined( 'MAPBOX_ACCESS_TOKEN' ) ) {
        return MAPBOX_ACCESS_TOKEN;
    }
    
    // Fallback token - REPLACE THIS WITH YOUR ACTUAL MAPBOX TOKEN
    // Get your token from: https://account.mapbox.com/access-tokens/
    return 'pk.eyJ1IjoicGxhY3ktdGVzdCIsImEiOiJjbTN2dHE0YWgwMm42MnFwdXFwNWxiOTZjIn0.L-uQzXJlWvqYGPQvXJ-Q0Q';
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
