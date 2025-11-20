<?php
/**
 * POI List Dynamic Block Template
 * Viser kun dynamiske POIs fra Google Places API
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
$places_min_reviews = isset( $attributes['placesMinReviews'] ) ? $attributes['placesMinReviews'] : 50;
$places_exclude_types = isset( $attributes['placesExcludeTypes'] ) ? $attributes['placesExcludeTypes'] : array( 'lodging' );

// Build data attributes
$data_attributes = array(
    'data-places-enabled' => $places_enabled ? 'true' : 'false',
    'data-places-category' => esc_attr( $places_category ),
    'data-places-keyword' => esc_attr( $places_keyword ),
    'data-places-radius' => esc_attr( $places_radius ),
    'data-places-min-rating' => esc_attr( $places_min_rating ),
    'data-places-min-reviews' => esc_attr( $places_min_reviews ),
    'data-places-exclude-types' => esc_attr( json_encode( $places_exclude_types ) ),
);

// Generate unique block ID
$block_id = 'poi-list-dynamic-' . wp_unique_id( 'block-' );

// Build wrapper attributes array
$wrapper_attrs = array(
    'id' => $block_id,
    'class' => 'poi-list-block poi-list-dynamic-block w-full mb-6',
);

// Merge with data attributes
$wrapper_attrs = array_merge( $wrapper_attrs, $data_attributes );

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_attrs );

?>

<div <?php echo $wrapper_attributes; ?>>
    <?php if ( $places_enabled ) : ?>
        <div class="flex flex-col">
            <!-- Google Places results will be inserted here by JavaScript -->
            <div class="poi-list-dynamic-placeholder"></div>
        </div>
    <?php else : ?>
        <p class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <?php _e( 'Google Places API er deaktivert for denne blokken.', 'placy' ); ?>
        </p>
    <?php endif; ?>
</div>
