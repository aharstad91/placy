<?php
/**
 * The template for displaying single Theme Story posts
 *
 * @package Placy
 * @since 1.0.0
 */

get_header(); ?>

<main id="main-content" class="site-main single-theme-story">
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header" style="padding: 120px 0 40px; text-align: center;">
                <div class="max-w-4xl mx-auto px-6">
                    <?php the_title( '<h1 class="entry-title text-4xl font-bold mb-4">', '</h1>' ); ?>
                </div>
            </header>

            <div class="entry-content max-w-4xl mx-auto px-6 py-8">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php
get_footer();
