<?php
/**
 * Template part for displaying posts
 *
 * @package Placy
 * @since 1.0.0
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'mb-8' ); ?>>
    <header class="entry-header mb-4">
        <?php
        if ( is_singular() ) :
            the_title( '<h1 class="entry-title text-3xl font-bold mb-2">', '</h1>' );
        else :
            the_title( '<h2 class="entry-title text-2xl font-bold mb-2"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
        endif;
        ?>
        
        <div class="entry-meta text-gray-600 text-sm">
            <span class="posted-on"><?php echo get_the_date(); ?></span>
            <span class="byline"> <?php esc_html_e( 'av', 'placy' ); ?> <?php the_author(); ?></span>
        </div>
    </header>

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="post-thumbnail mb-4">
            <?php the_post_thumbnail( 'large', array( 'class' => 'w-full rounded-lg' ) ); ?>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php
        if ( is_singular() ) :
            the_content();
            
            wp_link_pages( array(
                'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'placy' ),
                'after'  => '</div>',
            ) );
        else :
            the_excerpt();
            ?>
            <a href="<?php the_permalink(); ?>" class="read-more text-overvik-green hover:underline">
                <?php esc_html_e( 'Les mer', 'placy' ); ?> &rarr;
            </a>
            <?php
        endif;
        ?>
    </div>

    <?php if ( is_singular() ) : ?>
        <footer class="entry-footer mt-4 pt-4 border-t">
            <?php
            $categories_list = get_the_category_list( ', ' );
            if ( $categories_list ) :
                printf( '<span class="cat-links">' . esc_html__( 'Posted in %1$s', 'placy' ) . '</span>', $categories_list );
            endif;

            $tags_list = get_the_tag_list( '', ', ' );
            if ( $tags_list ) :
                printf( '<span class="tags-links"> | ' . esc_html__( 'Tagged %1$s', 'placy' ) . '</span>', $tags_list );
            endif;
            ?>
        </footer>
    <?php endif; ?>
</article>
