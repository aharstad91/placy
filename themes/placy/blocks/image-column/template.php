<?php
/**
 * Image Column Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get the images from ACF fields
$image_1 = get_field( 'image_1' );
$image_2 = get_field( 'image_2' );

// Get block wrapper attributes
$block_id = 'image-column-' . $block['id'];
$class_name = 'image-column-block';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}

?>

<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $class_name ); ?> w-full my-8">
    <div class="flex gap-4" style="height: 20rem;">
        <?php if ( $image_1 ) : ?>
            <div class="w-3/5 overflow-hidden rounded-lg">
                <img 
                    src="<?php echo esc_url( $image_1['url'] ); ?>" 
                    alt="<?php echo esc_attr( $image_1['alt'] ?: 'Image 1' ); ?>"
                    class="w-full h-full object-cover"
                    loading="lazy"
                />
            </div>
        <?php endif; ?>
        
        <?php if ( $image_2 ) : ?>
            <div class="w-2/5 overflow-hidden rounded-lg">
                <img 
                    src="<?php echo esc_url( $image_2['url'] ); ?>" 
                    alt="<?php echo esc_attr( $image_2['alt'] ?: 'Image 2' ); ?>"
                    class="w-full h-full object-cover"
                    loading="lazy"
                />
            </div>
        <?php endif; ?>
    </div>
</div>
