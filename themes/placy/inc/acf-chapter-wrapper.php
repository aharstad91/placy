<?php
/**
 * Chapter Wrapper ACF Field Group Registration
 *
 * Programmatic field group registration for the Chapter Wrapper block.
 * Supports both placy_native_point and placy_google_point CPTs.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register ACF fields for Chapter Wrapper block
 * 
 * DEPRECATED: This post-level field group has been replaced by the ACF Block version
 * in acf-chapter-wrapper-block.php which stores fields per block instance.
 * Keeping the helper functions below active.
 */
// add_action( 'acf/init', 'placy_register_chapter_wrapper_fields' );
function placy_register_chapter_wrapper_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key' => 'group_chapter_wrapper',
        'title' => 'Chapter Wrapper Settings',
        'fields' => array(
            // ===========================
            // Tab: Front Section (Visible in chapter)
            // ===========================
            array(
                'key' => 'field_chapter_tab_front',
                'label' => 'Front Section',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_chapter_category_name',
                'label' => 'Kategorinavn',
                'name' => 'chapter_category_name',
                'type' => 'text',
                'instructions' => 'Kort kategorinavn som vises over tittelen (f.eks. "DAILY LOGISTICS", "LUNCH & ERRANDS")',
                'placeholder' => 'DAILY LOGISTICS',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chapter_icon',
                'label' => 'Kategori-ikon',
                'name' => 'chapter_icon',
                'type' => 'select',
                'instructions' => 'Velg ikon for kategorien',
                'choices' => array(
                    'train' => '🚆 Tog/Transport',
                    'bus' => '🚌 Buss',
                    'bike' => '🚲 Sykkel',
                    'car' => '🚗 Bil',
                    'food' => '🍽️ Mat/Restaurant',
                    'coffee' => '☕ Kafé',
                    'shopping' => '🛍️ Shopping',
                    'hotel' => '🏨 Hotell',
                    'meeting' => '👥 Møte',
                    'nature' => '🌳 Natur/Park',
                    'gym' => '💪 Treningssenter',
                    'culture' => '🎭 Kultur',
                    'bar' => '🍺 Bar',
                    'health' => '🏥 Helse',
                    'services' => '🔧 Tjenester',
                ),
                'default_value' => 'food',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chapter_front_title',
                'label' => 'Tittel',
                'name' => 'chapter_front_title',
                'type' => 'text',
                'instructions' => 'Hovedtittel for seksjonen',
                'placeholder' => 'Effortless arrival via train, bike, or bus.',
            ),
            array(
                'key' => 'field_chapter_front_ingress',
                'label' => 'Ingress',
                'name' => 'chapter_front_ingress',
                'type' => 'textarea',
                'instructions' => 'Kort beskrivelse/ingress som introduserer seksjonen',
                'placeholder' => 'Whether you\'re commuting from the suburbs or the city center, the logistics just work.',
                'rows' => 3,
            ),
            array(
                'key' => 'field_chapter_highlighted_points',
                'label' => 'Fremhevede steder (POI-kort)',
                'name' => 'chapter_highlighted_points',
                'type' => 'relationship',
                'instructions' => 'Velg 3 steder som vises som POI-kort i front-seksjonen. Kan være en blanding av Native Points og Google Points.',
                'post_type' => array(
                    'placy_native_point',
                    'placy_google_point',
                ),
                'filters' => array(
                    'search',
                    'post_type',
                ),
                'elements' => array(
                    'featured_image',
                ),
                'min' => 0,
                'max' => 3,
                'return_format' => 'id',
            ),

            // ===========================
            // Tab: Modal/Mega-drawer Section
            // ===========================
            array(
                'key' => 'field_chapter_tab_modal',
                'label' => 'Mega-modal',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_chapter_key_takeaways',
                'label' => 'Key Takeaways',
                'name' => 'chapter_key_takeaways',
                'type' => 'wysiwyg',
                'instructions' => 'Nøkkelpunkter som vises i mega-modalen (bruk liste-format). Vises som en utvidbar accordion.',
                'default_value' => '<ul>
<li>High density of options within 5 minutes</li>
<li>Mix of premium and casual locations</li>
<li>Accessible by all major transit lines</li>
</ul>',
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ),
            array(
                'key' => 'field_chapter_all_points',
                'label' => 'Alle steder (All Locations)',
                'name' => 'chapter_all_points',
                'type' => 'relationship',
                'instructions' => 'Velg alle steder som skal vises i mega-modalen. Kan være en blanding av Native Points og Google Points. Disse vises i en scrollbar liste med gangtider.',
                'post_type' => array(
                    'placy_native_point',
                    'placy_google_point',
                ),
                'filters' => array(
                    'search',
                    'post_type',
                ),
                'elements' => array(
                    'featured_image',
                ),
                'min' => 0,
                'max' => 50,
                'return_format' => 'id',
            ),

            // ===========================
            // Tab: Display Settings
            // ===========================
            array(
                'key' => 'field_chapter_tab_display',
                'label' => 'Visningsinnstillinger',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_chapter_show_mega_modal',
                'label' => 'Vis "Se alle steder" knapp',
                'name' => 'chapter_show_mega_modal',
                'type' => 'true_false',
                'instructions' => 'Vis knapp for å åpne mega-modal med alle steder',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chapter_cta_text',
                'label' => 'CTA-knapp tekst',
                'name' => 'chapter_cta_text',
                'type' => 'text',
                'instructions' => 'Tekst på "Se alle steder" knappen',
                'default_value' => 'See all places in this category',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_chapter_show_mega_modal',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chapter_default_travel_mode',
                'label' => 'Standard reisemodus',
                'name' => 'chapter_default_travel_mode',
                'type' => 'select',
                'instructions' => 'Velg standard reisemodus for gangtider',
                'choices' => array(
                    'walking' => 'Til fots',
                    'cycling' => 'Sykkel',
                    'driving' => 'Bil',
                ),
                'default_value' => 'walking',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chapter_default_time_budget',
                'label' => 'Standard tidsbudsjett',
                'name' => 'chapter_default_time_budget',
                'type' => 'select',
                'instructions' => 'Standard tidsfilter for mega-modal',
                'choices' => array(
                    '5' => '≤ 5 min',
                    '10' => '≤ 10 min',
                    '15' => '≤ 15 min',
                ),
                'default_value' => '10',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'project',
                ),
            ),
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'theme-story',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => 'Felter for Chapter Wrapper blokk med mega-modal funksjonalitet',
    ) );
}

