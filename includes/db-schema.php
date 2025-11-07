<?php
if (!defined('ABSPATH')) exit;

/**
 * Create or update SCM database tables
 * 
 * IMPORTANT: dbDelta is very particular about formatting:
 * - Must have 2 spaces after PRIMARY KEY
 * - Must have KEY definition on separate line
 * - No spaces around default values
 */
function scm_saas_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sales_table = $wpdb->prefix . 'scm_sales';
    $forecasts_table = $wpdb->prefix . 'scm_forecasts';
    $inventory_table = $wpdb->prefix . 'scm_inventory';
    $suppliers_table = $wpdb->prefix . 'scm_suppliers';
    $kpis_table = $wpdb->prefix . 'scm_kpis';

    // Sales History Table
    $sql_sales = "CREATE TABLE {$sales_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id VARCHAR(50) NOT NULL,
        sale_date DATE NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        revenue DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY product_date (product_id,sale_date),
        KEY sale_date (sale_date)
    ) {$charset_collate};";

    // Forecasts Table
    $sql_forecasts = "CREATE TABLE {$forecasts_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id VARCHAR(50) NOT NULL,
        forecast_date DATE NOT NULL,
        forecast_qty DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        model_used VARCHAR(50) NOT NULL DEFAULT 'Unknown',
        confidence_lower DECIMAL(10,2) DEFAULT NULL,
        confidence_upper DECIMAL(10,2) DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY product_forecast (product_id,forecast_date),
        KEY forecast_date (forecast_date),
        KEY model_used (model_used)
    ) {$charset_collate};";

    // Inventory Table
    $sql_inventory = "CREATE TABLE {$inventory_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id VARCHAR(50) NOT NULL,
        current_stock DECIMAL(10,2) DEFAULT 0.00,
        eoq DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        rop DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        safety_stock DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        annual_demand DECIMAL(10,2) DEFAULT NULL,
        order_cost DECIMAL(10,2) DEFAULT NULL,
        holding_cost DECIMAL(10,2) DEFAULT NULL,
        lead_time_days INT DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY product_id (product_id),
        KEY updated_at (updated_at)
    ) {$charset_collate};";

    // Suppliers Table
    $sql_suppliers = "CREATE TABLE {$suppliers_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        supplier_name VARCHAR(100) NOT NULL,
        on_time DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        cost_variance DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        defect_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        avg_lead_time DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        performance_score DECIMAL(5,2) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY supplier_name (supplier_name),
        KEY performance_score (performance_score),
        KEY status (status)
    ) {$charset_collate};";

    // KPIs Table
    $sql_kpis = "CREATE TABLE {$kpis_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        metric_name VARCHAR(100) NOT NULL,
        metric_value DECIMAL(10,2) NOT NULL,
        metric_date DATE NOT NULL,
        metadata TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY metric_name_date (metric_name,metric_date),
        KEY metric_date (metric_date)
    ) {$charset_collate};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_sales);
    dbDelta($sql_forecasts);
    dbDelta($sql_inventory);
    dbDelta($sql_suppliers);
    dbDelta($sql_kpis);
    
    // Store current database version
    update_option('scm_db_version', '1.0.0');
    
    // Log table creation
    do_action('scm_tables_created');
}

/**
 * Drop all SCM tables (use with caution!)
 */
function scm_saas_drop_tables() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'scm_sales',
        $wpdb->prefix . 'scm_forecasts',
        $wpdb->prefix . 'scm_inventory',
        $wpdb->prefix . 'scm_suppliers',
        $wpdb->prefix . 'scm_kpis'
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    delete_option('scm_db_version');
    
    do_action('scm_tables_dropped');
}

/**
 * Check if tables exist
 * 
 * @return bool
 */
function scm_saas_tables_exist() {
    global $wpdb;
    
    $sales_table = $wpdb->prefix . 'scm_sales';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sales_table}'");
    
    return ($table_exists === $sales_table);
}

/**
 * Database migration handler
 * Call this on plugin update to handle schema changes
 */
function scm_saas_maybe_upgrade_db() {
    $current_version = get_option('scm_db_version', '0.0.0');
    $target_version = '1.0.0';
    
    if (version_compare($current_version, $target_version, '<')) {
        scm_saas_create_tables();
        
        // Run migrations based on version
        if (version_compare($current_version, '1.0.0', '<')) {
            // Future migrations here
        }
        
        update_option('scm_db_version', $target_version);
    }
}

/**
 * Seed sample data for testing
 * Only use in development!
 */
function scm_saas_seed_sample_data() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return new WP_Error('not_debug_mode', 'Sample data can only be seeded in debug mode.');
    }
    
    global $wpdb;
    
    // Seed sales data
    $sales_table = $wpdb->prefix . 'scm_sales';
    $products = ['P001', 'P002', 'P003'];
    
    for ($i = 30; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        foreach ($products as $product) {
            $qty = rand(10, 50);
            $price = rand(100, 500) / 10;
            
            $wpdb->insert($sales_table, [
                'product_id' => $product,
                'sale_date' => $date,
                'quantity' => $qty,
                'price' => $price,
                'revenue' => $qty * $price,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
        }
    }
    
    // Seed supplier data
    $suppliers = [
        ['name' => 'Supplier A', 'on_time' => 95, 'cost_var' => 5, 'defects' => 2, 'lead_time' => 7],
        ['name' => 'Supplier B', 'on_time' => 85, 'cost_var' => 10, 'defects' => 5, 'lead_time' => 10],
        ['name' => 'Supplier C', 'on_time' => 90, 'cost_var' => 8, 'defects' => 3, 'lead_time' => 8]
    ];
    
    foreach ($suppliers as $supplier) {
        SCM_Suppliers::save_supplier(
            $supplier['name'],
            $supplier['on_time'],
            $supplier['cost_var'],
            $supplier['defects'],
            $supplier['lead_time']
        );
    }
    
    do_action('scm_sample_data_seeded');
    
    return true;
}