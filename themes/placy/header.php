<?php
/**
 * The header for our theme
 *
 * @package Placy
 * @since 1.0.0
 */
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
    <a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'placy' ); ?></a>

    <!-- Header Navigation -->
    <header class="bg-[#76908D] fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <?php
                    if ( has_custom_logo() ) :
                        the_custom_logo();
                    else :
                        ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-white text-xl font-bold">
                            <?php bloginfo( 'name' ); ?>
                        </a>
                        <?php
                    endif;
                    ?>
                </div>
                
                <!-- Navigation Menu (Desktop) -->
                <nav class="hidden md:flex space-x-8 nav-font">
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'primary',
                        'menu_class'     => 'flex space-x-8',
                        'container'      => false,
                        'fallback_cb'    => false,
                    ) );
                    ?>
                </nav>
                
                <!-- CTA Button -->
                <div>
                    <a href="#" class="bg-overvik-green text-white py-2 px-4 rounded-full nav-font font-medium hover:bg-opacity-90 transition-all">
                        <?php esc_html_e( 'Meld interesse', 'placy' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </header>