/**
 * Helper function to get point data (works for both native and google points)
 * 
 * @param int $post_id The post ID
 * @return array Point data array
 */
function placy_get_point_data( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        return null;
    }

    $post_type = get_post_type( $post_id );

    // Get display name: ACF 'name' field first, fallback to post title
    $acf_name = get_field( 'name', $post_id );
    $display_name = ! empty( $acf_name ) ? $acf_name : get_the_title( $post_id );

    $data = array(
        'id' => $post_id,
        'title' => get_the_title( $post_id ),  // Keep original title for reference
        'name' => $display_name,                // Short display name (ACF name or fallback to title)
        'type' => $post_type,
        'permalink' => get_permalink( $post_id ),
    );

    if ( $post_type === 'placy_google_point' ) {
        // Google Point - get data from nearby_search_cache
        $cache = get_post_meta( $post_id, 'nearby_search_cache', true );
        $cache_data = null;
        
        if ( is_array( $cache ) ) {
            $cache_data = $cache;
        } elseif ( ! empty( $cache ) ) {
            // Clean up and decode JSON
            $cache = html_entity_decode( $cache, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $cache = stripslashes( $cache );
            $cache = rtrim( $cache, "%\x00\x1F" );
            $cache_data = json_decode( $cache, true );
        }
        
        $data['place_id'] = get_post_meta( $post_id, 'google_place_id', true );
        
        // Get coordinates from cache geometry or location
        if ( isset( $cache_data['geometry']['lat'], $cache_data['geometry']['lng'] ) ) {
            $data['lat'] = floatval( $cache_data['geometry']['lat'] );
            $data['lng'] = floatval( $cache_data['geometry']['lng'] );
        } elseif ( isset( $cache_data['location']['lat'], $cache_data['location']['lng'] ) ) {
            $data['lat'] = floatval( $cache_data['location']['lat'] );
            $data['lng'] = floatval( $cache_data['location']['lng'] );
        } else {
            $data['lat'] = 0;
            $data['lng'] = 0;
        }
        
        // Get rating and review count from cache
        $data['rating'] = isset( $cache_data['rating'] ) ? floatval( $cache_data['rating'] ) : 0;
        $data['review_count'] = isset( $cache_data['user_ratings_total'] ) ? intval( $cache_data['user_ratings_total'] ) : 0;
        
        // Get category/type
        $data['category'] = get_post_meta( $post_id, 'primary_type', true );
        if ( empty( $data['category'] ) && isset( $cache_data['types'][0] ) ) {
            $data['category'] = $cache_data['types'][0];
        }
        
        // Get description - prefer editorial_text, then placy_description
        $data['description'] = get_post_meta( $post_id, 'editorial_text', true );
        if ( empty( $data['description'] ) ) {
            $data['description'] = get_post_meta( $post_id, 'placy_description', true );
        }
        
        // Get address from cache
        $data['address'] = isset( $cache_data['formatted_address'] ) ? $cache_data['formatted_address'] : '';
        
        // Get photo URL from cache photos array
        if ( isset( $cache_data['photos'][0]['name'] ) ) {
            $photo_reference = $cache_data['photos'][0]['name'];
            // Use caching proxy to reduce API calls (30-day cache)
            $data['image'] = rest_url( 'placy/v1/photo/proxy/' . urlencode( $photo_reference ) ) . '?maxwidth=400';
        }
    } else {
        // Native Point - get data from ACF
        // Try coordinates_latitude/longitude first, then latitude/longitude
        $data['lat'] = floatval( 
            get_field( 'coordinates_latitude', $post_id ) ?: 
            get_post_meta( $post_id, 'coordinates_latitude', true ) ?: 
            get_field( 'latitude', $post_id ) ?: 
            get_post_meta( $post_id, 'latitude', true ) 
        );
        $data['lng'] = floatval( 
            get_field( 'coordinates_longitude', $post_id ) ?: 
            get_post_meta( $post_id, 'coordinates_longitude', true ) ?: 
            get_field( 'longitude', $post_id ) ?: 
            get_post_meta( $post_id, 'longitude', true ) 
        );
        $data['rating'] = floatval( get_field( 'rating', $post_id ) ?: 4.5 );
        $data['category'] = get_field( 'category', $post_id ) ?: get_post_meta( $post_id, 'category', true );
        $data['description'] = get_field( 'description', $post_id ) ?: $post->post_excerpt ?: wp_trim_words( $post->post_content, 20 );
        
        // Get featured image
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            $data['image'] = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
        }
    }

    // Ensure we have coordinates
    if ( empty( $data['lat'] ) || empty( $data['lng'] ) ) {
        $data['lat'] = 0;
        $data['lng'] = 0;
    }

    return $data;
}

