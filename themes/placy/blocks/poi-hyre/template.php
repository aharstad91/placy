<?php
/**
 * POI Hyre Block Template
 * Single POI selector for Hyre car sharing stations with live availability
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get the single POI item from the relationship field
$poi_items = get_field( 'poi_item' );
$poi_item = is_array( $poi_items ) && ! empty( $poi_items ) ? $poi_items[0] : $poi_items;

// Get block wrapper attributes
$block_id = 'poi-hyre-' . $block['id'];
$class_name = 'poi-hyre-block';

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

<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $class_name ); ?> w-full mb-6">
    <?php if ( $poi_item ) : ?>
        <?php 
        // Get project origin coordinates and ID
        $origin_data = function_exists( 'placy_get_project_origin_data' ) 
            ? placy_get_project_origin_data() 
            : array( 'lat' => 63.4305, 'lng' => 10.3951, 'project_id' => null );
        $origin = array( 'lat' => $origin_data['lat'], 'lng' => $origin_data['lng'] );
        $project_id = $origin_data['project_id'];
        
        // Get POI coordinates
        $poi_coords = placy_get_poi_coordinates( $poi_item->ID );
        $coords = '';
        $lat = null;
        $lng = null;
        $travel_times = null;
        
        if ( $poi_coords ) {
            $lat = $poi_coords['lat'];
            $lng = $poi_coords['lng'];
            $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
            
            // Pre-calculate travel times for all modes
            if ( function_exists( 'placy_calculate_travel_times' ) ) {
                $travel_times = placy_calculate_travel_times( $origin['lat'], $origin['lng'], $lat, $lng, $poi_item->ID, $project_id );
            }
        }
        
        // Get Hyre integration data
        $hyre_station_id = get_field( 'hyre_station_id', $poi_item->ID );
        $show_hyre_availability = get_field( 'show_hyre_availability', $poi_item->ID );
        
        // Get category icon for display
        $category_icon = placy_get_poi_category_icon( $poi_item->ID );
        
        // Get POI name and walk time
        $poi_name = get_the_title( $poi_item->ID );
        $walk_time = $travel_times ? $travel_times['walk'] : null;
        
        // Add "Hyre:" prefix if not already present
        if ( strpos( $poi_name, 'Hyre:' ) !== 0 ) {
            $display_name = 'Hyre: ' . $poi_name;
        } else {
            $display_name = $poi_name;
        }
        ?>
        
        <div <?php if ( $is_in_chapter ) echo 'data-chapter-poi-hyre="true"'; ?>>
            <!-- Use API Accordion Card Component -->
            <?php 
            get_template_part( 'template-parts/components/api-accordion-card', null, array(
                'point_id'     => $poi_item->ID,
                'context'      => 'theme-story',
                'travel_times' => $travel_times,
                'travel_mode'  => 'walk',
            ) );
            ?>
        </div>
    <?php else : ?>
        <p class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <?php _e( 'Ingen POI valgt. Vennligst velg en Hyre-stasjon i blokkinnstillingene.', 'placy' ); ?>
        </p>
    <?php endif; ?>
</div>
