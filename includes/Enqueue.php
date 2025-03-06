<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Enqueue {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }

    public function enqueueAdminScripts() {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
        wp_enqueue_style('admin-dashboard-style', WPWPPS_PLUGIN_URL . 'assets/css/admin/admin-dashboard-style.css');
        wp_enqueue_style('product-sync-style', WPWPPS_PLUGIN_URL . 'assets/css/admin/product-sync-style.css');
        wp_enqueue_script('admin-dashboard', WPWPPS_PLUGIN_URL . 'assets/js/admin/admin-dashboard.js', ['jquery'], false, true);
        wp_enqueue_script('product-sync', WPWPPS_PLUGIN_URL . 'assets/js/admin/product-sync.js', ['jquery'], false, true);
    }
}