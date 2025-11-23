<?php
/**
 * POI List Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get the POI items from the relationship field
$poi_items = get_field( 'poi_items' );

// Get block wrapper attributes
$block_id = 'poi-list-' . $block['id'];
$class_name = 'poi-list-block';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}

if ( ! empty( $block['align'] ) ) {
    $class_name .= ' align' . $block['align'];
}

// Check if this block is inside a chapter section
$parent_classes = isset( $block['context']['groupClassName'] ) ? $block['context']['groupClassName'] : '';
$is_in_chapter = strpos( $parent_classes, 'chapter' ) !== false;

?>

<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $class_name ); ?> w-full mb-6">
    <?php if ( $poi_items && is_array( $poi_items ) ) : ?>
        <div class="flex flex-col" <?php if ( $is_in_chapter ) echo 'data-chapter-poi-list="true"'; ?>>
            <?php foreach ( $poi_items as $poi ) : 
                // Get POI coordinates (works for both Native and Google Points)
                $poi_coords = placy_get_poi_coordinates( $poi->ID );
                $coords = '';
                $lat = null;
                $lng = null;
                
                if ( $poi_coords ) {
                    $lat = $poi_coords['lat'];
                    $lng = $poi_coords['lng'];
                    $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
                }
                
                // Get featured image URL - landscape format for card top
                $featured_image = get_the_post_thumbnail_url( $poi->ID, 'large' );
                
                // Get editorial text if available, otherwise use post content
                $editorial_text = get_field( 'editorial_text', $poi->ID );
                $content = $editorial_text ? $editorial_text : apply_filters( 'the_content', get_post_field( 'post_content', $poi->ID ) );
                $excerpt = get_the_excerpt( $poi->ID );
                
                // Get Entur integration data
                $entur_stopplace_id = get_field( 'entur_stopplace_id', $poi->ID );
                $entur_quay_id = get_field( 'entur_quay_id', $poi->ID );
                $entur_transport_mode = get_field( 'entur_transport_mode', $poi->ID );
                $show_live_departures = get_field( 'show_live_departures', $poi->ID );
                
                // Get Bysykkel integration data
                $bysykkel_station_id = get_field( 'bysykkel_station_id', $poi->ID );
                $show_bike_availability = get_field( 'show_bike_availability', $poi->ID );
            ?>
                <article 
                    class="poi-list-card bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:border-gray-300"
                    data-poi-id="<?php echo esc_attr( $poi->ID ); ?>"
                    data-poi-title="<?php echo esc_attr( get_the_title( $poi->ID ) ); ?>"
                    <?php 
                        $google_place_id = get_field( 'google_place_id', $poi->ID );
                        if ( $google_place_id ) : 
                    ?>
                        data-google-place-id="<?php echo esc_attr( $google_place_id ); ?>"
                    <?php 
                            // Get photo reference for Google Points
                            $place_data_for_photo = placy_get_poi_place_data( $poi->ID );
                            if ( $place_data_for_photo && ! empty( $place_data_for_photo['photo_reference'] ) ) :
                    ?>
                        data-google-photo-reference="<?php echo esc_attr( $place_data_for_photo['photo_reference'] ); ?>"
                    <?php 
                            endif;
                        endif; 
                    ?>
                    <?php if ( $coords ) : ?>
                        data-poi-coords="<?php echo $coords; ?>"
                    <?php endif; ?>
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
                    <?php endif; ?>
                    <?php if ( $bysykkel_station_id && $show_bike_availability ) : ?>
                        data-bysykkel-station-id="<?php echo esc_attr( $bysykkel_station_id ); ?>"
                        data-show-bike-availability="1"
                    <?php endif; ?>
                >
                    <div class="poi-card-content flex gap-4 p-4">
                        <?php if ( $featured_image ) : ?>
                            <div class="flex-shrink-0">
                                <img src="<?php echo esc_url( $featured_image ); ?>" 
                                     alt="<?php echo esc_attr( get_the_title( $poi->ID ) ); ?>"
                                     class="w-24 h-24 object-cover rounded-lg">
                            </div>
                        <?php elseif ( isset( $place_data_for_photo ) && ! empty( $place_data_for_photo['photo_reference'] ) ) : ?>
                            <?php 
                                // Show Google Places photo if no featured image
                                $photo_url_list = 'https://places.googleapis.com/v1/' . $place_data_for_photo['photo_reference'] . '/media?maxWidthPx=400&key=' . ( defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '' );
                            ?>
                            <div class="flex-shrink-0">
                                <img src="<?php echo esc_url( $photo_url_list ); ?>" 
                                     alt="<?php echo esc_attr( get_the_title( $poi->ID ) ); ?>"
                                     class="w-24 h-24 object-cover rounded-lg">
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-1 min-w-0 flex flex-col">
                            <div class="flex items-start justify-between">
                                <!-- Title row -->
                                <div class="flex flex-col mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?php echo esc_html( get_the_title( $poi->ID ) ); ?>
                                    </h3>
                                    <?php 
                                    // Get point_type terms
                                    $point_types = get_the_terms( $poi->ID, 'point_type' );
                                    if ( $point_types && ! is_wp_error( $point_types ) ) :
                                    ?>
                                        <div class="flex items-center gap-2 mt-1 mb-2">
                                            <?php foreach ( $point_types as $term ) : ?>
                                                <span class="inline-block px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded">
                                                    <?php echo esc_html( $term->name ); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <!-- Google rating and walking time row -->
                                    <div class="flex items-center gap-4">
                                        <?php 
                                        // Get Google Places rating data
                                        $place_data = placy_get_poi_place_data( $poi->ID );
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
                                                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
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
                                        <?php endif; ?>
                                        
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <span class="poi-walking-time text-sm font-medium">
                                                Beregner...
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="poi-button-container flex-shrink-0">
                                    <button 
                                        class="poi-show-on-map inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors whitespace-nowrap"
                                        onclick="showPOIOnMap(this)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Se på kart
                                    </button>
                                </div>
                            </div>
                            <!-- Bottom row: Tekst kommer her -->
                            <?php if ( $content ) : ?>
                                <div class="poi-description text-sm text-gray-600 line-clamp-2">
                                    <?php echo wp_kses_post( $content ); ?>
                                </div>
                            <?php elseif ( $excerpt ) : ?>
                                <div class="poi-description text-sm text-gray-600 line-clamp-2">
                                    <?php echo esc_html( $excerpt ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <?php _e( 'Ingen POIs valgt. Vennligst legg til POIs i blokkinnstillingene.', 'placy' ); ?>
        </p>
    <?php endif; ?>
</div>
