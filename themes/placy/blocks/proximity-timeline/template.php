<?php
/**
 * Proximity Timeline Block Template
 * 
 * Displays proximity points with timeline. 
 * Compact version without dynamic travel time calculation.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get ACF fields
$title = get_field( 'timeline_title' );
$subtitle = get_field( 'timeline_subtitle' );
$items = get_field( 'timeline_items' );
$footer_text = get_field( 'footer_text' );

// If no items, don't render
if ( empty( $items ) ) {
    if ( is_admin() ) {
        echo '<div class="acf-block-placeholder" style="padding: 2rem; background: #f0f0f0; text-align: center;">
            <p>Legg til timeline-punkter i sidepanelet.</p>
        </div>';
    }
    return;
}

// Get project name for label (optional)
$project_name = '';
global $post;
if ( $post ) {
    $project = get_field( 'project', $post->ID );
    if ( $project && is_object( $project ) ) {
        $project_name = get_the_title( $project->ID );
    }
}

// Block wrapper
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'proximity-timeline-block',
) );
?>

<div <?php echo $wrapper_attributes; ?>>
    
    <?php if ( $title ) : ?>
        <h3 class="proximity-timeline-title"><?php echo esc_html( $title ); ?></h3>
    <?php endif; ?>
    
    <?php if ( $subtitle ) : ?>
        <p class="proximity-timeline-subtitle"><?php echo esc_html( $subtitle ); ?></p>
    <?php endif; ?>
    
    <!-- Timeline container -->
    <div class="proximity-timeline-container">
        <!-- Cards row -->
        <div class="proximity-timeline-cards">
            <?php foreach ( $items as $index => $item ) : 
                $item_title = $item['title'] ?? '';
                $item_desc = $item['description'] ?? '';
                $item_lat = $item['latitude'] ?? null;
                $item_lng = $item['longitude'] ?? null;
                
                // Build coords attribute for JavaScript
                $coords_attr = '';
                if ( $item_lat && $item_lng ) {
                    $coords_attr = sprintf( 'data-poi-coords="[%s,%s]"', esc_attr( $item_lat ), esc_attr( $item_lng ) );
                }
            ?>
                <div class="proximity-timeline-card" <?php echo $coords_attr; ?>>
                    <?php if ( $item_lat && $item_lng ) : ?>
                        <div class="timeline-travel-time" style="display: none;">
                            <span class="poi-travel-icon"><i class="fas fa-shoe-prints"></i></span>
                            <span class="poi-travel-time-text"></span>
                        </div>
                    <?php endif; ?>
                    
                    <h4 class="timeline-card-title"><?php echo esc_html( $item_title ); ?></h4>
                    
                    <?php if ( $item_desc ) : ?>
                        <p class="timeline-card-description"><?php echo esc_html( $item_desc ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Timeline line with markers -->
        <div class="proximity-timeline-line">
            <div class="timeline-track"></div>
            <div class="proximity-timeline-markers">
                <?php foreach ( $items as $index => $item ) : ?>
                    <div class="timeline-marker">
                        <span class="marker-dot"></span>
                        <span class="marker-dash"></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php if ( $footer_text ) : ?>
        <p class="proximity-timeline-footer"><?php echo esc_html( $footer_text ); ?></p>
    <?php endif; ?>
</div>
