<?php
/**
 * POI Gallery Block Template
 * 
 * Displays multiple POIs in a grid layout
 */

// Get the selected POIs
$pois = get_field('poi_items');

if ( ! $pois || empty( $pois ) ) {
    echo '<p style="padding: 20px; background: #f0f0f0; text-align: center;">Please select POIs in block settings</p>';
    return;
}
?>

<div class="poi-gallery grid grid-cols-2 gap-6 mb-8">
    <?php foreach ( $pois as $poi ) : 
        $poi_id = $poi->ID;
        $title = get_the_title( $poi_id );
        $content = apply_filters( 'the_content', get_post_field( 'post_content', $poi_id ) );
        $featured_image = get_the_post_thumbnail_url( $poi_id, 'medium_large' );
        
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
    
    <article class="poi-list-item poi-gallery-item bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow" 
             data-poi-id="<?php echo esc_attr( $poi_id ); ?>"
             data-poi-title="<?php echo esc_attr( $title ); ?>"
             <?php if ( $coords ) : ?>
                data-poi-coords="<?php echo $coords; ?>"
             <?php endif; ?>
             <?php if ( $featured_image ) : ?>
                data-poi-image="<?php echo esc_url( $featured_image ); ?>"
             <?php endif; ?>>
        
        <?php if ( $featured_image ) : ?>
            <div class="poi-gallery-image">
                <img src="<?php echo esc_url( $featured_image ); ?>" 
                     alt="<?php echo esc_attr( $title ); ?>"
                     class="w-full h-48 object-cover">
            </div>
        <?php endif; ?>
        
        <div class="poi-gallery-content p-6">
            <h3 class="text-xl font-bold mb-3 text-gray-900">
                <?php echo esc_html( $title ); ?>
            </h3>
            
            <?php if ( $walking_time ) : ?>
                <div class="flex items-center gap-2 mb-3 text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-sm font-medium"><?php echo esc_html( $walking_time ); ?> min</span>
                </div>
            <?php endif; ?>
            
            <div class="poi-gallery-text prose prose-sm max-w-none mb-4 line-clamp-3">
                <?php echo wp_kses_post( $content ); ?>
            </div>
            
            <div class="poi-button-container flex items-center gap-2 flex-wrap">
                <button onclick="showPOIOnMap(this)" 
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Se på kart
                </button>
            </div>
        </div>
    </article>
    
    <?php endforeach; ?>
</div>
