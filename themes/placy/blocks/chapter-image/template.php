<?php
/**
 * Chapter Image Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get ACF fields
$image           = get_field( 'image' );
$alt_text        = get_field( 'alt_text' );
$caption         = get_field( 'caption' );
$display_style   = get_field( 'display_style' ) ?: 'standard';
$aspect_ratio    = get_field( 'aspect_ratio' ) ?: 'original';
$border_style    = get_field( 'border_style' ) ?: 'rounded';
$click_action    = get_field( 'click_action' ) ?: 'none';
$link_url        = get_field( 'link_url' );
$spacing_top     = get_field( 'spacing_top' ) ?: 'none';
$spacing_bottom  = get_field( 'spacing_bottom' ) ?: 'md';

// If no image, show placeholder in admin
if ( empty( $image ) ) {
    if ( is_admin() ) {
        echo '<div class="acf-block-placeholder" style="padding: 2rem; background: #f0f0f0; text-align: center; border: 1px dashed #ccc;">
            <p style="margin:0; color:#666;">Velg et bilde i sidepanelet.</p>
        </div>';
    }
    return;
}

// Get image URL and alt
$image_url = is_array( $image ) ? $image['url'] : $image;
$image_alt = ! empty( $alt_text ) ? $alt_text : ( is_array( $image ) ? $image['alt'] : '' );

// Build CSS classes
$classes = array(
    'pl-chapter-block',
    'pl-chapter-block--image',
    'pl-chapter-block--image-' . $display_style,
    'pl-ratio-' . $aspect_ratio,
    'pl-border-' . $border_style,
    'pl-space-top-' . $spacing_top,
    'pl-space-bottom-' . $spacing_bottom,
);

$wrapper_class = esc_attr( implode( ' ', $classes ) );
?>

<figure class="<?php echo $wrapper_class; ?>">
    <?php if ( $click_action === 'link' && ! empty( $link_url ) ) : ?>
        <a href="<?php echo esc_url( $link_url ); ?>" class="pl-chapter-block__image-link">
            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" class="pl-chapter-block__image-element" />
        </a>
    <?php elseif ( $click_action === 'lightbox' ) : ?>
        <a href="<?php echo esc_url( $image_url ); ?>" class="pl-chapter-block__image-link" data-lightbox="chapter-image">
            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" class="pl-chapter-block__image-element" />
        </a>
    <?php else : ?>
        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" class="pl-chapter-block__image-element" />
    <?php endif; ?>
    
    <?php if ( $caption ) : ?>
        <figcaption class="pl-chapter-block__caption"><?php echo esc_html( $caption ); ?></figcaption>
    <?php endif; ?>
</figure>
