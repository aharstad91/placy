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
    <?php while ( have_posts() ) : the_post(); 
        // Get parent story for back button
        $parent_story = get_field( 'parent_story' );
    ?>
        <!-- Navigation Column (Sticky Left) -->
        <nav class="nav-column">
            <div class="nav-inner">
                <?php if ( $parent_story ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $parent_story->ID ) ); ?>" class="back-button">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Tilbake til <?php echo esc_html( get_the_title( $parent_story->ID ) ); ?></span>
                    </a>
                <?php endif; ?>
                
                <div class="chapter-nav" id="chapter-nav">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </nav>
        
        <!-- Content Column (Scrollable Middle) -->
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
        
        <!-- Map Column (Sticky Right) -->
        <div class="map-column">
            <div id="tema-story-map" class="tema-story-map"></div>
        </div>
    <?php endwhile; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
