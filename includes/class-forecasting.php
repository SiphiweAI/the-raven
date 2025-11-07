<?php
if (!defined('ABSPATH')) exit;

class SCM_Forecasting {

    /**
     * Simple Moving Average Forecast
     * 
     * @param array $sales_data Array of numeric sales values
     * @param int   $window_size Moving average window
     * @return array|WP_Error Forecast values or error
     */
    public static function moving_average_forecast($sales_data, $window_size = 3) {
        // Validate inputs
        if (!is_array($sales_data) || empty($sales_data)) {
            return new WP_Error('invalid_data', __('Sales data must be a non-empty array.', 'scm-plugin'));
        }
        
        if (!is_int($window_size) || $window_size < 1) {
            return new WP_Error('invalid_window', __('Window size must be a positive integer.', 'scm-plugin'));
        }
        
        $count = count($sales_data);
        
        if ($count < $window_size) {
            return new WP_Error(
                'insufficient_data',
                sprintf(
                    __('Insufficient data: %d points required, %d provided.', 'scm-plugin'),
                    $window_size,
                    $count
                )
            );
        }

        $forecasts = [];
        
        for ($i = $window_size; $i <= $count; $i++) {
            $window = array_slice($sales_data, $i - $window_size, $window_size);
            $average = array_sum($window) / $window_size;
            $forecasts[] = round($average, 2);
        }

        return $forecasts;
    }

    /**
     * Exponential Smoothing Forecast
     * 
     * @param array $sales_data
     * @param float $alpha Smoothing factor (0-1)
     * @return array|WP_Error
     */
    public static function exponential_smoothing_forecast($sales_data, $alpha = 0.3) {
        if (!is_array($sales_data) || empty($sales_data)) {
            return new WP_Error('invalid_data', __('Sales data must be a non-empty array.', 'scm-plugin'));
        }
        
        if ($alpha < 0 || $alpha > 1) {
            return new WP_Error('invalid_alpha', __('Alpha must be between 0 and 1.', 'scm-plugin'));
        }
        
        $forecasts = [];
        $forecasts[0] = $sales_data[0]; // Initialize with first value
        
        for ($i = 1; $i < count($sales_data); $i++) {
            $forecasts[$i] = $alpha * $sales_data[$i - 1] + (1 - $alpha) * $forecasts[$i - 1];
        }
        
        return $forecasts;
    }

    /**
     * Save forecast results to database
     * 
     * @param string      $product_id
     * @param array       $forecasts
     * @param string|null $last_sale_date YYYY-MM-DD format
     * @param string      $model Model name
     * @return bool|WP_Error Success status or error
     */
    public static function save_forecasts($product_id, $forecasts, $last_sale_date = null, $model = 'Moving Average') {
        global $wpdb;
        
        // Validate inputs
        if (empty($product_id)) {
            return new WP_Error('invalid_product', __('Product ID is required.', 'scm-plugin'));
        }
        
        if (!is_array($forecasts) || empty($forecasts)) {
            return new WP_Error('empty_forecasts', __('Forecasts array cannot be empty.', 'scm-plugin'));
        }
        
        // Validate/set date
        if (!$last_sale_date) {
            $last_sale_date = current_time('Y-m-d');
        } else {
            $date_obj = DateTime::createFromFormat('Y-m-d', $last_sale_date);
            if (!$date_obj || $date_obj->format('Y-m-d') !== $last_sale_date) {
                return new WP_Error('invalid_date', __('Invalid date format. Use YYYY-MM-DD.', 'scm-plugin'));
            }
        }
        
        $table = $wpdb->prefix . 'scm_forecasts';
        $product_id = sanitize_text_field($product_id);
        $model = sanitize_text_field($model);
        
        $success_count = 0;
        $errors = [];
        
        // Use transaction for atomic operation
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($forecasts as $index => $forecast_qty) {
                if (!is_numeric($forecast_qty) || $forecast_qty < 0) {
                    throw new Exception("Invalid forecast value at index {$index}: {$forecast_qty}");
                }
                
                $forecast_date = date('Y-m-d', strtotime("+{$index} day", strtotime($last_sale_date)));
                
                $result = $wpdb->insert(
                    $table,
                    [
                        'product_id'    => $product_id,
                        'forecast_date' => $forecast_date,
                        'forecast_qty'  => floatval($forecast_qty),
                        'model_used'    => $model,
                        'created_at'    => current_time('mysql')
                    ],
                    ['%s', '%s', '%f', '%s', '%s']
                );
                
                if ($result === false) {
                    throw new Exception("Database insert failed: {$wpdb->last_error}");
                }
                
                $success_count++;
            }
            
            $wpdb->query('COMMIT');
            
            do_action('scm_forecasts_saved', $product_id, $success_count, $model);
            
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log("SCM Forecast Save Error: " . $e->getMessage());
            
            return new WP_Error(
                'save_failed',
                sprintf(
                    __('Failed to save forecasts: %s', 'scm-plugin'),
                    $e->getMessage()
                )
            );
        }
    }
    
    /**
     * Get forecasts for a product
     * 
     * @param string $product_id
     * @param int    $days Number of days to retrieve
     * @return array|WP_Error
     */
    public static function get_forecasts($product_id, $days = 30) {
        global $wpdb;
        
        if (empty($product_id)) {
            return new WP_Error('invalid_product', __('Product ID is required.', 'scm-plugin'));
        }
        
        $table = $wpdb->prefix . 'scm_forecasts';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE product_id = %s 
             AND forecast_date >= CURDATE()
             ORDER BY forecast_date ASC 
             LIMIT %d",
            sanitize_text_field($product_id),
            absint($days)
        ), ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Delete old forecasts
     * 
     * @param int $days_old Delete forecasts older than X days
     * @return int|false Number of rows deleted
     */
    public static function cleanup_old_forecasts($days_old = 90) {
        global $wpdb;
        $table = $wpdb->prefix . 'scm_forecasts';
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE forecast_date < DATE_SUB(CURDATE(), INTERVAL %d DAY)",
            absint($days_old)
        ));
    }
}