<?php
if (!defined('ABSPATH')) exit;

/**
 * Enqueue frontend assets
 */
function scm_saas_enqueue_frontend_assets() {
    wp_enqueue_style(
        'scm-frontend-styles',
        SCM_PLUGIN_URL . 'assets/css/frontend.css',
        [],
        SCM_VERSION
    );

    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        [],
        '4.4.0',
        true
    );

    wp_enqueue_script(
        'scm-frontend-scripts',
        SCM_PLUGIN_URL . 'assets/js/frontend.js',
        ['jquery', 'chartjs'],
        SCM_VERSION,
        true
    );

    wp_localize_script('scm-frontend-scripts', 'scmFrontend', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('scm_ajax_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'scm_saas_enqueue_frontend_assets');

/**
 * Frontend portal shortcode
 */
function scm_saas_render_frontend() {
    ob_start(); ?>

    <div class="scm-saas-portal">

        <div class="scm-logo">
            <img src="<?php echo SCM_PLUGIN_URL . 'assets/img/logo.png'; ?>" alt="The Raven Logo">
        </div>

        <h1 class="scm-title">The Raven Portal</h1>
        <p class="scm-subtitle">Supply Chain Management Suite with AI Forecasting, Inventory Optimization, and KPI Analytics.</p>

        <!-- Navigation Tabs -->
        <nav class="scm-tabs">
            <button class="scm-tab active" data-target="dashboard">Dashboard</button>
            <button class="scm-tab" data-target="forecasting">Forecasting</button>
            <button class="scm-tab" data-target="inventory">Inventory</button>
            <button class="scm-tab" data-target="ai-forecast">AI Forecast</button>
            <button class="scm-tab" data-target="db-inspector">DB Inspector</button>
        </nav>

        <div class="scm-tab-content active" id="dashboard">
            <?php
            if (file_exists(SCM_PLUGIN_DIR . 'admin/views/views-dashboard.php')) {
                include SCM_PLUGIN_DIR . 'admin/views/views-dashboard.php';
            } else {
                echo '<p>Dashboard view not found.</p>';
            }
            ?>
        </div>

        <div class="scm-tab-content" id="forecasting">
            <?php
            if (file_exists(SCM_PLUGIN_DIR . 'admin/views/views-forecast.php')) {
                include SCM_PLUGIN_DIR . 'admin/views/views-forecast.php';
            } else {
                echo '<p>Forecasting view not found.</p>';
            }
            ?>
        </div>

        <div class="scm-tab-content" id="inventory">
            <?php
            if (file_exists(SCM_PLUGIN_DIR . 'admin/views/views-inventory.php')) {
                include SCM_PLUGIN_DIR . 'admin/views/views-inventory.php';
            } else {
                echo '<p>Inventory view not found.</p>';
            }
            ?>
        </div>

        <div class="scm-tab-content" id="ai-forecast">
            <?php
            if (file_exists(SCM_PLUGIN_DIR . 'admin/views/views-ai-forecast.php')) {
                include SCM_PLUGIN_DIR . 'admin/views/views-ai-forecast.php';
            } else {
                echo '<p>AI Forecast view not found.</p>';
            }
            ?>
        </div>

        <div class="scm-tab-content" id="db-inspector">
            <?php
            if (file_exists(SCM_PLUGIN_DIR . 'admin/views/views-db-inspector.php')) {
                include SCM_PLUGIN_DIR . 'admin/views/views-db-inspector.php';
            } else {
                echo '<p>DB Inspector view not found.</p>';
            }
            ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('scm_saas_portal', 'scm_saas_render_frontend');
