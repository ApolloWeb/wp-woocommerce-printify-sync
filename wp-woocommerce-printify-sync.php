/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Description: Integrates Printify with WooCommerce for seamless synchronization of product data, orders, categories, images, and SEO metadata.
 * Version: 1.0.0
 * Author: Rob Owen
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 */

<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPTFY_PLUGIN_FILE', __FILE__);
define('WPTFY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPTFY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPTFY_PLUGIN_VERSION', '1.0.0');

add_action('before_woocommerce_init', function () {
    if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

class WPWooCommercePrintifySync {
    private $admin;

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        if ($this->check_dependencies()) {
            $this->includes();
            $this->init_components();
        }
    }

    private function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return false;
        }
        return true;
    }

    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>' . __('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync') . '</p></div>';
    }

    public function load_textdomain() {
        load_plugin_textdomain('wp-woocommerce-printify-sync', false, basename(dirname(__FILE__)) . '/languages');
    }

    private function includes() {
        require_once WPTFY_PLUGIN_DIR . 'includes/Autoloader.php';
        $autoloader = new \ApolloWeb\WooCommercePrintifySync\Autoloader();
        $autoloader->register();
    }

    private function init_components() {
        if (class_exists('\ApolloWeb\WooCommercePrintifySync\Admin')) {
            $this->admin = new \ApolloWeb\WooCommercePrintifySync\Admin();
        } else {
            error_log("Admin class could not be loaded. Check Autoloader.");
        }
    }
}

function wp_woocommerce_printify_sync() {
    return WPWooCommercePrintifySync::instance();
}

wp_woocommerce_printify_sync();
