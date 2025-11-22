<?php
/**
 * Chapter Wrapper Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get attributes from block
// In render.php, attributes are available as $attributes array
$chapter_id = isset( $attributes['chapterId'] ) && ! empty( $attributes['chapterId'] ) ? $attributes['chapterId'] : '';
$chapter_anchor = isset( $attributes['chapterAnchor'] ) && ! empty( $attributes['chapterAnchor'] ) ? $attributes['chapterAnchor'] : '';
$chapter_title = isset( $attributes['chapterTitle'] ) && ! empty( $attributes['chapterTitle'] ) ? $attributes['chapterTitle'] : '';

// If no chapter ID is set, generate one based on block ID
if ( empty( $chapter_id ) ) {
    $chapter_id = 'chapter-' . substr( $block['id'], 0, 8 );
}

// Use anchor as fallback for ID if provided
if ( empty( $chapter_anchor ) ) {
    $chapter_anchor = $chapter_id;
}

// Extract chapter number from chapter ID (e.g., "chapter-1" -> "1")
$chapter_number = '';
if ( preg_match( '/chapter-(\d+)/', $chapter_id, $matches ) ) {
    $chapter_number = $matches[1];
}

// Count total chapters in this post
global $post;
$total_chapters = 0;
if ( $post ) {
    // Count opening chapter-wrapper blocks only (not closing tags)
    preg_match_all( '/<!-- wp:placy\/chapter-wrapper/', $post->post_content, $matches );
    $total_chapters = count( $matches[0] );
}

// Build progress indicator
$progress = '';
if ( ! empty( $chapter_number ) && $total_chapters > 0 ) {
    $progress = $chapter_number . '/' . $total_chapters;
}

// Get Google Places settings
$places_enabled = isset( $attributes['placesEnabled'] ) ? $attributes['placesEnabled'] : true;
$places_category = isset( $attributes['placesCategory'] ) ? $attributes['placesCategory'] : 'restaurant';
$places_keyword = isset( $attributes['placesKeyword'] ) ? $attributes['placesKeyword'] : '';
$places_radius = isset( $attributes['placesRadius'] ) ? $attributes['placesRadius'] : 1500;
$places_min_rating = isset( $attributes['placesMinRating'] ) ? $attributes['placesMinRating'] : 4.3;
$places_min_reviews = isset( $attributes['placesMinReviews'] ) ? $attributes['placesMinReviews'] : 50;
$places_exclude_types = isset( $attributes['placesExcludeTypes'] ) ? $attributes['placesExcludeTypes'] : array( 'lodging' );

// Build data attributes array
$data_attributes = array(
    'class'                => 'chapter chapter-with-map',
    'id'                   => esc_attr( $chapter_anchor ),
    'data-chapter-id'      => esc_attr( $chapter_id ),
    'data-chapter-anchor'  => esc_attr( $chapter_anchor ),
    'data-chapter-title'   => esc_attr( $chapter_title ),
    'data-places-enabled'  => $places_enabled ? 'true' : 'false',
    'data-places-category' => esc_attr( $places_category ),
    'data-places-keyword'  => esc_attr( $places_keyword ),
    'data-places-radius'   => esc_attr( $places_radius ),
    'data-places-min-rating' => esc_attr( $places_min_rating ),
    'data-places-min-reviews' => esc_attr( $places_min_reviews ),
    'data-places-exclude-types' => esc_attr( json_encode( $places_exclude_types ) ),
);

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes( $data_attributes );

?>

<section <?php echo $wrapper_attributes; ?>>
    <?php if ( ! empty( $chapter_title ) ) : ?>
        <div class="chapter-title-header">
            <span class="chapter-title-label" <?php if ( ! empty( $progress ) ) echo 'data-progress="(' . esc_attr( $progress ) . ')"'; ?>>
                <?php echo esc_html( $chapter_title ); ?>
            </span>
        </div>
    <?php endif; ?>
    <div class="chapter-grid">
        <div class="chapter-content">
            <?php echo $content; ?>
        </div>
        <div class="chapter-map-column p-6 rounded-lg bg-white">
            <div class="chapter-map-wrapper">
                <div id="<?php echo esc_attr( 'map-' . $chapter_id ); ?>" 
                     class="tema-story-map chapter-map" 
                     data-chapter-id="<?php echo esc_attr( $chapter_id ); ?>">
                </div>
            </div>
        </div>
    </div>
</section>
