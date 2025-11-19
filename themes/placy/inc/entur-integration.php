<?php
/**
 * Entur API Integration for Live Departures
 * 
 * Provides REST API endpoint and helper functions for fetching
 * real-time departure information from Entur's Journey Planner API
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API endpoint for Entur live departures
 */
function placy_register_entur_api_endpoint() {
    register_rest_route( 'placy/v1', '/entur/departures/(?P<stopplace_id>[a-zA-Z0-9:%]+)', array(
        'methods' => 'GET',
        'callback' => 'placy_get_entur_departures',
        'permission_callback' => '__return_true',
        'args' => array(
            'stopplace_id' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Entur StopPlace ID (format: NSR:StopPlace:xxxxx)',
                'validate_callback' => function( $param ) {
                    // Decode URL-encoded colons before validation
                    $param = str_replace('%3A', ':', $param);
                    return preg_match( '/^NSR:StopPlace:\d+$/', $param );
                },
                'sanitize_callback' => function( $param ) {
                    // Decode URL-encoded colons
                    return str_replace('%3A', ':', $param);
                },
            ),
            'quay_id' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Optional Quay ID to filter specific platform',
            ),
            'transport_mode' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Optional transport mode filter (rail, bus, water, etc.)',
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'placy_register_entur_api_endpoint' );

/**
 * Fetch live departures from Entur API
 * 
 * @param WP_REST_Request $request Request object
 * @return WP_REST_Response|WP_Error Response object or error
 */
function placy_get_entur_departures( $request ) {
    $stopplace_id = $request->get_param( 'stopplace_id' );
    $quay_id = $request->get_param( 'quay_id' );
    $transport_mode = $request->get_param( 'transport_mode' );
    
    // Validate StopPlace ID format
    if ( ! preg_match( '/^NSR:StopPlace:\d+$/', $stopplace_id ) ) {
        return new WP_Error(
            'invalid_stopplace_id',
            'Invalid StopPlace ID format',
            array( 'status' => 400 )
        );
    }
    
    // Build GraphQL query
    $query = placy_build_entur_graphql_query( $stopplace_id, $quay_id, $transport_mode );
    
    // Make API request with error handling
    $response = placy_fetch_from_entur_api( $query );
    
    if ( is_wp_error( $response ) ) {
        error_log( 'Entur API Error for ' . $stopplace_id . ': ' . $response->get_error_message() );
        
        // Return empty response instead of error (fail silently on frontend)
        return rest_ensure_response( array(
            'success' => false,
            'stopplace_id' => $stopplace_id,
            'departures' => array(),
            'error' => 'api_unavailable',
        ) );
    }
    
    // Transform and filter the data
    $departures = placy_transform_entur_response( $response, $transport_mode );
    
    // Check if we got any valid departures
    if ( empty( $departures ) ) {
        return rest_ensure_response( array(
            'success' => true,
            'stopplace_id' => $stopplace_id,
            'timestamp' => current_time( 'H:i' ),
            'departures' => array(),
            'message' => 'no_departures',
        ) );
    }
    
    return rest_ensure_response( array(
        'success' => true,
        'stopplace_id' => $stopplace_id,
        'timestamp' => current_time( 'H:i' ),
        'departures' => $departures,
    ) );
}

/**
 * Build GraphQL query for Entur API
 * 
 * @param string $stopplace_id StopPlace ID
 * @param string|null $quay_id Optional Quay ID
 * @param string|null $transport_mode Optional transport mode filter
 * @return string GraphQL query
 */
function placy_build_entur_graphql_query( $stopplace_id, $quay_id = null, $transport_mode = null ) {
    // Get current time in ISO format for filtering future departures
    $now = current_time( 'c' );
    
    $query = '{
        stopPlace(id: "' . esc_attr( $stopplace_id ) . '") {
            id
            name
            estimatedCalls(
                numberOfDepartures: 50
                timeRange: 10800
                startTime: "' . $now . '"
            ) {
                expectedDepartureTime
                realtime
                cancellation
                destinationDisplay {
                    frontText
                }
                serviceJourney {
                    line {
                        publicCode
                        name
                        transportMode
                    }
                }
                quay {
                    id
                    name
                    publicCode
                }
            }
        }
    }';
    
    return $query;
}

/**
 * Fetch data from Entur Journey Planner API
 * 
 * @param string $query GraphQL query
 * @return array|WP_Error API response or error
 */
