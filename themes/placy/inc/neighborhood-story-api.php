<?php
/**
 * Neighborhood Story - REST API Endpoints
 * 
 * Provides REST endpoints for:
 * - Fetching theme-story content for modals
 * - Fetching all points for global map
 * - Updating user preferences (travel mode, time budget)
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API routes
 */
function placy_register_neighborhood_story_routes() {
    $namespace = 'placy/v1';

    // Get theme-story content
    register_rest_route( $namespace, '/theme-story/(?P<id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'placy_get_theme_story_content',
        'permission_callback' => '__return_true',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param );
                },
            ),
        ),
    ) );

    // Get all points for a project (global map)
    register_rest_route( $namespace, '/project/(?P<id>\d+)/points', array(
        'methods'             => 'GET',
        'callback'            => 'placy_get_project_global_points',
        'permission_callback' => '__return_true',
        'args'                => array(
            'id' => array(
                'required'          => true,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param );
                },
            ),
        ),
    ) );

    // Get chapter data with points
    register_rest_route( $namespace, '/project/(?P<id>\d+)/chapters', array(
        'methods'             => 'GET',
        'callback'            => 'placy_get_project_chapters',
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'placy_register_neighborhood_story_routes' );

/**
 * Get theme-story content for modal
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function placy_get_theme_story_content( $request ) {
    $theme_story_id = (int) $request->get_param( 'id' );
    
    $post = get_post( $theme_story_id );
    if ( ! $post || $post->post_type !== 'theme-story' ) {
        return new WP_Error( 
            'not_found', 
            'Theme story not found', 
            array( 'status' => 404 ) 
        );
    }

    // Get content
    $content = apply_filters( 'the_content', $post->post_content );
    
    // Get ACF fields
    $intro_title = get_field( 'intro_title', $theme_story_id );
    $intro_text = get_field( 'intro_text', $theme_story_id );
    $key_takeaways = get_field( 'key_takeaways', $theme_story_id );
    
    // Get all locations
    $all_locations = get_field( 'all_locations', $theme_story_id );
    $locations_data = array();
    
    if ( $all_locations ) {
        foreach ( $all_locations as $point ) {
            $point_id = is_object( $point ) ? $point->ID : $point;
            $locations_data[] = placy_format_point_data( $point_id );
        }
    }

    $response = array(
        'id'            => $theme_story_id,
        'title'         => get_the_title( $theme_story_id ),
        'intro_title'   => $intro_title,
        'intro_text'    => $intro_text,
        'content'       => $content,
        'key_takeaways' => $key_takeaways,
        'locations'     => $locations_data,
        'total_count'   => count( $locations_data ),
    );

    return rest_ensure_response( $response );
}

/**
 * Get all points for a project's global map
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function placy_get_project_global_points( $request ) {
    $project_id = (int) $request->get_param( 'id' );
    
    $post = get_post( $project_id );
    if ( ! $post || $post->post_type !== 'project' ) {
        return new WP_Error( 
            'not_found', 
            'Project not found', 
            array( 'status' => 404 ) 
        );
    }

    // Get all chapters
    $chapters = get_field( 'story_chapters', $project_id );
    $all_points = array();
    $point_ids_seen = array();

    if ( $chapters ) {
        foreach ( $chapters as $chapter_index => $chapter ) {
            $theme_story = $chapter['theme_story'];
            if ( ! $theme_story ) continue;
            
            $chapter_anchor = $chapter['anchor_id'] ?? 'chapter-' . $chapter_index;
            $chapter_title = $chapter['front_title'] ?: get_the_title( $theme_story );
            
            $locations = get_field( 'all_locations', $theme_story->ID );
            if ( ! $locations ) continue;
            
            foreach ( $locations as $point ) {
                $point_id = is_object( $point ) ? $point->ID : $point;
                
                // Skip duplicates
                if ( in_array( $point_id, $point_ids_seen, true ) ) continue;
                $point_ids_seen[] = $point_id;
                
                $point_data = placy_format_point_data( $point_id );
                $point_data['chapter_id'] = $chapter_anchor;
                $point_data['chapter_title'] = $chapter_title;
                
                $all_points[] = $point_data;
            }
        }
    }

    $response = array(
        'project_id'  => $project_id,
        'total_count' => count( $all_points ),
        'points'      => $all_points,
    );

    return rest_ensure_response( $response );
}

/**
 * Get all chapters for a project
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function placy_get_project_chapters( $request ) {
    $project_id = (int) $request->get_param( 'id' );
    
    $post = get_post( $project_id );
    if ( ! $post || $post->post_type !== 'project' ) {
        return new WP_Error( 
            'not_found', 
            'Project not found', 
            array( 'status' => 404 ) 
        );
    }

    $chapters = get_field( 'story_chapters', $project_id );
    $chapters_data = array();

    if ( $chapters ) {
        foreach ( $chapters as $index => $chapter ) {
            $theme_story = $chapter['theme_story'];
            if ( ! $theme_story ) continue;
            
            $theme_story_id = $theme_story->ID;
            $all_locations = get_field( 'all_locations', $theme_story_id );
            
            $chapters_data[] = array(
                'index'         => $index,
                'anchor_id'     => $chapter['anchor_id'] ?? 'chapter-' . $index,
                'title'         => $chapter['front_title'] ?: get_the_title( $theme_story ),
                'subtitle'      => $chapter['front_subtitle'] ?? '',
                'description'   => $chapter['front_text'] ?? '',
                'icon'          => $chapter['icon'] ?? 'walk',
                'theme_story'   => array(
                    'id'    => $theme_story_id,
                    'title' => get_the_title( $theme_story ),
                    'url'   => get_permalink( $theme_story ),
                ),
                'location_count' => is_array( $all_locations ) ? count( $all_locations ) : 0,
            );
        }
    }

    return rest_ensure_response( array(
        'project_id' => $project_id,
        'chapters'   => $chapters_data,
    ) );
}

/**
 * Format a point for API response
 *
 * @param int $point_id Point post ID.
 * @return array Formatted point data.
 */
function placy_format_point_data( $point_id ) {
    $point_type = get_post_type( $point_id );
    
    // Get category
    $category = '';
    if ( $point_type === 'placy_google_point' ) {
        $category = get_field( 'type_label', $point_id ) ?: get_field( 'type', $point_id );
    } else {
        $terms = get_the_terms( $point_id, 'poi_category' );
        $category = $terms ? $terms[0]->name : '';
    }
    
    // Get description
    $description = '';
    if ( $point_type === 'placy_google_point' ) {
        $description = get_field( 'description', $point_id );
    } else {
        $description = get_the_excerpt( $point_id );
    }
    
    // Get coordinates
    $lat = get_field( 'latitude', $point_id ) ?: get_post_meta( $point_id, 'latitude', true );
    $lng = get_field( 'longitude', $point_id ) ?: get_post_meta( $point_id, 'longitude', true );
    
    // Get cached travel times
    $walk_time = (int) ( get_field( 'cached_walk_time', $point_id ) ?: get_post_meta( $point_id, 'cached_walk_time', true ) ?: 0 );
    $bike_time = (int) ( get_field( 'cached_bike_time', $point_id ) ?: get_post_meta( $point_id, 'cached_bike_time', true ) ?: 0 );
    $drive_time = (int) ( get_field( 'cached_drive_time', $point_id ) ?: get_post_meta( $point_id, 'cached_drive_time', true ) ?: 0 );
    
    // Get rating
    $rating = get_field( 'rating', $point_id ) ?: get_post_meta( $point_id, 'rating', true );
    
    // Get featured image
    $image_url = get_the_post_thumbnail_url( $point_id, 'medium' );

    return array(
        'id'          => $point_id,
        'name'        => get_the_title( $point_id ),
        'category'    => $category,
        'description' => $description,
        'latitude'    => $lat ? (float) $lat : null,
        'longitude'   => $lng ? (float) $lng : null,
        'times'       => array(
            'walk' => $walk_time,
            'bike' => $bike_time,
            'car'  => $drive_time,
        ),
        'rating'      => $rating ? (float) $rating : null,
        'image'       => $image_url ?: null,
        'type'        => $point_type,
    );
}
