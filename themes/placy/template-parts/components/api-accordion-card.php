<?php
/**
 * API Accordion Card Component
 * 
 * Renders a POI card with accordion for live API data
 * (Entur departures, Bysykkel availability, Hyre cars)
 * 
 * @package Placy
 * @since 1.0.0
 * 
 * @param int    $point_id       Post ID of the native/google point
 * @param string $context        Context: 'chapter-modal', 'global-map', 'sidebar'
 * @param array  $travel_times   Optional travel times array (walk, bike, car)
 * @param string $travel_mode    Current travel mode for display
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get point ID from args or global
$point_id = $args['point_id'] ?? get_the_ID();
if ( ! $point_id ) {
    return;
}

$context = $args['context'] ?? 'default';
$travel_times = $args['travel_times'] ?? array();
$current_travel_mode = $args['travel_mode'] ?? 'walk';

// Get point data
$point_title = get_the_title( $point_id );
$point_type = get_post_type( $point_id );

// Get coordinates for "Se på kart" button
// Use placy_get_poi_coordinates which handles both native and google points
$poi_coords = function_exists( 'placy_get_poi_coordinates' ) ? placy_get_poi_coordinates( $point_id ) : null;
$lat = $poi_coords ? $poi_coords['lat'] : null;
$lng = $poi_coords ? $poi_coords['lng'] : null;

// Fallback to legacy field names if coordinates not found
if ( ! $lat || ! $lng ) {
    $lat = get_field( 'latitude', $point_id ) ?: get_post_meta( $point_id, 'latitude', true );
    $lng = get_field( 'longitude', $point_id ) ?: get_post_meta( $point_id, 'longitude', true );
}

// Check which API integrations are active
$api_integrations = get_field( 'api_integrations', $point_id ) ?: array();

// Entur data
$entur_stopplace_id = '';
$entur_quay_id = '';
$entur_transport_mode = '';
$entur_group_by_direction = true;
$entur_line_filter = '';
$show_live_departures = false;

if ( in_array( 'entur', $api_integrations, true ) || get_field( 'show_live_departures', $point_id ) ) {
    $entur_stopplace_id = get_field( 'entur_stopplace_id', $point_id );
    $entur_quay_id = get_field( 'entur_quay_id', $point_id );
    $entur_transport_mode = get_field( 'entur_transport_mode', $point_id );
    $entur_group_by_direction = get_field( 'entur_group_by_direction', $point_id ) !== false;
    $entur_line_filter = get_field( 'entur_line_filter', $point_id );
    $show_live_departures = get_field( 'show_live_departures', $point_id );
}

// Bysykkel data
$bysykkel_station_id = '';
$show_bike_availability = false;

if ( in_array( 'bysykkel', $api_integrations, true ) || get_field( 'show_bike_availability', $point_id ) ) {
    $bysykkel_station_id = get_field( 'bysykkel_station_id', $point_id );
    $show_bike_availability = get_field( 'show_bike_availability', $point_id );
}

// Hyre data
$hyre_station_id = '';
$show_hyre_availability = false;

if ( in_array( 'hyre', $api_integrations, true ) || get_field( 'show_hyre_availability', $point_id ) ) {
    $hyre_station_id = get_field( 'hyre_station_id', $point_id );
    $show_hyre_availability = get_field( 'show_hyre_availability', $point_id );
}

// Determine if this point has any API integration
$has_api = ( $entur_stopplace_id && $show_live_departures ) 
        || ( $bysykkel_station_id && $show_bike_availability )
        || ( $hyre_station_id && $show_hyre_availability );

if ( ! $has_api ) {
    return; // Don't render if no API integration
}

// Get travel time for display
$display_time = '';
$display_mode_label = '';
if ( ! empty( $travel_times ) ) {
    $display_time = $travel_times[ $current_travel_mode ] ?? $travel_times['walk'] ?? '';
    $mode_labels = array(
        'walk' => 'gange',
        'bike' => 'sykkel',
        'car'  => 'bil',
    );
    $display_mode_label = $mode_labels[ $current_travel_mode ] ?? 'gange';
}

// Determine API type and icon
$api_type = '';
$icon_class = '';
$icon_svg = '';

// Get taxonomy-based icon for this POI
$category_icon = placy_get_poi_category_icon( $point_id );
$icon_color = $category_icon['color'] ?? '#6366F1';
$icon_name = $category_icon['icon'] ?? 'fa-location-dot';

// Override with specific colors per API type if needed
if ( $entur_stopplace_id && $show_live_departures ) {
    $api_type = 'entur';
    $icon_class = 'ns-api-icon--entur';
} elseif ( $bysykkel_station_id && $show_bike_availability ) {
    $api_type = 'bysykkel';
    $icon_class = 'ns-api-icon--bysykkel';
} elseif ( $hyre_station_id && $show_hyre_availability ) {
    $api_type = 'hyre';
    $icon_class = 'ns-api-icon--hyre';
}

// Build data attributes for API fetching
$data_attrs = array(
    'data-poi-id="' . esc_attr( $point_id ) . '"',
    'data-has-api="true"',
    'data-api-type="' . esc_attr( $api_type ) . '"',
);

if ( $lat && $lng ) {
    $data_attrs[] = 'data-lat="' . esc_attr( $lat ) . '"';
    $data_attrs[] = 'data-lng="' . esc_attr( $lng ) . '"';
    $data_attrs[] = 'data-poi-coords="[' . esc_attr( $lat ) . ',' . esc_attr( $lng ) . ']"';
}

// Add POI title for modal context
if ( $point_title ) {
    $data_attrs[] = 'data-poi-title="' . esc_attr( $point_title ) . '"';
}

// Add category icon data
if ( ! empty( $category_icon ) ) {
    if ( ! empty( $category_icon['icon'] ) ) {
        $data_attrs[] = 'data-poi-icon="' . esc_attr( $category_icon['icon'] ) . '"';
    }
    if ( ! empty( $category_icon['color'] ) ) {
        $data_attrs[] = 'data-poi-icon-color="' . esc_attr( $category_icon['color'] ) . '"';
    }
}

// Add pre-calculated travel times for modal
if ( ! empty( $travel_times ) ) {
    $data_attrs[] = 'data-travel-times="' . esc_attr( wp_json_encode( $travel_times ) ) . '"';
}

if ( $entur_stopplace_id && $show_live_departures ) {
    $data_attrs[] = 'data-entur-stopplace-id="' . esc_attr( $entur_stopplace_id ) . '"';
    $data_attrs[] = 'data-show-live-departures="1"';
    $data_attrs[] = 'data-entur-group-by-direction="' . ( $entur_group_by_direction ? '1' : '0' ) . '"';
    if ( $entur_quay_id ) {
        $data_attrs[] = 'data-entur-quay-id="' . esc_attr( $entur_quay_id ) . '"';
    }
    if ( $entur_transport_mode ) {
        $data_attrs[] = 'data-entur-transport-mode="' . esc_attr( $entur_transport_mode ) . '"';
    }
    if ( $entur_line_filter ) {
        $data_attrs[] = 'data-entur-line-filter="' . esc_attr( $entur_line_filter ) . '"';
    }
}

if ( $bysykkel_station_id && $show_bike_availability ) {
    $data_attrs[] = 'data-bysykkel-station-id="' . esc_attr( $bysykkel_station_id ) . '"';
    $data_attrs[] = 'data-show-bike-availability="1"';
}

if ( $hyre_station_id && $show_hyre_availability ) {
    $data_attrs[] = 'data-hyre-station-id="' . esc_attr( $hyre_station_id ) . '"';
    $data_attrs[] = 'data-show-hyre-availability="1"';
}
?>

<div class="ns-api-card" <?php echo implode( ' ', $data_attrs ); ?>>
    <!-- Accordion Header (always visible) -->
    <div class="ns-api-header" role="button" tabindex="0" aria-expanded="false">
        <!-- Icon -->
        <div class="ns-api-icon <?php echo esc_attr( $icon_class ); ?>" style="background-color: <?php echo esc_attr( $icon_color ); ?>; color: #fff;">
            <i class="<?php echo esc_attr( $icon_name ); ?>"></i>
        </div>

        <!-- Content -->
        <div class="ns-api-content">
            <h4 class="ns-api-title"><?php echo esc_html( $point_title ); ?></h4>
            <div class="ns-api-meta">
                <?php if ( $display_time ) : ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <?php if ( $current_travel_mode === 'walk' ) : ?>
                            <path d="M13 4v4l3 3m-3-3l-3 3M12 21V8m-4 5v6m8-6v6"/>
                        <?php elseif ( $current_travel_mode === 'bike' ) : ?>
                            <circle cx="5" cy="17" r="3"/><circle cx="19" cy="17" r="3"/><path d="M12 17V5l4 6h3"/>
                        <?php else : ?>
                            <path d="M5 17h14v-5l-2-4H7l-2 4v5z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/>
                        <?php endif; ?>
                    </svg>
                    <span><?php echo esc_html( $display_time ); ?> min <?php echo esc_html( $display_mode_label ); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="ns-api-actions">
            <?php if ( $lat && $lng ) : ?>
                <button type="button" class="ns-api-map-btn" data-show-on-map data-poi-id="<?php echo esc_attr( $point_id ); ?>" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <span>Se på kart</span>
                </button>
            <?php endif; ?>

            <!-- Expand/collapse toggle -->
            <div class="ns-api-toggle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Accordion Body (collapsible) -->
    <div class="ns-api-body">
        <!-- Loading state (shown while fetching) -->
        <div class="ns-api-loading">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4m0 12v4m10-10h-4M6 12H2m15.07-5.07l-2.83 2.83M9.76 14.24l-2.83 2.83m0-10.14l2.83 2.83m4.48 4.48l2.83 2.83"/>
            </svg>
            <span>Henter data...</span>
        </div>

        <!-- Content will be injected by JavaScript based on API type -->
        <div class="ns-api-content-wrapper" data-api-content>
            <!-- Entur departures, Bysykkel availability, or Hyre availability will appear here -->
        </div>
    </div>
</div>
