<?php
/**
 * Uninstall functionality for WP WooCommerce Printify Sync
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load autoloader to access Plugin class
require_once plugin_dir_path(__FILE__) . 'src/Autoloader.php';
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

// Call static uninstall method
\ApolloWeb\WPWooCommercePrintifySync\Plugin::uninstall();
