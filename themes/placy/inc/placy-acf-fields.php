<?php
/**
 * Placy ACF Field Groups Configuration
 * Programmatic field group registration for Native and Google Points
 *
 * @package Placy
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register ACF fields for Native Points
 */
add_action( 'acf/init', 'placy_register_native_point_fields' );
function placy_register_native_point_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }
    
    acf_add_local_field_group( array(
        'key' => 'group_placy_native_point',
        'title' => 'Native Point Details',
        'fields' => array(
            // Tab: Location
            array(
                'key' => 'field_native_tab_location',
                'label' => 'Location',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_native_project_id',
                'label' => 'Project',
                'name' => 'project_id',
                'type' => 'relationship',
                'post_type' => array(
                    0 => 'project',
                ),
                'filters' => array(
                    0 => 'search',
                ),
                'return_format' => 'id',
                'max' => 1,
            ),
            array(
                'key' => 'field_native_name',
                'label' => 'Name',
                'name' => 'name',
                'type' => 'text',
                'required' => 1,
            ),
            array(
                'key' => 'field_native_address',
                'label' => 'Address',
                'name' => 'address',
                'type' => 'text',
            ),
            array(
                'key' => 'field_native_coordinates',
                'label' => 'Coordinates',
                'name' => 'coordinates',
                'type' => 'group',
                'layout' => 'row',
                'sub_fields' => array(
                    array(
                        'key' => 'field_native_latitude',
                        'label' => 'Latitude',
                        'name' => 'latitude',
                        'type' => 'number',
                        'required' => 1,
                        'step' => 0.000001,
                    ),
                    array(
                        'key' => 'field_native_longitude',
                        'label' => 'Longitude',
                        'name' => 'longitude',
                        'type' => 'number',
                        'required' => 1,
                        'step' => 0.000001,
                    ),
                ),
            ),
            
            // Tab: Content
            array(
                'key' => 'field_native_tab_content',
                'label' => 'Content',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_native_description',
                'label' => 'Description',
                'name' => 'description',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ),
            array(
                'key' => 'field_native_images',
                'label' => 'Images',
                'name' => 'images',
                'type' => 'gallery',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            
            // Tab: Contact
            array(
                'key' => 'field_native_tab_contact',
                'label' => 'Contact',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_native_website',
                'label' => 'Website',
                'name' => 'website',
                'type' => 'url',
            ),
            array(
                'key' => 'field_native_phone',
                'label' => 'Phone',
                'name' => 'phone',
                'type' => 'text',
            ),
            
            // Tab: Display
            array(
                'key' => 'field_native_tab_display',
                'label' => 'Display',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_native_featured',
                'label' => 'Featured',
                'name' => 'featured',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
            ),
            array(
                'key' => 'field_native_display_priority',
                'label' => 'Display Priority',
                'name' => 'display_priority',
                'type' => 'range',
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'default_value' => 5,
                'instructions' => '1 = Lowest priority, 10 = Highest priority',
            ),
            array(
                'key' => 'field_native_hide_from_display',
                'label' => 'Hide from Display',
                'name' => 'hide_from_display',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
                'instructions' => 'Temporarily hide this point without deleting it',
            ),
            
            // Tab: Business
            array(
                'key' => 'field_native_tab_business',
                'label' => 'Business',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_native_is_sponsored',
                'label' => 'Is Sponsored',
                'name' => 'is_sponsored',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
            ),
            array(
                'key' => 'field_native_sponsor_info',
                'label' => 'Sponsor Information',
                'name' => 'sponsor_info',
                'type' => 'group',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_is_sponsored',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
                'sub_fields' => array(
                    array(
                        'key' => 'field_native_sponsor_name',
                        'label' => 'Sponsor Name',
                        'name' => 'sponsor_name',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_native_sponsor_url',
                        'label' => 'Sponsor URL',
                        'name' => 'sponsor_url',
                        'type' => 'url',
                    ),
                ),
            ),
            array(
                'key' => 'field_native_seasonal_active',
                'label' => 'Seasonal Active Period',
                'name' => 'seasonal_active',
                'type' => 'group',
                'instructions' => 'Optional: Set date range when this point is active',
                'sub_fields' => array(
                    array(
                        'key' => 'field_native_season_start',
                        'label' => 'Start Date',
                        'name' => 'start_date',
                        'type' => 'date_picker',
                        'display_format' => 'd/m/Y',
                        'return_format' => 'Y-m-d',
                    ),
                    array(
                        'key' => 'field_native_season_end',
                        'label' => 'End Date',
                        'name' => 'end_date',
                        'type' => 'date_picker',
                        'display_format' => 'd/m/Y',
                        'return_format' => 'Y-m-d',
                    ),
                ),
            ),
            array(
                'key' => 'field_native_internal_notes',
                'label' => 'Internal Notes',
                'name' => 'internal_notes',
                'type' => 'textarea',
                'instructions' => 'Private notes for internal use only',
            ),
            
            // Tab: Transport/API Integrations
            array(
                'key' => 'field_native_tab_transport',
                'label' => 'Transport/API',
                'type' => 'tab',
            ),
            // API Integration Selector
            array(
                'key' => 'field_native_api_integrations',
                'label' => 'Aktive API-integrasjoner',
                'name' => 'api_integrations',
                'type' => 'checkbox',
                'instructions' => 'Velg hvilke API-integrasjoner som skal aktiveres for dette punktet. Kun relevante felter vil vises.',
                'choices' => array(
                    'entur' => '🚌 Entur Kollektivtransport',
                    'bysykkel' => '🚲 Trondheim Bysykkel',
                    'hyre' => '🚗 Hyre (Bilutleie)',
                ),
                'layout' => 'horizontal',
                'return_format' => 'value',
            ),
            
            // Entur Accordion
            array(
                'key' => 'field_native_accordion_entur',
                'label' => '🚌 Entur Kollektivtransport',
                'type' => 'accordion',
                'open' => 1,
                'multi_expand' => 1,
                'endpoint' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'entur',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_entur_stopplace_id',
                'label' => 'Entur StopPlace ID',
                'name' => 'entur_stopplace_id',
                'type' => 'text',
                'instructions' => 'StopPlace ID fra Entur (format: NSR:StopPlace:xxxxx). Finn ID på <a href="https://stoppested.entur.org" target="_blank">stoppested.entur.org</a>. Eksempel: NSR:StopPlace:41620 (Hesthagen)',
                'placeholder' => 'NSR:StopPlace:41620',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'entur',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_entur_quay_id',
                'label' => 'Entur Quay ID (valgfri)',
                'name' => 'entur_quay_id',
                'type' => 'text',
                'instructions' => 'For å kun vise én retning, oppgi Quay ID (format: NSR:Quay:xxxxx). La stå tom for å vise alle retninger.',
                'placeholder' => 'NSR:Quay:xxxxx',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'entur',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_entur_group_by_direction',
                'label' => 'Grupper per retning',
                'name' => 'entur_group_by_direction',
                'type' => 'true_false',
                'instructions' => 'Vis avganger gruppert per retning/plattform (anbefalt for holdeplasser med flere retninger)',
                'ui' => 1,
                'default_value' => 1,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'entur',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_entur_transport_mode',
                'label' => 'Transportmiddel',
                'name' => 'entur_transport_mode',
                'type' => 'select',
                'instructions' => 'Valgfri - Filtrer avganger på transportmiddel.',
                'choices' => array(
                    '' => 'Alle transportmiddel',
                    'rail' => 'Tog',
                    'bus' => 'Buss',
                    'coach' => 'Ekspressbuss',
                    'water' => 'Båt/Ferge',
                    'metro' => 'T-bane',
                    'tram' => 'Trikk',
                ),
                'default_value' => '',
                'allow_null' => 1,
                'ui' => 1,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'entur',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_entur_line_filter',
                'label' => 'Linjefilter',
                'name' => 'entur_line_filter',
                'type' => 'text',
                'instructions' => 'Valgfri - Vis kun avganger for spesifikke linjer. Kommaseparert liste av linjenummer. Eksempel: FB73 eller 1,2,3',
                'placeholder' => 'FB73',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'entur',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_show_live_departures',
                'label' => 'Vis Live Avganger',
                'name' => 'show_live_departures',
                'type' => 'true_false',
                'instructions' => 'Aktiver for å vise sanntids avgangsinformasjon fra Entur i POI-kortet',
                'ui' => 1,
                'default_value' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'entur',
                        ),
                    ),
                ),
            ),
            
            // Bysykkel Accordion
            array(
                'key' => 'field_native_accordion_bysykkel',
                'label' => '🚲 Trondheim Bysykkel',
                'type' => 'accordion',
                'open' => 0,
                'multi_expand' => 1,
                'endpoint' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'bysykkel',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_bysykkel_station_id',
                'label' => 'Trondheim Bysykkel Station ID',
                'name' => 'bysykkel_station_id',
                'type' => 'text',
                'instructions' => 'Station ID fra Trondheim Bysykkel. Eksempel: 5430 (Jernbanebrua)',
                'placeholder' => '5430',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'bysykkel',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_show_bike_availability',
                'label' => 'Vis Ledig Bysykkel',
                'name' => 'show_bike_availability',
                'type' => 'true_false',
                'instructions' => 'Aktiver for å vise antall ledige sykler i sanntid',
                'ui' => 1,
                'default_value' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'bysykkel',
                        ),
                    ),
                ),
            ),
            
            // Hyre Accordion
            array(
                'key' => 'field_native_accordion_hyre',
                'label' => '🚗 Hyre (Bilutleie)',
                'type' => 'accordion',
                'open' => 0,
                'multi_expand' => 1,
                'endpoint' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'hyre',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_hyre_station_id',
                'label' => 'Hyre Station ID',
                'name' => 'hyre_station_id',
                'type' => 'text',
                'instructions' => 'Station ID fra Hyre (format: HYR:Station:xxxx). Finn ID via /wp-json/placy/v1/hyre/stations?region=norge_trondheim',
                'placeholder' => 'HYR:Station:c21af781-24dd-4bcf-bfce-07fae61f4114',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'hyre',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_native_show_hyre_availability',
                'label' => 'Vis Ledig Hyre',
                'name' => 'show_hyre_availability',
                'type' => 'true_false',
                'instructions' => 'Aktiver for å vise antall ledige biler fra Hyre i sanntid',
                'ui' => 1,
                'default_value' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_native_api_integrations',
                            'operator' => '==',
                            'value' => 'hyre',
                        ),
                    ),
                ),
            ),
            // End accordion
            array(
                'key' => 'field_native_accordion_end',
                'label' => '',
                'type' => 'accordion',
                'endpoint' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'placy_native_point',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ) );
}

