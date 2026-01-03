<?php
/**
 * Placy Bulk Import Feature
 * 
 * Allows bulk importing of Google Places as placy_google_point posts
 * 
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Bulk Import menu page under Google Points
 */
function placy_add_bulk_import_menu() {
    add_submenu_page(
        'edit.php?post_type=placy_google_point',
        'Bulk Import',
        'Bulk Import',
        'manage_options',
        'placy-bulk-import',
        'placy_bulk_import_page'
    );
}
add_action( 'admin_menu', 'placy_add_bulk_import_menu' );

/**
 * Render the Bulk Import admin page
 */
function placy_bulk_import_page() {
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'placy' ) );
    }
    
    // Get all projects
    $projects = get_posts( array(
        'post_type' => 'project',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ) );
    
    // Get all categories
    $categories = get_terms( array(
        'taxonomy' => 'placy_categories',
        'hide_empty' => false,
    ) );
    
    // Create nonce
    $nonce = wp_create_nonce( 'placy_bulk_import' );
    ?>
    <div class="wrap">
        <h1>Google Places Bulk Import</h1>
        
        <div class="placy-bulk-import-container">
            <form id="placy-search-form" class="placy-search-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="search-type">Place Type</label>
                        </th>
                        <td>
                            <select id="search-type" name="search-type" required>
                                <option value="">Select type</option>
                                <option value="cafe">Café</option>
                                <option value="restaurant">Restaurant</option>
                                <option value="bar">Bar</option>
                                <option value="bakery">Bakery</option>
                                <option value="meal_takeaway">Takeaway</option>
                                <option value="tourist_attraction">Tourist Attraction</option>
                                <option value="museum">Museum</option>
                                <option value="park">Park</option>
                                <option value="art_gallery">Art Gallery</option>
                                <option value="gym">Gym</option>
                                <option value="shopping_mall">Shopping Mall</option>
                                <option value="store">Store</option>
                            </select>
                            <p class="description">Select the type of place to search for</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="search-query">Search Query (optional)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="search-query" 
                                   name="search-query" 
                                   class="regular-text" 
                                   placeholder="e.g. fine dining, pizza, organic">
                            <p class="description">Additional keywords to refine search (optional)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="search-project">Project</label>
                        </th>
                        <td>
                            <select id="search-project" name="search-project" required>
                                <option value="">Select a project</option>
                                <?php foreach ( $projects as $project ) : ?>
                                    <option value="<?php echo esc_attr( $project->ID ); ?>">
                                        <?php echo esc_html( $project->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Search will use project's location as center point</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="search-radius">Search Radius</label>
                        </th>
                        <td>
                            <select id="search-radius" name="search-radius">
                                <option value="500">500 meters</option>
                                <option value="1000" selected>1 kilometer</option>
                                <option value="2000">2 kilometers</option>
                                <option value="5000">5 kilometers</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="auto-category">Auto-assign Category</label>
                        </th>
                        <td>
                            <select id="auto-category" name="auto-category">
                                <option value="">None (skip category assignment)</option>
                                <?php foreach ( $categories as $category ) : ?>
                                    <option value="<?php echo esc_attr( $category->term_id ); ?>">
                                        <?php echo esc_html( $category->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Automatically assign this category to all imported points</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" id="search-google-places-btn" class="button button-primary button-large">
                        Search Google Places
                    </button>
                </p>
            </form>
            
            <div id="placy-search-results"></div>
            
            <div id="placy-import-progress" style="display: none;">
                <h3>Import Progress</h3>
                <progress id="import-progress-bar" max="100" value="0" style="width: 100%; height: 30px;"></progress>
                <p id="import-status">Importing...</p>
            </div>
        </div>
    </div>
    
    <style>
        .placy-bulk-import-container {
            max-width: 1200px;
            margin: 20px 0;
        }
        
        .placy-search-form {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 30px;
        }
        
        .placy-bulk-actions {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .placy-results-list {
            margin-bottom: 20px;
        }
        
        .placy-result-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            background: #fff;
            border-radius: 4px;
            gap: 15px;
        }
        
        .placy-result-item:hover {
            background: #f9f9f9;
        }
        
        .placy-result-checkbox {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .placy-result-checkbox:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .placy-result-info {
            flex: 1;
        }
        
        .placy-result-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .placy-result-meta {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .placy-result-rating {
            color: #f5b301;
        }
        
        .placy-result-address {
            color: #666;
        }
        
        .placy-result-status {
            flex-shrink: 0;
            padding: 5px 12px;
            border-radius: 3px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-new {
            background: #d4edda;
            color: #155724;
        }
        
        .status-exists {
            background: #fff3cd;
            color: #856404;
        }
        
        #placy-import-progress {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        #import-progress-bar {
            width: 100%;
            height: 30px;
            margin-bottom: 10px;
        }
        
        #import-status {
            font-size: 14px;
            color: #666;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        const nonce = '<?php echo $nonce; ?>';
        
        // Handle search form submission
        $('#placy-search-form').on('submit', function(e) {
            e.preventDefault();
            
            const type = $('#search-type').val();
            const query = $('#search-query').val();
            const projectId = $('#search-project').val();
            const radius = $('#search-radius').val();
            
            if (!type || !projectId) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Show loading
            $('#search-google-places-btn').prop('disabled', true).text('Searching...');
            $('#placy-search-results').html('<p>Searching Google Places...</p>');
            
            // Call AJAX
            $.post(ajaxurl, {
                action: 'placy_search_google_places',
                nonce: nonce,
                type: type,
                query: query,
                project_id: projectId,
                radius: radius
            })
            .done(function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            })
            .fail(function() {
                alert('Search failed. Please try again.');
            })
            .always(function() {
                $('#search-google-places-btn').prop('disabled', false).text('Search Google Places');
            });
        });
        
        // Display search results
        function displayResults(data) {
            let html = '<div class="placy-bulk-actions">';
            html += '<button class="button" id="select-all">Select All</button> ';
            html += '<button class="button" id="deselect-all">Deselect All</button>';
            html += '<span style="margin-left: 20px;">Selected: <strong id="selected-count">0</strong></span>';
            html += '</div>';
            
            html += '<div class="placy-results-list">';
            
            if (data.places && data.places.length > 0) {
                data.places.forEach(function(place) {
                    const isNew = place.status === 'new';
                    const checked = isNew ? 'checked' : '';
                    const disabled = !isNew ? 'disabled' : '';
                    const rating = place.rating ? place.rating.toFixed(1) : 'N/A';
                    const ratingCount = place.userRatingCount || 0;
                    const distance = place.distance ? place.distance + 'm' : '';
                    
                    // Build photo URL if available using caching proxy
                    let photoHtml = '';
                    if (place.photoReference) {
                        // Use WordPress REST API caching proxy (30-day cache, Google ToS compliant)
                        const photoUrl = '<?php echo rest_url( "placy/v1/photo/proxy/" ); ?>' + encodeURIComponent(place.photoReference) + '?maxwidth=100';
                        photoHtml = `<img src="${photoUrl}" alt="${place.displayName.text}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">`;
                    } else {
                        photoHtml = '<div style="width: 60px; height: 60px; background: #e0e0e0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999;">📷</div>';
                    }
                    
                    html += `
                    <div class="placy-result-item">
                        <input type="checkbox" 
                               class="placy-result-checkbox" 
                               value="${place.id}" 
                               ${checked} 
                               ${disabled}>
                        ${photoHtml}
                        <div class="placy-result-info">
                            <div class="placy-result-name">${place.displayName.text}</div>
                            <div class="placy-result-meta">
                                <span class="placy-result-rating">⭐ ${rating} (${ratingCount})</span>
                                ${distance ? '<span class="placy-result-distance">📍 ' + distance + '</span>' : ''}
                                <span class="placy-result-address">${place.formattedAddress || 'No address'}</span>
                            </div>
                        </div>
                        <span class="placy-result-status status-${place.status}">
                            ${isNew ? 'New' : 'Already imported'}
                        </span>
                    </div>`;
                });
            } else {
                html += '<p>No results found. Try adjusting your search query or radius.</p>';
            }
            
            html += '</div>';
            
            if (data.places && data.places.length > 0) {
                html += `<p class="submit">
                    <button type="button" id="import-selected-btn" class="button button-primary button-large">
                        Import Selected (<span id="import-count">0</span>)
                    </button>
                </p>`;
            }
            
            $('#placy-search-results').html(html);
            updateCounters();
        }
        
        // Update selection counters
        function updateCounters() {
            const count = $('.placy-result-checkbox:checked').length;
            $('#selected-count').text(count);
            $('#import-count').text(count);
        }
        
        // Select/Deselect All handlers
        $(document).on('click', '#select-all', function() {
            $('.placy-result-checkbox:not(:disabled)').prop('checked', true);
            updateCounters();
        });
        
        $(document).on('click', '#deselect-all', function() {
            $('.placy-result-checkbox').prop('checked', false);
            updateCounters();
        });
        
        // Checkbox change handler
        $(document).on('change', '.placy-result-checkbox', updateCounters);
        
        // Import selected places
        $(document).on('click', '#import-selected-btn', function() {
            const placeIds = $('.placy-result-checkbox:checked').map(function() {
                return this.value;
            }).get();
            
            if (placeIds.length === 0) {
                alert('Please select at least one place to import');
                return;
            }
            
            const projectId = $('#search-project').val();
            const categoryId = $('#auto-category').val();
            
            // Show progress
            $('#placy-import-progress').show();
            $('#import-progress-bar').val(0);
            $('#import-status').text('Importing ' + placeIds.length + ' places...');
            
            // Disable buttons
            $('#import-selected-btn').prop('disabled', true);
            $('.placy-result-checkbox').prop('disabled', true);
            
            // Call import AJAX
            $.post(ajaxurl, {
                action: 'placy_bulk_import_places',
                nonce: nonce,
                place_ids: placeIds,
                project_id: projectId,
                category_id: categoryId
            })
            .done(function(response) {
                if (response.success) {
                    $('#import-progress-bar').val(100);
                    let statusMsg = '<span style="color: green;">✓ Successfully imported ' + 
                        response.data.imported + ' Google Points!</span>';
                    
                    if (response.data.skipped > 0) {
                        statusMsg += '<br>Skipped ' + response.data.skipped + ' duplicates';
                    }
                    
                    $('#import-status').html(statusMsg);
                    
                    // Auto-redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = 'edit.php?post_type=placy_google_point';
                    }, 2000);
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                    $('#import-selected-btn').prop('disabled', false);
                    $('.placy-result-checkbox:not([disabled])').prop('disabled', false);
                }
            })
            .fail(function() {
                alert('Import failed. Please try again.');
                $('#import-selected-btn').prop('disabled', false);
                $('.placy-result-checkbox:not([disabled])').prop('disabled', false);
            });
        });
    });
    </script>
    <?php
}

