<?php
/**
 * The main template file
 *
 * @package Placy
 * @since 1.0.0
 */

get_header(); ?>

<main id="main-content" class="site-main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            get_template_part( 'template-parts/content', get_post_type() );
        endwhile;

        // Pagination
        the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => __( '&laquo; Previous', 'placy' ),
            'next_text' => __( 'Next &raquo;', 'placy' ),
        ) );
    else :
        get_template_part( 'template-parts/content', 'none' );
    endif;
    ?>
</main>

<?php
get_sidebar();
get_footer();
