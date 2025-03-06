<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class Enqueue {

    /**
     * Registers the enqueuing actions.
     */
    public static function register() {
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
    }

    /**
     * Enqueues admin assets.
     */
    public static function enqueueAdminAssets($hook) {
        // Enqueue Shards Dashboard Lite CSS and JS
        wp_enqueue_style('shards-dashboard-lite-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/shards-dashboard-lite.min.css');
        wp_enqueue_script('shards-dashboard-lite-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/shards-dashboard-lite.min.js', ['jquery'], null, true);

        // Enqueue Chart.js
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);

        // Enqueue custom admin CSS and JS based on the page
        if ($hook === 'toplevel_page_printify-sync' || strpos($hook, 'printify-sync') !== false) {
            wp_enqueue_style('printify-sync-admin-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/admin.css');
            wp_enqueue_script('printify-sync-admin-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'chart-js'], null, true);
        }
    }
}