<?php
/**
 * Plugin activation handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Core\Database;
use ApolloWeb\WPWooCommercePrintifySync\Core\Scheduler;

/**
 * Class Activator
 */
class Activator {
    /**
     * Plugin activation logic
     *
     * @return void
     */
    public static function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.3', '<')) {
            deactivate_plugins(WPWPS_PLUGIN_BASENAME);
            wp_die(
                esc_html__('WP WooCommerce Printify Sync requires PHP 7.3 or higher.', 'wp-woocommerce-printify-sync'),
                esc_html__('Plugin Activation Error', 'wp-woocommerce-printify-sync'),
                ['back_link' => true]
            );
        }

        // Check WordPress version
        if (version_compare($GLOBALS['wp_version'], '5.6', '<')) {
            deactivate_plugins(WPWPS_PLUGIN_BASENAME);
            wp_die(
                esc_html__('WP WooCommerce Printify Sync requires WordPress 5.6 or higher.', 'wp-woocommerce-printify-sync'),
                esc_html__('Plugin Activation Error', 'wp-woocommerce-printify-sync'),
                ['back_link' => true]
            );
        }

        // Create custom database tables
        $database = new Database();
        $database->createTables();
        
        // Set default options
        self::setDefaultOptions();
        
        // Schedule initial cron jobs
        Scheduler::scheduleEvents();
        
        // Trigger action for other components to hook into activation
        do_action('wpwps_activated');
        
        // Set activation flag for redirect
        set_transient('wpwps_activation_redirect', true, 30);
    }
    
    /**
     * Set default plugin options
     *
     * @return void
     */
    private static function setDefaultOptions() {
        $options = [
            'printify_api_endpoint' => 'https://api.printify.com/v1',
            'email_queue_interval' => 5, // minutes
            'stock_sync_interval' => 6, // hours
            'gpt_temperature' => 0.7,
            'version' => WPWPS_VERSION,
        ];
        
        foreach ($options as $option => $value) {
            if (get_option('wpwps_' . $option) === false) {
                update_option('wpwps_' . $option, $value);
            }
        }
    }
}
