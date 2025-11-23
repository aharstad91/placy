<?php
/**
 * Placy Google Places API Integration
 * Handles fetching and caching data for Google Points
 *
 * @package Placy
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get coordinates from any POI (Native or Google)
 *
 * @param int $post_id Post ID
 * @return array|null Array with 'lat' and 'lng' or null
 */
function placy_get_poi_coordinates( $post_id ) {
    $post_type = get_post_type( $post_id );
    
    if ( $post_type === 'placy_native_point' ) {
        // Native Point: coordinates are in ACF field
        $coords = get_field( 'coordinates', $post_id );
        
        if ( $coords && isset( $coords['latitude'], $coords['longitude'] ) ) {
            return array(
                'lat' => $coords['latitude'],
                'lng' => $coords['longitude'],
            );
        }
        
        // Fallback: separate lat/lng fields
        $lat = get_field( 'latitude', $post_id );
        $lng = get_field( 'longitude', $post_id );
        
        if ( $lat && $lng ) {
            return array(
                'lat' => $lat,
                'lng' => $lng,
            );
        }
    }
    
    if ( $post_type === 'placy_google_point' ) {
        // Google Point: coordinates are in nearby_search_cache
        // Use get_post_meta to avoid ACF double-escaping
        $cache = get_post_meta( $post_id, 'nearby_search_cache', true );
        
        if ( is_array( $cache ) ) {
            $data = $cache;
        } else if ( ! empty( $cache ) ) {
            // Decode HTML entities and parse JSON
            $cache = wp_kses_decode_entities( $cache );
            $data = json_decode( $cache, true );
        } else {
            $data = null;
        }
        
        if ( is_array( $data ) ) {
            // Try geometry.lat/lng (from Nearby Search API)
            if ( isset( $data['geometry']['lat'], $data['geometry']['lng'] ) ) {
                return array(
                    'lat' => $data['geometry']['lat'],
                    'lng' => $data['geometry']['lng'],
                );
            }
            
            // Fallback: try location.lat/lng
            if ( isset( $data['location']['lat'], $data['location']['lng'] ) ) {
                return array(
                    'lat' => $data['location']['lat'],
                    'lng' => $data['location']['lng'],
                );
            }
        }
    }
    
    return null;
}

/**
 * Check if API rate limit allows request
 *
 * @return bool
 */
function placy_check_rate_limit() {
    $count = get_transient( 'placy_google_api_count' );
    
    if ( false === $count ) {
        set_transient( 'placy_google_api_count', 1, DAY_IN_SECONDS );
        return true;
    }
    
    // Limit: 100 requests per day (configurable)
    $daily_limit = apply_filters( 'placy_google_api_daily_limit', 100 );
    
    if ( $count >= $daily_limit ) {
        error_log( 'Placy: Daily Google API rate limit reached' );
        return false;
    }
    
    set_transient( 'placy_google_api_count', $count + 1, DAY_IN_SECONDS );
    return true;
}

/**
 * Fetch Nearby Search data from Google Places API
 *
 * @param string $place_id Google Place ID
 * @return array|false Place data or false on failure
 */
