<?php
/**
 * Plugin Name: SCM SaaS (Demand Planning & Procurement)
 * Description: A free supply chain analytics and demand planning platform.
 * Version: 0.1
 * Author: Siphiwe Themba
 * License: MIT
 */


if (!defined('ABSPATH')) exit; // Protect from direct access


// Include dependencies
require_once plugin_dir_path(__FILE__) . 'includes/class-forecasting.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-inventory.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-suppliers.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-kpi.php';
require_once plugin_dir_path(__FILE__) . 'includes/db-schema.php';
// require_once plugin_dir_path(__FILE__) . 'admin/menu.php';
add_action('admin_menu', 'scm_saas_load_admin_menu');


function scm_saas_load_admin_menu() {
    require_once plugin_dir_path(__FILE__) . 'admin/menu.php';
}


// Activation hook - create database tables
register_activation_hook(__FILE__, 'scm_saas_create_tables');


// Initialize admin menu
add_action('admin_menu', 'scm_saas_add_admin_menu');