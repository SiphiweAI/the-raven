<?php
if (!defined('ABSPATH')) exit;

class SCM_KPI {

    /**
     * Calculate Mean Absolute Percentage Error (MAPE)
     * 
     * @param array $actual   Actual values
     * @param array $forecast Forecasted values
     * @return float|WP_Error MAPE percentage or error
     */
    public static function calculate_mape($actual, $forecast) {
        if (!is_array($actual) || !is_array($forecast)) {
            return new WP_Error('invalid_input', __('Both actual and forecast must be arrays.', 'scm-plugin'));
        }
        
        if (count($actual) !== count($forecast)) {
            return new WP_Error('mismatched_arrays', __('Actual and forecast arrays must have the same length.', 'scm-plugin'));
        }
        
        $n = count($actual);
        
        if ($n === 0) {
            return new WP_Error('empty_arrays', __('Arrays cannot be empty.', 'scm-plugin'));
        }
        
        $error_sum = 0;
        $valid_count = 0;
        
        for ($i = 0; $i < $n; $i++) {
            // Skip zero actual values to avoid division by zero
            if ($actual[$i] == 0) {
                continue;
            }
            
            $error_sum += abs(($actual[$i] - $forecast[$i]) / $actual[$i]);
            $valid_count++;
        }
        
        if ($valid_count === 0) {
            return new WP_Error('no_valid_data', __('No valid data points for MAPE calculation.', 'scm-plugin'));
        }
        
        $mape = ($error_sum / $valid_count) * 100;
        
        return round($mape, 2);
    }
    
    /**
     * Calculate Mean Absolute Error (MAE)
     * 
     * @param array $actual
     * @param array $forecast
     * @return float|WP_Error
     */
    public static function calculate_mae($actual, $forecast) {
        if (!is_array($actual) || !is_array($forecast)) {
            return new WP_Error('invalid_input', __('Both actual and forecast must be arrays.', 'scm-plugin'));
        }
        
        if (count($actual) !== count($forecast)) {
            return new WP_Error('mismatched_arrays', __('Arrays must have the same length.', 'scm-plugin'));
        }
        
        $n = count($actual);
        if ($n === 0) {
            return new WP_Error('empty_arrays', __('Arrays cannot be empty.', 'scm-plugin'));
        }
        
        $error_sum = 0;
        for ($i = 0; $i < $n; $i++) {
            $error_sum += abs($actual[$i] - $forecast[$i]);
        }
        
        return round($error_sum / $n, 2);
    }

    /**
     * Inventory Turnover Ratio
     * Formula: Cost of Goods Sold / Average Inventory
     * 
     * @param float $cogs          Cost of goods sold
     * @param float $avg_inventory Average inventory value
     * @return float|WP_Error
     */
    public static function calculate_inventory_turnover($cogs, $avg_inventory) {
        if (!is_numeric($cogs) || $cogs < 0) {
            return new WP_Error('invalid_cogs', __('COGS must be a non-negative number.', 'scm-plugin'));
        }
        
        if (!is_numeric($avg_inventory) || $avg_inventory < 0) {
            return new WP_Error('invalid_inventory', __('Average inventory must be a non-negative number.', 'scm-plugin'));
        }
        
        if ($avg_inventory == 0) {
            return new WP_Error('zero_inventory', __('Average inventory cannot be zero.', 'scm-plugin'));
        }
        
        return round($cogs / $avg_inventory, 2);
    }

    /**
     * Calculate On-Time In-Full (OTIF) percentage
     * 
     * @param float $on_time_pct   On-time delivery percentage
     * @param float $in_full_pct   In-full delivery percentage
     * @return float|WP_Error
     */
    public static function calculate_otif($on_time_pct, $in_full_pct) {
        if (!is_numeric($on_time_pct) || $on_time_pct < 0 || $on_time_pct > 100) {
            return new WP_Error('invalid_on_time', __('On-time percentage must be between 0 and 100.', 'scm-plugin'));
        }
        
        if (!is_numeric($in_full_pct) || $in_full_pct < 0 || $in_full_pct > 100) {
            return new WP_Error('invalid_in_full', __('In-full percentage must be between 0 and 100.', 'scm-plugin'));
        }
        
        // OTIF is typically the product, not average
        $otif = ($on_time_pct / 100) * ($in_full_pct / 100) * 100;
        
        return round($otif, 2);
    }
    
    /**
     * Calculate Fill Rate
     * 
     * @param int $orders_filled
     * @param int $total_orders
     * @return float|WP_Error
     */
    public static function calculate_fill_rate($orders_filled, $total_orders) {
        if (!is_numeric($orders_filled) || $orders_filled < 0) {
            return new WP_Error('invalid_filled', __('Orders filled must be a non-negative number.', 'scm-plugin'));
        }
        
        if (!is_numeric($total_orders) || $total_orders <= 0) {
            return new WP_Error('invalid_total', __('Total orders must be a positive number.', 'scm-plugin'));
        }
        
        if ($orders_filled > $total_orders) {
            return new WP_Error('invalid_ratio', __('Orders filled cannot exceed total orders.', 'scm-plugin'));
        }
        
        return round(($orders_filled / $total_orders) * 100, 2);
    }

    /**
     * Save KPI to database
     * 
     * @param string $name  Metric name
     * @param float  $value Metric value
     * @param string $date  Optional date (YYYY-MM-DD)
     * @return int|WP_Error Insert ID or error
     */
    public static function save_kpi($name, $value, $date = null) {
        global $wpdb;
        
        if (empty($name)) {
            return new WP_Error('invalid_name', __('Metric name is required.', 'scm-plugin'));
        }
        
        if (!is_numeric($value)) {
            return new WP_Error('invalid_value', __('Metric value must be numeric.', 'scm-plugin'));
        }
        
        if (!$date) {
            $date = current_time('mysql');
        } else {
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$date_obj) {
                return new WP_Error('invalid_date', __('Invalid date format. Use YYYY-MM-DD.', 'scm-plugin'));
            }
        }
        
        $table = $wpdb->prefix . 'scm_kpis';
        
        $result = $wpdb->insert(
            $table,
            [
                'metric_name'  => sanitize_text_field($name),
                'metric_value' => floatval($value),
                'metric_date'  => $date
            ],
            ['%s', '%f', '%s']
        );
        
        if ($result === false) {
            return new WP_Error('save_failed', __('Failed to save KPI.', 'scm-plugin'));
        }
        
        do_action('scm_kpi_saved', $wpdb->insert_id, $name, $value);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get KPI history
     * 
     * @param string $metric_name
     * @param int    $days
     * @return array
     */
    public static function get_kpi_history($metric_name, $days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'scm_kpis';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE metric_name = %s 
             AND metric_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             ORDER BY metric_date DESC",
            sanitize_text_field($metric_name),
            absint($days)
        ), ARRAY_A);
    }
}