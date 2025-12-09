<?php
/**
 * Chapter Text Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get ACF fields
$text_content    = get_field( 'text_content' );
$text_variant    = get_field( 'text_variant' ) ?: 'standard';
$max_width       = get_field( 'max_width' ) ?: 'normal';
$spacing_top     = get_field( 'spacing_top' ) ?: 'none';
$spacing_bottom  = get_field( 'spacing_bottom' ) ?: 'md';

// If no content, show placeholder in admin
if ( empty( $text_content ) ) {
    if ( is_admin() ) {
        echo '<div class="acf-block-placeholder" style="padding: 1rem; background: #f0f0f0; text-align: center; border: 1px dashed #ccc;">
            <p style="margin:0; color:#666;">Skriv inn tekst i sidepanelet.</p>
        </div>';
    }
    return;
}

// Build CSS classes
$classes = array(
    'pl-chapter-block',
    'pl-chapter-block--text',
    'pl-chapter-block--text-' . $text_variant,
    'pl-width-' . $max_width,
    'pl-space-top-' . $spacing_top,
    'pl-space-bottom-' . $spacing_bottom,
);

$wrapper_class = esc_attr( implode( ' ', $classes ) );
?>

<section class="<?php echo $wrapper_class; ?>">
    <div class="pl-chapter-block__content">
        <?php echo wp_kses_post( $text_content ); ?>
    </div>
</section>
