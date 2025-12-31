<?php
/**
 * Chapter Wrapper ACF Block Registration
 *
 * Registrerer chapter-wrapper som en ACF Block der hvert blokk-instans
 * har sine egne felterverdier. Støtter flere chapter-wrappers per story.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register ACF Block for Chapter Wrapper
 */
add_action( 'acf/init', 'placy_register_chapter_wrapper_acf_block' );
function placy_register_chapter_wrapper_acf_block() {
    if ( ! function_exists( 'acf_register_block_type' ) ) {
        return;
    }

    // Register the ACF Block
    acf_register_block_type( array(
        'name'              => 'chapter-wrapper-acf',
        'title'             => __( 'Tema Story Kapittel (ACF)', 'placy' ),
        'description'       => __( 'Kapittel-seksjon med NarrativeSection design, POI-kort og mega-modal. Hvert kapittel har egne innstillinger. Støtter Gutenberg-blokker for ekstra innhold.', 'placy' ),
        'render_template'   => get_template_directory() . '/blocks/chapter-wrapper-acf/render.php',
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
 * Register ACF Field Group for Chapter Wrapper Block
 */
add_action( 'acf/init', 'placy_register_chapter_wrapper_block_fields' );
function placy_register_chapter_wrapper_block_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key' => 'group_chapter_wrapper_block',
        'title' => 'Kapittel-innstillinger',
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
                    'value' => 'acf/chapter-wrapper-acf',
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
        'description' => 'Felter for hvert Chapter Wrapper kapittel',
    ) );
}

// Helper functions are defined in acf-chapter-wrapper.php