/**
 * Calculate distance between two coordinates using Haversine formula
 *
 * @param float $lat1 Latitude 1
 * @param float $lng1 Longitude 1
 * @param float $lat2 Latitude 2
 * @param float $lng2 Longitude 2
 * @return float Distance in meters
 */
function placy_calculate_distance( $lat1, $lng1, $lat2, $lng2 ) {
    $earth_radius = 6371000; // meters
    
    $lat1_rad = deg2rad( $lat1 );
    $lat2_rad = deg2rad( $lat2 );
    $delta_lat = deg2rad( $lat2 - $lat1 );
    $delta_lng = deg2rad( $lng2 - $lng1 );
    
    $a = sin( $delta_lat / 2 ) * sin( $delta_lat / 2 ) +
         cos( $lat1_rad ) * cos( $lat2_rad ) *
         sin( $delta_lng / 2 ) * sin( $delta_lng / 2 );
    
    $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
    
    return $earth_radius * $c;
}

/**
 * Fetch nearby places from Google Places API (New)
 *
 * @param string $type Place type (e.g. 'cafe', 'restaurant')
 * @param string $query Optional additional search query
 * @param array $center Center coordinates ['lat' => float, 'lng' => float]
 * @param int $radius Radius in meters
 * @return array|WP_Error Array of places or error
 */
