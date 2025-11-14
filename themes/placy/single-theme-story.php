<?php
/**
 * The template for displaying single Theme Story posts
 * Full-screen immersive experience without header/footer
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
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

<div class="tema-story-container">
    <?php while ( have_posts() ) : the_post(); ?>
        <!-- Content Column -->
        <div class="content-column">
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <!-- Hero Section -->
                <header class="tema-story-hero">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="hero-image">
                            <?php the_post_thumbnail( 'full' ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hero-content">
                        <?php the_title( '<h1 class="hero-title">', '</h1>' ); ?>
                        
                        <?php if ( has_excerpt() ) : ?>
                            <div class="hero-intro">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </header>

                <!-- Story Content with Chapters -->
                <div class="tema-story-content">
                    <?php the_content(); ?>
                </div>
            </article>
        </div>
        
        <!-- Map Column -->
        <div class="map-column">
            <div id="tema-story-map" class="tema-story-map"></div>
        </div>
    <?php endwhile; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
