
<?php
// Hook into the WordPress admin menu
add_action('admin_menu', 'scm_saas_add_admin_menu');


function scm_saas_add_admin_menu() {
    // Top-level menu item
    add_menu_page(
        'SCM SaaS Dashboard',         // Page title
        'SCM SaaS',                   // Menu title
        'manage_options',             // Capability
        'scm-saas-dashboard',         // Menu slug
        'scm_saas_render_dashboard',  // Callback function
        'dashicons-chart-line',       // Icon
        3                             // Position in admin menu
    );


    // Submenu: Forecasting
    add_submenu_page(
        'scm-saas-dashboard',
        'Forecasting',
        'Forecasting',
        'manage_options',
        'scm-saas-forecasting',
        'scm_saas_render_forecast'
    );


    // Submenu: Inventory Optimization
    add_submenu_page(
        'scm-saas-dashboard',
        'Inventory Optimization',
        'Inventory',
        'manage_options',
        'scm-saas-inventory',
        'scm_saas_render_inventory'
    );
    add_submenu_page(
        'SCM DB Inspector',
        'DB Inspector',
        'manage_options',
        'scm-db-inspector',
        function() {
            $tables = SCM_DB_Inspector::list_tables();
            echo "<h2>SCM Tables</h2><ul>";
            foreach ($tables as $table) {
                echo "<li>{$table[0]}</li>";
            }
            echo "</ul>";
        },
        'dashicons-database',
        75
    );
    add_submenu_page(
        'SCM AI Forecast',
        'AI Forecast',
        'manage_options',
        'scm-ai-forecast',
        function() {
            require_once plugin_dir_path(__FILE__) . 'admin/run-ai-forecast.php';
        },
        'dashicons-chart-line',
        76
    );
}


// ===== Render Functions =====


function scm_saas_render_dashboard() {
    include plugin_dir_path(__FILE__) . 'views-dashboard.php';
}


function scm_saas_render_forecast() {
    include plugin_dir_path(__FILE__) . 'views-forecast.php';
}


function scm_saas_render_inventory() {
    include plugin_dir_path(__FILE__) . 'views-inventory.php';
}
