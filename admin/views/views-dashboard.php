<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Get statistics
$sales_table = $wpdb->prefix . 'scm_sales';
$forecasts_table = $wpdb->prefix . 'scm_forecasts';
$inventory_table = $wpdb->prefix . 'scm_inventory';
$suppliers_table = $wpdb->prefix . 'scm_suppliers';

$total_sales = $wpdb->get_var("SELECT COUNT(*) FROM {$sales_table}");
$total_forecasts = $wpdb->get_var("SELECT COUNT(*) FROM {$forecasts_table}");
$total_products = $wpdb->get_var("SELECT COUNT(DISTINCT product_id) FROM {$inventory_table}");
$total_suppliers = $wpdb->get_var("SELECT COUNT(*) FROM {$suppliers_table}");

// Get recent activity
$recent_sales = $wpdb->get_var("SELECT COUNT(*) FROM {$sales_table} WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
?>

<div class="wrap scm-wrap">
    <h1><?php esc_html_e('SCM SaaS Dashboard', 'scm-plugin'); ?></h1>
    <p class="scm-subtitle"><?php esc_html_e('Welcome to your Supply Chain Management Suite', 'scm-plugin'); ?></p>

    <div class="scm-stats-grid">
        <div class="scm-stat-card">
            <div class="scm-stat-icon">📊</div>
            <div class="scm-stat-content">
                <h3><?php echo esc_html(number_format($total_sales)); ?></h3>
                <p><?php esc_html_e('Total Sales Records', 'scm-plugin'); ?></p>
            </div>
        </div>
        
        <div class="scm-stat-card">
            <div class="scm-stat-icon">📈</div>
            <div class="scm-stat-content">
                <h3><?php echo esc_html(number_format($total_forecasts)); ?></h3>
                <p><?php esc_html_e('Forecast Points', 'scm-plugin'); ?></p>
            </div>
        </div>
        
        <div class="scm-stat-card">
            <div class="scm-stat-icon">📦</div>
            <div class="scm-stat-content">
                <h3><?php echo esc_html(number_format($total_products)); ?></h3>
                <p><?php esc_html_e('Products Managed', 'scm-plugin'); ?></p>
            </div>
        </div>
        
        <div class="scm-stat-card">
            <div class="scm-stat-icon">🤝</div>
            <div class="scm-stat-content">
                <h3><?php echo esc_html(number_format($total_suppliers)); ?></h3>
                <p><?php esc_html_e('Suppliers', 'scm-plugin'); ?></p>
            </div>
        </div>
    </div>

    <div class="scm-modules-grid">
        <div class="scm-module-card">
            <h2>📈 <?php esc_html_e('Demand Forecasting', 'scm-plugin'); ?></h2>
            <p><?php esc_html_e('Upload sales data and generate forecasts using moving averages or AI models.', 'scm-plugin'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=scm-saas-forecasting')); ?>" class="button button-primary">
                <?php esc_html_e('Open Module', 'scm-plugin'); ?>
            </a>
        </div>
        
        <div class="scm-module-card">
            <h2>📦 <?php esc_html_e('Inventory Optimization', 'scm-plugin'); ?></h2>
            <p><?php esc_html_e('Calculate EOQ, safety stock, and reorder points for optimal inventory management.', 'scm-plugin'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=scm-saas-inventory')); ?>" class="button button-primary">
                <?php esc_html_e('Open Module', 'scm-plugin'); ?>
            </a>
        </div>
        
        <div class="scm-module-card">
            <h2>🤖 <?php esc_html_e('AI Forecasting', 'scm-plugin'); ?></h2>
            <p><?php esc_html_e('Advanced forecasting using Facebook Prophet machine learning model.', 'scm-plugin'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=scm-ai-forecast')); ?>" class="button button-primary">
                <?php esc_html_e('Open Module', 'scm-plugin'); ?>
            </a>
        </div>
        
        <div class="scm-module-card">
            <h2>🔍 <?php esc_html_e('Database Inspector', 'scm-plugin'); ?></h2>
            <p><?php esc_html_e('View and inspect SCM database tables and their structure.', 'scm-plugin'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=scm-db-inspector')); ?>" class="button button-secondary">
                <?php esc_html_e('Open Module', 'scm-plugin'); ?>
            </a>
        </div>
    </div>

    <div class="scm-card">
        <h2><?php esc_html_e('Recent Activity', 'scm-plugin'); ?></h2>
        <p>
            <?php 
            printf(
                esc_html__('%s sales recorded in the last 7 days', 'scm-plugin'),
                '<strong>' . esc_html(number_format($recent_sales)) . '</strong>'
            );
            ?>
        </p>
    </div>
</div>

<style>
.scm-wrap {
    max-width: 1400px;
}
.scm-subtitle {
    font-size: 16px;
    color: #666;
    margin-top: 5px;
}
.scm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}
.scm-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-left: 4px solid #2271b1;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.scm-stat-icon {
    font-size: 48px;
}
.scm-stat-content h3 {
    margin: 0;
    font-size: 32px;
    color: #2271b1;
}
.scm-stat-content p {
    margin: 5px 0 0;
    color: #666;
}
.scm-modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 30px 0;
}
.scm-module-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.scm-module-card h2 {
    margin-top: 0;
    font-size: 18px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
.scm-module-card p {
    min-height: 60px;
}
.scm-module-card .button {
    margin-top: 10px;
}
</style>