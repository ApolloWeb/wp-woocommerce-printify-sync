<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.3
 * License: MIT
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'src/autoloader.php';

if (!class_exists('WP_WooCommerce_Printify_Sync')) {
    class WP_WooCommerce_Printify_Sync
    {
        private $services = [];

        public function __construct()
        {
            // Initialize plugin
            $this->init();

            // Register services
            $this->load_services();
        }

        private function init()
        {
            $this->services = [
                new ApolloWeb\WPWooCommercePrintifySync\AdminSettings(),
                new ApolloWeb\WPWooCommercePrintifySync\AjaxHandlers(),
                new ApolloWeb\WPWooCommercePrintifySync\WebhookManager(),
                new ApolloWeb\WPWooCommercePrintifySync\WooCommerceHooks(),
                new ApolloWeb\WPWooCommercePrintifySync\ActionScheduler(),
                new ApolloWeb\WPWooCommercePrintifySync\ImageHandler(),
                new ApolloWeb\WPWooCommercePrintifySync\AdminProductImport(),
                new ApolloWeb\WPWooCommercePrintifySync\AjaxProductImportHandlers(),
                new ApolloWeb\WPWooCommercePrintifySync\EnqueueAssets(),
            ];

            $this->boot_services();
        }

        private function load_services()
        {
            foreach ($this->services as $service) {
                if (method_exists($service, 'boot')) {
                    $service->boot();
                }
            }
        }

        private function boot_services()
        {
            foreach ($this->services as $service) {
                if (method_exists($service, 'boot')) {
                    $service->boot();
                }
            }
        }
    }

    new WP_WooCommerce_Printify_Sync();
}