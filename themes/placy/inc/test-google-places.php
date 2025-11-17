<?php
/**
 * Test Google Places API
 * Visit: /wp-admin/admin-ajax.php?action=test_google_places
 */

function placy_test_google_places_callback() {
    $place_id = 'ChIJab_zEdAxbUYRiMCFnG3IS34'; // Speilsalen
    
    echo "<h2>Testing Google Places API</h2>";
    echo "<p><strong>Place ID:</strong> " . esc_html( $place_id ) . "</p>";
    
    // Check API key
    $api_key = defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '';
    echo "<p><strong>API Key configured:</strong> " . ( $api_key ? 'Yes (' . substr($api_key, 0, 20) . '...)' : 'No' ) . "</p>";
    
    // Clear cache first
    placy_clear_place_cache($place_id);
    echo "<p>Cache cleared...</p>";
    
    // Make direct API test
    $url = 'https://places.googleapis.com/v1/places/' . $place_id;
    echo "<p><strong>API URL:</strong> " . esc_html( $url ) . "</p>";
    
    $response = wp_remote_get( $url, array(
        'headers' => array(
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,googleMapsUri',
        ),
        'timeout' => 10,
    ) );
    
    echo "<h3>Response:</h3>";
    if ( is_wp_error( $response ) ) {
        echo "<p style='color:red'>WP Error: " . esc_html( $response->get_error_message() ) . "</p>";
    } else {
        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        echo "<p><strong>HTTP Code:</strong> " . esc_html( $code ) . "</p>";
        echo "<p><strong>Response Body:</strong></p>";
        echo "<pre>" . esc_html( $body ) . "</pre>";
        
        if ( $code === 200 ) {
            $data = json_decode( $body, true );
            echo "<h3>✅ Parsed Data:</h3>";
            echo "<pre>";
            print_r( $data );
            echo "</pre>";
        }
    }
    
    wp_die();
}

add_action('wp_ajax_test_google_places', 'placy_test_google_places_callback');
add_action('wp_ajax_nopriv_test_google_places', 'placy_test_google_places_callback');
