<?php
if (!defined('ABSPATH')) exit;

/**
 * Register AJAX handlers
 */
add_action('wp_ajax_scm_upload_sales', 'scm_handle_sales_upload');

/**
 * Handle sales CSV upload via AJAX
 * 
 * Security: Nonce checked, capability checked, file validated
 */
function scm_handle_sales_upload() {
    // Security checks
    check_ajax_referer('scm_upload_sales', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('Unauthorized access.', 'scm-plugin')
        ]);
    }

    // Validate file upload
    if (!isset($_FILES['sales_csv']) || $_FILES['sales_csv']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error([
            'message' => __('File upload failed.', 'scm-plugin')
        ]);
    }

    $file = $_FILES['sales_csv'];
    
    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        wp_send_json_error([
            'message' => __('File too large. Maximum size is 5MB.', 'scm-plugin')
        ]);
    }

    // Validate file extension
    $allowed_extensions = ['csv'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_extensions, true)) {
        wp_send_json_error([
            'message' => __('Invalid file type. Only CSV files are allowed.', 'scm-plugin')
        ]);
    }

    // Validate MIME type (more secure than extension check)
    $allowed_mimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_mimes, true)) {
        wp_send_json_error([
            'message' => __('Invalid file MIME type. File may be corrupted or not a valid CSV.', 'scm-plugin')
        ]);
    }

    // Process CSV
    $result = scm_process_sales_csv($file['tmp_name']);
    
    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => $result->get_error_message()
        ]);
    }
    
    wp_send_json_success($result);
}

/**
 * Process sales CSV file
 * 
 * @param string $file_path Path to uploaded CSV
 * @return array|WP_Error Processing results or error
 */
