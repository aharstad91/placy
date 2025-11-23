<?php
/**
 * Placy Cron Jobs
 * Scheduled tasks for refreshing Google Point data
 *
 * @package Placy
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schedule daily refresh for featured points
 */
add_action( 'wp', 'placy_schedule_daily_refresh' );
function placy_schedule_daily_refresh() {
    if ( ! wp_next_scheduled( 'placy_daily_refresh' ) ) {
        wp_schedule_event(
            strtotime( 'tomorrow 02:00:00' ), // 2 AM next day
            'daily',
            'placy_daily_refresh'
        );
    }
}

/**
 * Daily refresh callback - refresh featured Google Points
 */
add_action( 'placy_daily_refresh', 'placy_daily_refresh_callback' );
function placy_daily_refresh_callback() {
    // Get all featured Google Points
    $featured_points = get_posts( array(
        'post_type' => 'placy_google_point',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'featured',
                'value' => '1',
                'compare' => '=',
            ),
        ),
    ) );
    
    $refreshed_count = 0;
    $error_count = 0;
    
    foreach ( $featured_points as $point ) {
        if ( placy_needs_refresh( $point->ID ) ) {
            $result = placy_refresh_google_point( $point->ID );
            
            if ( $result ) {
                $refreshed_count++;
            } else {
                $error_count++;
            }
            
            // Throttle to avoid rate limiting
            sleep( 1 );
        }
    }
    
    // Log results
    error_log( sprintf(
        'Placy Daily Refresh: %d featured points refreshed, %d errors',
        $refreshed_count,
        $error_count
    ) );
}

/**
 * Schedule weekly refresh for regular points
 */
add_action( 'wp', 'placy_schedule_weekly_refresh' );
function placy_schedule_weekly_refresh() {
    if ( ! wp_next_scheduled( 'placy_weekly_refresh' ) ) {
        wp_schedule_event(
            strtotime( 'next Sunday 03:00:00' ), // 3 AM on Sunday
            'weekly',
            'placy_weekly_refresh'
        );
    }
}

/**
 * Weekly refresh callback - refresh regular Google Points
 */
add_action( 'placy_weekly_refresh', 'placy_weekly_refresh_callback' );
function placy_weekly_refresh_callback() {
    // Get all non-featured Google Points
    $regular_points = get_posts( array(
        'post_type' => 'placy_google_point',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'featured',
                'value' => '0',
                'compare' => '=',
            ),
            array(
                'key' => 'featured',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ) );
    
    $refreshed_count = 0;
    $error_count = 0;
    $skipped_count = 0;
    
    foreach ( $regular_points as $point ) {
        if ( placy_needs_refresh( $point->ID ) ) {
            $result = placy_refresh_google_point( $point->ID );
            
            if ( $result ) {
                $refreshed_count++;
            } else {
                $error_count++;
            }
            
            // Throttle to avoid rate limiting
            sleep( 2 );
        } else {
            $skipped_count++;
        }
    }
    
    // Log results
    error_log( sprintf(
        'Placy Weekly Refresh: %d regular points refreshed, %d errors, %d skipped',
        $refreshed_count,
        $error_count,
        $skipped_count
    ) );
}

/**
 * Clear scheduled cron jobs on deactivation (for testing)
 */
register_deactivation_hook( __FILE__, 'placy_clear_scheduled_crons' );
function placy_clear_scheduled_crons() {
    $timestamp_daily = wp_next_scheduled( 'placy_daily_refresh' );
    if ( $timestamp_daily ) {
        wp_unschedule_event( $timestamp_daily, 'placy_daily_refresh' );
    }
    
    $timestamp_weekly = wp_next_scheduled( 'placy_weekly_refresh' );
    if ( $timestamp_weekly ) {
        wp_unschedule_event( $timestamp_weekly, 'placy_weekly_refresh' );
    }
}

/**
 * Add custom cron schedules if needed
 */
add_filter( 'cron_schedules', 'placy_custom_cron_schedules' );
function placy_custom_cron_schedules( $schedules ) {
    // Add custom schedule if needed (e.g., twice daily)
    if ( ! isset( $schedules['twice_daily'] ) ) {
        $schedules['twice_daily'] = array(
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __( 'Twice Daily', 'placy' ),
        );
    }
    
    return $schedules;
}
