<?php
if (!defined('ABSPATH')) exit;

/**
 * Register background task for AI forecasting
 */
add_action('scm_run_ai_forecast_event', 'scm_run_ai_forecast_callback');

/**
 * Run AI forecast in background
 * This executes when the scheduled event fires
 */
function scm_run_ai_forecast_callback() {
    global $wpdb;
    
    $log_file = plugin_dir_path(__FILE__) . '../ai/forecast_log.txt';
    $start_time = microtime(true);
    
    // Log start
    scm_log_forecast('Starting AI forecast process...', $log_file);
    
    try {
        // Step 1: Export sales data
        $sales_table = $wpdb->prefix . 'scm_sales';
        $product_id = apply_filters('scm_ai_forecast_product_id', 'P001');
        
        $sales_data = $wpdb->get_results($wpdb->prepare(
            "SELECT sale_date as ds, quantity as y, product_id 
             FROM {$sales_table} 
             WHERE product_id = %s 
             ORDER BY sale_date ASC",
            $product_id
        ), ARRAY_A);
        
        if (empty($sales_data)) {
            throw new Exception('No sales data found for product ' . $product_id);
        }
        
        scm_log_forecast(sprintf('Fetched %d sales records', count($sales_data)), $log_file);
        
        // Step 2: Save to JSON for Python
        $export_file = plugin_dir_path(__FILE__) . '../ai/sales_export.json';
        $json_result = file_put_contents($export_file, json_encode($sales_data, JSON_PRETTY_PRINT));
        
        if ($json_result === false) {
            throw new Exception('Failed to write sales export file');
        }
        
        scm_log_forecast('Sales data exported to JSON', $log_file);
        
        // Step 3: Run Python script
        $python_path = apply_filters('scm_python_path', '/usr/bin/python3');
        $script_path = plugin_dir_path(__FILE__) . '../ai/prophet_forecast.py';
        
        // Validate paths exist
        if (!file_exists($python_path)) {
            throw new Exception('Python executable not found at: ' . $python_path);
        }
        
        if (!file_exists($script_path)) {
            throw new Exception('Prophet script not found at: ' . $script_path);
        }
        
        // Change to AI directory for execution
        $ai_dir = plugin_dir_path(__FILE__) . '../ai/';
        $old_dir = getcwd();
        chdir($ai_dir);
        
        // Execute Python script with proper error handling
        $command = escapeshellcmd($python_path) . ' ' . escapeshellarg(basename($script_path)) . ' 2>&1';
        exec($command, $output, $return_code);
        
        chdir($old_dir);
        
        $output_text = implode("\n", $output);
        scm_log_forecast('Python output: ' . $output_text, $log_file);
        
        if ($return_code !== 0) {
            throw new Exception('Python script failed with code ' . $return_code . ': ' . $output_text);
        }
        
        // Step 4: Verify forecast was generated
        $forecast_file = plugin_dir_path(__FILE__) . '../ai/forecast_' . $product_id . '.json';
        
        if (!file_exists($forecast_file)) {
            throw new Exception('Forecast file was not generated');
        }
        
        $forecast_data = json_decode(file_get_contents($forecast_file), true);
        
        if (!$forecast_data) {
            throw new Exception('Invalid forecast data generated');
        }
        
        scm_log_forecast(sprintf('Successfully generated %d forecast points', count($forecast_data)), $log_file);
        
        // Step 5: Save to database
        require_once plugin_dir_path(__DIR__) . 'includes/class-forecasting.php';
        
        $forecast_values = array_column($forecast_data, 'yhat');
        $last_sale_date = end($sales_data)['ds'];
        
        $save_result = SCM_Forecasting::save_forecasts(
            $product_id,
            $forecast_values,
            $last_sale_date,
            'Prophet AI'
        );
        
        if (is_wp_error($save_result)) {
            throw new Exception('Failed to save forecasts: ' . $save_result->get_error_message());
        }
        
        $duration = round(microtime(true) - $start_time, 2);
        scm_log_forecast(sprintf('AI forecast completed successfully in %s seconds', $duration), $log_file);
        
        // Send success notification
        do_action('scm_ai_forecast_completed', $product_id, $forecast_data);
        
        return true;
        
    } catch (Exception $e) {
        $error_message = 'AI Forecast Error: ' . $e->getMessage();
        scm_log_forecast($error_message, $log_file);
        error_log($error_message);
        
        // Send error notification
        do_action('scm_ai_forecast_failed', $e->getMessage());
        
        return false;
    }
}

/**
 * Helper function to log forecast activity
 * 
 * @param string $message
 * @param string $log_file
 */
function scm_log_forecast($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = sprintf("[%s] %s\n", $timestamp, $message);
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Clear forecast log (admin action)
 */
add_action('wp_ajax_scm_clear_forecast_log', 'scm_clear_forecast_log');

function scm_clear_forecast_log() {
    check_ajax_referer('scm_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'scm-plugin')]);
    }
    
    $log_file = plugin_dir_path(__FILE__) . '../ai/forecast_log.txt';
    
    if (file_exists($log_file)) {
        unlink($log_file);
    }
    
    wp_send_json_success(['message' => __('Log cleared', 'scm-plugin')]);
}