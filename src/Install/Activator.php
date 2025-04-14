<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Install;

class Activator {
    public static function activate() {
        // Only run if this is first activation
        if (!get_option('wpwps_installed')) {
            self::setupInitialOptions();
            update_option('wpwps_installed', time());
        }
    }

    private static function setupInitialOptions() {
        // Set default options
        add_option('wpwps_api_endpoint', 'https://api.printify.com/v1/');
        add_option('wpwps_products_per_batch', 5);
        add_option('wpwps_retry_attempts', 3);
        add_option('wpwps_retry_delay', 300); // 5 minutes
    }
}
