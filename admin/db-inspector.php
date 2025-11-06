<?php
require_once plugin_dir_path(__FILE__) . '../includes/class-db-inspector.php';


$tables = SCM_DB_Inspector::list_tables();
foreach ($tables as $table) {
    echo "<li>{$table[0]}</li>";
}
