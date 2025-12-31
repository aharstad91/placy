<?php
/**
 * Test script for Neighborhood Story
 */
require_once dirname(__FILE__, 4) . '/wp-load.php';

// Find the project
$args = array(
    'post_type' => 'project',
    'name' => 'ferjemannsveien-10',
    'posts_per_page' => 1
);
$q = new WP_Query($args);

if ($q->have_posts()) {
    $post = $q->posts[0];
    echo "Project ID: " . $post->ID . "\n";
    echo "Title: " . $post->post_title . "\n\n";
    
    // Check story_chapters field
    $chapters = get_field('story_chapters', $post->ID);
    echo "story_chapters: " . (is_array($chapters) ? count($chapters) . " chapters" : "NOT SET or empty") . "\n";
    
    // Check if function exists
    echo "placy_project_has_neighborhood_story exists: " . (function_exists('placy_project_has_neighborhood_story') ? 'YES' : 'NO') . "\n";
    
    if (function_exists('placy_project_has_neighborhood_story')) {
        echo "Has neighborhood story: " . (placy_project_has_neighborhood_story($post->ID) ? 'YES' : 'NO') . "\n";
    }
    
    // List all ACF field groups for this project
    echo "\nAll ACF fields for this project:\n";
    $fields = get_fields($post->ID);
    if ($fields) {
        foreach ($fields as $key => $value) {
            $type = gettype($value);
            if ($type === 'array') {
                echo "  $key: [array with " . count($value) . " items]\n";
            } elseif (is_object($value)) {
                echo "  $key: [object]\n";
            } else {
                echo "  $key: $value\n";
            }
        }
    }
} else {
    echo "Project not found!\n";
    
    // List all projects
    echo "\nAvailable projects:\n";
    $all = get_posts(array('post_type' => 'project', 'posts_per_page' => 10));
    foreach ($all as $p) {
        echo "  - " . $p->post_name . " (ID: " . $p->ID . ")\n";
    }
}
