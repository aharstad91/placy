<?php
/**
 * Bysykkel Stations Block Template
 *
 * Displays a list of Trondheim Bysykkel stations in accordion format with live availability.
 * Based on Figma prototype design.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get block settings
$custom_title = get_field( 'custom_title' ) ?: 'Trondheim Bysykkel Stasjoner';
$stations = get_field( 'stations' ) ?: array();

// Block wrapper
$block_id = 'bysykkel-stations-' . $block['id'];
$class_name = 'bysykkel-stations-block';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}

// Get project coordinates for walking time display
$project = null;
global $post;
if ( $post ) {
    if ( get_post_type( $post ) === 'theme-story' ) {
        $project = get_field( 'parent_story', $post->ID );
    } elseif ( get_post_type( $post ) === 'project' ) {
        $project = $post;
    }
}
?>

<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $class_name ); ?>">
    <h3 class="bysykkel-stations-title"><?php echo esc_html( $custom_title ); ?></h3>
    
    <?php if ( empty( $stations ) ) : ?>
        <?php if ( is_admin() ) : ?>
            <p class="bysykkel-stations-placeholder">Velg bysykkelstasjoner i blokkinnstillingene</p>
        <?php endif; ?>
    <?php else : ?>
        <div class="bysykkel-stations-list">
            <?php foreach ( $stations as $index => $station ) : 
                $poi = $station['station'] ?? null;
                if ( ! $poi ) continue;
                
                $poi_id = $poi->ID;
                $title = get_the_title( $poi_id );
                $walking_time = $station['walking_time'] ?? '';
                $bysykkel_station_id = get_field( 'bysykkel_station_id', $poi_id );
                
                // Get coordinates for map
                $coords = '';
                $lat = get_field( 'latitude', $poi_id );
                $lng = get_field( 'longitude', $poi_id );
                if ( $lat && $lng ) {
                    $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
                }
            ?>
                <div class="bysykkel-station-item" 
                     data-poi-id="<?php echo esc_attr( $poi_id ); ?>"
                     data-poi-title="<?php echo esc_attr( $title ); ?>"
                     <?php if ( $coords ) : ?>
                        data-poi-coords="<?php echo esc_attr( $coords ); ?>"
                     <?php endif; ?>
                     <?php if ( $bysykkel_station_id ) : ?>
                        data-bysykkel-station-id="<?php echo esc_attr( $bysykkel_station_id ); ?>"
                        data-show-bike-availability="1"
                     <?php endif; ?>>
                    
                    <div class="bysykkel-station-header" role="button" tabindex="0" aria-expanded="false" aria-controls="bysykkel-content-<?php echo esc_attr( $block_id . '-' . $index ); ?>">
                        <div class="bysykkel-station-info">
                            <h4 class="bysykkel-station-name"><?php echo esc_html( $title ); ?></h4>
                            <?php if ( $walking_time ) : ?>
                                <span class="bysykkel-station-time"><?php echo esc_html( $walking_time ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="bysykkel-station-actions">
                            <button type="button" class="bysykkel-map-btn" onclick="event.stopPropagation(); showPOIOnMap(this.closest('.bysykkel-station-item'));">
                                Se på kart
                            </button>
                            <span class="bysykkel-accordion-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    
                    <div id="bysykkel-content-<?php echo esc_attr( $block_id . '-' . $index ); ?>" class="bysykkel-station-content" hidden>
                        <div class="bysykkel-availability" data-station-id="<?php echo esc_attr( $bysykkel_station_id ); ?>">
                            <div class="bysykkel-availability-loading">
                                <span class="spinner"></span> Henter tilgjengelighet...
                            </div>
                            <div class="bysykkel-availability-data" style="display: none;">
                                <div class="bysykkel-availability-item">
                                    <span class="bysykkel-availability-label">Ledige sykler</span>
                                    <span class="bysykkel-availability-value bysykkel-bikes">-</span>
                                </div>
                                <div class="bysykkel-availability-item">
                                    <span class="bysykkel-availability-label">Ledige plasser</span>
                                    <span class="bysykkel-availability-value bysykkel-docks">-</span>
                                </div>
                            </div>
                            <div class="bysykkel-availability-error" style="display: none;">
                                Kunne ikke hente tilgjengelighet
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
