<?php
/**
 * POI Highlight Block Template
 * 
 * Displays a single POI with prominent hero-style layout
 */

// Get the selected POI
$poi = get_field('poi_item');

if ( ! $poi ) {
    echo '<p style="padding: 20px; background: #f0f0f0; text-align: center;">Please select a POI in block settings</p>';
    return;
}

// Get POI data
$poi_id = $poi->ID;
$title = get_the_title( $poi_id );
$content = apply_filters( 'the_content', get_post_field( 'post_content', $poi_id ) );
$featured_image = get_the_post_thumbnail_url( $poi_id, 'large' );
$secondary_image = get_field( 'secondary_image', $poi_id );

// Get coordinates for map syncing
$lat = get_field( 'latitude', $poi_id );
$lng = get_field( 'longitude', $poi_id );
$coords = '';

if ( $lat && $lng ) {
    $coords = sprintf( '[%s,%s]', esc_attr( $lat ), esc_attr( $lng ) );
}

// Get walking time if available
$walking_time = get_post_meta( $poi_id, 'walking_time', true );
?>

<article class="poi-list-item poi-highlight mb-8" 
         data-poi-id="<?php echo esc_attr( $poi_id ); ?>"
         data-poi-title="<?php echo esc_attr( $title ); ?>"
         <?php if ( $coords ) : ?>
            data-poi-coords="<?php echo $coords; ?>"
         <?php endif; ?>
         <?php if ( $featured_image ) : ?>
            data-poi-image="<?php echo esc_url( $featured_image ); ?>"
         <?php endif; ?>>
    
    <?php if ( $featured_image && $secondary_image ) : ?>
        <!-- Two images side by side -->
        <div class="poi-highlight-images mb-6 grid grid-cols-2 gap-4">
            <div class="rounded-lg overflow-hidden">
                <img src="<?php echo esc_url( $featured_image ); ?>" 
                     alt="<?php echo esc_attr( $title ); ?>"
                     class="w-full h-64 object-cover">
            </div>
            <div class="rounded-lg overflow-hidden">
                <img src="<?php echo esc_url( $secondary_image ); ?>" 
                     alt="<?php echo esc_attr( $title ); ?>"
                     class="w-full h-64 object-cover">
            </div>
        </div>
    <?php elseif ( $featured_image ) : ?>
        <!-- Single featured image (full width) -->
        <div class="poi-highlight-image mb-6 rounded-lg overflow-hidden">
            <img src="<?php echo esc_url( $featured_image ); ?>" 
                 alt="<?php echo esc_attr( $title ); ?>"
                 class="w-full h-64 object-cover">
        </div>
    <?php endif; ?>
    
    <div class="poi-highlight-content rounded-lg shadow-sm">
        <h2 class="text-3xl font-bold mb-4 text-gray-900">
            <?php echo esc_html( $title ); ?>
        </h2>
        
        <?php if ( $walking_time ) : ?>
            <div class="flex items-center gap-2 mb-4 text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-sm font-medium"><?php echo esc_html( $walking_time ); ?> min gange</span>
            </div>
        <?php endif; ?>
        
        <div class="poi-highlight-text prose prose-lg max-w-none">
            <?php echo wp_kses_post( $content ); ?>
        </div>
        
        <div class="poi-button-container mt-6 flex items-center gap-2">
            <button onclick="showPOIOnMap(this)" 
                    class="inline-flex items-center gap-2 px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Se på kart
            </button>
        </div>
    </div>
</article>
