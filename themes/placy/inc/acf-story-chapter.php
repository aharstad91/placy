<?php
/**
 * Story Chapter ACF Block Registration
 *
 * Registrerer story-chapter som en ACF Block der hvert blokk-instans
 * har sine egne felterverdier. Støtter flere story-chapters per project.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register ACF Block for Story Chapter
 */
add_action( 'acf/init', 'placy_register_story_chapter_acf_block' );
function placy_register_story_chapter_acf_block() {
    if ( ! function_exists( 'acf_register_block_type' ) ) {
        return;
    }

    // Register the ACF Block
    acf_register_block_type( array(
        'name'              => 'story-chapter',
        'title'             => __( 'Story Chapter', 'placy' ),
        'description'       => __( 'Kapittel-seksjon med POI-kort og mega-modal. Hvert kapittel har egne innstillinger og henter innhold fra Theme Story.', 'placy' ),
        'render_template'   => get_template_directory() . '/blocks/story-chapter/render.php',
        'category'          => 'placy-blocks',
        'icon'              => 'location-alt',
        'keywords'          => array( 'chapter', 'kapittel', 'story', 'poi', 'location', 'mega-modal' ),
        'mode'              => 'preview', // Show preview in editor
        'align'             => 'full',
        'supports'          => array(
            'align'         => array( 'full', 'wide' ),
            'mode'          => true, // Allow switching between edit/preview
            'jsx'           => true, // Support inner blocks
            'anchor'        => true,
        ),
        'allowed_blocks'    => array(
            'core/heading',
            'core/paragraph',
            'core/image',
            'core/list',
            'core/gallery',
            'core/quote',
        ),
        'example'           => array(
            'attributes' => array(
                'mode' => 'preview',
                'data' => array(
                    'chapter_category_name' => 'DAILY LOGISTICS',
                    'chapter_front_title'   => 'Dining within steps',
                    'chapter_icon'          => 'food',
                ),
            ),
        ),
    ) );
}

/**
 * Register ACF Field Group for Story Chapter Block
 */
