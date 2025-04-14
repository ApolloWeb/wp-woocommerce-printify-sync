<?php
/*
Plugin Name: WP WooCommerce Printify Sync
Description: Sync products from Printify to WooCommerce
Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
Version: 1.0.0
Author: ApolloWeb
Author URI: https://github.com/ApolloWeb
Text Domain: wp-woocommerce-printify-sync
Domain Path: /languages
Requires at least: 5.6
Requires PHP: 7.3
License: MIT
*/

defined('ABSPATH') || exit;

define('WPWPS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_FILE', __FILE__);

// Check if Composer autoloader exists
$autoloader = WPWPS_PLUGIN_PATH . 'vendor/autoload.php';

if (!file_exists($autoloader)) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php esc_html_e('WP WooCommerce Printify Sync requires Composer dependencies to be installed. Please run "composer install" in the plugin directory.', 'wp-woocommerce-printify-sync'); ?>
            </p>
        </div>
        <?php
    });
    return;
}

require_once $autoloader;

// Register activation hook
register_activation_hook(WPWPS_PLUGIN_FILE, ['ApolloWeb\WPWooCommercePrintifySync\Install\Activator', 'activate']);

// Initialize plugin
add_action('plugins_loaded', function () {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php esc_html_e('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
            <?php
        });
        return;
    }
    
    // Create settings page with admin menu
    global $wpwps_settings;
    $wpwps_settings = new \ApolloWeb\WPWooCommercePrintifySync\Admin\SettingsPage();
    
    // Initialize webhook handler
    $webhook = new \ApolloWeb\WPWooCommercePrintifySync\Webhook\Handler();
    $webhook->init();
    
    // Initialize batch importer
    $importer = new \ApolloWeb\WPWooCommercePrintifySync\Import\BatchImporter();
    $importer->init();
});

add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
