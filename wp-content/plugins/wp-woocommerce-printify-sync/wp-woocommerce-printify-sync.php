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

namespace ApolloWeb\WPWooCommercePrintifySync;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Register autoloader - Update path to be relative to this file
require_once __DIR__ . '/src/Core/Autoloader.php';
$autoloader = new Core\Autoloader(
    'ApolloWeb\\WPWooCommercePrintifySync\\',
    __DIR__ . '/'
);
$autoloader->register();

/**
 * Plugin version.
 */
define('WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION', '1.0.0');

/**
 * Asset paths
 */
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Direct asset loader as fallback
 */
require_once plugin_dir_path(__FILE__) . 'direct-asset-loader.php';

/**
 * Add AJAX nonce to JavaScript
 */
add_action('admin_enqueue_scripts', function() {
    wp_localize_script('wpwps-common', 'wpwps_data', [
        'nonce' => wp_create_nonce('wpwps_nonce'),
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}, 999);

/**
 * Plugin initialization
 */
function init()
{
    // Create service container
    $container = new Core\ServiceContainer();
    
    // Register core services
    $container->set('loader', new Core\Loader());
    $container->set('template_engine', new Core\TemplateEngine());
    
    // Register API services
    $container->set('printify_api', function() {
        return new API\PrintifyAPI(
            get_option('wpwps_printify_api_key', ''),
            get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1')
        );
    });
    
    // Register WooCommerce services
    $container->set('product_importer', new WooCommerce\ProductImporter());
    
    // Register plugin
    $container->set('plugin', function($container) {
        return new Plugin(
            $container->get('loader'),
            $container->get('template_engine'),
            $container
        );
    });
    
    // Run the plugin
    $container->get('plugin')->run();
}

add_action('plugins_loaded', __NAMESPACE__ . '\\init');