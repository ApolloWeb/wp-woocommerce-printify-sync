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

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:14:00

defined('ABSPATH') || exit;

if (!class_exists('WP_WooCommerce_Printify_Sync')) {
    class WP_WooCommerce_Printify_Sync
    {
        private $services = [];

        public function __construct()
        {
            // Load dependencies
            $this->load_dependencies();

            // Initialize plugin
            $this->init();

            // Enqueue admin styles and scripts
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }

        private function load_dependencies()
        {
            require_once plugin_dir_path(__FILE__) . 'src/ServiceProvider.php';
            require_once plugin_dir_path(__FILE__) . 'src/AdminSettings.php';
            require_once plugin_dir_path(__FILE__) . 'src/AjaxHandlers.php';
            require_once plugin_dir_path(__FILE__) . 'src/WebhookManager.php';
            require_once plugin_dir_path(__FILE__) . 'src/WooCommerceHooks.php';
            require_once plugin_dir_path(__FILE__) . 'src/ActionScheduler.php';
            require_once plugin_dir_path(__FILE__) . 'src/ImageHandler.php';
            require_once plugin_dir_path(__FILE__) . 'src/AdminProductImport.php';
            require_once plugin_dir_path(__FILE__) . 'src/AjaxProductImportHandlers.php';
            require_once plugin_dir_path(__FILE__) . 'src/HttpClient.php';
            require_once plugin_dir_path(__FILE__) . 'src/PrintifyAPI.php';
        }

        private function init()
        {
            $this->register_service(new ApolloWeb\WPWooCommercePrintifySync\AdminSettings());
            $this->register_service(new ApolloWeb\WPWooCommercePrintifySync\AjaxHandlers());
            $this->register_service(new ApolloWeb\WPWooCommercePrintifySync\WebhookManager());
            $this->register_service(new ApolloWeb\WPWooCommercePrintifySync\WooCommerceHooks());
            $this->register_service(new ApolloWeb\WPWooCommercePrintifySync\ActionScheduler());
            $this->register_service(new ApolloWeb\WPWooCommercePrintifySync\ImageHandler());
            $this->register_service(new ApolloWeb\WPWooCommercePrintifySync\AdminProductImport());
            $this->register_service(new ApolloWeb\WPWooCommercePrintifySync\AjaxProductImportHandlers());

            $this->boot_services();
        }

        private function register_service($service)
        {
            $this->services[] = $service;
        }

        private function boot_services()
        {
            foreach ($this->services as $service) {
                if (method_exists($service, 'boot')) {
                    $service->boot();
                }
            }
        }

        public function enqueue_admin_assets()
        {
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
            wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
            wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', ['jquery'], '4.5.2', true);
            wp_enqueue_style('printify-sync-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', [], '1.0.0');
            wp_enqueue_script('printify-sync-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], '1.0.0', true);

            // Localize script for AJAX
            wp_localize_script('printify-sync-admin', 'printifySync', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('printify_sync_nonce'),
            ]);
        }
    }

    new WP_WooCommerce_Printify_Sync();
}