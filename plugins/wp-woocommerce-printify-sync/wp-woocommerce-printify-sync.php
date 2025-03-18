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

// Define plugin constants
define('PRINTIFY_SYNC_VERSION', '1.0.0');
define('PRINTIFY_SYNC_FILE', __FILE__);
define('PRINTIFY_SYNC_PATH', plugin_dir_path(__FILE__));
define('PRINTIFY_SYNC_URL', plugin_dir_url(__FILE__));

require_once plugin_dir_path(__FILE__) . 'src/autoloader.php';

if (!class_exists('WP_WooCommerce_Printify_Sync')) {
    class WP_WooCommerce_Printify_Sync
    {
        private $services = [];

        public function __construct()
        {
            // Initialize plugin
            $this->init();
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

            foreach ($this->services as $service) {
                $service->register();
                $service->boot();
            }
        }

        private function load_services()
        {
            // This method is now handled in init()
        }

        private function boot_services()
        {
            // This method is now handled in init()
        }
    }

    new WP_WooCommerce_Printify_Sync();
}

// Bootstrap the plugin
function wp_woocommerce_printify_sync_bootstrap() {
    $container = new ApolloWeb\WPWooCommercePrintifySync\Container\Container();
    
    // Register core services
    $container->bind(
        ApolloWeb\WPWooCommercePrintifySync\Contracts\ProductRepositoryInterface::class,
        ApolloWeb\WPWooCommercePrintifySync\Repositories\PrintifyProductRepository::class
    );
    
    // Register and boot service providers
    $providers = [
        ApolloWeb\WPWooCommercePrintifySync\AdminSettings::class,
        ApolloWeb\WPWooCommercePrintifySync\WebhookManager::class,
        // ...other providers
    ];
    
    foreach ($providers as $provider) {
        $instance = new $provider($container);
        $instance->register();
        $instance->boot();
    }
}