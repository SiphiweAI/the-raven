<?php
if (!defined('ABSPATH')) exit;

// Handle form submission
$forecast_status = '';
$forecast_message = '';

if (isset($_POST['run_forecast']) && check_admin_referer('scm_run_ai_forecast', 'scm_ai_nonce')) {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'scm-plugin'));
    }
    
    // Schedule background task
    if (!wp_next_scheduled('scm_run_ai_forecast_event')) {
        wp_schedule_single_event(time() + 10, 'scm_run_ai_forecast_event');
        $forecast_status = 'success';
        $forecast_message = __('AI forecast scheduled. Results will be available shortly.', 'scm-plugin');
    } else {
        $forecast_status = 'info';
        $forecast_message = __('A forecast is already scheduled or running.', 'scm-plugin');
    }
}

// Check for recent forecast results
$forecast_file = plugin_dir_path(__DIR__) . '../ai/forecast_P001.json';
$forecast_results = null;
$forecast_date = null;

if (file_exists($forecast_file)) {
    $forecast_date = date('Y-m-d H:i:s', filemtime($forecast_file));
    $forecast_results = json_decode(file_get_contents($forecast_file), true);
}
?>

<div class="wrap scm-wrap">
    <h1><?php esc_html_e('🤖 AI-Powered Forecasting (Prophet)', 'scm-plugin'); ?></h1>

    <?php if ($forecast_status): ?>
        <div class="notice notice-<?php echo esc_attr($forecast_status); ?> is-dismissible">
            <p><?php echo esc_html($forecast_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="scm-card">
        <h2><?php esc_html_e('Run AI Forecast', 'scm-plugin'); ?></h2>
        <p><?php esc_html_e('Generate advanced forecasts using Facebook Prophet machine learning model.', 'scm-plugin'); ?></p>
        
        <div class="scm-info-box">
            <h4><?php esc_html_e('Requirements:', 'scm-plugin'); ?></h4>
            <ul>
                <li><?php esc_html_e('Python 3.7+ installed on server', 'scm-plugin'); ?></li>
                <li><?php esc_html_e('Prophet library installed (pip install prophet)', 'scm-plugin'); ?></li>
                <li><?php esc_html_e('Sales data available in database', 'scm-plugin'); ?></li>
            </ul>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('scm_run_ai_forecast', 'scm_ai_nonce'); ?>
            <p>
                <button type="submit" name="run_forecast" class="button button-primary">
                    <?php esc_html_e('🚀 Run AI Forecast', 'scm-plugin'); ?>
                </button>
            </p>
        </form>
    </div>

    <?php if ($forecast_results): ?>
        <div class="scm-card">
            <h2><?php esc_html_e('Latest Forecast Results', 'scm-plugin'); ?></h2>
            <p class="description">
                <?php printf(__('Generated: %s', 'scm-plugin'), esc_html($forecast_date)); ?>
            </p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'scm-plugin'); ?></th>
                        <th><?php esc_html_e('Forecasted Quantity', 'scm-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forecast_results as $result): ?>
                        <tr>
                            <td><?php echo esc_html($result['ds']); ?></td>
                            <td><?php echo esc_html(number_format($result['yhat'], 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="scm-chart-container" style="margin-top: 20px;">
                <canvas id="aiForecastChart" width="800" height="400"></canvas>
            </div>
        </div>
    <?php endif; ?>

    <div class="scm-card">
        <h2><?php esc_html_e('System Status', 'scm-plugin'); ?></h2>
        
        <?php
        // Check Python availability
        $python_check = shell_exec('which python3 2>&1');
        $python_available = !empty($python_check);
        
        // Check Prophet
        $prophet_check = shell_exec('python3 -c "import prophet" 2>&1');
        $prophet_available = empty($prophet_check);
        ?>
        
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Python 3', 'scm-plugin'); ?></th>
                <td>
                    <?php if ($python_available): ?>
                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                        <code><?php echo esc_html(trim($python_check)); ?></code>
                    <?php else: ?>
                        <span class="dashicons dashicons-dismiss" style="color: red;"></span>
                        <?php esc_html_e('Not found', 'scm-plugin'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Prophet Library', 'scm-plugin'); ?></th>
                <td>
                    <?php if ($prophet_available): ?>
                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                        <?php esc_html_e('Installed', 'scm-plugin'); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-dismiss" style="color: red;"></span>
                        <?php esc_html_e('Not installed', 'scm-plugin'); ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php if ($forecast_results): ?>
<script>
jQuery(document).ready(function($) {
    const forecastData = <?php echo json_encode($forecast_results); ?>;
    
    const ctx = document.getElementById('aiForecastChart');
    const dates = forecastData.map(item => item.ds);
    const values = forecastData.map(item => parseFloat(item.yhat));
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: '<?php esc_html_e('AI Forecast', 'scm-plugin'); ?>',
                data: values,
                borderColor: 'rgb(139, 69, 255)',
                backgroundColor: 'rgba(139, 69, 255, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '<?php esc_html_e('7-Day AI Forecast (Prophet)', 'scm-plugin'); ?>'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '<?php esc_html_e('Quantity', 'scm-plugin'); ?>'
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<style>
.scm-info-box {
    background: #e7f5fe;
    border-left: 4px solid #00a0d2;
    padding: 15px;
    margin: 15px 0;
}
.scm-info-box h4 {
    margin-top: 0;
}
.scm-info-box ul {
    margin-bottom: 0;
}
</style>