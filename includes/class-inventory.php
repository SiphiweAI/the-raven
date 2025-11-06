<?php
if (!defined('ABSPATH')) exit;

class SCM_Inventory {

    /**
     * Economic Order Quantity (EOQ)
     * EOQ = sqrt((2DS) / H)
     */
    public static function calculate_eoq($annual_demand, $order_cost, $holding_cost) {
        if ($holding_cost == 0) return 0;
        return sqrt((2 * $annual_demand * $order_cost) / $holding_cost);
    }

    /**
     * Reorder Point (ROP)
     * ROP = (daily demand * lead time) + safety stock
     */
    public static function calculate_rop($daily_demand, $lead_time, $safety_stock = 0) {
        return ($daily_demand * $lead_time) + $safety_stock;
    }

    /**
     * Safety Stock
     * SS = z * σ_demand * sqrt(lead time)
     */
    public static function calculate_safety_stock($z_value, $demand_std_dev, $lead_time) {
        return $z_value * $demand_std_dev * sqrt($lead_time);
    }

    /**
     * Save results to DB
     */
    public static function save_inventory($product_id, $eoq, $rop, $safety_stock) {
        global $wpdb;
        $table = $wpdb->prefix . 'scm_inventory';

        $wpdb->insert($table, [
            'product_id'    => sanitize_text_field($product_id),
            'eoq'           => floatval($eoq),
            'rop'           => floatval($rop),
            'safety_stock'  => floatval($safety_stock)
        ]);
    }
}
