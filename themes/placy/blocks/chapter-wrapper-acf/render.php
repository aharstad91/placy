<?php
/**
 * Chapter Wrapper ACF Block Template
 *
 * Renders chapter section with NarrativeSection design.
 * Each block instance has its own field values.
 *
 * @package Placy
 * @since 1.0.0
 * 
 * @param array $block The block settings and attributes.
 * @param string $content The block inner HTML (empty).
 * @param bool $is_preview True during backend preview render.
 * @param int $post_id The post ID the block is rendering on.
 * @param array $context The context provided to the block by the post or its parent block.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================================
// PROJECT ORIGIN: Set project coordinates globally BEFORE rendering theme-story
// This ensures travel time calculations use correct origin
// ============================================================================
global $post;
$project_lat = 63.4305; // Default fallback (Trondheim center)
$project_lng = 10.3951;
$project_id_for_cache = null;

if ( $post ) {
    // For project posts, use current post ID
    if ( get_post_type( $post->ID ) === 'project' ) {
        $project_id_for_cache = $post->ID;
    } else {
        // For theme-story, get parent project
        $project_obj = get_field( 'project', $post->ID );
        $project_id_for_cache = is_object( $project_obj ) ? $project_obj->ID : ( is_numeric( $project_obj ) ? $project_obj : null );
        
        // Also check post parent
        if ( ! $project_id_for_cache ) {
            $parent_id = wp_get_post_parent_id( $post->ID );
            if ( $parent_id && get_post_type( $parent_id ) === 'project' ) {
                $project_id_for_cache = $parent_id;
            }
        }
    }
    
    if ( $project_id_for_cache ) {
        // Try 'start_latitude/longitude' first (current field names)
        $lat = get_field( 'start_latitude', $project_id_for_cache );
        $lng = get_field( 'start_longitude', $project_id_for_cache );
        
        // Fallback to 'project_latitude/longitude'
        if ( ! $lat ) {
            $lat = get_field( 'project_latitude', $project_id_for_cache );
            $lng = get_field( 'project_longitude', $project_id_for_cache );
        }
        
        if ( $lat && $lng ) {
            $project_lat = (float) $lat;
            $project_lng = (float) $lng;
        }
    }
}

// Set global origin for travel time calculations (used by POI blocks inside theme-story)
$GLOBALS['placy_project_origin_data'] = array(
    'lat' => $project_lat,
    'lng' => $project_lng,
    'project_id' => $project_id_for_cache,
);
$GLOBALS['placy_project_origin'] = array( 'lat' => $project_lat, 'lng' => $project_lng );

// ============================================================================

// Get block fields for Front Section (always from block)
$category_name       = get_field( 'chapter_category_name' );
$category_icon       = get_field( 'chapter_icon' ) ?: 'food';
$front_title         = get_field( 'chapter_front_title' );
$front_ingress       = get_field( 'chapter_front_ingress' );
$highlighted_points  = get_field( 'chapter_highlighted_points' ) ?: array();

// Get Theme Story for Mega-modal content
$theme_story_id      = get_field( 'chapter_theme_story' );
$theme_story_content = '';

// Fallback POI list from block (if no theme story or for override)
$all_points_block    = get_field( 'chapter_all_points' ) ?: array();

// Settings from block
$chapter_id          = get_field( 'chapter_id' ) ?: 'chapter-' . substr( $block['id'], 0, 8 );
$nav_label           = get_field( 'chapter_nav_label' ) ?: $front_title;
$show_cta_button     = get_field( 'chapter_show_cta_button' );
$cta_button_text     = get_field( 'chapter_cta_button_text' ) ?: 'See all places in this category';
$default_travel_mode = get_field( 'chapter_default_travel_mode' ) ?: 'walking';
$default_time_budget = get_field( 'chapter_default_time_budget' ) ?: '10';

// Initialize mega-modal specific vars
$all_points          = array();
$map_zoom            = 13;

// Get Mega-modal data from Theme Story if selected
if ( $theme_story_id ) {
    $theme_story_post = get_post( $theme_story_id );
    
    if ( $theme_story_post ) {
        // Get Gutenberg content from theme-story
        // Save current global post and switch to theme-story context
        global $post;
        $saved_post = $post;
        $post = $theme_story_post;
        setup_postdata( $post );
        
        // Parse blocks first, then render each one with output buffering
        $parsed_blocks = parse_blocks( $theme_story_post->post_content );
        $theme_story_parts = array();
        
        foreach ( $parsed_blocks as $parsed_block ) {
            // Skip empty/null blocks (these are just whitespace)
            if ( empty( $parsed_block['blockName'] ) ) {
                continue;
            }
            
            // Use output buffering to ensure we capture all output
            ob_start();
            $rendered = render_block( $parsed_block );
            $buffered = ob_get_clean();
            
            // Combine returned and buffered output, trimmed
            $block_output = trim( $rendered . $buffered );
            if ( ! empty( $block_output ) ) {
                $theme_story_parts[] = $block_output;
            }
        }
        
        // Join all block outputs together (no extra whitespace between them)
        $theme_story_content = implode( "\n", $theme_story_parts );
        
        // Restore original post context
        $post = $saved_post;
        if ( $post ) {
            setup_postdata( $post );
        } else {
            wp_reset_postdata();
        }
        
        // POI Relations from theme-story
        $all_points = get_field( 'all_locations', $theme_story_id ) ?: array();
        
        // Convert to IDs if objects were returned
        if ( ! empty( $all_points ) && is_object( $all_points[0] ) ) {
            $all_points = wp_list_pluck( $all_points, 'ID' );
        }
        
        // Travel settings from theme-story (map coordinates come from project)
        $map_zoom            = get_field( 'map_zoom', $theme_story_id ) ?: 13;
        $default_travel_mode = get_field( 'travel_mode', $theme_story_id ) ?: $default_travel_mode;
        $default_time_budget = get_field( 'time_budget', $theme_story_id ) ?: $default_time_budget;
        
        // Use theme-story slug for chapter ID if not set
        if ( ! get_field( 'chapter_id' ) ) {
            $chapter_id = 'chapter-' . $theme_story_post->post_name;
        }
    }
}

// Use block's all_points as fallback if theme story has none
if ( empty( $all_points ) && ! empty( $all_points_block ) ) {
    $all_points = $all_points_block;
}

// Block classes
$block_classes = array( 'chapter-wrapper-acf', 'narrative-section' );
if ( ! empty( $block['className'] ) ) {
    $block_classes[] = $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $block_classes[] = 'align' . $block['align'];
}

// Get highlighted points data
$highlighted_points_data = placy_get_chapter_points( $highlighted_points );
$all_points_data         = placy_get_chapter_points( $all_points );
// Use all_points count if available, otherwise fall back to highlighted_points count
$total_locations         = count( $all_points_data ) > 0 ? count( $all_points_data ) : count( $highlighted_points_data );

// Get project coordinates for map center (always from project - single source of truth)
$project_lat = 63.4305; // Default fallback
$project_lng = 10.3951;

global $post;
if ( $post ) {
    // For project posts, use current post ID
    $project_id = null;
    
    if ( get_post_type( $post->ID ) === 'project' ) {
        $project_id = $post->ID;
    } else {
        // For theme-story, get parent project
        $project_obj = get_field( 'project', $post->ID );
        $project_id = is_object( $project_obj ) ? $project_obj->ID : ( is_numeric( $project_obj ) ? $project_obj : null );
    }
    
    if ( $project_id ) {
        // Try 'start_latitude/longitude' first (current field names)
        $lat = get_field( 'start_latitude', $project_id );
        $lng = get_field( 'start_longitude', $project_id );
        
        // Fallback to 'project_latitude/longitude'
        if ( ! $lat ) {
            $lat = get_field( 'project_latitude', $project_id );
        }
        if ( ! $lng ) {
            $lng = get_field( 'project_longitude', $project_id );
        }
        
        if ( $lat ) {
            $project_lat = (float) $lat;
        }
        if ( $lng ) {
            $project_lng = (float) $lng;
        }
    }
}

// Icon SVG
$icon_svg = function_exists( 'placy_get_chapter_icon_svg' ) ? placy_get_chapter_icon_svg( $category_icon ) : '';

// Preview mode message (only show if no data configured)
$has_content = ! empty( $front_title ) || ! empty( $category_name ) || ! empty( $theme_story_id );
if ( $is_preview && ! $has_content ) : ?>
    <div class="chapter-wrapper-acf-preview p-8 bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg text-center">
        <div class="text-gray-500 mb-2">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Tema Story Kapittel</h3>
        <p class="text-gray-500 mb-4">Konfigurer dette kapittelet:</p>
        <div class="text-sm text-gray-600">
            <p class="mb-2"><strong>Front Section:</strong> Fyll inn tittel, ingress og fremhevede steder</p>
            <p><strong>Mega-modal:</strong> Velg en Theme Story for rikt Gutenberg-innhold</p>
        </div>
    </div>
<?php
    return;
endif;
?>

<section 
    id="<?php echo esc_attr( $chapter_id ); ?>" 
    class="<?php echo esc_attr( implode( ' ', $block_classes ) ); ?>"
    data-chapter-id="<?php echo esc_attr( $chapter_id ); ?>"
    data-nav-label="<?php echo esc_attr( $nav_label ); ?>"
    data-travel-mode="<?php echo esc_attr( $default_travel_mode ); ?>"
    data-time-budget="<?php echo esc_attr( $default_time_budget ); ?>"
    data-project-lat="<?php echo esc_attr( $project_lat ); ?>"
    data-project-lng="<?php echo esc_attr( $project_lng ); ?>"
>
    <!-- NarrativeSection Header -->
    <div class="narrative-header max-w-4xl mx-auto px-6 py-12">
        <?php if ( $category_name ) : ?>
            <div class="category-badge flex items-center gap-2 text-sm font-medium text-gray-600 uppercase tracking-wider mb-4">
                <?php if ( $icon_svg ) : ?>
                    <span class="category-icon text-gray-500"><?php echo $icon_svg; ?></span>
                <?php endif; ?>
                <span><?php echo esc_html( $category_name ); ?></span>
            </div>
        <?php endif; ?>

        <?php if ( $front_title ) : ?>
            <h2 class="narrative-title text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html( $front_title ); ?>
            </h2>
        <?php endif; ?>

        <?php if ( $front_ingress ) : ?>
            <p class="narrative-ingress text-lg text-gray-600 max-w-2xl">
                <?php echo esc_html( $front_ingress ); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- POI Cards Grid (4-column layout with CTA as 4th card) -->
    <?php if ( ! empty( $highlighted_points_data ) || $show_cta_button ) : ?>
        <div class="poi-cards-grid max-w-6xl mx-auto px-6 pb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php 
                // Show up to 3 POI cards (leave room for CTA)
                $display_points = array_slice( $highlighted_points_data, 0, 3 );
                foreach ( $display_points as $index => $point ) : 
                    // Generate a simulated walk time (in production, this would come from actual calculation)
                    $walk_time = rand( 1, 12 );
                    $category_label = strtoupper( $point['category'] ?: 'PLACE' );
                ?>
                    <div class="poi-card bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow cursor-pointer relative"
                         data-poi-id="<?php echo esc_attr( $point['id'] ); ?>"
                         data-poi-lat="<?php echo esc_attr( $point['lat'] ); ?>"
                         data-poi-lng="<?php echo esc_attr( $point['lng'] ); ?>"
                         onclick="openChapterMegaModal('<?php echo esc_attr( $chapter_id ); ?>', <?php echo esc_js( $point['id'] ); ?>)">
                        
                        <!-- Walking time badge (top-right) -->
                        <div class="poi-walk-badge absolute top-3 right-3 bg-white/90 backdrop-blur-sm rounded-full px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm z-10 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M13 6a2 2 0 100-4 2 2 0 000 4zm-1 2L8 14l-2-1m6-5l4 4m-4-4l-4 4m0 6l2-2m8-4l-4 4-2-2"/>
                            </svg>
                            <span class="poi-travel-time" data-poi-id="<?php echo esc_attr( $point['id'] ); ?>"><?php echo esc_html( $walk_time ); ?></span> min walk
                        </div>
                        
                        <!-- Image area -->
                        <?php if ( ! empty( $point['image'] ) ) : ?>
                            <div class="poi-card-image aspect-[4/3] bg-gray-100 overflow-hidden">
                                <img src="<?php echo esc_url( $point['image'] ); ?>" 
                                     alt="<?php echo esc_attr( $point['title'] ); ?>"
                                     class="w-full h-full object-cover"
                                     loading="lazy">
                            </div>
                        <?php else : ?>
                            <div class="poi-card-image aspect-[4/3] bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Card content -->
                        <div class="poi-card-content p-3">
                            <!-- Category + Rating row -->
                            <div class="poi-card-meta flex items-center justify-between mb-2 text-xs">
                                <span class="poi-category uppercase tracking-wider font-medium text-gray-500">
                                    <?php echo esc_html( $category_label ); ?>
                                </span>
                                <?php if ( ! empty( $point['rating'] ) ) : ?>
                                    <span class="poi-rating flex items-center gap-1 text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <?php echo esc_html( number_format( (float) $point['rating'], 1 ) ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Title -->
                            <h4 class="poi-card-title font-semibold text-gray-900 text-sm mb-1 line-clamp-1">
                                <?php echo esc_html( $point['title'] ); ?>
                            </h4>
                            
                            <!-- Description -->
                            <?php if ( ! empty( $point['description'] ) ) : ?>
                                <p class="poi-card-description text-xs text-gray-500 line-clamp-2">
                                    <?php echo esc_html( wp_trim_words( $point['description'], 12 ) ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- CTA Card (4th position in grid) -->
                <?php if ( $show_cta_button ) : ?>
                    <button type="button" 
                            class="mega-modal-trigger bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border border-gray-200 p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:shadow-md transition-all group min-h-[200px]"
                            data-chapter-id="<?php echo esc_attr( $chapter_id ); ?>"
                            onclick="openChapterMegaModal('<?php echo esc_attr( $chapter_id ); ?>')">
                        
                        <!-- Icon circles decoration -->
                        <div class="cta-icons flex items-center justify-center -space-x-2 mb-4">
                            <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center border-2 border-white shadow-sm">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center border-2 border-white shadow-sm">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>
                                </svg>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center border-2 border-white shadow-sm">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- CTA text -->
                        <h4 class="cta-title font-semibold text-gray-900 text-sm mb-1 group-hover:text-teal-700 transition-colors">
                            <?php echo esc_html( $cta_button_text ); ?>
                        </h4>
                        <p class="cta-subtitle text-xs text-teal-600 font-medium">
                            Total <?php echo esc_html( $total_locations ); ?> locations
                        </p>
                        
                        <!-- Arrow indicator -->
                        <div class="cta-arrow mt-3 text-gray-400 group-hover:text-teal-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </div>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Chapter Modal Data (for JavaScript) -->
    <script type="application/json" class="pl-chapter-modal-data">
    <?php
        // Property names match what JavaScript expects
        $modal_data = array(
            'chapterId'          => $chapter_id,
            'categoryName'       => $category_name,
            'title'              => $front_title,         // JS uses data.title
            'ingress'            => $front_ingress,       // JS uses data.ingress
            'defaultTravelMode'  => $default_travel_mode,
            'defaultTimeBudget'  => (int) $default_time_budget,
            'originLat'          => $project_lat,         // JS uses data.originLat
            'originLng'          => $project_lng,         // JS uses data.originLng
            'mapZoom'            => (int) $map_zoom,
        );
        
        // Theme story content for the mega-modal
        // Since we're not rendering it on the page, we include it in JSON for the drawer
        if ( ! empty( $theme_story_content ) ) {
            $modal_data['useThemeStory']   = true;
            $modal_data['themeStoryId']    = $theme_story_id;
            $modal_data['themeStoryHtml']  = $theme_story_content;
        } else {
            // Fallback: use the old allPoints array
            $modal_points = array_merge( $highlighted_points_data, $all_points_data );
            // Remove duplicates by ID
            $unique_points = [];
            $seen_ids = [];
            foreach ( $modal_points as $point ) {
                if ( ! in_array( $point['id'], $seen_ids ) ) {
                    $unique_points[] = $point;
                    $seen_ids[] = $point['id'];
                }
            }
            $modal_data['allPoints']          = $unique_points;
            $modal_data['highlightedPointIds'] = wp_list_pluck( $highlighted_points_data, 'id' );
            $modal_data['totalCount']         = $total_locations;
            $modal_data['useThemeStory']      = false;
        }
        
        echo wp_json_encode( $modal_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    ?>
    </script>
</section>
