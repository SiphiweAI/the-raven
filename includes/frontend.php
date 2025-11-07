<?php

function scm_saas_render_frontend() {
    ob_start();
    ?>
    <div class="scm-saas-container">
        <h2>Welcome to The Raven Portal</h2>
        <p>This content comes from your plugin!</p>  // must be changed later
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'scm_saas_portal', 'scm_saas_render_frontend' );
