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
        <div class="flex flex-col gap-6" <?php if ( $is_in_chapter ) echo 'data-chapter-poi-list="true"'; ?>>
            <?php foreach ( $poi_items as $poi ) : 
                // Get POI coordinates
                $lat = get_field( 'latitude', $poi->ID );
                $lng = get_field( 'longitude', $poi->ID );
                $coords = '';
                
                if ( $lat && $lng ) {
                    $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
                }
                
                // Get featured image URL - landscape format for card top
                $featured_image = get_the_post_thumbnail_url( $poi->ID, 'large' );
                
                // Get POI content
                $content = apply_filters( 'the_content', get_post_field( 'post_content', $poi->ID ) );
                $excerpt = get_the_excerpt( $poi->ID );
            ?>
                <article 
                    class="poi-list-item bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:border-gray-300"
                    data-poi-id="<?php echo esc_attr( $poi->ID ); ?>"
                    data-poi-title="<?php echo esc_attr( get_the_title( $poi->ID ) ); ?>"
                    <?php if ( $coords ) : ?>
                        data-poi-coords="<?php echo $coords; ?>"
                    <?php endif; ?>
                    <?php if ( $featured_image ) : ?>
                        data-poi-image="<?php echo esc_url( $featured_image ); ?>"
                    <?php endif; ?>
                >
                    <div class="poi-card-content flex gap-4 p-4">
                        <?php if ( $featured_image ) : ?>
                            <div class="flex-shrink-0">
                                <img src="<?php echo esc_url( $featured_image ); ?>" 
                                     alt="<?php echo esc_attr( get_the_title( $poi->ID ) ); ?>"
                                     class="w-20 h-20 object-cover rounded-lg">
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 truncate">
                                <?php echo esc_html( get_the_title( $poi->ID ) ); ?>
                            </h3>
                            
                            <?php if ( $content ) : ?>
                                <div class="poi-description text-sm text-gray-600 line-clamp-2 mb-3">
                                    <?php echo wp_kses_post( $content ); ?>
                                </div>
                            <?php elseif ( $excerpt ) : ?>
                                <div class="poi-description text-sm text-gray-600 line-clamp-2 mb-3">
                                    <?php echo esc_html( $excerpt ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="poi-walking-time text-xs font-medium text-gray-500">
                                        Beregner...
                                    </span>
                                </div>
                                
                                <div class="poi-button-container flex items-center gap-2 flex-shrink-0">
                                    <button 
                                        class="poi-show-on-map px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded transition-colors duration-200 whitespace-nowrap"
                                        onclick="showPOIOnMap(this)"
                                    >
                                        Se på kart
                                    </button>
                                </div>
                            </div>
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
