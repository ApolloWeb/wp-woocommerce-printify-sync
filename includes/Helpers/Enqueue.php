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
        // Enqueue Bootstrap CSS
        wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');

        // Enqueue Font Awesome
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

        // Enqueue Chart.js
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js');

        // Enqueue custom admin CSS and JS based on the page
        if ($hook === 'toplevel_page_printify-sync' || strpos($hook, 'printify-sync') !== false) {
            wp_enqueue_style('printify-sync-admin-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/admin.css');
            wp_enqueue_script('printify-sync-admin-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'chart-js'], null, true);
        }

        if ($hook === 'printify-sync_page_printify-sync-product') {
            wp_enqueue_style('printify-sync-product-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/product-sync.css');
            wp_enqueue_script('printify-sync-product-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/product-sync.js', ['jquery'], null, true);
        }

        if ($hook === 'printify-sync_page_printify-sync-order') {
            wp_enqueue_style('printify-sync-order-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/order-sync.css');
            wp_enqueue_script('printify-sync-order-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/order-sync.js', ['jquery'], null, true);
        }

        if ($hook === 'printify-sync_page_printify-sync-error-logs') {
            wp_enqueue_style('printify-sync-error-logs-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/error-logs.css');
            wp_enqueue_script('printify-sync-error-logs-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/error-logs.js', ['jquery'], null, true);
        }

        if ($hook === 'printify-sync_page_printify-sync-tickets') {
            wp_enqueue_style('printify-sync-tickets-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/tickets.css');
            wp_enqueue_script('printify-sync-tickets-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/tickets.js', ['jquery'], null, true);
        }

        if ($hook === 'printify-sync_page_printify-sync-postman') {
            wp_enqueue_style('printify-sync-postman-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/postman.css');
            wp_enqueue_script('printify-sync-postman-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/postman.js', ['jquery'], null, true);
        }

        if ($hook === 'printify-sync_page_printify-sync-exchange-rate') {
            wp_enqueue_style('printify-sync-exchange-rate-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/exchange-rate.css');
            wp_enqueue_script('printify-sync-exchange-rate-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/exchange-rate.js', ['jquery'], null, true);
        }
    }
}