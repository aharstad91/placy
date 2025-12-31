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
 * URL format: /kunde-slug/prosjekt-slug/
 */
function placy_custom_rewrites() {
    // Pattern: customer-slug/project-slug (for projects)
    // Exclude tema-historie URLs
    add_rewrite_rule(
        '^(?!tema-historie)([^/]+)/([^/]+)/?$',
        'index.php?post_type=project&name=$matches[2]&customer_slug=$matches[1]',
        'top'
    );
}
add_action( 'init', 'placy_custom_rewrites' );

/**
 * Add custom query vars
 */
function placy_query_vars( $vars ) {
    $vars[] = 'customer_slug';
    $vars[] = 'project_slug';
    return $vars;
}
add_filter( 'query_vars', 'placy_query_vars' );

/**
 * Modify project permalinks to include customer slug
 * URL format: /kunde-slug/prosjekt-slug/
 */
function placy_custom_permalinks( $post_link, $post ) {
    if ( $post->post_type === 'project' ) {
        $customer = get_field( 'customer', $post->ID );
        
        if ( $customer ) {
            $customer_slug = $customer->post_name;
            $project_slug = $post->post_name;
            return home_url( "/{$customer_slug}/{$project_slug}/" );
        }
    }

    return $post_link;
}
add_filter( 'post_type_link', 'placy_custom_permalinks', 10, 2 );

/**
 * Flush rewrite rules on theme activation
 */
function placy_flush_rewrites() {
    placy_custom_rewrites();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'placy_flush_rewrites' );

/**
 * Force flush rewrite rules when needed
 * Call this function manually or on init if permalinks aren't working
 */
function placy_maybe_flush_rewrites() {
    $flush = get_option( 'placy_flush_rewrite_rules' );
    if ( $flush !== 'done_v5' ) {
        flush_rewrite_rules();
        update_option( 'placy_flush_rewrite_rules', 'done_v5' );
    }
}
add_action( 'init', 'placy_maybe_flush_rewrites', 999 );
