<?php
// Add this code to your theme's functions.php

function wpwcs_test_mode_banner() {
    $test_mode = get_option('wpwcs_test_mode', false);

    if ($test_mode) {
        echo '<div class="test-mode-banner">TEST MODE</div>';
    }
}
add_action('wp_footer', 'wpwcs_test_mode_banner');

// Add CSS for Test Mode Banner in the front-end
function wpwcs_enqueue_styles() {
    wp_enqueue_style('wpwcs-custom-styles', get_template_directory_uri() . '/wpwcs-custom-styles.css');
}
add_action('wp_enqueue_scripts', 'wpwcs_enqueue_styles');
