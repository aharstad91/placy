/**
 * Mapbox Utilities
 *
 * Shared utilities for all Mapbox map instances in Placy.
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * POI label layer patterns to hide from Mapbox default styles
     * These patterns match the layer IDs in Mapbox streets-v12 and similar styles
     */
    var POI_LAYER_PATTERNS = [
        // POI labels (businesses, shops, restaurants, etc.)
        /^poi-label/,
        // Transit labels (bus stops, train stations shown by Mapbox)
        /^transit-label/,
        // Road labels can be kept or removed based on preference
        // /^road-label/,
    ];

    /**
     * Hide Mapbox's built-in POI labels from a map instance
     *
     * Call this function after map.on('load') to remove clutter
     * from third-party POIs (restaurants, shops, etc.)
     *
     * @param {mapboxgl.Map} map - The Mapbox map instance
     * @param {Object} options - Optional configuration
     * @param {boolean} options.keepTransit - Keep transit labels (default: false)
     * @param {boolean} options.keepRoads - Keep road labels (default: true)
     */
    function hideMapboxPOILayers(map, options) {
        if (!map || typeof map.getStyle !== 'function') {
            return;
        }

        options = options || {};
        var keepTransit = options.keepTransit || false;

        var style = map.getStyle();
        if (!style || !style.layers) {
            return;
        }

        style.layers.forEach(function(layer) {
            var layerId = layer.id;

            // Check if this layer matches any of our POI patterns
            var shouldHide = POI_LAYER_PATTERNS.some(function(pattern) {
                // Skip transit if keepTransit is true
                if (keepTransit && pattern.toString().includes('transit')) {
                    return false;
                }
                return pattern.test(layerId);
            });

            if (shouldHide) {
                try {
                    map.setLayoutProperty(layerId, 'visibility', 'none');
                } catch (e) {
                    // Layer might not exist in all styles
                }
            }
        });
    }

    /**
     * Show Mapbox's built-in POI labels (reverse of hideMapboxPOILayers)
     *
     * @param {mapboxgl.Map} map - The Mapbox map instance
     */
    function showMapboxPOILayers(map) {
        if (!map || typeof map.getStyle !== 'function') {
            return;
        }

        var style = map.getStyle();
        if (!style || !style.layers) {
            return;
        }

        style.layers.forEach(function(layer) {
            var layerId = layer.id;

            var shouldShow = POI_LAYER_PATTERNS.some(function(pattern) {
                return pattern.test(layerId);
            });

            if (shouldShow) {
                try {
                    map.setLayoutProperty(layerId, 'visibility', 'visible');
                } catch (e) {
                    // Layer might not exist in all styles
                }
            }
        });
    }

    // Expose globally
    window.PlacyMapUtils = {
        hideMapboxPOILayers: hideMapboxPOILayers,
        showMapboxPOILayers: showMapboxPOILayers
    };

})();
