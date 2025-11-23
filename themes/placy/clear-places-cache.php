<?php
/**
 * Clear Google Places Cache
 * Run this file once to clear all cached place data
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Check if user is logged in and is admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Unauthorized - you must be logged in as admin');
}

global $wpdb;

// Delete all place cache transients
$deleted = $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_placy_place_%'");
$deleted += $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_placy_place_%'");

echo "✓ Cleared $deleted cache entries\n";
echo "\n";
echo "Now refresh your page to see the images!\n";
echo "\n";
echo "You can delete this file after use: " . __FILE__;
