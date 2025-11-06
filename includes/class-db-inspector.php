
<?php
if (!defined('ABSPATH')) exit;


class SCM_DB_Inspector {


    public static function list_tables() {
        global $wpdb;
        return $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}scm_%'", ARRAY_N);
    }


    public static function describe_table($table_name) {
        global $wpdb;
        return $wpdb->get_results("DESCRIBE {$wpdb->prefix}{$table_name}", ARRAY_A);
    }
}
