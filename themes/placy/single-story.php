<?php
/**
 * The template for displaying single Story posts
 *
 * @package Placy
 * @since 1.0.0
 */

// Get intro fields
$intro_image = get_field( 'story_intro_image' );
$intro_text = get_field( 'story_intro_text' );
$container_bg_color = get_field( 'story_container_bg_color' );
if ( ! $container_bg_color ) {
    $container_bg_color = '#ffffff';
}

// Get foreword fields
$foreword_text = get_field( 'story_foreword_text' );
$foreword_image = get_field( 'story_foreword_image' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">

<?php while ( have_posts() ) : the_post(); ?>

<!-- Story Intro Section (100vh) -->
<section class="story-intro-section px-12" <?php if ( $intro_image ) : ?>style="background-image: url('<?php echo esc_url( $intro_image['url'] ); ?>');"<?php endif; ?> data-gradient-color="<?php echo esc_attr( $container_bg_color ); ?>">
    <div class="story-intro-content">
        <h1 class="story-intro-title"><?php the_title(); ?></h1>
        
        <?php if ( $intro_text ) : ?>
            <div class="story-intro-text"><?php echo wp_kses_post( $intro_text ); ?></div>
        <?php endif; ?>
    </div>
    
    <!-- Scroll indicator -->
    <div class="scroll-indicator">
        <span class="scroll-text">Scroll for å utforske</span>
        <svg class="scroll-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
    
    <!-- Gradient overlay to content background -->
    <div class="story-intro-gradient" style="background: linear-gradient(to bottom, transparent 0%, transparent 60%, <?php echo esc_attr( $container_bg_color ); ?> 100%);"></div>
</section>

<!-- Story Foreword/Index Section -->
<section class="story-foreword-section" style="background-color: <?php echo esc_attr( $container_bg_color ); ?>;">
    <div class="story-foreword-container mx-auto px-12" style="max-width: 1920px;">
        <?php if ( $foreword_text ) : ?>
            <div class="story-foreword-text">
                <?php echo wp_kses_post( $foreword_text ); ?>
            </div>
        <?php endif; ?>
        
        <div class="story-foreword-content">
            <!-- Chapter cards (populated by JavaScript) -->
            <div class="story-chapter-cards" id="story-chapter-cards">
                <!-- Will be populated by JavaScript from chapter-wrapper blocks -->
            </div>
            
            <?php if ( $foreword_image ) : ?>
                <div class="story-foreword-image">
                    <img src="<?php echo esc_url( $foreword_image['url'] ); ?>" 
                         alt="<?php echo esc_attr( $foreword_image['alt'] ?? '' ); ?>"
                         class="rounded-lg">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Sticky TOC Navigation Bar (positioned after foreword, becomes sticky on scroll) -->
<nav class="sticky-toc-nav px-12" id="sticky-toc-nav">
    <div class="sticky-toc-title">
        <?php the_title(); ?>
    </div>
    <div class="sticky-toc-inner">
        <!-- Chapter pills will be populated by JavaScript -->
    </div>
</nav>

<main id="main-content" class="site-main single-story" style="background-color: <?php echo esc_attr( $container_bg_color ); ?>;">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="entry-content mx-auto px-12 py-8" style="max-width: 1920px;">
            <?php the_content(); ?>
        </div>
    </article>
</main>

<?php endwhile; ?>

<?php
get_footer();
