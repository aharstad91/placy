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
 * Register POI Post Type
 */
function placy_register_poi_post_type() {
    $labels = array(
        'name'                  => 'POIs',
        'singular_name'         => 'POI',
        'menu_name'             => 'POIs',
        'add_new'               => 'Legg til ny',
        'add_new_item'          => 'Legg til ny POI',
        'edit_item'             => 'Rediger POI',
        'new_item'              => 'Ny POI',
        'view_item'             => 'Vis POI',
        'search_items'          => 'Søk POIs',
        'not_found'             => 'Ingen POIs funnet',
        'not_found_in_trash'    => 'Ingen POIs funnet i papirkurv',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'poi' ),
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'menu_position'         => 23,
        'menu_icon'             => 'dashicons-location-alt',
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'          => true,
    );

    register_post_type( 'poi', $args );
}
add_action( 'init', 'placy_register_poi_post_type' );
