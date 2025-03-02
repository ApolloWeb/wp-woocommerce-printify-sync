<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://example.com/wp-woocommerce-printify-sync
 * Description: Sync products from Printify to WooCommerce and manage orders.
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://example.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 */

if (!defined('WPINC')) {
    die;
}

define('WPWCS_PLUGIN_FILE', __FILE__);

require_once plugin_dir_path(__FILE__) . 'includes/Activator.php';
require_once plugin_dir_path(__FILE__) . 'includes/Deactivator.php';
require_once plugin_dir_path(__FILE__) . 'includes/Api.php';
require_once plugin_dir_path(__FILE__) . 'includes/ShopPrintify.php';
require_once plugin_dir_path(__FILE__) . 'includes/DBLogger.php';
require_once plugin_dir_path(__FILE__) . 'includes/AjaxHandler.php';
require_once plugin_dir_path(__FILE__) . 'includes/WPWCSLogger.php';
require_once plugin_dir_path(__FILE__) . 'includes/WPWCSNotifications.php';
require_once plugin_dir_path(__FILE__) . 'includes/EnqueueHelper.php';
require_once plugin_dir_path(__FILE__) . 'includes/Settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/Products.php';
require_once plugin_dir_path(__FILE__) . 'includes/CurrencyManager.php';
require_once plugin_dir_path(__FILE__) . 'includes/CronJob.php';
require_once plugin_dir_path(__FILE__) . 'includes/Menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/EncryptionHelper.php';
require_once plugin_dir_path(__FILE__) . 'includes/Bootstrap.php';

ApolloWeb\WPWooCommercePrintifySync\Bootstrap::init();