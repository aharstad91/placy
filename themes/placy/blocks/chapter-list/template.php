<?php
/**
 * Chapter List Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get ACF fields
$list_title      = get_field( 'list_title' );
$list_type       = get_field( 'list_type' ) ?: 'unordered';
$items           = get_field( 'items' );
$columns         = get_field( 'columns' ) ?: '1';
$spacing_top     = get_field( 'spacing_top' ) ?: 'none';
$spacing_bottom  = get_field( 'spacing_bottom' ) ?: 'md';

// If no items, show placeholder in admin
if ( empty( $items ) ) {
    if ( is_admin() ) {
        echo '<div class="acf-block-placeholder" style="padding: 1rem; background: #f0f0f0; text-align: center; border: 1px dashed #ccc;">
            <p style="margin:0; color:#666;">Legg til listepunkter i sidepanelet.</p>
        </div>';
    }
    return;
}

// Build CSS classes
$classes = array(
    'pl-chapter-block',
    'pl-chapter-block--list',
    'pl-chapter-block--list-' . $list_type,
    'pl-chapter-block--cols-' . $columns,
    'pl-space-top-' . $spacing_top,
    'pl-space-bottom-' . $spacing_bottom,
);

$wrapper_class = esc_attr( implode( ' ', $classes ) );

// Icon mapping
$icon_map = array(
    'check'  => '<i class="fas fa-check"></i>',
    'arrow'  => '<i class="fas fa-arrow-right"></i>',
    'info'   => '<i class="fas fa-info-circle"></i>',
    'dot'    => '<span class="pl-list-dot"></span>',
);
?>

<section class="<?php echo $wrapper_class; ?>">
    <?php if ( $list_title ) : ?>
        <p class="pl-chapter-block__list-title"><?php echo esc_html( $list_title ); ?></p>
    <?php endif; ?>

    <?php if ( $list_type === 'ordered' ) : ?>
        <ol class="pl-chapter-block__list">
            <?php foreach ( $items as $item ) : 
                $item_text = $item['item_text'] ?? '';
            ?>
                <li class="pl-chapter-block__list-item">
                    <?php echo wp_kses_post( $item_text ); ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php else : ?>
        <ul class="pl-chapter-block__list">
            <?php foreach ( $items as $item ) : 
                $item_text = $item['item_text'] ?? '';
                $item_icon = $item['item_icon'] ?? 'none';
                $icon_html = isset( $icon_map[ $item_icon ] ) ? $icon_map[ $item_icon ] : '';
            ?>
                <li class="pl-chapter-block__list-item pl-chapter-block__list-item--icon-<?php echo esc_attr( $item_icon ); ?>">
                    <?php if ( $icon_html ) : ?>
                        <span class="pl-chapter-block__list-icon"><?php echo $icon_html; ?></span>
                    <?php endif; ?>
                    <span class="pl-chapter-block__list-text"><?php echo wp_kses_post( $item_text ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
