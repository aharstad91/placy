<?php
/**
 * Placy Admin Utilities
 * Helper functions and admin UI improvements
 *
 * @package Placy
 * @since 2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add admin menu for Placy settings
 */
add_action( 'admin_menu', 'placy_add_admin_menu' );
function placy_add_admin_menu() {
    add_menu_page(
        'Placy Settings',
        'Placy',
        'manage_options',
        'placy-settings',
        'placy_settings_page',
        'dashicons-location',
        30
    );
    
    add_submenu_page(
        'placy-settings',
        'Placy Status',
        'Status',
        'manage_options',
        'placy-status',
        'placy_status_page'
    );
}

/**
 * Placy settings page
 */
function placy_settings_page() {
    ?>
    <div class="wrap">
        <h1>Placy Settings</h1>
        
        <div class="card">
            <h2>API Configuration</h2>
            
            <table class="form-table">
                <tr>
                    <th>Google Places API Key</th>
                    <td>
                        <?php if ( defined( 'GOOGLE_PLACES_API_KEY' ) && GOOGLE_PLACES_API_KEY ): ?>
                            <span style="color: green;">✓ Configured</span>
                            <code><?php echo substr( GOOGLE_PLACES_API_KEY, 0, 10 ); ?>...</code>
                        <?php else: ?>
                            <span style="color: red;">✗ Not configured</span>
                            <p class="description">Add to wp-config.php: <code>define('GOOGLE_PLACES_API_KEY', 'your-key-here');</code></p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th>Mapbox Token</th>
                    <td>
                        <?php 
                        $mapbox_token = placy_get_mapbox_token();
                        if ( $mapbox_token ): 
                        ?>
                            <span style="color: green;">✓ Configured</span>
                            <code><?php echo substr( $mapbox_token, 0, 10 ); ?>...</code>
                        <?php else: ?>
                            <span style="color: red;">✗ Not configured</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2>Quick Actions</h2>
            
            <p>
                <a href="<?php echo admin_url( 'post-new.php?post_type=placy_native_point' ); ?>" class="button button-primary">
                    Add Native Point
                </a>
                
                <a href="<?php echo admin_url( 'post-new.php?post_type=placy_google_point' ); ?>" class="button button-primary">
                    Add Google Point
                </a>
                
                <a href="<?php echo admin_url( 'admin.php?page=placy-status' ); ?>" class="button">
                    View Status
                </a>
            </p>
        </div>
        
        <div class="card">
            <h2>Cron Schedule</h2>
            
            <?php
            $daily_next = wp_next_scheduled( 'placy_daily_refresh' );
            $weekly_next = wp_next_scheduled( 'placy_weekly_refresh' );
            ?>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Schedule</th>
                        <th>Next Run</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Daily Refresh (Featured Points)</td>
                        <td>Daily at 2:00 AM</td>
                        <td>
                            <?php 
                            echo $daily_next 
                                ? date( 'Y-m-d H:i:s', $daily_next ) 
                                : '<span style="color: red;">Not scheduled</span>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Weekly Refresh (Regular Points)</td>
                        <td>Weekly on Sunday at 3:00 AM</td>
                        <td>
                            <?php 
                            echo $weekly_next 
                                ? date( 'Y-m-d H:i:s', $weekly_next ) 
                                : '<span style="color: red;">Not scheduled</span>';
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Placy status page
 */
function placy_status_page() {
    // Get statistics
    $native_count = wp_count_posts( 'placy_native_point' )->publish;
    $google_count = wp_count_posts( 'placy_google_point' )->publish;
    
    $featured_native = count( get_posts( array(
        'post_type' => 'placy_native_point',
        'posts_per_page' => -1,
        'meta_key' => 'featured',
        'meta_value' => '1',
        'fields' => 'ids',
    ) ) );
    
    $featured_google = count( get_posts( array(
        'post_type' => 'placy_google_point',
        'posts_per_page' => -1,
        'meta_key' => 'featured',
        'meta_value' => '1',
        'fields' => 'ids',
    ) ) );
    
    $api_count = get_transient( 'placy_google_api_count' );
    $api_limit = apply_filters( 'placy_google_api_daily_limit', 100 );
    
    ?>
    <div class="wrap">
        <h1>Placy Status</h1>
        
        <div class="card">
            <h2>Statistics</h2>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Native Points</td>
                        <td><strong><?php echo $native_count; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Google Points</td>
                        <td><strong><?php echo $google_count; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Total Points</td>
                        <td><strong><?php echo $native_count + $google_count; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Featured Native Points</td>
                        <td><strong><?php echo $featured_native; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Featured Google Points</td>
                        <td><strong><?php echo $featured_google; ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>API Usage (Today)</h2>
            
            <p>
                <strong><?php echo $api_count ?: 0; ?></strong> / <?php echo $api_limit; ?> requests used
            </p>
            
            <div style="background: #f0f0f0; height: 30px; border-radius: 5px; overflow: hidden;">
                <div style="background: <?php echo $api_count > $api_limit * 0.8 ? '#e74c3c' : '#3498db'; ?>; height: 100%; width: <?php echo min( 100, ( $api_count / $api_limit ) * 100 ); ?>%;"></div>
            </div>
            
            <?php if ( $api_count >= $api_limit ): ?>
                <p style="color: red; margin-top: 10px;">
                    ⚠️ <strong>Rate limit reached!</strong> API requests will be blocked until tomorrow.
                </p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Recent Google Points</h2>
            
            <?php
            $recent_google = get_posts( array(
                'post_type' => 'placy_google_point',
                'posts_per_page' => 10,
                'orderby' => 'modified',
                'order' => 'DESC',
            ) );
            ?>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Place ID</th>
                        <th>Last Synced</th>
                        <th>Featured</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_google as $point ): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link( $point->ID ); ?>">
                                    <?php echo get_the_title( $point->ID ); ?>
                                </a>
                            </td>
                            <td>
                                <code><?php echo get_field( 'google_place_id', $point->ID ); ?></code>
                            </td>
                            <td>
                                <?php 
                                $last_synced = get_field( 'last_synced', $point->ID );
                                echo $last_synced ?: '<em>Never</em>';
                                ?>
                            </td>
                            <td>
                                <?php echo get_field( 'featured', $point->ID ) ? '⭐ Yes' : 'No'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Add custom columns to Native Points list
 */
add_filter( 'manage_placy_native_point_posts_columns', 'placy_native_point_columns' );
function placy_native_point_columns( $columns ) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['location'] = 'Location';
    $new_columns['featured'] = 'Featured';
    $new_columns['priority'] = 'Priority';
    $new_columns['taxonomy-placy_categories'] = 'Categories';
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}

