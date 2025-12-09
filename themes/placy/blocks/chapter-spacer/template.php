<?php
/**
 * Chapter Spacer Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get ACF fields
$size            = get_field( 'size' ) ?: 'md';
$visibility      = get_field( 'visibility' );

// Default visibility to both if not set
if ( empty( $visibility ) ) {
    $visibility = array( 'desktop', 'mobile' );
}

// Build CSS classes
$classes = array(
    'pl-chapter-block',
    'pl-chapter-block--spacer',
    'pl-chapter-block--spacer-' . $size,
);

// Add visibility classes
if ( ! in_array( 'desktop', $visibility, true ) ) {
    $classes[] = 'pl-hide-desktop';
}
if ( ! in_array( 'mobile', $visibility, true ) ) {
    $classes[] = 'pl-hide-mobile';
}

$wrapper_class = esc_attr( implode( ' ', $classes ) );
?>

<div class="<?php echo $wrapper_class; ?>" aria-hidden="true"></div>
