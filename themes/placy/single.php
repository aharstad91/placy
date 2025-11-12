<?php
/**
 * The template for displaying all single posts
 *
 * @package Placy
 * @since 1.0.0
 */

get_header(); ?>

<main id="main-content" class="site-main single-post">
    <?php
    while ( have_posts() ) :
        the_post();
        get_template_part( 'template-parts/content', get_post_type() );
    endwhile;
    ?>
</main>

<?php
get_footer();
