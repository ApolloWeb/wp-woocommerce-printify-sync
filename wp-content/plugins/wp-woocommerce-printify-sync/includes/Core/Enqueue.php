<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Enqueue {
    public static function register_admin_assets($hook) {
        // ...existing code...

        // Font Awesome (updated version)
        wp_register_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            [],
            '5.15.4'
        );

        // Load styles on all admin pages for menu icon
        wp_enqueue_style('wpwps-fontawesome');

        // Register core admin script with common functionality
        wp_register_script(
            'wpwps-admin',
            WWPS_URL . 'assets/js/admin.js',
            ['jquery'],
            WWPS_VERSION,
            true
        );

        // Register page-specific scripts
        wp_register_script(
            'wpwps-settings',
            WWPS_URL . 'assets/js/settings.js',
            ['jquery', 'wpwps-admin'],
            WWPS_VERSION,
            true
        );

        wp_register_script(
            'wpwps-product-import',
            WWPS_URL . 'assets/js/product-import.js',
            ['jquery', 'wpwps-admin'],
            WWPS_VERSION,
            true
        );

        wp_register_script(
            'wpwps-order',
            WWPS_URL . 'assets/js/order.js',
            ['jquery', 'wpwps-admin'],
            WWPS_VERSION,
            true
        );

        // Load on plugin pages
        if (strpos($hook, 'printify') !== false) {
            wp_enqueue_style('wpwps-adminlte');
            wp_enqueue_style('wpwps-fontawesome');
            wp_enqueue_style('wpwps-admin');
            wp_enqueue_script('wpwps-adminlte');
            wp_enqueue_script('wpwps-admin');

            // Page-specific script loading
            if (strpos($hook, 'printify-sync-settings') !== false) {
                wp_enqueue_script('wpwps-settings');
                wp_localize_script('wpwps-settings', 'wpwpsSettings', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wpwps_nonce'),
                    'strings' => [
                        'enterApiKey' => __('Please enter an API key.', 'wp-woocommerce-printify-sync'),
                        'testing' => __('Testing...', 'wp-woocommerce-printify-sync'),
                        'testConnection' => __('Test Connection', 'wp-woocommerce-printify-sync'),
                        'selectShop' => __('Select a shop', 'wp-woocommerce-printify-sync'),
                        'error' => __('An error occurred', 'wp-woocommerce-printify-sync')
                    ]
                ]);
            }

            if (strpos($hook, 'printify-sync-import') !== false) {
                wp_enqueue_script('wpwps-product-import');
                wp_localize_script('wpwps-product-import', 'wpwpsImport', [
                    'nonce' => wp_create_nonce('wwps_import_products'),
                    'importing' => __('Importing...', 'wp-woocommerce-printify-sync'),
                    'imported' => __('Imported', 'wp-woocommerce-printify-sync'),
                    'failed' => __('Failed', 'wp-woocommerce-printify-sync')
                ]);
            }

            // Load order script on WooCommerce order pages
            global $post;
            if ($post && get_post_type($post) === 'shop_order') {
                wp_enqueue_script('wpwps-order');
                wp_localize_script('wpwps-order', 'wpwpsOrder', [
                    'nonce' => wp_create_nonce('wpwps_order'),
                    'strings' => [
                        'confirmRetry' => __('Are you sure you want to retry syncing this order?', 'wp-woocommerce-printify-sync'),
                        'confirmCancel' => __('Are you sure you want to cancel this order in Printify?', 'wp-woocommerce-printify-sync')
                    ]
                ]);
            }
        }

        // ...existing code...
    }
}
