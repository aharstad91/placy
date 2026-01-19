<?php
/**
 * POI Highlight Block Template
 * 
 * Displays a single POI with prominent hero-style layout
 *
 * @param array $block The block settings and attributes.
 * @param string $content The block inner HTML (empty).
 * @param bool $is_preview True during AJAX preview.
 * @param int|string $post_id The post ID this block is saved to.
 */

// Get the selected POI
$poi = get_field('poi_item');

// Debug: log what we get
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'POI Highlight Block - POI Data: ' . print_r( $poi, true ) );
}

if ( ! $poi ) {
    if ( is_admin() ) {
        echo '<p style="padding: 20px; background: #f0f0f0; text-align: center;">Please select a POI in block settings</p>';
    } else {
        echo '<!-- POI Highlight: No POI selected -->';
    }
    return;
}

// Get POI data
$poi_id = $poi->ID;
$title = get_the_title( $poi_id );

// Get display name: ACF 'name' field first, fallback to post title
$acf_name = get_field( 'name', $poi_id );
$display_name = ! empty( $acf_name ) ? $acf_name : $title;

// Get editorial text if available, otherwise use post content
$editorial_text = get_field( 'editorial_text', $poi_id );
$content = $editorial_text ? $editorial_text : apply_filters( 'the_content', get_post_field( 'post_content', $poi_id ) );

$featured_image = get_the_post_thumbnail_url( $poi_id, 'large' );
$secondary_image = get_field( 'secondary_image', $poi_id );

// Get coordinates for map syncing (works for both Native and Google Points)
$coords = '';
$lat = null;
$lng = null;
$post_type = get_post_type( $poi_id );

