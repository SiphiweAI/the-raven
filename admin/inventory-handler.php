<?php
if (!defined('ABSPATH')) exit;

function scm_handle_inventory_calc() {
    require_once plugin_dir_path(__DIR__) . 'includes/class-inventory.php';

    $product_id = sanitize_text_field($_POST['product_id']);
    $annual_demand = floatval($_POST['annual_demand']);
    $order_cost = floatval($_POST['order_cost']);
    $holding_cost = floatval($_POST['holding_cost']);
    $daily_demand = floatval($_POST['daily_demand']);
    $lead_time = floatval($_POST['lead_time']);
    $z_value = floatval($_POST['z_value']);
    $demand_std = floatval($_POST['demand_std']);

    // Validate inputs
    foreach ([$annual_demand, $order_cost, $holding_cost, $daily_demand, $lead_time, $z_value, $demand_std] as $val) {
        if ($val < 0) {
            wp_send_json_error('Invalid input: numeric values must be non-negative.');
        }
    }

    $eoq = SCM_Inventory::calculate_eoq($annual_demand, $order_cost, $holding_cost);
    $ss = SCM_Inventory::calculate_safety_stock($z_value, $demand_std, $lead_time);
    $rop = SCM_Inventory::calculate_rop($daily_demand, $lead_time, $ss);

    try {
        SCM_Inventory::save_inventory($product_id, $eoq, $rop, $ss);
    } catch (Exception $e) {
        wp_send_json_error('DB error while saving inventory metrics: ' . $e->getMessage());
    }

    wp_send_json_success([
        'message' => 'Inventory metrics saved!',
        'metrics' => [
            'EOQ' => round($eoq, 2),
            'Safety Stock' => round($ss, 2),
            'ROP' => round($rop, 2)
        ]
    ]);
}

