<?php
/**
 * Register ACF Field Groups
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Customer Fields (minimal - just title for now)
 * No additional fields needed yet
 */

/**
 * Register Project Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_project_fields',
        'title' => 'Project Fields',
        'fields' => array(
            array(
                'key' => 'field_project_customer',
                'label' => 'Kunde',
                'name' => 'customer',
                'type' => 'post_object',
                'instructions' => 'Velg hvilken kunde dette prosjektet tilhører',
                'required' => 1,
                'post_type' => array(
                    0 => 'customer',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'return_format' => 'object',
            ),
            array(
                'key' => 'field_project_start_latitude',
                'label' => 'Eiendommens Latitude',
                'name' => 'start_latitude',
                'type' => 'text',
                'instructions' => 'Latitude-koordinat for eiendommen/startpunkt (f.eks. 63.4305)',
                'required' => 0,
                'placeholder' => '63.4305',
            ),
            array(
                'key' => 'field_project_start_longitude',
                'label' => 'Eiendommens Longitude',
                'name' => 'start_longitude',
                'type' => 'text',
                'instructions' => 'Longitude-koordinat for eiendommen/startpunkt (f.eks. 10.3951)',
                'required' => 0,
                'placeholder' => '10.3951',
            ),
            array(
                'key' => 'field_project_property_logo',
                'label' => 'Eiendomslogo',
                'name' => 'property_logo',
                'type' => 'image',
                'instructions' => 'Last opp logo for eiendommen (vises på kartet og i popup)',
                'required' => 0,
                'return_format' => 'url',
                'preview_size' => 'thumbnail',
                'library' => 'all',
            ),
            array(
                'key' => 'field_project_property_background',
                'label' => 'Eiendomsbilde (Bakgrunn)',
                'name' => 'property_background',
                'type' => 'image',
                'instructions' => 'Last opp bakgrunnsbilde for eiendommen (vises i popup)',
                'required' => 0,
                'return_format' => 'url',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_project_property_label',
                'label' => 'Eiendommens Navn',
                'name' => 'property_label',
                'type' => 'text',
                'instructions' => 'Navn på eiendommen (f.eks. "Clarion Hotel & Congress Trondheim", "Scandic Nidelven")',
                'required' => 0,
                'placeholder' => 'Eiendommens navn',
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
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register Story Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_story_fields',
        'title' => 'Story Fields',
        'fields' => array(
            array(
                'key' => 'field_story_customer',
                'label' => 'Kunde',
                'name' => 'customer',
                'type' => 'post_object',
                'instructions' => 'Velg hvilken kunde denne historien tilhører',
                'required' => 1,
                'post_type' => array(
                    0 => 'customer',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'return_format' => 'object',
            ),
            array(
                'key' => 'field_story_project',
                'label' => 'Prosjekt',
                'name' => 'project',
                'type' => 'post_object',
                'instructions' => 'Velg hvilket prosjekt denne historien tilhører',
                'required' => 1,
                'post_type' => array(
                    0 => 'project',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'return_format' => 'object',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'story',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register Point Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_point_fields',
        'title' => 'Point Fields',
        'fields' => array(
            array(
                'key' => 'field_point_latitude',
                'label' => 'Latitude',
                'name' => 'latitude',
                'type' => 'text',
                'instructions' => 'Latitude-koordinat (f.eks. 62.3113)',
                'required' => 1,
                'placeholder' => '62.3113',
            ),
            array(
                'key' => 'field_point_longitude',
                'label' => 'Longitude',
                'name' => 'longitude',
                'type' => 'text',
                'instructions' => 'Longitude-koordinat (f.eks. 6.1326)',
                'required' => 1,
                'placeholder' => '6.1326',
            ),
            array(
                'key' => 'field_point_secondary_image',
                'label' => 'Secondary Image',
                'name' => 'secondary_image',
                'type' => 'image',
                'instructions' => 'Sekundært bilde for bruk i POI Highlight (vises side-om-side med featured image)',
                'required' => 0,
                'return_format' => 'url',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_point_google_place_id',
                'label' => 'Google Place ID',
                'name' => 'google_place_id',
                'type' => 'text',
                'instructions' => 'Google Place ID for dette stedet (f.eks. ChIJN1t_tDeuEmsRUsoyG83frY4). Brukes til å hente rating og anmeldelser fra Google. Finn Place ID på: https://developers.google.com/maps/documentation/places/web-service/place-id',
                'required' => 0,
                'placeholder' => 'ChIJN1t_tDeuEmsRUsoyG83frY4',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'point',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register API Integrations Fields for Point CPT
 * Conditional visibility: Only shown when Point Type = "Transport"
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_point_api_integrations',
        'title' => 'API Integrations',
        'fields' => array(
            array(
                'key' => 'field_entur_stopplace_id',
                'label' => 'Entur StopPlace ID',
                'name' => 'entur_stopplace_id',
                'type' => 'text',
                'instructions' => 'StopPlace ID fra Entur (format: NSR:StopPlace:xxxxx). Finn ID på stoppested.entur.org. Eksempel: NSR:StopPlace:74006 (Trondheim hurtigbåtterminal)',
                'required' => 0,
                'placeholder' => 'NSR:StopPlace:74006',
            ),
            array(
                'key' => 'field_entur_quay_id',
                'label' => 'Entur Quay ID',
                'name' => 'entur_quay_id',
                'type' => 'text',
                'instructions' => 'Valgfri - For spesifikk kai/plattform (format: NSR:Quay:xxxxx). La stå tom for å vise alle avganger fra stoppestedet.',
                'required' => 0,
                'placeholder' => 'NSR:Quay:xxxxx',
            ),
            array(
                'key' => 'field_entur_transport_mode',
                'label' => 'Transportmiddel',
                'name' => 'entur_transport_mode',
                'type' => 'select',
                'instructions' => 'Valgfri - Filtrer avganger på transportmiddel. Viktig for steder med både tog og buss (som Trondheim S).',
                'required' => 0,
                'choices' => array(
                    '' => 'Alle transportmiddel',
                    'rail' => 'Tog',
                    'bus' => 'Buss',
                    'water' => 'Båt/Ferge',
                    'metro' => 'T-bane',
                    'tram' => 'Trikk',
                ),
                'default_value' => '',
                'allow_null' => 1,
                'ui' => 1,
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_show_live_departures',
                'label' => 'Vis Live Avganger',
                'name' => 'show_live_departures',
                'type' => 'true_false',
                'instructions' => 'Aktiver for å vise sanntids avgangsinformasjon fra Entur i POI-kortet',
                'required' => 0,
                'default_value' => 0,
                'ui' => 1,
            ),
            array(
                'key' => 'field_bysykkel_station_id',
                'label' => 'Trondheim Bysykkel Station ID',
                'name' => 'bysykkel_station_id',
                'type' => 'text',
                'instructions' => 'Station ID fra Trondheim Bysykkel (format: 5430 eller 66). Finn ID på urbansharing.com eller via API. Eksempel: 5430 (Jernbanebrua)',
                'required' => 0,
                'placeholder' => '5430',
            ),
            array(
                'key' => 'field_show_bike_availability',
                'label' => 'Vis Ledig Bysykkel',
                'name' => 'show_bike_availability',
                'type' => 'true_false',
                'instructions' => 'Aktiver for å vise antall ledige sykler i sanntid fra Trondheim Bysykkel',
                'required' => 0,
                'default_value' => 0,
                'ui' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'point',
                ),
                array(
                    'param' => 'post_taxonomy',
                    'operator' => '==',
                    'value' => 'point_type:transport',
                ),
            ),
        ),
        'menu_order' => 1,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register Detail Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_detail_fields',
        'title' => 'Detail Fields',
        'fields' => array(
            array(
                'key' => 'field_detail_latitude',
                'label' => 'Latitude',
                'name' => 'latitude',
                'type' => 'text',
                'instructions' => 'Latitude-koordinat (f.eks. 62.3113)',
                'required' => 1,
                'placeholder' => '62.3113',
            ),
            array(
                'key' => 'field_detail_longitude',
                'label' => 'Longitude',
                'name' => 'longitude',
                'type' => 'text',
                'instructions' => 'Longitude-koordinat (f.eks. 6.1326)',
                'required' => 1,
                'placeholder' => '6.1326',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'detail',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register Area Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_area_fields',
        'title' => 'Area Fields',
        'fields' => array(
            array(
                'key' => 'field_area_latitude',
                'label' => 'Latitude',
                'name' => 'latitude',
                'type' => 'text',
                'instructions' => 'Latitude-koordinat (f.eks. 62.3113)',
                'required' => 1,
                'placeholder' => '62.3113',
            ),
            array(
                'key' => 'field_area_longitude',
                'label' => 'Longitude',
                'name' => 'longitude',
                'type' => 'text',
                'instructions' => 'Longitude-koordinat (f.eks. 6.1326)',
                'required' => 1,
                'placeholder' => '6.1326',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'area',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register POI Map Card Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_poi_map_card',
        'title' => 'POI Kart Innstillinger',
        'fields' => array(
            array(
                'key' => 'field_map_title',
                'label' => 'Kart Tittel',
                'name' => 'map_title',
                'type' => 'text',
                'instructions' => 'Tittel som vises på kartblokken',
                'required' => 1,
                'default_value' => 'POI Kart',
                'placeholder' => 'F.eks. Idrett & Trening',
            ),
            array(
                'key' => 'field_selected_pois',
                'label' => 'Velg Points',
                'name' => 'selected_pois',
                'type' => 'relationship',
                'instructions' => 'Velg hvilke Points som skal vises på kartet',
                'required' => 1,
                'post_type' => array(
                    0 => 'point',
                ),
                'filters' => array(
                    0 => 'search',
                    1 => 'post_type',
                ),
                'return_format' => 'object',
                'min' => 1,
                'max' => '',
                'elements' => array(
                    0 => 'featured_image',
                ),
                'bidirectional_target' => array(),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/poi-map-card',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register POI List Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_poi_list',
        'title' => 'POI Liste Innstillinger',
        'fields' => array(
            array(
                'key' => 'field_poi_items',
                'label' => 'Velg POIs',
                'name' => 'poi_items',
                'type' => 'relationship',
                'instructions' => 'Velg hvilke POIs (Points) som skal vises i listen',
                'required' => 1,
                'post_type' => array(
                    0 => 'point',
                ),
                'filters' => array(
                    0 => 'search',
                    1 => 'post_type',
                ),
                'return_format' => 'object',
                'min' => 1,
                'max' => '',
                'elements' => array(
                    0 => 'featured_image',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/poi-list',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register Theme Story Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_theme_story_fields',
        'title' => 'Theme Story Fields',
        'fields' => array(
            array(
                'key' => 'field_theme_story_intro_title',
                'label' => 'Intro Tittel',
                'name' => 'intro_title',
                'type' => 'text',
                'instructions' => 'Hovedtittel for intro-seksjonen (vises i 100vh intro)',
                'required' => 0,
                'placeholder' => 'Velkommen til...',
            ),
            array(
                'key' => 'field_theme_story_intro_text',
                'label' => 'Intro Tekst',
                'name' => 'intro_text',
                'type' => 'textarea',
                'instructions' => 'Ingress/introduksjonstekst for intro-seksjonen',
                'required' => 0,
                'rows' => 4,
                'placeholder' => 'En kort beskrivelse av historien...',
            ),
            array(
                'key' => 'field_theme_story_intro_background',
                'label' => 'Intro Bakgrunnsbilde',
                'name' => 'intro_background',
                'type' => 'image',
                'instructions' => 'Bakgrunnsbilde for intro-seksjonen (100vh)',
                'required' => 0,
                'return_format' => 'url',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_theme_story_container_bg_color',
                'label' => 'Container Bakgrunnsfarge',
                'name' => 'container_background_color',
                'type' => 'color_picker',
                'instructions' => 'Valgfri bakgrunnsfarge for området mellom intro og innhold (hex-kode, f.eks. #383d46). Går gradvis over til hvit.',
                'required' => 0,
                'default_value' => '#f5f5f5',
                'enable_opacity' => 0,
                'return_format' => 'string',
            ),
            array(
                'key' => 'field_theme_story_project',
                'label' => 'Prosjekt',
                'name' => 'project',
                'type' => 'post_object',
                'instructions' => 'Velg hvilket prosjekt denne theme story tilhører',
                'required' => 0,
                'post_type' => array(
                    0 => 'project',
                ),
                'allow_null' => 1,
                'multiple' => 0,
                'return_format' => 'object',
            ),
            array(
                'key' => 'field_theme_story_parent_story',
                'label' => 'Parent Story',
                'name' => 'parent_story',
                'type' => 'post_object',
                'instructions' => 'Velg overordnet story som denne tema-story tilhører (for tilbake-knapp)',
                'required' => 0,
                'post_type' => array(
                    0 => 'story',
                ),
                'allow_null' => 1,
                'multiple' => 0,
                'return_format' => 'object',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'theme-story',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register Image Column Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_image_column_block',
        'title' => 'Image Column Block Fields',
        'fields' => array(
            array(
                'key' => 'field_image_column_image_1',
                'label' => 'Image 1 (60%)',
                'name' => 'image_1',
                'type' => 'image',
                'instructions' => 'Første bilde (tar 60% av bredden)',
                'required' => 0,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_image_column_image_2',
                'label' => 'Image 2 (40%)',
                'name' => 'image_2',
                'type' => 'image',
                'instructions' => 'Andre bilde (tar 40% av bredden)',
                'required' => 0,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/image-column',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register POI Highlight Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_poi_highlight_block',
        'title' => 'POI Highlight Block Fields',
        'fields' => array(
            array(
                'key' => 'field_poi_highlight_poi',
                'label' => 'POI Item',
                'name' => 'poi_item',
                'type' => 'post_object',
                'instructions' => 'Velg ett POI som skal fremheves med stor layout',
                'required' => 1,
                'post_type' => array(
                    0 => 'point',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'return_format' => 'object',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/poi-highlight',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register POI Gallery Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_poi_gallery_block',
        'title' => 'POI Gallery Block Fields',
        'fields' => array(
            array(
                'key' => 'field_poi_gallery_pois',
                'label' => 'Velg POIs',
                'name' => 'poi_items',
                'type' => 'relationship',
                'instructions' => 'Velg POIs som skal vises i galleriet',
                'required' => 1,
                'post_type' => array(
                    0 => 'point',
                ),
                'filters' => array(
                    0 => 'search',
                    1 => 'post_type',
                ),
                'return_format' => 'object',
                'min' => 1,
                'max' => '',
                'elements' => array(
                    0 => 'featured_image',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/poi-gallery',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}
