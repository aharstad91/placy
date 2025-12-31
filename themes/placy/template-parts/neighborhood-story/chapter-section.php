<?php
/**
 * Neighborhood Story - Chapter Section (Front Section)
 * 
 * The visible section on the main page for each story chapter.
 * Shows:
 * - Title and subtitle
 * - Brief description
 * - Preview POIs (3-4 cards)
 * - "See all" button to open modal
 *
 * @package Placy
 * @since 1.0.0
 * 
 * @param array  $chapter       Chapter data from story_chapters repeater
 * @param int    $chapter_index Index of this chapter (0-based)
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

// Get chapter details
$anchor_id = $chapter['anchor_id'] ?? 'chapter-' . $chapter_index;
$title = $chapter['front_title'] ?: get_the_title( $theme_story );
$subtitle = $chapter['front_subtitle'] ?? '';
$description = $chapter['front_text'] ?? '';
$icon = $chapter['icon'] ?? 'walk';
$show_map = $chapter['show_map_preview'] ?? true;

// Get preview POIs
$preview_points = $chapter['preview_points'] ?? array();

// If no preview points set, get from theme story's all_locations
if ( empty( $preview_points ) ) {
    $all_locations = get_field( 'all_locations', $theme_story->ID );
    if ( $all_locations ) {
        $preview_points = array_slice( $all_locations, 0, 3 );
    }
}

// Get total count from theme story
$all_locations = get_field( 'all_locations', $theme_story->ID );
$total_count = is_array( $all_locations ) ? count( $all_locations ) : 0;

// Get category label from theme story
$category_label = get_the_title( $theme_story );

// Icon SVG map
$icon_svgs = array(
    'train' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />',
    'walk'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h3m0 0v3m0-3L7 16m9-9l3 3" />',
    'food'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
);
?>

<section 
    id="<?php echo esc_attr( $anchor_id ); ?>" 
    class="ns-chapter-section"
    data-anchor="<?php echo esc_attr( $anchor_id ); ?>"
    data-chapter-index="<?php echo esc_attr( $chapter_index ); ?>"
>
    <!-- Category Badge -->
    <div class="ns-chapter-badge">
        <?php echo esc_html( strtoupper( $category_label ) ); ?>
    </div>

    <!-- Title & Subtitle -->
    <h2 class="ns-chapter-title"><?php echo esc_html( $subtitle ?: $title ); ?></h2>
    
    <?php if ( $description ) : ?>
        <p class="ns-chapter-description"><?php echo esc_html( $description ); ?></p>
    <?php endif; ?>

    <!-- Preview POI Cards -->
    <?php if ( $preview_points ) : ?>
        <div class="ns-chapter-pois">
            <?php foreach ( $preview_points as $point ) : 
                // Get point data
                $point_id = is_object( $point ) ? $point->ID : $point;
                $point_title = get_the_title( $point_id );
                $point_type = get_post_type( $point_id );
                
                // Get category/label
                $point_category = '';
                if ( $point_type === 'placy_google_point' ) {
                    $point_category = get_field( 'type_label', $point_id ) ?: get_field( 'type', $point_id );
                } else {
                    $terms = get_the_terms( $point_id, 'poi_category' );
                    $point_category = $terms ? $terms[0]->name : '';
                }
                
                // Get travel times
                $walk_time = get_field( 'cached_walk_time', $point_id ) ?: get_post_meta( $point_id, 'cached_walk_time', true );
                $bike_time = get_field( 'cached_bike_time', $point_id ) ?: get_post_meta( $point_id, 'cached_bike_time', true );
                $drive_time = get_field( 'cached_drive_time', $point_id ) ?: get_post_meta( $point_id, 'cached_drive_time', true );
                
                $times_json = wp_json_encode( array(
                    'walk' => (int) $walk_time ?: 5,
                    'bike' => (int) $bike_time ?: 2,
                    'car'  => (int) $drive_time ?: 3,
                ) );
                
                // Get description/subtitle
                $point_desc = '';
                if ( $point_type === 'placy_google_point' ) {
                    $point_desc = get_field( 'description', $point_id );
                } else {
                    $point_desc = get_the_excerpt( $point_id );
                }
                
                // Get rating
                $rating = get_field( 'rating', $point_id ) ?: get_post_meta( $point_id, 'rating', true );
            ?>
                <div 
                    class="ns-poi-card" 
                    data-poi-times='<?php echo esc_attr( $times_json ); ?>'
                    data-poi-id="<?php echo esc_attr( $point_id ); ?>"
                >
                    <div class="ns-poi-card-header">
                        <span class="ns-poi-time"><?php echo esc_html( $walk_time ?: '5' ); ?> min walk</span>
                        <?php if ( $point_category ) : ?>
                            <span class="ns-poi-category"><?php echo esc_html( strtoupper( $point_category ) ); ?></span>
                        <?php endif; ?>
                        <?php if ( $rating ) : ?>
                            <span class="ns-poi-rating"><?php echo esc_html( $rating ); ?></span>
                        <?php endif; ?>
                    </div>
                    <h4 class="ns-poi-card-title"><?php echo esc_html( $point_title ); ?></h4>
                    <?php if ( $point_desc ) : ?>
                        <p class="ns-poi-card-desc"><?php echo esc_html( wp_trim_words( $point_desc, 8 ) ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- See All Button -->
    <?php if ( $total_count > count( $preview_points ) ) : 
        $remaining = $total_count - count( $preview_points );
    ?>
        <button 
            type="button" 
            class="ns-chapter-see-all"
            data-open-chapter="<?php echo esc_attr( $anchor_id ); ?>"
        >
            <span class="ns-see-all-count">+<?php echo esc_html( $remaining ); ?></span>
            <span class="ns-see-all-text">See all places in this category</span>
            <span class="ns-see-all-total">Total <?php echo esc_html( $total_count ); ?> locations</span>
        </button>
    <?php endif; ?>
</section>
