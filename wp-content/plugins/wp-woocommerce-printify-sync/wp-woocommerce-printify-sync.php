<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://yourwebsite.com/wp-woocommerce-printify-sync
 * Description: Synchronize your WooCommerce store with Printify products, inventory, and orders.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_FILE', __FILE__);
define('WPWPS_PATH', plugin_dir_path(__FILE__));
define('WPWPS_URL', plugin_dir_url(__FILE__));
define('WPWPS_BASENAME', plugin_basename(__FILE__));
define('WPWPS_ASSETS_URL', WPWPS_URL . 'assets/');

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Check if WooCommerce is active
function wpwps_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wpwps_woocommerce_missing_notice');
        return false;
    }
    return true;
}

// WooCommerce missing notice
function wpwps_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync'); ?></p>
    </div>
    <?php
}

// Load plugin
function wpwps_load_plugin() {
    // Check if WooCommerce is installed and active
    if (!wpwps_check_woocommerce()) {
        return;
    }

    // Load autoloader
    require_once WPWPS_PATH . 'src/Core/Autoloader.php';
    \ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader::register();

    // Boot plugin
    $plugin = new \ApolloWeb\WPWooCommercePrintifySync\Core\Plugin();
    $plugin->boot();
}

// Initialize plugin
wpwps_load_plugin();
