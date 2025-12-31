<?php
/**
 * Master Map Modal Template
 * 
 * Displays all places from all Story Chapters in a single map view.
 * Reuses ns-* classes from neighborhood-story.css for consistency.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $post;
$project_id = $post->ID;

// Get project coordinates for map center
$project_lat = get_field( 'start_latitude', $project_id ) ?: get_field( 'project_latitude', $project_id ) ?: 63.4305;
$project_lng = get_field( 'start_longitude', $project_id ) ?: get_field( 'project_longitude', $project_id ) ?: 10.3951;

// Get default values from global state or project settings
$default_travel_mode = get_field( 'default_travel_mode', $project_id ) ?: 'walk';
$default_time_budget = get_field( 'default_time_budget', $project_id ) ?: 10;

// Parse all Story Chapter blocks to collect POIs
$content = get_the_content();
$blocks = parse_blocks( $content );

$all_places = array();
$categories = array();

foreach ( $blocks as $block ) {
    if ( $block['blockName'] === 'acf/story-chapter' ) {
        $chapter_category = '';
        $chapter_title = '';
        $chapter_id = '';
        $chapter_points = array();
        
        if ( isset( $block['attrs']['data'] ) ) {
            $data = $block['attrs']['data'];
            $chapter_category = isset( $data['chapter_category_name'] ) ? $data['chapter_category_name'] : '';
            $chapter_title = isset( $data['chapter_front_title'] ) ? $data['chapter_front_title'] : '';
            $chapter_id = isset( $data['chapter_id'] ) ? $data['chapter_id'] : 'chapter-' . substr( $block['attrs']['id'] ?? '', 0, 8 );
            
            $theme_story_id = isset( $data['chapter_theme_story'] ) ? $data['chapter_theme_story'] : null;
            
            if ( $theme_story_id ) {
                $theme_points = get_field( 'all_locations', $theme_story_id );
                if ( ! empty( $theme_points ) ) {
                    $chapter_points = $theme_points;
                }
            }
            
            if ( empty( $chapter_points ) ) {
                $block_points = isset( $data['chapter_all_points'] ) ? $data['chapter_all_points'] : array();
                if ( ! empty( $block_points ) ) {
                    $chapter_points = $block_points;
                }
            }
            
            if ( empty( $chapter_points ) ) {
                $highlighted = isset( $data['chapter_highlighted_points'] ) ? $data['chapter_highlighted_points'] : array();
                if ( ! empty( $highlighted ) ) {
                    $chapter_points = $highlighted;
                }
            }
        }
        
        if ( ! empty( $chapter_category ) && ! in_array( $chapter_category, array_column( $categories, 'name' ) ) ) {
            $categories[] = array(
                'id' => sanitize_title( $chapter_category ),
                'name' => $chapter_category,
                'chapter_id' => $chapter_id
            );
        }
        
        if ( ! empty( $chapter_points ) && function_exists( 'placy_get_chapter_points' ) ) {
            $points_data = placy_get_chapter_points( $chapter_points );
            
            foreach ( $points_data as $point ) {
                $point['name'] = $point['title'] ?? '';
                $point['type'] = $point['category'] ?? '';
                $point['category'] = $chapter_category;
                $point['category_id'] = sanitize_title( $chapter_category );
                $point['chapter_id'] = $chapter_id;
                $all_places[] = $point;
            }
        }
    }
}

$master_map_data = array(
    'projectId' => $project_id,
    'projectLat' => (float) $project_lat,
    'projectLng' => (float) $project_lng,
    'places' => $all_places,
    'categories' => $categories,
    'totalPlaces' => count( $all_places ),
    'defaultTravelMode' => $default_travel_mode,
    'defaultTimeBudget' => (int) $default_time_budget
);
?>

<script type="application/json" id="master-map-data">
<?php echo wp_json_encode( $master_map_data ); ?>
</script>

<div 
    id="master-map-modal" 
    class="ns-modal ns-master-map" 
    role="dialog"
    aria-modal="true"
    aria-labelledby="master-map-title"
    aria-hidden="true"
>
    <div class="ns-modal-backdrop"></div>

    <div class="ns-modal-topbar ns-modal-topbar-dark">
        <span class="ns-modal-brand">NEIGHBORHOOD STORY</span>
        <button type="button" class="ns-modal-close" data-close-modal aria-label="<?php esc_attr_e( 'Close', 'placy' ); ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <div class="ns-modal-content ns-master-map-content">
        
        <div class="ns-modal-panel ns-modal-panel-left ns-master-map-list">
            
            <div class="ns-modal-header">
                <div class="ns-modal-header-row">
                    <h2 id="master-map-title" class="ns-modal-title"><?php esc_html_e( 'All places', 'placy' ); ?></h2>
                    <span class="ns-result-count"><?php echo esc_html( count( $all_places ) ); ?> <?php esc_html_e( 'results', 'placy' ); ?></span>
                </div>
                
                <div class="ns-filter-chips">
                    <button type="button" class="ns-filter-chip active" data-filter="all">
                        <?php esc_html_e( 'All', 'placy' ); ?>
                    </button>
                    <?php foreach ( $categories as $cat ) : ?>
                        <button type="button" class="ns-filter-chip" data-filter="<?php echo esc_attr( $cat['id'] ); ?>">
                            <?php echo esc_html( $cat['name'] ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="ns-modal-controls ns-master-map-controls">
                <?php 
                // Shared Travel Controls Component
                get_template_part( 'template-parts/components/travel-controls', null, array(
                    'default_mode' => $default_travel_mode,
                    'default_time' => $default_time_budget,
                    'context'      => 'modal',
                ) );
                ?>
                
                <div class="ns-control-section ns-search-section">
                    <label class="ns-control-label"><?php esc_html_e( 'Search', 'placy' ); ?></label>
                    <div class="ns-search-input-wrapper">
                        <svg class="ns-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="search" class="ns-search-input" placeholder="<?php esc_attr_e( 'Search places...', 'placy' ); ?>" data-search-input />
                    </div>
                </div>
            </div>
            
            <div class="ns-locations-section ns-master-map-locations" data-poi-container>
                <?php if ( ! empty( $all_places ) ) : ?>
                    <div class="ns-locations-list">
                        <?php foreach ( $all_places as $index => $place ) : ?>
                            <div class="ns-location-item" data-place-id="<?php echo esc_attr( $place['id'] ?? $index ); ?>" data-category="<?php echo esc_attr( $place['category_id'] ); ?>" data-lat="<?php echo esc_attr( $place['lat'] ?? '' ); ?>" data-lng="<?php echo esc_attr( $place['lng'] ?? '' ); ?>" data-chapter="<?php echo esc_attr( $place['chapter_id'] ); ?>">
                                <div class="ns-location-info">
                                    <h3 class="ns-location-name"><?php echo esc_html( $place['name'] ); ?></h3>
                                    <div class="ns-location-meta">
                                        <span class="ns-location-category"><?php echo esc_html( $place['category'] ); ?></span>
                                        <span class="ns-location-type"><?php echo esc_html( strtolower( $place['type'] ?? '' ) ); ?></span>
                                    </div>
                                </div>
                                <div class="ns-location-actions">
                                    <span class="ns-location-time">
                                        <span data-place-duration>--</span>
                                        <span class="ns-location-time-unit"> min</span>
                                    </span>
                                    <button type="button" class="ns-btn-small ns-btn-view-story" data-view-in-story="<?php echo esc_attr( $place['chapter_id'] ); ?>">
                                        <?php esc_html_e( 'View in story', 'placy' ); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="ns-empty-state"><?php esc_html_e( 'No places found.', 'placy' ); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="ns-modal-footer">
                <span class="ns-footer-label"><?php esc_html_e( 'All story places', 'placy' ); ?></span>
                <span class="ns-footer-count"><?php echo esc_html( count( $all_places ) ); ?></span>
            </div>
        </div>
        
        <div class="ns-modal-panel ns-modal-panel-right ns-master-map-panel">
            <div id="master-map-container" class="ns-map-container" data-lat="<?php echo esc_attr( $project_lat ); ?>" data-lng="<?php echo esc_attr( $project_lng ); ?>"></div>
        </div>
        
    </div>
</div>
