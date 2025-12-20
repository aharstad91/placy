<?php
/**
 * Travel Mode Selector Block
 *
 * Displays mode selector buttons that broadcast travel mode changes.
 * Syncs with proximity-timeline, POI cards, and maps.
 *
 * @package Placy
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Layout fields (col-span)
$col_d = get_field( 'layout_col_span_desktop' ) ?: '12';
$col_m = get_field( 'layout_col_span_mobile' ) ?: '12';
$layout_classes = sprintf( 'pl-col-d-%s pl-col-m-%s', esc_attr( $col_d ), esc_attr( $col_m ) );

// Block wrapper
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'travel-mode-selector-block pl-chapter-block ' . $layout_classes,
) );
?>

<div <?php echo $wrapper_attributes; ?>>
    <div class="travel-mode-buttons">
        <button class="travel-mode-btn active" data-mode="walk" title="Til fots">
            <i class="fas fa-shoe-prints"></i> Til fots
        </button>
        <button class="travel-mode-btn" data-mode="bike" title="Sykkel">
            <i class="fas fa-bicycle"></i> Sykkel
        </button>
        <button class="travel-mode-btn" data-mode="drive" title="Bil">
            <i class="fas fa-car"></i> Bil
        </button>
    </div>
</div>
