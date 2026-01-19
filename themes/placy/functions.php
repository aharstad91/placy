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
    wp_enqueue_style( 'placy-typography', get_template_directory_uri() . '/css/typography.css', array(), filemtime( get_template_directory() . '/css/typography.css' ) );
    wp_enqueue_style( 'placy-tailwind', get_template_directory_uri() . '/css/tailwind-output.css', array( 'placy-typography' ), '1.0.0' );
    wp_enqueue_style( 'placy-custom', get_template_directory_uri() . '/css/styles.css', array(), '1.0.0' );
    wp_enqueue_style( 'placy-layout-chapters', get_template_directory_uri() . '/css/layout-chapters.css', array(), '1.0.0' );
    
    // Enqueue Font Awesome 6 for category icons
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1' );
    
    // Enqueue Adobe Typekit fonts
    wp_enqueue_style( 'adobe-typekit', 'https://use.typekit.net/jlp3dzl.css', array(), null );
    
    // Enqueue Google Fonts (Figtree)
    wp_enqueue_style( 'google-fonts-figtree', 'https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800&display=swap', array(), null );
    
    // Enqueue Mapbox GL JS
    wp_enqueue_style( 'mapbox-gl-css', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css', array(), '2.15.0' );
    wp_enqueue_script( 'mapbox-gl-js', 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js', array(), '2.15.0', true );

    // Mapbox utilities (hide POI labels, etc.) - load before other map scripts
    wp_enqueue_script( 'placy-mapbox-utils', get_template_directory_uri() . '/js/mapbox-utils.js', array( 'mapbox-gl-js' ), '2.0.0', true );

    // POI Map Modal script
    wp_enqueue_script( 'placy-poi-map-modal', get_template_directory_uri() . '/js/poi-map-modal.js', array( 'placy-mapbox-utils' ), '1.0.0', true );
    
    // Story Chapter styles and scripts (on project post type)
    if ( is_singular( 'project' ) ) {
        // Live data scripts for mobility blocks
        wp_enqueue_script( 'placy-entur-live-departures', get_template_directory_uri() . '/js/entur-live-departures.js', array(), '1.0.0', true );
        wp_enqueue_script( 'placy-bysykkel-live-availability', get_template_directory_uri() . '/js/bysykkel-live-availability.js', array(), '1.0.0', true );
        wp_enqueue_script( 'placy-hyre-live-availability', get_template_directory_uri() . '/js/hyre-live-availability.js', array(), '1.0.0', true );
        
        // POI API Accordion toggle functionality
        wp_enqueue_script( 'placy-poi-api-accordion', get_template_directory_uri() . '/js/poi-api-accordion.js', array(), '1.0.0', true );
        
        // Story Chapter Mega Modal CSS and JS (use time() for dev, filemtime for prod)
        wp_enqueue_style( 'placy-chapter-mega-modal', get_template_directory_uri() . '/css/chapter-mega-modal.css', array(), time() );
        wp_enqueue_script( 'placy-chapter-mega-modal', get_template_directory_uri() . '/js/chapter-mega-modal.js', array( 'placy-mapbox-utils' ), time(), true );
        
        // Project Sidebar CSS and JS (navigation + global settings)
        wp_enqueue_style( 'placy-project-sidebar', get_template_directory_uri() . '/css/project-sidebar.css', array(), '1.0.0' );
        wp_enqueue_script( 'placy-project-sidebar', get_template_directory_uri() . '/js/project-sidebar.js', array(), '1.0.0', true );
        
        // Neighborhood Story shared CSS (ns-* base classes)
        wp_enqueue_style( 'placy-neighborhood-story', get_template_directory_uri() . '/css/neighborhood-story.css', array(), '1.0.0' );
        
        // Travel Controls shared component CSS
        wp_enqueue_style( 'placy-travel-controls', get_template_directory_uri() . '/css/travel-controls.css', array( 'placy-neighborhood-story' ), '1.0.0' );
        
        // API Accordion component CSS and JS
        wp_enqueue_style( 'placy-api-accordion', get_template_directory_uri() . '/css/api-accordion.css', array( 'placy-neighborhood-story' ), '1.0.0' );
        wp_enqueue_script( 'placy-api-accordion', get_template_directory_uri() . '/js/api-accordion.js', array(), '1.0.0', true );
        
        // Master Map Modal CSS and JS (Open full map)
        wp_enqueue_style( 'placy-master-map-modal', get_template_directory_uri() . '/css/master-map-modal.css', array( 'placy-neighborhood-story' ), '1.0.0' );
        wp_enqueue_script( 'placy-master-map-modal', get_template_directory_uri() . '/js/master-map-modal.js', array( 'placy-mapbox-utils' ), '1.0.0', true );
        
        // Localize for Mapbox access and REST API URLs
        wp_localize_script( 'placy-chapter-mega-modal', 'placyMapbox', array(
            'accessToken' => placy_get_mapbox_token(),
            'restUrl' => esc_url_raw( rest_url( 'placy/v1/travel-calc' ) ),
            'restBaseUrl' => esc_url_raw( rest_url( 'placy/v1' ) ),
        ) );
        
        // Localize Entur script with REST API URL
        wp_localize_script( 'placy-entur-live-departures', 'enturSettings', array(
            'restUrl' => esc_url_raw( rest_url( 'placy/v1/entur/departures' ) ),
        ) );
        
        // Localize Bysykkel script with REST API URL
        wp_localize_script( 'placy-bysykkel-live-availability', 'bysykkelSettings', array(
            'restUrl' => esc_url_raw( rest_url( 'placy/v1/bysykkel/availability' ) ),
        ) );
        
        // Localize Hyre script with REST API URL
        wp_localize_script( 'placy-hyre-live-availability', 'hyreSettings', array(
            'restUrl' => esc_url_raw( rest_url( 'placy/v1/hyre/availability' ) ),
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
 * Include Google Places API integration
 */
require_once get_template_directory() . '/inc/google-places.php';

/**
 * Include Google Points CPT Query API
 */
require_once get_template_directory() . '/inc/google-points-query.php';

/**
 * Include Google Points Descriptions API
 */
require_once get_template_directory() . '/inc/google-points-descriptions-api.php';

/**
 * Include Placy Point System (New)
 */
require_once get_template_directory() . '/inc/placy-acf-fields.php';
require_once get_template_directory() . '/inc/placy-google-api.php';
require_once get_template_directory() . '/inc/placy-graphql.php';
require_once get_template_directory() . '/inc/placy-cron.php';
require_once get_template_directory() . '/inc/placy-admin.php';
require_once get_template_directory() . '/inc/placy-bulk-import.php';

/**
 * Include ACF Block Field Groups
 */
require_once get_template_directory() . '/inc/acf-chip-scrollytelling.php';
require_once get_template_directory() . '/inc/acf-focus-panel.php';
require_once get_template_directory() . '/inc/acf-story-chapter.php'; // Story Chapter ACF Block
require_once get_template_directory() . '/inc/acf-poi-api-blocks.php'; // POI API Blocks (Entur, Bysykkel, Hyre)

/**
 * Include Neighborhood Story controller (assets & config)
 */
require_once get_template_directory() . '/inc/neighborhood-story.php';

/**
 * Include Entur API integration
 */
require_once get_template_directory() . '/inc/entur-integration.php';

/**
 * Include Trondheim Bysykkel API integration
 */
require_once get_template_directory() . '/inc/bysykkel-integration.php';

/**
 * Include Hyre Car Sharing API integration
 */
require_once get_template_directory() . '/inc/hyre-integration.php';

/**
 * Include Mapbox Directions API proxy for Travel Calculator
 */
require_once get_template_directory() . '/inc/mapbox-directions-api.php';

// Legacy tema-story-patterns removed - Story Chapter blocks used instead
// Note: Sidebar navigation and global settings use ACF fields, no extra include needed

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
function placy_enqueue_block_assets() {
    // Map block names to their style files
    $block_styles = array(
        'poi-map-card'      => '/blocks/poi-map-card/style.css',
        'poi-list'          => '/blocks/poi-list/style.css',
        'poi-list-dynamic'  => '/blocks/poi-list-dynamic/style.css',
        'poi-highlight'     => '/blocks/poi-highlight/style.css',
        'poi-gallery'       => '/blocks/poi-gallery/style.css',
        'poi-entur'         => '/blocks/poi-entur/style.css',
        'poi-bysykkel'      => '/blocks/poi-bysykkel/style.css',
        'poi-hyre'          => '/blocks/poi-hyre/style.css',
        'image-column'      => '/blocks/image-column/style.css',
        'travel-mode-selector' => '/blocks/travel-mode-selector/style.css',
    );
    
    // Enqueue all block styles
    foreach ( $block_styles as $block_name => $style_path ) {
        $style_file = get_template_directory() . $style_path;
        
        // Only enqueue if file exists
        if ( file_exists( $style_file ) ) {
            $handle = 'placy-block-' . $block_name;
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
        
        // Register POI Entur block
        acf_register_block_type( array(
            'name'              => 'poi-entur',
            'title'             => __( 'POI Entur', 'placy' ),
            'description'       => __( 'Velg én Entur-stoppeplass med live avganger', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/poi-entur/template.php',
            'category'          => 'placy-content',
            'icon'              => 'bus',
            'keywords'          => array( 'poi', 'entur', 'bus', 'transport', 'avganger' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/poi-entur/style.css',
        ) );
        
        // Register POI Bysykkel block
        acf_register_block_type( array(
            'name'              => 'poi-bysykkel',
            'title'             => __( 'POI Bysykkel', 'placy' ),
            'description'       => __( 'Velg én Bysykkel-stasjon med live tilgjengelighet', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/poi-bysykkel/template.php',
            'category'          => 'placy-content',
            'icon'              => 'admin-site',
            'keywords'          => array( 'poi', 'bysykkel', 'sykkel', 'bike', 'trondheim' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/poi-bysykkel/style.css',
        ) );
        
        // Register POI Hyre block
        acf_register_block_type( array(
            'name'              => 'poi-hyre',
            'title'             => __( 'POI Hyre', 'placy' ),
            'description'       => __( 'Velg én Hyre-stasjon med live bildeling tilgjengelighet', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/poi-hyre/template.php',
            'category'          => 'placy-content',
            'icon'              => 'car',
            'keywords'          => array( 'poi', 'hyre', 'car', 'bil', 'sharing', 'deling' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/poi-hyre/style.css',
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
        
        // Register Travel Calculator block
        acf_register_block_type( array(
            'name'              => 'travel-calculator',
            'title'             => __( 'Travel Calculator', 'placy' ),
            'description'       => __( 'Beregn reisetid fra adresse til eiendommen', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/travel-calculator/template.php',
            'category'          => 'placy-content',
            'icon'              => 'clock',
            'keywords'          => array( 'travel', 'reisetid', 'calculator', 'kalkulator', 'avstand' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_assets'    => function() {
                wp_enqueue_style( 'placy-travel-calculator', get_template_directory_uri() . '/blocks/travel-calculator/style.css', array(), '1.0.0' );
                wp_enqueue_script( 'placy-travel-calculator', get_template_directory_uri() . '/js/travel-calculator.js', array( 'placy-mapbox-utils' ), '1.0.0', true );
                wp_localize_script( 'placy-travel-calculator', 'travelCalcSettings', array(
                    'restUrl' => esc_url_raw( rest_url( 'placy/v1/travel-calc' ) ),
                    'mapboxToken' => placy_get_mapbox_token(),
                ) );
            },
        ) );
        
        // Register Chapter Index block
        acf_register_block_type( array(
            'name'              => 'chapter-index',
            'title'             => __( 'Kapittel Indeks', 'placy' ),
            'description'       => __( 'Navigasjonspiller for seksjoner innenfor et kapittel', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/chapter-index/template.php',
            'category'          => 'placy-content',
            'icon'              => 'list-view',
            'keywords'          => array( 'index', 'indeks', 'navigation', 'kapittel', 'chapter' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/chapter-index/style.css',
        ) );
        
        // Register Proximity Timeline block
        acf_register_block_type( array(
            'name'              => 'proximity-timeline',
            'title'             => __( 'Nærhet Timeline', 'placy' ),
            'description'       => __( 'Viser nærhetsavstander med timeline og dynamisk tidsberegning', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/proximity-timeline/template.php',
            'category'          => 'placy-content',
            'icon'              => 'clock',
            'keywords'          => array( 'proximity', 'timeline', 'nærhet', 'avstand', 'tid' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => array( 'wide', 'full' ),
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/proximity-timeline/style.css',
            'enqueue_script'    => get_template_directory_uri() . '/blocks/proximity-timeline/script.js',
        ) );
        
        // Register Travel Mode Selector block
        acf_register_block_type( array(
            'name'              => 'travel-mode-selector',
            'title'             => __( 'Reisetid Velger', 'placy' ),
            'description'       => __( 'Viser knapper for valg av reisemodus (gange/sykkel/bil)', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/travel-mode-selector/template.php',
            'category'          => 'placy-content',
            'icon'              => 'admin-site-alt3',
            'keywords'          => array( 'travel', 'mode', 'transport', 'reisetid', 'gange', 'sykkel', 'bil' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/travel-mode-selector/style.css',
            'enqueue_script'    => get_template_directory_uri() . '/blocks/travel-mode-selector/script.js',
        ) );
        
        // ===========================================
        // CHAPTER BLOCKS - For use inside chapter-wrapper
        // ===========================================
        
        // Register Chapter Heading block
        acf_register_block_type( array(
            'name'              => 'chapter-heading',
            'title'             => __( 'Kapittel Overskrift', 'placy' ),
            'description'       => __( 'Standardisert overskrift for kapitler med kontroll på hierarki og stil', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/chapter-heading/template.php',
            'category'          => 'placy-chapters',
            'icon'              => 'heading',
            'keywords'          => array( 'heading', 'overskrift', 'kapittel', 'tittel', 'h2', 'h3' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/css/chapter-blocks.css',
        ) );
        
        // Register Chapter Text block
        acf_register_block_type( array(
            'name'              => 'chapter-text',
            'title'             => __( 'Kapittel Tekst', 'placy' ),
            'description'       => __( 'Kontrollert brødtekstblokk med varianter og bredde-innstillinger', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/chapter-text/template.php',
            'category'          => 'placy-chapters',
            'icon'              => 'editor-paragraph',
            'keywords'          => array( 'text', 'tekst', 'paragraph', 'avsnitt', 'brødtekst' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/css/chapter-blocks.css',
        ) );
        
        // Register Chapter Image block
        acf_register_block_type( array(
            'name'              => 'chapter-image',
            'title'             => __( 'Kapittel Bilde', 'placy' ),
            'description'       => __( 'Full kontroll på bilder i kapitler med aspect ratio og stilvalg', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/chapter-image/template.php',
            'category'          => 'placy-chapters',
            'icon'              => 'format-image',
            'keywords'          => array( 'image', 'bilde', 'foto', 'media' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/css/chapter-blocks.css',
        ) );
        
        // Register Chapter List block
        acf_register_block_type( array(
            'name'              => 'chapter-list',
            'title'             => __( 'Kapittel Liste', 'placy' ),
            'description'       => __( 'Standardisert listeblokk med punkter, nummerering eller checkliste', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/chapter-list/template.php',
            'category'          => 'placy-chapters',
            'icon'              => 'editor-ul',
            'keywords'          => array( 'list', 'liste', 'punkter', 'checklist', 'nummerert' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/css/chapter-blocks.css',
        ) );
        
        // Register Chapter Spacer block
        acf_register_block_type( array(
            'name'              => 'chapter-spacer',
            'title'             => __( 'Kapittel Mellomrom', 'placy' ),
            'description'       => __( 'Kontrollert vertikal luft mellom elementer', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/chapter-spacer/template.php',
            'category'          => 'placy-chapters',
            'icon'              => 'arrow-down-alt2',
            'keywords'          => array( 'spacer', 'mellomrom', 'luft', 'margin', 'padding' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/css/chapter-blocks.css',
        ) );
        
        // Register Feature Spotlight block
        acf_register_block_type( array(
            'name'              => 'feature-spotlight',
            'title'             => __( 'Feature Spotlight', 'placy' ),
            'description'       => __( 'Apple-stil "Ta en nærmere titt" komponent med ekspanderbare kort og bakgrunnsbilder', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/feature-spotlight/template.php',
            'category'          => 'placy-content',
            'icon'              => 'visibility',
            'keywords'          => array( 'feature', 'spotlight', 'apple', 'cards', 'expand', 'nærmere', 'titt' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => array( 'wide', 'full' ),
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/feature-spotlight/style.css',
            'enqueue_script'    => get_template_directory_uri() . '/blocks/feature-spotlight/script.js',
        ) );
        
        // Register Chip Scrollytelling block
        acf_register_block_type( array(
            'name'              => 'chip-scrollytelling',
            'title'             => __( 'Chip Scrollytelling', 'placy' ),
            'description'       => __( 'Apple-inspirert sticky scrollytelling med segmented control', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/chip-scrollytelling/template.php',
            'category'          => 'placy-content',
            'icon'              => 'slides',
            'keywords'          => array( 'chip', 'scrollytelling', 'sticky', 'apple', 'progress', 'tabs', 'm5' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => array( 'wide', 'full' ),
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/chip-scrollytelling/style.css',
            'enqueue_script'    => get_template_directory_uri() . '/blocks/chip-scrollytelling/script.js',
        ) );
        
        // Register Focus Panel block
        acf_register_block_type( array(
            'name'              => 'focus-panel',
            'title'             => __( 'Focus Panel', 'placy' ),
            'description'       => __( 'Apple-inspirert sentrert modal/drawer med blur-overlay for detaljert innhold', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/focus-panel/template.php',
            'category'          => 'placy-content',
            'icon'              => 'external',
            'keywords'          => array( 'focus', 'panel', 'modal', 'drawer', 'les mer', 'read more', 'overlay', 'popup' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/focus-panel/style.css',
            'enqueue_script'    => get_template_directory_uri() . '/blocks/focus-panel/script.js',
        ) );
        
        // ===========================================
        // MOBILITY BLOCKS - Transport/commute cards
        // ===========================================
        
        // Register Bysykkel Stations block
        acf_register_block_type( array(
            'name'              => 'bysykkel-stations',
            'title'             => __( 'Bysykkel Stasjoner', 'placy' ),
            'description'       => __( 'Accordion-liste med Trondheim Bysykkel stasjoner og sanntids tilgjengelighet', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/bysykkel-stations/template.php',
            'category'          => 'placy-content',
            'icon'              => 'admin-site-alt3',
            'keywords'          => array( 'bysykkel', 'sykkel', 'bike', 'stasjoner', 'stations', 'transport' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/bysykkel-stations/style.css',
            'enqueue_script'    => get_template_directory_uri() . '/blocks/bysykkel-stations/script.js',
        ) );
        
        // Register Hyre Stations block
        acf_register_block_type( array(
            'name'              => 'hyre-stations',
            'title'             => __( 'Hyre Stasjoner', 'placy' ),
            'description'       => __( 'Accordion-liste med Hyre bildeling stasjoner og sanntids tilgjengelighet', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/hyre-stations/template.php',
            'category'          => 'placy-content',
            'icon'              => 'car',
            'keywords'          => array( 'hyre', 'bil', 'car', 'sharing', 'bildeling', 'transport' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/hyre-stations/style.css',
            'enqueue_script'    => get_template_directory_uri() . '/blocks/hyre-stations/script.js',
        ) );
        
        // Register Bus Stops block
        acf_register_block_type( array(
            'name'              => 'bus-stops',
            'title'             => __( 'Buss Holdeplasser', 'placy' ),
            'description'       => __( 'Accordion-liste med bussholdeplasser og sanntids avganger fra Entur', 'placy' ),
            'render_template'   => get_template_directory() . '/blocks/bus-stops/template.php',
            'category'          => 'placy-content',
            'icon'              => 'admin-site-alt2',
            'keywords'          => array( 'buss', 'bus', 'holdeplass', 'stops', 'transport', 'flybuss', 'entur' ),
            'mode'              => 'preview',
            'supports'          => array(
                'align' => false,
                'anchor' => true,
            ),
            'enqueue_style'     => get_template_directory_uri() . '/blocks/bus-stops/style.css',
            'enqueue_script'    => get_template_directory_uri() . '/blocks/bus-stops/script.js',
        ) );
    }
}
add_action( 'acf/init', 'placy_register_acf_blocks' );

/**
 * Register POI List Dynamic Block
 */
function placy_register_poi_list_dynamic_block() {
    $block_path = get_template_directory() . '/blocks/poi-list-dynamic';
    
    if ( file_exists( $block_path . '/block.json' ) ) {
        $result = register_block_type( $block_path );
        if ( is_wp_error( $result ) ) {
            error_log( 'POI List Dynamic registration error: ' . $result->get_error_message() );
        }
    } else {
        error_log( 'POI List Dynamic block.json not found at: ' . $block_path );
    }
}
add_action( 'init', 'placy_register_poi_list_dynamic_block' );

/**
 * Enqueue block editor styles and scripts (admin only)
 */
function placy_block_editor_styles() {
    // Enqueue Story Chapter editor styles
    $story_chapter_editor_css = get_template_directory() . '/blocks/story-chapter/editor.css';
    if ( file_exists( $story_chapter_editor_css ) ) {
        wp_enqueue_style(
            'placy-story-chapter-editor',
            get_template_directory_uri() . '/blocks/story-chapter/editor.css',
            array(),
            filemtime( $story_chapter_editor_css )
        );
    }
    
    // Enqueue POI List Dynamic block editor script (manual enqueue as fallback)
    $block_js = get_template_directory() . '/blocks/poi-list-dynamic/block.js';
    if ( file_exists( $block_js ) ) {
        wp_enqueue_script(
            'placy-poi-list-dynamic-editor',
            get_template_directory_uri() . '/blocks/poi-list-dynamic/block.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
            filemtime( $block_js ),
            true
        );
    }
    
    // Enqueue all block styles in editor
    $blocks = array(
        'poi-map-card',
        'poi-list',
        'poi-list-dynamic',
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
}
add_action( 'enqueue_block_editor_assets', 'placy_block_editor_styles' );
