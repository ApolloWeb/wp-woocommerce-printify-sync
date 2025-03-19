<?php
/**
 * Direct asset inclusion for admin pages
 * This can be used as a last resort if WordPress asset registration isn't working
 */

// Base URL for assets
$plugin_url = 'https://bigfootfashion.ngrok.app/wp-content/plugins/wp-woocommerce-printify-sync/';

// Output direct stylesheet links in the head of the admin page
function wpwps_direct_asset_links() {
    global $plugin_url;
    
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
    echo '<link rel="stylesheet" href="' . $plugin_url . 'assets/css/wpwps-common.css">';
    
    // Current page CSS
    $page = $_GET['page'] ?? '';
    if (strpos($page, 'wpwps-') === 0) {
        $pagename = str_replace('wpwps-', '', $page);
        echo '<link rel="stylesheet" href="' . $plugin_url . 'assets/css/wpwps-' . $pagename . '.css">';
    }
}

// Add direct script links at the end of the admin page
function wpwps_direct_script_links() {
    global $plugin_url;
    
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo '<script src="' . $plugin_url . 'assets/js/wpwps-common.js"></script>';
    
    // Current page JavaScript
    $page = $_GET['page'] ?? '';
    if (strpos($page, 'wpwps-') === 0) {
        $pagename = str_replace('wpwps-', '', $page);
        echo '<script src="' . $plugin_url . 'assets/js/wpwps-' . $pagename . '.js"></script>';
    }
}

// Hook these functions into appropriate WordPress actions
add_action('admin_head', 'wpwps_direct_asset_links');
add_action('admin_footer', 'wpwps_direct_script_links');
