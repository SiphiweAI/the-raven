<?php
if (!defined('ABSPATH')) exit;

class SCM_KPI {

    /**
     * Calculate Forecast Accuracy (MAPE)
     */
    public static function calculate_mape($actual, $forecast) {
        $n = count($actual);
        $error_sum = 0;

        for ($i = 0; $i < $n; $i++) {
            if ($actual[$i] == 0) continue;
            $error_sum += abs(($actual[$i] - $forecast[$i]) / $actual[$i]);
        }

        return round(($error_sum / $n) * 100, 2);
    }

    /**
     * Inventory Turnover
     * = COGS / Avg Inventory
     */
    public static function calculate_inventory_turnover($cogs, $avg_inventory) {
        if ($avg_inventory == 0) return 0;
        return round($cogs / $avg_inventory, 2);
    }

    /**
     * Supplier OTIF (On-Time In-Full)
     */
    public static function calculate_otif($on_time, $in_full) {
        return round(($on_time + $in_full) / 2, 2);
    }

    /**
     * Save KPI to DB
     */
    public static function save_kpi($name, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'scm_kpis';

        $wpdb->insert($table, [
            'metric_name'  => sanitize_text_field($name),
            'metric_value' => floatval($value),
            'metric_date'  => current_time('mysql')
        ]);
    }
}
