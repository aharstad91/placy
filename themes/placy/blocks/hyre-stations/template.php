<?php
/**
 * Hyre Stations Block Template
 *
 * Displays a list of Hyre car sharing stations in accordion format with live availability.
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
$section_title = get_field( 'section_title' ) ?: 'Bil, bildeling og taxi – fleksibilitet når du trenger det';
$section_description = get_field( 'section_description' ) ?: '';
$stations = get_field( 'stations' ) ?: array();

// Block wrapper
$block_id = 'hyre-stations-' . $block['id'];
$class_name = 'hyre-stations-block';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}
?>

<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $class_name ); ?>">
    <?php if ( $section_title ) : ?>
        <h2 class="hyre-section-title"><?php echo esc_html( $section_title ); ?></h2>
    <?php endif; ?>
    
    <?php if ( $section_description ) : ?>
        <p class="hyre-section-description"><?php echo esc_html( $section_description ); ?></p>
    <?php endif; ?>
    
    <?php if ( empty( $stations ) ) : ?>
        <?php if ( is_admin() ) : ?>
            <p class="hyre-stations-placeholder">Velg Hyre-stasjoner i blokkinnstillingene</p>
        <?php endif; ?>
    <?php else : ?>
        <div class="hyre-stations-list">
            <?php foreach ( $stations as $index => $station ) : 
                $poi = $station['station'] ?? null;
                if ( ! $poi ) continue;
                
                $poi_id = $poi->ID;
                $title = get_the_title( $poi_id );
                $walking_time = $station['walking_time'] ?? '';
                $hyre_station_id = get_field( 'hyre_station_id', $poi_id );
                
                // Get coordinates for map
                $coords = '';
                $lat = get_field( 'latitude', $poi_id );
                $lng = get_field( 'longitude', $poi_id );
                if ( $lat && $lng ) {
                    $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
                }
            ?>
                <div class="hyre-station-item" 
                     data-poi-id="<?php echo esc_attr( $poi_id ); ?>"
                     data-poi-title="<?php echo esc_attr( $title ); ?>"
                     <?php if ( $coords ) : ?>
                        data-poi-coords="<?php echo esc_attr( $coords ); ?>"
                     <?php endif; ?>
                     <?php if ( $hyre_station_id ) : ?>
                        data-hyre-station-id="<?php echo esc_attr( $hyre_station_id ); ?>"
                        data-show-hyre-availability="1"
                     <?php endif; ?>>
                    
                    <div class="hyre-station-badge">HYRE</div>
                    
                    <div class="hyre-station-header" role="button" tabindex="0" aria-expanded="false" aria-controls="hyre-content-<?php echo esc_attr( $block_id . '-' . $index ); ?>">
                        <div class="hyre-station-info">
                            <h4 class="hyre-station-name"><?php echo esc_html( $title ); ?></h4>
                            <?php if ( $walking_time ) : ?>
                                <span class="hyre-station-time"><?php echo esc_html( $walking_time ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="hyre-station-actions">
                            <button type="button" class="hyre-map-btn" onclick="event.stopPropagation(); showPOIOnMap(this.closest('.hyre-station-item'));">
                                Se på kart
                            </button>
                            <span class="hyre-accordion-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    
                    <div id="hyre-content-<?php echo esc_attr( $block_id . '-' . $index ); ?>" class="hyre-station-content" hidden>
                        <div class="hyre-availability" data-station-id="<?php echo esc_attr( $hyre_station_id ); ?>">
                            <div class="hyre-availability-loading">
                                <span class="spinner"></span> Henter tilgjengelighet...
                            </div>
                            <div class="hyre-availability-data" style="display: none;">
                                <div class="hyre-availability-item">
                                    <span class="hyre-availability-label">Ledige biler</span>
                                    <span class="hyre-availability-value hyre-cars">-</span>
                                </div>
                            </div>
                            <div class="hyre-availability-error" style="display: none;">
                                Kunne ikke hente tilgjengelighet
                            </div>
                        </div>
                        <a href="https://hyre.no" target="_blank" rel="noopener noreferrer" class="hyre-book-link">
                            Gå til Hyre for booking →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
