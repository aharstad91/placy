<?php
/**
 * Chapter Heading Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get ACF fields
$heading_text    = get_field( 'heading_text' );
$heading_level   = get_field( 'heading_level' ) ?: 'h2';
$heading_style   = get_field( 'heading_style' ) ?: 'standard';
$eyebrow         = get_field( 'eyebrow' );
$align           = get_field( 'align' ) ?: 'left';
$spacing_top     = get_field( 'spacing_top' ) ?: 'none';
$spacing_bottom  = get_field( 'spacing_bottom' ) ?: 'md';
$anchor_id       = get_field( 'anchor_id' );

// Layout fields (col-span)
$col_d = get_field( 'layout_col_span_desktop' ) ?: '12';
$col_m = get_field( 'layout_col_span_mobile' ) ?: '12';
$layout_classes = sprintf( 'pl-col-d-%s pl-col-m-%s', esc_attr( $col_d ), esc_attr( $col_m ) );

// If no heading text, show placeholder in admin
if ( empty( $heading_text ) ) {
    if ( is_admin() ) {
        echo '<div class="acf-block-placeholder" style="padding: 1rem; background: #f0f0f0; text-align: center; border: 1px dashed #ccc;">
            <p style="margin:0; color:#666;">Skriv inn overskriftstekst i sidepanelet.</p>
        </div>';
    }
    return;
}

// Build CSS classes
$classes = array(
    'pl-chapter-block',
    'pl-chapter-block--heading',
    'pl-chapter-block--heading-' . $heading_style,
    'pl-align-' . $align,
    'pl-space-top-' . $spacing_top,
    'pl-space-bottom-' . $spacing_bottom,
    $layout_classes,
);

// Build wrapper attributes
$wrapper_id = ! empty( $anchor_id ) ? 'id="' . esc_attr( $anchor_id ) . '"' : '';
$wrapper_class = esc_attr( implode( ' ', $classes ) );

// Sanitize heading tag
$allowed_tags = array( 'h2', 'h3', 'h4' );
$tag = in_array( $heading_level, $allowed_tags, true ) ? $heading_level : 'h2';
?>

<section class="<?php echo $wrapper_class; ?>" <?php echo $wrapper_id; ?>>
    <?php if ( $eyebrow ) : ?>
        <p class="pl-chapter-block__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
    <?php endif; ?>
    
    <<?php echo $tag; ?> class="pl-chapter-block__heading">
        <?php echo esc_html( $heading_text ); ?>
    </<?php echo $tag; ?>>
</section>
