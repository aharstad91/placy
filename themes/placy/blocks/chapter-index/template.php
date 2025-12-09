<?php
/**
 * Chapter Index Block Template
 * 
 * Displays navigation pills for sections within a chapter.
 * Uses ACF repeater field for index items.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get ACF fields
$index_items = get_field( 'index_items' );

// If no items, don't render anything
if ( empty( $index_items ) ) {
    return;
}

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'chapter-index',
) );

?>

<div <?php echo $wrapper_attributes; ?>>
    <div class="chapter-index-pills">
        <?php foreach ( $index_items as $item ) : 
            $label = $item['label'] ?? '';
            $anchor = $item['anchor'] ?? '';
            
            // Only require label, anchor is optional
            if ( empty( $label ) ) {
                continue;
            }
            
            // Ensure anchor doesn't start with #
            $anchor = ltrim( $anchor, '#' );
            
            // If no anchor, render as span instead of link
            if ( empty( $anchor ) ) :
        ?>
            <span class="chapter-index-pill chapter-index-pill--static">
                <span class="chapter-index-dot"></span>
                <span class="chapter-index-label"><?php echo esc_html( $label ); ?></span>
            </span>
        <?php else : ?>
            <a href="#<?php echo esc_attr( $anchor ); ?>" class="chapter-index-pill" data-anchor="<?php echo esc_attr( $anchor ); ?>">
                <span class="chapter-index-dot"></span>
                <span class="chapter-index-label"><?php echo esc_html( $label ); ?></span>
            </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
