<?php
if (!defined('ABSPATH')) exit;

/**
 * Register AJAX handler for inventory calculations
 */
add_action('wp_ajax_scm_calculate_inventory', 'scm_handle_inventory_calc');

/**
 * Handle inventory calculations via AJAX
 */
function scm_handle_inventory_calc() {
    // Security checks
    check_ajax_referer('scm_inventory_calc', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('Unauthorized access.', 'scm-plugin')
        ]);
    }

    // Load class
    require_once plugin_dir_path(__DIR__) . 'includes/class-inventory.php';

    // Sanitize and validate inputs
    $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';
    
    if (empty($product_id)) {
        wp_send_json_error([
            'message' => __('Product ID is required.', 'scm-plugin')
        ]);
    }

    $inputs = [
        'annual_demand' => isset($_POST['annual_demand']) ? floatval($_POST['annual_demand']) : 0,
        'order_cost'    => isset($_POST['order_cost']) ? floatval($_POST['order_cost']) : 0,
        'holding_cost'  => isset($_POST['holding_cost']) ? floatval($_POST['holding_cost']) : 0,
        'daily_demand'  => isset($_POST['daily_demand']) ? floatval($_POST['daily_demand']) : 0,
        'lead_time'     => isset($_POST['lead_time']) ? floatval($_POST['lead_time']) : 0,
        'z_value'       => isset($_POST['z_value']) ? floatval($_POST['z_value']) : 1.65,
        'demand_std'    => isset($_POST['demand_std']) ? floatval($_POST['demand_std']) : 0,
    ];

    // Validate all inputs are non-negative
    foreach ($inputs as $key => $value) {
        if ($value < 0) {
            wp_send_json_error([
                'message' => sprintf(__('%s must be non-negative.', 'scm-plugin'), $key)
            ]);
        }
    }

    // Calculate metrics
    $eoq = SCM_Inventory::calculate_eoq(
        $inputs['annual_demand'],
        $inputs['order_cost'],
        $inputs['holding_cost']
    );

    if (is_wp_error($eoq)) {
        wp_send_json_error(['message' => $eoq->get_error_message()]);
    }

    $safety_stock = SCM_Inventory::calculate_safety_stock(
        $inputs['z_value'],
        $inputs['demand_std'],
        $inputs['lead_time']
    );

    if (is_wp_error($safety_stock)) {
        wp_send_json_error(['message' => $safety_stock->get_error_message()]);
    }

    $rop = SCM_Inventory::calculate_rop(
        $inputs['daily_demand'],
        $inputs['lead_time'],
        $safety_stock
    );

    if (is_wp_error($rop)) {
        wp_send_json_error(['message' => $rop->get_error_message()]);
    }

    // Save to database
    $save_result = SCM_Inventory::save_inventory($product_id, $eoq, $rop, $safety_stock);

    if (is_wp_error($save_result)) {
        wp_send_json_error(['message' => $save_result->get_error_message()]);
    }

    // Clear cache
    wp_cache_delete("scm_inventory_{$product_id}", 'scm_plugin');

    wp_send_json_success([
        'message' => __('Inventory metrics calculated and saved successfully!', 'scm-plugin'),
        'metrics' => [
            'product_id'   => $product_id,
            'eoq'          => round($eoq, 2),
            'safety_stock' => round($safety_stock, 2),
            'rop'          => round($rop, 2),
            'inputs'       => $inputs
        ]
    ]);
}