<?php
/**
 * Register Custom Post Types
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Customer Post Type
 */
function placy_register_customer_post_type() {
    $labels = array(
        'name'                  => 'Kunder',
        'singular_name'         => 'Kunde',
        'menu_name'             => 'Kunder',
        'add_new'               => 'Legg til ny',
        'add_new_item'          => 'Legg til ny kunde',
        'edit_item'             => 'Rediger kunde',
        'new_item'              => 'Ny kunde',
        'view_item'             => 'Vis kunde',
        'search_items'          => 'Søk kunder',
        'not_found'             => 'Ingen kunder funnet',
        'not_found_in_trash'    => 'Ingen kunder funnet i papirkurv',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'kunde' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-building',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'          => true,
    );

    register_post_type( 'customer', $args );
}
add_action( 'init', 'placy_register_customer_post_type' );

/**
 * Register Project Post Type
 */
function placy_register_project_post_type() {
    $labels = array(
        'name'                  => 'Prosjekter',
        'singular_name'         => 'Prosjekt',
        'menu_name'             => 'Prosjekter',
        'add_new'               => 'Legg til nytt',
        'add_new_item'          => 'Legg til nytt prosjekt',
        'edit_item'             => 'Rediger prosjekt',
        'new_item'              => 'Nytt prosjekt',
        'view_item'             => 'Vis prosjekt',
        'search_items'          => 'Søk prosjekter',
        'not_found'             => 'Ingen prosjekter funnet',
        'not_found_in_trash'    => 'Ingen prosjekter funnet i papirkurv',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'prosjekt' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 21,
        'menu_icon'             => 'dashicons-portfolio',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'          => true,
    );

    register_post_type( 'project', $args );
}
add_action( 'init', 'placy_register_project_post_type' );

/**
 * Register Native Point Post Type
 */
function placy_register_native_point_post_type() {
    $labels = array(
        'name'                  => 'Native Points',
        'singular_name'         => 'Native Point',
        'menu_name'             => 'Native Points',
        'add_new'               => 'Add Native Point',
        'add_new_item'          => 'Add New Native Point',
        'edit_item'             => 'Edit Native Point',
        'new_item'              => 'New Native Point',
        'view_item'             => 'View Native Point',
        'search_items'          => 'Search Native Points',
        'not_found'             => 'No native points found',
        'not_found_in_trash'    => 'No native points found in trash',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => false,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'native-points' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 23,
        'menu_icon'             => 'dashicons-location-alt',
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest'          => true,
        'show_in_graphql'       => true,
        'graphql_single_name'   => 'NativePoint',
        'graphql_plural_name'   => 'NativePoints',
    );

    register_post_type( 'placy_native_point', $args );
}
add_action( 'init', 'placy_register_native_point_post_type' );

/**
 * Register Google Point Post Type
 */
function placy_register_google_point_post_type() {
    $labels = array(
        'name'                  => 'Google Points',
        'singular_name'         => 'Google Point',
        'menu_name'             => 'Google Points',
        'add_new'               => 'Add Google Point',
        'add_new_item'          => 'Add New Google Point',
        'edit_item'             => 'Edit Google Point',
        'new_item'              => 'New Google Point',
        'view_item'             => 'View Google Point',
        'search_items'          => 'Search Google Points',
        'not_found'             => 'No google points found',
        'not_found_in_trash'    => 'No google points found in trash',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => false,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'google-points' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 24,
        'menu_icon'             => 'dashicons-location',
        'supports'              => array( 'title' ),
        'show_in_rest'          => true,
        'show_in_graphql'       => true,
        'graphql_single_name'   => 'GooglePoint',
        'graphql_plural_name'   => 'GooglePoints',
    );

    register_post_type( 'placy_google_point', $args );
}
add_action( 'init', 'placy_register_google_point_post_type' );

/**
 * Optimize Google Points admin list performance
 */
