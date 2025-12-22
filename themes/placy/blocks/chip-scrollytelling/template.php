<?php
/**
 * Chip Scrollytelling Block Template
 *
 * TRUE pinned scrollytelling architecture:
 * - Outer wrapper has height = stepsCount * 100vh (provides scroll distance)
 * - Sticky scene stays pinned while you scroll through the wrapper
 * - JS reads scroll-progress (0→1) and sets data-active-step on root
 * - CSS uses [data-active-step="N"] selectors to crossfade content
 * - Scroll is PRIMARY driver. Clicks are just "jump to scroll position".
 *
 * NOT a tab component. NOT click-driven state.
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
$steps_height_vh    = get_field( 'steps_height_vh' ) ?: 300;
$scene_height_vh    = get_field( 'scene_height_vh' ) ?: 100;
$scene_max_width    = get_field( 'scene_max_width' ) ?: 1320;
$enable_click_scroll = get_field( 'enable_click_to_scroll' );
$cta_label          = get_field( 'cta_label' ) ?: 'Utforsk';
$cta_url            = get_field( 'cta_url' ) ?: '';
$plus_button_enabled = get_field( 'plus_button_enabled' );
$reduced_motion_mode = get_field( 'reduced_motion_mode' ) ?: 'respect';
$steps              = get_field( 'steps' ) ?: array();

// Handle defaults for booleans
if ( $enable_click_scroll === '' || $enable_click_scroll === null ) {
    $enable_click_scroll = true;
}
if ( $plus_button_enabled === '' || $plus_button_enabled === null ) {
    $plus_button_enabled = true;
}

// Block wrapper
$block_id = 'chip-scrolly-' . $block['id'];
$class_name = 'chip-scrolly';

if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}

// Add motion class based on setting
if ( $reduced_motion_mode === 'force_off' ) {
    $class_name .= ' chip-scrolly--reduce-motion';
} elseif ( $reduced_motion_mode === 'force_on' ) {
    $class_name .= ' chip-scrolly--force-motion';
}

$steps_count = count( $steps );

// Admin preview placeholder
if ( empty( $steps ) ) {
    if ( is_admin() ) {
        echo '<div class="chip-scrolly-placeholder" style="padding: 60px 40px; background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%); color: #fff; text-align: center; border-radius: 28px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" style="margin-bottom: 16px; opacity: 0.7;">
                <rect x="3" y="3" width="18" height="18" rx="4" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            <h4 style="margin: 0 0 8px; font-size: 20px; font-weight: 600;">Chip Scrollytelling</h4>
            <p style="margin: 0; opacity: 0.6; font-size: 14px;">Legg til steg i blokkinnstillingene for å se forhåndsvisning</p>
        </div>';
    }
    return;
}

// Build glow colors CSS variables
$glow_css_vars = '';
foreach ( $steps as $index => $step ) {
    $color = $step['glow_color'] ?? '#2b6cff';
    $glow_css_vars .= "--glow-color-{$index}: {$color}; ";
}

// Minimal props for JS (just what's needed for click-to-scroll)
$props = array(
    'stepsCount'        => $steps_count,
    'stepsHeightVh'     => intval( $steps_height_vh ),
    'enableClickScroll' => $enable_click_scroll,
);

?>

<!--
  ARCHITECTURE (do not change):
  ┌─────────────────────────────────────────────────────────────────┐
  │ section.chip-scrolly                                            │
  │   height: 300vh (or stepsCount * 100vh)                        │
  │   This creates the scroll distance.                            │
  │                                                                 │
  │   ┌─────────────────────────────────────────────────────────┐   │
  │   │ div.chip-scrolly__scene                                 │   │
  │   │   position: sticky; top: 0; height: 100vh;              │   │
  │   │   This stays pinned while you scroll.                   │   │
  │   │                                                         │   │
  │   │   ┌─────────────────────────────────────────────────┐   │   │
  │   │   │ div.chip-scrolly__card                          │   │   │
  │   │   │   All step content pre-rendered.                │   │   │
  │   │   │   CSS crossfades based on [data-active-step]    │   │   │
  │   │   └─────────────────────────────────────────────────┘   │   │
  │   └─────────────────────────────────────────────────────────┘   │
  └─────────────────────────────────────────────────────────────────┘
  
  JS calculates: progress = (how far scrolled into section) / (section height - viewport)
  Maps progress to step index, sets data-active-step="0|1|2" on root.
  CSS does the rest with [data-active-step="N"] selectors.
-->

<section 
    id="<?php echo esc_attr( $block_id ); ?>" 
    class="<?php echo esc_attr( $class_name ); ?>"
    data-props="<?php echo esc_attr( wp_json_encode( $props ) ); ?>"
    data-active-step="0"
    data-steps-count="<?php echo esc_attr( $steps_count ); ?>"
    style="
        --steps-height-vh: <?php echo esc_attr( $steps_height_vh ); ?>;
        --scene-height-vh: <?php echo esc_attr( $scene_height_vh ); ?>;
        --scene-max-width: <?php echo esc_attr( $scene_max_width ); ?>px;
        --steps-count: <?php echo esc_attr( $steps_count ); ?>;
        <?php echo $glow_css_vars; ?>
    "
>
    
    <!-- Sticky scene: pinned while scrolling through the section -->
    <div class="chip-scrolly__scene">
        
        <!-- Main card -->
        <div class="chip-scrolly__card">
            
            <!-- Glow effect (color transitions via CSS based on data-active-step) -->
            <div class="chip-scrolly__glow" aria-hidden="true"></div>
            
            <!-- 
              VISUAL STACK: All step images pre-rendered, stacked with position:absolute.
              CSS shows the active one with [data-active-step="N"] selector.
            -->
            <div class="chip-scrolly__visual-stack">
                <?php foreach ( $steps as $index => $step ) : 
                    $visual_image = $step['visual_image'];
                    if ( ! empty( $visual_image ) ) :
                        $img_src = $visual_image['sizes']['large'] ?? $visual_image['url'];
                        $img_srcset = wp_get_attachment_image_srcset( $visual_image['ID'], 'large' );
                        $img_alt = $visual_image['alt'] ?? '';
                ?>
                <figure 
                    class="chip-scrolly__visual-item"
                    data-step="<?php echo esc_attr( $index ); ?>"
                    aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>"
                >
                    <img 
                        src="<?php echo esc_url( $img_src ); ?>"
                        <?php if ( $img_srcset ) : ?>
                        srcset="<?php echo esc_attr( $img_srcset ); ?>"
                        sizes="(max-width: 768px) 100vw, 55vw"
                        <?php endif; ?>
                        alt="<?php echo esc_attr( $img_alt ); ?>"
                        loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                        decoding="async"
                    />
                </figure>
                <?php endif; endforeach; ?>
            </div>
            
            <!-- CONTENT STACK -->
            <div class="chip-scrolly__content-stack">
                
                <!-- Segmented control: clicks = jump to scroll position (secondary) -->
                <nav class="chip-scrolly__segmented" role="tablist" aria-label="Velg variant">
                    <?php foreach ( $steps as $index => $step ) : 
                        $chip_label = $step['chip_label'] ?? '';
                        $chip_icon = $step['chip_icon'] ?? 'none';
                    ?>
                    <button
                        type="button"
                        class="chip-scrolly__chip"
                        role="tab"
                        aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                        tabindex="<?php echo $index === 0 ? '0' : '-1'; ?>"
                        data-step="<?php echo esc_attr( $index ); ?>"
                    >
                        <?php if ( $chip_icon === 'apple' ) : ?>
                        <svg class="chip-scrolly__chip-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M18.71 19.5C17.88 20.74 17 21.95 15.66 21.97C14.32 22 13.89 21.18 12.37 21.18C10.84 21.18 10.37 21.95 9.1 22C7.79 22.05 6.8 20.68 5.96 19.47C4.25 17 2.94 12.45 4.7 9.39C5.57 7.87 7.13 6.91 8.82 6.88C10.1 6.86 11.32 7.75 12.11 7.75C12.89 7.75 14.37 6.68 15.92 6.84C16.57 6.87 18.39 7.1 19.56 8.82C19.47 8.88 17.39 10.1 17.41 12.63C17.44 15.65 20.06 16.66 20.09 16.67C20.06 16.74 19.67 18.11 18.71 19.5ZM13 3.5C13.73 2.67 14.94 2.04 15.94 2C16.07 3.17 15.6 4.35 14.9 5.19C14.21 6.04 13.07 6.7 11.95 6.61C11.8 5.46 12.36 4.26 13 3.5Z"/>
                        </svg>
                        <?php endif; ?>
                        <span><?php echo esc_html( $chip_label ); ?></span>
                    </button>
                    <?php endforeach; ?>
                </nav>
                
                <!-- 
                  COPY STACK: All step text pre-rendered, stacked with position:absolute.
                  CSS shows the active one with [data-active-step="N"] selector.
                -->
                <div class="chip-scrolly__copy-stack">
                    <?php foreach ( $steps as $index => $step ) : 
                        $headline = $step['headline'] ?? '';
                        $body = $step['body'] ?? '';
                        $kpi1_label = $step['kpi_1_label'] ?? '';
                        $kpi1_value = $step['kpi_1_value'] ?? '';
                        $kpi2_label = $step['kpi_2_label'] ?? '';
                        $kpi2_value = $step['kpi_2_value'] ?? '';
                    ?>
                    <article 
                        class="chip-scrolly__step-copy"
                        data-step="<?php echo esc_attr( $index ); ?>"
                        role="tabpanel"
                        aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>"
                    >
                        <h2 class="chip-scrolly__headline"><?php echo esc_html( $headline ); ?></h2>
                        
                        <?php if ( ! empty( $body ) ) : ?>
                        <p class="chip-scrolly__body"><?php echo esc_html( $body ); ?></p>
                        <?php endif; ?>
                        
                        <?php if ( ! empty( $kpi1_label ) || ! empty( $kpi2_label ) ) : ?>
                        <dl class="chip-scrolly__kpis">
                            <?php if ( ! empty( $kpi1_label ) ) : ?>
                            <div class="chip-scrolly__kpi">
                                <dt><?php echo esc_html( $kpi1_label ); ?></dt>
                                <dd><?php echo esc_html( $kpi1_value ); ?></dd>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ( ! empty( $kpi2_label ) ) : ?>
                            <div class="chip-scrolly__kpi">
                                <dt><?php echo esc_html( $kpi2_label ); ?></dt>
                                <dd><?php echo esc_html( $kpi2_value ); ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- CTA buttons (shared across all steps) -->
                <?php if ( ! empty( $cta_url ) || $plus_button_enabled ) : ?>
                <div class="chip-scrolly__cta-row">
                    <?php if ( ! empty( $cta_url ) ) : ?>
                    <a href="<?php echo esc_url( $cta_url ); ?>" class="chip-scrolly__cta-btn">
                        <?php echo esc_html( $cta_label ); ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ( $plus_button_enabled && ! empty( $cta_url ) ) : ?>
                    <a href="<?php echo esc_url( $cta_url ); ?>" class="chip-scrolly__plus-btn" aria-label="Les mer">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            </div>
            
        </div>
        
    </div>
    
</section>

<!-- No-JS fallback: show all steps stacked vertically -->
<noscript>
<style>
    #<?php echo esc_attr( $block_id ); ?> { height: auto !important; }
    #<?php echo esc_attr( $block_id ); ?> .chip-scrolly__scene { position: relative !important; height: auto !important; }
    #<?php echo esc_attr( $block_id ); ?> .chip-scrolly__card { flex-direction: column !important; height: auto !important; }
    #<?php echo esc_attr( $block_id ); ?> .chip-scrolly__visual-stack,
    #<?php echo esc_attr( $block_id ); ?> .chip-scrolly__copy-stack { 
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 2rem !important;
    }
    #<?php echo esc_attr( $block_id ); ?> .chip-scrolly__visual-item,
    #<?php echo esc_attr( $block_id ); ?> .chip-scrolly__step-copy { 
        position: relative !important; 
        opacity: 1 !important; 
        visibility: visible !important;
        transform: none !important;
        pointer-events: auto !important;
    }
    #<?php echo esc_attr( $block_id ); ?> .chip-scrolly__segmented { display: none !important; }
</style>
</noscript>
