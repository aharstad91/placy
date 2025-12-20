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
            array(
                'key' => 'field_project_address',
                'label' => 'Prosjektadresse',
                'name' => 'project_address',
                'type' => 'text',
                'instructions' => 'Full adresse til prosjektet (brukes for proximity filter)',
                'required' => 0,
                'placeholder' => 'Kongens gate 1, 7011 Trondheim',
            ),
            array(
                'key' => 'field_project_coordinates',
                'label' => 'Prosjektkoordinater',
                'name' => 'project_coordinates',
                'type' => 'text',
                'instructions' => 'Koordinater i lat,lng format (f.eks. 63.4305,10.3951). Brukes for proximity filter.',
                'required' => 0,
                'placeholder' => '63.4305,10.3951',
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
            array(
                'key' => 'field_point_cached_walk_time',
                'label' => 'Cached Walk Time',
                'name' => 'cached_walk_time',
                'type' => 'number',
                'instructions' => 'Cached walking time in minutes (auto-filled by proximity filter)',
                'required' => 0,
                'readonly' => 1,
            ),
            array(
                'key' => 'field_point_cached_bike_time',
                'label' => 'Cached Bike Time',
                'name' => 'cached_bike_time',
                'type' => 'number',
                'instructions' => 'Cached biking time in minutes (auto-filled by proximity filter)',
                'required' => 0,
                'readonly' => 1,
            ),
            array(
                'key' => 'field_point_cached_drive_time',
                'label' => 'Cached Drive Time',
                'name' => 'cached_drive_time',
                'type' => 'number',
                'instructions' => 'Cached driving time in minutes (auto-filled by proximity filter)',
                'required' => 0,
                'readonly' => 1,
            ),
            array(
                'key' => 'field_point_cache_timestamp',
                'label' => 'Cache Timestamp',
                'name' => 'cache_timestamp',
                'type' => 'date_time_picker',
                'instructions' => 'Last updated timestamp for cached times',
                'required' => 0,
                'readonly' => 1,
                'display_format' => 'd/m/Y H:i',
                'return_format' => 'Y-m-d H:i:s',
            ),
            array(
                'key' => 'field_point_cache_from_coordinates',
                'label' => 'Cache From Coordinates',
                'name' => 'cache_from_coordinates',
                'type' => 'text',
                'instructions' => 'Coordinates cache was calculated from (for validation)',
                'required' => 0,
                'readonly' => 1,
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
                    0 => 'placy_native_point',
                    1 => 'placy_google_point',
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
                    0 => 'placy_native_point',
                    1 => 'placy_google_point',
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
                    0 => 'placy_native_point',
                    1 => 'placy_google_point',
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
                'key' => 'field_poi_gallery_filter_category',
                'label' => 'Filter by Category',
                'name' => 'filter_category',
                'type' => 'select',
                'instructions' => 'Filtrer POIs på kategori (valgfritt)',
                'required' => 0,
                'choices' => array(),
                'allow_null' => 1,
                'multiple' => 0,
                'ui' => 1,
                'ajax' => 0,
                'return_format' => 'value',
                'placeholder' => 'Alle kategorier',
            ),
            array(
                'key' => 'field_poi_gallery_pois',
                'label' => 'Velg POIs',
                'name' => 'poi_items',
                'type' => 'relationship',
                'instructions' => 'Velg POIs som skal vises i galleriet',
                'required' => 1,
                'post_type' => array(
                    0 => 'placy_native_point',
                    1 => 'placy_google_point',
                ),
                'filters' => array(
                    0 => 'search',
                    1 => 'post_type',
                    2 => 'taxonomy',
                ),
                'taxonomy' => 'placy_categories',
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

/**
 * Register Proximity Filter Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_proximity_filter_block',
        'title' => 'Proximity Filter Block Fields',
        'fields' => array(
            array(
                'key' => 'field_proximity_default_time',
                'label' => 'Default Time',
                'name' => 'default_time',
                'type' => 'select',
                'instructions' => 'Default reisetid ved lasting',
                'required' => 1,
                'choices' => array(
                    '10' => '10 minutter',
                    '20' => '20 minutter',
                    '30' => '30 minutter',
                ),
                'default_value' => '10',
                'allow_null' => 0,
                'ui' => 1,
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_proximity_default_mode',
                'label' => 'Default Mode',
                'name' => 'default_mode',
                'type' => 'select',
                'instructions' => 'Default transportmiddel ved lasting',
                'required' => 1,
                'choices' => array(
                    'walk' => 'Gange',
                    'bike' => 'Sykkel',
                    'drive' => 'Bil',
                ),
                'default_value' => 'walk',
                'allow_null' => 0,
                'ui' => 1,
                'return_format' => 'value',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/proximity-filter',
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
 * Populate POI Gallery category filter with actual categories
 */
add_filter( 'acf/load_field/name=filter_category', 'placy_populate_poi_gallery_categories' );
function placy_populate_poi_gallery_categories( $field ) {
    // Reset choices
    $field['choices'] = array();
    
    // Get all terms
    $terms = get_terms( array(
        'taxonomy' => 'placy_categories',
        'hide_empty' => false,
    ) );
    
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $field['choices'][ $term->term_id ] = $term->name;
        }
    }
    
    return $field;
}