function scm_process_sales_csv($file_path) {
    global $wpdb;
    
    $handle = fopen($file_path, 'r');
    if ($handle === false) {
        return new WP_Error('file_open_failed', __('Could not open file.', 'scm-plugin'));
    }

    // Read and validate header
    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        return new WP_Error('empty_file', __('File is empty.', 'scm-plugin'));
    }
    
    $header = array_map('trim', array_map('strtolower', $header));
    $expected_headers = ['sale_date', 'product_id', 'quantity', 'price'];
    
    if ($header !== $expected_headers) {
        fclose($handle);
        return new WP_Error(
            'invalid_header',
            sprintf(
                __('Invalid CSV header. Expected: %s. Got: %s', 'scm-plugin'),
                implode(', ', $expected_headers),
                implode(', ', $header)
            )
        );
    }

    $sales_table = $wpdb->prefix . 'scm_sales';
    $sales_data_per_product = [];
    $row_number = 1;
    $inserted_count = 0;
    $skipped_count = 0;
    $errors = [];

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $row_number++;
            
            if (count($row) !== 4) {
                $skipped_count++;
                $errors[] = sprintf(__('Row %d: Invalid column count', 'scm-plugin'), $row_number);
                continue;
            }

            list($date, $product_id, $quantity, $price) = array_map('trim', $row);

            // Validate date format
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
                $skipped_count++;
                $errors[] = sprintf(__('Row %d: Invalid date format (use YYYY-MM-DD)', 'scm-plugin'), $row_number);
                continue;
            }

            // Validate product_id
            if (empty($product_id) || strlen($product_id) > 50) {
                $skipped_count++;
                $errors[] = sprintf(__('Row %d: Invalid product ID', 'scm-plugin'), $row_number);
                continue;
            }

            // Validate quantity
            if (!is_numeric($quantity) || floatval($quantity) <= 0) {
                $skipped_count++;
                $errors[] = sprintf(__('Row %d: Quantity must be positive', 'scm-plugin'), $row_number);
                continue;
            }

            // Validate price
            if (!is_numeric($price) || floatval($price) < 0) {
                $skipped_count++;
                $errors[] = sprintf(__('Row %d: Price must be non-negative', 'scm-plugin'), $row_number);
                continue;
            }

            $product_id = sanitize_text_field($product_id);
            $quantity = floatval($quantity);
            $price = floatval($price);
            $revenue = $quantity * $price;

            // Insert into database
            $inserted = $wpdb->insert(
                $sales_table,
                [
                    'sale_date'  => $date,
                    'product_id' => $product_id,
                    'quantity'   => $quantity,
                    'price'      => $price,
                    'revenue'    => $revenue,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%s', '%s', '%f', '%f', '%f', '%s', '%s']
            );

            if ($inserted === false) {
                throw new Exception(
                    sprintf(__('Row %d: Database insert failed: %s', 'scm-plugin'), $row_number, $wpdb->last_error)
                );
            }

            $inserted_count++;

            // Collect data for forecasting
            if (!isset($sales_data_per_product[$product_id])) {
                $sales_data_per_product[$product_id] = [
                    'quantities' => [],
                    'dates' => []
                ];
            }
            $sales_data_per_product[$product_id]['quantities'][] = $quantity;
            $sales_data_per_product[$product_id]['dates'][] = $date;
        }

        if ($inserted_count === 0) {
            throw new Exception(__('No valid sales data found in CSV.', 'scm-plugin'));
        }

        $wpdb->query('COMMIT');

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        fclose($handle);
        return new WP_Error('processing_failed', $e->getMessage());
    }

    fclose($handle);

    // Generate forecasts
    require_once plugin_dir_path(__DIR__) . 'includes/class-forecasting.php';
    
    $forecast_results = [];
    $window_size = apply_filters('scm_forecast_window_size', 3);

    foreach ($sales_data_per_product as $prod_id => $data) {
        $quantities = $data['quantities'];
        $dates = $data['dates'];
        
        if (count($quantities) < $window_size) {
            continue;
        }

        $last_date = end($dates);
        $forecasts = SCM_Forecasting::moving_average_forecast($quantities, $window_size);
        
        if (is_wp_error($forecasts)) {
            $errors[] = sprintf(
                __('Forecast error for %s: %s', 'scm-plugin'),
                $prod_id,
                $forecasts->get_error_message()
            );
            continue;
        }

        $save_result = SCM_Forecasting::save_forecasts($prod_id, $forecasts, $last_date);
        
        if (!is_wp_error($save_result)) {
            $forecast_results[$prod_id] = [
                'actual' => $quantities,
                'forecast' => $forecasts,
                'last_date' => $last_date
            ];
        }
    }

    // Clear relevant caches
    wp_cache_delete('scm_dashboard_stats', 'scm_plugin');

    return [
        'inserted' => $inserted_count,
        'skipped' => $skipped_count,
        'forecasts' => $forecast_results,
        'errors' => array_slice($errors, 0, 10), // Limit to first 10 errors
        'message' => sprintf(
            __('Successfully imported %d rows. %d rows skipped.', 'scm-plugin'),
            $inserted_count,
            $skipped_count
        )
    ];
}

/**
 * Get sales data for chart display
 */
add_action('wp_ajax_scm_get_sales_data', 'scm_get_sales_data_ajax');

function scm_get_sales_data_ajax() {
    check_ajax_referer('scm_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized', 'scm-plugin')]);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'scm_sales';
    
    $product_id = isset($_GET['product_id']) ? sanitize_text_field($_GET['product_id']) : '';
    $days = isset($_GET['days']) ? absint($_GET['days']) : 30;

    if (empty($product_id)) {
        wp_send_json_error(['message' => __('Product ID required', 'scm-plugin')]);
    }

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT sale_date, SUM(quantity) as total_quantity 
         FROM {$table} 
         WHERE product_id = %s 
         AND sale_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
         GROUP BY sale_date 
         ORDER BY sale_date ASC",
        $product_id,
        $days
    ), ARRAY_A);

    wp_send_json_success(['sales' => $results]);
}