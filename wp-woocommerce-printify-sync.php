<?php

/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Automate product syncing, order management, shipping integration, and refunds between Printify and WooCommerce.
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 2.0.0
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

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define WPWPS_VERSION constant for asset versioning.
if (!defined('WPWPS_VERSION')) {
    define('WPWPS_VERSION', '2.0.0');
}

// Remove Composer autoloader check and use our own Autoloader.
require_once __DIR__ . '/src/Autoloader.php';
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

use ApolloWeb\WPWooCommercePrintifySync\Plugin;

function wp_woocommerce_printify_sync_run() {
	$plugin = new Plugin();
	$plugin->run();
}

wp_woocommerce_printify_sync_run();

class Plugin {
    // ...existing code...

    public function save_printify_settings() {
        // Check nonce and permissions just as before
        check_ajax_referer('wpwps_nonce', '_ajax_nonce');

        // Sanitize and retrieve settings data
        $api_key  = sanitize_text_field($_POST['api_key']);
        $endpoint = sanitize_text_field($_POST['endpoint'] ?? 'https://api.printify.com/v1');
        $shop_id  = sanitize_text_field($_POST['shop'] ?? '');

        // Save settings into WordPress options
        update_option('wpwps_api_key', $api_key);
        update_option('wpwps_endpoint', $endpoint);
        update_option('wpwps_shop_id', $shop_id);

        // Optionally, perform additional validation/testing here

        wp_send_json_success(['message' => __('Settings saved successfully.', 'wp-woocommerce-printify-sync')]);
    }

    // ...existing code...
}
