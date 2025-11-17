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

// Get attributes from block
// In render.php, attributes are available as $attributes array
$chapter_id = isset( $attributes['chapterId'] ) && ! empty( $attributes['chapterId'] ) ? $attributes['chapterId'] : '';
$chapter_anchor = isset( $attributes['chapterAnchor'] ) && ! empty( $attributes['chapterAnchor'] ) ? $attributes['chapterAnchor'] : '';
$chapter_title = isset( $attributes['chapterTitle'] ) && ! empty( $attributes['chapterTitle'] ) ? $attributes['chapterTitle'] : '';

// If no chapter ID is set, generate one based on block ID
if ( empty( $chapter_id ) ) {
    $chapter_id = 'chapter-' . substr( $block['id'], 0, 8 );
}

// Use anchor as fallback for ID if provided
if ( empty( $chapter_anchor ) ) {
    $chapter_anchor = $chapter_id;
}

// Get block wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class'                => 'chapter',
    'id'                   => esc_attr( $chapter_anchor ),
    'data-chapter-id'      => esc_attr( $chapter_id ),
    'data-chapter-anchor'  => esc_attr( $chapter_anchor ),
    'data-chapter-title'   => esc_attr( $chapter_title ),
) );

?>

<section <?php echo $wrapper_attributes; ?>>
    <?php echo $content; ?>
</section>
