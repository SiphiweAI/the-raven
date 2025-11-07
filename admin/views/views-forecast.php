<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap scm-wrap">
    <h1><?php esc_html_e('📈 Demand Forecasting', 'scm-plugin'); ?></h1>
    
    <div class="scm-card">
        <h2><?php esc_html_e('Upload Sales Data', 'scm-plugin'); ?></h2>
        <p><?php esc_html_e('Upload a CSV file with columns: sale_date, product_id, quantity, price', 'scm-plugin'); ?></p>
        
        <form id="scm-upload-form" enctype="multipart/form-data">
            <?php wp_nonce_field('scm_upload_sales', 'scm_upload_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sales_csv"><?php esc_html_e('CSV File', 'scm-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="file" 
                               name="sales_csv" 
                               id="sales_csv" 
                               accept=".csv" 
                               required 
                               aria-describedby="sales-csv-description" />
                        <p class="description" id="sales-csv-description">
                            <?php esc_html_e('Maximum file size: 5MB. Format: CSV', 'scm-plugin'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary" id="upload-btn">
                    <?php esc_html_e('Upload & Generate Forecast', 'scm-plugin'); ?>
                </button>
                <span class="spinner" id="upload-spinner" style="float:none;display:none;"></span>
            </p>
        </form>

        <div id="upload-result" style="display:none;" role="alert"></div>
    </div>

    <div class="scm-card">
        <h2><?php esc_html_e('Forecast Visualization', 'scm-plugin'); ?></h2>
        <canvas id="forecastChart" width="800" height="400"></canvas>
    </div>

    <div class="scm-card">
        <h2><?php esc_html_e('Product Selection', 'scm-plugin'); ?></h2>
        <select id="product-selector">
            <option value=""><?php esc_html_e('Select a product...', 'scm-plugin'); ?></option>
        </select>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#scm-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'scm_upload_sales');
        formData.append('nonce', $('#scm_upload_nonce').val());
        
        $('#upload-btn').prop('disabled', true);
        $('#upload-spinner').show();
        $('#upload-result').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#upload-btn').prop('disabled', false);
                $('#upload-spinner').hide();
                
                if (response.success) {
                    $('#upload-result')
                        .removeClass('notice-error')
                        .addClass('notice notice-success')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                    
                    // Update product selector
                    updateProductSelector(response.data.forecasts);
                    
                    // Reset form
                    $('#scm-upload-form')[0].reset();
                } else {
                    $('#upload-result')
                        .removeClass('notice-success')
                        .addClass('notice notice-error')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                }
            },
            error: function() {
                $('#upload-btn').prop('disabled', false);
                $('#upload-spinner').hide();
                $('#upload-result')
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p><?php esc_html_e('An error occurred. Please try again.', 'scm-plugin'); ?></p>')
                    .show();
            }
        });
    });
    
    function updateProductSelector(forecasts) {
        var $selector = $('#product-selector');
        $selector.empty().append('<option value=""><?php esc_html_e('Select a product...', 'scm-plugin'); ?></option>');
        
        $.each(forecasts, function(productId, data) {
            $selector.append('<option value="' + productId + '">' + productId + '</option>');
        });
    }
});
</script>

<style>
.scm-wrap {
    max-width: 1200px;
}
.scm-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin: 20px 0;
    padding: 20px;
}
.scm-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
#forecastChart {
    max-width: 100%;
    height: auto !important;
}
</style>