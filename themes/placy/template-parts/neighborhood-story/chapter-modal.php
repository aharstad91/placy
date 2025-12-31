<?php
/**
 * Neighborhood Story - Chapter Modal
 * 
 * Full-screen modal that displays the theme-story content.
 * Includes:
 * - Header with title and close button
 * - Travel Mode & Time Budget controls (synced globally)
 * - Search functionality
 * - Theme intro and key takeaways
 * - All locations list
 * - Map panel
 *
 * @package Placy
 * @since 1.0.0
 * 
 * @param array  $chapter       Chapter data from story_chapters repeater
 * @param int    $chapter_index Index of this chapter
 * @param int    $project_id    Project post ID
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure we have chapter data
if ( empty( $chapter ) ) {
    return;
}

// Get theme story
$theme_story = $chapter['theme_story'];
if ( ! $theme_story ) {
    return;
}

$theme_story_id = $theme_story->ID;
$anchor_id = $chapter['anchor_id'] ?? 'chapter-' . $chapter_index;
$title = $chapter['front_title'] ?: get_the_title( $theme_story );

// Get theme story content
$intro_text = get_field( 'intro_text', $theme_story_id ) ?: get_the_excerpt( $theme_story_id );
$key_takeaways = get_field( 'key_takeaways', $theme_story_id );

// Get all locations from theme story
$all_locations = get_field( 'all_locations', $theme_story_id );
$total_count = is_array( $all_locations ) ? count( $all_locations ) : 0;

// Get global settings
$default_travel_mode = get_field( 'default_travel_mode', $project_id ) ?: 'walk';
$default_time_budget = get_field( 'default_time_budget', $project_id ) ?: '10';

// Travel mode labels
$travel_labels = array(
    'walk' => 'Til fots',
    'bike' => 'Sykkel',
    'car'  => 'Bil',
);

// Prepare points data for map
$map_points = array();
if ( $all_locations ) {
    foreach ( $all_locations as $point ) {
        $point_id = is_object( $point ) ? $point->ID : $point;
        $lat = get_field( 'latitude', $point_id ) ?: get_post_meta( $point_id, 'latitude', true );
        $lng = get_field( 'longitude', $point_id ) ?: get_post_meta( $point_id, 'longitude', true );
        
        if ( $lat && $lng ) {
            $map_points[] = array(
                'id'        => $point_id,
                'name'      => get_the_title( $point_id ),
                'latitude'  => (float) $lat,
                'longitude' => (float) $lng,
                'category'  => get_field( 'type_label', $point_id ) ?: get_field( 'type', $point_id ),
            );
        }
    }
}
?>

<div 
    class="ns-modal ns-chapter-modal" 
    data-chapter-modal="<?php echo esc_attr( $anchor_id ); ?>"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title-<?php echo esc_attr( $anchor_id ); ?>"
    aria-hidden="true"
>
    <!-- Backdrop -->
    <div class="ns-modal-backdrop"></div>

    <!-- Top Bar -->
    <div class="ns-modal-topbar">
        <span class="ns-modal-brand">NEIGHBORHOOD STORY</span>
        <button type="button" class="ns-modal-close" data-close-modal aria-label="Close modal">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Modal Content -->
    <div class="ns-modal-content">
        <!-- Left Panel: List & Content -->
        <div class="ns-modal-panel ns-modal-panel-left">
            <!-- Header -->
            <div class="ns-modal-header">
                <h2 id="modal-title-<?php echo esc_attr( $anchor_id ); ?>" class="ns-modal-title">
                    <?php echo esc_html( $title ); ?>
                </h2>
                <div class="ns-modal-meta">
                    <span class="ns-modal-count"><?php echo esc_html( $total_count ); ?> places found</span>
                    <span class="ns-modal-highlight">
                        <span data-highlight-count>0</span> highlighted within ≤<span class="ns-time-budget-display"><?php echo esc_html( $default_time_budget ); ?></span> min
                    </span>
                </div>
            </div>

            <!-- Controls -->
            <div class="ns-modal-controls">
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

            <!-- Theme Intro -->
            <?php if ( $intro_text ) : ?>
                <div class="ns-modal-section">
                    <h3 class="ns-section-label">THEME INTRO</h3>
                    <p class="ns-intro-text"><?php echo wp_kses_post( $intro_text ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Key Takeaways (Collapsible) -->
            <?php if ( $key_takeaways ) : ?>
                <div class="ns-modal-section ns-collapsible" data-collapsible>
                    <button type="button" class="ns-collapsible-trigger" data-collapsible-trigger>
                        <span>KEY TAKEAWAYS (<?php echo count( $key_takeaways ); ?>)</span>
                        <svg class="ns-collapsible-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="ns-collapsible-content" data-collapsible-content>
                        <ul class="ns-takeaways-list">
                            <?php foreach ( $key_takeaways as $takeaway ) : ?>
                                <li><?php echo esc_html( $takeaway['text'] ?? $takeaway ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All Locations -->
            <div class="ns-modal-section ns-locations-section" data-poi-container>
                <h3 class="ns-section-label">ALL LOCATIONS</h3>
                
                <?php if ( $all_locations ) : ?>
                    <div class="ns-locations-list">
                        <?php foreach ( $all_locations as $index => $point ) : 
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
                            
                            // Get description
                            $point_desc = '';
                            if ( $point_type === 'placy_google_point' ) {
                                $point_desc = get_field( 'description', $point_id );
                            } else {
                                $point_desc = get_the_excerpt( $point_id );
                            }
                            
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
                            
                            // Get icon based on category
                            $icon_class = 'icon-location';
                        ?>
                            <?php if ( $has_api ) : ?>
                                <!-- API Accordion Card -->
                                <?php 
                                get_template_part( 'template-parts/components/api-accordion-card', null, array(
                                    'point_id'     => $point_id,
                                    'context'      => 'chapter-modal',
                                    'travel_times' => $travel_times,
                                    'travel_mode'  => $default_travel_mode,
                                ) );
                                ?>
                            <?php else : ?>
                                <!-- Standard Location Item -->
                                <div 
                                    class="ns-location-item"
                                    data-poi-times='<?php echo esc_attr( $times_json ); ?>'
                                    data-poi-id="<?php echo esc_attr( $point_id ); ?>"
                                    data-poi-name="<?php echo esc_attr( strtolower( $point_title ) ); ?>"
                                    data-poi-category="<?php echo esc_attr( strtolower( $point_category ) ); ?>"
                                >
                                    <div class="ns-location-icon">
                                        <span class="<?php echo esc_attr( $icon_class ); ?>"></span>
                                    </div>
                                    <div class="ns-location-content">
                                        <div class="ns-location-header">
                                            <h4 class="ns-location-title"><?php echo esc_html( $point_title ); ?></h4>
                                            <span class="ns-location-category"><?php echo esc_html( strtoupper( $point_category ) ); ?></span>
                                        </div>
                                        <?php if ( $point_desc ) : ?>
                                            <p class="ns-location-desc"><?php echo esc_html( wp_trim_words( $point_desc, 10 ) ); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ns-location-time">
                                        <span class="ns-travel-mode-icon"></span>
                                        <span class="ns-poi-time"><?php echo esc_html( $walk_time ); ?></span>
                                        <span class="ns-time-unit">min</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="ns-empty-message">No locations added yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Panel: Map -->
        <div class="ns-modal-panel ns-modal-panel-right">
            <div 
                class="ns-modal-map" 
                data-map-points='<?php echo esc_attr( wp_json_encode( $map_points ) ); ?>'
            >
                <!-- Map will be initialized by JavaScript -->
                <div class="ns-map-placeholder">
                    <span>MAP</span>
                </div>
            </div>
            
            <!-- Time indicators on map -->
            <div class="ns-map-time-indicators">
                <?php if ( $all_locations ) : 
                    foreach ( array_slice( $all_locations, 0, 5 ) as $point ) :
                        $point_id = is_object( $point ) ? $point->ID : $point;
                        $walk_time = (int) ( get_field( 'cached_walk_time', $point_id ) ?: 5 );
                    ?>
                        <div class="ns-time-indicator"><?php echo esc_html( $walk_time ); ?> min</div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>
</div>