function placy_fetch_from_entur_api( $query ) {
    $url = 'https://api.entur.io/journey-planner/v3/graphql';
    
    // Make API request with timeout
    $response = wp_remote_post( $url, array(
        'timeout' => 5, // 5 second timeout as specified
        'headers' => array(
            'Content-Type' => 'application/json',
            'ET-Client-Name' => 'placy-stasjonskvartalet', // Required by Entur
        ),
        'body' => wp_json_encode( array(
            'query' => $query,
        ) ),
    ) );
    
    // Handle network errors (timeout, connection failure, etc.)
    if ( is_wp_error( $response ) ) {
        error_log( 'Entur API Network Error: ' . $response->get_error_message() );
        return $response;
    }
    
    // Check HTTP status code
    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code !== 200 ) {
        error_log( 'Entur API HTTP Error: Status ' . $status_code );
        return new WP_Error( 'entur_http_error', 'API returned HTTP status ' . $status_code );
    }
    
    // Parse JSON response
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        error_log( 'Entur API JSON Error: ' . json_last_error_msg() );
        return new WP_Error( 'json_decode_error', 'Failed to decode API response' );
    }
    
    // Check for GraphQL errors
    if ( isset( $data['errors'] ) && ! empty( $data['errors'] ) ) {
        $error_messages = array_map( function( $error ) {
            return $error['message'] ?? 'Unknown error';
        }, $data['errors'] );
        
        error_log( 'Entur GraphQL Errors: ' . implode( ', ', $error_messages ) );
        return new WP_Error( 'graphql_error', implode( ', ', $error_messages ) );
    }
    
    // Check if data structure is valid
    if ( ! isset( $data['data'] ) ) {
        error_log( 'Entur API: Invalid response structure' );
        return new WP_Error( 'invalid_response', 'Invalid API response structure' );
    }
    
    return $data;
}

/**
 * Transform Entur API response to simplified format
 * 
 * @param array $response Raw API response
 * @param string|null $transport_mode Optional transport mode filter
 * @return array Transformed departures
 */
function placy_transform_entur_response( $response, $transport_mode = null ) {
    $departures = array();
    
    if ( ! isset( $response['data']['stopPlace']['estimatedCalls'] ) ) {
        return $departures;
    }
    
    $calls = $response['data']['stopPlace']['estimatedCalls'];
    $now = new DateTime( 'now', new DateTimeZone( 'Europe/Oslo' ) );
    
    foreach ( $calls as $call ) {
        // Skip cancelled departures
        if ( isset( $call['cancellation'] ) && $call['cancellation'] === true ) {
            continue;
        }
        
        // Filter by transport mode if specified
        if ( $transport_mode && isset( $call['serviceJourney']['line']['transportMode'] ) ) {
            $call_mode = $call['serviceJourney']['line']['transportMode'];
            if ( strtolower( $call_mode ) !== strtolower( $transport_mode ) ) {
                continue;
            }
        }
        
        // Parse departure time
        $departure_time = new DateTime( $call['expectedDepartureTime'], new DateTimeZone( 'Europe/Oslo' ) );
        
        // Calculate relative time in minutes
        $interval = $now->diff( $departure_time );
        $minutes = ( $interval->days * 24 * 60 ) + ( $interval->h * 60 ) + $interval->i;
        
        // Skip if departure is in the past
        if ( $minutes < 0 ) {
            continue;
        }
        
        // Extract relevant data
        $line_name = '';
        $line_number = '';
        $transport_mode = '';
        
        if ( isset( $call['serviceJourney']['line'] ) ) {
            $line = $call['serviceJourney']['line'];
            $line_name = $line['name'] ?? '';
            $line_number = $line['publicCode'] ?? '';
            $transport_mode = $line['transportMode'] ?? '';
        }
        
        $destination = $call['destinationDisplay']['frontText'] ?? 'Ukjent';
        $quay_name = isset( $call['quay']['name'] ) ? $call['quay']['name'] : null;
        $quay_code = isset( $call['quay']['publicCode'] ) ? $call['quay']['publicCode'] : null;
        
        $departures[] = array(
            'time' => $departure_time->format( 'H:i' ),
            'relative_time' => $minutes,
            'destination' => $destination,
            'line_name' => $line_name,
            'line_number' => $line_number,
            'transport_mode' => $transport_mode,
            'realtime' => $call['realtime'] ?? false,
            'quay' => $quay_name,
            'quay_code' => $quay_code,
        );
        
        // Limit to 5 departures
        if ( count( $departures ) >= 5 ) {
            break;
        }
    }
    
    return $departures;
}

/**
 * Format duration in minutes to human-readable format
 * 
 * @param int $minutes Duration in minutes
 * @return string Formatted duration
 */
function placy_format_departure_time( $minutes ) {
    if ( $minutes === 0 ) {
        return 'Nå';
    } elseif ( $minutes < 60 ) {
        return $minutes . ' min';
    } else {
        $hours = floor( $minutes / 60 );
        $remaining_minutes = $minutes % 60;
        if ( $remaining_minutes === 0 ) {
            return $hours . ' t';
        }
        return $hours . ' t ' . $remaining_minutes . ' min';
    }
}

/**
 * Add Entur fields to Point REST API response
 */
function placy_add_entur_fields_to_rest_api() {
    register_rest_field(
        'point',
        'entur_data',
        array(
            'get_callback' => function( $post ) {
                return array(
                    'stopplace_id' => get_field( 'entur_stopplace_id', $post['id'] ),
                    'quay_id' => get_field( 'entur_quay_id', $post['id'] ),
                    'show_live_departures' => get_field( 'show_live_departures', $post['id'] ),
                );
            },
            'schema' => array(
                'description' => 'Entur integration data',
                'type' => 'object',
            ),
        )
    );
}
add_action( 'rest_api_init', 'placy_add_entur_fields_to_rest_api' );
