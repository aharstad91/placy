<?php
/**
 * Template part for displaying a message when no posts are found
 *
 * @package Placy
 * @since 1.0.0
 */
?>

<section class="no-results not-found">
    <header class="page-header mb-4">
        <h1 class="page-title text-3xl font-bold"><?php esc_html_e( 'Ingenting funnet', 'placy' ); ?></h1>
    </header>

    <div class="page-content">
        <?php
        if ( is_home() && current_user_can( 'publish_posts' ) ) :
            ?>
            <p><?php
                printf(
                    wp_kses(
                        __( 'Klar til å publisere ditt første innlegg? <a href="%1$s">Kom i gang her</a>.', 'placy' ),
                        array(
                            'a' => array(
                                'href' => array(),
                            ),
                        )
                    ),
                    esc_url( admin_url( 'post-new.php' ) )
                );
            ?></p>
            <?php
        elseif ( is_search() ) :
            ?>
            <p><?php esc_html_e( 'Beklager, men ingenting matchet søket ditt. Vennligst prøv igjen med andre søkeord.', 'placy' ); ?></p>
            <?php
            get_search_form();
        else :
            ?>
            <p><?php esc_html_e( 'Det ser ut til at vi ikke kan finne det du leter etter. Kanskje søk kan hjelpe.', 'placy' ); ?></p>
            <?php
            get_search_form();
        endif;
        ?>
    </div>
</section>
