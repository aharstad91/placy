<?php
/**
 * Placy Theme Functions
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Theme Setup
 */
function placy_theme_setup() {
    // Add theme support for title tag
    add_theme_support( 'title-tag' );
    
    // Add theme support for post thumbnails
    add_theme_support( 'post-thumbnails' );
    
    // Add theme support for HTML5
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ) );
    
    // Add theme support for custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    
    // Register navigation menus
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'placy' ),
        'footer'  => __( 'Footer Menu', 'placy' ),
    ) );
}
add_action( 'after_setup_theme', 'placy_theme_setup' );

/**
 * Enqueue scripts and styles
 */
function placy_enqueue_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style( 'placy-style', get_stylesheet_uri(), array(), '1.0.0' );
    
    // Enqueue custom styles
    wp_enqueue_style( 'placy-custom-styles', get_template_directory_uri() . '/css/styles.css', array(), '1.0.0' );
    
    // Enqueue Tailwind CSS from CDN
    wp_enqueue_script( 'tailwind-cdn', 'https://cdn.tailwindcss.com', array(), null, false );
    
    // Enqueue Adobe Typekit fonts
    wp_enqueue_style( 'adobe-typekit', 'https://use.typekit.net/jlp3dzl.css', array(), null );
    
    // Enqueue Google Fonts (Raleway)
    wp_enqueue_style( 'google-fonts-raleway', 'https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700&display=swap', array(), null );
    
    // Enqueue theme JavaScript files
    wp_enqueue_script( 'placy-app', get_template_directory_uri() . '/js/app.js', array(), '1.0.0', true );
    wp_enqueue_script( 'placy-components', get_template_directory_uri() . '/js/components.js', array(), '1.0.0', true );
    wp_enqueue_script( 'placy-config', get_template_directory_uri() . '/js/config.js', array(), '1.0.0', true );
    wp_enqueue_script( 'placy-performance', get_template_directory_uri() . '/js/performance.js', array(), '1.0.0', true );
    
    // Add Tailwind configuration inline
    $tailwind_config = "
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'overvik-green': '#78908E',
                        'overvik-light': '#D1E5E6',
                    },
                    fontFamily: {
                        'campaign': ['campaign', 'Raleway', 'sans-serif'],
                        'campaign-serif': ['campaign-serif', 'Raleway', 'serif'],
                        'raleway': ['Raleway', 'sans-serif'],
                    }
                }
            }
        }
    ";
    wp_add_inline_script( 'tailwind-cdn', $tailwind_config );
}
add_action( 'wp_enqueue_scripts', 'placy_enqueue_scripts' );

/**
 * Register widget areas
 */
function placy_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'placy' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Add widgets here to appear in your sidebar.', 'placy' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'placy_widgets_init' );

/**
 * Set content width
 */
if ( ! isset( $content_width ) ) {
    $content_width = 1200;
}