/**
 * Get all points for a chapter with their data
 * 
 * @param array $point_ids Array of post IDs
 * @return array Array of point data
 */
function placy_get_chapter_points( $point_ids ) {
    if ( empty( $point_ids ) || ! is_array( $point_ids ) ) {
        return array();
    }

    $points = array();
    foreach ( $point_ids as $post_id ) {
        $point_data = placy_get_point_data( $post_id );
        if ( $point_data ) {
            $points[] = $point_data;
        }
    }

    return $points;
}

/**
 * Get icon class name from icon key
 * 
 * @param string $icon_key The icon key from ACF select
 * @return string Icon class or SVG path
 */
function placy_get_chapter_icon_svg( $icon_key ) {
    $icons = array(
        'train' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 15.5C4 17.985 6.015 20 8.5 20L7 22m9-2l-1.5-2m.5 0h-6m3-16v0a3 3 0 013 3v9a3 3 0 01-3 3v0m-3 0h6m-6 0v0a3 3 0 01-3-3V7a3 3 0 013-3v0m3 0h-6m0 0V2m6 2V2m-5 6.5h4m-4 4h4"/></svg>',
        'bus' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 18v2a1 1 0 001 1h2a1 1 0 001-1v-2m8 0v2a1 1 0 001 1h2a1 1 0 001-1v-2M5 18H4a2 2 0 01-2-2V8a4 4 0 014-4h12a4 4 0 014 4v8a2 2 0 01-2 2h-1M5 18h14M6 12h.01M18 12h.01M7 8h10"/></svg>',
        'bike' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M15 6a1 1 0 100-2 1 1 0 000 2zm-3 11.5V14l-3-3 4-4 2 2h3"/></svg>',
        'car' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m-4 0H3v-4l2-4h10l2 4v4h-2m-8 0h8m0 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>',
        'food' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zm4-5v3m4-3v3m4-3v3"/></svg>',
        'coffee' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 8h1a4 4 0 010 8h-1M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8zm2-6l1 2m4-2l1 2m4-2l1 2"/></svg>',
        'shopping' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
        'hotel' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-16 0H3m4-10h.01M12 11h.01M17 11h.01M8 15h.01M12 15h.01M17 15h.01"/></svg>',
        'meeting' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m22 0v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
        'nature' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22V8m0 0l-3 3m3-3l3 3M5 3v4a2 2 0 002 2h10a2 2 0 002-2V3M7 14l5-5 5 5M7 18l5-5 5 5"/></svg>',
        'gym' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6.5 6.5L17.5 17.5M6.5 17.5L17.5 6.5M2 12h3m14 0h3M12 2v3m0 14v3"/></svg>',
        'culture' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 19l-4-4 4-4m8 8l4-4-4-4M14.5 4l-5 16"/></svg>',
        'bar' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 22h8m-4-11v11m0-11L6 3h12l-6 8z"/></svg>',
        'health' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
        'services' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
    );

    return isset( $icons[ $icon_key ] ) ? $icons[ $icon_key ] : $icons['food'];
}