function placy_optimize_google_points_admin( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }
    
    // Limit posts per page for Google Points admin screen
    if ( $query->get('post_type') === 'placy_google_point' ) {
        $query->set( 'posts_per_page', 50 ); // Limit to 50 per page
        $query->set( 'no_found_rows', false ); // Enable pagination
    }
}
add_action( 'pre_get_posts', 'placy_optimize_google_points_admin' );

/**
 * Add custom columns to Google Points admin list
 */
function placy_google_point_custom_columns( $columns ) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['rating'] = 'Rating';
    $new_columns['project'] = 'Project';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter( 'manage_placy_google_point_posts_columns', 'placy_google_point_custom_columns' );

/**
 * Populate custom columns
 */
function placy_google_point_custom_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'rating':
            $rating = get_field( 'google_rating', $post_id );
            $reviews = get_field( 'google_user_ratings_total', $post_id );
            if ( $rating ) {
                echo '⭐ ' . esc_html( $rating );
                if ( $reviews ) {
                    echo ' (' . esc_html( $reviews ) . ')';
                }
            } else {
                echo '—';
            }
            break;
        
        case 'project':
            $project = get_field( 'project', $post_id );
            if ( $project ) {
                echo '<a href="' . get_edit_post_link( $project->ID ) . '">' . esc_html( $project->post_title ) . '</a>';
            } else {
                echo '—';
            }
            break;
    }
}
add_action( 'manage_placy_google_point_posts_custom_column', 'placy_google_point_custom_column_content', 10, 2 );

/**
 * Add database index for google_place_id for faster duplicate checking
 * Run once on theme activation
 */
function placy_add_google_place_id_index() {
    global $wpdb;
    
    // Check if index already exists
    $index_exists = $wpdb->get_var(
        "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'google_place_id_index'"
    );
    
    if ( ! $index_exists ) {
        // Add index on meta_key and meta_value for google_place_id lookups
        $wpdb->query(
            "ALTER TABLE {$wpdb->postmeta} 
            ADD INDEX google_place_id_index (meta_key(50), meta_value(100))"
        );
    }
}
add_action( 'after_switch_theme', 'placy_add_google_place_id_index' );

/**
 * Register Detail Post Type
 */
function placy_register_detail_post_type() {
    $labels = array(
        'name'                  => 'Details',
        'singular_name'         => 'Detail',
        'menu_name'             => 'Details',
        'add_new'               => 'Legg til ny',
        'add_new_item'          => 'Legg til ny Detail',
        'edit_item'             => 'Rediger Detail',
        'new_item'              => 'Ny Detail',
        'view_item'             => 'Vis Detail',
        'search_items'          => 'Søk Details',
        'not_found'             => 'Ingen Details funnet',
        'not_found_in_trash'    => 'Ingen Details funnet i papirkurv',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'detail' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 24,
        'menu_icon'             => 'dashicons-admin-site-alt3',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'          => true,
    );

    register_post_type( 'detail', $args );
}
add_action( 'init', 'placy_register_detail_post_type' );

/**
 * Register Area Post Type
 */
function placy_register_area_post_type() {
    $labels = array(
        'name'                  => 'Areas',
        'singular_name'         => 'Area',
        'menu_name'             => 'Areas',
        'add_new'               => 'Legg til ny',
        'add_new_item'          => 'Legg til ny Area',
        'edit_item'             => 'Rediger Area',
        'new_item'              => 'Ny Area',
        'view_item'             => 'Vis Area',
        'search_items'          => 'Søk Areas',
        'not_found'             => 'Ingen Areas funnet',
        'not_found_in_trash'    => 'Ingen Areas funnet i papirkurv',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'area' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-admin-multisite',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'          => true,
    );

    register_post_type( 'area', $args );
}
add_action( 'init', 'placy_register_area_post_type' );

/**
 * Register Theme Story Post Type
 */
