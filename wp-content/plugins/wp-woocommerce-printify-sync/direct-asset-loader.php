<?php
/**
 * Emergency fallback for loading assets directly
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

// Don't call this file directly
if (!defined('WPINC')) {
    die;
}

/**
 * Manual direct asset registration as a fallback
 */
function wpwps_manual_asset_registration($hook) {
    // Only on our plugin pages
    if (strpos($hook, 'wpwps-') === false) {
        return;
    }
    
    $plugin_url = plugin_dir_url(__FILE__);
    
    // Third-party assets
    wp_enqueue_style('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_style('wpwps-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    wp_enqueue_script('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
    wp_enqueue_script('wpwps-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    
    // Common CSS and JS
    wp_enqueue_style('wpwps-common', $plugin_url . 'assets/css/wpwps-common.css', [], '1.0.0');
    wp_enqueue_script('wpwps-common', $plugin_url . 'assets/js/wpwps-common.js', ['jquery'], '1.0.0', true);
    
    // Page-specific assets
    $current_page = $_GET['page'] ?? '';
    if (strpos($current_page, 'wpwps-') === 0) {
        $page = str_replace('wpwps-', '', $current_page);
        $css_file = $plugin_url . "assets/css/wpwps-{$page}.css";
        $js_file = $plugin_url . "assets/js/wpwps-{$page}.js";
        
        wp_enqueue_style("wpwps-{$page}", $css_file, ['wpwps-common'], '1.0.0');
        wp_enqueue_script("wpwps-{$page}", $js_file, ['jquery', 'wpwps-common'], '1.0.0', true);
    }
}

// Use the namespace when adding the hook
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\wpwps_manual_asset_registration', 999);
