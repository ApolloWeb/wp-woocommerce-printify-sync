<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/apolloweb/wp-woocommerce-printify-sync
 * Description: Synchronize products and orders between WooCommerce and Printify
 * Version: 1.0.0
 * Author: Apollo Web
 * Author URI: https://apolloweb.com
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package WPWooCommercePrintifySync
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
require_once WPWPS_PLUGIN_DIR . 'src/Core/Autoloader.php';

// Load debug tools when in debug mode
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once WPWPS_PLUGIN_DIR . 'includes/debug-tools.php';
}

// Initialize autoloader with correct base directory and namespace
$autoloader = new \ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader(
    'ApolloWeb\\WPWooCommercePrintifySync',
    WPWPS_PLUGIN_DIR
);
$autoloader->register();

// Direct asset loader fallback
require_once WPWPS_PLUGIN_DIR . 'direct-asset-loader.php';

// Initialize Action Scheduler integration
use ApolloWeb\WPWooCommercePrintifySync\Core\ActionScheduler\Bootstrapper;

// Detect plugin activation
register_activation_hook(__FILE__, function() {
    // Initialize Action Scheduler
    Bootstrapper::init();
});

// Add HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

// Initialize plugin
function init() {
    try {
        // Core services
        $container = new Core\ServiceContainer();
        
        // Register base services
        $container->set('loader', new Core\Loader());
        $container->set('template_engine', new Core\TemplateEngine());
        
        // Register Printify HTTP client service
        $container->set('printify_http_client', function($container) {
            return new API\PrintifyHttpClient(
                get_option('wpwps_printify_api_key', ''),
                get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1')
            );
        });

        // Register API service
        $container->set('printify_api', function($container) {
            return new API\PrintifyAPI($container->get('printify_http_client'));
        });

        // Register WooCommerce services
        $customOrderStatuses = new WooCommerce\CustomOrderStatuses();
        $container->set('custom_order_statuses', $customOrderStatuses);
        $container->set('product_importer', new WooCommerce\ProductImporter());
        $container->set('order_importer', new WooCommerce\OrderImporter());
        
        // Register AJAX handler
        $container->set('ajax_handler', function($container) {
            return new Ajax\AjaxHandler($container);
        });

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
                $handler = $container->get('ajax_handler');
                $handler->handleAjax();
            } catch (\Exception $e) {
                wp_send_json_error(['message' => 'Error processing request: ' . $e->getMessage()]);
            }
        });
        
        add_action('wp_ajax_nopriv_printify_sync', function() {
            wp_send_json_error(['message' => 'Unauthorized access']);
        });

        // Initialize Action Scheduler
        Bootstrapper::init();
        
        // Fire initialization complete action
        do_action('wpwps_initialized');

        // Run the plugin
        $container->get('plugin')->run();

    } catch (\Exception $e) {
        error_log('WP WooCommerce Printify Sync initialization error: ' . $e->getMessage());
    }
}

// Initialize plugin on WordPress init
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

// Make the nonce available earlier to ensure it's always set
add_action('admin_enqueue_scripts', function() {
    wp_localize_script('wpwps-common', 'wpwps_data', [
        'nonce' => wp_create_nonce('wpwps_nonce'),
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}, 999);
