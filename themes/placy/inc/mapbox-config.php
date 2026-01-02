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
    // Priority 1: Check WordPress options (set in admin or via plugin)
    $token = get_option( 'placy_mapbox_token' );
    if ( ! empty( $token ) ) {
        return $token;
    }
    
    // Priority 2: Check if token is defined in wp-config.php
    if ( defined( 'MAPBOX_ACCESS_TOKEN' ) ) {
        return MAPBOX_ACCESS_TOKEN;
    }
    
    // Priority 3: Check environment variable
    if ( ! empty( $_ENV['MAPBOX_TOKEN'] ) ) {
        return $_ENV['MAPBOX_TOKEN'];
    }
    
    // ERROR: No token configured
    if ( is_admin() ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Placy Theme:</strong> Mapbox token not configured. Please add MAPBOX_ACCESS_TOKEN to wp-config.php or set placy_mapbox_token option.</p></div>';
        } );
    }
    
    error_log( 'Placy Theme: Mapbox token not configured. Maps will not work.' );
    return '';
}

/**
 * Localize Mapbox settings for JavaScript
 */
function placy_localize_mapbox_settings() {
    wp_localize_script( 'placy-poi-map-modal', 'placyMapbox', array(
        'accessToken' => placy_get_mapbox_token(),
        'defaultCenter' => array( 6.1326, 62.3113 ), // Ranheim coordinates [lng, lat]
        'defaultZoom' => 13,
        // Mapbox Default gallery style: https://www.mapbox.com/gallery#mapbox-default
        'style' => 'mapbox://styles/mapbox/streets-v12'
    ) );
}
add_action( 'wp_enqueue_scripts', 'placy_localize_mapbox_settings' );
