
<?php
if (!defined('ABSPATH')) exit;


function scm_handle_sales_upload() {
    if (!isset($_FILES['sales_csv']) || $_FILES['sales_csv']['error'] !== UPLOAD_ERR_OK) {
        echo "<p style='color:red;'>File upload failed.</p>";
        return;
    }


    // Security: Check nonce
    if (!isset($_POST['scm_upload_nonce']) || !wp_verify_nonce($_POST['scm_upload_nonce'], 'scm_upload_action')) {
        echo "<p style='color:red;'>Security check failed.</p>";
        return;
    }


    $file = $_FILES['sales_csv']['tmp_name'];
    $file_ext = strtolower(pathinfo($_FILES['sales_csv']['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        echo "<p style='color:red;'>Invalid file type. Please upload a CSV.</p>";
        return;
    }


    $handle = fopen($file, 'r');
    if ($handle === false) {
        echo "<p style='color:red;'>Could not open file.</p>";
        return;
    }


    global $wpdb;
    $sales_table    = $wpdb->prefix . 'scm_sales';


    // Skip header
    $header = fgetcsv($handle);
    if (!$header || count($header) < 4) {
        echo "<p style='color:red;'>Invalid CSV format.</p>";
        fclose($handle);
        return;
    }


    $sales_data_per_product = [];


    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        list($date, $product_id, $quantity, $price) = $row;


        $date       = sanitize_text_field($date);
        $product_id = sanitize_text_field($product_id);
        $quantity   = floatval($quantity);
        $price      = floatval($price);


        if (!$date || !$product_id || $quantity <= 0) continue;


        $inserted = $wpdb->insert($sales_table, [
            'sale_date'  => $date,
            'product_id' => $product_id,
            'quantity'   => $quantity,
            'price'      => $price
        ]);


        if ($inserted === false) {
            echo "<p style='color:red;'>Database insert failed for product {$product_id}: {$wpdb->last_error}</p>";
            continue;
        }


        if (!isset($sales_data_per_product[$product_id])) {
            $sales_data_per_product[$product_id] = [
                'quantities' => [],
                'dates'      => []
            ];
        }


        $sales_data_per_product[$product_id]['quantities'][] = $quantity;
        $sales_data_per_product[$product_id]['dates'][]      = $date;
    }


    fclose($handle);


    if (empty($sales_data_per_product)) {
        echo "<p style='color:red;'>No valid sales data found.</p>";
        return;
    }


    require_once plugin_dir_path(__DIR__) . 'includes/class-forecasting.php';


    $all_forecast_results = [];


    foreach ($sales_data_per_product as $product_id => $data) {
        $quantities = $data['quantities'];
        $dates      = $data['dates'];
        $last_date  = end($dates);


        if (count($quantities) < 3) {
            echo "<p style='color:red;'>Not enough data to forecast for product {$product_id}.</p>";
            continue;
        }


        $forecasts = SCM_Forecasting::moving_average_forecast($quantities, 3);
        SCM_Forecasting::save_forecasts($product_id, $forecasts, $last_date);


        $all_forecast_results[$product_id] = [
            'actual'   => $quantities,
            'forecast' => $forecasts
        ];
    }


    echo "<script>
        const forecastResults = " . wp_json_encode($all_forecast_results) . ";
    </script>";


    echo "<p style='color:green;'>Data uploaded and forecasts generated successfully!</p>";
}
