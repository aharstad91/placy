<?php
/**
 * The template for displaying single Project posts
 * 
 * Uses Story Chapter blocks via the_content() for chapter content.
 * Adds optional sidebar with manual navigation and global travel settings.
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

// Check if sidebar is enabled (has nav items)
$sidebar_nav_items = get_field( 'sidebar_nav_items' );
$has_sidebar = ! empty( $sidebar_nav_items );
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

<?php if ( $has_sidebar ) : ?>
    <?php get_template_part( 'template-parts/project', 'sidebar' ); ?>
<?php endif; ?>

<!-- Wrapper with sidebar offset -->
<div class="project-content-wrapper <?php echo $has_sidebar ? 'has-sidebar' : ''; ?>">

<!-- Project Foreword/Index Section -->
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

<?php 
// Include Master Map Modal (for "Open full map" functionality)
get_template_part( 'template-parts/master-map-modal' );
?>

</div><!-- .project-content-wrapper -->

<?php endwhile; ?>

<?php
get_footer();
