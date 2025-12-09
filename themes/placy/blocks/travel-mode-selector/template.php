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

// Block wrapper
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'travel-mode-selector-block',
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
