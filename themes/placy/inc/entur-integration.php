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
            'group_by_direction' => array(
                'required' => false,
                'type' => 'string',
                'default' => '1',
                'description' => 'Group departures by direction/quay (1 or 0)',
            ),
            'line_filter' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Comma-separated list of line codes to filter (e.g., FB73 or 1,2,3)',
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
    $group_by_direction = $request->get_param( 'group_by_direction' ) !== '0';
    $line_filter = $request->get_param( 'line_filter' );
    
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
    
    // Transform and filter the data (pass quay_id and line_filter for filtering)
    $result = placy_transform_entur_response( $response, $transport_mode, $group_by_direction, $quay_id, $line_filter );
    
    // Check if we got any valid departures
    if ( empty( $result['departures'] ) && empty( $result['grouped'] ) ) {
        return rest_ensure_response( array(
            'success' => true,
            'stopplace_id' => $stopplace_id,
            'stopplace_name' => $result['stopplace_name'] ?? '',
            'timestamp' => current_time( 'H:i' ),
            'departures' => array(),
            'grouped' => array(),
            'message' => 'no_departures',
        ) );
    }
    
    return rest_ensure_response( array(
        'success' => true,
        'stopplace_id' => $stopplace_id,
        'stopplace_name' => $result['stopplace_name'] ?? '',
        'timestamp' => current_time( 'H:i' ),
        'departures' => $result['departures'],
        'grouped' => $result['grouped'],
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
            quays {
                id
                name
                description
                publicCode
            }
            estimatedCalls(
                numberOfDepartures: 50
                timeRange: 86400
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
                    description
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
 * @param bool $group_by_direction Whether to group by direction/quay
 * @param string|null $quay_id Optional quay ID to filter by
 * @param string|null $line_filter Optional comma-separated list of line codes to filter
 * @return array Transformed departures with optional grouping
 */
function placy_transform_entur_response( $response, $transport_mode = null, $group_by_direction = true, $quay_id = null, $line_filter = null ) {
    // Parse line filter into array
    $allowed_lines = array();
    if ( $line_filter ) {
        $allowed_lines = array_map( 'trim', explode( ',', $line_filter ) );
        $allowed_lines = array_filter( $allowed_lines ); // Remove empty values
    }
    $result = array(
        'stopplace_name' => '',
        'departures' => array(),
        'grouped' => array(),
    );
    
    if ( ! isset( $response['data']['stopPlace'] ) ) {
        return $result;
    }
    
    $stopPlace = $response['data']['stopPlace'];
    $result['stopplace_name'] = $stopPlace['name'] ?? '';
    
    // Build quay info map for descriptions
    $quay_info = array();
    if ( isset( $stopPlace['quays'] ) ) {
        foreach ( $stopPlace['quays'] as $quay ) {
            $quay_info[ $quay['id'] ] = array(
                'name' => $quay['name'] ?? '',
                'description' => $quay['description'] ?? '',
                'publicCode' => $quay['publicCode'] ?? '',
            );
        }
    }
    
    if ( ! isset( $stopPlace['estimatedCalls'] ) ) {
        return $result;
    }
    
    $calls = $stopPlace['estimatedCalls'];
    $now = new DateTime( 'now', new DateTimeZone( 'Europe/Oslo' ) );
    
    $all_departures = array();
    $grouped_departures = array();
    
    foreach ( $calls as $call ) {
        // Skip cancelled departures
        if ( isset( $call['cancellation'] ) && $call['cancellation'] === true ) {
            continue;
        }
        
        // Filter by quay ID if specified
        $call_quay_id = isset( $call['quay']['id'] ) ? $call['quay']['id'] : '';
        if ( $quay_id && $call_quay_id !== $quay_id ) {
            continue;
        }
        
        // Filter by transport mode if specified
        $call_mode = '';
        if ( isset( $call['serviceJourney']['line']['transportMode'] ) ) {
            $call_mode = $call['serviceJourney']['line']['transportMode'];
        }
        
        if ( $transport_mode && strtolower( $call_mode ) !== strtolower( $transport_mode ) ) {
            continue;
        }
        
        // Filter by line code if specified
        $call_line_code = '';
        if ( isset( $call['serviceJourney']['line']['publicCode'] ) ) {
            $call_line_code = $call['serviceJourney']['line']['publicCode'];
        }
        
        if ( ! empty( $allowed_lines ) && ! in_array( $call_line_code, $allowed_lines, true ) ) {
            continue;
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
        
        if ( isset( $call['serviceJourney']['line'] ) ) {
            $line = $call['serviceJourney']['line'];
            $line_name = $line['name'] ?? '';
            $line_number = $line['publicCode'] ?? '';
        }
        
        $destination = $call['destinationDisplay']['frontText'] ?? 'Ukjent';
        
        // Get quay info
        $quay_id = isset( $call['quay']['id'] ) ? $call['quay']['id'] : '';
        $quay_name = isset( $call['quay']['name'] ) ? $call['quay']['name'] : '';
        $quay_description = isset( $call['quay']['description'] ) ? $call['quay']['description'] : '';
        $quay_code = isset( $call['quay']['publicCode'] ) ? $call['quay']['publicCode'] : '';
        
        // Use description from quay_info if available (more reliable)
        if ( $quay_id && isset( $quay_info[ $quay_id ] ) && ! empty( $quay_info[ $quay_id ]['description'] ) ) {
            $quay_description = $quay_info[ $quay_id ]['description'];
        }
        
        $departure = array(
            'time' => $departure_time->format( 'H:i' ),
            'relative_time' => $minutes,
            'destination' => $destination,
            'line_name' => $line_name,
            'line_number' => $line_number,
            'transport_mode' => $call_mode,
            'realtime' => $call['realtime'] ?? false,
            'quay_id' => $quay_id,
            'quay' => $quay_name,
            'quay_description' => $quay_description,
            'quay_code' => $quay_code,
        );
        
        $all_departures[] = $departure;
        
        // Group by quay if requested
        if ( $group_by_direction && $quay_id ) {
            if ( ! isset( $grouped_departures[ $quay_id ] ) ) {
                // Create a direction label
                $direction_label = $quay_description;
                if ( empty( $direction_label ) ) {
                    $direction_label = $quay_name;
                    if ( $quay_code ) {
                        $direction_label .= ' (' . $quay_code . ')';
                    }
                }
                
                $grouped_departures[ $quay_id ] = array(
                    'quay_id' => $quay_id,
                    'direction' => $direction_label,
                    'departures' => array(),
                );
            }
            
            // Limit to 3 departures per direction
            if ( count( $grouped_departures[ $quay_id ]['departures'] ) < 3 ) {
                $grouped_departures[ $quay_id ]['departures'][] = $departure;
            }
        }
    }
    
    // Sort flat departures by time and limit to 5
    usort( $all_departures, function( $a, $b ) {
        return $a['relative_time'] - $b['relative_time'];
    } );
    $result['departures'] = array_slice( $all_departures, 0, 5 );
    
    // Convert grouped departures to indexed array and sort by first departure time
    $grouped_array = array_values( $grouped_departures );
    usort( $grouped_array, function( $a, $b ) {
        $time_a = isset( $a['departures'][0] ) ? $a['departures'][0]['relative_time'] : PHP_INT_MAX;
        $time_b = isset( $b['departures'][0] ) ? $b['departures'][0]['relative_time'] : PHP_INT_MAX;
        return $time_a - $time_b;
    } );
    $result['grouped'] = $grouped_array;
    
    return $result;
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
