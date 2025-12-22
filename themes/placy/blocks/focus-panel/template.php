<?php
/**
 * Focus Panel Block Template
 *
 * Apple-inspired centered modal/drawer with blur overlay
 * for detailed content, "read more" sections, and supplementary info.
 *
 * @param array $block The block settings and attributes.
 * @param string $content The block inner HTML (empty).
 * @param bool $is_preview True during AJAX preview.
 * @param int|string $post_id The post ID this block is saved to.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generate unique ID for this block instance
$panel_id = 'focuspanel-' . uniqid();

// Trigger settings
$trigger_type = get_field( 'trigger_type' ) ?: 'button';
$trigger_label = get_field( 'trigger_label' ) ?: 'Les mer';
$trigger_icon = get_field( 'trigger_icon' ) ?: false;
$trigger_alignment = get_field( 'trigger_alignment' ) ?: 'left';
$trigger_size = get_field( 'trigger_size' ) ?: 'md';
$trigger_variant = get_field( 'trigger_variant' ) ?: 'default';
$trigger_helper_text = get_field( 'trigger_helper_text' ) ?: '';

// Panel content
$panel_title = get_field( 'panel_title' ) ?: '';
$panel_kicker = get_field( 'panel_kicker' ) ?: '';
$panel_body = get_field( 'panel_body' ) ?: '';
$panel_media = get_field( 'panel_media' );
$panel_media_caption = get_field( 'panel_media_caption' ) ?: '';
$panel_footer_cta_label = get_field( 'panel_footer_cta_label' ) ?: '';
$panel_footer_cta_url = get_field( 'panel_footer_cta_url' ) ?: '';

// Layout settings
$panel_width = get_field( 'panel_width' ) ?: 'md';
$panel_height_mode = get_field( 'panel_height_mode' ) ?: 'auto_with_max';
$overlay_blur_strength = get_field( 'overlay_blur_strength' );
$overlay_dim_strength = get_field( 'overlay_dim_strength' );
$close_on_overlay_click = get_field( 'close_on_overlay_click' );
$show_close_button = get_field( 'show_close_button' );

// Handle defaults for select/boolean fields
if ( $overlay_blur_strength === '' || $overlay_blur_strength === null ) {
    $overlay_blur_strength = 20;
}
if ( $overlay_dim_strength === '' || $overlay_dim_strength === null ) {
    $overlay_dim_strength = 55;
}
if ( $close_on_overlay_click === '' || $close_on_overlay_click === null ) {
    $close_on_overlay_click = true;
}
if ( $show_close_button === '' || $show_close_button === null ) {
    $show_close_button = true;
}

// Analytics events (optional)
$event_name_open = get_field( 'event_name_open' ) ?: 'focuspanel_open';
$event_name_close = get_field( 'event_name_close' ) ?: 'focuspanel_close';

// Block wrapper classes
$class_name = 'focuspanel';
if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}
$class_name .= ' focuspanel--align-' . $trigger_alignment;

// Title ID for aria-labelledby
$title_id = $panel_id . '-title';

// Width classes map
$width_classes = array(
    'md' => 'focuspanel__panel--width-md',
    'lg' => 'focuspanel__panel--width-lg',
    'xl' => 'focuspanel__panel--width-xl',
);

// Height mode classes
$height_classes = array(
    'auto_with_max' => 'focuspanel__panel--height-auto',
    'fuller' => 'focuspanel__panel--height-fuller',
);

// Admin preview placeholder
if ( empty( $panel_title ) && empty( $panel_body ) ) {
    if ( is_admin() ) {
        echo '<div class="focuspanel-placeholder" style="padding: 32px; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); color: #334155; text-align: center; border-radius: 12px; border: 2px dashed #94a3b8;">
            <i class="fa-solid fa-expand" style="font-size: 36px; margin-bottom: 12px; opacity: 0.6;"></i>
            <h4 style="margin: 0 0 6px; font-size: 16px; font-weight: 600;">Focus Panel</h4>
            <p style="margin: 0; opacity: 0.7; font-size: 13px;">Legg til tittel og innhold i blokkinnstillingene</p>
        </div>';
        return;
    }
    return;
}

// Build trigger classes
$trigger_classes = array(
    'focuspanel__trigger',
    'focuspanel__trigger--' . $trigger_type,
    'focuspanel__trigger--size-' . $trigger_size,
    'focuspanel__trigger--' . $trigger_variant,
);

// Get media data
$media_url = '';
$media_alt = '';
if ( $panel_media ) {
    $media_url = $panel_media['sizes']['large'] ?? $panel_media['url'];
    $media_alt = $panel_media['alt'] ?? '';
}

// Build panel classes
$panel_classes = array(
    'focuspanel__panel',
    $width_classes[ $panel_width ] ?? 'focuspanel__panel--width-md',
    $height_classes[ $panel_height_mode ] ?? 'focuspanel__panel--height-auto',
);

?>
<div class="<?php echo esc_attr( $class_name ); ?>" data-focuspanel-block>
    
    <?php // Trigger Button ?>
    <div class="focuspanel__trigger-wrapper">
        <button 
            type="button"
            class="<?php echo esc_attr( implode( ' ', $trigger_classes ) ); ?>"
            data-focuspanel-open="<?php echo esc_attr( $panel_id ); ?>"
            aria-haspopup="dialog"
            aria-controls="<?php echo esc_attr( $panel_id ); ?>"
            aria-expanded="false"
        >
            <?php if ( $trigger_icon ) : ?>
                <span class="focuspanel__trigger-icon" aria-hidden="true">
                    <i class="fa-solid fa-plus"></i>
                </span>
            <?php endif; ?>
            <span class="focuspanel__trigger-label"><?php echo esc_html( $trigger_label ); ?></span>
        </button>
        
        <?php if ( $trigger_helper_text ) : ?>
            <span class="focuspanel__trigger-helper"><?php echo esc_html( $trigger_helper_text ); ?></span>
        <?php endif; ?>
    </div>
    
    <?php // Sticky Close Button - positioned at block level (outside overlay) for true fixed positioning ?>
    <?php if ( $show_close_button ) : ?>
        <button 
            type="button" 
            class="focuspanel__close focuspanel__close--sticky"
            data-focuspanel-close="<?php echo esc_attr( $panel_id ); ?>"
            aria-label="Lukk panel"
            hidden
        >
            <span aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </button>
    <?php endif; ?>
    
    <?php // Overlay + Panel (hidden by default) ?>
    <div 
        class="focuspanel__overlay" 
        data-focuspanel-overlay="<?php echo esc_attr( $panel_id ); ?>"
        data-close-on-click="<?php echo $close_on_overlay_click ? 'true' : 'false'; ?>"
        data-event-open="<?php echo esc_attr( $event_name_open ); ?>"
        data-event-close="<?php echo esc_attr( $event_name_close ); ?>"
        hidden
        style="--overlay-blur: <?php echo intval( $overlay_blur_strength ); ?>px; --overlay-dim: <?php echo intval( $overlay_dim_strength ) / 100; ?>;"
    >
        
        <div 
            class="<?php echo esc_attr( implode( ' ', $panel_classes ) ); ?>"
            role="dialog"
            aria-modal="true"
            id="<?php echo esc_attr( $panel_id ); ?>"
            aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
            tabindex="-1"
            data-focuspanel-panel
        >
            <?php // Panel Header ?>
            <header class="focuspanel__header">
                <div class="focuspanel__header-content">
                    <?php if ( $panel_kicker ) : ?>
                        <span class="focuspanel__kicker"><?php echo esc_html( $panel_kicker ); ?></span>
                    <?php endif; ?>
                    <h2 id="<?php echo esc_attr( $title_id ); ?>" class="focuspanel__title">
                        <?php echo esc_html( $panel_title ); ?>
                    </h2>
                </div>
            </header>
            
            <?php // Panel Body (scrollable) ?>
            <div class="focuspanel__body">
                <?php if ( $media_url ) : ?>
                    <figure class="focuspanel__media">
                        <img 
                            src="<?php echo esc_url( $media_url ); ?>" 
                            alt="<?php echo esc_attr( $media_alt ); ?>"
                            loading="lazy"
                        />
                        <?php if ( $panel_media_caption ) : ?>
                            <figcaption class="focuspanel__media-caption">
                                <?php echo esc_html( $panel_media_caption ); ?>
                            </figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endif; ?>
                
                <div class="focuspanel__content">
                    <?php echo wp_kses_post( $panel_body ); ?>
                </div>
            </div>
            
            <?php // Panel Footer (optional CTA) ?>
            <?php if ( $panel_footer_cta_label && $panel_footer_cta_url ) : ?>
                <footer class="focuspanel__footer">
                    <a 
                        href="<?php echo esc_url( $panel_footer_cta_url ); ?>" 
                        class="focuspanel__cta"
                    >
                        <?php echo esc_html( $panel_footer_cta_label ); ?>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </footer>
            <?php endif; ?>
        </div>
    </div>
</div>
