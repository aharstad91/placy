<?php
/**
 * Trondheim Bysykkel Integration
 * 
 * Integrates with GBFS (General Bikeshare Feed Specification) API
 * to display real-time bike availability for Trondheim Bysykkel stations
 * 
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API endpoints for Trondheim Bysykkel
 */
add_action( 'rest_api_init', function() {
    register_rest_route( 'placy/v1', '/bysykkel/availability/(?P<station_id>[0-9]+)', array(
        'methods' => 'GET',
        'callback' => 'placy_get_bysykkel_availability',
        'permission_callback' => '__return_true', // Public endpoint
        'args' => array(
            'station_id' => array(
                'required' => true,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param );
                }
            ),
        ),
    ) );
} );

/**
 * Get bike availability for a station
 * 
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response|WP_Error Response object or error
 */
function placy_get_bysykkel_availability( $request ) {
    $station_id = $request->get_param( 'station_id' );
    
    // Fetch data from GBFS API
    $result = placy_fetch_bysykkel_status( $station_id );
    
    if ( is_wp_error( $result ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error' => $result->get_error_message(),
        ), 500 );
    }
    
    return new WP_REST_Response( array(
        'success' => true,
        'station_id' => $station_id,
        'bikes_available' => $result['bikes_available'],
        'docks_available' => $result['docks_available'],
        'is_renting' => $result['is_renting'],
        'is_installed' => $result['is_installed'],
        'last_reported' => $result['last_reported'],
    ), 200 );
}

/**
 * Fetch station status from GBFS API
 * 
 * @param string $station_id Station ID
 * @return array|WP_Error Station status or error
 */
function placy_fetch_bysykkel_status( $station_id ) {
    $url = 'https://gbfs.urbansharing.com/trondheimbysykkel.no/station_status.json';
    
    // Make API request with timeout
    $response = wp_remote_get( $url, array(
        'timeout' => 5,
        'headers' => array(
            'Client-Identifier' => 'placy-stasjonskvartalet', // Required by Urban Sharing
        ),
    ) );
    
    // Handle network errors
    if ( is_wp_error( $response ) ) {
        error_log( 'Bysykkel API Network Error: ' . $response->get_error_message() );
        return $response;
    }
    
    // Check HTTP status
    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code !== 200 ) {
        error_log( 'Bysykkel API HTTP Error: Status ' . $status_code );
        return new WP_Error( 'bysykkel_http_error', 'API returned HTTP status ' . $status_code );
    }
    
    // Parse JSON
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        error_log( 'Bysykkel API JSON Error: ' . json_last_error_msg() );
        return new WP_Error( 'bysykkel_json_error', 'Invalid JSON response' );
    }
    
    // Find the specific station
    if ( ! isset( $data['data']['stations'] ) ) {
        return new WP_Error( 'bysykkel_invalid_response', 'Invalid API response structure' );
    }
    
    $stations = $data['data']['stations'];
    $station = null;
    
    foreach ( $stations as $s ) {
        if ( $s['station_id'] === $station_id || $s['station_id'] === (string) $station_id ) {
            $station = $s;
            break;
        }
    }
    
    if ( ! $station ) {
        return new WP_Error( 'bysykkel_station_not_found', 'Station not found: ' . $station_id );
    }
    
    // Extract relevant data
    return array(
        'bikes_available' => isset( $station['num_bikes_available'] ) ? (int) $station['num_bikes_available'] : 0,
        'docks_available' => isset( $station['num_docks_available'] ) ? (int) $station['num_docks_available'] : 0,
        'is_renting' => isset( $station['is_renting'] ) ? (bool) $station['is_renting'] : false,
        'is_installed' => isset( $station['is_installed'] ) ? (bool) $station['is_installed'] : false,
        'last_reported' => isset( $station['last_reported'] ) ? $station['last_reported'] : time(),
    );
}
