<?php
if (!defined('ABSPATH')) exit;

class SCM_DB_Inspector {
    
    /**
     * List all SCM plugin tables
     * 
     * @return array|null Array of table names or null on failure
     */
    public static function list_tables() {
        global $wpdb;
        
        try {
            $pattern = $wpdb->esc_like($wpdb->prefix . 'scm_') . '%';
            $tables = $wpdb->get_results(
                $wpdb->prepare("SHOW TABLES LIKE %s", $pattern),
                ARRAY_N
            );
            
            return $tables ?: [];
        } catch (Exception $e) {
            error_log('SCM DB Inspector Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Describe table structure
     * 
     * @param string $table_name Table name without prefix (e.g., 'scm_sales')
     * @return array|null Table structure or null on failure
     */
    public static function describe_table($table_name) {
        global $wpdb;
        
        // Whitelist allowed tables
        $allowed_tables = [
            'scm_sales',
            'scm_forecasts',
            'scm_inventory',
            'scm_suppliers',
            'scm_kpis'
        ];
        
        if (!in_array($table_name, $allowed_tables, true)) {
            error_log("SCM: Attempted to describe unauthorized table: {$table_name}");
            return null;
        }
        
        $full_table_name = $wpdb->prefix . $table_name;
        
        // Verify table exists
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->esc_like($full_table_name)
            )
        );
        
        if (!$table_exists) {
            return null;
        }
        
        // Safe to use table name from whitelist
        return $wpdb->get_results("DESCRIBE {$full_table_name}", ARRAY_A);
    }
    
    /**
     * Get table row count
     * 
     * @param string $table_name
     * @return int|null
     */
    public static function get_table_count($table_name) {
        global $wpdb;
        
        $allowed_tables = [
            'scm_sales',
            'scm_forecasts',
            'scm_inventory',
            'scm_suppliers',
            'scm_kpis'
        ];
        
        if (!in_array($table_name, $allowed_tables, true)) {
            return null;
        }
        
        $full_table_name = $wpdb->prefix . $table_name;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
    }
}