add_action( 'manage_placy_native_point_posts_custom_column', 'placy_native_point_column_content', 10, 2 );
function placy_native_point_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'location':
            $coords = get_field( 'coordinates', $post_id );
            if ( $coords ) {
                echo sprintf( '%.4f, %.4f', $coords['latitude'], $coords['longitude'] );
            }
            break;
            
        case 'featured':
            echo get_field( 'featured', $post_id ) ? '⭐' : '';
            break;
            
        case 'priority':
            echo get_field( 'display_priority', $post_id ) ?: '5';
            break;
    }
}

/**
 * Add custom columns to Google Points list
 */
add_filter( 'manage_placy_google_point_posts_columns', 'placy_google_point_columns' );
function placy_google_point_columns( $columns ) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['place_id'] = 'Place ID';
    $new_columns['rating'] = 'Rating';
    $new_columns['last_synced'] = 'Last Synced';
    $new_columns['featured'] = 'Featured';
    $new_columns['taxonomy-placy_categories'] = 'Categories';
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}

add_action( 'manage_placy_google_point_posts_custom_column', 'placy_google_point_column_content', 10, 2 );
function placy_google_point_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'place_id':
            $place_id = get_field( 'google_place_id', $post_id );
            if ( $place_id ) {
                echo '<code>' . substr( $place_id, 0, 20 ) . '...</code>';
            }
            break;
            
        case 'rating':
            $cache = get_field( 'nearby_search_cache', $post_id );
            $data = json_decode( $cache, true );
            if ( isset( $data['rating'] ) ) {
                echo '⭐ ' . $data['rating'];
            }
            break;
            
        case 'last_synced':
            $synced = get_field( 'last_synced', $post_id );
            if ( $synced ) {
                echo human_time_diff( strtotime( $synced ), current_time( 'timestamp' ) ) . ' ago';
            } else {
                echo '<em>Never</em>';
            }
            break;
            
        case 'featured':
            echo get_field( 'featured', $post_id ) ? '⭐' : '';
            break;
    }
}

/**
 * Add Google Data refresh metabox
 */
add_action( 'add_meta_boxes', 'placy_add_google_refresh_metabox' );
function placy_add_google_refresh_metabox() {
    add_meta_box(
        'placy_google_refresh',
        '🔄 Google Data',
        'placy_google_refresh_metabox_callback',
        'placy_google_point',
        'side',
        'high'
    );
}