function placy_fetch_nearby_places( $type, $query, $center, $radius ) {
    $api_key = defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '';
    
    if ( empty( $api_key ) ) {
        return new WP_Error( 'api_key_missing', 'Google Places API key not configured' );
    }
    
    // Use Places API (New) - Text Search with includedType for precise filtering
    $url = 'https://places.googleapis.com/v1/places:searchText';
    
    // Build text query - combine type with optional keyword
    $text_query = ! empty( $query ) ? $type . ' ' . $query : $type;
    
    $body = array(
        'textQuery' => $text_query,
        'includedType' => $type,
        'maxResultCount' => 20,
        'locationBias' => array(
            'circle' => array(
                'center' => array(
                    'latitude' => floatval( $center['lat'] ),
                    'longitude' => floatval( $center['lng'] ),
                ),
                'radius' => floatval( $radius ),
            ),
        ),
    );
    
    // Make API request
    $response = wp_remote_post( $url, array(
        'timeout' => 15,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.rating,places.userRatingCount,places.location,places.photos',
        ),
        'body' => json_encode( $body ),
    ) );
    
    if ( is_wp_error( $response ) ) {
        error_log( 'Google Places Nearby Search Error: ' . $response->get_error_message() );
        return $response;
    }
    
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        $body = wp_remote_retrieve_body( $response );
        error_log( 'Google Places Nearby Search HTTP Error: ' . $response_code . ' - ' . $body );
        return new WP_Error( 'api_error', 'API returned status ' . $response_code );
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    if ( ! isset( $data['places'] ) || ! is_array( $data['places'] ) ) {
        return array();
    }
    
    $center_lat = floatval( $center['lat'] );
    $center_lng = floatval( $center['lng'] );
    
    // Format results and filter by actual distance
    $places = array();
    foreach ( $data['places'] as $place ) {
        // Get place coordinates
        $place_lat = isset( $place['location']['latitude'] ) ? floatval( $place['location']['latitude'] ) : 0;
        $place_lng = isset( $place['location']['longitude'] ) ? floatval( $place['location']['longitude'] ) : 0;
        
        // Skip if coordinates are missing
        if ( ! $place_lat || ! $place_lng ) {
            continue;
        }
        
        // Calculate actual distance
        $distance = placy_calculate_distance( $center_lat, $center_lng, $place_lat, $place_lng );
        
        // Filter: Only include places within the specified radius
        if ( $distance > $radius ) {
            continue;
        }
        
        // Extract first photo reference if available
        $photo_reference = null;
        if ( isset( $place['photos'] ) && is_array( $place['photos'] ) && ! empty( $place['photos'] ) ) {
            $first_photo = $place['photos'][0];
            if ( isset( $first_photo['name'] ) ) {
                $photo_reference = $first_photo['name'];
            }
        }
        
        $places[] = array(
            'id' => isset( $place['id'] ) ? $place['id'] : '',
            'displayName' => isset( $place['displayName'] ) ? $place['displayName'] : array( 'text' => 'Unknown' ),
            'formattedAddress' => isset( $place['formattedAddress'] ) ? $place['formattedAddress'] : '',
            'rating' => isset( $place['rating'] ) ? floatval( $place['rating'] ) : 0,
            'userRatingCount' => isset( $place['userRatingCount'] ) ? intval( $place['userRatingCount'] ) : 0,
            'location' => isset( $place['location'] ) ? $place['location'] : array(),
            'distance' => round( $distance ), // Distance in meters
            'photoReference' => $photo_reference,
        );
    }
    
    // Already sorted by distance due to rankPreference: DISTANCE
    return $places;
}

