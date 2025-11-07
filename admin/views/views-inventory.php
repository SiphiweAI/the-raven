<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap scm-wrap">
    <h1><?php esc_html_e('📦 Inventory Optimization', 'scm-plugin'); ?></h1>

    <div class="scm-card">
        <h2><?php esc_html_e('Calculate Inventory Metrics', 'scm-plugin'); ?></h2>
        
        <form id="scm-inventory-form">
            <?php wp_nonce_field('scm_inventory_calc', 'scm_inventory_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="product_id"><?php esc_html_e('Product ID', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="product_id" 
                               id="product_id" 
                               class="regular-text" 
                               required 
                               placeholder="e.g., P001" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="annual_demand"><?php esc_html_e('Annual Demand (units)', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="annual_demand" 
                               id="annual_demand" 
                               min="0" 
                               step="1" 
                               required 
                               placeholder="10000" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="order_cost"><?php esc_html_e('Order Cost ($)', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="order_cost" 
                               id="order_cost" 
                               min="0" 
                               step="0.01" 
                               required 
                               placeholder="50.00" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="holding_cost"><?php esc_html_e('Holding Cost ($/unit/year)', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="holding_cost" 
                               id="holding_cost" 
                               min="0" 
                               step="0.01" 
                               required 
                               placeholder="2.50" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="daily_demand"><?php esc_html_e('Daily Demand (units)', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="daily_demand" 
                               id="daily_demand" 
                               min="0" 
                               step="0.1" 
                               required 
                               placeholder="27.4" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="lead_time"><?php esc_html_e('Lead Time (days)', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="lead_time" 
                               id="lead_time" 
                               min="0" 
                               step="1" 
                               required 
                               placeholder="7" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="z_value"><?php esc_html_e('Z-Value (Service Level)', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="z_value" 
                               id="z_value" 
                               min="0" 
                               step="0.01" 
                               value="1.65" 
                               required />
                        <p class="description">
                            <?php esc_html_e('Common values: 1.28 (90%), 1.65 (95%), 1.96 (97.5%), 2.33 (99%)', 'scm-plugin'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="demand_std"><?php esc_html_e('Demand Std Deviation', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               name="demand_std" 
                               id="demand_std" 
                               min="0" 
                               step="0.01" 
                               required 
                               placeholder="5.0" />
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary" id="calc-btn">
                    <?php esc_html_e('Calculate Metrics', 'scm-plugin'); ?>
                </button>
                <span class="spinner" id="calc-spinner" style="float:none;display:none;"></span>
            </p>
        </form>

        <div id="calc-result" style="display:none;" role="alert"></div>
    </div>

    <div class="scm-card" id="metrics-display" style="display:none;">
        <h2><?php esc_html_e('Calculated Metrics', 'scm-plugin'); ?></h2>
        
        <div class="scm-metrics-grid">
            <div class="scm-metric">
                <h3><?php esc_html_e('Economic Order Quantity (EOQ)', 'scm-plugin'); ?></h3>
                <p class="scm-metric-value" id="eoq-value">-</p>
                <p class="description"><?php esc_html_e('Optimal order quantity to minimize total inventory costs', 'scm-plugin'); ?></p>
            </div>
            
            <div class="scm-metric">
                <h3><?php esc_html_e('Safety Stock', 'scm-plugin'); ?></h3>
                <p class="scm-metric-value" id="safety-stock-value">-</p>
                <p class="description"><?php esc_html_e('Buffer inventory to prevent stockouts', 'scm-plugin'); ?></p>
            </div>
            
            <div class="scm-metric">
                <h3><?php esc_html_e('Reorder Point (ROP)', 'scm-plugin'); ?></h3>
                <p class="scm-metric-value" id="rop-value">-</p>
                <p class="description"><?php esc_html_e('Inventory level that triggers a new order', 'scm-plugin'); ?></p>
            </div>
        </div>

        <div class="scm-chart-container">
            <canvas id="inventoryChart" width="800" height="400"></canvas>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let inventoryChart = null;
    
    $('#scm-inventory-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=scm_calculate_inventory';
        formData += '&nonce=' + $('#scm_inventory_nonce').val();
        
        $('#calc-btn').prop('disabled', true);
        $('#calc-spinner').show();
        $('#calc-result').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#calc-btn').prop('disabled', false);
                $('#calc-spinner').hide();
                
                if (response.success) {
                    $('#calc-result')
                        .removeClass('notice-error')
                        .addClass('notice notice-success')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                    
                    // Display metrics
                    displayMetrics(response.data.metrics);
                    
                } else {
                    $('#calc-result')
                        .removeClass('notice-success')
                        .addClass('notice notice-error')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                }
            },
            error: function() {
                $('#calc-btn').prop('disabled', false);
                $('#calc-spinner').hide();
                $('#calc-result')
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p><?php esc_html_e('An error occurred. Please try again.', 'scm-plugin'); ?></p>')
                    .show();
            }
        });
    });
    
    function displayMetrics(metrics) {
        // Update metric values
        $('#eoq-value').text(metrics.eoq.toFixed(2) + ' units');
        $('#safety-stock-value').text(metrics.safety_stock.toFixed(2) + ' units');
        $('#rop-value').text(metrics.rop.toFixed(2) + ' units');
        
        // Show metrics display
        $('#metrics-display').slideDown();
        
        // Create/update chart
        renderInventoryChart(metrics);
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#metrics-display').offset().top - 100
        }, 500);
    }
    
    function renderInventoryChart(metrics) {
        const ctx = document.getElementById('inventoryChart');
        
        if (inventoryChart) {
            inventoryChart.destroy();
        }
        
        // Simulate inventory levels over time
        const days = 90;
        const inventoryLevels = [];
        const ropLine = [];
        const safetyStockLine = [];
        const labels = [];
        
        let currentStock = metrics.eoq;
        const dailyDemand = metrics.inputs.daily_demand;
        const leadTime = metrics.inputs.lead_time;
        
        for (let day = 0; day < days; day++) {
            labels.push('Day ' + day);
            
            // Decrease by daily demand
            currentStock -= dailyDemand;
            
            // Reorder when hitting ROP
            if (currentStock <= metrics.rop && day % leadTime === 0) {
                currentStock += metrics.eoq;
            }
            
            inventoryLevels.push(Math.max(0, currentStock));
            ropLine.push(metrics.rop);
            safetyStockLine.push(metrics.safety_stock);
        }
        
        inventoryChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '<?php esc_html_e('Inventory Level', 'scm-plugin'); ?>',
                    data: inventoryLevels,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1,
                    fill: true
                }, {
                    label: '<?php esc_html_e('Reorder Point', 'scm-plugin'); ?>',
                    data: ropLine,
                    borderColor: 'rgb(255, 159, 64)',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                }, {
                    label: '<?php esc_html_e('Safety Stock', 'scm-plugin'); ?>',
                    data: safetyStockLine,
                    borderColor: 'rgb(255, 99, 132)',
                    borderDash: [10, 5],
                    pointRadius: 0,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '<?php esc_html_e('Inventory Level Simulation (90 days)', 'scm-plugin'); ?>'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '<?php esc_html_e('Units', 'scm-plugin'); ?>'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: '<?php esc_html_e('Time', 'scm-plugin'); ?>'
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
.scm-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.scm-metric {
    background: #f7f7f7;
    border-left: 4px solid #2271b1;
    padding: 15px;
}
.scm-metric h3 {
    margin-top: 0;
    font-size: 14px;
    color: #666;
}
.scm-metric-value {
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
    margin: 10px 0;
}
.scm-chart-container {
    height: 400px;
    position: relative;
}
</style>