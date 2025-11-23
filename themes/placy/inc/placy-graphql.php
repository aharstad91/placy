<?php
/**
 * Placy GraphQL Integration
 * Unified PlacyPoint type and resolvers for both Native and Google Points
 *
 * @package Placy
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register unified PlacyPoint type in GraphQL
 */
add_action( 'graphql_register_types', 'placy_register_graphql_types' );
function placy_register_graphql_types() {
    
    // Register Location type
    register_graphql_object_type( 'PlacyLocation', array(
        'description' => 'Point location data',
        'fields' => array(
            'latitude' => array(
                'type' => 'Float',
                'description' => 'Latitude coordinate',
            ),
            'longitude' => array(
                'type' => 'Float',
                'description' => 'Longitude coordinate',
            ),
            'address' => array(
                'type' => 'String',
                'description' => 'Formatted address',
            ),
        ),
    ) );
    
    // Register Image type
    register_graphql_object_type( 'PlacyImage', array(
        'description' => 'Point image data',
        'fields' => array(
            'url' => array(
                'type' => 'String',
                'description' => 'Image URL',
            ),
            'alt' => array(
                'type' => 'String',
                'description' => 'Image alt text',
            ),
            'width' => array(
                'type' => 'Int',
                'description' => 'Image width',
            ),
            'height' => array(
                'type' => 'Int',
                'description' => 'Image height',
            ),
        ),
    ) );
    
    // Register Attribution type
    register_graphql_object_type( 'PlacyAttribution', array(
        'description' => 'Data attribution information',
        'fields' => array(
            'source' => array(
                'type' => 'String',
                'description' => 'Data source (e.g., "Google", "Native")',
            ),
            'logoUrl' => array(
                'type' => 'String',
                'description' => 'Attribution logo URL',
            ),
            'requiresDisplay' => array(
                'type' => 'Boolean',
                'description' => 'Whether attribution must be displayed',
            ),
        ),
    ) );
    
    // Register OpeningHours type
    register_graphql_object_type( 'PlacyOpeningHours', array(
        'description' => 'Opening hours information',
        'fields' => array(
            'openNow' => array(
                'type' => 'Boolean',
                'description' => 'Whether the place is currently open',
            ),
            'weekdayText' => array(
                'type' => array( 'list_of' => 'String' ),
                'description' => 'Formatted opening hours for each day',
            ),
        ),
    ) );
    
    // Register unified PlacyPoint interface
    register_graphql_interface_type( 'PlacyPoint', array(
        'description' => 'Unified point of interest (Native or Google)',
        'fields' => array(
            'id' => array(
                'type' => 'ID',
                'description' => 'Unique point ID',
            ),
            'type' => array(
                'type' => 'String',
                'description' => 'Point type: "native" or "google"',
            ),
            'name' => array(
                'type' => 'String',
                'description' => 'Point name',
            ),
            'location' => array(
                'type' => 'PlacyLocation',
                'description' => 'Location data',
            ),
            'description' => array(
                'type' => 'String',
                'description' => 'Point description',
            ),
            'images' => array(
                'type' => array( 'list_of' => 'PlacyImage' ),
                'description' => 'Point images',
            ),
            'rating' => array(
                'type' => 'Float',
                'description' => 'Rating (1-5)',
            ),
            'reviewCount' => array(
                'type' => 'Int',
                'description' => 'Number of reviews',
            ),
            'openingHours' => array(
                'type' => 'PlacyOpeningHours',
                'description' => 'Opening hours (Google points only)',
            ),
            'website' => array(
                'type' => 'String',
                'description' => 'Website URL',
            ),
            'phone' => array(
                'type' => 'String',
                'description' => 'Phone number',
            ),
            'categories' => array(
                'type' => array( 'list_of' => 'String' ),
                'description' => 'Category names',
            ),
            'tags' => array(
                'type' => array( 'list_of' => 'String' ),
                'description' => 'Tag names',
            ),
            'lifestyleSegments' => array(
                'type' => array( 'list_of' => 'String' ),
                'description' => 'Lifestyle segment names',
            ),
            'featured' => array(
                'type' => 'Boolean',
                'description' => 'Is featured point',
            ),
            'displayPriority' => array(
                'type' => 'Int',
                'description' => 'Display priority (1-10)',
            ),
            'isSponsored' => array(
                'type' => 'Boolean',
                'description' => 'Is sponsored content',
            ),
            'attribution' => array(
                'type' => 'PlacyAttribution',
                'description' => 'Data attribution',
            ),
        ),
    ) );
    
    // Add PlacyPoints field to Story post type
    register_graphql_field( 'Story', 'placyPoints', array(
        'type' => array( 'list_of' => 'PlacyPoint' ),
        'description' => 'All points associated with this story',
        'resolve' => function( $post ) {
            return placy_get_story_points( $post->ID );
        },
    ) );
    
    // Add PlacyPoints field to ThemeStory post type
    register_graphql_field( 'ThemeStory', 'placyPoints', array(
        'type' => array( 'list_of' => 'PlacyPoint' ),
        'description' => 'All points associated with this theme story',
        'resolve' => function( $post ) {
            return placy_get_story_points( $post->ID );
        },
    ) );
}

