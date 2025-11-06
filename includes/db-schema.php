<?php
function scm_saas_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sales = $wpdb->prefix . 'scm_sales';
    $forecasts = $wpdb->prefix . 'scm_forecasts';
    $inventory = $wpdb->prefix . 'scm_inventory';
    $suppliers = $wpdb->prefix . 'scm_suppliers';
    $kpis = $wpdb->prefix . 'scm_kpis';

    $sql = "
    CREATE TABLE IF NOT EXISTS $sales (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id VARCHAR(50),
        sale_date DATE,
        quantity INT,
        price DECIMAL(10,2)
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $forecasts (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id VARCHAR(50),
        forecast_date DATE,
        forecast_qty DECIMAL(10,2),
        model_used VARCHAR(50),
        UNIQUE KEY product_forecast_date (product_id, forecast_date),
        INDEX idx_product_id (product_id)
) $charset_collate;

    CREATE TABLE IF NOT EXISTS $inventory (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id VARCHAR(50),
        eoq DECIMAL(10,2),
        rop DECIMAL(10,2),
        safety_stock DECIMAL(10,2)
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $suppliers (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        supplier_name VARCHAR(100),
        on_time DECIMAL(5,2),
        cost_variance DECIMAL(5,2),
        defect_rate DECIMAL(5,2),
        avg_lead_time DECIMAL(10,2)
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS $kpis (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        metric_name VARCHAR(100),
        metric_value DECIMAL(10,2),
        metric_date DATE
    ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
