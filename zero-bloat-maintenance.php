<?php
/**
 * Plugin Name: Zero-Bloat Maintenance Mode
 * Description: A lightweight maintenance mode with advanced access control, SEO toggles, and custom branding.
 * Version: 1.5.2
 * Author: Sapthesh V
 * License: GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Register Settings
 */
function zbmm_register_settings() {
    register_setting( 'zbmm_settings_group', 'zbmm_enabled', array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_seo_status', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '503' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_message', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_whitelist', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_exclude_urls', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_countdown_date', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_custom_css', array( 'sanitize_callback' => 'wp_strip_all_tags' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_logo_url', array( 'sanitize_callback' => 'sanitize_url' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_bypass_token', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'zbmm_settings_group', 'zbmm_bypass_expiry', array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 24 ) );
    
    register_setting( 'zbmm_settings_group', 'zbmm_allowed_roles', array(
        'type'              => 'array',
        'sanitize_callback' => 'zbmm_sanitize_roles_array'
    ) );
}
add_action( 'admin_init', 'zbmm_register_settings' );

function zbmm_sanitize_roles_array( $input ) {
    if ( ! is_array( $input ) ) return array();
    return array_map( 'sanitize_text_field', $input );
}

/**
 * 2. Create Admin Menu
 */
function zbmm_add_admin_menu() {
    add_options_page( 'Zero-Bloat Maintenance', 'Maintenance Mode', 'manage_options', 'zero-bloat-maintenance', 'zbmm_settings_page' );
}
add_action( 'admin_menu', 'zbmm_add_admin_menu' );

/**
 * 3. Enqueue Media Scripts
 */
function zbmm_admin_scripts( $hook ) {
    if ( $hook !== 'settings_page_zero-bloat-maintenance' ) return;
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'zbmm_admin_scripts' );

/**
 * 4. Settings Page UI
 */
