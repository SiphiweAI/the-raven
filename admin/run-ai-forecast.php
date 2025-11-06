<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

if (isset($_POST['run_forecast'])) {
    // Schedule background event or trigger async process instead of blocking shell_exec
    if (!wp_next_scheduled('scm_run_ai_forecast_event')) {
        wp_schedule_single_event(time(), 'scm_run_ai_forecast_event');
        echo '<p>Forecast scheduled in background. Refresh later for results.</p>';
    } else {
        echo '<p>Forecast is already running or scheduled.</p>';
    }
}

add_action('scm_run_ai_forecast_event', 'scm_run_ai_forecast_callback');
function scm_run_ai_forecast_callback() {
    $python = '/usr/bin/python3'; // Adjust path if necessary
    $script = plugin_dir_path(__FILE__) . "../ai/prophet_forecast.py";
    exec(escapeshellcmd("$python $script 2>&1"), $output, $status);
    // Log $output and $status to file or DB for admin review
}
?>

<form method="post">
    <input type="submit" name="run_forecast" value="Run AI Forecast" class="button button-primary">
</form>
