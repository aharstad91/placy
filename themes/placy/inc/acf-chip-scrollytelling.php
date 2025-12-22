<?php
/**
 * Chip Scrollytelling ACF Field Group Registration
 *
 * Programmatic field group registration for the Chip Scrollytelling block.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register ACF fields for Chip Scrollytelling block
 */
add_action( 'acf/init', 'placy_register_chip_scrollytelling_fields' );
function placy_register_chip_scrollytelling_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key' => 'group_chip_scrollytelling',
        'title' => 'Chip Scrollytelling',
        'fields' => array(
            // Tab: Content
            array(
                'key' => 'field_chip_scrolly_tab_content',
                'label' => 'Innhold',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_chip_scrolly_title',
                'label' => 'Tittel',
                'name' => 'title',
                'type' => 'text',
                'instructions' => 'Valgfri overskrift over seksjonen',
            ),
            array(
                'key' => 'field_chip_scrolly_subtitle',
                'label' => 'Undertittel',
                'name' => 'subtitle',
                'type' => 'text',
                'instructions' => 'Valgfri undertekst',
            ),
            
            // Steps repeater
            array(
                'key' => 'field_chip_scrolly_steps',
                'label' => 'Steg',
                'name' => 'steps',
                'type' => 'repeater',
                'instructions' => 'Legg til 3 steg (M5 / M4 Pro / M4 Max stil). Nøyaktig 3 steg anbefales for best resultat.',
                'required' => 1,
                'min' => 1,
                'max' => 5,
                'layout' => 'block',
                'button_label' => 'Legg til steg',
                'sub_fields' => array(
                    // Chip/tab settings
                    array(
                        'key' => 'field_chip_scrolly_step_chip_label',
                        'label' => 'Chip-label',
                        'name' => 'chip_label',
                        'type' => 'text',
                        'instructions' => 'Kort navn for tab (f.eks. "M5", "M4 Pro")',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => 'field_chip_scrolly_step_chip_icon',
                        'label' => 'Chip-ikon',
                        'name' => 'chip_icon',
                        'type' => 'select',
                        'choices' => array(
                            'none' => 'Ingen',
                            'apple' => 'Apple-logo',
                        ),
                        'default_value' => 'apple',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    
                    // Content
                    array(
                        'key' => 'field_chip_scrolly_step_headline',
                        'label' => 'Overskrift',
                        'name' => 'headline',
                        'type' => 'text',
                        'instructions' => 'Hovedtittel for dette steget',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_chip_scrolly_step_body',
                        'label' => 'Brødtekst',
                        'name' => 'body',
                        'type' => 'textarea',
                        'instructions' => 'Kort beskrivelse (maks ~240 tegn anbefalt for best layout)',
                        'rows' => 3,
                        'maxlength' => 300,
                    ),
                    
                    // KPIs
                    array(
                        'key' => 'field_chip_scrolly_step_kpi1_label',
                        'label' => 'KPI 1 - Label',
                        'name' => 'kpi_1_label',
                        'type' => 'text',
                        'instructions' => 'F.eks. "BATTERITID", "TILGJENGELIG"',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => 'field_chip_scrolly_step_kpi1_value',
                        'label' => 'KPI 1 - Verdi',
                        'name' => 'kpi_1_value',
                        'type' => 'text',
                        'instructions' => 'F.eks. "Opp til 24 timer", "14" og 16""',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => 'field_chip_scrolly_step_kpi2_label',
                        'label' => 'KPI 2 - Label',
                        'name' => 'kpi_2_label',
                        'type' => 'text',
                        'instructions' => 'F.eks. "YTELSE", "HASTIGHET"',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => 'field_chip_scrolly_step_kpi2_value',
                        'label' => 'KPI 2 - Verdi',
                        'name' => 'kpi_2_value',
                        'type' => 'text',
                        'instructions' => 'F.eks. "Opp til 6× raskere"',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    
                    // Visual
                    array(
                        'key' => 'field_chip_scrolly_step_visual_image',
                        'label' => 'Visuelt bilde',
                        'name' => 'visual_image',
                        'type' => 'image',
                        'instructions' => 'Hovedbilde for dette steget (venstre side). Anbefalt: 800x600px eller større, moderne format (WebP).',
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                        'mime_types' => 'jpg,jpeg,png,webp',
                    ),
                    
                    // Glow color
                    array(
                        'key' => 'field_chip_scrolly_step_glow_color',
                        'label' => 'Glow-farge',
                        'name' => 'glow_color',
                        'type' => 'color_picker',
                        'instructions' => 'Subtil bakgrunnsglow-farge for dette steget',
                        'default_value' => '#2b6cff',
                    ),
                ),
            ),
            
            // Tab: Layout
            array(
                'key' => 'field_chip_scrolly_tab_layout',
                'label' => 'Layout',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_chip_scrolly_steps_height_vh',
                'label' => 'Scroll-høyde (vh)',
                'name' => 'steps_height_vh',
                'type' => 'number',
                'instructions' => 'Total scrollbar høyde for seksjonen i viewport-enheter. Standard: 300 (= 3 × 100vh for 3 steg)',
                'default_value' => 300,
                'min' => 150,
                'max' => 600,
                'step' => 50,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chip_scrolly_scene_height_vh',
                'label' => 'Scene-høyde (vh)',
                'name' => 'scene_height_vh',
                'type' => 'number',
                'instructions' => 'Høyde på det sticky kortet i viewport-enheter. Standard: 70',
                'default_value' => 70,
                'min' => 40,
                'max' => 100,
                'step' => 5,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chip_scrolly_scene_max_width',
                'label' => 'Maks bredde (px)',
                'name' => 'scene_max_width',
                'type' => 'number',
                'instructions' => 'Maksimal bredde på kortet i piksler. Standard: 1320',
                'default_value' => 1320,
                'min' => 800,
                'max' => 1600,
                'step' => 40,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            
            // Tab: CTA
            array(
                'key' => 'field_chip_scrolly_tab_cta',
                'label' => 'CTA',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_chip_scrolly_cta_label',
                'label' => 'CTA-tekst',
                'name' => 'cta_label',
                'type' => 'text',
                'instructions' => 'Tekst på hovedknappen',
                'default_value' => 'Utforsk',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chip_scrolly_cta_url',
                'label' => 'CTA-lenke',
                'name' => 'cta_url',
                'type' => 'url',
                'instructions' => 'URL for CTA-knappen',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chip_scrolly_plus_button_enabled',
                'label' => 'Vis pluss-knapp',
                'name' => 'plus_button_enabled',
                'type' => 'true_false',
                'instructions' => 'Vis rund pluss-knapp ved siden av CTA',
                'default_value' => 1,
                'ui' => 1,
            ),
            
            // Tab: Behavior
            array(
                'key' => 'field_chip_scrolly_tab_behavior',
                'label' => 'Oppførsel',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_chip_scrolly_enable_click_scroll',
                'label' => 'Klikk-til-scroll',
                'name' => 'enable_click_to_scroll',
                'type' => 'true_false',
                'instructions' => 'Når aktivert, scroller siden til riktig posisjon ved klikk på chip',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chip_scrolly_enable_snap',
                'label' => 'Snap-scroll',
                'name' => 'enable_snap',
                'type' => 'true_false',
                'instructions' => 'Aktiverer CSS scroll-snap (kan være irriterende på noen enheter)',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_chip_scrolly_reduced_motion_mode',
                'label' => 'Reduced motion',
                'name' => 'reduced_motion_mode',
                'type' => 'select',
                'instructions' => 'Hvordan animasjoner håndteres for brukere med reduced motion-preferanse',
                'choices' => array(
                    'respect' => 'Respekter brukerpreferanse',
                    'force_off' => 'Alltid redusert bevegelse',
                    'force_on' => 'Alltid full animasjon',
                ),
                'default_value' => 'respect',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chip-scrollytelling',
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
        'description' => 'Felt for Chip Scrollytelling blokken - Apple-inspirert sticky scrollytelling',
        'show_in_rest' => 1,
    ) );
}
