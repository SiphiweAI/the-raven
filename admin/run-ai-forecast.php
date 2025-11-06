
<?php
if (!defined('ABSPATH')) exit;


if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}


echo '<h2>Run AI Forecast</h2>';


if (isset($_POST['run_forecast'])) {
    $output = shell_exec("python3 " . plugin_dir_path(__FILE__) . "../ai/prophet_forecast.py 2>&1");
    echo "<pre>$output</pre>";


    echo "<p>Forecast generated and saved to DB.</p>";
}
?>


<form method="post">
    <input type="submit" name="run_forecast" value="Run AI Forecast" class="button button-primary">
</form>
