<?php
/**
 * Hyre Car Sharing Integration
 * 
 * Integrates with Entur Mobility v2 GBFS API to display
 * real-time car availability for Hyre stations
 * 
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API endpoints for Hyre
 */
add_action( 'rest_api_init', function() {
    register_rest_route( 'placy/v1', '/hyre/availability/(?P<station_id>.+)', array(
        'methods' => 'GET',
        'callback' => 'placy_get_hyre_availability',
        'permission_callback' => '__return_true',
        'args' => array(
            'station_id' => array(
                'required' => true,
                'validate_callback' => function( $param ) {
                    // Decode URL-encoded colons before validation
                    $param = rawurldecode( $param );
                    return preg_match( '/^HYR:Station:[a-f0-9\-]+$/', $param );
                },
                'sanitize_callback' => function( $param ) {
                    // Decode URL-encoded characters and preserve colons
                    $param = rawurldecode( $param );
                    // Only allow safe characters for station ID
                    return preg_replace( '/[^a-zA-Z0-9:\-]/', '', $param );
                },
            ),
        ),
    ) );
    
    // Endpoint to get station info (for admin/setup)
    register_rest_route( 'placy/v1', '/hyre/stations', array(
        'methods' => 'GET',
        'callback' => 'placy_get_hyre_stations',
        'permission_callback' => '__return_true',
        'args' => array(
            'region' => array(
                'required' => false,
                'default' => 'norge_trondheim',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
} );

/**
 * Get car availability for a Hyre station
 * 
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response Response object
 */
function placy_get_hyre_availability( $request ) {
    $station_id = $request->get_param( 'station_id' );
    
    // Fetch station info and status
    $station_info = placy_fetch_hyre_station_info( $station_id );
    $station_status = placy_fetch_hyre_station_status( $station_id );
    
    if ( is_wp_error( $station_info ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error' => $station_info->get_error_message(),
        ), 500 );
    }
    
    if ( is_wp_error( $station_status ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error' => $station_status->get_error_message(),
        ), 500 );
    }
    
    // Get vehicle types for readable names
    $vehicle_types = placy_fetch_hyre_vehicle_types();
    
    // Build available vehicles list with readable names
    $available_vehicles = array();
    if ( ! empty( $station_status['vehicle_types_available'] ) ) {
        foreach ( $station_status['vehicle_types_available'] as $vt ) {
            if ( $vt['count'] > 0 ) {
                $type_id = $vt['vehicle_type_id'];
                $readable_name = isset( $vehicle_types[ $type_id ] ) ? $vehicle_types[ $type_id ] : $type_id;
                $available_vehicles[] = array(
                    'type_id' => $type_id,
                    'name' => $readable_name,
                    'count' => $vt['count'],
                );
            }
        }
    }
    
    return new WP_REST_Response( array(
        'success' => true,
        'station_id' => $station_id,
        'station_name' => $station_info['name'],
        'address' => $station_info['address'],
        'vehicles_available' => $station_status['num_vehicles_available'],
        'docks_available' => $station_status['num_docks_available'],
        'capacity' => $station_info['capacity'],
        'is_charging_station' => $station_info['is_charging_station'],
        'available_vehicles' => $available_vehicles,
        'rental_url' => $station_info['rental_url'],
        'last_reported' => $station_status['last_reported'],
        'timestamp' => current_time( 'H:i' ),
    ), 200 );
}

/**
 * Get all Hyre stations for a region
 * 
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response Response object
 */
function placy_get_hyre_stations( $request ) {
    $region = $request->get_param( 'region' );
    $region_id = 'HYR:Region:' . $region;
    
    $url = 'https://api.entur.io/mobility/v2/gbfs/v3/hyrenorge/station_information';
    
    $response = wp_remote_get( $url, array(
        'timeout' => 10,
        'headers' => array(
            'ET-Client-Name' => 'placy-stasjonskvartalet',
        ),
    ) );
    
    if ( is_wp_error( $response ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error' => $response->get_error_message(),
        ), 500 );
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! isset( $data['data']['stations'] ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error' => 'Invalid API response',
        ), 500 );
    }
    
    // Filter by region
    $stations = array_filter( $data['data']['stations'], function( $station ) use ( $region_id ) {
        return isset( $station['region_id'] ) && $station['region_id'] === $region_id;
    } );
    
    // Simplify output
    $result = array_map( function( $station ) {
        return array(
            'station_id' => $station['station_id'],
            'name' => isset( $station['name'][0]['text'] ) ? $station['name'][0]['text'] : 'Unknown',
            'address' => $station['address'] ?? '',
            'lat' => $station['lat'],
            'lon' => $station['lon'],
            'capacity' => $station['capacity'] ?? 0,
            'is_charging_station' => $station['is_charging_station'] ?? false,
        );
    }, array_values( $stations ) );
    
    return new WP_REST_Response( array(
        'success' => true,
        'region' => $region,
        'stations' => $result,
    ), 200 );
}

