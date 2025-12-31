<?php
/**
 * Project Sidebar - Navigation + Global Settings
 * 
 * Includes:
 * - Manual nav items from ACF repeater
 * - Global Travel Mode dropdown
 * - Global Time Budget dropdown  
 * - Open Full Map button
 *
 * @package Placy
 * @since 1.0.0
 */

$project_id = get_the_ID();

// Get sidebar config
$sidebar_header = get_field( 'sidebar_header', $project_id );
$nav_items = get_field( 'sidebar_nav_items', $project_id );

// Get global defaults
$default_travel_mode = get_field( 'default_travel_mode', $project_id ) ?: 'walk';
$default_time_budget = get_field( 'default_time_budget', $project_id ) ?: '15';

// Get project info for header
$project_title = $sidebar_header['title'] ?? get_the_title();
$project_address = $sidebar_header['address'] ?? get_field( 'project_address', $project_id );

// Travel mode labels
$travel_labels = array(
    'walk' => 'Til fots',
    'bike' => 'Sykkel', 
    'car'  => 'Bil'
);
?>

<aside class="project-sidebar" id="project-sidebar">
    <!-- Header -->
    <div class="project-sidebar__header">
        <div class="project-sidebar__icon">
            <i class="fas fa-map"></i>
        </div>
        <div class="project-sidebar__title-group">
            <h2 class="project-sidebar__title">Story Index</h2>
            <?php if ( $project_address ) : ?>
                <p class="project-sidebar__address"><?php echo esc_html( $project_address ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation -->
    <?php if ( $nav_items ) : ?>
    <nav class="project-sidebar__nav">
        <div class="project-sidebar__nav-label">Chapters</div>
        <?php foreach ( $nav_items as $item ) : 
            $anchor = $item['anchor_id'] ?? '';
            $label = $item['label'] ?? '';
            $icon = $item['icon'] ?? 'circle';
            $type = $item['type'] ?? 'scroll';
        ?>
            <button 
                class="project-sidebar__nav-item"
                data-nav-anchor="<?php echo esc_attr( $anchor ); ?>"
                data-nav-type="<?php echo esc_attr( $type ); ?>"
            >
                <i class="fas fa-<?php echo esc_attr( $icon ); ?>"></i>
                <span><?php echo esc_html( $label ); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <!-- Global Settings -->
    <div class="project-sidebar__settings">
        <div class="project-sidebar__settings-label">Global settings</div>
        
        <?php 
        // Shared Travel Controls Component
        get_template_part( 'template-parts/components/travel-controls', null, array(
            'default_mode' => $default_travel_mode,
            'default_time' => $default_time_budget,
            'context'      => 'sidebar',
        ) );
        ?>

        <!-- Open Full Map Button -->
        <button class="project-sidebar__map-btn" data-open-global-map>
            <i class="fas fa-map"></i>
            Open full map
        </button>
    </div>
</aside>

<script>
// Initialize global state from defaults
if (typeof window.PlacyGlobalState === 'undefined') {
    window.PlacyGlobalState = {
        travelMode: '<?php echo esc_js( $default_travel_mode ); ?>',
        timeBudget: <?php echo (int) $default_time_budget; ?>
    };
}
</script>
