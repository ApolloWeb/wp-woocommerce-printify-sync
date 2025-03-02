<?php
/**
 * Enqueue scripts and styles for the plugin
 */
function wpwcs_enqueue_admin_assets() {
    // Only load on plugin pages
    $screen = get_current_screen();
    if (!strpos($screen->id, 'wpwcs')) {
        return;
    }
    
    // Define plugin directory URL
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    
    // Styles
    wp_enqueue_style(
        'wpwcs-admin-style',
        $plugin_url . 'assets/css/admin.css',
        array(),
        filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/admin.css')
    );
    
    // Font Awesome
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
        array(),
        '5.15.4'
    );
    
    // AdminLTE CSS
    wp_enqueue_style(
        'admin-lte',
        'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css',
        array(),
        '3.2.0'
    );
    
    // Scripts
    wp_enqueue_script('jquery');
    
    // Chart.js
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
        array('jquery'),
        '3.9.1',
        true
    );
    
    // AdminLTE JS
    wp_enqueue_script(
        'admin-lte',
        'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js',
        array('jquery'),
        '3.2.0',
        true
    );
    
    // Custom admin script
    wp_enqueue_script(
        'wpwcs-admin-script',
        $plugin_url . 'assets/js/admin.js',
        array('jquery', 'chartjs', 'admin-lte'),
        filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/js/admin.js'),
        true
    );
    
    // Chart script
    wp_enqueue_script(
        'wpwcs-chart-script',
        $plugin_url . 'assets/js/chart.js',
        array('jquery', 'chartjs'),
        filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/js/chart.js'),
        true
    );
    
    // Localize script for AJAX
    wp_localize_script('wpwcs-admin-script', 'wpwcs_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpwcs-nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'wpwcs_enqueue_admin_assets');