/**
 * Chapter Index Block Fields
 * Repeater with label + anchor for in-chapter navigation
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_chapter_index',
        'title' => 'Kapittel Indeks',
        'fields' => array(
            array(
                'key' => 'field_chapter_index_items',
                'label' => 'Indeks-elementer',
                'name' => 'index_items',
                'type' => 'repeater',
                'instructions' => 'Legg til navigasjonselementer som peker til seksjoner i kapittelet',
                'required' => 0,
                'min' => 0,
                'max' => 10,
                'layout' => 'table',
                'button_label' => 'Legg til element',
                'sub_fields' => array(
                    array(
                        'key' => 'field_chapter_index_label',
                        'label' => 'Tekst',
                        'name' => 'label',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 1,
                        'placeholder' => 'F.eks. Buss & metrobuss',
                        'wrapper' => array(
                            'width' => '60',
                        ),
                    ),
                    array(
                        'key' => 'field_chapter_index_anchor',
                        'label' => 'Anker',
                        'name' => 'anchor',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 1,
                        'placeholder' => 'buss-metrobuss',
                        'prepend' => '#',
                        'wrapper' => array(
                            'width' => '40',
                        ),
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-index',
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
 * Register Story Intro Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_story_intro_fields',
        'title' => 'Story Intro',
        'fields' => array(
            array(
                'key' => 'field_story_intro_image',
                'label' => 'Intro Bilde',
                'name' => 'story_intro_image',
                'type' => 'image',
                'instructions' => 'Bakgrunnsbilde for intro-seksjonen (100vh)',
                'required' => 0,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_story_intro_text',
                'label' => 'Intro Tekst',
                'name' => 'story_intro_text',
                'type' => 'wysiwyg',
                'instructions' => 'Introduksjonstekst som vises over bildet',
                'required' => 0,
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ),
            array(
                'key' => 'field_story_container_bg_color',
                'label' => 'Container Bakgrunnsfarge',
                'name' => 'story_container_bg_color',
                'type' => 'color_picker',
                'instructions' => 'Bakgrunnsfarge som introen fader til (default: hvit)',
                'required' => 0,
                'default_value' => '#ffffff',
                'enable_opacity' => 0,
                'return_format' => 'string',
            ),
            array(
                'key' => 'field_story_foreword_text',
                'label' => 'Forord Tekst',
                'name' => 'story_foreword_text',
                'type' => 'wysiwyg',
                'instructions' => 'Forord/ingress som vises i indeks-seksjonen (under intro, før innholdet)',
                'required' => 0,
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 0,
            ),
            array(
                'key' => 'field_story_foreword_image',
                'label' => 'Forord Bilde',
                'name' => 'story_foreword_image',
                'type' => 'image',
                'instructions' => 'Bilde som vises ved siden av kapitteloversikten i forord-seksjonen',
                'required' => 0,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
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
        'menu_order' => 100,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register Proximity Timeline Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_proximity_timeline_block',
        'title' => 'Proximity Timeline Block Fields',
        'fields' => array(
            array(
                'key' => 'field_proximity_timeline_title',
                'label' => 'Tittel',
                'name' => 'timeline_title',
                'type' => 'text',
                'instructions' => 'Hovedtittel for timeline-seksjonen',
                'required' => 0,
                'placeholder' => 'Teknostallen er så nærme disse viktige punktene',
            ),
            array(
                'key' => 'field_proximity_timeline_subtitle',
                'label' => 'Undertittel',
                'name' => 'timeline_subtitle',
                'type' => 'text',
                'instructions' => 'Valgfri undertittel/ingress',
                'required' => 0,
                'placeholder' => 'Tiden det tar fra Teknostallen til steder du faktisk bruker i hverdagen.',
            ),
            array(
                'key' => 'field_proximity_timeline_items',
                'label' => 'Timeline Punkter',
                'name' => 'timeline_items',
                'type' => 'repeater',
                'instructions' => 'Legg til opptil 4 nærhetspunkter',
                'required' => 0,
                'min' => 1,
                'max' => 4,
                'layout' => 'block',
                'button_label' => 'Legg til punkt',
                'sub_fields' => array(
                    array(
                        'key' => 'field_proximity_timeline_item_title',
                        'label' => 'Tittel',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                        'placeholder' => 'NTNU Gløshaugen',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => 'field_proximity_timeline_item_description',
                        'label' => 'Beskrivelse',
                        'name' => 'description',
                        'type' => 'textarea',
                        'required' => 0,
                        'rows' => 2,
                        'placeholder' => 'Samarbeid rett over gata – forelesere, studenter og forskningsmiljøer innen få...',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => 'field_proximity_timeline_item_lat',
                        'label' => 'Latitude',
                        'name' => 'latitude',
                        'type' => 'number',
                        'required' => 1,
                        'placeholder' => '63.4195',
                        'step' => 'any',
                        'wrapper' => array(
                            'width' => '25',
                        ),
                    ),
                    array(
                        'key' => 'field_proximity_timeline_item_lng',
                        'label' => 'Longitude',
                        'name' => 'longitude',
                        'type' => 'number',
                        'required' => 1,
                        'placeholder' => '10.4016',
                        'step' => 'any',
                        'wrapper' => array(
                            'width' => '25',
                        ),
                    ),
                    array(
                        'key' => 'field_proximity_timeline_item_image',
                        'label' => 'Bilde (valgfritt)',
                        'name' => 'image',
                        'type' => 'image',
                        'required' => 0,
                        'return_format' => 'array',
                        'preview_size' => 'thumbnail',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_proximity_timeline_footer_text',
                'label' => 'Bunntekst',
                'name' => 'footer_text',
                'type' => 'text',
                'instructions' => 'Valgfri tekst under timeline',
                'required' => 0,
                'placeholder' => 'Lenger ned i historien ser du hvordan hverdagen ser ut med sykkel, buss og bysykkel fra Teknostallen.',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/proximity-timeline',
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
 * Register Chapter Heading Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_chapter_heading',
        'title' => 'Chapter Heading',
        'fields' => array(
            array(
                'key' => 'field_chapter_heading_level',
                'label' => 'Overskriftsnivå',
                'name' => 'heading_level',
                'type' => 'select',
                'instructions' => 'Velg overskriftsnivå',
                'required' => 0,
                'choices' => array(
                    'h2' => 'H2 - Hovedoverskrift',
                    'h3' => 'H3 - Underoverskrift',
                    'h4' => 'H4 - Liten overskrift',
                ),
                'default_value' => 'h2',
                'wrapper' => array(
                    'width' => '30',
                ),
            ),
            array(
                'key' => 'field_chapter_heading_text',
                'label' => 'Overskrift',
                'name' => 'heading_text',
                'type' => 'text',
                'required' => 1,
                'placeholder' => 'Skriv overskriften her...',
                'wrapper' => array(
                    'width' => '70',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-heading',
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
 * Register Chapter Text Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_chapter_text',
        'title' => 'Chapter Text',
        'fields' => array(
            array(
                'key' => 'field_chapter_text_variant',
                'label' => 'Tekstvariant',
                'name' => 'text_variant',
                'type' => 'select',
                'instructions' => 'Velg tekststil',
                'required' => 0,
                'choices' => array(
                    'default' => 'Standard',
                    'intro' => 'Intro (større tekst)',
                    'caption' => 'Bildetekst (liten)',
                ),
                'default_value' => 'default',
                'wrapper' => array(
                    'width' => '30',
                ),
            ),
            array(
                'key' => 'field_chapter_text_content',
                'label' => 'Tekst',
                'name' => 'text_content',
                'type' => 'wysiwyg',
                'required' => 1,
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
                'wrapper' => array(
                    'width' => '70',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-text',
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
 * Register Chapter Image Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_chapter_image',
        'title' => 'Chapter Image',
        'fields' => array(
            array(
                'key' => 'field_chapter_image_image',
                'label' => 'Bilde',
                'name' => 'image',
                'type' => 'image',
                'required' => 1,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_chapter_image_caption',
                'label' => 'Bildetekst',
                'name' => 'image_caption',
                'type' => 'text',
                'required' => 0,
                'placeholder' => 'Valgfri bildetekst...',
                'wrapper' => array(
                    'width' => '60',
                ),
            ),
            array(
                'key' => 'field_chapter_image_size',
                'label' => 'Bildestørrelse',
                'name' => 'image_size',
                'type' => 'select',
                'required' => 0,
                'choices' => array(
                    'default' => 'Standard',
                    'wide' => 'Bred',
                    'full' => 'Fullbredde',
                ),
                'default_value' => 'default',
                'wrapper' => array(
                    'width' => '40',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-image',
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
 * Register Chapter List Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_chapter_list',
        'title' => 'Chapter List',
        'fields' => array(
            array(
                'key' => 'field_chapter_list_style',
                'label' => 'Listestil',
                'name' => 'list_style',
                'type' => 'select',
                'required' => 0,
                'choices' => array(
                    'bullet' => 'Punktliste',
                    'numbered' => 'Nummerert',
                    'icon' => 'Med ikoner',
                ),
                'default_value' => 'bullet',
            ),
            array(
                'key' => 'field_chapter_list_items',
                'label' => 'Listeelementer',
                'name' => 'list_items',
                'type' => 'repeater',
                'required' => 1,
                'min' => 1,
                'layout' => 'table',
                'button_label' => 'Legg til element',
                'sub_fields' => array(
                    array(
                        'key' => 'field_chapter_list_item_text',
                        'label' => 'Tekst',
                        'name' => 'item_text',
                        'type' => 'text',
                        'required' => 1,
                        'placeholder' => 'Skriv listeelement...',
                        'wrapper' => array(
                            'width' => '70',
                        ),
                    ),
                    array(
                        'key' => 'field_chapter_list_item_icon',
                        'label' => 'Ikon (Font Awesome)',
                        'name' => 'item_icon',
                        'type' => 'text',
                        'required' => 0,
                        'placeholder' => 'fa-check',
                        'instructions' => 'Kun for ikonstil',
                        'wrapper' => array(
                            'width' => '30',
                        ),
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-list',
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
 * Register Chapter Spacer Block Fields
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_chapter_spacer',
        'title' => 'Chapter Spacer',
        'fields' => array(
            array(
                'key' => 'field_chapter_spacer_size',
                'label' => 'Størrelse',
                'name' => 'spacer_size',
                'type' => 'select',
                'required' => 0,
                'choices' => array(
                    'small' => 'Liten (16px)',
                    'medium' => 'Medium (32px)',
                    'large' => 'Stor (64px)',
                ),
                'default_value' => 'medium',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-spacer',
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
 * Register Chapter Layout Fields (Col Span)
 * 
 * Shared layout fields for all chapter content blocks.
 * Controls desktop and mobile column spans in 12-col grid.
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_chapter_layout_col_span',
        'title' => 'Layout (Kolonne-bredde)',
        'fields' => array(
            array(
                'key' => 'field_layout_col_span_desktop',
                'label' => 'Desktop bredde (kolonner)',
                'name' => 'layout_col_span_desktop',
                'type' => 'select',
                'instructions' => 'Velg bredde på desktop (12-kol grid)',
                'required' => 0,
                'choices' => array(
                    '12' => '12 - Full bredde',
                    '9'  => '9 - 3/4 bredde',
                    '8'  => '8 - 2/3 bredde',
                    '6'  => '6 - Halv bredde',
                    '4'  => '4 - 1/3 bredde',
                    '3'  => '3 - 1/4 bredde',
                ),
                'default_value' => '12',
                'wrapper' => array(
                    'width' => '50',
                ),
            ),
            array(
                'key' => 'field_layout_col_span_mobile',
                'label' => 'Mobil bredde (kolonner)',
                'name' => 'layout_col_span_mobile',
                'type' => 'select',
                'instructions' => 'Velg bredde på mobil',
                'required' => 0,
                'choices' => array(
                    '12' => '12 - Full bredde',
                    '6'  => '6 - Halv bredde',
                ),
                'default_value' => '12',
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
                    'value' => 'acf/chapter-heading',
                ),
            ),
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-text',
                ),
            ),
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-image',
                ),
            ),
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-list',
                ),
            ),
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/image-column',
                ),
            ),
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/proximity-filter',
                ),
            ),
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-index',
                ),
            ),
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/proximity-timeline',
                ),
            ),
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/travel-mode-selector',
                ),
            ),
        ),
        'menu_order' => 100,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );
}

/**
 * Register Chapter Wrapper Layout Fields
 * 
 * Controls map visibility and chapter layout mode.
 */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_chapter_wrapper_layout',
        'title' => 'Chapter Layout',
        'fields' => array(
            array(
                'key' => 'field_chapter_wrapper_has_map',
                'label' => 'Vis kart',
                'name' => 'has_map',
                'type' => 'true_false',
                'instructions' => 'Aktiver for å vise kart ved siden av innholdet',
                'required' => 0,
                'default_value' => 1,
                'ui' => 1,
                'ui_on_text' => 'Ja',
                'ui_off_text' => 'Nei',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'block',
                    'operator' => '==',
                    'value' => 'acf/chapter-wrapper',
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
