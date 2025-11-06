
<?php
if (!defined('ABSPATH')) exit;


function scm_handle_inventory_calc() {
    require_once plugin_dir_path(__DIR__) . 'includes/class-inventory.php';


    $product_id     = sanitize_text_field($_POST['product_id']);
    $annual_demand  = floatval($_POST['annual_demand']);
    $order_cost     = floatval($_POST['order_cost']);
    $holding_cost   = floatval($_POST['holding_cost']);
    $daily_demand   = floatval($_POST['daily_demand']);
    $lead_time      = floatval($_POST['lead_time']);
    $z_value        = floatval($_POST['z_value']);
    $demand_std     = floatval($_POST['demand_std']);


    // --- calculations
    $eoq = SCM_Inventory::calculate_eoq($annual_demand, $order_cost, $holding_cost);
    $ss  = SCM_Inventory::calculate_safety_stock($z_value, $demand_std, $lead_time);
    $rop = SCM_Inventory::calculate_rop($daily_demand, $lead_time, $ss);


    SCM_Inventory::save_inventory($product_id, $eoq, $rop, $ss);


    echo "<div class='updated notice'><p><strong>Inventory metrics saved!</strong></p></div>";
    echo "<table class='widefat'>
            <thead><tr><th>Metric</th><th>Value</th></tr></thead>
            <tbody>
                <tr><td>Economic Order Quantity (EOQ)</td><td>" . round($eoq,2) . "</td></tr>
                <tr><td>Safety Stock</td><td>" . round($ss,2) . "</td></tr>
                <tr><td>Reorder Point (ROP)</td><td>" . round($rop,2) . "</td></tr>
            </tbody>
          </table>";


    // JS data for Chart.js
    echo "<script>
        const invLabels = ['EOQ','Safety Stock','ROP'];
        const invValues = [" . round($eoq,2) . "," . round($ss,2) . "," . round($rop,2) . "];
    </script>";
}