/**
 * Register ACF fields for Google Points
 */
add_action( 'acf/init', 'placy_register_google_point_fields' );
function placy_register_google_point_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }
    
    acf_add_local_field_group( array(
        'key' => 'group_placy_google_point',
        'title' => 'Google Point Details',
        'fields' => array(
            // Tab: Google Data
            array(
                'key' => 'field_google_tab_google_data',
                'label' => 'Google Data',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_google_project_id',
                'label' => 'Project',
                'name' => 'project_id',
                'type' => 'relationship',
                'post_type' => array(
                    0 => 'project',
                ),
                'filters' => array(
                    0 => 'search',
                ),
                'return_format' => 'id',
                'max' => 1,
            ),
            array(
                'key' => 'field_google_place_id',
                'label' => 'Google Place ID',
                'name' => 'google_place_id',
                'type' => 'text',
                'required' => 1,
                'instructions' => 'Enter the Google Place ID. Data will be fetched automatically on save.',
            ),
            array(
                'key' => 'field_google_nearby_search_cache',
                'label' => 'Nearby Search Cache',
                'name' => 'nearby_search_cache',
                'type' => 'textarea',
                'rows' => 8,
                'readonly' => 1,
                'wrapper' => array(
                    'class' => 'placy-readonly-field',
                ),
            ),
            array(
                'key' => 'field_google_place_details_cache',
                'label' => 'Place Details Cache',
                'name' => 'place_details_cache',
                'type' => 'textarea',
                'rows' => 8,
                'readonly' => 1,
                'wrapper' => array(
                    'class' => 'placy-readonly-field',
                ),
            ),
            array(
                'key' => 'field_google_last_synced',
                'label' => 'Last Synced',
                'name' => 'last_synced',
                'type' => 'text',
                'readonly' => 1,
                'wrapper' => array(
                    'class' => 'placy-readonly-field',
                ),
            ),
            
            // Tab: Editorial
            array(
                'key' => 'field_google_tab_editorial',
                'label' => 'Editorial',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_google_editorial_text',
                'label' => 'Editorial Text',
                'name' => 'editorial_text',
                'type' => 'wysiwyg',
                'instructions' => 'Add custom editorial content to supplement Google data',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ),
            array(
                'key' => 'field_google_featured',
                'label' => 'Featured',
                'name' => 'featured',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
                'instructions' => 'Featured points are refreshed daily',
            ),
            array(
                'key' => 'field_google_display_priority',
                'label' => 'Display Priority',
                'name' => 'display_priority',
                'type' => 'range',
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'default_value' => 5,
                'instructions' => '1 = Lowest priority, 10 = Highest priority',
            ),
            
            // Tab: Business
            array(
                'key' => 'field_google_tab_business',
                'label' => 'Business',
                'type' => 'tab',
            ),
            array(
                'key' => 'field_google_is_sponsored',
                'label' => 'Is Sponsored',
                'name' => 'is_sponsored',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
            ),
            array(
                'key' => 'field_google_sponsor_info',
                'label' => 'Sponsor Information',
                'name' => 'sponsor_info',
                'type' => 'group',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_google_is_sponsored',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
                'sub_fields' => array(
                    array(
                        'key' => 'field_google_sponsor_name',
                        'label' => 'Sponsor Name',
                        'name' => 'sponsor_name',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_google_sponsor_url',
                        'label' => 'Sponsor URL',
                        'name' => 'sponsor_url',
                        'type' => 'url',
                    ),
                ),
            ),
            array(
                'key' => 'field_google_internal_notes',
                'label' => 'Internal Notes',
                'name' => 'internal_notes',
                'type' => 'textarea',
                'instructions' => 'Private notes for internal use only',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'placy_google_point',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ) );
}

/**
 * Register ACF fields for Placy Categories taxonomy
 * Adds icon field for category markers on map
 */
add_action( 'acf/init', 'placy_register_category_taxonomy_fields' );
function placy_register_category_taxonomy_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }
    
    acf_add_local_field_group( array(
        'key' => 'group_placy_category_taxonomy',
        'title' => 'Category Settings',
        'fields' => array(
            array(
                'key' => 'field_category_icon',
                'label' => 'Font Awesome Icon',
                'name' => 'category_icon',
                'type' => 'text',
                'instructions' => 'Skriv inn Font Awesome ikon-klasse (f.eks. <code>fa-bus</code>, <code>fa-utensils</code>, <code>fa-coffee</code>). Se alle ikoner på <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com/icons</a>',
                'placeholder' => 'fa-location-dot',
                'prepend' => 'fa-solid',
            ),
            array(
                'key' => 'field_category_color',
                'label' => 'Icon Background Color',
                'name' => 'category_color',
                'type' => 'color_picker',
                'instructions' => 'Bakgrunnsfarge for ikon-markøren på kartet',
                'default_value' => '#6366F1',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => 'placy_categories',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ) );
}

/**
 * Get category icon for a POI
 * Returns the Font Awesome icon class from the first category term
 * 
 * @param int $post_id The POI post ID
 * @return array Array with 'icon' and 'color' keys, or defaults if not set
 */
function placy_get_poi_category_icon( $post_id ) {
    $default = array(
        'icon' => 'fa-location-dot',
        'color' => '#6366F1',
    );
    
    // Get placy_categories terms for this post
    $terms = get_the_terms( $post_id, 'placy_categories' );
    
    if ( ! $terms || is_wp_error( $terms ) ) {
        return $default;
    }
    
    // Get the first term with an icon set
    foreach ( $terms as $term ) {
        $icon = get_field( 'category_icon', 'placy_categories_' . $term->term_id );
        $color = get_field( 'category_color', 'placy_categories_' . $term->term_id );
        
        if ( $icon ) {
            return array(
                'icon' => $icon,
                'color' => $color ? $color : $default['color'],
            );
        }
    }
    
    return $default;
}

/**
 * Register ACF fields for Travel Calculator Block
 */
add_action( 'acf/init', 'placy_register_travel_calculator_fields' );
function placy_register_travel_calculator_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }
    
    acf_add_local_field_group( array(
        'key' => 'group_travel_calculator',
        'title' => 'Travel Calculator Settings',
        'fields' => array(
            array(
                'key' => 'field_travel_calc_transport_mode',
                'label' => 'Transportmiddel',
                'name' => 'transport_mode',
                'type' => 'select',
                'choices' => array(
                    'cycling' => '🚴 Sykkel',
                    'walking' => '🚶 Gange',
                    'driving' => '🚗 Bil',
                ),
                'default_value' => 'cycling',
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_travel_calc_custom_title',
                'label' => 'Egendefinert tittel (valgfri)',
                'name' => 'custom_title',
                'type' => 'text',
                'instructions' => 'La stå tom for standard tittel basert på transportmiddel og eiendomsnavn.',
                'placeholder' => 'F.eks. "Beregn sykkeltid til kontoret"',
            ),
            array(
                'key' => 'field_travel_calc_custom_placeholder',
                'label' => 'Egendefinert placeholder (valgfri)',
                'name' => 'custom_placeholder',
                'type' => 'text',
                'instructions' => 'Placeholder-tekst i søkefeltet.',
                'placeholder' => 'Skriv inn adressen din...',
            ),
            array(
                'key' => 'field_travel_calc_quick_areas',
                'label' => 'Hurtigknapper (områder)',
                'name' => 'quick_areas',
                'type' => 'repeater',
                'instructions' => 'Legg til typiske startområder som hurtigknapper. Brukeren kan klikke på disse istedenfor å skrive inn adresse.',
                'min' => 0,
                'max' => 5,
                'layout' => 'table',
                'button_label' => 'Legg til område',
                'sub_fields' => array(
                    array(
                        'key' => 'field_travel_calc_area_name',
                        'label' => 'Områdenavn',
                        'name' => 'area_name',
                        'type' => 'text',
                        'placeholder' => 'F.eks. Midtbyen',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '30',
                        ),
                    ),
                    array(
                        'key' => 'field_travel_calc_area_lat',
                        'label' => 'Breddegrad (lat)',
                        'name' => 'area_lat',
                        'type' => 'number',
                        'placeholder' => '63.4305',
                        'step' => 'any',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '35',
                        ),
                    ),
                    array(
                        'key' => 'field_travel_calc_area_lng',
                        'label' => 'Lengdegrad (lng)',
                        'name' => 'area_lng',
                        'type' => 'number',
                        'placeholder' => '10.3951',
                        'step' => 'any',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '35',
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
                    'value' => 'acf/travel-calculator',
                ),
            ),
        ),
    ) );
    
    // ===========================================
    // BYSYKKEL STATIONS BLOCK FIELDS
    // ===========================================
    acf_add_local_field_group( array(
        'key' => 'group_bysykkel_stations',
        'title' => 'Bysykkel Stasjoner Settings',
        'fields' => array(
            array(
                'key' => 'field_bysykkel_custom_title',
                'label' => 'Tittel',
                'name' => 'custom_title',
                'type' => 'text',
                'default_value' => 'Trondheim Bysykkel Stasjoner',
                'placeholder' => 'F.eks. "Trondheim Bysykkel Stasjoner"',
            ),
            array(
                'key' => 'field_bysykkel_stations',
                'label' => 'Stasjoner',
                'name' => 'stations',
                'type' => 'repeater',
                'instructions' => 'Legg til bysykkelstasjoner som skal vises.',
                'min' => 0,
                'max' => 10,
                'layout' => 'block',
                'button_label' => 'Legg til stasjon',
                'sub_fields' => array(
                    array(
                        'key' => 'field_bysykkel_station_poi',
                        'label' => 'Stasjon (POI)',
                        'name' => 'station',
                        'type' => 'post_object',
                        'post_type' => array( 'placy_native_point' ),
                        'return_format' => 'object',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '60',
                        ),
                    ),
                    array(
                        'key' => 'field_bysykkel_walking_time',
                        'label' => 'Gangtid',
                        'name' => 'walking_time',
                        'type' => 'text',
                        'placeholder' => 'F.eks. "3 min"',
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
                    'value' => 'acf/bysykkel-stations',
                ),
            ),
        ),
    ) );
    
    // ===========================================
    // HYRE STATIONS BLOCK FIELDS
    // ===========================================
    acf_add_local_field_group( array(
        'key' => 'group_hyre_stations',
        'title' => 'Hyre Stasjoner Settings',
        'fields' => array(
            array(
                'key' => 'field_hyre_section_title',
                'label' => 'Seksjonstittel',
                'name' => 'section_title',
                'type' => 'text',
                'default_value' => 'Bil, bildeling og taxi – fleksibilitet når du trenger det',
            ),
            array(
                'key' => 'field_hyre_section_description',
                'label' => 'Seksjonsbeskrivelse',
                'name' => 'section_description',
                'type' => 'textarea',
                'rows' => 3,
                'placeholder' => 'Kort beskrivelse av Hyre-tilbudet i området.',
            ),
            array(
                'key' => 'field_hyre_stations',
                'label' => 'Stasjoner',
                'name' => 'stations',
                'type' => 'repeater',
                'instructions' => 'Legg til Hyre-stasjoner som skal vises.',
                'min' => 0,
                'max' => 10,
                'layout' => 'block',
                'button_label' => 'Legg til stasjon',
                'sub_fields' => array(
                    array(
                        'key' => 'field_hyre_station_poi',
                        'label' => 'Stasjon (POI)',
                        'name' => 'station',
                        'type' => 'post_object',
                        'post_type' => array( 'placy_native_point' ),
                        'return_format' => 'object',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '60',
                        ),
                    ),
                    array(
                        'key' => 'field_hyre_walking_time',
                        'label' => 'Gangtid',
                        'name' => 'walking_time',
                        'type' => 'text',
                        'placeholder' => 'F.eks. "0 min gange"',
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
                    'value' => 'acf/hyre-stations',
                ),
            ),
        ),
    ) );
    
    // ===========================================
    // BUS STOPS BLOCK FIELDS
    // ===========================================
    acf_add_local_field_group( array(
        'key' => 'group_bus_stops',
        'title' => 'Buss Holdeplasser Settings',
        'fields' => array(
            array(
                'key' => 'field_bus_section_title',
                'label' => 'Seksjonstittel',
                'name' => 'section_title',
                'type' => 'text',
                'default_value' => 'Buss og flybuss',
            ),
            array(
                'key' => 'field_bus_section_description',
                'label' => 'Seksjonsbeskrivelse',
                'name' => 'section_description',
                'type' => 'textarea',
                'rows' => 3,
                'placeholder' => 'Kort beskrivelse av busstilbudet i området.',
            ),
            array(
                'key' => 'field_bus_stops',
                'label' => 'Holdeplasser',
                'name' => 'stops',
                'type' => 'repeater',
                'instructions' => 'Legg til bussholdeplasser som skal vises.',
                'min' => 0,
                'max' => 10,
                'layout' => 'block',
                'button_label' => 'Legg til holdeplass',
                'sub_fields' => array(
                    array(
                        'key' => 'field_bus_stop_poi',
                        'label' => 'Holdeplass (POI)',
                        'name' => 'stop',
                        'type' => 'post_object',
                        'post_type' => array( 'placy_native_point' ),
                        'return_format' => 'object',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '40',
                        ),
                    ),
                    array(
                        'key' => 'field_bus_direction_label',
                        'label' => 'Retningsetikett',
                        'name' => 'direction_label',
                        'type' => 'text',
                        'placeholder' => 'F.eks. "fra sentrum" eller "til sentrum"',
                        'wrapper' => array(
                            'width' => '30',
                        ),
                    ),
                    array(
                        'key' => 'field_bus_walking_time',
                        'label' => 'Gangtid',
                        'name' => 'walking_time',
                        'type' => 'text',
                        'placeholder' => 'F.eks. "5 min gange"',
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
                    'value' => 'acf/bus-stops',
                ),
            ),
        ),
    ) );
}

/**
 * Add custom CSS for readonly fields
 */
add_action( 'acf/input/admin_head', 'placy_acf_admin_styles' );
function placy_acf_admin_styles() {
    ?>
    <style>
        .placy-readonly-field textarea,
        .placy-readonly-field input {
            background-color: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }
    </style>
    <?php
}