// Google Points: get from cached nearby_search data
if ( $post_type === 'placy_google_point' ) {
    // Use get_post_meta to avoid ACF double-escaping
    $cache = get_post_meta( $poi_id, 'nearby_search_cache', true );
    
    if ( is_array( $cache ) ) {
        $data = $cache;
    } else if ( ! empty( $cache ) ) {
        // Clean up: remove HTML entities, escaped slashes, and trailing garbage
        $cache = html_entity_decode( $cache, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $cache = stripslashes( $cache );
        $cache = rtrim( $cache, "%\x00\x1F" ); // Remove trailing % and control characters
        $data = json_decode( $cache, true );
    } else {
        $data = null;
    }
    
    // Try geometry.lat/lng first (from Nearby Search API)
    if ( isset( $data['geometry']['lat'], $data['geometry']['lng'] ) ) {
        $lat = $data['geometry']['lat'];
        $lng = $data['geometry']['lng'];
        $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
    }
    // Fallback: try location.lat/lng
    elseif ( isset( $data['location']['lat'], $data['location']['lng'] ) ) {
        $lat = $data['location']['lat'];
        $lng = $data['location']['lng'];
        $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
    }
} elseif ( $post_type === 'placy_native_point' ) {
    // Native Point: coordinates field or separate lat/lng
    $coords_field = get_field( 'coordinates', $poi_id );
    
    if ( $coords_field && isset( $coords_field['latitude'], $coords_field['longitude'] ) ) {
        $lat = $coords_field['latitude'];
        $lng = $coords_field['longitude'];
        $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
    } else {
        // Fallback: separate fields
        $lat = get_field( 'latitude', $poi_id );
        $lng = get_field( 'longitude', $poi_id );
        
        if ( $lat && $lng ) {
            $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
        }
    }
}

// Get walking time if available
$walking_time = get_post_meta( $poi_id, 'walking_time', true );

// Get Entur integration data
$entur_stopplace_id = get_field( 'entur_stopplace_id', $poi_id );
$entur_quay_id = get_field( 'entur_quay_id', $poi_id );
$entur_transport_mode = get_field( 'entur_transport_mode', $poi_id );
$entur_group_by_direction = get_field( 'entur_group_by_direction', $poi_id );
$entur_line_filter = get_field( 'entur_line_filter', $poi_id );
$show_live_departures = get_field( 'show_live_departures', $poi_id );

// Get Bysykkel integration data
$bysykkel_station_id = get_field( 'bysykkel_station_id', $poi_id );
$show_bike_availability = get_field( 'show_bike_availability', $poi_id );

// Get Hyre integration data
$hyre_station_id = get_field( 'hyre_station_id', $poi_id );
$show_hyre_availability = get_field( 'show_hyre_availability', $poi_id );

// Get category icon for map marker
$category_icon = placy_get_poi_category_icon( $poi_id );
?>

<article class="poi-list-item poi-highlight p-6 mb-8 border border-gray-200 rounded-lg"
         data-poi-id="<?php echo esc_attr( $poi_id ); ?>"
         data-poi-title="<?php echo esc_attr( $title ); ?>"
         data-poi-name="<?php echo esc_attr( $display_name ); ?>"
         <?php 
            $google_place_id = get_field( 'google_place_id', $poi_id );
            if ( $google_place_id ) : 
         ?>
            data-google-place-id="<?php echo esc_attr( $google_place_id ); ?>"
         <?php 
                // Get photo reference for Google Points
                $place_data_for_photo_hl = placy_get_poi_place_data( $poi_id );
                if ( $place_data_for_photo_hl && ! empty( $place_data_for_photo_hl['photo_reference'] ) ) :
         ?>
            data-google-photo-reference="<?php echo esc_attr( $place_data_for_photo_hl['photo_reference'] ); ?>"
         <?php 
                endif;
            endif; 
         ?>
         <?php if ( $coords ) : ?>
            data-poi-coords="<?php echo esc_attr( $coords ); ?>"
         <?php endif; ?>
         data-poi-icon="<?php echo esc_attr( $category_icon['icon'] ); ?>"
         data-poi-icon-color="<?php echo esc_attr( $category_icon['color'] ); ?>"
         <?php if ( $featured_image ) : ?>
            data-poi-image="<?php echo esc_url( $featured_image ); ?>"
         <?php endif; ?>
         <?php if ( $entur_stopplace_id && $show_live_departures ) : ?>
            data-entur-stopplace-id="<?php echo esc_attr( $entur_stopplace_id ); ?>"
            data-show-live-departures="1"
            data-entur-group-by-direction="<?php echo $entur_group_by_direction ? '1' : '0'; ?>"
            <?php if ( $entur_quay_id ) : ?>
                data-entur-quay-id="<?php echo esc_attr( $entur_quay_id ); ?>"
            <?php endif; ?>
            <?php if ( $entur_transport_mode ) : ?>
                data-entur-transport-mode="<?php echo esc_attr( $entur_transport_mode ); ?>"
            <?php endif; ?>
            <?php if ( $entur_line_filter ) : ?>
                data-entur-line-filter="<?php echo esc_attr( $entur_line_filter ); ?>"
            <?php endif; ?>
         <?php endif; ?>
         <?php if ( $bysykkel_station_id && $show_bike_availability ) : ?>
            data-bysykkel-station-id="<?php echo esc_attr( $bysykkel_station_id ); ?>"
            data-show-bike-availability="1"
         <?php endif; ?>
         <?php if ( $hyre_station_id && $show_hyre_availability ) : ?>
            data-hyre-station-id="<?php echo esc_attr( $hyre_station_id ); ?>"
            data-show-hyre-availability="1"
         <?php endif; ?>>
    
    <?php if ( $featured_image && $secondary_image ) : ?>
        <!-- Two images side by side -->
        <div class="poi-highlight-images mb-6 grid grid-cols-2 gap-4">
            <div class="rounded-lg overflow-hidden">
                <img src="<?php echo esc_url( $featured_image ); ?>" 
                     alt="<?php echo esc_attr( $title ); ?>"
                     class="w-full h-64 object-cover">
            </div>
            <div class="rounded-lg overflow-hidden">
                <img src="<?php echo esc_url( $secondary_image ); ?>" 
                     alt="<?php echo esc_attr( $title ); ?>"
                     class="w-full h-64 object-cover">
            </div>
        </div>
    <?php elseif ( $featured_image ) : ?>
        <!-- Single featured image (full width) -->
        <div class="poi-highlight-image mb-6 rounded-lg overflow-hidden">
            <img src="<?php echo esc_url( $featured_image ); ?>" 
                 alt="<?php echo esc_attr( $title ); ?>"
                 class="w-full h-64 object-cover">
        </div>
    <?php elseif ( isset( $place_data_for_photo_hl ) && ! empty( $place_data_for_photo_hl['photo_reference'] ) ) : ?>
        <!-- Google Places photo if no featured image -->
        <?php 
            // Use caching proxy to reduce API calls (30-day cache)
            $photo_url_hl = rest_url( 'placy/v1/photo/proxy/' . urlencode( $place_data_for_photo_hl['photo_reference'] ) ) . '?maxwidth=1200';
        ?>
        <div class="poi-highlight-image mb-6 rounded-lg overflow-hidden">
            <img src="<?php echo esc_url( $photo_url_hl ); ?>" 
                 alt="<?php echo esc_attr( $title ); ?>"
                 class="w-full h-64 object-cover">
        </div>
    <?php endif; ?>
    
    <div class="poi-highlight-content  rounded-lg shadow-sm">
        <h2 class="text-3xl font-bold mb-4 text-gray-900">
            <?php echo esc_html( $title ); ?>
        </h2>
        
        <?php 
        // Get Google Places rating data
        $place_data = placy_get_poi_place_data( $poi_id );
        if ( $place_data && isset( $place_data['rating'] ) ) :
        ?>
            <div class="poi-rating flex items-center gap-2 mb-4">
                <?php if ( ! empty( $place_data['google_maps_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $place_data['google_maps_url'] ); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="flex items-center gap-2 hover:opacity-80 transition-opacity"
                       title="Se anmeldelser på Google"
                       aria-label="Se anmeldelser på Google">
                        <span class="poi-rating-value flex items-center gap-1 text-lg font-semibold text-gray-900">
                            <span class="text-yellow-500">★</span>
                            <?php echo esc_html( number_format( $place_data['rating'], 1 ) ); ?>
                        </span>
                        <?php if ( isset( $place_data['review_count'] ) && $place_data['review_count'] > 0 ) : ?>
                            <span class="poi-rating-count text-sm text-gray-600">
                                (<?php echo esc_html( number_format( $place_data['review_count'] ) ); ?>)
                            </span>
                        <?php endif; ?>
                        <span class="flex items-center gap-1 text-xs text-gray-400 ml-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            <span class="text-[10px] font-medium">Google</span>
                        </span>
                    </a>
                <?php else : ?>
                    <span class="poi-rating-value flex items-center gap-1 text-lg font-semibold text-gray-900">
                        <span class="text-yellow-500">★</span>
                        <?php echo esc_html( number_format( $place_data['rating'], 1 ) ); ?>
                    </span>
                    <?php if ( isset( $place_data['review_count'] ) && $place_data['review_count'] > 0 ) : ?>
                        <span class="poi-rating-count text-sm text-gray-600">
                            (<?php echo esc_html( number_format( $place_data['review_count'] ) ); ?>)
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="poi-travel-time flex items-center gap-2 mb-4 text-gray-600" style="display: none;" data-default-time="<?php echo esc_attr( $walking_time ); ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="poi-travel-time-text text-sm font-medium"></span>
        </div>
        
        <?php
        // Get contact info from place_details_cache
        $place_details_cache_hl = get_field( 'place_details_cache', $poi_id );
        $place_details_hl = ! empty( $place_details_cache_hl ) ? json_decode( $place_details_cache_hl, true ) : array();
        $has_contact_info_hl = ! empty( $place_details_hl['website'] ) || ! empty( $place_details_hl['phone'] ) || ! empty( $place_details_hl['opening_hours'] );
        
        if ( $has_contact_info_hl ) : ?>
            <div class="poi-contact-info flex flex-col gap-3 mb-6 p-4 bg-gray-50 rounded-lg">
                <?php if ( ! empty( $place_details_hl['website'] ) ) : ?>
                    <a href="<?php echo esc_url( $place_details_hl['website'] ); ?>" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <span class="font-medium">Besøk nettside</span>
                    </a>
                <?php endif; ?>
                <?php if ( ! empty( $place_details_hl['phone'] ) ) : ?>
                    <a href="tel:<?php echo esc_attr( str_replace( ' ', '', $place_details_hl['phone'] ) ); ?>" class="flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span class="font-medium"><?php echo esc_html( $place_details_hl['phone'] ); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ( ! empty( $place_details_hl['opening_hours']['weekdayDescriptions'] ) ) : ?>
                    <div class="flex items-start gap-2 text-sm text-gray-700">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <span class="font-medium block mb-1">Åpningstider:</span>
                            <?php foreach ( array_slice( $place_details_hl['opening_hours']['weekdayDescriptions'], 0, 3 ) as $day ) : ?>
                                <div class="text-xs text-gray-600"><?php echo esc_html( $day ); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="poi-highlight-text">
            <?php echo wp_kses_post( $content ); ?>
        </div>
        
        <div class="poi-button-container mt-6 flex items-center gap-2">
            <button onclick="showPOIOnMap(this)" 
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Se på kart
            </button>
        </div>
    </div>
</article>
