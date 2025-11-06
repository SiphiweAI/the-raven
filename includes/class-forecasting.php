<?php
if (!defined('ABSPATH')) exit;

class SCM_Forecasting {

    public static function save_forecasts($product_id, $forecasts, $last_sale_date = null, $model = 'Moving Average') {
        if (empty($forecasts)) return;

        global $wpdb;
        $table = $wpdb->prefix . 'scm_forecasts';

        if (!$last_sale_date) {
            $last_sale_date = date('Y-m-d');
        }

        foreach ($forecasts as $index => $forecast_qty) {
            $forecast_date = date('Y-m-d', strtotime("+$index day", strtotime($last_sale_date)));

            // Check if record exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE product_id=%s AND forecast_date=%s", 
                $product_id, $forecast_date
            ));

            if ($exists) {
                $wpdb->update(
                    $table,
                    ['forecast_qty' => floatval($forecast_qty), 'model_used' => $model],
                    ['id' => $exists],
                    ['%f', '%s'],
                    ['%d']
                );
            } else {
                $wpdb->insert(
                    $table,
                    [
                        'product_id' => sanitize_text_field($product_id),
                        'forecast_date' => $forecast_date,
                        'forecast_qty' => floatval($forecast_qty),
                        'model_used' => $model
                    ]
                );
            }
        }
    }
}
