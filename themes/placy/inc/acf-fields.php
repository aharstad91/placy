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
