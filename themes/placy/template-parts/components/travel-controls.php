<?php
/**
 * Shared Travel Controls Component
 * 
 * Unified Travel Mode and Time Budget controls used across:
 * - Sidebar
 * - Chapter Modal
 * - Global Map Modal
 *
 * @package Placy
 * @since 1.0.0
 * 
 * @param string $default_mode  Default travel mode: 'walk', 'bike', 'car'
 * @param string $default_time  Default time budget: '5', '10', '15'
 * @param string $context       Context identifier for CSS scoping (optional)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get passed args or defaults
$default_mode = $args['default_mode'] ?? 'walk';
$default_time = $args['default_time'] ?? '10';
$context      = $args['context'] ?? '';

// Travel mode configuration
$travel_modes = array(
    'walk' => array(
        'label' => 'Til fots',
        'icon'  => 'icon-walk',
        'emoji' => '🚶',
    ),
    'bike' => array(
        'label' => 'Sykkel',
        'icon'  => 'icon-bike',
        'emoji' => '🚲',
    ),
    'car' => array(
        'label' => 'Bil',
        'icon'  => 'icon-car',
        'emoji' => '🚗',
    ),
);

// Time budget options
$time_options = array(
    '5'  => '≤ 5 min',
    '10' => '10 min',
    '15' => '15 min',
);

$wrapper_class = 'ns-travel-controls';
if ( $context ) {
    $wrapper_class .= ' ns-travel-controls--' . esc_attr( $context );
}
?>

<div class="<?php echo esc_attr( $wrapper_class ); ?>" data-travel-controls>
    <!-- Travel Mode -->
    <div class="ns-tc-group">
        <div class="ns-tc-header">
            <span class="ns-tc-label">Travel Mode</span>
            <button type="button" class="ns-tc-info" aria-label="Travel mode info" title="Choose how you travel to calculate distance times">
                <svg class="ns-tc-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                    <path stroke-linecap="round" stroke-width="1.5" d="M12 16v-4m0-4h.01"/>
                </svg>
            </button>
        </div>
        <div class="ns-tc-toggle-group" role="radiogroup" aria-label="Travel mode">
            <?php foreach ( $travel_modes as $mode_key => $mode_data ) : 
                $is_active = ( $mode_key === $default_mode );
            ?>
                <button 
                    type="button" 
                    class="ns-tc-toggle-btn <?php echo $is_active ? 'active' : ''; ?>"
                    data-travel-mode="<?php echo esc_attr( $mode_key ); ?>"
                    role="radio"
                    aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>"
                    aria-label="<?php echo esc_attr( $mode_data['label'] ); ?>"
                >
                    <span class="ns-tc-toggle-icon <?php echo esc_attr( $mode_data['icon'] ); ?>" aria-hidden="true"></span>
                    <span class="ns-tc-toggle-text"><?php echo esc_html( $mode_data['label'] ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Time Budget -->
    <div class="ns-tc-group">
        <div class="ns-tc-header">
            <span class="ns-tc-label">Time Budget</span>
            <button type="button" class="ns-tc-info" aria-label="Time budget info" title="Highlights places within the selected time budget. Nothing is hidden.">
                <svg class="ns-tc-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                    <path stroke-linecap="round" stroke-width="1.5" d="M12 16v-4m0-4h.01"/>
                </svg>
            </button>
        </div>
        <div class="ns-tc-toggle-group" role="radiogroup" aria-label="Time budget">
            <?php foreach ( $time_options as $time_key => $time_label ) : 
                $is_active = ( $time_key === $default_time );
            ?>
                <button 
                    type="button" 
                    class="ns-tc-toggle-btn <?php echo $is_active ? 'active' : ''; ?>"
                    data-time-budget="<?php echo esc_attr( $time_key ); ?>"
                    role="radio"
                    aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>"
                >
                    <?php echo esc_html( $time_label ); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
