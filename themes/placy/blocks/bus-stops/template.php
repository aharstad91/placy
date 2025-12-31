<?php
/**
 * Bus Stops Block Template
 *
 * Displays a list of bus stops in accordion format with live departure times.
 * Supports both local buses (AtB) and airport express (Værnes-ekspressen).
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get block settings
$section_title = get_field( 'section_title' ) ?: 'Buss og flybuss';
$section_description = get_field( 'section_description' ) ?: '';
$stops = get_field( 'stops' ) ?: array();

// Block wrapper
$block_id = 'bus-stops-' . $block['id'];
$class_name = 'bus-stops-block';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}
?>

<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $class_name ); ?>">
    <?php if ( $section_title ) : ?>
        <h2 class="bus-section-title"><?php echo esc_html( $section_title ); ?></h2>
    <?php endif; ?>
    
    <?php if ( $section_description ) : ?>
        <p class="bus-section-description"><?php echo esc_html( $section_description ); ?></p>
    <?php endif; ?>
    
    <?php if ( empty( $stops ) ) : ?>
        <?php if ( is_admin() ) : ?>
            <p class="bus-stops-placeholder">Velg bussholdeplasser i blokkinnstillingene</p>
        <?php endif; ?>
    <?php else : ?>
        <div class="bus-stops-list">
            <?php foreach ( $stops as $index => $stop ) : 
                $poi = $stop['stop'] ?? null;
                if ( ! $poi ) continue;
                
                $poi_id = $poi->ID;
                $title = get_the_title( $poi_id );
                $walking_time = $stop['walking_time'] ?? '';
                $direction_label = $stop['direction_label'] ?? '';
                
                // Get Entur integration data
                $entur_stopplace_id = get_field( 'entur_stopplace_id', $poi_id );
                $entur_quay_id = get_field( 'entur_quay_id', $poi_id );
                $entur_transport_mode = get_field( 'entur_transport_mode', $poi_id );
                $entur_line_filter = get_field( 'entur_line_filter', $poi_id );
                $entur_group_by_direction = get_field( 'entur_group_by_direction', $poi_id );
                
                // Get coordinates for map
                $coords = '';
                $lat = get_field( 'latitude', $poi_id );
                $lng = get_field( 'longitude', $poi_id );
                if ( $lat && $lng ) {
                    $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
                }
                
                // Determine display title
                $display_title = $title;
                if ( $direction_label ) {
                    $display_title .= ' (' . $direction_label . ')';
                }
            ?>
                <div class="bus-stop-item" 
                     data-poi-id="<?php echo esc_attr( $poi_id ); ?>"
                     data-poi-title="<?php echo esc_attr( $title ); ?>"
                     <?php if ( $coords ) : ?>
                        data-poi-coords="<?php echo esc_attr( $coords ); ?>"
                     <?php endif; ?>
                     <?php if ( $entur_stopplace_id ) : ?>
                        data-entur-stopplace-id="<?php echo esc_attr( $entur_stopplace_id ); ?>"
                        data-show-live-departures="1"
                        <?php if ( $entur_quay_id ) : ?>
                            data-entur-quay-id="<?php echo esc_attr( $entur_quay_id ); ?>"
                        <?php endif; ?>
                        <?php if ( $entur_transport_mode ) : ?>
                            data-entur-transport-mode="<?php echo esc_attr( $entur_transport_mode ); ?>"
                        <?php endif; ?>
                        <?php if ( $entur_line_filter ) : ?>
                            data-entur-line-filter="<?php echo esc_attr( $entur_line_filter ); ?>"
                        <?php endif; ?>
                        data-entur-group-by-direction="<?php echo $entur_group_by_direction ? '1' : '0'; ?>"
                     <?php endif; ?>>
                    
                    <div class="bus-stop-header" role="button" tabindex="0" aria-expanded="false" aria-controls="bus-content-<?php echo esc_attr( $block_id . '-' . $index ); ?>">
                        <div class="bus-stop-info">
                            <h4 class="bus-stop-name"><?php echo esc_html( $display_title ); ?></h4>
                            <?php if ( $walking_time ) : ?>
                                <span class="bus-stop-time"><?php echo esc_html( $walking_time ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="bus-stop-actions">
                            <button type="button" class="bus-map-btn" onclick="event.stopPropagation(); showPOIOnMap(this.closest('.bus-stop-item'));">
                                Se på kart
                            </button>
                            <span class="bus-accordion-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    
                    <div id="bus-content-<?php echo esc_attr( $block_id . '-' . $index ); ?>" class="bus-stop-content" hidden>
                        <div class="bus-departures" data-stopplace-id="<?php echo esc_attr( $entur_stopplace_id ); ?>" data-quay-id="<?php echo esc_attr( $entur_quay_id ); ?>">
                            <div class="bus-departures-loading">
                                <span class="spinner"></span> Henter avganger...
                            </div>
                            <div class="bus-departures-data" style="display: none;">
                                <ul class="bus-departures-list"></ul>
                            </div>
                            <div class="bus-departures-error" style="display: none;">
                                Kunne ikke hente avganger
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
