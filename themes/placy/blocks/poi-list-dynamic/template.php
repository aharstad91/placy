<?php
/**
 * POI List Dynamic Block Template
 * Viser Google Points fra CPT basert på filtre
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get block attributes
$places_enabled = isset( $attributes['placesEnabled'] ) ? $attributes['placesEnabled'] : true;
$places_category = isset( $attributes['placesCategory'] ) ? $attributes['placesCategory'] : 'restaurant';
$places_keyword = isset( $attributes['placesKeyword'] ) ? $attributes['placesKeyword'] : '';
$places_radius = isset( $attributes['placesRadius'] ) ? $attributes['placesRadius'] : 1500;
$places_min_rating = isset( $attributes['placesMinRating'] ) ? $attributes['placesMinRating'] : 4.3;
$places_min_reviews = isset( $attributes['placesMinReviews'] ) ? $attributes['placesMinReviews'] : 20;
$places_exclude_types = isset( $attributes['placesExcludeTypes'] ) ? $attributes['placesExcludeTypes'] : array( 'lodging' );
$max_results = isset( $attributes['maxResults'] ) ? intval( $attributes['maxResults'] ) : 10;

// Generate unique block ID
$block_id = 'poi-list-dynamic-' . wp_unique_id( 'block-' );

// Build wrapper attributes array
$wrapper_attrs = array(
    'id' => $block_id,
    'class' => 'poi-list-block poi-list-dynamic-block w-full mb-6',
);

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_attrs );

// Query Google Points CPT if enabled
$poi_items = array();
$project_id = null;
if ( $places_enabled ) {
    // Get reference point from project context
    $origin_data = function_exists( 'placy_get_project_origin_data' ) 
        ? placy_get_project_origin_data() 
        : array( 'lat' => 63.4305, 'lng' => 10.3951, 'project_id' => null );
    $reference_lat = $origin_data['lat'];
    $reference_lng = $origin_data['lng'];
    $project_id = $origin_data['project_id'];
    
    // Basic WP_Query for Google Points
    $args = array(
        'post_type' => 'placy_google_point',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'nearby_search_cache',
                'compare' => 'EXISTS',
            ),
        ),
    );
    
    // Add category filter if specified
    if ( ! empty( $places_category ) && $places_category !== 'all' ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'placy_categories',
                'field' => 'slug',
                'terms' => $places_category,
            ),
        );
    }
    
    $query = new WP_Query( $args );
    
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Get cached data
            $nearby_search_cache = get_field( 'nearby_search_cache', $post_id );
            if ( empty( $nearby_search_cache ) ) {
                continue;
            }
            
            $place_data = json_decode( $nearby_search_cache, true );
            if ( ! $place_data ) {
                continue;
            }
            
            // Filter by rating
            if ( isset( $place_data['rating'] ) && $place_data['rating'] < $places_min_rating ) {
                continue;
            }
            
            // Filter by review count
            if ( isset( $place_data['user_ratings_total'] ) && $place_data['user_ratings_total'] < $places_min_reviews ) {
                continue;
            }
            
            // Calculate distance
            if ( isset( $place_data['geometry']['lat'] ) && isset( $place_data['geometry']['lng'] ) ) {
                $place_lat = $place_data['geometry']['lat'];
                $place_lng = $place_data['geometry']['lng'];
                $distance = placy_calculate_distance( $reference_lat, $reference_lng, $place_lat, $place_lng );
                
                // Filter by radius
                if ( $distance > $places_radius ) {
                    continue;
                }
            } else {
                continue;
            }
            
            // Filter by keyword (search in name, types, and vicinity)
            if ( ! empty( $places_keyword ) ) {
                $keyword_lower = strtolower( $places_keyword );
                $found = false;
                
                // Search in name
                if ( isset( $place_data['name'] ) && stripos( $place_data['name'], $places_keyword ) !== false ) {
                    $found = true;
                }
                
                // Search in types array
                if ( ! $found && isset( $place_data['types'] ) && is_array( $place_data['types'] ) ) {
                    foreach ( $place_data['types'] as $type ) {
                        if ( stripos( $type, $places_keyword ) !== false ) {
                            $found = true;
                            break;
                        }
                    }
                }
                
                // Search in vicinity/address
                if ( ! $found && isset( $place_data['vicinity'] ) && stripos( $place_data['vicinity'], $places_keyword ) !== false ) {
                    $found = true;
                }
                
                if ( ! $found ) {
                    continue;
                }
            }
            
            // Filter by excluded types
            if ( ! empty( $places_exclude_types ) && isset( $place_data['types'] ) && is_array( $place_data['types'] ) ) {
                $has_excluded = false;
                foreach ( $places_exclude_types as $excluded_type ) {
                    if ( in_array( $excluded_type, $place_data['types'] ) ) {
                        $has_excluded = true;
                        break;
                    }
                }
                if ( $has_excluded ) {
                    continue;
                }
            }
            
            // Add to results
            $poi_items[] = get_post( $post_id );
            
            // Check if we've reached the max results limit
            if ( count( $poi_items ) >= $max_results ) {
                break;
            }
        }
        wp_reset_postdata();
    }
}

?>

<div <?php echo $wrapper_attributes; ?>>
    <?php if ( $places_enabled ) : ?>
        <?php if ( ! empty( $poi_items ) ) : ?>
            <!-- POI results container - initially hidden, revealed by button click -->
            <div class="poi-list-dynamic-results flex flex-col" style="display: none;">
                <?php foreach ( $poi_items as $poi ) : 
                    // Get POI coordinates (works for both Native and Google Points)
                    $poi_coords = placy_get_poi_coordinates( $poi->ID );
                    $coords = '';
                    $lat = null;
                    $lng = null;
                    $travel_times = null;
                    
                    if ( $poi_coords ) {
                        $lat = $poi_coords['lat'];
                        $lng = $poi_coords['lng'];
                        $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
                        
                        // Pre-calculate travel times for all modes
                        if ( function_exists( 'placy_calculate_travel_times' ) ) {
                            $travel_times = placy_calculate_travel_times( $reference_lat, $reference_lng, $lat, $lng, $poi->ID, $project_id );
                        }
                    }
                    
                    // Get featured image URL
                    $featured_image = get_the_post_thumbnail_url( $poi->ID, 'large' );
                    
                    // Get category icon for map marker
                    $category_icon = placy_get_poi_category_icon( $poi->ID );
                    
                    // Get description - check editorial_text, then ACF description field (Native Points), then post_content
                    $editorial_text = get_field( 'editorial_text', $poi->ID );
                    $acf_description = get_field( 'description', $poi->ID );
                    $post_content = get_post_field( 'post_content', $poi->ID );
                    
                    if ( $editorial_text ) {
                        $content = $editorial_text;
                    } elseif ( $acf_description ) {
                        $content = $acf_description;
                    } elseif ( $post_content ) {
                        $content = apply_filters( 'the_content', $post_content );
                    } else {
                        $content = '';
                    }
                    $excerpt = get_the_excerpt( $poi->ID );
                ?>
                    <article 
                        class="poi-list-card bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:border-gray-300 mb-4"
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
                        <?php if ( $travel_times ) : ?>
                            data-travel-times='<?php echo esc_attr( wp_json_encode( $travel_times ) ); ?>'
                        <?php endif; ?>
                        data-poi-icon="<?php echo esc_attr( $category_icon['icon'] ); ?>"
                        data-poi-icon-color="<?php echo esc_attr( $category_icon['color'] ); ?>"
                        <?php if ( $featured_image ) : ?>
                            data-poi-image="<?php echo esc_url( $featured_image ); ?>"
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
                                                <span class="poi-travel-icon"><i class="fas fa-walking"></i></span>
                                                <span class="poi-walking-time text-sm font-medium">
                                                    <?php 
                                                    if ( $travel_times ) {
                                                        echo esc_html( $travel_times['walk'] ) . ' min';
                                                    } else {
                                                        echo 'Beregner...';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php
                                    // Get contact info from place_details_cache
                                    $place_details_cache_dyn = get_field( 'place_details_cache', $poi->ID );
                                    $place_details_dyn = ! empty( $place_details_cache_dyn ) ? json_decode( $place_details_cache_dyn, true ) : array();
                                    $has_contact_info_dyn = ! empty( $place_details_dyn['website'] ) || ! empty( $place_details_dyn['phone'] );
                                    
                                    if ( $has_contact_info_dyn ) : ?>
                                        <div class="poi-contact-info flex items-center gap-4 mt-2 text-xs text-gray-600">
                                            <?php if ( ! empty( $place_details_dyn['website'] ) ) : ?>
                                                <a href="<?php echo esc_url( $place_details_dyn['website'] ); ?>" target="_blank" rel="noopener" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                                    </svg>
                                                    <span>Nettside</span>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $place_details_dyn['phone'] ) ) : ?>
                                                <a href="tel:<?php echo esc_attr( str_replace( ' ', '', $place_details_dyn['phone'] ) ); ?>" class="flex items-center gap-1 hover:text-blue-600 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                    <span><?php echo esc_html( $place_details_dyn['phone'] ); ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
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
                                <!-- Bottom row: Tekst -->
                                <?php if ( $content ) : ?>
                                    <div class="poi-description text-sm text-gray-600">
                                        <?php echo wp_kses_post( $content ); ?>
                                    </div>
                                <?php elseif ( $excerpt ) : ?>
                                    <div class="poi-description text-sm text-gray-600">
                                        <?php echo esc_html( $excerpt ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Button to reveal results with loading animation -->
            <div class="places-api-button-container" style="margin-top: 24px; text-align: center;">
                <button 
                    class="places-api-show-all-button" 
                    data-category="<?php echo esc_attr( $places_category ); ?>"
                    data-category-norwegian="<?php 
                        // Convert category to Norwegian
                        $category_norwegian_map = array(
                            'restaurant' => 'restauranter',
                            'cafe' => 'kafeer',
                            'bar' => 'barer',
                            'bakery' => 'bakerier',
                            'food' => 'spisesteder',
                        );
                        echo esc_attr( isset( $category_norwegian_map[ $places_category ] ) ? $category_norwegian_map[ $places_category ] : 'steder' );
                    ?>"
                    style="padding: 12px 24px; background-color: #ef4444; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; opacity: 1;">
                    Se flere <?php 
                        // Display Norwegian category
                        $category_norwegian_map = array(
                            'restaurant' => 'restauranter',
                            'cafe' => 'kafeer',
                            'bar' => 'barer',
                            'bakery' => 'bakerier',
                            'food' => 'spisesteder',
                        );
                        echo isset( $category_norwegian_map[ $places_category ] ) ? $category_norwegian_map[ $places_category ] : 'steder';
                    ?> i området
                </button>
            </div>
        <?php else : ?>
            <!-- No results found - still show button that will display message -->
            <div class="poi-list-dynamic-results flex flex-col" style="display: none;">
                <p class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <?php 
                        if ( ! empty( $places_keyword ) ) {
                            printf( 
                                __( 'Ingen steder funnet som matcher "%s" innenfor %d meter.', 'placy' ),
                                esc_html( $places_keyword ),
                                esc_html( $places_radius )
                            );
                        } else {
                            _e( 'Ingen steder funnet som matcher kriteriene.', 'placy' );
                        }
                    ?>
                </p>
            </div>
            
            <div class="places-api-button-container" style="margin-top: 24px; text-align: center;">
                <button 
                    class="places-api-show-all-button" 
                    data-category="<?php echo esc_attr( $places_category ); ?>"
                    data-category-norwegian="<?php 
                        $category_norwegian_map = array(
                            'restaurant' => 'restauranter',
                            'cafe' => 'kafeer',
                            'bar' => 'barer',
                            'bakery' => 'bakerier',
                            'food' => 'spisesteder',
                        );
                        echo esc_attr( isset( $category_norwegian_map[ $places_category ] ) ? $category_norwegian_map[ $places_category ] : 'steder' );
                    ?>"
                    style="padding: 12px 24px; background-color: #ef4444; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; opacity: 1;">
                    Se flere <?php 
                        $category_norwegian_map = array(
                            'restaurant' => 'restauranter',
                            'cafe' => 'kafeer',
                            'bar' => 'barer',
                            'bakery' => 'bakerier',
                            'food' => 'spisesteder',
                        );
                        echo isset( $category_norwegian_map[ $places_category ] ) ? $category_norwegian_map[ $places_category ] : 'steder';
                    ?> i området
                </button>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <p class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <?php _e( 'Dynamisk POI-liste er deaktivert for denne blokken.', 'placy' ); ?>
        </p>
    <?php endif; ?>
</div>
