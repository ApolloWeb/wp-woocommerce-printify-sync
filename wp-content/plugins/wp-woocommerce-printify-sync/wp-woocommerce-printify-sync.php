<?php
/**
 * Plugin Name: WordPress WooCommerce Printify Sync Plugin
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Description: A WordPress plugin to sync WooCommerce with Printify.
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://apollo-web.co.uk
 * License: MIT
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('WPWPSP_VERSION', '1.0.0');
define('WPWPSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPSP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the Composer autoloader.
require_once WPWPSP_PLUGIN_DIR . 'vendor/autoload.php';

// Include required files.
require_once WPWPSP_PLUGIN_DIR . 'includes/functions.php';
require_once WPWPSP_PLUGIN_DIR . 'includes/admin/AdminFunctions.php';
require_once WPWPSP_PLUGIN_DIR . 'includes/helpers/Enqueue.php';
require_once WPWPSP_PLUGIN_DIR . 'includes/api/ApiFunctions.php';

use ApolloWeb\WpWooCommercePrintifySync\Controllers\AdminController;
use ApolloWeb\WpWooCommercePrintifySync\Controllers\ApiController;
use ApolloWeb\WpWooCommercePrintifySync\Controllers\FrontendController;
use ApolloWeb\WpWooCommercePrintifySync\Controllers\PrintifySyncController;

// Initialize the plugin.
function wpwpsp_init()
{
    load_plugin_textdomain('wp-woocommerce-printify-sync', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize controllers
    $adminController = new AdminController();
    $apiController = new ApiController();
    $frontendController = new FrontendController();
    $printifySyncController = new PrintifySyncController();

    // Example usage
    add_action('admin_menu', function () use ($adminController) {
        add_menu_page('Printify Sync', 'Printify Sync', 'manage_options', 'printify-sync', [$adminController, 'renderAdminPage']);
    });

    add_action('wp_loaded', function () use ($frontendController) {
        if (is_page('printify-sync')) {
            $frontendController->renderFrontendPage();
        }
    });

    // Example sync actions
    add_action('wp_ajax_sync_products', [$printifySyncController, 'syncProducts']);
    add_action('wp_ajax_sync_orders', [$printifySyncController, 'syncOrders']);
}

add_action('plugins_loaded', 'wpwpsp_init');