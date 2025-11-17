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
 * Register Story Post Type
 */
function placy_register_story_post_type() {
    $labels = array(
        'name'                  => 'Historier',
        'singular_name'         => 'Historie',
        'menu_name'             => 'Historier',
        'add_new'               => 'Legg til ny',
        'add_new_item'          => 'Legg til ny historie',
        'edit_item'             => 'Rediger historie',
        'new_item'              => 'Ny historie',
        'view_item'             => 'Vis historie',
        'search_items'          => 'Søk historier',
        'not_found'             => 'Ingen historier funnet',
        'not_found_in_trash'    => 'Ingen historier funnet i papirkurv',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => false, // Use custom rewrite rules
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 22,
        'menu_icon'             => 'dashicons-location',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'          => true,
    );

    register_post_type( 'story', $args );
}
add_action( 'init', 'placy_register_story_post_type' );

/**
 * Register Point Post Type
 */
function placy_register_point_post_type() {
    $labels = array(
        'name'                  => 'Points',
        'singular_name'         => 'Point',
        'menu_name'             => 'Points',
        'add_new'               => 'Legg til ny',
        'add_new_item'          => 'Legg til ny Point',
        'edit_item'             => 'Rediger Point',
        'new_item'              => 'Ny Point',
        'view_item'             => 'Vis Point',
        'search_items'          => 'Søk Points',
        'not_found'             => 'Ingen Points funnet',
        'not_found_in_trash'    => 'Ingen Points funnet i papirkurv',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'point' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 23,
        'menu_icon'             => 'dashicons-location-alt',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'          => true,
    );

    register_post_type( 'point', $args );
}
add_action( 'init', 'placy_register_point_post_type' );

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
 * Register Point Type Taxonomy
 */
function placy_register_point_type_taxonomy() {
    $labels = array(
        'name'                       => 'Point Typer',
        'singular_name'              => 'Point Type',
        'menu_name'                  => 'Point Typer',
        'all_items'                  => 'Alle Point Typer',
        'edit_item'                  => 'Rediger Point Type',
        'view_item'                  => 'Vis Point Type',
        'update_item'                => 'Oppdater Point Type',
        'add_new_item'               => 'Legg til ny Point Type',
        'new_item_name'              => 'Ny Point Type Navn',
        'parent_item'                => 'Forelder Point Type',
        'parent_item_colon'          => 'Forelder Point Type:',
        'search_items'               => 'Søk Point Typer',
        'popular_items'              => 'Populære Point Typer',
        'separate_items_with_commas' => 'Separer Point Typer med komma',
        'add_or_remove_items'        => 'Legg til eller fjern Point Typer',
        'choose_from_most_used'      => 'Velg fra mest brukte Point Typer',
        'not_found'                  => 'Ingen Point Typer funnet',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'show_in_nav_menus'     => true,
        'show_in_rest'          => true,
        'show_tagcloud'         => true,
        'show_in_quick_edit'    => true,
        'show_admin_column'     => true,
        'hierarchical'          => true,
        'query_var'             => true,
        'rewrite'               => array( 
            'slug' => 'point-type',
            'with_front' => false,
            'hierarchical' => true,
        ),
    );

    register_taxonomy( 'point_type', array( 'point' ), $args );
}
add_action( 'init', 'placy_register_point_type_taxonomy' );

