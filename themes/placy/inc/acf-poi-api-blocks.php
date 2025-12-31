<?php
/**
 * ACF POI API Blocks Registration
 * 
 * Registers three single-POI blocks for API integrations:
 * - poi-entur: Entur bus/transit stops with live departures
 * - poi-bysykkel: Trondheim Bysykkel stations with availability
 * - poi-hyre: Hyre car sharing stations with availability
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register ACF Field Groups for POI API Blocks
 */
add_action( 'acf/init', 'placy_register_poi_api_blocks_fields' );
function placy_register_poi_api_blocks_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    // ============================================================
    // POI Entur Block - Field Group
    // ============================================================
    acf_add_local_field_group( array(
        'key' => 'group_poi_entur_block',
        'title' => 'Entur POI Settings',
        'fields' => array(
            array(
                'key' => 'field_poi_entur_item',
                'label' => 'Velg Entur-stoppeplass',
                'name' => 'poi_item',
                'type' => 'relationship',
                'instructions' => 'Velg én POI som har Entur-integrasjon (bussholdeplass, t-bane, etc.)',
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
                'max' => 1,
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
                    'value' => 'acf/poi-entur',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );

    // ============================================================
    // POI Bysykkel Block - Field Group
    // ============================================================
    acf_add_local_field_group( array(
        'key' => 'group_poi_bysykkel_block',
        'title' => 'Bysykkel POI Settings',
        'fields' => array(
            array(
                'key' => 'field_poi_bysykkel_item',
                'label' => 'Velg Bysykkel-stasjon',
                'name' => 'poi_item',
                'type' => 'relationship',
                'instructions' => 'Velg én POI som har Trondheim Bysykkel-integrasjon',
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
                'max' => 1,
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
                    'value' => 'acf/poi-bysykkel',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
    ) );

    // ============================================================
    // POI Hyre Block - Field Group
    // ============================================================
    acf_add_local_field_group( array(
        'key' => 'group_poi_hyre_block',
        'title' => 'Hyre POI Settings',
        'fields' => array(
            array(
                'key' => 'field_poi_hyre_item',
                'label' => 'Velg Hyre-stasjon',
                'name' => 'poi_item',
                'type' => 'relationship',
                'instructions' => 'Velg én POI som har Hyre bildeling-integrasjon',
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
                'max' => 1,
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
                    'value' => 'acf/poi-hyre',
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
