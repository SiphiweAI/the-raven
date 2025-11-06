<?php
if (!defined('ABSPATH')) exit;

function scm_handle_sales_upload() {
    if (!isset($_FILES['sales_csv']) || $_FILES['sales_csv']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('File upload failed.');
    }

    if (!isset($_POST['scm_upload_nonce']) || !wp_verify_nonce($_POST['scm_upload_nonce'], 'scm_upload_action')) {
        wp_send_json_error('Security check failed.');
    }

    $file = $_FILES['sales_csv']['tmp_name'];
    $file_ext = strtolower(pathinfo($_FILES['sales_csv']['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        wp_send_json_error('Invalid file type. Please upload a CSV.');
    }

    $handle = fopen($file, 'r');
    if ($handle === false) {
        wp_send_json_error('Could not open file.');
    }

    // Validate header explicitly
    $header = fgetcsv($handle);
    $expected = ['sale_date', 'product_id', 'quantity', 'price'];
    if (array_map('strtolower', $header) !== $expected) {
        fclose($handle);
        wp_send_json_error('Invalid CSV header. Expected columns: ' . implode(', ', $expected));
    }

    global $wpdb;
    $sales_table = $wpdb->prefix . 'scm_sales';

    $wpdb->query('START TRANSACTION');

    $sales_data_per_product = [];

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        list($date, $product_id, $quantity, $price) = $row;
        $date = sanitize_text_field($date);
        $product_id = sanitize_text_field($product_id);
        $quantity = floatval($quantity);
        $price = floatval($price);

        // Validate required fields & data ranges
        if (!$date || !$product_id || $quantity <= 0) continue;

        $inserted = $wpdb->insert($sales_table, [
            'sale_date' => $date,
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $price
        ]);

        if ($inserted === false) {
            $wpdb->query('ROLLBACK');
            fclose($handle);
            wp_send_json_error("DB insert failed: {$wpdb->last_error}");
        }

        if (!isset($sales_data_per_product[$product_id])) {
            $sales_data_per_product[$product_id] = ['quantities' => [], 'dates' => []];
        }
        $sales_data_per_product[$product_id]['quantities'][] = $quantity;
        $sales_data_per_product[$product_id]['dates'][] = $date;
    }

    fclose($handle);
    $wpdb->query('COMMIT');

    if (empty($sales_data_per_product)) {
        wp_send_json_error('No valid sales data found.');
    }

    require_once plugin_dir_path(__DIR__) . 'includes/class-forecasting.php';

    $all_forecast_results = [];

    foreach ($sales_data_per_product as $prod_id => $data) {
        $quantities = $data['quantities'];
        $dates = $data['dates'];
        $last_date = end($dates);

        if (count($quantities) < 3) {
            continue;
        }

        // Allow configurable window size
        $forecasts = SCM_Forecasting::moving_average_forecast($quantities, 3);
        SCM_Forecasting::save_forecasts($prod_id, $forecasts, $last_date);

        $all_forecast_results[$prod_id] = ['actual' => $quantities, 'forecast' => $forecasts];
    }

    wp_send_json_success(['forecastResults' => $all_forecast_results]);
}