/**
 * Create a Google Point post
 *
 * @param string $place_id Google Place ID
 * @param int $project_id Project ID to associate with
 * @return int|WP_Error Post ID or error
 */
function placy_create_google_point( $place_id, $project_id ) {
    if ( empty( $place_id ) ) {
        return new WP_Error( 'invalid_place_id', 'Place ID is required' );
    }
    
    // Create post
    $post_id = wp_insert_post( array(
        'post_type' => 'placy_google_point',
        'post_title' => 'Loading...', // Will be updated by API
        'post_status' => 'publish',
    ) );
    
    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }
    
    // Set Google Place ID
    update_field( 'google_place_id', $place_id, $post_id );
    
    // Set project relationship if provided
    if ( $project_id ) {
        update_field( 'project', $project_id, $post_id );
    }
    
    // Manually trigger Google data refresh
    // This ensures data is fetched immediately during bulk import
    if ( function_exists( 'placy_refresh_google_point' ) ) {
        placy_refresh_google_point( $post_id );
    }
    
    return $post_id;
}

/**
 * AJAX Handler: Search Google Places
 */
function placy_ajax_search_google_places() {
    // Verify nonce
    check_ajax_referer( 'placy_bulk_import', 'nonce' );
    
    // Check capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    
    $type = sanitize_text_field( $_POST['type'] );
    $query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';
    $project_id = intval( $_POST['project_id'] );
    $radius = intval( $_POST['radius'] );
    
    // Get project coordinates
    $lat = get_field( 'start_latitude', $project_id );
    $lng = get_field( 'start_longitude', $project_id );
    
    if ( ! $lat || ! $lng ) {
        wp_send_json_error( 'Project coordinates not found' );
    }
    
    // Call Google Nearby Search API
    $results = placy_fetch_nearby_places(
        $type,
        $query,
        array( 'lat' => floatval( $lat ), 'lng' => floatval( $lng ) ),
        $radius
    );
    
    if ( is_wp_error( $results ) ) {
        wp_send_json_error( $results->get_error_message() );
    }
    
    // Check each place for existing imports
    foreach ( $results as &$place ) {
        $existing = get_posts( array(
            'post_type' => 'placy_google_point',
            'meta_key' => 'google_place_id',
            'meta_value' => $place['id'],
            'posts_per_page' => 1,
        ) );
        
        $place['status'] = ! empty( $existing ) ? 'exists' : 'new';
        $place['existing_id'] = ! empty( $existing ) ? $existing[0]->ID : null;
    }
    
    wp_send_json_success( array(
        'places' => $results,
        'count' => count( $results ),
    ) );
}
add_action( 'wp_ajax_placy_search_google_places', 'placy_ajax_search_google_places' );

