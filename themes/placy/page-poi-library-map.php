<?php
/**
 * Template Name: POI Library Map
 *
 * Full-screen map showing all POIs from the library.
 * Accessible at /poi-library-map/ (requires creating a page with this template)
 *
 * @package Placy
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Require login for this page
if ( ! is_user_logged_in() ) {
    auth_redirect();
}

// Get Mapbox token
$mapbox_token = placy_get_mapbox_token();

// Gather all Native POIs with coordinates
$native_pois = get_posts( array(
    'post_type'      => 'placy_native_point',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
) );

// Gather all Google POIs with coordinates
$google_pois = get_posts( array(
    'post_type'      => 'placy_google_point',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
) );

// Get all categories with their ACF fields
$categories = get_terms( array(
    'taxonomy'   => 'placy_categories',
    'hide_empty' => false,
) );

$categories_data = array();
foreach ( $categories as $cat ) {
    $categories_data[] = array(
        'slug'  => $cat->slug,
        'name'  => $cat->name,
        'icon'  => get_field( 'category_icon', 'placy_categories_' . $cat->term_id ) ?: 'fa-map-marker-alt',
        'color' => get_field( 'category_color', 'placy_categories_' . $cat->term_id ) ?: '#3B82F6',
        'count' => $cat->count,
    );
}

// Build POI data array
$pois_data = array();

// Process Native POIs
foreach ( $native_pois as $poi ) {
    $lat = get_post_meta( $poi->ID, 'coordinates_latitude', true );
    $lng = get_post_meta( $poi->ID, 'coordinates_longitude', true );

    if ( empty( $lat ) || empty( $lng ) ) {
        continue;
    }

    $poi_categories = wp_get_post_terms( $poi->ID, 'placy_categories', array( 'fields' => 'slugs' ) );
    $category_slug = ! empty( $poi_categories ) ? $poi_categories[0] : '';

    // Find category data
    $cat_data = null;
    foreach ( $categories_data as $cat ) {
        if ( $cat['slug'] === $category_slug ) {
            $cat_data = $cat;
            break;
        }
    }

    $pois_data[] = array(
        'id'            => $poi->ID,
        'title'         => get_the_title( $poi->ID ),
        'lat'           => (float) $lat,
        'lng'           => (float) $lng,
        'type'          => 'native',
        'category'      => $category_slug,
        'categoryName'  => $cat_data ? $cat_data['name'] : '',
        'categoryIcon'  => $cat_data ? $cat_data['icon'] : 'fa-map-marker-alt',
        'categoryColor' => $cat_data ? $cat_data['color'] : '#3B82F6',
        'editUrl'       => admin_url( 'post.php?post=' . $poi->ID . '&action=edit' ),
    );
}

// Process Google POIs
foreach ( $google_pois as $poi ) {
    $cache = get_field( 'nearby_search_cache', $poi->ID );
    $data = json_decode( $cache, true );

    if ( empty( $data['geometry']['location']['lat'] ) || empty( $data['geometry']['location']['lng'] ) ) {
        continue;
    }

    $poi_categories = wp_get_post_terms( $poi->ID, 'placy_categories', array( 'fields' => 'slugs' ) );
    $category_slug = ! empty( $poi_categories ) ? $poi_categories[0] : '';

    // Find category data
    $cat_data = null;
    foreach ( $categories_data as $cat ) {
        if ( $cat['slug'] === $category_slug ) {
            $cat_data = $cat;
            break;
        }
    }

    $pois_data[] = array(
        'id'            => $poi->ID,
        'title'         => get_the_title( $poi->ID ),
        'lat'           => (float) $data['geometry']['location']['lat'],
        'lng'           => (float) $data['geometry']['location']['lng'],
        'type'          => 'google',
        'category'      => $category_slug,
        'categoryName'  => $cat_data ? $cat_data['name'] : '',
        'categoryIcon'  => $cat_data ? $cat_data['icon'] : 'fa-map-marker-alt',
        'categoryColor' => $cat_data ? $cat_data['color'] : '#3B82F6',
        'editUrl'       => admin_url( 'post.php?post=' . $poi->ID . '&action=edit' ),
    );
}

// Calculate map center from POIs
$center_lat = 63.4305;
$center_lng = 10.3951;
if ( ! empty( $pois_data ) ) {
    $sum_lat = 0;
    $sum_lng = 0;
    foreach ( $pois_data as $poi ) {
        $sum_lat += $poi['lat'];
        $sum_lng += $poi['lng'];
    }
    $center_lat = $sum_lat / count( $pois_data );
    $center_lng = $sum_lng / count( $pois_data );
}

// Prepare data for JavaScript
$map_data = array(
    'pois'        => $pois_data,
    'categories'  => $categories_data,
    'center'      => array( $center_lng, $center_lat ),
    'mapboxToken' => $mapbox_token,
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POI Library Map - <?php bloginfo( 'name' ); ?></title>

    <!-- Mapbox GL JS -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f5f5f5;
            color: #1a1a1a;
        }

        /* Header */
        .poi-map-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .poi-map-title {
            font-size: 16px;
            font-weight: 600;
            white-space: nowrap;
        }

        .poi-map-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }

        .poi-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            background: #fff;
            color: #1a1a1a;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .poi-filter-chip:hover {
            border-color: rgba(0, 0, 0, 0.3);
            background: #f5f5f5;
        }

        .poi-filter-chip.active {
            background: var(--chip-color, #3B82F6);
            border-color: var(--chip-color, #3B82F6);
            color: #fff;
        }

        .poi-filter-chip .count {
            opacity: 0.7;
            font-size: 11px;
        }

        .poi-map-search {
            width: 200px;
        }

        .poi-map-search input {
            width: 100%;
            padding: 8px 14px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            background: #fff;
            color: #1a1a1a;
            font-size: 13px;
            outline: none;
        }

        .poi-map-search input::placeholder {
            color: rgba(0, 0, 0, 0.4);
        }

        .poi-map-search input:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        /* Map Container */
        #poi-library-map {
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            bottom: 40px;
        }

        /* Footer / Status Bar */
        .poi-map-status {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: rgba(0, 0, 0, 0.6);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .poi-map-status a {
            color: #3B82F6;
            text-decoration: none;
        }

        .poi-map-status a:hover {
            text-decoration: underline;
        }

        /* Markers - IMPORTANT: Outer container must NOT expand beyond dot size
           Mapbox uses the element dimensions for anchor positioning */
        .poi-library-marker {
            cursor: pointer;
            /* NO position relative here - let inner handle it */
        }

        /* Inner wrapper - contains all visual elements, provides positioning context */
        .poi-library-marker-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .poi-library-marker-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--marker-color, #3B82F6);
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .poi-library-marker-dot i {
            color: #fff;
            font-size: 12px;
        }

        .poi-library-marker:hover .poi-library-marker-dot {
            transform: scale(1.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        /* Label - positioned BELOW the marker, absolutely positioned so it doesn't affect marker dimensions */
        .poi-library-marker-label {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 8px;
            padding: 6px 10px;
            background: #1e1e1e;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            border-radius: 6px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
            z-index: 10;
            pointer-events: none;
        }

        .poi-library-marker:hover .poi-library-marker-label {
            display: block;
        }

        .poi-library-marker-label::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-bottom-color: #1e1e1e;
        }

        /* Popup */
        .poi-library-popup .mapboxgl-popup-content {
            padding: 0;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            min-width: 220px;
            background: #fff;
        }

        .poi-library-popup .mapboxgl-popup-close-button {
            font-size: 20px;
            padding: 6px 10px;
            color: #666;
        }

        .poi-library-popup .mapboxgl-popup-close-button:hover {
            color: #000;
            background: transparent;
        }

        .poi-popup-content {
            padding: 15px;
        }

        .poi-popup-title {
            font-weight: 600;
            font-size: 15px;
            color: #1e1e1e;
            margin-bottom: 6px;
            padding-right: 25px;
        }

        .poi-popup-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }

        .poi-popup-type {
            display: inline-block;
            padding: 2px 8px;
            background: #f0f0f0;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .poi-popup-coords {
            font-size: 11px;
            color: #999;
            font-family: monospace;
            margin-bottom: 12px;
        }

        .poi-popup-edit {
            display: inline-block;
            padding: 8px 14px;
            background: #3B82F6;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: background 0.15s ease;
        }

        .poi-popup-edit:hover {
            background: #2563EB;
            color: #fff;
        }

        /* Responsive */
        @media screen and (max-width: 900px) {
            .poi-map-header {
                flex-wrap: wrap;
                padding: 10px 15px;
            }

            .poi-map-title {
                order: 1;
            }

            .poi-map-search {
                order: 2;
                width: 150px;
            }

            .poi-map-filters {
                order: 3;
                width: 100%;
                justify-content: flex-start;
                margin-top: 8px;
            }

            #poi-library-map {
                top: 110px;
            }
        }
    </style>
</head>
<body>
    <header class="poi-map-header">
        <h1 class="poi-map-title">POI Library</h1>

        <div class="poi-map-filters">
            <button type="button" class="poi-filter-chip active" data-filter="all">
                All <span class="count"><?php echo count( $pois_data ); ?></span>
            </button>
            <?php foreach ( $categories_data as $cat ) : ?>
                <?php if ( $cat['count'] > 0 ) : ?>
                    <button type="button" class="poi-filter-chip" data-filter="<?php echo esc_attr( $cat['slug'] ); ?>" style="--chip-color: <?php echo esc_attr( $cat['color'] ); ?>">
                        <?php echo esc_html( $cat['name'] ); ?> <span class="count"><?php echo esc_html( $cat['count'] ); ?></span>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="poi-map-search">
            <input type="search" id="poi-search" placeholder="Search POIs..." />
        </div>
    </header>

    <div id="poi-library-map"></div>

    <footer class="poi-map-status">
        <span id="poi-count">Showing <?php echo count( $pois_data ); ?> POIs</span>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=placy-settings' ) ); ?>">Back to Admin</a>
    </footer>

    <script type="application/json" id="poi-library-data">
    <?php echo wp_json_encode( $map_data ); ?>
    </script>

    <script>
    (function() {
        'use strict';

        let mapInstance = null;
        let markers = [];
        let poisData = [];
        let categoriesData = [];
        let currentFilter = 'all';
        let currentSearch = '';

        // Parse data
        const dataElement = document.getElementById('poi-library-data');
        if (dataElement) {
            try {
                const data = JSON.parse(dataElement.textContent);
                poisData = data.pois || [];
                categoriesData = data.categories || [];

                console.log('Loaded POI data:', {
                    total: poisData.length,
                    center: data.center,
                    sample: poisData.slice(0, 3)
                });

                // Set Mapbox token
                if (data.mapboxToken) {
                    mapboxgl.accessToken = data.mapboxToken;
                }

                initMap(data.center);
            } catch (e) {
                console.error('Failed to parse data:', e);
            }
        }

        function initMap(center) {
            const container = document.getElementById('poi-library-map');
            if (!container || typeof mapboxgl === 'undefined') return;

            mapInstance = new mapboxgl.Map({
                container: container,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: center || [10.3951, 63.4305],
                zoom: 13
            });

            mapInstance.addControl(new mapboxgl.NavigationControl(), 'bottom-right');

            mapInstance.on('load', function() {
                addMarkers();
                fitMapToBounds();
            });
        }

        function addMarkers() {
            if (!mapInstance) return;

            markers.forEach(m => m.marker.remove());
            markers = [];

            poisData.forEach(function(poi) {
                if (!poi.lat || !poi.lng) return;

                const el = document.createElement('div');
                el.className = 'poi-library-marker';
                el.dataset.poiId = poi.id;
                el.dataset.category = poi.category;
                el.style.setProperty('--marker-color', poi.categoryColor || '#3B82F6');

                // Inner wrapper pattern - keeps marker dimensions correct for Mapbox anchor
                el.innerHTML =
                    '<div class="poi-library-marker-inner">' +
                        '<div class="poi-library-marker-dot">' +
                            '<i class="fa-solid ' + escapeHtml(poi.categoryIcon || 'fa-map-marker-alt') + '"></i>' +
                        '</div>' +
                        '<div class="poi-library-marker-label">' + escapeHtml(poi.title) + '</div>' +
                    '</div>';

                el.addEventListener('click', function() {
                    showPopup(poi);
                });

                // anchor: 'bottom' is critical for correct positioning with transforms
                const marker = new mapboxgl.Marker({ element: el, anchor: 'bottom' })
                    .setLngLat([poi.lng, poi.lat])
                    .addTo(mapInstance);

                markers.push({
                    marker: marker,
                    element: el,
                    poi: poi
                });
            });
        }

        function showPopup(poi) {
            document.querySelectorAll('.mapboxgl-popup').forEach(p => p.remove());

            new mapboxgl.Popup({
                offset: 20,
                closeButton: true,
                className: 'poi-library-popup'
            })
            .setLngLat([poi.lng, poi.lat])
            .setHTML(
                '<div class="poi-popup-content">' +
                    '<div class="poi-popup-title">' + escapeHtml(poi.title) + '</div>' +
                    '<div class="poi-popup-meta">' +
                        '<span class="poi-popup-type">' + (poi.type === 'native' ? 'Native' : 'Google') + '</span>' +
                        (poi.categoryName ? '<span>' + escapeHtml(poi.categoryName) + '</span>' : '') +
                    '</div>' +
                    '<div class="poi-popup-coords">' + poi.lat.toFixed(6) + ', ' + poi.lng.toFixed(6) + '</div>' +
                    '<a href="' + escapeHtml(poi.editUrl) + '" class="poi-popup-edit" target="_blank">Edit POI</a>' +
                '</div>'
            )
            .addTo(mapInstance);
        }

        function fitMapToBounds() {
            if (!mapInstance) return;

            const visibleMarkers = markers.filter(m => m.element.style.display !== 'none');
            if (visibleMarkers.length === 0) return;

            const bounds = new mapboxgl.LngLatBounds();
            visibleMarkers.forEach(m => {
                bounds.extend([m.poi.lng, m.poi.lat]);
            });

            mapInstance.fitBounds(bounds, {
                padding: 60,
                maxZoom: 15
            });
        }

        function setFilter(filter) {
            currentFilter = filter;

            document.querySelectorAll('.poi-filter-chip').forEach(function(chip) {
                chip.classList.toggle('active', chip.dataset.filter === filter);
            });

            filterMarkers();
        }

        function filterMarkers() {
            let visibleCount = 0;

            markers.forEach(function(m) {
                const matchesFilter = currentFilter === 'all' || m.poi.category === currentFilter;
                const matchesSearch = !currentSearch || m.poi.title.toLowerCase().includes(currentSearch);
                const isVisible = matchesFilter && matchesSearch;

                m.element.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            document.getElementById('poi-count').textContent = 'Showing ' + visibleCount + ' POIs';

            if (visibleCount > 0) {
                fitMapToBounds();
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Bind events
        document.querySelectorAll('.poi-filter-chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                setFilter(this.dataset.filter);
            });
        });

        const searchInput = document.getElementById('poi-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const input = this;
                searchTimeout = setTimeout(function() {
                    currentSearch = input.value.toLowerCase().trim();
                    filterMarkers();
                }, 200);
            });
        }
    })();
    </script>
</body>
</html>
