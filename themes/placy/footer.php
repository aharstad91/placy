<?php
/**
 * The footer for our theme
 *
 * @package Placy
 * @since 1.0.0
 */
?>

    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4"><?php bloginfo( 'name' ); ?></h3>
                    <p class="text-gray-400"><?php bloginfo( 'description' ); ?></p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4"><?php esc_html_e( 'Navigasjon', 'placy' ); ?></h3>
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'footer',
                        'menu_class'     => 'space-y-2',
                        'container'      => false,
                        'fallback_cb'    => false,
                    ) );
                    ?>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4"><?php esc_html_e( 'Kontakt', 'placy' ); ?></h3>
                    <p class="text-gray-400">
                        <?php esc_html_e( 'Kontaktinformasjon kommer her', 'placy' ); ?>
                    </p>
                </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-400">
                <p>&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Alle rettigheter reservert.', 'placy' ); ?></p>
            </div>
        </div>
    </footer>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
