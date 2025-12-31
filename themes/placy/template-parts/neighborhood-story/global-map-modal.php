<?php
/**
 * Neighborhood Story - Global Map Modal
 * 
 * Full-screen modal that displays ALL POIs from all chapters.
 * Includes:
 * - Category filters
 * - Travel Mode & Time Budget controls
 * - Search functionality
 * - Full list of all locations with chapter badges
 * - Map with all points
 *
 * @package Placy
 * @since 1.0.0
 * 
 * @param int $project_id Project post ID
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$project_id = $project_id ?? get_the_ID();

// Get all chapters
$chapters = get_field( 'story_chapters', $project_id );

// Aggregate all points from all chapters
$all_points = array();
$chapter_map = array(); // To track which chapter each point belongs to

if ( $chapters ) {
    foreach ( $chapters as $chapter_index => $chapter ) {
        $theme_story = $chapter['theme_story'];
        if ( ! $theme_story ) continue;
        
        $chapter_title = $chapter['front_title'] ?: get_the_title( $theme_story );
        $chapter_anchor = $chapter['anchor_id'] ?? 'chapter-' . $chapter_index;
        
        $locations = get_field( 'all_locations', $theme_story->ID );
        if ( ! $locations ) continue;
        
        foreach ( $locations as $point ) {
            $point_id = is_object( $point ) ? $point->ID : $point;
            
            // Skip duplicates
            if ( isset( $chapter_map[ $point_id ] ) ) continue;
            
            $chapter_map[ $point_id ] = array(
                'title'  => $chapter_title,
                'anchor' => $chapter_anchor,
            );
            
            $all_points[] = $point;
        }
    }
}

$total_count = count( $all_points );

// Get global settings
$default_travel_mode = get_field( 'default_travel_mode', $project_id ) ?: 'walk';
$default_time_budget = get_field( 'default_time_budget', $project_id ) ?: '10';

// Define filter categories
$filter_categories = array(
    array( 'label' => 'All', 'value' => 'all' ),
    array( 'label' => 'Food & coffee', 'values' => array( 'bakery', 'restaurant', 'food', 'bar', 'coffee', 'cafe' ) ),
    array( 'label' => 'Services', 'values' => array( 'shopping', 'groceries', 'gym', 'service' ) ),
    array( 'label' => 'Commute', 'values' => array( 'train', 'bus', 'bike', 'boat', 'car', 'transport' ) ),
    array( 'label' => 'Breaks', 'values' => array( 'nature', 'park', 'walks' ) ),
    array( 'label' => 'Meetings', 'values' => array( 'hotel', 'venue', 'meeting' ) ),
    array( 'label' => 'After work', 'values' => array( 'culture', 'nightlife', 'entertainment' ) ),
);

// Prepare all points data for map
$map_points = array();
foreach ( $all_points as $point ) {
    $point_id = is_object( $point ) ? $point->ID : $point;
    $lat = get_field( 'latitude', $point_id ) ?: get_post_meta( $point_id, 'latitude', true );
    $lng = get_field( 'longitude', $point_id ) ?: get_post_meta( $point_id, 'longitude', true );
    
    if ( $lat && $lng ) {
        $point_type = get_post_type( $point_id );
        $category = '';
        if ( $point_type === 'placy_google_point' ) {
            $category = get_field( 'type_label', $point_id ) ?: get_field( 'type', $point_id );
        } else {
            $terms = get_the_terms( $point_id, 'poi_category' );
            $category = $terms ? $terms[0]->name : '';
        }
        
        $map_points[] = array(
            'id'        => $point_id,
            'name'      => get_the_title( $point_id ),
            'latitude'  => (float) $lat,
            'longitude' => (float) $lng,
            'category'  => $category,
            'chapterId' => $chapter_map[ $point_id ]['anchor'] ?? '',
        );
    }
}
?>

<div 
    class="ns-modal ns-global-map" 
    role="dialog"
    aria-modal="true"
    aria-labelledby="global-map-title"
    aria-hidden="true"
>
    <!-- Top Bar -->
    <div class="ns-modal-topbar ns-modal-topbar-dark">
        <span class="ns-modal-brand">NEIGHBORHOOD STORY</span>
        <button type="button" class="ns-modal-close" data-close-modal aria-label="Close map">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Modal Content -->
    <div class="ns-modal-content ns-global-map-content">
        <!-- Left Panel: Filters & List -->
        <div class="ns-modal-panel ns-modal-panel-left ns-global-map-list">
            <!-- Header -->
            <div class="ns-modal-header">
                <div class="ns-modal-header-row">
                    <h2 id="global-map-title" class="ns-modal-title">All places</h2>
                    <span class="ns-result-count"><?php echo esc_html( $total_count ); ?> results</span>
                </div>

                <!-- Category Filters -->
                <div class="ns-filter-chips">
                    <?php foreach ( $filter_categories as $filter ) : ?>
                        <button 
                            type="button" 
                            class="ns-filter-chip <?php echo $filter['value'] === 'all' ? 'active' : ''; ?>"
                            data-filter="<?php echo esc_attr( $filter['value'] ?? $filter['label'] ); ?>"
                            data-filter-values='<?php echo isset( $filter['values'] ) ? esc_attr( wp_json_encode( $filter['values'] ) ) : ''; ?>'
                        >
                            <?php echo esc_html( $filter['label'] ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Controls -->
            <div class="ns-modal-controls ns-global-map-controls">
                <?php 
                // Shared Travel Controls Component
                get_template_part( 'template-parts/components/travel-controls', null, array(
                    'default_mode' => $default_travel_mode,
                    'default_time' => $default_time_budget,
                    'context'      => 'modal',
                ) );
                ?>

                <!-- Search -->
                <div class="ns-control-section ns-search-section">
                    <label class="ns-control-label">Search</label>
                    <div class="ns-search-input-wrapper">
                        <svg class="ns-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input 
                            type="search" 
                            class="ns-search-input" 
                            placeholder="Search places..."
                            data-search-input
                        />
                    </div>
                </div>
            </div>

            <!-- All Locations List -->
            <div class="ns-locations-section ns-global-locations" data-poi-container>
                <?php if ( $all_points ) : ?>
                    <div class="ns-locations-list">
                        <?php foreach ( $all_points as $point ) : 
                            $point_id = is_object( $point ) ? $point->ID : $point;
                            $point_title = get_the_title( $point_id );
                            $point_type = get_post_type( $point_id );
                            
                            // Get category
                            $point_category = '';
                            if ( $point_type === 'placy_google_point' ) {
                                $point_category = get_field( 'type_label', $point_id ) ?: get_field( 'type', $point_id );
                            } else {
                                $terms = get_the_terms( $point_id, 'poi_category' );
                                $point_category = $terms ? $terms[0]->name : '';
                            }
                            
                            // Get chapter info
                            $chapter_info = $chapter_map[ $point_id ] ?? null;
                            
                            // Get travel times
                            $walk_time = (int) ( get_field( 'cached_walk_time', $point_id ) ?: get_post_meta( $point_id, 'cached_walk_time', true ) ?: 5 );
                            $bike_time = (int) ( get_field( 'cached_bike_time', $point_id ) ?: get_post_meta( $point_id, 'cached_bike_time', true ) ?: 2 );
                            $drive_time = (int) ( get_field( 'cached_drive_time', $point_id ) ?: get_post_meta( $point_id, 'cached_drive_time', true ) ?: 3 );
                            
                            $travel_times = array(
                                'walk' => $walk_time,
                                'bike' => $bike_time,
                                'car'  => $drive_time,
                            );
                            $times_json = wp_json_encode( $travel_times );
                            
                            // Check for API integrations
                            $api_integrations = get_field( 'api_integrations', $point_id ) ?: array();
                            $has_entur = ( in_array( 'entur', $api_integrations, true ) && get_field( 'show_live_departures', $point_id ) ) 
                                      || ( get_field( 'entur_stopplace_id', $point_id ) && get_field( 'show_live_departures', $point_id ) );
                            $has_bysykkel = ( in_array( 'bysykkel', $api_integrations, true ) && get_field( 'show_bike_availability', $point_id ) )
                                         || ( get_field( 'bysykkel_station_id', $point_id ) && get_field( 'show_bike_availability', $point_id ) );
                            $has_hyre = ( in_array( 'hyre', $api_integrations, true ) && get_field( 'show_hyre_availability', $point_id ) )
                                     || ( get_field( 'hyre_station_id', $point_id ) && get_field( 'show_hyre_availability', $point_id ) );
                            $has_api = $has_entur || $has_bysykkel || $has_hyre;
                        ?>
                            <?php if ( $has_api ) : ?>
                                <!-- API Accordion Card -->
                                <?php 
                                get_template_part( 'template-parts/components/api-accordion-card', null, array(
                                    'point_id'     => $point_id,
                                    'context'      => 'global-map',
                                    'travel_times' => $travel_times,
                                    'travel_mode'  => $default_travel_mode,
                                ) );
                                ?>
                            <?php else : ?>
                                <!-- Standard Location Item -->
                                <div 
                                    class="ns-location-item ns-global-location-item"
                                    data-poi-times='<?php echo esc_attr( $times_json ); ?>'
                                    data-poi-id="<?php echo esc_attr( $point_id ); ?>"
                                    data-poi-name="<?php echo esc_attr( strtolower( $point_title ) ); ?>"
                                    data-poi-category="<?php echo esc_attr( strtolower( $point_category ) ); ?>"
                                    data-chapter="<?php echo esc_attr( $chapter_info['anchor'] ?? '' ); ?>"
                                >
                                    <div class="ns-location-icon">
                                        <span class="icon-location"></span>
                                    </div>
                                    <div class="ns-location-content">
                                        <div class="ns-location-header">
                                            <h4 class="ns-location-title"><?php echo esc_html( $point_title ); ?></h4>
                                            <?php if ( $chapter_info ) : ?>
                                                <span class="ns-location-chapter-badge">
                                                    <?php echo esc_html( strtoupper( $chapter_info['title'] ) ); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="ns-location-category"><?php echo esc_html( $point_category ); ?></p>
                                    </div>
                                    <div class="ns-location-time">
                                        <span class="ns-poi-time"><?php echo esc_html( $walk_time ); ?> min walk</span>
                                    </div>
                                    <button 
                                        type="button" 
                                        class="ns-view-in-story"
                                        data-open-chapter="<?php echo esc_attr( $chapter_info['anchor'] ?? '' ); ?>"
                                        <?php if ( ! $chapter_info ) echo 'disabled'; ?>
                                    >
                                        View in story
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="ns-empty-message">No locations found.</p>
                <?php endif; ?>
            </div>

            <!-- Footer: All story places count -->
            <div class="ns-global-map-footer">
                <span class="ns-footer-dot"></span>
                <span>All story places</span>
                <span class="ns-footer-count"><?php echo esc_html( $total_count ); ?></span>
            </div>
        </div>

        <!-- Right Panel: Map -->
        <div class="ns-modal-panel ns-modal-panel-right ns-global-map-container"
             data-map-points='<?php echo esc_attr( wp_json_encode( $map_points ) ); ?>'
        >
            <!-- Map will be initialized by JavaScript -->
            <div class="ns-map-placeholder">
                <span>MAP</span>
            </div>
        </div>
    </div>
</div>