/**
 * Get all points for a story (unified)
 *
 * @param int $story_id Story post ID
 * @return array Unified point data
 */
function placy_get_story_points( $story_id ) {
    $points = array();
    
    // Get points from ACF relationship field (if exists)
    $related_points = get_field( 'points', $story_id );
    
    if ( ! $related_points ) {
        return $points;
    }
    
    foreach ( $related_points as $point_post ) {
        $point_type = get_post_type( $point_post->ID );
        
        if ( $point_type === 'placy_native_point' ) {
            $points[] = placy_format_native_point( $point_post->ID );
        } elseif ( $point_type === 'placy_google_point' ) {
            $points[] = placy_format_google_point( $point_post->ID );
        }
    }
    
    return $points;
}

/**
 * Format Native Point for GraphQL
 *
 * @param int $post_id Native Point post ID
 * @return array Formatted point data
 */
function placy_format_native_point( $post_id ) {
    $coordinates = get_field( 'coordinates', $post_id );
    $images_raw = get_field( 'images', $post_id );
    $categories = wp_get_post_terms( $post_id, 'placy_categories', array( 'fields' => 'names' ) );
    $tags = wp_get_post_terms( $post_id, 'placy_tags', array( 'fields' => 'names' ) );
    
    // Format images
    $images = array();
    if ( is_array( $images_raw ) ) {
        foreach ( $images_raw as $image ) {
            $images[] = array(
                'url' => $image['url'],
                'alt' => $image['alt'],
                'width' => $image['width'],
                'height' => $image['height'],
            );
        }
    }
    
    $lifestyle_segments = wp_get_post_terms( $post_id, 'lifestyle_segments', array( 'fields' => 'names' ) );
    
    return array(
        'id' => $post_id,
        'type' => 'native',
        'name' => get_field( 'name', $post_id ) ?: get_the_title( $post_id ),
        'location' => array(
            'latitude' => isset( $coordinates['latitude'] ) ? floatval( $coordinates['latitude'] ) : null,
            'longitude' => isset( $coordinates['longitude'] ) ? floatval( $coordinates['longitude'] ) : null,
            'address' => get_field( 'address', $post_id ),
        ),
        'description' => get_field( 'description', $post_id ),
        'images' => $images,
        'rating' => null,
        'reviewCount' => null,
        'openingHours' => null,
        'website' => get_field( 'website', $post_id ),
        'phone' => get_field( 'phone', $post_id ),
        'categories' => is_array( $categories ) ? $categories : array(),
        'tags' => is_array( $tags ) ? $tags : array(),
        'lifestyleSegments' => is_array( $lifestyle_segments ) ? $lifestyle_segments : array(),
        'featured' => (bool) get_field( 'featured', $post_id ),
        'displayPriority' => (int) get_field( 'display_priority', $post_id ) ?: 5,
        'isSponsored' => (bool) get_field( 'is_sponsored', $post_id ),
        'attribution' => null,
    );
}

