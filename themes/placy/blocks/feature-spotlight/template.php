<?php
/**
 * Feature Spotlight Block Template
 *
 * Apple-style "Ta en nærmere titt" component with expandable items
 * and swappable background images.
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

// Get block settings
$title = get_field( 'title' ) ?: '';
$subtitle = get_field( 'subtitle' ) ?: '';
$height_mode = get_field( 'height_mode' ) ?: '66vh';
$allow_list_scroll = get_field( 'allow_list_scroll' ) ?: false;
$initial_open_index = get_field( 'initial_open_index' );
$show_close = get_field( 'show_close' );
$show_arrows = get_field( 'show_arrows' );
$items = get_field( 'items' ) ?: array();

// Handle defaults (ACF returns empty string for unset booleans)
if ( $show_close === '' || $show_close === null ) {
    $show_close = true;
}
if ( $show_arrows === '' || $show_arrows === null ) {
    $show_arrows = true;
}

// Block wrapper
$block_id = 'feature-spotlight-' . $block['id'];
$class_name = 'feature-spotlight';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}

// Height mode class
$class_name .= ' feature-spotlight--height-' . str_replace( 'vh', '', $height_mode );

if ( $allow_list_scroll ) {
    $class_name .= ' feature-spotlight--scrollable';
}

// Prepare items data for JavaScript
$items_data = array();
foreach ( $items as $index => $item ) {
    $bg_image = $item['bg_image'];
    $media_image = $item['media_image'];
    
    $items_data[] = array(
        'label'       => $item['label'] ?? '',
        'titlePrefix' => $item['title_prefix'] ?? '',
        'body'        => $item['body'] ?? '',
        'mediaImage'  => $media_image ? array(
            'src' => $media_image['sizes']['large'] ?? $media_image['url'],
            'alt' => $media_image['alt'] ?? '',
        ) : null,
        'bgImage'     => $bg_image ? array(
            'src' => $bg_image['sizes']['2048x2048'] ?? $bg_image['sizes']['large'] ?? $bg_image['url'],
        ) : null,
        'bgPosition'  => $item['bg_position'] ?? 'right center',
        'footnote'    => $item['footnote'] ?? '',
    );
}

// Props for JavaScript
$props = array(
    'title'            => $title,
    'subtitle'         => $subtitle,
    'heightMode'       => $height_mode,
    'allowListScroll'  => $allow_list_scroll,
    'initialOpenIndex' => is_numeric( $initial_open_index ) ? intval( $initial_open_index ) : null,
    'showClose'        => $show_close,
    'showArrows'       => $show_arrows,
    'items'            => $items_data,
);

// Admin preview placeholder
if ( empty( $items ) ) {
    if ( is_admin() ) {
        echo '<div class="feature-spotlight-placeholder" style="padding: 40px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; text-align: center; border-radius: 12px;">
            <i class="fa-solid fa-eye" style="font-size: 48px; margin-bottom: 16px; opacity: 0.7;"></i>
            <h4 style="margin: 0 0 8px; font-size: 18px;">Feature Spotlight</h4>
            <p style="margin: 0; opacity: 0.7; font-size: 14px;">Legg til elementer i blokkinnstillingene for å se forhåndsvisning</p>
        </div>';
    }
    return;
}

// Get first item's background for initial state
$first_bg = ! empty( $items_data[0]['bgImage'] ) ? $items_data[0]['bgImage']['src'] : '';
$initial_index = is_numeric( $initial_open_index ) ? intval( $initial_open_index ) : null;
$active_bg = $initial_index !== null && isset( $items_data[ $initial_index ]['bgImage'] ) 
    ? $items_data[ $initial_index ]['bgImage']['src'] 
    : $first_bg;

?>

<div 
    id="<?php echo esc_attr( $block_id ); ?>" 
    class="<?php echo esc_attr( $class_name ); ?>"
    data-props="<?php echo esc_attr( wp_json_encode( $props ) ); ?>"
>
    <!-- Background layer -->
    <div class="feature-spotlight__bg" aria-hidden="true">
        <?php foreach ( $items_data as $index => $item ) : 
            if ( ! empty( $item['bgImage'] ) ) :
                $is_active = ( $initial_index !== null && $index === $initial_index ) || ( $initial_index === null && $index === 0 );
        ?>
        <div 
            class="feature-spotlight__bg-image<?php echo $is_active ? ' is-active' : ''; ?>"
            data-index="<?php echo esc_attr( $index ); ?>"
            style="background-image: url('<?php echo esc_url( $item['bgImage']['src'] ); ?>'); background-position: <?php echo esc_attr( $item['bgPosition'] ); ?>;"
        ></div>
        <?php endif; endforeach; ?>
        <div class="feature-spotlight__bg-overlay"></div>
    </div>

    <!-- Content layer -->
    <div class="feature-spotlight__content">
        <?php if ( $title || $subtitle ) : ?>
        <header class="feature-spotlight__header">
            <?php if ( $title ) : ?>
            <h2 class="feature-spotlight__title"><?php echo esc_html( $title ); ?></h2>
            <?php endif; ?>
            <?php if ( $subtitle ) : ?>
            <p class="feature-spotlight__subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>
        </header>
        <?php endif; ?>

        <div class="feature-spotlight__main">
            <!-- Navigation arrows (left side) -->
            <?php if ( $show_arrows ) : ?>
            <div class="feature-spotlight__arrows" aria-hidden="true">
                <button type="button" class="feature-spotlight__arrow feature-spotlight__arrow--up" aria-label="Forrige">
                    <i class="fa-solid fa-chevron-up"></i>
                </button>
                <button type="button" class="feature-spotlight__arrow feature-spotlight__arrow--down" aria-label="Neste">
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
            </div>
            <?php endif; ?>

            <!-- Items list -->
            <div class="feature-spotlight__list" role="tablist" aria-orientation="vertical">
                <?php foreach ( $items_data as $index => $item ) : 
                    $is_active = $initial_index !== null && $index === $initial_index;
                    $item_id = $block_id . '-item-' . $index;
                    $panel_id = $block_id . '-panel-' . $index;
                ?>
                <div 
                    class="feature-spotlight__item<?php echo $is_active ? ' is-active' : ''; ?>"
                    data-index="<?php echo esc_attr( $index ); ?>"
                >
                    <button 
                        type="button"
                        id="<?php echo esc_attr( $item_id ); ?>"
                        class="feature-spotlight__item-btn"
                        role="tab"
                        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                        aria-controls="<?php echo esc_attr( $panel_id ); ?>"
                        aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>"
                    >
                        <span class="feature-spotlight__item-label"><?php echo esc_html( $item['label'] ); ?></span>
                    </button>
                    
                    <div 
                        id="<?php echo esc_attr( $panel_id ); ?>"
                        class="feature-spotlight__item-panel"
                        role="tabpanel"
                        aria-labelledby="<?php echo esc_attr( $item_id ); ?>"
                        <?php echo $is_active ? '' : 'hidden'; ?>
                    >
                        <div class="feature-spotlight__item-content">
                            <?php if ( ! empty( $item['titlePrefix'] ) ) : ?>
                            <h3 class="feature-spotlight__item-title"><?php echo esc_html( $item['titlePrefix'] ); ?></h3>
                            <?php endif; ?>
                            
                            <?php if ( ! empty( $item['body'] ) ) : ?>
                            <div class="feature-spotlight__item-body">
                                <?php echo wp_kses_post( wpautop( $item['body'] ) ); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ( ! empty( $item['mediaImage'] ) ) : ?>
                            <div class="feature-spotlight__item-media">
                                <img 
                                    src="<?php echo esc_url( $item['mediaImage']['src'] ); ?>" 
                                    alt="<?php echo esc_attr( $item['mediaImage']['alt'] ); ?>"
                                    loading="lazy"
                                />
                            </div>
                            <?php endif; ?>
                            
                            <?php if ( ! empty( $item['footnote'] ) ) : ?>
                            <p class="feature-spotlight__item-footnote"><?php echo esc_html( $item['footnote'] ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Fallback for no-JS -->
    <noscript>
        <style>
            #<?php echo esc_attr( $block_id ); ?> .feature-spotlight__item-panel { display: block !important; }
            #<?php echo esc_attr( $block_id ); ?> .feature-spotlight__arrows { display: none !important; }
            #<?php echo esc_attr( $block_id ); ?> .feature-spotlight__item-close { display: none !important; }
        </style>
    </noscript>
</div>
