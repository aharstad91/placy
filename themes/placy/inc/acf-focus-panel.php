<?php
/**
 * Focus Panel ACF Field Group Registration
 *
 * Programmatic field group registration for the Focus Panel block.
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register ACF fields for Focus Panel block
 */
add_action( 'acf/init', 'placy_register_focus_panel_fields' );
function placy_register_focus_panel_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key' => 'group_focus_panel',
        'title' => 'Focus Panel',
        'fields' => array(
            // ===========================
            // Tab: Trigger (knapp/link)
            // ===========================
            array(
                'key' => 'field_focuspanel_tab_trigger',
                'label' => 'Trigger (knapp)',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_focuspanel_trigger_type',
                'label' => 'Trigger-type',
                'name' => 'trigger_type',
                'type' => 'select',
                'instructions' => 'Velg hvordan triggeren skal se ut',
                'choices' => array(
                    'button' => 'Knapp (standard)',
                    'link' => 'Lenke/tekst',
                    'pill' => 'Pill (subtil knapp)',
                    'icon_button' => 'Ikonknapp (kun +)',
                ),
                'default_value' => 'button',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_trigger_variant',
                'label' => 'Trigger-variant',
                'name' => 'trigger_variant',
                'type' => 'select',
                'instructions' => 'Farge/stil for trigger',
                'choices' => array(
                    'default' => 'Standard (blå/primær)',
                    'subtle' => 'Subtil (grå bakgrunn)',
                    'ghost' => 'Ghost (med kant)',
                ),
                'default_value' => 'default',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_trigger_label',
                'label' => 'Trigger-tekst',
                'name' => 'trigger_label',
                'type' => 'text',
                'instructions' => 'Tekst på knappen/lenken',
                'default_value' => 'Les mer',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_trigger_icon',
                'label' => 'Vis +-ikon',
                'name' => 'trigger_icon',
                'type' => 'true_false',
                'instructions' => 'Vis et +-ikon foran teksten',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_trigger_size',
                'label' => 'Trigger-størrelse',
                'name' => 'trigger_size',
                'type' => 'select',
                'choices' => array(
                    'sm' => 'Liten',
                    'md' => 'Medium (standard)',
                    'lg' => 'Stor',
                ),
                'default_value' => 'md',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_trigger_alignment',
                'label' => 'Trigger-justering',
                'name' => 'trigger_alignment',
                'type' => 'select',
                'choices' => array(
                    'left' => 'Venstre',
                    'center' => 'Sentrert',
                    'right' => 'Høyre',
                ),
                'default_value' => 'left',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_trigger_helper_text',
                'label' => 'Hjelpetekst (under trigger)',
                'name' => 'trigger_helper_text',
                'type' => 'text',
                'instructions' => 'Valgfri liten tekst under knappen',
            ),

            // ===========================
            // Tab: Panel-innhold
            // ===========================
            array(
                'key' => 'field_focuspanel_tab_content',
                'label' => 'Panel-innhold',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_focuspanel_panel_kicker',
                'label' => 'Kicker (liten label)',
                'name' => 'panel_kicker',
                'type' => 'text',
                'instructions' => 'Valgfri liten label over tittelen (f.eks. "Transport", "Fasiliteter")',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_panel_title',
                'label' => 'Panel-tittel',
                'name' => 'panel_title',
                'type' => 'text',
                'instructions' => 'Hovedtittel i panelet',
                'required' => 1,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_panel_body',
                'label' => 'Panel-innhold',
                'name' => 'panel_body',
                'type' => 'wysiwyg',
                'instructions' => 'Hovedinnholdet i panelet. Kan inneholde tekst, lister, bilder m.m.',
                'required' => 1,
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ),
            array(
                'key' => 'field_focuspanel_panel_media',
                'label' => 'Hero-bilde (valgfritt)',
                'name' => 'panel_media',
                'type' => 'image',
                'instructions' => 'Stort bilde øverst i panelet',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_panel_media_caption',
                'label' => 'Bildetekst',
                'name' => 'panel_media_caption',
                'type' => 'text',
                'instructions' => 'Valgfri bildetekst under hero-bildet',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),

            // ===========================
            // Tab: Footer/CTA
            // ===========================
            array(
                'key' => 'field_focuspanel_tab_footer',
                'label' => 'Footer/CTA',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_focuspanel_panel_footer_cta_label',
                'label' => 'CTA-knapp tekst',
                'name' => 'panel_footer_cta_label',
                'type' => 'text',
                'instructions' => 'Tekst på valgfri CTA-knapp nederst i panelet',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_panel_footer_cta_url',
                'label' => 'CTA-knapp URL',
                'name' => 'panel_footer_cta_url',
                'type' => 'url',
                'instructions' => 'URL som CTA-knappen lenker til',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),

            // ===========================
            // Tab: Layout/utseende
            // ===========================
            array(
                'key' => 'field_focuspanel_tab_layout',
                'label' => 'Layout',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_focuspanel_panel_width',
                'label' => 'Panel-bredde',
                'name' => 'panel_width',
                'type' => 'select',
                'instructions' => 'Maks bredde på panelet',
                'choices' => array(
                    'md' => 'Medium (860px)',
                    'lg' => 'Stor (1040px)',
                    'xl' => 'Ekstra stor (1200px)',
                ),
                'default_value' => 'md',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_panel_height_mode',
                'label' => 'Panel-høyde',
                'name' => 'panel_height_mode',
                'type' => 'select',
                'instructions' => 'Maks høyde på panelet',
                'choices' => array(
                    'auto_with_max' => 'Standard (maks 85vh)',
                    'fuller' => 'Fyldigere (maks 92vh)',
                ),
                'default_value' => 'auto_with_max',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_overlay_blur_strength',
                'label' => 'Bakgrunnsblur',
                'name' => 'overlay_blur_strength',
                'type' => 'select',
                'instructions' => 'Styrke på blur-effekten bak panelet',
                'choices' => array(
                    '0' => 'Ingen blur',
                    '12' => 'Lett (12px)',
                    '20' => 'Medium (20px) - standard',
                    '28' => 'Sterk (28px)',
                ),
                'default_value' => '20',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_overlay_dim_strength',
                'label' => 'Bakgrunn mørkhet',
                'name' => 'overlay_dim_strength',
                'type' => 'select',
                'instructions' => 'Hvor mørk bakgrunnen skal være',
                'choices' => array(
                    '40' => 'Lett (40%)',
                    '55' => 'Medium (55%) - standard',
                    '70' => 'Sterk (70%)',
                ),
                'default_value' => '55',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_show_close_button',
                'label' => 'Vis lukkeknapp (X)',
                'name' => 'show_close_button',
                'type' => 'true_false',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_close_on_overlay_click',
                'label' => 'Lukk ved klikk utenfor',
                'name' => 'close_on_overlay_click',
                'type' => 'true_false',
                'instructions' => 'Lukk panelet når brukeren klikker på bakgrunnen',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => array(
                    'width' => '50',
                ),
            ),

            // ===========================
            // Tab: Analytics (valgfritt)
            // ===========================
            array(
                'key' => 'field_focuspanel_tab_analytics',
                'label' => 'Analytics',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_focuspanel_event_name_open',
                'label' => 'Event ved åpning',
                'name' => 'event_name_open',
                'type' => 'text',
                'instructions' => 'Navn på analytics-event når panelet åpnes',
                'default_value' => 'focuspanel_open',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_focuspanel_event_name_close',
                'label' => 'Event ved lukking',
                'name' => 'event_name_close',
                'type' => 'text',
                'instructions' => 'Navn på analytics-event når panelet lukkes',
                'default_value' => 'focuspanel_close',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/focus-panel',
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
        'description' => 'Feltgruppe for Focus Panel blokken - Apple-inspirert modal/drawer',
        'show_in_rest' => 0,
    ) );
}