add_action( 'acf/init', 'placy_register_story_chapter_block_fields' );
function placy_register_story_chapter_block_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key' => 'group_story_chapter_block',
        'title' => 'Story Chapter Settings',
        'fields' => array(
            // ===========================
            // Tab: Front Section (Visible in chapter)
            // ===========================
            array(
                'key' => 'field_cwb_tab_front',
                'label' => 'Front Section',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_cwb_category_name',
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
                'key' => 'field_cwb_icon',
                'label' => 'Kategori-ikon',
                'name' => 'chapter_icon',
                'type' => 'select',
                'instructions' => 'Velg ikon for kategorien',
                'choices' => array(
                    'train'    => '🚆 Tog/Transport',
                    'bus'      => '🚌 Buss',
                    'bike'     => '🚲 Sykkel',
                    'car'      => '🚗 Bil',
                    'food'     => '🍽️ Mat/Restaurant',
                    'coffee'   => '☕ Kafé',
                    'shopping' => '🛍️ Shopping',
                    'hotel'    => '🏨 Hotell',
                    'meeting'  => '👥 Møte',
                    'nature'   => '🌳 Natur/Park',
                    'gym'      => '💪 Treningssenter',
                    'culture'  => '🎭 Kultur',
                    'bar'      => '🍺 Bar',
                    'health'   => '🏥 Helse',
                    'services' => '🔧 Tjenester',
                ),
                'default_value' => 'food',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_cwb_front_title',
                'label' => 'Tittel',
                'name' => 'chapter_front_title',
                'type' => 'text',
                'instructions' => 'Hovedtittel for seksjonen',
                'placeholder' => 'Effortless arrival via train, bike, or bus.',
            ),
            array(
                'key' => 'field_cwb_front_ingress',
                'label' => 'Ingress',
                'name' => 'chapter_front_ingress',
                'type' => 'textarea',
                'instructions' => 'Kort beskrivelse/ingress som introduserer seksjonen',
                'placeholder' => 'Whether you\'re commuting from the suburbs or the city center, the logistics just work.',
                'rows' => 3,
            ),
            array(
                'key' => 'field_cwb_highlighted_points',
                'label' => 'Fremhevede steder (POI-kort)',
                'name' => 'chapter_highlighted_points',
                'type' => 'relationship',
                'instructions' => 'Velg inntil 3 steder som vises som POI-kort i front-seksjonen.',
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
                'key' => 'field_cwb_tab_modal',
                'label' => 'Mega-modal',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_cwb_theme_story',
                'label' => 'Theme Story',
                'name' => 'chapter_theme_story',
                'type' => 'post_object',
                'instructions' => 'Velg en Theme Story post. Alt innhold i mega-modalen (Gutenberg-blokker, POI-er, kartinnstillinger) hentes fra denne posten.',
                'required' => 0,
                'post_type' => array(
                    'theme-story',
                ),
                'allow_null' => 1,
                'multiple' => 0,
                'return_format' => 'id',
            ),
            array(
                'key' => 'field_cwb_all_points',
                'label' => 'Alle steder (All Locations)',
                'name' => 'chapter_all_points',
                'type' => 'relationship',
                'instructions' => 'Fallback: Velg steder hvis ingen Theme Story er valgt, eller for å overstyre.',
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
                'key' => 'field_cwb_tab_settings',
                'label' => 'Innstillinger',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_cwb_chapter_id',
                'label' => 'Kapittel-ID',
                'name' => 'chapter_id',
                'type' => 'text',
                'instructions' => 'Unik ID for navigasjon (f.eks. "daily-logistics"). Brukes i URL-ankre.',
                'placeholder' => 'daily-logistics',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_cwb_nav_label',
                'label' => 'Navigasjonslabel',
                'name' => 'chapter_nav_label',
                'type' => 'text',
                'instructions' => 'Kort label for kapittelnavigasjon (vises i sidebar)',
                'placeholder' => 'Daily Logistics',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_cwb_show_cta_button',
                'label' => 'Vis "Se alle steder" knapp',
                'name' => 'chapter_show_cta_button',
                'type' => 'true_false',
                'instructions' => 'Vis knapp for å åpne mega-modal med alle steder',
                'default_value' => 1,
                'ui' => 1,
            ),
            array(
                'key' => 'field_cwb_cta_button_text',
                'label' => 'CTA-knapp tekst',
                'name' => 'chapter_cta_button_text',
                'type' => 'text',
                'instructions' => 'Tekst på "Se alle steder" knappen',
                'default_value' => 'See all places in this category',
                'placeholder' => 'See all places in this category',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_cwb_show_cta_button',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_cwb_default_travel_mode',
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
                'key' => 'field_cwb_default_time_budget',
                'label' => 'Standard tidsbudsjett',
                'name' => 'chapter_default_time_budget',
                'type' => 'select',
                'instructions' => 'Standard tidsfilter for mega-modal',
                'choices' => array(
                    '5'  => '≤ 5 min',
                    '10' => '≤ 10 min',
                    '15' => '≤ 15 min',
                ),
                'default_value' => '10',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_cwb_show_map',
                'label' => 'Vis kart',
                'name' => 'chapter_show_map',
                'type' => 'true_false',
                'instructions' => 'Vis kartvisning for dette kapittelet',
                'default_value' => 1,
                'ui' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/story-chapter',
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
        'description' => 'Felter for hvert Story Chapter kapittel',
    ) );
}

/**
 * Helper function: Get POI data from point IDs
 * 
 * @param array $point_ids Array of post IDs (placy_native_point or placy_google_point)
 * @return array Array of POI data
 */
