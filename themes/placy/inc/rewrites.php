<?php
/**
 * Custom URL Rewrites
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add custom rewrite rules for customer/project structure
 */
function placy_custom_rewrites() {
    // Pattern: customer-slug/project-slug
    add_rewrite_rule(
        '^([^/]+)/([^/]+)/?$',
        'index.php?post_type=project&name=$matches[2]&customer_slug=$matches[1]',
        'top'
    );
}
add_action( 'init', 'placy_custom_rewrites' );

/**
 * Add customer_slug query var
 */
function placy_query_vars( $vars ) {
    $vars[] = 'customer_slug';
    return $vars;
}
add_filter( 'query_vars', 'placy_query_vars' );

/**
 * Modify project permalink to include customer slug
 */
function placy_project_permalink( $post_link, $post ) {
    if ( $post->post_type !== 'project' ) {
        return $post_link;
    }

    $customer = get_field( 'customer', $post->ID );
    
    if ( $customer ) {
        $customer_slug = $customer->post_name;
        $project_slug = $post->post_name;
        return home_url( "/{$customer_slug}/{$project_slug}/" );
    }

    return $post_link;
}
add_filter( 'post_type_link', 'placy_project_permalink', 10, 2 );

/**
 * Flush rewrite rules on theme activation
 */
function placy_flush_rewrites() {
    placy_custom_rewrites();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'placy_flush_rewrites' );
