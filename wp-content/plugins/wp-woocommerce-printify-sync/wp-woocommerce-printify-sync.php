<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce.
 * Version: 1.0.0
 * Author: ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

if (!defined('WPINC')) {
    die;
}

// Define plugin paths
define('WPWPS_PLUGIN_FILE', __FILE__);
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION', '1.0.0');

// Load the autoloader
require_once WPWPS_PLUGIN_DIR . '/src/Core/Autoloader.php';

// Load debug tools when in debug mode
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once WPWPS_PLUGIN_DIR . 'includes/debug-tools.php';
}

// Initialize autoloader with correct base directory
$autoloader = new Core\Autoloader(
    'ApolloWeb\\WPWooCommercePrintifySync',
    WPWPS_PLUGIN_DIR
);
$autoloader->register();

// Direct asset loader fallback
require_once WPWPS_PLUGIN_DIR . 'direct-asset-loader.php';

// Initialize plugin
function init() {
    try {
        // Core services
        $container = new Core\ServiceContainer();
        
        // Register base services
        $container->set('loader', new Core\Loader());
        $container->set('template_engine', new Core\TemplateEngine());
        
        // Register API service
        $container->set('printify_api', function() {
            return new API\PrintifyAPI(
                get_option('wpwps_printify_api_key', ''),
                get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1')
            );
        });

        // Register WooCommerce services
        $customOrderStatuses = new WooCommerce\CustomOrderStatuses();
        $container->set('custom_order_statuses', $customOrderStatuses);
        $container->set('product_importer', new WooCommerce\ProductImporter());
        
        // Register AJAX handler
        $container->set('ajax_handler', new Ajax\AjaxHandler($container));

        // Register plugin instance
        $container->set('plugin', function($container) {
            return new Plugin(
                $container->get('loader'),
                $container->get('template_engine'),
                $container
            );
        });

        // Register WooCommerce hooks
        add_action('init', [$customOrderStatuses, 'registerOrderStatuses']);
        add_filter('wc_order_statuses', [$customOrderStatuses, 'addOrderStatusesToWooCommerce']);

        // Register AJAX handlers first - before other services
        add_action('wp_ajax_printify_sync', function() use ($container) {
            try {
                check_ajax_referer('wpwps_nonce', 'nonce');
                error_log('AJAX printify_sync request received: ' . json_encode($_REQUEST));
                $handler = $container->get('ajax_handler');
                $handler->handleAjax();
            } catch (\Exception $e) {
                error_log('AJAX Error: ' . $e->getMessage());
                wp_send_json_error(['message' => 'Error processing request: ' . $e->getMessage()]);
            }
        });
        
        add_action('wp_ajax_nopriv_printify_sync', function() {
            wp_send_json_error(['message' => 'Unauthorized access']);
        });

        // Run the plugin
        $container->get('plugin')->run();

    } catch (\Exception $e) {
        error_log('WP WooCommerce Printify Sync initialization error: ' . $e->getMessage());
    }
}

// Initialize plugin on WordPress init
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

// Add AJAX nonce to JavaScript
add_action('admin_enqueue_scripts', function() {
    wp_localize_script('wpwps-common', 'wpwps_data', [
        'nonce' => wp_create_nonce('wpwps_nonce'),
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}, 999);
