<?php
/**
 * Chapter Wrapper Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get the chapter ID from block attributes
$chapter_id = isset( $block['attrs']['chapterId'] ) ? $block['attrs']['chapterId'] : '';

// If no chapter ID is set, generate one based on block ID
if ( empty( $chapter_id ) ) {
    $chapter_id = 'chapter-' . substr( $block['id'], 0, 8 );
}

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class'           => 'chapter',
    'data-chapter-id' => esc_attr( $chapter_id ),
) );

?>

<section <?php echo $wrapper_attributes; ?>>
    <?php echo $content; ?>
</section>
