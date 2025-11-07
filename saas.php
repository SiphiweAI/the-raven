<?php
/**
 * Plugin Name: The Raven SaaS Plugin
 * Plugin URI: https://yoursite.com/scm-plugin
 * Description: Supply Chain Management Suite with AI Forecasting, Inventory Optimization, and KPI Analytics
 * Version: 0.0.1
 * Author: Siphiwe Themba
 * Author URI: https://yoursite.com
 * License: GPL v2 or later
 * Text Domain: scm-plugin
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('SCM_VERSION', '1.0.0');
define('SCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin activation
 */
register_activation_hook(__FILE__, 'scm_saas_activate');

function scm_saas_activate() {
    // Create tables
    require_once SCM_PLUGIN_DIR . 'includes/db-schema.php';
    scm_saas_create_tables();
    
    // Set default options
    add_option('scm_plugin_version', SCM_VERSION);
    add_option('scm_python_path', '/usr/bin/python3');
    
    // Create AI directory if it doesn't exist
    $ai_dir = SCM_PLUGIN_DIR . 'ai/';
    if (!file_exists($ai_dir)) {
        wp_mkdir_p($ai_dir);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation
 */
register_deactivation_hook(__FILE__, 'scm_saas_deactivate');

function scm_saas_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('scm_run_ai_forecast_event');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall - see uninstall.php
 */

/**
 * Load plugin textdomain for translations
 */
add_action('plugins_loaded', 'scm_saas_load_textdomain');

function scm_saas_load_textdomain() {
    load_plugin_textdomain(
        'scm-plugin',
        false,
        dirname(SCM_PLUGIN_BASENAME) . '/languages'
    );
}

/**
 * core classes
 */
require_once SCM_PLUGIN_DIR . 'includes/class-db-inspector.php';
require_once SCM_PLUGIN_DIR . 'includes/class-forecasting.php';
require_once SCM_PLUGIN_DIR . 'includes/class-inventory.php';
require_once SCM_PLUGIN_DIR . 'includes/class-kpi.php';
require_once SCM_PLUGIN_DIR . 'includes/class-suppliers.php';
require_once SCM_PLUGIN_DIR . 'includes/frontend.php';


/**
 * admin files
 */
if (is_admin()) {
    require_once SCM_PLUGIN_DIR . 'admin/menu.php';
    require_once SCM_PLUGIN_DIR . 'admin/forecast-handler.php';
    require_once SCM_PLUGIN_DIR . 'admin/inventory-handler.php';
    require_once SCM_PLUGIN_DIR . 'admin/run-ai-forecast.php';
}

/**
 * Check for database updates
 */
add_action('plugins_loaded', 'scm_saas_check_version');

function scm_saas_check_version() {
    $installed_version = get_option('scm_plugin_version', '0.0.0');
    
    if (version_compare($installed_version, SCM_VERSION, '<')) {
        require_once SCM_PLUGIN_DIR . 'includes/db-schema.php';
        scm_saas_maybe_upgrade_db();
        update_option('scm_plugin_version', SCM_VERSION);
    }
}

/**
 * Add settings link on plugin page
 */
add_filter('plugin_action_links_' . SCM_PLUGIN_BASENAME, 'scm_saas_action_links');

function scm_saas_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=scm-saas-dashboard') . '">' . 
                     __('Dashboard', 'scm-plugin') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Add admin notices
 
*add_action('admin_notices', 'scm_saas_admin_notices');

*function scm_saas_admin_notices() {
 *   // Check if tables exist
  *  if (!scm_saas_tables_exist()) {
*        ?>
 *       <div class="notice notice-error">
  *          <p>
*                <?php esc_html_e('SCM Plugin: Database tables are missing. Please deactivate and reactivate the plugin.', 'scm-plugin'); ?>
 *           </p>
  *      </div>
*        <?php
 *   }
*}
    */

/**
 * Register REST API endpoints (for future use)
 */
add_action('rest_api_init', 'scm_register_rest_routes');

function scm_register_rest_routes() {
    register_rest_route('scm/v1', '/forecasts/(?P<product_id>[a-zA-Z0-9-]+)', [
        'methods' => 'GET',
        'callback' => 'scm_rest_get_forecasts',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
}

function scm_rest_get_forecasts($request) {
    $product_id = sanitize_text_field($request['product_id']);
    $forecasts = SCM_Forecasting::get_forecasts($product_id);
    
    if (is_wp_error($forecasts)) {
        return new WP_Error(
            'forecast_error',
            $forecasts->get_error_message(),
            ['status' => 400]
        );
    }
    
    return rest_ensure_response($forecasts);
}

/* Create a page with the shortcode on activation */

function scm_saas_create_page() {
    $page = array(
        'post_title'    => 'The Raven Portal',   // Page title
        'post_content'  => '[scm_saas_portal]', 
        'post_status'   => 'publish',
        'post_type'     => 'page',
    );

    // Only create the page if it doesn't already exist
    if ( ! get_page_by_title( $page['post_title'] ) ) {
        wp_insert_post( $page );
    }
}
register_activation_hook( __FILE__, 'scm_saas_create_page' );