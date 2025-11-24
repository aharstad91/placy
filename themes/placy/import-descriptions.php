<?php
/**
 * Import Descriptions from JSON to WordPress
 * 
 * This script reads a JSON file with Google Point descriptions
 * and imports them into WordPress via the REST API.
 * 
 * Usage from command line:
 * php import-descriptions.php descriptions.json
 * 
 * JSON Format:
 * {
 *   "ChIJab_zEdAxbUYRiMCFnG3IS34": "Description text here...",
 *   "ChIJN1t_tDeuEmsRUsoyG83frY4": "Another description..."
 * }
 * 
 * @package Placy
 * @since 1.0.0
 */

// Check if running from command line
if ( php_sapi_name() !== 'cli' ) {
    die( "This script can only be run from the command line.\n" );
}

// Get JSON file from command line argument
$json_file = $argv[1] ?? null;

if ( ! $json_file ) {
    echo "Usage: php import-descriptions.php <json-file>\n";
    echo "Example: php import-descriptions.php descriptions.json\n";
    exit( 1 );
}

if ( ! file_exists( $json_file ) ) {
    echo "Error: File not found: {$json_file}\n";
    exit( 1 );
}

// Read JSON file
$json_content = file_get_contents( $json_file );
$descriptions = json_decode( $json_content, true );

if ( json_last_error() !== JSON_ERROR_NONE ) {
    echo "Error: Invalid JSON file. " . json_last_error_msg() . "\n";
    exit( 1 );
}

if ( empty( $descriptions ) || ! is_array( $descriptions ) ) {
    echo "Error: JSON must be an object with place_id keys and description values.\n";
    exit( 1 );
}

echo "========================================\n";
echo "  Placy Description Import\n";
echo "========================================\n\n";

echo "File: {$json_file}\n";
echo "Descriptions to import: " . count( $descriptions ) . "\n\n";

// Get WordPress URL from environment or config
$wp_url = getenv( 'WP_URL' ) ?: 'http://localhost:8888/placy';
$api_url = rtrim( $wp_url, '/' ) . '/wp-json/placy/v1/google-points/descriptions';

echo "API Endpoint: {$api_url}\n";

// Get authentication credentials
echo "\nAuthentication:\n";
echo "You need to authenticate to update descriptions.\n";
echo "Options:\n";
echo "  1. Application Password (recommended)\n";
echo "  2. Skip authentication (if endpoint is public)\n\n";

echo "Enter your username (or press Enter to skip auth): ";
$username = trim( fgets( STDIN ) );

$auth_header = '';
if ( ! empty( $username ) ) {
    echo "Enter your application password: ";
    // Disable echo for password input
    if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
        $password = trim( fgets( STDIN ) );
    } else {
        system( 'stty -echo' );
        $password = trim( fgets( STDIN ) );
        system( 'stty echo' );
        echo "\n";
    }
    
    $auth_header = 'Authorization: Basic ' . base64_encode( "{$username}:{$password}" );
}

echo "\n";
echo "Sending request...\n\n";

// Prepare POST data
$post_data = json_encode( array(
    'descriptions' => $descriptions,
) );

// Send POST request
$ch = curl_init( $api_url );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_POST, true );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array_filter( array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen( $post_data ),
    $auth_header,
) ) );

$response = curl_exec( $ch );
$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
curl_close( $ch );

// Parse response
$result = json_decode( $response, true );

echo "========================================\n";
echo "  Results\n";
echo "========================================\n\n";

if ( $http_code === 200 && isset( $result['success'] ) && $result['success'] ) {
    echo "✓ Success!\n\n";
    
    if ( isset( $result['stats'] ) ) {
        echo "Statistics:\n";
        echo "  Total:   " . $result['stats']['total'] . "\n";
        echo "  Updated: " . $result['stats']['updated'] . " ✓\n";
        echo "  Failed:  " . $result['stats']['failed'] . ( $result['stats']['failed'] > 0 ? " ✗" : "" ) . "\n";
        echo "  Skipped: " . $result['stats']['skipped'] . "\n\n";
    }
    
    if ( isset( $result['message'] ) ) {
        echo $result['message'] . "\n\n";
    }
    
    // Show updated posts
    if ( ! empty( $result['updated'] ) ) {
        echo "Updated posts:\n";
        foreach ( $result['updated'] as $item ) {
            echo "  ✓ {$item['name']}\n";
            echo "    Post ID: {$item['post_id']}\n";
            echo "    Place ID: {$item['place_id']}\n";
            echo "    Edit URL: {$item['edit_url']}\n\n";
        }
    }
    
    // Show failures
    if ( ! empty( $result['failed'] ) ) {
        echo "Failed updates:\n";
        foreach ( $result['failed'] as $item ) {
            echo "  ✗ Place ID: {$item['place_id']}\n";
            echo "    Reason: {$item['reason']}\n\n";
        }
    }
    
    // Show skipped
    if ( ! empty( $result['skipped'] ) ) {
        echo "Skipped:\n";
        foreach ( $result['skipped'] as $item ) {
            echo "  - Place ID: {$item['place_id']}\n";
            echo "    Reason: {$item['reason']}\n\n";
        }
    }
    
    echo "========================================\n";
    echo "Import completed successfully!\n";
    echo "========================================\n";
    
    exit( 0 );
} else {
    echo "✗ Error!\n\n";
    echo "HTTP Status: {$http_code}\n";
    echo "Response:\n";
    echo json_encode( $result, JSON_PRETTY_PRINT ) . "\n\n";
    
    exit( 1 );
}
