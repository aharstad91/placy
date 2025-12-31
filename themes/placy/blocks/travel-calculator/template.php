<?php
/**
 * Travel Calculator Block Template
 *
 * Lets users calculate travel time from their location to the property.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get block settings
$transport_mode = get_field( 'transport_mode' ) ?: 'cycling';
$custom_title = get_field( 'custom_title' );
$custom_placeholder = get_field( 'custom_placeholder' );
$quick_areas_raw = get_field( 'quick_areas' ) ?: array();

// Get destination coordinates using the centralized helper function
$dest_lat = null;
$dest_lng = null;
$dest_name = 'destinasjonen';

// Use the standardized helper function that works for all blocks
if ( function_exists( 'placy_get_project_origin_coordinates' ) ) {
    $origin = placy_get_project_origin_coordinates();
    $dest_lat = $origin['lat'];
    $dest_lng = $origin['lng'];
}

// Filter out quick areas that have coordinates too close to the destination
// This prevents 0 min / 0 km results when coordinates are incorrectly set
$quick_areas = array();
if ( ! empty( $quick_areas_raw ) && $dest_lat && $dest_lng ) {
    foreach ( $quick_areas_raw as $area ) {
        $area_lat = floatval( $area['area_lat'] ?? 0 );
        $area_lng = floatval( $area['area_lng'] ?? 0 );
        
        // Calculate approximate distance (simple lat/lng difference)
        // Skip areas that are within ~100m of destination
        $lat_diff = abs( $area_lat - $dest_lat );
        $lng_diff = abs( $area_lng - $dest_lng );
        
        // ~0.001 degrees is approximately 100m at this latitude
        if ( $lat_diff > 0.001 || $lng_diff > 0.001 ) {
            $quick_areas[] = $area;
        }
    }
} elseif ( ! empty( $quick_areas_raw ) ) {
    // If no destination coords, include all areas
    $quick_areas = $quick_areas_raw;
}

// Get destination name from project
global $post;
$project = null;

// First try to get project from the block field
$block_project = get_field( 'project' );
if ( $block_project ) {
    $project = $block_project;
}

// Then try from the story/post's related project
if ( ! $project && $post ) {
    $story_project = get_field( 'project', $post->ID );
    if ( $story_project ) {
        $project = $story_project;
    }
}

// Get the property label for the destination name
if ( $project ) {
    $property_label = get_field( 'property_label', $project->ID );
    if ( $property_label ) {
        $dest_name = $property_label;
    }
}

// Transport mode labels and icons
$transport_labels = array(
    'cycling' => array(
        'icon' => 'fa-bicycle',
        'verb' => 'sykle',
        'default_title' => 'Hvor lang tid tar det å sykle til %s?',
    ),
    'walking' => array(
        'icon' => 'fa-person-walking',
        'verb' => 'gå',
        'default_title' => 'Hvor lang tid tar det å gå til %s?',
    ),
    'driving' => array(
        'icon' => 'fa-car',
        'verb' => 'kjøre',
        'default_title' => 'Hvor lang tid tar det å kjøre til %s?',
    ),
);

$mode_config = $transport_labels[ $transport_mode ] ?? $transport_labels['cycling'];
$title = $custom_title ?: sprintf( $mode_config['default_title'], $dest_name );
$placeholder = $custom_placeholder ?: 'Skriv inn adressen din...';

// Block wrapper
$block_id = 'travel-calc-' . $block['id'];
$class_name = 'travel-calculator-block';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}

?>

<div 
    id="<?php echo esc_attr( $block_id ); ?>" 
    class="<?php echo esc_attr( $class_name ); ?>"
    data-dest-lat="<?php echo esc_attr( $dest_lat ); ?>"
    data-dest-lng="<?php echo esc_attr( $dest_lng ); ?>"
    data-dest-name="<?php echo esc_attr( $dest_name ); ?>"
    data-transport-mode="<?php echo esc_attr( $transport_mode ); ?>"
    data-transport-verb="<?php echo esc_attr( $mode_config['verb'] ); ?>"
>
    <div class="travel-calc-header">
        <i class="fa-solid <?php echo esc_attr( $mode_config['icon'] ); ?> travel-calc-icon"></i>
        <h4 class="travel-calc-title"><?php echo esc_html( $title ); ?></h4>
    </div>
    
    <div class="travel-calc-input-wrapper">
        <?php if ( ! empty( $quick_areas ) ) : ?>
        <div class="travel-calc-quick-areas">
            <span class="travel-calc-quick-label">Velg område:</span>
            <div class="travel-calc-quick-buttons">
                <?php foreach ( $quick_areas as $area ) : ?>
                <button 
                    type="button" 
                    class="travel-calc-quick-btn"
                    data-lat="<?php echo esc_attr( $area['area_lat'] ); ?>"
                    data-lng="<?php echo esc_attr( $area['area_lng'] ); ?>"
                    data-name="<?php echo esc_attr( $area['area_name'] ); ?>"
                >
                    <?php echo esc_html( $area['area_name'] ); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="travel-calc-or-divider">eller skriv inn adresse</div>
        <?php endif; ?>
        
        <div class="travel-calc-input-container">
            <i class="fa-solid fa-location-dot travel-calc-input-icon"></i>
            <input 
                type="text" 
                class="travel-calc-input" 
                placeholder="<?php echo esc_attr( $placeholder ); ?>"
                autocomplete="off"
            />
            <button type="button" class="travel-calc-clear" style="display: none;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <ul class="travel-calc-suggestions" style="display: none;"></ul>
    </div>
    
    <div class="travel-calc-result" style="display: none;">
        <div class="travel-calc-result-content">
            <span class="travel-calc-result-text"></span>
        </div>
        <div class="travel-calc-map-container">
            <div class="travel-calc-map"></div>
        </div>
        <a href="#" class="travel-calc-google-link" target="_blank" rel="noopener noreferrer">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            Åpne i Google Maps
        </a>
    </div>
    
    <div class="travel-calc-loading" style="display: none;">
        <i class="fa-solid fa-spinner fa-spin"></i>
        <span>Beregner...</span>
    </div>
    
    <div class="travel-calc-error" style="display: none;">
        <i class="fa-solid fa-exclamation-circle"></i>
        <span class="travel-calc-error-text"></span>
    </div>
</div>
