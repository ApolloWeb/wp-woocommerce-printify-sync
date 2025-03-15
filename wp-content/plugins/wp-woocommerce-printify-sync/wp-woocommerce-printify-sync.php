<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://example.com/wp-woocommerce-printify-sync
 * Description: Synchronize products between WooCommerce and Printify
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

defined('ABSPATH') || exit;

// Plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_PLUGIN_FILE', __FILE__);
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
if (file_exists(WPWPS_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WPWPS_PLUGIN_DIR . 'vendor/autoload.php';
}

// Plugin activation
register_activation_hook(__FILE__, function() {
    require_once WPWPS_PLUGIN_DIR . 'includes/class-activator.php';
    WPWooCommercePrintifySync\Includes\Activator::activate();
});

// Initialize plugin
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
            <?php
        });
        return;
    }

    require_once WPWPS_PLUGIN_DIR . 'includes/class-plugin.php';
    $plugin = new WPWooCommercePrintifySync\Includes\Plugin();
    $plugin->run();
});