/**
 * AJAX Handler: Bulk Import Places
 */
function placy_ajax_bulk_import_places() {
    // Verify nonce
    check_ajax_referer( 'placy_bulk_import', 'nonce' );
    
    // Check capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    
    $place_ids = isset( $_POST['place_ids'] ) && is_array( $_POST['place_ids'] ) 
        ? array_map( 'sanitize_text_field', $_POST['place_ids'] ) 
        : array();
    $project_id = intval( $_POST['project_id'] );
    $category_id = ! empty( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : null;
    
    if ( empty( $place_ids ) ) {
        wp_send_json_error( 'No places selected' );
    }
    
    $imported = array();
    $skipped = array();
    
    foreach ( $place_ids as $place_id ) {
        // Sanitize place_id (already sanitized in array_map above, but double-check)
        $place_id = sanitize_text_field( $place_id );
        
        // Check for duplicates
        $existing = get_posts( array(
            'post_type' => 'placy_google_point',
            'meta_key' => 'google_place_id',
            'meta_value' => $place_id,
            'posts_per_page' => 1,
        ) );
        
        if ( ! empty( $existing ) ) {
            $skipped[] = $place_id;
            continue;
        }
        
        // Create Google Point
        $post_id = placy_create_google_point( $place_id, $project_id );
        
        if ( ! is_wp_error( $post_id ) ) {
            // Auto-assign category if selected
            if ( $category_id ) {
                wp_set_object_terms( $post_id, $category_id, 'placy_categories' );
            }
            
            $imported[] = $post_id;
        }
        
        // Throttle: 1 second between requests to avoid API rate limits
        sleep( 1 );
    }
    
    wp_send_json_success( array(
        'imported' => count( $imported ),
        'skipped' => count( $skipped ),
        'imported_ids' => $imported,
    ) );
}
add_action( 'wp_ajax_placy_bulk_import_places', 'placy_ajax_bulk_import_places' );
