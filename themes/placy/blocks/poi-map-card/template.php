<?php
/**
 * POI Map Card Block Template
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get block fields
$block_title = get_field('map_title') ?: 'POI Kart';
$selected_pois = get_field('selected_pois');
$block_id = 'poi-map-' . $block['id'];

// Generate unique ID for this block instance
$unique_id = uniqid('poi-map-');

// Prepare POI data
$poi_data = array();
if ( $selected_pois ) {
    foreach ( $selected_pois as $poi_post ) {
        $poi_id = $poi_post->ID;
        $lat = get_field('latitude', $poi_id);
        $lng = get_field('longitude', $poi_id);
        $thumbnail = get_the_post_thumbnail_url($poi_id, 'medium');
        
        if ( $lat && $lng ) {
            $poi_data[] = array(
                'id' => $poi_id,
                'slug' => $poi_post->post_name,
                'title' => get_the_title($poi_id),
                'description' => get_the_excerpt($poi_id) ?: get_the_content(null, false, $poi_id),
                'latitude' => floatval($lat),
                'longitude' => floatval($lng),
                'thumbnail' => $thumbnail,
                'clickable' => true
            );
        }
    }
}

// Get first 3 POIs for preview
$preview_pois = array_slice($poi_data, 0, 3);

// Classes for the block
$className = 'poi-map-card-block';
if ( ! empty( $block['className'] ) ) {
    $className .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $className .= ' align' . $block['align'];
}
?>

<div id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr($className); ?>" data-block-id="<?php echo esc_attr($unique_id); ?>">
    
    <!-- Map Preview Card -->
    <div class="poi-map-preview bg-gray-100 rounded-lg relative mb-12 transition-all hover:bg-gray-200 cursor-pointer border border-gray-300" 
         style="height: calc(var(--viewport-height, 100vh) * 0.4);" 
         onclick="openPOIMapModal('<?php echo esc_js($unique_id); ?>')">
        
        <!-- Top gradient overlay -->
        <div class="absolute top-0 left-0 right-0 h-20 bg-gradient-to-b from-white to-transparent rounded-t-lg pointer-events-none"></div>
        
        <!-- Bottom gradient overlay -->
        <div class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-white to-transparent rounded-b-lg pointer-events-none"></div>
        
        <!-- Title at top left -->
        <div class="absolute top-4 left-4 z-10">
            <div class="text-gray-600 text-lg font-medium"><?php echo esc_html($block_title); ?></div>
            <div class="text-gray-500 text-sm mt-1">Klikk for å aktivere kart</div>
        </div>
        
        <!-- Modal indicator at top right -->
        <div class="absolute top-4 right-4 z-10">
            <div class="bg-white rounded-full p-2 shadow-sm">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M8 3v3a2 2 0 01-2 2H3m18 0h-3a2 2 0 01-2-2V3m0 18v-3a2 2 0 012-2h3M3 16h3a2 2 0 012 2v3"/>
                </svg>
            </div>
        </div>
        
        <!-- Fullscreen hint -->
        <div class="absolute bottom-16 left-4 z-10">
            <div class="text-gray-500 text-xs flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M15 3h6v6m-6 0l6-6M9 21H3v-6m6 0l-6 6"/>
                </svg>
                Fullskjerm
            </div>
        </div>
        
        <!-- POI Tags - Show first 3 POIs -->
        <?php if ( ! empty( $preview_pois ) ) : ?>
        <div class="absolute bottom-4 left-4 right-4 z-10">
            <div class="poi-tags-container flex gap-2 overflow-x-auto pb-2">
                <?php foreach ( $preview_pois as $poi ) : ?>
                <button class="poi-tag flex-shrink-0 bg-white/90 backdrop-blur-sm rounded-full px-4 py-2 flex items-center gap-2 shadow-sm hover:bg-white transition-colors" 
                        onclick="event.stopPropagation(); openPOIMapModal('<?php echo esc_js($unique_id); ?>', '<?php echo esc_js($poi['slug']); ?>')">
                    <span class="poi-tag-icon text-base">📍</span>
                    <span class="poi-tag-text text-sm font-medium text-gray-800"><?php echo esc_html($poi['title']); ?></span>
                </button>
                <?php endforeach; ?>
                
                <?php if ( count($poi_data) > 3 ) : ?>
                <button class="poi-tag flex-shrink-0 bg-gray-600/90 backdrop-blur-sm rounded-full px-4 py-2 flex items-center gap-2 shadow-sm hover:bg-gray-700 transition-colors" 
                        onclick="event.stopPropagation(); openPOIMapModal('<?php echo esc_js($unique_id); ?>')">
                    <span class="poi-tag-text text-sm font-medium text-white">+<?php echo count($poi_data) - 3; ?> mer</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Hidden data for JavaScript -->
    <script type="application/json" class="poi-map-data" data-block-id="<?php echo esc_attr($unique_id); ?>">
        <?php echo wp_json_encode(array(
            'blockId' => $unique_id,
            'title' => $block_title,
            'pois' => $poi_data
        )); ?>
    </script>
    
</div>
