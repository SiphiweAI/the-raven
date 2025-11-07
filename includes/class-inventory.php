<?php
if (!defined('ABSPATH')) exit;

class SCM_Inventory {

    /**
     * Economic Order Quantity (EOQ)
     * Formula: EOQ = sqrt((2 * D * S) / H)
     * 
     * @param float $annual_demand   Annual demand in units
     * @param float $order_cost      Cost per order
     * @param float $holding_cost    Annual holding cost per unit
     * @return float|WP_Error        EOQ value or error
     */
    public static function calculate_eoq($annual_demand, $order_cost, $holding_cost) {
        // Validate inputs
        if (!is_numeric($annual_demand) || $annual_demand <= 0) {
            return new WP_Error('invalid_demand', __('Annual demand must be a positive number.', 'scm-plugin'));
        }
        
        if (!is_numeric($order_cost) || $order_cost <= 0) {
            return new WP_Error('invalid_order_cost', __('Order cost must be a positive number.', 'scm-plugin'));
        }
        
        if (!is_numeric($holding_cost) || $holding_cost <= 0) {
            return new WP_Error('invalid_holding_cost', __('Holding cost must be a positive number.', 'scm-plugin'));
        }
        
        $eoq = sqrt((2 * $annual_demand * $order_cost) / $holding_cost);
        
        return round($eoq, 2);
    }

    /**
     * Reorder Point (ROP)
     * Formula: ROP = (daily demand × lead time) + safety stock
     * 
     * @param float $daily_demand
     * @param int   $lead_time      Lead time in days
     * @param float $safety_stock
     * @return float|WP_Error
     */
    public static function calculate_rop($daily_demand, $lead_time, $safety_stock = 0) {
        if (!is_numeric($daily_demand) || $daily_demand < 0) {
            return new WP_Error('invalid_demand', __('Daily demand must be a non-negative number.', 'scm-plugin'));
        }
        
        if (!is_numeric($lead_time) || $lead_time < 0) {
            return new WP_Error('invalid_lead_time', __('Lead time must be a non-negative number.', 'scm-plugin'));
        }
        
        if (!is_numeric($safety_stock) || $safety_stock < 0) {
            return new WP_Error('invalid_safety_stock', __('Safety stock must be a non-negative number.', 'scm-plugin'));
        }
        
        $rop = ($daily_demand * $lead_time) + $safety_stock;
        
        return round($rop, 2);
    }

    /**
     * Safety Stock Calculation
     * Formula: SS = Z × σ_demand × sqrt(lead_time)
     * 
     * @param float $z_value          Z-score for service level (e.g., 1.65 for 95%)
     * @param float $demand_std_dev   Standard deviation of demand
     * @param int   $lead_time        Lead time in days
     * @return float|WP_Error
     */
    public static function calculate_safety_stock($z_value, $demand_std_dev, $lead_time) {
        if (!is_numeric($z_value) || $z_value < 0) {
            return new WP_Error('invalid_z_value', __('Z-value must be a non-negative number.', 'scm-plugin'));
        }
        
        if (!is_numeric($demand_std_dev) || $demand_std_dev < 0) {
            return new WP_Error('invalid_std_dev', __('Standard deviation must be a non-negative number.', 'scm-plugin'));
        }
        
        if (!is_numeric($lead_time) || $lead_time < 0) {
            return new WP_Error('invalid_lead_time', __('Lead time must be a non-negative number.', 'scm-plugin'));
        }
        
        $safety_stock = $z_value * $demand_std_dev * sqrt($lead_time);
        
        return round($safety_stock, 2);
    }

    /**
     * Save inventory calculations to database
     * 
     * @param string $product_id
     * @param float  $eoq
     * @param float  $rop
     * @param float  $safety_stock
     * @return int|WP_Error Insert ID or error
     */
    public static function save_inventory($product_id, $eoq, $rop, $safety_stock) {
        global $wpdb;
        
        // Validate inputs
        if (empty($product_id)) {
            return new WP_Error('invalid_product', __('Product ID is required.', 'scm-plugin'));
        }
        
        if (!is_numeric($eoq) || $eoq < 0) {
            return new WP_Error('invalid_eoq', __('EOQ must be a non-negative number.', 'scm-plugin'));
        }
        
        if (!is_numeric($rop) || $rop < 0) {
            return new WP_Error('invalid_rop', __('ROP must be a non-negative number.', 'scm-plugin'));
        }
        
        if (!is_numeric($safety_stock) || $safety_stock < 0) {
            return new WP_Error('invalid_safety_stock', __('Safety stock must be a non-negative number.', 'scm-plugin'));
        }
        
        $table = $wpdb->prefix . 'scm_inventory';
        
        // Check if record exists, update or insert
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE product_id = %s ORDER BY id DESC LIMIT 1",
            sanitize_text_field($product_id)
        ));
        
        $data = [
            'product_id'    => sanitize_text_field($product_id),
            'eoq'           => floatval($eoq),
            'rop'           => floatval($rop),
            'safety_stock'  => floatval($safety_stock),
            'updated_at'    => current_time('mysql')
        ];
        
        $format = ['%s', '%f', '%f', '%f', '%s'];
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $table,
                $data,
                ['id' => $existing],
                $format,
                ['%d']
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', __('Failed to update inventory data.', 'scm-plugin'));
            }
            
            do_action('scm_inventory_updated', $existing, $product_id);
            return $existing;
            
        } else {
            // Insert new record
            $data['created_at'] = current_time('mysql');
            $format[] = '%s';
            
            $result = $wpdb->insert($table, $data, $format);
            
            if ($result === false) {
                return new WP_Error('insert_failed', __('Failed to save inventory data.', 'scm-plugin'));
            }
            
            $insert_id = $wpdb->insert_id;
            do_action('scm_inventory_created', $insert_id, $product_id);
            
            return $insert_id;
        }
    }
    
    /**
     * Get inventory data for a product
     * 
     * @param string $product_id
     * @return object|null
     */
    public static function get_inventory($product_id) {
        global $wpdb;
        
        if (empty($product_id)) {
            return null;
        }
        
        $table = $wpdb->prefix . 'scm_inventory';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %s ORDER BY id DESC LIMIT 1",
            sanitize_text_field($product_id)
        ));
    }
    
    /**
     * Calculate current stock status
     * 
     * @param float $current_stock
     * @param float $rop
     * @return string Status: 'ok', 'warning', 'critical'
     */
    public static function get_stock_status($current_stock, $rop) {
        if ($current_stock <= 0) {
            return 'critical';
        } elseif ($current_stock <= $rop) {
            return 'warning';
        }
        return 'ok';
    }
}