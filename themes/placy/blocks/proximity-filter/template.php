<?php
/**
 * Proximity Filter Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get block settings
$default_time = get_field( 'default_time' ) ?: '10';
$default_mode = get_field( 'default_mode' ) ?: 'walk';

// Get block wrapper attributes
$block_id = 'proximity-filter-' . $block['id'];
$class_name = 'proximity-filter-block';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}

// Get project coordinates from the theme story (from current post's project field)
$post_id = get_the_ID();
$project = get_field( 'project', $post_id );
$project_coords = null;

if ( $project ) {
    $start_lat = get_field( 'start_latitude', $project->ID );
    $start_lng = get_field( 'start_longitude', $project->ID );
    
    if ( $start_lat && $start_lng ) {
        $project_coords = array(
            'lat' => floatval( $start_lat ),
            'lng' => floatval( $start_lng )
        );
    }
}

// Admin error handling
if ( is_admin() && ! $project_coords ) {
    echo '<div class="notice notice-error"><p><strong>Proximity Filter:</strong> Project coordinates missing. Please set project address in Project settings.</p></div>';
    return;
}

// Don't render on frontend if coordinates missing
if ( ! is_admin() && ! $project_coords ) {
    return;
}

?>

<div id="<?php echo esc_attr( $block_id ); ?>" 
     class="<?php echo esc_attr( $class_name ); ?> w-full mb-8"
     data-default-time="<?php echo esc_attr( $default_time ); ?>"
     data-default-mode="<?php echo esc_attr( $default_mode ); ?>"
     data-project-coords="<?php echo esc_attr( json_encode( $project_coords ) ); ?>"
     data-project-id="<?php echo esc_attr( $project ? $project->ID : '' ); ?>">
    
    <div class="proximity-filter-container bg-white rounded-lg shadow-sm p-6">
        <!-- Time Selector -->
        <div class="proximity-time-selector mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Reisetid</label>
            <div class="flex gap-2">
                <button class="proximity-time-btn <?php echo $default_time == 10 ? 'active' : ''; ?>" data-time="10">
                    10 min
                </button>
                <button class="proximity-time-btn <?php echo $default_time == 20 ? 'active' : ''; ?>" data-time="20">
                    20 min
                </button>
                <button class="proximity-time-btn <?php echo $default_time == 30 ? 'active' : ''; ?>" data-time="30">
                    30 min
                </button>
            </div>
        </div>

        <!-- Mode Selector -->
        <div class="proximity-mode-selector mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Transportmiddel</label>
            <div class="flex gap-2">
                <button class="proximity-mode-btn <?php echo $default_mode === 'walk' ? 'active' : ''; ?>" data-mode="walk">
                    🚶 Gange
                </button>
                <button class="proximity-mode-btn <?php echo $default_mode === 'bike' ? 'active' : ''; ?>" data-mode="bike">
                    🚴 Sykkel
                </button>
                <button class="proximity-mode-btn <?php echo $default_mode === 'drive' ? 'active' : ''; ?>" data-mode="drive">
                    🚗 Bil
                </button>
            </div>
        </div>

        <!-- Result Counter -->
        <div class="proximity-result-counter text-sm text-gray-600">
            <span class="loading-state hidden">Laster...</span>
            <span class="result-text">Viser <strong class="result-count">0</strong> steder innen <strong class="result-time"><?php echo esc_html( $default_time ); ?></strong> min <strong class="result-mode"><?php echo esc_html( $default_mode === 'walk' ? 'gange' : ( $default_mode === 'bike' ? 'sykkel' : 'bil' ) ); ?></strong></span>
            <span class="empty-state hidden text-orange-600">Ingen steder innen denne tiden. Prøv å utvide filteret.</span>
        </div>
    </div>
</div>
