<?php
if (!defined('ABSPATH')) exit;


class SCM_Forecasting {


    /**
     * Simple Moving Average Forecast
     * @param array $sales_data (array of numbers)
     * @param int $window_size
     * @return array
     */
    public static function moving_average_forecast($sales_data, $window_size = 3) {
        $forecasts = [];
        $count = count($sales_data);


        if ($count < $window_size) return [];


        for ($i = $window_size; $i <= $count; $i++) {
            $window = array_slice($sales_data, $i - $window_size, $window_size);
            $forecasts[] = array_sum($window) / $window_size;
        }


        return $forecasts;
    }


    /**
     * Insert forecast results into DB
     * @param string $product_id
     * @param array $forecasts
     * @param string|null $last_sale_date (YYYY-MM-DD)
     * @param string $model
     */
    public static function save_forecasts($product_id, $forecasts, $last_sale_date = null, $model = 'Moving Average') {
        if (empty($forecasts)) return;


        global $wpdb;
        $table = $wpdb->prefix . 'scm_forecasts';


        if (!$last_sale_date) {
            $last_sale_date = date('Y-m-d'); // default to today
        }


        foreach ($forecasts as $index => $forecast_qty) {
            $forecast_date = date('Y-m-d', strtotime("+$index day", strtotime($last_sale_date)));
            $wpdb->insert($table, [
                'product_id'    => sanitize_text_field($product_id),
                'forecast_date' => $forecast_date,
                'forecast_qty'  => floatval($forecast_qty),
                'model_used'    => $model
            ]);
        }
    }
}