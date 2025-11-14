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

<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $class_name ); ?> w-full my-8">
    <?php if ( $poi_items && is_array( $poi_items ) ) : ?>
        <div class="flex flex-col gap-4" <?php if ( $is_in_chapter ) echo 'data-chapter-poi-list="true"'; ?>>
            <?php foreach ( $poi_items as $poi ) : 
                // Get POI coordinates
                $lat = get_field( 'latitude', $poi->ID );
                $lng = get_field( 'longitude', $poi->ID );
                $coords = '';
                
                if ( $lat && $lng ) {
                    $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
                }
            ?>
                <div 
                    class="poi-list-item p-6 bg-white rounded-lg shadow-sm transition-all duration-300 cursor-pointer hover:shadow-md hover:-translate-y-0.5 min-h-[80px]"
                    data-poi-id="<?php echo esc_attr( $poi->ID ); ?>"
                    <?php if ( $coords ) : ?>
                        data-poi-coords="<?php echo $coords; ?>"
                    <?php endif; ?>
                >
                    <h3 class="poi-list-item-title text-lg font-semibold mb-2 text-gray-900">
                        <?php echo esc_html( get_the_title( $poi->ID ) ); ?>
                    </h3>
                    
                    <?php 
                    // Optional: Display excerpt or short description
                    $excerpt = get_the_excerpt( $poi->ID );
                    if ( $excerpt ) : 
                    ?>
                        <p class="text-base text-gray-600 leading-relaxed m-0">
                            <?php echo esc_html( $excerpt ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <?php _e( 'Ingen POIs valgt. Vennligst legg til POIs i blokkinnstillingene.', 'placy' ); ?>
        </p>
    <?php endif; ?>
</div>
