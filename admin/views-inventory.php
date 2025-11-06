<div class="wrap">
    <h1>📦 Inventory Optimization</h1>


<form method="post" action="">
    <table class="form-table">
        <tr><th>Product ID</th><td><input type="text" name="product_id" required></td></tr>
        <tr><th>Annual Demand (units)</th><td><input type="number" name="annual_demand" required></td></tr>
        <tr><th>Order Cost ($ per order)</th><td><input type="number" name="order_cost" required></td></tr>
        <tr><th>Holding Cost ($ per unit per year)</th><td><input type="number" name="holding_cost" required></td></tr>
        <tr><th>Daily Demand (units)</th><td><input type="number" name="daily_demand" required></td></tr>
        <tr><th>Lead Time (days)</th><td><input type="number" name="lead_time" required></td></tr>
        <tr><th>Z-value (service level factor)</th><td><input type="number" step="0.01" name="z_value" value="1.65"></td></tr>
        <tr><th>Demand Std Deviation (units)</th><td><input type="number" name="demand_std" required></td></tr>
    </table>
    <p><input type="submit" name="calc_inventory" class="button button-primary" value="Calculate"></p>
</form>


    <?php
    if (isset($_POST['calc_inventory'])) {
        require_once plugin_dir_path(__FILE__) . 'inventory-handler.php';
        scm_handle_inventory_calc();
    }
?>

<canvas id="inventoryChart" width="600" height="300"></canvas>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo plugins_url('../assets/js/inventory-chart.js', __FILE__); ?>"></script>