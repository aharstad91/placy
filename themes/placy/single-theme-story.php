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

<?php while ( have_posts() ) : the_post(); 
    // Get parent story for back button
    $parent_story = get_field( 'parent_story' );
    
    // Get intro fields
    $intro_title = get_field( 'intro_title' );
    $intro_text = get_field( 'intro_text' );
    $intro_background = get_field( 'intro_background' );
    
    // Get container background color
    $container_bg_color = get_field( 'container_background_color' );
    if ( ! $container_bg_color ) {
        $container_bg_color = '#f5f5f5'; // Default fallback
    }
?>
    <!-- Intro Section (93vh full width with parallax) -->
    <?php if ( $intro_title || $intro_text ) : ?>
        <section class="intro-section" <?php if ( $intro_background ) : ?>style="background-image: url('<?php echo esc_url( $intro_background ); ?>');"<?php endif; ?> data-gradient-color="<?php echo esc_attr( $container_bg_color ); ?>">
            <div class="intro-content">
                <?php if ( $intro_title ) : ?>
                    <h1 class="intro-title"><?php echo esc_html( $intro_title ); ?></h1>
                <?php endif; ?>
                
                <?php if ( $intro_text ) : ?>
                    <div class="intro-text"><?php echo wp_kses_post( nl2br( $intro_text ) ); ?></div>
                <?php endif; ?>
                
                <!-- Chapter navigation in intro -->
                <div class="intro-chapter-nav" id="intro-chapter-nav">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Scroll indicator -->
            <div class="scroll-indicator">
                <span class="scroll-text">Scroll for å utforske</span>
                <svg class="scroll-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
            
            <div class="intro-gradient" style="background: linear-gradient(to bottom, transparent 0%, transparent 40%, <?php echo esc_attr( $container_bg_color ); ?> 100%);"></div>
        </section>
    <?php endif; ?>

    <!-- Main Content Wrapper (scrollable container) -->
    <div class="main-content-wrapper">
        <div class="tema-story-container" data-bg-color="<?php echo esc_attr( $container_bg_color ); ?>">
            
            <!-- Content with per-chapter maps -->
            <div class="content-column">
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="tema-story-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            </div>

            <!-- Navigation Column - Horizontal Sticky Top -->
            <nav class="nav-column">
                <div class="nav-inner">
                    <?php if ( $parent_story ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $parent_story->ID ) ); ?>" class="back-button">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span>Tilbake</span>
                        </a>
                    <?php endif; ?>
                    
                    <div class="chapter-nav" id="chapter-nav">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </nav>
        </div>
    </div>
<?php endwhile; ?>

<?php wp_footer(); ?>
</body>
</html>