/**
 * Format Google Point for GraphQL
 *
 * @param int $post_id Google Point post ID
 * @return array Formatted point data
 */
function placy_format_google_point( $post_id ) {
    // Parse cached data
    $nearby_cache = get_field( 'nearby_search_cache', $post_id );
    $details_cache = get_field( 'place_details_cache', $post_id );
    
    $nearby_data = json_decode( $nearby_cache, true ) ?: array();
    $details_data = json_decode( $details_cache, true ) ?: array();
    
    $categories = wp_get_post_terms( $post_id, 'placy_categories', array( 'fields' => 'names' ) );
    $tags = wp_get_post_terms( $post_id, 'placy_tags', array( 'fields' => 'names' ) );
    $lifestyle_segments = wp_get_post_terms( $post_id, 'lifestyle_segments', array( 'fields' => 'names' ) );
    
    // Format images from Google photos
    $images = array();
    if ( isset( $nearby_data['photos'] ) && is_array( $nearby_data['photos'] ) ) {
        foreach ( array_slice( $nearby_data['photos'], 0, 5 ) as $photo ) {
            if ( isset( $photo['photo_reference'] ) ) {
                $api_key = defined( 'GOOGLE_PLACES_API_KEY' ) ? GOOGLE_PLACES_API_KEY : '';
                $images[] = array(
                    'url' => sprintf(
                        'https://maps.googleapis.com/maps/api/place/photo?maxwidth=800&photo_reference=%s&key=%s',
                        $photo['photo_reference'],
                        $api_key
                    ),
                    'alt' => $nearby_data['name'] ?? '',
                    'width' => 800,
                    'height' => 600,
                );
            }
        }
    }
    
    // Format opening hours
    $opening_hours = null;
    if ( isset( $nearby_data['opening_hours'] ) ) {
        $opening_hours = array(
            'openNow' => $nearby_data['opening_hours']['open_now'] ?? null,
            'weekdayText' => $nearby_data['opening_hours']['weekday_text'] ?? array(),
        );
    }
    
    return array(
        'id' => $post_id,
        'type' => 'google',
        'name' => $nearby_data['name'] ?? get_the_title( $post_id ),
        'location' => array(
            'latitude' => isset( $nearby_data['geometry']['lat'] ) ? floatval( $nearby_data['geometry']['lat'] ) : null,
            'longitude' => isset( $nearby_data['geometry']['lng'] ) ? floatval( $nearby_data['geometry']['lng'] ) : null,
            'address' => $nearby_data['formatted_address'] ?? $nearby_data['vicinity'] ?? '',
        ),
        'description' => get_field( 'editorial_text', $post_id ),
        'images' => $images,
        'rating' => isset( $nearby_data['rating'] ) ? floatval( $nearby_data['rating'] ) : null,
        'reviewCount' => isset( $nearby_data['user_ratings_total'] ) ? intval( $nearby_data['user_ratings_total'] ) : null,
        'openingHours' => $opening_hours,
        'website' => $details_data['website'] ?? '',
        'phone' => $details_data['phone'] ?? '',
        'categories' => is_array( $categories ) ? $categories : array(),
        'tags' => is_array( $tags ) ? $tags : array(),
        'lifestyleSegments' => is_array( $lifestyle_segments ) ? $lifestyle_segments : array(),
        'featured' => (bool) get_field( 'featured', $post_id ),
        'displayPriority' => (int) get_field( 'display_priority', $post_id ) ?: 5,
        'isSponsored' => (bool) get_field( 'is_sponsored', $post_id ),
        'attribution' => array(
            'source' => 'Google',
            'logoUrl' => get_template_directory_uri() . '/assets/google-logo.svg',
            'requiresDisplay' => true,
        ),
    );
}
