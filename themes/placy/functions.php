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
        // Enqueue theme styles
    wp_enqueue_style( 'placy-style', get_stylesheet_uri(), array(), '1.0.0' );
    wp_enqueue_style( 'placy-tailwind', get_template_directory_uri() . '/css/tailwind-output.css', array(), '1.0.0' );
    wp_enqueue_style( 'placy-custom', get_template_directory_uri() . '/css/styles.css', array(), '1.0.0' );
    
    // Enqueue Adobe Typekit fonts
    wp_enqueue_style( 'adobe-typekit', 'https://use.typekit.net/jlp3dzl.css', array(), null );
    
    // Enqueue Google Fonts (Figtree)
    wp_enqueue_style( 'google-fonts-figtree', 'https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800&display=swap', array(), null );
    
    // Enqueue Mapbox GL JS
    wp_enqueue_style( 'mapbox-gl-css', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css', array(), '2.15.0' );
    wp_enqueue_script( 'mapbox-gl-js', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js', array(), '2.15.0', true );
    
    // Enqueue theme JavaScript files
    // Prototype scripts disabled - using production POI map scripts
    // wp_enqueue_script( 'placy-app', get_template_directory_uri() . '/js/app.js', array(), '1.0.0', true );
    // wp_enqueue_script( 'placy-components', get_template_directory_uri() . '/js/components.js', array(), '1.0.0', true );
    // wp_enqueue_script( 'placy-config', get_template_directory_uri() . '/js/config.js', array(), '1.0.0', true );
    // wp_enqueue_script( 'placy-performance', get_template_directory_uri() . '/js/performance.js', array(), '1.0.0', true );
    
    // POI Map Modal script
    wp_enqueue_script( 'placy-poi-map-modal', get_template_directory_uri() . '/js/poi-map-modal.js', array(), '1.0.0', true );
    
    // Tema Story styles and scripts (only on theme-story post type)
    if ( is_singular( 'theme-story' ) ) {
        wp_enqueue_style( 'placy-tema-story', get_template_directory_uri() . '/css/tema-story.css', array(), '1.0.0' );
        wp_enqueue_style( 'placy-chapter-wrapper', get_template_directory_uri() . '/blocks/chapter-wrapper/style.css', array(), '1.0.0' );
        wp_enqueue_script( 'placy-tema-story-map', get_template_directory_uri() . '/js/tema-story-map.js', array( 'mapbox-gl-js' ), '1.0.0', true );
        wp_enqueue_script( 'placy-chapter-nav', get_template_directory_uri() . '/js/chapter-nav.js', array(), '1.0.0', true );
        
        // Get start location from ACF fields
        $start_lat = get_field( 'start_latitude' );
        $start_lng = get_field( 'start_longitude' );
        $start_location = null;
        
        if ( $start_lat && $start_lng ) {
            $start_location = array(
                floatval( $start_lng ),
                floatval( $start_lat )
            );
        }
        
        // Pass Mapbox token and start location to the script
        wp_localize_script( 'placy-tema-story-map', 'placyMapConfig', array(
            'mapboxToken' => placy_get_mapbox_token(),
            'startLocation' => $start_location,
        ) );
    }
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

/**
 * Include custom post types
 */
require_once get_template_directory() . '/inc/post-types.php';

/**
 * Include ACF field groups
 */
require_once get_template_directory() . '/inc/acf-fields.php';

/**
 * Include custom rewrites
 */
require_once get_template_directory() . '/inc/rewrites.php';

/**
 * Include Mapbox configuration
 */
require_once get_template_directory() . '/inc/mapbox-config.php';

/**
 * Include Tema Story block patterns
 */
require_once get_template_directory() . '/inc/tema-story-patterns.php';

/**
 * Register custom block category for Placy blocks
 */
function placy_register_block_category( $categories ) {
    return array_merge(
        array(
            array(
                'slug'  => 'placy-content',
                'title' => __( 'Placy Content', 'placy' ),
                'icon'  => 'location-alt',
            ),
        ),
        $categories
    );
}
add_filter( 'block_categories_all', 'placy_register_block_category', 10, 1 );

/**
 * Enqueue block styles for both frontend and editor
 */
function placy_enqueue_block_assets( $block_name ) {
    // Map block names to their style files
    $block_styles = array(
        'acf/poi-map-card'  => '/blocks/poi-map-card/style.css',
        'acf/poi-list'      => '/blocks/poi-list/style.css',
        'acf/poi-highlight' => '/blocks/poi-highlight/style.css',
        'acf/poi-gallery'   => '/blocks/poi-gallery/style.css',
        'acf/image-column'  => '/blocks/image-column/style.css',
    );
    
    // Check if this block has styles
    if ( isset( $block_styles[ $block_name ] ) ) {
        $style_path = $block_styles[ $block_name ];
        $style_file = get_template_directory() . $style_path;
        
        // Only enqueue if file exists
        if ( file_exists( $style_file ) ) {
            $handle = 'placy-' . str_replace( '/', '-', $block_name );
            wp_enqueue_style(
                $handle,
                get_template_directory_uri() . $style_path,
                array(),
                filemtime( $style_file )
            );
        }
    }
}
add_action( 'enqueue_block_assets', 'placy_enqueue_block_assets' );

/**
 * Register ACF Blocks
 */
function placy_register_acf_blocks() {
    if ( function_exists( 'acf_register_block_type' ) ) {
        // Register POI Map Card block
        acf_register_block_type( array(
            'name'              => 'poi-map-card',
            'title'             => __( 'POI Kart', 'placy' ),
            'description'       => __( 'Interaktivt kartblokk som viser valgte POIs', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/poi-map-card/template.php',
            'category'          => 'placy-content',
            'icon'              => 'location-alt',
            'keywords'          => array( 'poi', 'map', 'kart', 'location' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => array( 'wide', 'full' ),
                'anchor' => true,
            ),
        ) );
        
        // Register POI List block
        acf_register_block_type( array(
            'name'              => 'poi-list',
            'title'             => __( 'POI Liste', 'placy' ),
            'description'       => __( 'Viser en liste med POIs for tema story kapitler', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/poi-list/template.php',
            'category'          => 'placy-content',
            'icon'              => 'list-view',
            'keywords'          => array( 'poi', 'list', 'tema', 'story', 'kapittel' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => array( 'wide', 'full' ),
                'anchor' => true,
            ),
        ) );
        
        // Register Image Column block
        acf_register_block_type( array(
            'name'              => 'image-column',
            'title'             => __( 'Image Column (60/40)', 'placy' ),
            'description'       => __( 'To bilder side ved side i 60/40 fordeling', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/image-column/template.php',
            'category'          => 'placy-content',
            'icon'              => 'images-alt2',
            'keywords'          => array( 'image', 'column', 'gallery', 'bilde' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
        ) );
        
        // Register POI Highlight block
        acf_register_block_type( array(
            'name'              => 'poi-highlight',
            'title'             => __( 'POI Highlight', 'placy' ),
            'description'       => __( 'Fremhevet POI med stor hero-layout', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/poi-highlight/template.php',
            'category'          => 'placy-content',
            'icon'              => 'star-filled',
            'keywords'          => array( 'poi', 'highlight', 'hero', 'fremhevet' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
        ) );
        
        // Register POI Gallery block
        acf_register_block_type( array(
            'name'              => 'poi-gallery',
            'title'             => __( 'POI Gallery', 'placy' ),
            'description'       => __( 'Vis flere POIs i et grid', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/poi-gallery/template.php',
            'category'          => 'placy-content',
            'icon'              => 'grid-view',
            'keywords'          => array( 'poi', 'gallery', 'grid', 'list' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
        ) );
    }
}
add_action( 'acf/init', 'placy_register_acf_blocks' );

/**
 * Register Chapter Wrapper Block
 */
function placy_register_chapter_wrapper_block() {
    register_block_type( get_template_directory() . '/blocks/chapter-wrapper' );
}
add_action( 'init', 'placy_register_chapter_wrapper_block' );

/**
 * Enqueue block editor styles and scripts (admin only)
 */
function placy_block_editor_styles() {
    // Enqueue all block styles in editor
    $blocks = array(
        'poi-map-card',
        'poi-list',
        'poi-highlight',
        'poi-gallery',
        'image-column'
    );
    
    foreach ( $blocks as $block ) {
        $style_file = get_template_directory() . '/blocks/' . $block . '/style.css';
        if ( file_exists( $style_file ) ) {
            wp_enqueue_style(
                'placy-block-' . $block,
                get_template_directory_uri() . '/blocks/' . $block . '/style.css',
                array(),
                filemtime( $style_file )
            );
        }
    }
    
    // Enqueue Adobe Typekit fonts in editor
    wp_enqueue_style( 
        'adobe-typekit-editor', 
        'https://use.typekit.net/jlp3dzl.css', 
        array(), 
        null 
    );
    
    // Enqueue Google Fonts (Figtree) in editor
    wp_enqueue_style( 
        'google-fonts-figtree-editor', 
        'https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800&display=swap', 
        array(), 
        null 
    );
    
    // Enqueue Chapter Wrapper block editor script
    wp_enqueue_script(
        'placy-chapter-wrapper-editor',
        get_template_directory_uri() . '/blocks/chapter-wrapper/block.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        '1.0.0',
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'placy_block_editor_styles' );