/**
 * Fetch station information from Hyre API
 * 
 * @param string $station_id Station ID
 * @return array|WP_Error Station info or error
 */
function placy_fetch_hyre_station_info( $station_id ) {
    $url = 'https://api.entur.io/mobility/v2/gbfs/v3/hyrenorge/station_information';
    
    $response = wp_remote_get( $url, array(
        'timeout' => 5,
        'headers' => array(
            'ET-Client-Name' => 'placy-stasjonskvartalet',
        ),
    ) );
    
    if ( is_wp_error( $response ) ) {
        error_log( 'Hyre API Network Error: ' . $response->get_error_message() );
        return $response;
    }
    
    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code !== 200 ) {
        return new WP_Error( 'hyre_http_error', 'API returned HTTP status ' . $status_code );
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! isset( $data['data']['stations'] ) ) {
        return new WP_Error( 'hyre_invalid_response', 'Invalid API response structure' );
    }
    
    // Find the specific station
    foreach ( $data['data']['stations'] as $station ) {
        if ( $station['station_id'] === $station_id ) {
            return array(
                'name' => isset( $station['name'][0]['text'] ) ? $station['name'][0]['text'] : 'Unknown',
                'address' => $station['address'] ?? '',
                'lat' => $station['lat'],
                'lon' => $station['lon'],
                'capacity' => $station['capacity'] ?? 0,
                'is_charging_station' => $station['is_charging_station'] ?? false,
                'rental_url' => $station['rental_uris']['web'] ?? '',
            );
        }
    }
    
    return new WP_Error( 'hyre_station_not_found', 'Station not found: ' . $station_id );
}

/**
 * Fetch station status from Hyre API
 * 
 * @param string $station_id Station ID
 * @return array|WP_Error Station status or error
 */
function placy_fetch_hyre_station_status( $station_id ) {
    $url = 'https://api.entur.io/mobility/v2/gbfs/v3/hyrenorge/station_status';
    
    $response = wp_remote_get( $url, array(
        'timeout' => 5,
        'headers' => array(
            'ET-Client-Name' => 'placy-stasjonskvartalet',
        ),
    ) );
    
    if ( is_wp_error( $response ) ) {
        error_log( 'Hyre API Network Error: ' . $response->get_error_message() );
        return $response;
    }
    
    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code !== 200 ) {
        return new WP_Error( 'hyre_http_error', 'API returned HTTP status ' . $status_code );
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! isset( $data['data']['stations'] ) ) {
        return new WP_Error( 'hyre_invalid_response', 'Invalid API response structure' );
    }
    
    // Find the specific station
    foreach ( $data['data']['stations'] as $station ) {
        if ( $station['station_id'] === $station_id ) {
            return array(
                'num_vehicles_available' => $station['num_vehicles_available'] ?? 0,
                'num_docks_available' => $station['num_docks_available'] ?? 0,
                'vehicle_types_available' => $station['vehicle_types_available'] ?? array(),
                'is_installed' => $station['is_installed'] ?? false,
                'is_renting' => $station['is_renting'] ?? false,
                'last_reported' => $station['last_reported'] ?? '',
            );
        }
    }
    
    return new WP_Error( 'hyre_station_not_found', 'Station not found: ' . $station_id );
}

/**
 * Fetch vehicle types for readable names
 * 
 * @return array Vehicle type ID => name mapping
 */
function placy_fetch_hyre_vehicle_types() {
    // Cache for 1 hour
    $cache_key = 'placy_hyre_vehicle_types';
    $cached = get_transient( $cache_key );
    
    if ( $cached !== false ) {
        return $cached;
    }
    
    $url = 'https://api.entur.io/mobility/v2/gbfs/v3/hyrenorge/vehicle_types';
    
    $response = wp_remote_get( $url, array(
        'timeout' => 5,
        'headers' => array(
            'ET-Client-Name' => 'placy-stasjonskvartalet',
        ),
    ) );
    
    if ( is_wp_error( $response ) ) {
        return array();
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! isset( $data['data']['vehicle_types'] ) ) {
        return array();
    }
    
    $types = array();
    foreach ( $data['data']['vehicle_types'] as $vt ) {
        $id = $vt['vehicle_type_id'];
        // Build readable name from make and model
        // Handle case where make/model could be arrays or strings
        $make = isset( $vt['make'] ) ? ( is_array( $vt['make'] ) ? ( $vt['make'][0]['text'] ?? '' ) : $vt['make'] ) : '';
        $model = isset( $vt['model'] ) ? ( is_array( $vt['model'] ) ? ( $vt['model'][0]['text'] ?? '' ) : $vt['model'] ) : '';
        $name = trim( $make . ' ' . $model );
        if ( empty( $name ) && isset( $vt['name'] ) ) {
            $name = is_array( $vt['name'] ) ? ( $vt['name'][0]['text'] ?? '' ) : $vt['name'];
        }
        $types[ $id ] = $name ?: $id;
    }
    
    set_transient( $cache_key, $types, HOUR_IN_SECONDS );
    
    return $types;
}
