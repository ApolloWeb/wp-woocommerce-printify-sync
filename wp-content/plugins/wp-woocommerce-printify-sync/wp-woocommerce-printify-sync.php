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
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_FILE', __FILE__);
define('WPWPS_PATH', plugin_dir_path(WPWPS_FILE));
define('WPWPS_URL', plugin_dir_url(WPWPS_FILE));
define('WPWPS_BASENAME', plugin_basename(WPWPS_FILE));

// Autoloader
require_once WPWPS_PATH . 'src/Core/Autoloader.php';
ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader::register();

// Boot plugin
use ApolloWeb\WPWooCommercePrintifySync\Core\Plugin;
(new Plugin())->boot();
