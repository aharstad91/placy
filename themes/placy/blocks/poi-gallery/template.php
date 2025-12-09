<?php
/**
 * POI Gallery Block Template
 * 
 * Displays multiple POIs in a grid layout
 */

// Get category filter
$filter_category = get_field('filter_category');

// Get the selected POIs
$pois = get_field('poi_items');

// Apply category filter if set
if ( $filter_category && $pois ) {
    $pois = array_filter( $pois, function( $poi ) use ( $filter_category ) {
        return has_term( $filter_category, 'placy_categories', $poi->ID );
    });
}

if ( ! $pois || empty( $pois ) ) {
    echo '<p style="padding: 20px; background: #f0f0f0; text-align: center;">Please select POIs in block settings</p>';
    return;
}
?>

<div class="poi-gallery grid grid-cols-2 gap-6 mb-8">
    <?php foreach ( $pois as $poi ) : 
        $poi_id = $poi->ID;
        $title = get_the_title( $poi_id );
        
        // Get description - check editorial_text, then ACF description field (Native Points), then post_content
        $editorial_text = get_field( 'editorial_text', $poi_id );
        $acf_description = get_field( 'description', $poi_id );
        $post_content = get_post_field( 'post_content', $poi_id );
        
        if ( $editorial_text ) {
            $content = $editorial_text;
        } elseif ( $acf_description ) {
            $content = $acf_description;
        } elseif ( $post_content ) {
            $content = apply_filters( 'the_content', $post_content );
        } else {
            $content = '';
        }
        
        $featured_image = get_the_post_thumbnail_url( $poi_id, 'medium_large' );
        
        // Get coordinates for map syncing (works for both Native and Google Points)
        $poi_coords = placy_get_poi_coordinates( $poi_id );
        $coords = '';
        $lat = null;
        $lng = null;
        
        if ( $poi_coords ) {
            $lat = $poi_coords['lat'];
            $lng = $poi_coords['lng'];
            $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
        }
        
        // Get category icon for map marker
        $category_icon = placy_get_poi_category_icon( $poi_id );
        
        // Get walking time if available
        $walking_time = get_post_meta( $poi_id, 'walking_time', true );
        
        // Get Entur integration data
        $entur_stopplace_id = get_field( 'entur_stopplace_id', $poi_id );
        $entur_quay_id = get_field( 'entur_quay_id', $poi_id );
        $entur_transport_mode = get_field( 'entur_transport_mode', $poi_id );
        $entur_line_filter = get_field( 'entur_line_filter', $poi_id );
        $show_live_departures = get_field( 'show_live_departures', $poi_id );
        
        // Get Bysykkel integration data
        $bysykkel_station_id = get_field( 'bysykkel_station_id', $poi_id );
        $show_bike_availability = get_field( 'show_bike_availability', $poi_id );
    ?>
    
    <article class="poi-list-item poi-gallery-item overflow-hidden border-gray-200 border rounded-lg" 
             data-poi-id="<?php echo esc_attr( $poi_id ); ?>"
             data-poi-title="<?php echo esc_attr( $title ); ?>"
             <?php 
                $google_place_id = get_field( 'google_place_id', $poi_id );
                if ( $google_place_id ) : 
             ?>
                data-google-place-id="<?php echo esc_attr( $google_place_id ); ?>"
             <?php 
                    // Get photo reference for Google Points
                    $place_data = placy_get_poi_place_data( $poi_id );
                    if ( $place_data && ! empty( $place_data['photo_reference'] ) ) :
             ?>
                data-google-photo-reference="<?php echo esc_attr( $place_data['photo_reference'] ); ?>"
             <?php 
                    endif;
                endif; 
             ?>
             <?php if ( $coords ) : ?>
                data-poi-coords="<?php echo $coords; ?>"
             <?php endif; ?>
             data-poi-icon="<?php echo esc_attr( $category_icon['icon'] ); ?>"
             data-poi-icon-color="<?php echo esc_attr( $category_icon['color'] ); ?>"
             <?php if ( $featured_image ) : ?>
                data-poi-image="<?php echo esc_url( $featured_image ); ?>"
             <?php endif; ?>
             <?php if ( $entur_stopplace_id && $show_live_departures ) : ?>
                data-entur-stopplace-id="<?php echo esc_attr( $entur_stopplace_id ); ?>"
                data-show-live-departures="1"
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
                >
        <?php if ( $featured_image ) : ?>
            <div class="poi-gallery-image">
                <img src="<?php echo esc_url( $featured_image ); ?>" 
                     alt="<?php echo esc_attr( $title ); ?>"
                     class="w-full h-48 object-cover">
            </div>
        <?php elseif ( isset( $place_data ) && ! empty( $place_data['photo_reference'] ) ) : ?>
            <?php 
                // Show Google Places photo if no featured image
                $photo_url = 'https://places.googleapis.com/v1/' . $place_data['photo_reference'] . '/media?maxWidthPx=800&key=' . ( defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '' );
            ?>
            <div class="poi-gallery-image">
                <img src="<?php echo esc_url( $photo_url ); ?>" 
                     alt="<?php echo esc_attr( $title ); ?>"
                     class="w-full h-48 object-cover">
            </div>
        <?php endif; ?>
        
        <div class="poi-gallery-content p-6">
            
            <!-- POI Icon and Title Row -->
            <div class="flex items-start gap-3 mb-3">
                <?php if ( ! empty( $category_icon['icon'] ) ) : ?>
                    <div class="poi-card-icon flex-shrink-0 flex items-center justify-center" 
                         style="width: 48px; height: 48px; border-radius: 5px; background-color: <?php echo esc_attr( $category_icon['color'] ); ?>;">
                        <i class="fa-solid <?php echo esc_attr( $category_icon['icon'] ); ?>" style="color: white; font-size: 18px;"></i>
                    </div>
                <?php endif; ?>
                
                <div class="flex-1 min-w-0">
                    <h3 class="text-xl font-bold text-gray-900 leading-tight">
                        <?php echo esc_html( $title ); ?>
                    </h3>
                    
                    <?php 
                    // Get point_type terms
                    $point_types = get_the_terms( $poi_id, 'point_type' );
                    if ( $point_types && ! is_wp_error( $point_types ) ) :
                    ?>
                        <div class="flex items-center gap-2 mt-1">
                            <?php foreach ( $point_types as $term ) : ?>
                                <span class="inline-block px-2 py-0.5 text-xs font-medium text-gray-500 bg-gray-100 rounded">
                                    <?php echo esc_html( $term->name ); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="flex items-center justify-between gap-4 mb-3">
                <?php 
                // Get Google Places rating data
                $place_data = placy_get_poi_place_data( $poi_id );
                if ( $place_data && isset( $place_data['rating'] ) ) :
                ?>
                    <div class="poi-rating flex items-center gap-2">
                        <?php if ( ! empty( $place_data['google_maps_url'] ) ) : ?>
                            <a href="<?php echo esc_url( $place_data['google_maps_url'] ); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="flex items-center gap-2 hover:opacity-80 transition-opacity"
                               title="Se anmeldelser på Google"
                               aria-label="Se anmeldelser på Google">
                                <span class="poi-rating-value flex items-center gap-1 text-sm font-medium text-gray-900">
                                    <span class="text-yellow-500">★</span>
                                    <?php echo number_format( $place_data['rating'], 1 ); ?>
                                </span>
                                <?php if ( isset( $place_data['review_count'] ) && $place_data['review_count'] > 0 ) : ?>
                                    <span class="poi-rating-count text-xs text-gray-500">
                                        (<?php echo number_format( $place_data['review_count'] ); ?>)
                                    </span>
                                <?php endif; ?>
                                <span class="flex items-center gap-1 text-xs text-gray-400">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="FBBC05"/>
                                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                    </svg>
                                    <span class="text-[10px]">Google</span>
                                </span>
                            </a>
                        <?php else : ?>
                            <span class="poi-rating-value flex items-center gap-1 text-sm font-medium text-gray-900">
                                <span class="text-yellow-500">★</span>
                                <?php echo number_format( $place_data['rating'], 1 ); ?>
                            </span>
                            <?php if ( isset( $place_data['review_count'] ) && $place_data['review_count'] > 0 ) : ?>
                                <span class="poi-rating-count text-xs text-gray-500">
                                    (<?php echo number_format( $place_data['review_count'] ); ?>)
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div></div>
                <?php endif; ?>
                
                <div class="flex items-center gap-2 text-gray-600">
                    <span class="poi-travel-icon"><i class="fas fa-walking"></i></span>
                    <span class="text-sm font-medium poi-walking-time">
                        <?php if ( $walking_time ) : ?>
                            <?php echo esc_html( $walking_time ); ?> min
                        <?php else : ?>
                            Beregner...
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <?php
            // Get contact info from place_details_cache
            $place_details_cache_gal = get_field( 'place_details_cache', $poi->ID );
            $place_details_gal = ! empty( $place_details_cache_gal ) ? json_decode( $place_details_cache_gal, true ) : array();
            $has_contact_info_gal = ! empty( $place_details_gal['website'] ) || ! empty( $place_details_gal['phone'] );
            
            if ( $has_contact_info_gal ) : ?>
                <div class="poi-contact-info flex items-center gap-3 mb-3 text-xs text-gray-600">
                    <?php if ( ! empty( $place_details_gal['website'] ) ) : ?>
                        <a href="<?php echo esc_url( $place_details_gal['website'] ); ?>" target="_blank" rel="noopener" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <span>Nettside</span>
                        </a>
                    <?php endif; ?>
                    <?php if ( ! empty( $place_details_gal['phone'] ) ) : ?>
                        <a href="tel:<?php echo esc_attr( str_replace( ' ', '', $place_details_gal['phone'] ) ); ?>" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span><?php echo esc_html( $place_details_gal['phone'] ); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="poi-gallery-text prose prose-sm max-w-none mb-4 line-clamp-3">
                <?php echo wp_kses_post( $content ); ?>
            </div>
            
            <div class="poi-button-container flex items-center gap-2 flex-wrap">
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
    
    <?php endforeach; ?>
</div>
