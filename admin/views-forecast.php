
<div class="wrap">
    <h1>📈 Demand Forecasting</h1>


    <form method="post" enctype="multipart/form-data" action="">
        <input type="file" name="sales_csv" accept=".csv" required />
        <input type="submit" name="upload_sales" class="button button-primary" value="Upload & Forecast">
    </form>


    <?php
    if (isset($_POST['upload_sales'])) {
        require_once plugin_dir_path(__FILE__) . 'forecast-handler.php';
        scm_handle_sales_upload();
    }
    ?>


    <canvas id="forecastChart" width="600" height="300"></canvas>
</div>


<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo plugins_url('../assets/js/forecast-chart.js', __FILE__); ?>"></script>
