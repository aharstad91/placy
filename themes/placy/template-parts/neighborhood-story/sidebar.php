<?php
/**
 * Neighborhood Story - Sidebar Template
 * 
 * Fixed sidebar with:
 * - Header (title + address)
 * - Chapter navigation
 * - Global settings (Travel Mode + Time Budget)
 * - Open full map button
 *
 * @package Placy
 * @since 1.0.0
 * 
 * @param int $project_id Project post ID
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get project ID
$project_id = $project_id ?? get_the_ID();

// Get sidebar header
$sidebar_header = get_field( 'sidebar_header', $project_id );
$header_title = $sidebar_header['title'] ?? 'Story Index';
$header_subtitle = $sidebar_header['subtitle'] ?? get_field( 'project_address', $project_id );

// Get navigation items
$nav_items = get_field( 'sidebar_nav_items', $project_id );

// Get global settings defaults
$default_travel_mode = get_field( 'default_travel_mode', $project_id ) ?: 'walk';
$default_time_budget = get_field( 'default_time_budget', $project_id ) ?: '10';
$enable_global_map = get_field( 'enable_global_map', $project_id );

// Icon mapping (emoji to SVG class)
$icon_map = array(
    'train'     => 'icon-train',
    'bus'       => 'icon-bus',
    'bike'      => 'icon-bike',
    'car'       => 'icon-car',
    'walk'      => 'icon-walk',
    'food'      => 'icon-food',
    'coffee'    => 'icon-coffee',
    'shopping'  => 'icon-shopping',
    'hotel'     => 'icon-hotel',
    'meeting'   => 'icon-meeting',
    'nature'    => 'icon-nature',
    'gym'       => 'icon-gym',
    'culture'   => 'icon-culture',
    'nightlife' => 'icon-nightlife',
    'summary'   => 'icon-summary',
    'services'  => 'icon-services',
);

// Travel mode labels
$travel_labels = array(
    'walk' => 'Til fots',
    'bike' => 'Sykkel',
    'car'  => 'Bil',
);
?>

<aside class="ns-sidebar" aria-label="Story navigation">
    <!-- Header -->
    <div class="ns-sidebar-header">
        <div class="ns-sidebar-logo">
            <div class="ns-sidebar-logo-icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                </svg>
            </div>
            <div class="ns-sidebar-logo-text">
                <h2 class="ns-sidebar-title"><?php echo esc_html( $header_title ); ?></h2>
                <?php if ( $header_subtitle ) : ?>
                    <p class="ns-sidebar-subtitle"><?php echo esc_html( $header_subtitle ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="ns-sidebar-nav" aria-label="Chapters">
        <div class="ns-sidebar-nav-label">Chapters</div>
        
        <?php if ( $nav_items ) : ?>
            <ul class="ns-sidebar-nav-list">
                <?php foreach ( $nav_items as $item ) : 
                    $icon_class = $icon_map[ $item['icon'] ] ?? 'icon-walk';
                    $nav_type = $item['type'] ?? 'chapter';
                ?>
                    <li class="ns-sidebar-nav-item">
                        <button 
                            type="button"
                            class="ns-sidebar-nav-button"
                            data-nav-anchor="<?php echo esc_attr( $item['anchor'] ); ?>"
                            data-nav-type="<?php echo esc_attr( $nav_type ); ?>"
                        >
                            <span class="ns-nav-icon <?php echo esc_attr( $icon_class ); ?>"></span>
                            <span class="ns-nav-label"><?php echo esc_html( $item['label'] ); ?></span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <!-- Fallback: Auto-generate from story_chapters -->
            <?php 
            $chapters = get_field( 'story_chapters', $project_id );
            if ( $chapters ) : 
            ?>
                <ul class="ns-sidebar-nav-list">
                    <?php foreach ( $chapters as $chapter ) : 
                        $theme_story = $chapter['theme_story'];
                        $title = $chapter['front_title'] ?: get_the_title( $theme_story );
                        $anchor = $chapter['anchor_id'];
                        $icon_class = $icon_map[ $chapter['icon'] ] ?? 'icon-walk';
                    ?>
                        <li class="ns-sidebar-nav-item">
                            <button 
                                type="button"
                                class="ns-sidebar-nav-button"
                                data-nav-anchor="<?php echo esc_attr( $anchor ); ?>"
                                data-nav-type="chapter"
                            >
                                <span class="ns-nav-icon <?php echo esc_attr( $icon_class ); ?>"></span>
                                <span class="ns-nav-label"><?php echo esc_html( $title ); ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <!-- Global Settings -->
    <div class="ns-sidebar-settings">
        <div class="ns-sidebar-settings-label">Global settings</div>
        
        <?php 
        // Shared Travel Controls Component
        get_template_part( 'template-parts/components/travel-controls', null, array(
            'default_mode' => $default_travel_mode,
            'default_time' => $default_time_budget,
            'context'      => 'sidebar',
        ) );
        ?>

        <!-- Open Full Map Button -->
        <?php if ( $enable_global_map !== false ) : ?>
            <button type="button" class="ns-open-map-button" data-open-global-map>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                </svg>
                <span>Open full map</span>
            </button>
        <?php endif; ?>
    </div>
</aside>
