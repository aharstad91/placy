<?php
/**
 * Neighborhood Story - Main Controller
 * 
 * Handles:
 * - Asset registration and enqueuing
 * - Template loading
 * - Data preparation for frontend
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include REST API endpoints
require_once get_template_directory() . '/inc/neighborhood-story-api.php';

/**
 * Register and enqueue Neighborhood Story assets
 */
function placy_enqueue_neighborhood_story_assets() {
    // Only load on project single pages (or customize this condition)
    if ( ! is_singular( 'project' ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();
    $theme_dir = get_template_directory();
    $version = wp_get_theme()->get( 'Version' ) ?: '1.0.0';

    // CSS
    wp_enqueue_style(
        'neighborhood-story',
        $theme_uri . '/css/neighborhood-story.css',
        array(),
        filemtime( $theme_dir . '/css/neighborhood-story.css' )
    );

    // JavaScript
    wp_enqueue_script(
        'neighborhood-story',
        $theme_uri . '/js/neighborhood-story.js',
        array(),
        filemtime( $theme_dir . '/js/neighborhood-story.js' ),
        true // Load in footer
    );

    // Prepare config data for JavaScript
    $project_id = get_the_ID();
    $config = placy_get_neighborhood_story_config( $project_id );

    wp_localize_script( 'neighborhood-story', 'neighborhoodStoryConfig', $config );
}
add_action( 'wp_enqueue_scripts', 'placy_enqueue_neighborhood_story_assets' );

/**
 * Get configuration data for Neighborhood Story
 *
 * @param int $project_id Project post ID.
 * @return array Configuration array.
 */
function placy_get_neighborhood_story_config( $project_id ) {
    // Get global settings
    $default_travel_mode = get_field( 'default_travel_mode', $project_id ) ?: 'walk';
    $default_time_budget = get_field( 'default_time_budget', $project_id ) ?: '10';
    $enable_global_map = get_field( 'enable_global_map', $project_id );

    // Get project center coordinates
    $start_lat = get_field( 'start_latitude', $project_id );
    $start_lng = get_field( 'start_longitude', $project_id );

    // Collect all points from all chapters
    $all_points = placy_collect_all_project_points( $project_id );

    return array(
        'projectId'         => $project_id,
        'defaultTravelMode' => $default_travel_mode,
        'defaultTimeBudget' => (int) $default_time_budget,
        'enableGlobalMap'   => $enable_global_map !== false,
        'center'            => array(
            'lat' => $start_lat ? (float) $start_lat : 63.4305,
            'lng' => $start_lng ? (float) $start_lng : 10.3951,
        ),
        'allPoints'         => $all_points,
        'restUrl'           => rest_url( 'placy/v1/' ),
        'nonce'             => wp_create_nonce( 'wp_rest' ),
    );
}

/**
 * Collect all points from all story chapters
 *
 * @param int $project_id Project post ID.
 * @return array All points with metadata.
 */
function placy_collect_all_project_points( $project_id ) {
    $chapters = get_field( 'story_chapters', $project_id );
    $all_points = array();
    $seen_ids = array();

    if ( ! $chapters ) {
        return $all_points;
    }

    foreach ( $chapters as $index => $chapter ) {
        $theme_story = $chapter['theme_story'];
        if ( ! $theme_story ) continue;

        $chapter_anchor = $chapter['anchor_id'] ?? 'chapter-' . $index;
        $chapter_title = $chapter['front_title'] ?: get_the_title( $theme_story );

        $locations = get_field( 'all_locations', $theme_story->ID );
        if ( ! $locations ) continue;

        foreach ( $locations as $point ) {
            $point_id = is_object( $point ) ? $point->ID : $point;

            // Skip duplicates
            if ( in_array( $point_id, $seen_ids, true ) ) continue;
            $seen_ids[] = $point_id;

            $point_data = placy_get_point_data_for_js( $point_id );
            $point_data['chapterId'] = $chapter_anchor;
            $point_data['chapterTitle'] = $chapter_title;

            $all_points[] = $point_data;
        }
    }

    return $all_points;
}

/**
 * Get point data formatted for JavaScript
 *
 * @param int $point_id Point post ID.
 * @return array Point data.
 */
function placy_get_point_data_for_js( $point_id ) {
    $point_type = get_post_type( $point_id );

    // Get category
    $category = '';
    if ( $point_type === 'placy_google_point' ) {
        $category = get_field( 'type_label', $point_id ) ?: get_field( 'type', $point_id );
    } else {
        $terms = get_the_terms( $point_id, 'poi_category' );
        $category = $terms ? $terms[0]->name : '';
    }

    // Get coordinates
    $lat = get_field( 'latitude', $point_id ) ?: get_post_meta( $point_id, 'latitude', true );
    $lng = get_field( 'longitude', $point_id ) ?: get_post_meta( $point_id, 'longitude', true );

    // Get cached travel times
    $walk_time = (int) ( get_field( 'cached_walk_time', $point_id ) ?: get_post_meta( $point_id, 'cached_walk_time', true ) ?: 5 );
    $bike_time = (int) ( get_field( 'cached_bike_time', $point_id ) ?: get_post_meta( $point_id, 'cached_bike_time', true ) ?: 2 );
    $drive_time = (int) ( get_field( 'cached_drive_time', $point_id ) ?: get_post_meta( $point_id, 'cached_drive_time', true ) ?: 3 );

    return array(
        'id'        => $point_id,
        'name'      => get_the_title( $point_id ),
        'category'  => $category,
        'latitude'  => $lat ? (float) $lat : null,
        'longitude' => $lng ? (float) $lng : null,
        'times'     => array(
            'walk' => $walk_time,
            'bike' => $bike_time,
            'car'  => $drive_time,
        ),
    );
}

/**
 * Render Neighborhood Story layout for a project
 *
 * @param int $project_id Project post ID.
 */
function placy_render_neighborhood_story( $project_id = null ) {
    $project_id = $project_id ?? get_the_ID();
    $chapters = get_field( 'story_chapters', $project_id );

    // Output config for JavaScript
    $config = placy_get_neighborhood_story_config( $project_id );
    echo '<script type="application/json" data-ns-config>' . wp_json_encode( $config ) . '</script>';

    // Render sidebar
    include get_template_directory() . '/template-parts/neighborhood-story/sidebar.php';

    // Main content wrapper (offset by sidebar width)
    echo '<main class="ns-main-content" style="margin-left: var(--ns-sidebar-width, 260px);">';

    // Render each chapter section
    if ( $chapters ) {
        foreach ( $chapters as $chapter_index => $chapter ) {
            include get_template_directory() . '/template-parts/neighborhood-story/chapter-section.php';
        }
    }

    echo '</main>';

    // Render all chapter modals (hidden by default)
    if ( $chapters ) {
        foreach ( $chapters as $chapter_index => $chapter ) {
            include get_template_directory() . '/template-parts/neighborhood-story/chapter-modal.php';
        }
    }

    // Render global map modal
    include get_template_directory() . '/template-parts/neighborhood-story/global-map-modal.php';
}

/**
 * Helper function to check if Neighborhood Story is enabled for a project
 *
 * @param int $project_id Project post ID.
 * @return bool
 */
function placy_project_has_neighborhood_story( $project_id ) {
    $chapters = get_field( 'story_chapters', $project_id );
    return ! empty( $chapters );
}

/**
 * Add body class for Neighborhood Story pages
 */
function placy_neighborhood_story_body_class( $classes ) {
    if ( is_singular( 'project' ) && placy_project_has_neighborhood_story( get_the_ID() ) ) {
        $classes[] = 'has-neighborhood-story';
    }
    return $classes;
}
add_filter( 'body_class', 'placy_neighborhood_story_body_class' );
