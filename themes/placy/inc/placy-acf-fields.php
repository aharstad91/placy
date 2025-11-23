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