function zbmm_settings_page() {
    $current_ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'Unknown';
    $logo_url      = get_option('zbmm_logo_url');
    $seo_status    = get_option('zbmm_seo_status', '503');
    $allowed_roles = get_option('zbmm_allowed_roles', array('administrator'));
    $expiry        = get_option('zbmm_bypass_expiry', 24);
    
    $bypass_token = get_option('zbmm_bypass_token');
    if ( empty( $bypass_token ) ) {
        $bypass_token = wp_generate_password( 10, false );
        update_option( 'zbmm_bypass_token', $bypass_token );
    }
    $bypass_url = add_query_arg( 'bypass', $bypass_token, home_url() );
    
    global $wp_roles;
    if ( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();
    ?>
    <div class="wrap">
        <h1>Zero-Bloat Maintenance Mode</h1>
        <form method="post" action="options.php" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; max-width: 800px;">
            <?php settings_fields( 'zbmm_settings_group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Mode</th>
                    <td>
                        <input type="checkbox" name="zbmm_enabled" value="1" <?php checked(1, get_option('zbmm_enabled'), true); ?> />
                        <label>Activate screen for visitors.</label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">SEO Status Code</th>
                    <td>
                        <select name="zbmm_seo_status">
                            <option value="503" <?php selected( $seo_status, '503' ); ?>>503 Service Unavailable (Maintenance - Do not index)</option>
                            <option value="200" <?php selected( $seo_status, '200' ); ?>>200 OK (Coming Soon - Index this page)</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Allowed User Roles</th>
                    <td>
                        <p class="description">Select which logged-in roles bypass the maintenance screen. Admins always bypass.</p>
                        <fieldset>
                            <?php foreach ( $wp_roles->roles as $role_key => $role_data ) : ?>
                                <label style="display: inline-block; margin-right: 15px;">
                                    <input type="checkbox" name="zbmm_allowed_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $allowed_roles ), true ); ?> />
                                    <?php echo esc_html( $role_data['name'] ); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Secret Bypass URL</th>
                    <td>
                        <input type="text" name="zbmm_bypass_token" value="<?php echo esc_attr( $bypass_token ); ?>" class="regular-text" />
                        <p style="background: #f0f0f1; padding: 10px; border-radius: 4px; display: inline-block; margin: 10px 0;">
                            <strong><a href="<?php echo esc_url( $bypass_url ); ?>" target="_blank"><?php echo esc_url( $bypass_url ); ?></a></strong>
                        </p>
                        <br>
                        <label for="zbmm_bypass_expiry"><strong>Cookie Expiry (Hours):</strong></label>
                        <input type="number" name="zbmm_bypass_expiry" id="zbmm_bypass_expiry" value="<?php echo esc_attr( $expiry ); ?>" class="small-text" min="1" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom Logo</th>
                    <td>
                        <div style="margin-bottom: 10px;">
                            <img id="zbmm_logo_preview" src="<?php echo esc_url($logo_url); ?>" style="max-width: 150px; display: <?php echo $logo_url ? 'block' : 'none'; ?>; border: 1px solid #ccc; padding: 5px; border-radius: 4px; margin-bottom: 10px;" />
                        </div>
                        <input type="hidden" name="zbmm_logo_url" id="zbmm_logo_url" value="<?php echo esc_url($logo_url); ?>" />
                        <button type="button" class="button" id="zbmm_upload_logo_btn">Choose Image</button>
                        <button type="button" class="button" id="zbmm_remove_logo_btn" style="color: #d63638; border-color: #d63638; display: <?php echo $logo_url ? 'inline-block' : 'none'; ?>;">Remove</button>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Maintenance Message</th>
                    <td>
                        <textarea name="zbmm_message" rows="3" cols="50" class="large-text"><?php echo esc_textarea(get_option('zbmm_message', 'We’ll be back soon!')); ?></textarea>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Excluded URLs</th>
                    <td>
                        <textarea name="zbmm_exclude_urls" rows="3" cols="50" class="large-text" placeholder="/privacy-policy&#10;/contact"><?php echo esc_textarea(get_option('zbmm_exclude_urls')); ?></textarea>
                        <p class="description">Add one relative URL path per line to keep them accessible to the public.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Launch Date (Countdown)</th>
                    <td>
                        <input type="datetime-local" name="zbmm_countdown_date" value="<?php echo esc_attr(get_option('zbmm_countdown_date')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">IP Whitelist</th>
                    <td>
                        <input type="text" name="zbmm_whitelist" value="<?php echo esc_attr(get_option('zbmm_whitelist')); ?>" class="regular-text" placeholder="e.g. 127.0.0.1" />
                        <p class="description">Your IP: <strong><?php echo esc_html( $current_ip ); ?></strong></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom CSS</th>
                    <td>
                        <textarea name="zbmm_custom_css" rows="6" cols="50" class="large-text" style="font-family: monospace;" placeholder="body { background-color: #000; color: #fff; }"><?php echo esc_html(get_option('zbmm_custom_css')); ?></textarea>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($){
        var mediaUploader;
        $('#zbmm_upload_logo_btn').click(function(e) {
            e.preventDefault();
            if (mediaUploader) { mediaUploader.open(); return; }
            mediaUploader = wp.media({ title: 'Choose a Logo', button: { text: 'Use this logo' }, multiple: false });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#zbmm_logo_url').val(attachment.url);
                $('#zbmm_logo_preview').attr('src', attachment.url).show();
                $('#zbmm_remove_logo_btn').show();
            });
            mediaUploader.open();
        });
        $('#zbmm_remove_logo_btn').click(function(e){
            e.preventDefault();
            $('#zbmm_logo_url').val('');
            $('#zbmm_logo_preview').hide();
            $(this).hide();
        });
    });
    </script>
    <?php
}

/**
 * 5. Logic: Redirection, Cookies & Permissions
 */