function placy_register_theme_story_post_type() {
    $labels = array(
        'name'                  => 'Tema Historier',
        'singular_name'         => 'Tema Historie',
        'menu_name'             => 'Tema Historier',
        'add_new'               => 'Legg til ny',
        'add_new_item'          => 'Legg til ny tema historie',
        'edit_item'             => 'Rediger tema historie',
        'new_item'              => 'Ny tema historie',
        'view_item'             => 'Vis tema historie',
        'search_items'          => 'Søk tema historier',
        'not_found'             => 'Ingen tema historier funnet',
        'not_found_in_trash'    => 'Ingen tema historier funnet i papirkurv',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'tema-historie' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 26,
        'menu_icon'             => 'dashicons-book-alt',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
        'show_in_rest'          => true,
    );

    register_post_type( 'theme-story', $args );
}
add_action( 'init', 'placy_register_theme_story_post_type' );

/**
 * Register Placy Taxonomies
 */
function placy_register_point_taxonomies() {
    $point_types = array( 'placy_native_point', 'placy_google_point' );
    
    // Categories
    $cat_labels = array(
        'name'                       => 'Categories',
        'singular_name'              => 'Category',
        'menu_name'                  => 'Categories',
        'all_items'                  => 'All Categories',
        'edit_item'                  => 'Edit Category',
        'view_item'                  => 'View Category',
        'update_item'                => 'Update Category',
        'add_new_item'               => 'Add New Category',
        'new_item_name'              => 'New Category Name',
        'search_items'               => 'Search Categories',
        'parent_item'                => 'Parent Category',
        'parent_item_colon'          => 'Parent Category:',
        'not_found'                  => 'No categories found',
    );

    register_taxonomy( 'placy_categories', $point_types, array(
        'labels'                => $cat_labels,
        'hierarchical'          => true,
        'public'                => true,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'show_in_rest'          => true,
        'show_in_graphql'       => true,
        'graphql_single_name'   => 'PlacyCategory',
        'graphql_plural_name'   => 'PlacyCategories',
        'rewrite'               => array( 'slug' => 'poi-category' ),
    ) );
    
    // Tags
    $tag_labels = array(
        'name'                       => 'Tags',
        'singular_name'              => 'Tag',
        'menu_name'                  => 'Tags',
        'all_items'                  => 'All Tags',
        'edit_item'                  => 'Edit Tag',
        'view_item'                  => 'View Tag',
        'update_item'                => 'Update Tag',
        'add_new_item'               => 'Add New Tag',
        'new_item_name'              => 'New Tag Name',
        'search_items'               => 'Search Tags',
        'not_found'                  => 'No tags found',
    );

    register_taxonomy( 'placy_tags', $point_types, array(
        'labels'                => $tag_labels,
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'show_in_rest'          => true,
        'show_in_graphql'       => true,
        'graphql_single_name'   => 'PlacyTag',
        'graphql_plural_name'   => 'PlacyTags',
        'rewrite'               => array( 'slug' => 'poi-tag' ),
    ) );
    
    // Lifestyle Segments
    $lifestyle_labels = array(
        'name'                       => 'Lifestyle Segments',
        'singular_name'              => 'Lifestyle Segment',
        'menu_name'                  => 'Lifestyle Segments',
        'all_items'                  => 'All Lifestyle Segments',
        'edit_item'                  => 'Edit Lifestyle Segment',
        'view_item'                  => 'View Lifestyle Segment',
        'update_item'                => 'Update Lifestyle Segment',
        'add_new_item'               => 'Add New Lifestyle Segment',
        'new_item_name'              => 'New Lifestyle Segment Name',
        'search_items'               => 'Search Lifestyle Segments',
        'not_found'                  => 'No lifestyle segments found',
    );

    register_taxonomy( 'lifestyle_segments', $point_types, array(
        'labels'                => $lifestyle_labels,
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'show_in_rest'          => true,
        'show_in_graphql'       => true,
        'graphql_single_name'   => 'LifestyleSegment',
        'graphql_plural_name'   => 'LifestyleSegments',
        'rewrite'               => array( 'slug' => 'lifestyle' ),
    ) );
}
add_action( 'init', 'placy_register_point_taxonomies' );