function placy_get_chapter_points( $point_ids ) {
    if ( empty( $point_ids ) || ! is_array( $point_ids ) ) {
        return array();
    }
    
    $points = array();
    
    foreach ( $point_ids as $point_id ) {
        // Handle both object and ID formats from ACF
        $post_id = is_object( $point_id ) ? $point_id->ID : intval( $point_id );
        $post = get_post( $post_id );
        
        if ( ! $post ) {
            continue;
        }
        
        $post_type = get_post_type( $post_id );
        
        // Initialize data
        $lat = null;
        $lng = null;
        $image = null;
        $category = '';
        $description = '';
        $rating = null;
        $google_place_id = '';
        
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
            
            $google_place_id = get_post_meta( $post_id, 'google_place_id', true );
            
            // Get coordinates from cache geometry or location
            if ( isset( $cache_data['geometry']['lat'], $cache_data['geometry']['lng'] ) ) {
                $lat = floatval( $cache_data['geometry']['lat'] );
                $lng = floatval( $cache_data['geometry']['lng'] );
            } elseif ( isset( $cache_data['location']['lat'], $cache_data['location']['lng'] ) ) {
                $lat = floatval( $cache_data['location']['lat'] );
                $lng = floatval( $cache_data['location']['lng'] );
            }
            
            // Get rating from cache
            $rating = isset( $cache_data['rating'] ) ? floatval( $cache_data['rating'] ) : null;
            
            // Get category/type
            $category = get_post_meta( $post_id, 'primary_type', true );
            if ( empty( $category ) && isset( $cache_data['types'][0] ) ) {
                $category = $cache_data['types'][0];
            }
            
            // Get description - prefer editorial_text
            $description = get_post_meta( $post_id, 'editorial_text', true );
            if ( empty( $description ) ) {
                $description = get_post_meta( $post_id, 'placy_description', true );
            }
            
            // Get photo URL from cache photos array
            if ( isset( $cache_data['photos'][0]['name'] ) && defined( 'GOOGLE_PLACES_API_KEY' ) ) {
                $photo_reference = $cache_data['photos'][0]['name'];
                $image = 'https://places.googleapis.com/v1/' . $photo_reference . '/media?maxWidthPx=400&key=' . GOOGLE_PLACES_API_KEY;
            }
            
        } elseif ( $post_type === 'placy_native_point' ) {
            // Native Point - get data from ACF fields or post meta
            // Try coordinates_latitude/longitude first, then latitude/longitude
            $lat = floatval( 
                get_field( 'coordinates_latitude', $post_id ) ?: 
                get_post_meta( $post_id, 'coordinates_latitude', true ) ?: 
                get_field( 'latitude', $post_id ) ?: 
                get_post_meta( $post_id, 'latitude', true ) 
            );
            $lng = floatval( 
                get_field( 'coordinates_longitude', $post_id ) ?: 
                get_post_meta( $post_id, 'coordinates_longitude', true ) ?: 
                get_field( 'longitude', $post_id ) ?: 
                get_post_meta( $post_id, 'longitude', true ) 
            );
            $rating = floatval( get_field( 'rating', $post_id ) ?: 4.5 );
            $category = get_field( 'category', $post_id ) ?: get_post_meta( $post_id, 'category', true );
            $description = get_field( 'description', $post_id ) ?: $post->post_excerpt ?: wp_trim_words( $post->post_content, 20 );
            
            // Get featured image
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            if ( $thumbnail_id ) {
                $image = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
            }
        }
        
        // Skip if no valid coordinates
        if ( ! $lat || ! $lng ) {
            continue;
        }
        
        $points[] = array(
            'id' => $post_id,
            'title' => $post->post_title,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'image' => $image,
            'category' => $category,
            'description' => $description,
            'rating' => $rating,
            'google_place_id' => $google_place_id,
            'post_type' => $post_type,
        );
    }
    
    return $points;
}

/**
 * Helper function: Get category icon SVG
 * 
 * @param string $icon Icon key
 * @return string SVG markup
 */
function placy_get_chapter_icon_svg( $icon ) {
    $icons = array(
        'food' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        'cafe' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h12a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9h2a2 2 0 012 2v1a2 2 0 01-2 2h-2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12"/></svg>',
        'transport' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M8 7H6a2 2 0 00-2 2v6a2 2 0 002 2h2m8-10v10m0-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2m-6 4l-2 2m10-2l2 2"/></svg>',
        'bus' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 11h8m-8 4h2m4 0h2M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>',
        'bike' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="17" r="3" stroke-width="2"/><circle cx="19" cy="17" r="3" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17l-4-8 8-2-4 10z"/></svg>',
        'car' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 6h5l3 5v6h-2m-8 0H6m0 0l-2-5h9l2 5"/></svg>',
        'shopping' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
        'hotel' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        'meeting' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        'nature' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
        'gym' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h4l3-9 4 18 3-9h4"/></svg>',
        'culture' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>',
        'bar' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
        'health' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
        'services' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    );
    
    return isset( $icons[ $icon ] ) ? $icons[ $icon ] : $icons['food'];
}