function zbmm_redirect_logic() {
    if ( get_option('zbmm_enabled') != 1 ) return;
    if ( is_admin() || $GLOBALS['pagenow'] === 'wp-login.php' ) return;

    // Role-Based Access Control
    if ( is_user_logged_in() ) {
        if ( current_user_can( 'manage_options' ) ) return; 
        
        $user          = wp_get_current_user();
        $user_roles    = (array) $user->roles;
        $allowed_roles = get_option('zbmm_allowed_roles', array('administrator'));
        
        if ( ! empty( array_intersect( $allowed_roles, $user_roles ) ) ) {
            return; 
        }
    }

    // Excluded URLs Check
    $excluded_raw = get_option('zbmm_exclude_urls', '');
    if ( ! empty( $excluded_raw ) ) {
        $current_path   = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $excluded_paths = array_filter( array_map( 'trim', explode( "\n", $excluded_raw ) ) );
        foreach ( $excluded_paths as $path ) {
            if ( ! empty( $path ) && stripos( $current_path, $path ) !== false ) {
                return; 
            }
        }
    }

    $bypass_token = get_option('zbmm_bypass_token');
    $expiry_hours = absint( get_option('zbmm_bypass_expiry', 24) );
    $expiry_hours = $expiry_hours > 0 ? $expiry_hours : 1; 

    // Handle Secret Bypass URL Click
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! empty( $bypass_token ) && isset( $_GET['bypass'] ) && sanitize_text_field( wp_unslash( $_GET['bypass'] ) ) === $bypass_token ) {
        setcookie( 'zbmm_bypass', $bypass_token, time() + ( $expiry_hours * HOUR_IN_SECONDS ), '/' );
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        wp_safe_redirect( remove_query_arg( 'bypass' ) );
        exit;
    }

    // Handle Active Bypass Cookie
    if ( ! empty( $bypass_token ) && isset( $_COOKIE['zbmm_bypass'] ) && sanitize_text_field( wp_unslash( $_COOKIE['zbmm_bypass'] ) ) === $bypass_token ) {
        return; 
    }

    // IP Whitelist Check
    $user_ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $whitelist_raw = get_option('zbmm_whitelist', '');
    $whitelist     = array_filter( array_map( 'trim', explode( ',', $whitelist_raw ) ) );
    if ( $user_ip && in_array( $user_ip, $whitelist, true ) ) return;

    // Serve Maintenance Page with chosen SEO Status
    $seo_status = get_option('zbmm_seo_status', '503');
    if ( $seo_status === '200' ) {
        status_header( 200 );
    } else {
        status_header( 503 );
        header( 'Retry-After: 3600' );
    }
    nocache_headers();
    
    $template_path = plugin_dir_path( __FILE__ ) . 'maintenance-template.php';
    if ( file_exists( $template_path ) ) {
        include( $template_path );
        exit;
    } else {
        $message = get_option( 'zbmm_message', 'Under Maintenance' );
        wp_die( esc_html( $message ), 'Under Maintenance', array( 'response' => (int) $seo_status ) );
    }
}
add_action( 'template_redirect', 'zbmm_redirect_logic', 1 );

/**
 * 6. Admin Bar Toggle Node & Handler
 */
function zbmm_admin_bar_toggle( $wp_admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $is_enabled = get_option( 'zbmm_enabled' ) == 1;
    $wp_admin_bar->add_node( array(
        'id'    => 'zbmm-status',
        'title' => $is_enabled ? '🔴 Maintenance: ON' : '🟢 Maintenance: OFF',
        'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=zbmm_toggle_status' ), 'zbmm_toggle' ),
    ) );
}
add_action( 'admin_bar_menu', 'zbmm_admin_bar_toggle', 999 );

function zbmm_handle_toggle() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
    check_admin_referer( 'zbmm_toggle' );
    update_option( 'zbmm_enabled', get_option( 'zbmm_enabled' ) == 1 ? 0 : 1 );
    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
    exit;
}
add_action( 'admin_post_zbmm_toggle_status', 'zbmm_handle_toggle' );