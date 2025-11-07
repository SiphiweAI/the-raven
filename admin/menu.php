<?php
if (!defined('ABSPATH')) exit;

/**
 * Register admin menu pages
 */
add_action('admin_menu', 'scm_saas_admin_menu');

function scm_saas_admin_menu() {
    // Main dashboard
    add_menu_page(
        __('SCM Dashboard', 'scm-plugin'),
        __('SCM SaaS', 'scm-plugin'),
        'manage_options',
        'scm-saas-dashboard',
        'scm_saas_render_dashboard',
        'dashicons-chart-line',
        30
    );

    // Forecasting submenu
    add_submenu_page(
        'scm-saas-dashboard',
        __('Demand Forecasting', 'scm-plugin'),
        __('Forecasting', 'scm-plugin'),
        'manage_options',
        'scm-saas-forecasting',
        'scm_saas_render_forecast'
    );

    // Inventory submenu
    add_submenu_page(
        'scm-saas-dashboard',
        __('Inventory Optimization', 'scm-plugin'),
        __('Inventory', 'scm-plugin'),
        'manage_options',
        'scm-saas-inventory',
        'scm_saas_render_inventory'
    );

    // DB Inspector submenu
    add_submenu_page(
        'scm-saas-dashboard',
        __('Database Inspector', 'scm-plugin'),
        __('DB Inspector', 'scm-plugin'),
        'manage_options',
        'scm-db-inspector',
        'scm_saas_render_db_inspector'
    );

    // AI Forecast submenu
    add_submenu_page(
        'scm-saas-dashboard',
        __('AI Forecasting', 'scm-plugin'),
        __('AI Forecast', 'scm-plugin'),
        'manage_options',
        'scm-ai-forecast',
        'scm_saas_render_ai_forecast'
    );
}

/**
 * Enqueue admin scripts and styles
 */
add_action('admin_enqueue_scripts', 'scm_saas_admin_enqueue_scripts');

function scm_saas_admin_enqueue_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'scm-saas') === false && strpos($hook, 'scm-') === false) {
        return;
    }

    // Admin styles
    wp_enqueue_style(
        'scm-admin-styles',
        plugins_url('../assets/css/admin.css', __FILE__),
        [],
        '1.0.0'
    );

    // Chart.js from CDN
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        [],
        '4.4.0',
        true
    );

    // Forecast chart script
    if ($hook === 'scm-saas_page_scm-saas-forecasting') {
        wp_enqueue_script(
            'scm-forecast-chart',
            plugins_url('../assets/js/forecast-chart.js', __FILE__),
            ['chartjs'],
            '1.0.0',
            true
        );
    }

    // Inventory chart script
    if ($hook === 'scm-saas_page_scm-saas-inventory') {
        wp_enqueue_script(
            'scm-inventory-chart',
            plugins_url('../assets/js/inventory-chart.js', __FILE__),
            ['chartjs'],
            '1.0.0',
            true
        );
    }

    // Localize script for AJAX
    wp_localize_script('scm-forecast-chart', 'scmAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('scm_ajax_nonce')
    ]);
}

/**
 * Dashboard page
 */
function scm_saas_render_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'scm-plugin'));
    }
    
    require_once plugin_dir_path(__FILE__) . 'views/views-dashboard.php';
}

/**
 * Forecasting page
 */
function scm_saas_render_forecast() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'scm-plugin'));
    }
    
    require_once plugin_dir_path(__FILE__) . 'views/views-forecast.php';
}

/**
 * Inventory page
 */
function scm_saas_render_inventory() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'scm-plugin'));
    }
    
    require_once plugin_dir_path(__FILE__) . 'views/views-inventory.php';
}

/**
 * DB Inspector page
 */
function scm_saas_render_db_inspector() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'scm-plugin'));
    }
    
    require_once plugin_dir_path(__DIR__) . 'includes/class-db-inspector.php';
    require_once plugin_dir_path(__FILE__) . 'views/views-db-inspector.php';
}

/**
 * AI Forecast page
 */
function scm_saas_render_ai_forecast() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'scm-plugin'));
    }
    
    require_once plugin_dir_path(__FILE__) . 'views/views-ai-forecast.php';
}