function placy_google_refresh_metabox_callback( $post ) {
    $place_id = get_field( 'google_place_id', $post->ID );
    $last_synced = get_field( 'last_synced', $post->ID );
    $cache = get_field( 'nearby_search_cache', $post->ID );
    $data = json_decode( $cache, true );
    
    ?>
    <div class="placy-refresh-metabox">
        <?php if ( empty( $place_id ) ): ?>
            <p><strong>⚠️ No Place ID</strong></p>
            <p>Add a Google Place ID in the "Google Data" tab below to fetch data automatically.</p>
        <?php else: ?>
            <p><strong>Place ID:</strong><br>
            <code style="word-break: break-all;"><?php echo esc_html( $place_id ); ?></code></p>
            
            <?php if ( $last_synced ): ?>
                <p><strong>Last Synced:</strong><br>
                <?php echo date( 'Y-m-d H:i', strtotime( $last_synced ) ); ?><br>
                <small>(<?php echo human_time_diff( strtotime( $last_synced ), current_time( 'timestamp' ) ); ?> ago)</small></p>
            <?php else: ?>
                <p><strong>Status:</strong> <span style="color: orange;">⚠️ Not synced yet</span></p>
            <?php endif; ?>
            
            <?php if ( ! empty( $data['name'] ) ): ?>
                <p><strong>Google Name:</strong><br><?php echo esc_html( $data['name'] ); ?></p>
            <?php endif; ?>
            
            <?php if ( isset( $data['rating'] ) ): ?>
                <p><strong>Rating:</strong> ⭐ <?php echo $data['rating']; ?> 
                (<?php echo $data['user_ratings_total'] ?? 0; ?> reviews)</p>
            <?php endif; ?>
            
            <button type="button" class="button button-primary button-large" id="placy-refresh-google-data" style="width: 100%; margin-top: 10px;">
                🔄 Refresh Google Data Now
            </button>
            
            <p class="description" style="margin-top: 10px;">
                Click to fetch latest data from Google Places API. This updates name, rating, photos, hours, etc.
            </p>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#placy-refresh-google-data').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('⏳ Fetching data...');
            
            $.post(ajaxurl, {
                action: 'placy_refresh_google_point',
                post_id: <?php echo $post->ID; ?>,
                nonce: '<?php echo wp_create_nonce( 'placy_refresh' ); ?>'
            }, function(response) {
                if (response.success) {
                    $btn.text('✅ Success! Reloading...');
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + response.data);
                    $btn.prop('disabled', false).text('🔄 Refresh Google Data Now');
                }
            }).fail(function() {
                alert('AJAX error occurred');
                $btn.prop('disabled', false).text('🔄 Refresh Google Data Now');
            });
        });
    });
    </script>
    
    <style>
    .placy-refresh-metabox p {
        margin: 10px 0;
    }
    .placy-refresh-metabox code {
        font-size: 11px;
        background: #f0f0f0;
        padding: 2px 4px;
        border-radius: 2px;
    }
    </style>
    <?php
}

/**
 * Add admin notices
 */
add_action( 'admin_notices', 'placy_admin_notices' );
function placy_admin_notices() {
    $screen = get_current_screen();
    
    // Check if API key is configured
    if ( ! defined( 'GOOGLE_PLACES_API_KEY' ) || empty( GOOGLE_PLACES_API_KEY ) ) {
        if ( $screen && in_array( $screen->post_type, array( 'placy_native_point', 'placy_google_point' ) ) ) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>Placy:</strong> Google Places API key is not configured. 
                    Add <code>define('GOOGLE_PLACES_API_KEY', 'your-key');</code> to wp-config.php
                </p>
            </div>
            <?php
        }
    }
    
    // Show notice on Google Point edit if no data synced yet
    if ( $screen && $screen->base === 'post' && $screen->post_type === 'placy_google_point' ) {
        global $post;
        if ( $post ) {
            $place_id = get_field( 'google_place_id', $post->ID );
            $last_synced = get_field( 'last_synced', $post->ID );
            
            if ( ! empty( $place_id ) && empty( $last_synced ) ) {
                ?>
                <div class="notice notice-info">
                    <p>
                        <strong>💡 Tip:</strong> Google data hasn't been fetched yet. 
                        Click the <strong>"🔄 Refresh Google Data Now"</strong> button in the sidebar to fetch name, rating, photos, and more.
                    </p>
                </div>
                <?php
            }
        }
    }
}