function placy_fetch_nearby_search( $place_id ) {
    if ( empty( $place_id ) ) {
        return false;
    }
    
    // Check rate limit
    if ( ! placy_check_rate_limit() ) {
        return false;
    }
    
    $api_key = defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '';
    
    if ( empty( $api_key ) ) {
        error_log( 'Placy: GOOGLE_PLACES_API_KEY not defined' );
        return false;
    }
    
    // Use new Places API (New) - Get Place Details
    $url = 'https://places.googleapis.com/v1/places/' . $place_id;
    
    $response = wp_remote_get( $url, array(
        'timeout' => 15,
        'headers' => array(
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'id,displayName,formattedAddress,rating,userRatingCount,location,types,photos',
        ),
    ) );
    
    if ( is_wp_error( $response ) ) {
        error_log( 'Placy Nearby Search Error: ' . $response->get_error_message() );
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        $body = wp_remote_retrieve_body( $response );
        error_log( 'Placy Nearby Search HTTP Error: ' . $response_code . ' - ' . $body );
        return false;
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( empty( $data ) ) {
        error_log( 'Placy Nearby Search: Invalid response' );
        return false;
    }
    
    // Format for compatibility with existing code
    return array(
        'name' => isset( $data['displayName']['text'] ) ? $data['displayName']['text'] : '',
        'rating' => isset( $data['rating'] ) ? floatval( $data['rating'] ) : null,
        'user_ratings_total' => isset( $data['userRatingCount'] ) ? intval( $data['userRatingCount'] ) : null,
        'geometry' => isset( $data['location'] ) ? array( 
            'lat' => $data['location']['latitude'],
            'lng' => $data['location']['longitude']
        ) : array(),
        'types' => isset( $data['types'] ) ? $data['types'] : array(),
        'vicinity' => isset( $data['formattedAddress'] ) ? $data['formattedAddress'] : '',
        'formatted_address' => isset( $data['formattedAddress'] ) ? $data['formattedAddress'] : '',
        'photos' => isset( $data['photos'] ) ? $data['photos'] : array(),
    );
}

/**
 * Fetch Place Details from Google Places API
 *
 * @param string $place_id Google Place ID
 * @return array|false Place details or false on failure
 */
function placy_fetch_place_details( $place_id ) {
    if ( empty( $place_id ) ) {
        return false;
    }
    
    // Check rate limit
    if ( ! placy_check_rate_limit() ) {
        return false;
    }
    
    $api_key = defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '';
    
    if ( empty( $api_key ) ) {
        return false;
    }
    
    // Use new Places API (New) - Get Place Details
    $url = 'https://places.googleapis.com/v1/places/' . $place_id;
    
    $response = wp_remote_get( $url, array(
        'timeout' => 15,
        'headers' => array(
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'websiteUri,nationalPhoneNumber,internationalPhoneNumber,currentOpeningHours,googleMapsUri',
        ),
    ) );
    
    if ( is_wp_error( $response ) ) {
        error_log( 'Placy Place Details Error: ' . $response->get_error_message() );
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        $body = wp_remote_retrieve_body( $response );
        error_log( 'Placy Place Details HTTP Error: ' . $response_code . ' - ' . $body );
        return false;
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( empty( $data ) ) {
        return false;
    }
    
    // Format for compatibility with existing code
    return array(
        'website' => isset( $data['websiteUri'] ) ? $data['websiteUri'] : '',
        'phone' => isset( $data['nationalPhoneNumber'] ) ? $data['nationalPhoneNumber'] : ( isset( $data['internationalPhoneNumber'] ) ? $data['internationalPhoneNumber'] : '' ),
        'google_maps_url' => isset( $data['googleMapsUri'] ) ? $data['googleMapsUri'] : '',
        'opening_hours' => isset( $data['currentOpeningHours'] ) ? $data['currentOpeningHours'] : array(),
    );
}

/**
 * Refresh Google Point data
 *
 * @param int $post_id Google Point post ID
 * @return bool Success
 */
function placy_refresh_google_point( $post_id ) {
    $place_id = get_field( 'google_place_id', $post_id );
    
    if ( empty( $place_id ) ) {
        return false;
    }
    
    // Fetch Nearby Search data
    $nearby_data = placy_fetch_nearby_search( $place_id );
    
    if ( ! $nearby_data ) {
        error_log( 'Placy Refresh Error: Failed to fetch nearby search data for Place ID: ' . $place_id );
        return false;
    }
    
    error_log( 'Placy Refresh Success: Fetched data for "' . ( isset( $nearby_data['name'] ) ? $nearby_data['name'] : 'Unknown' ) . '"' );
    
    // Fetch Place Details
    $details_data = placy_fetch_place_details( $place_id );
    
    if ( ! $details_data ) {
        $details_data = array();
    }
    
    // Update post title from Google data FIRST
    if ( ! empty( $nearby_data['name'] ) ) {
        $current_title = get_the_title( $post_id );
        
        error_log( 'Placy Refresh: Current title="' . $current_title . '", New title="' . $nearby_data['name'] . '"' );
        
        // Update if title is empty, "Auto Draft", "Loading...", or "(no title)"
        // Note: WordPress HTML-encodes "..." to "&#8230;" so we check for both
        if ( empty( $current_title ) || 
             $current_title === 'Auto Draft' || 
             $current_title === '(no title)' ||
             $current_title === 'Loading...' ||
             $current_title === 'Loading&#8230;' ||
             strpos( $current_title, 'Loading' ) !== false ||
             strpos( $current_title, 'AUTO-DRAFT' ) !== false ) {
            
            // Remove hook temporarily to avoid infinite loop
            remove_action( 'save_post_placy_google_point', 'placy_auto_fetch_google_data', 10 );
            
            $update_result = wp_update_post( array(
                'ID' => $post_id,
                'post_title' => $nearby_data['name'],
            ) );
            
            error_log( 'Placy Refresh: Title update result=' . ( $update_result ? 'SUCCESS' : 'FAILED' ) );
            
            // Re-add hook
            add_action( 'save_post_placy_google_point', 'placy_auto_fetch_google_data', 10, 2 );
        } else {
            error_log( 'Placy Refresh: Skipping title update (current title not in update list)' );
        }
    }
    
    // Store in ACF fields (use JSON_UNESCAPED_UNICODE only - let quotes be escaped)
    update_field( 'nearby_search_cache', json_encode( $nearby_data, JSON_UNESCAPED_UNICODE ), $post_id );
    update_field( 'place_details_cache', json_encode( $details_data, JSON_UNESCAPED_UNICODE ), $post_id );
    update_field( 'last_synced', current_time( 'Y-m-d H:i:s' ), $post_id );
    
    return true;
}

/**
 * Check if Google Point needs refresh
 *
 * @param int $post_id Google Point post ID
 * @return bool
 */
function placy_needs_refresh( $post_id ) {
    $last_synced = get_field( 'last_synced', $post_id );
    
    if ( empty( $last_synced ) ) {
        return true;
    }
    
    $last_synced_time = strtotime( $last_synced );
    $is_featured = get_field( 'featured', $post_id );
    
    // Featured: refresh if older than 1 day
    if ( $is_featured ) {
        return ( time() - $last_synced_time ) > DAY_IN_SECONDS;
    }
    
    // Regular: refresh if older than 7 days
    return ( time() - $last_synced_time ) > ( 7 * DAY_IN_SECONDS );
}

/**
 * Auto-fetch Google data when Place ID is added/changed
 */
add_action( 'save_post_placy_google_point', 'placy_auto_fetch_google_data', 10, 2 );
function placy_auto_fetch_google_data( $post_id, $post ) {
    // Skip autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Skip revisions
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    
    // Skip if this is the hook calling itself (prevent infinite loop)
    if ( defined( 'PLACY_FETCHING_DATA' ) && PLACY_FETCHING_DATA ) {
        return;
    }
    
    $place_id = get_field( 'google_place_id', $post_id );
    
    if ( empty( $place_id ) ) {
        return;
    }
    
    // Get previous Place ID to detect changes
    $old_place_id = get_post_meta( $post_id, '_placy_prev_place_id', true );
    
    // Fetch data if:
    // 1. No data exists yet, OR
    // 2. Place ID has changed
    $cache = get_field( 'nearby_search_cache', $post_id );
    
    if ( empty( $cache ) || $place_id !== $old_place_id ) {
        // Store current Place ID for next comparison
        update_post_meta( $post_id, '_placy_prev_place_id', $place_id );
        
        // Set flag to prevent infinite loop
        define( 'PLACY_FETCHING_DATA', true );
        
        // Fetch data
        placy_refresh_google_point( $post_id );
    }
}

/**
 * AJAX handler for manual refresh
 */
add_action( 'wp_ajax_placy_refresh_google_point', 'placy_ajax_refresh_google_point' );
function placy_ajax_refresh_google_point() {
    check_ajax_referer( 'placy_refresh', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    
    $post_id = intval( $_POST['post_id'] );
    
    if ( get_post_type( $post_id ) !== 'placy_google_point' ) {
        wp_send_json_error( 'Invalid post type' );
    }
    
    $result = placy_refresh_google_point( $post_id );
    
    if ( $result ) {
        wp_send_json_success( 'Data refreshed successfully' );
    } else {
        wp_send_json_error( 'Failed to refresh data' );
    }
}

/**
 * Add refresh button script to admin
 */
add_action( 'acf/input/admin_footer', 'placy_add_refresh_button_script' );
function placy_add_refresh_button_script() {
    global $post;
    
    if ( ! $post || $post->post_type !== 'placy_google_point' ) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add refresh button after Google Place ID field
        var $placeIdField = $('.acf-field[data-name="google_place_id"]');
        
        if ($placeIdField.length) {
            $placeIdField.append(
                '<div style="margin-top: 10px;">' +
                '<button type="button" class="button button-primary placy-refresh-google-data">' +
                '🔄 Refresh Google Data Now</button>' +
                '<span class="placy-refresh-status" style="margin-left: 10px;"></span>' +
                '</div>'
            );
        }
        
        $('.placy-refresh-google-data').on('click', function() {
            var $button = $(this);
            var $status = $('.placy-refresh-status');
            
            if (!confirm('Refresh Google API data? This will use 2 API requests.')) {
                return;
            }
            
            $button.prop('disabled', true).text('⏳ Refreshing...');
            $status.text('');
            
            $.post(ajaxurl, {
                action: 'placy_refresh_google_point',
                post_id: <?php echo $post->ID; ?>,
                nonce: '<?php echo wp_create_nonce('placy_refresh'); ?>'
            }, function(response) {
                $button.prop('disabled', false).text('🔄 Refresh Google Data Now');
                
                if (response.success) {
                    $status.html('<span style="color: green;">✓ ' + response.data + '</span>');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $status.html('<span style="color: red;">✗ Error: ' + response.data + '</span>');
                }
            });
        });
    });
    </script>
    <?php
}
