<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Description: WordPress plugin to synchronize WooCommerce products with Printify
 * Version: 1.0.0
 * Author: Rob Owen
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 * Main Plugin File
 * 
 * Author: Rob Owen
 * 
 * Timestamp: 2025-02-26 11:29:00
 *
 *
 */

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


/**
 * Class WPWooCommercePrintifySync
 * 
 * Main plugin class
 */
class WPWooCommercePrintifySync {
    /**
     * Instance of Admin class
     * @var ApolloWeb\WooCommercePrintifySync\Admin
     */
    private $admin;

    /**
     * Plugin instance
     * @var WPWooCommercePrintifySync
     */
    private static $instance = null;

    /**
     * Get plugin instance
     * 
     * @return WPWooCommercePrintifySync
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Load plugin textdomain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Check dependencies
        if ($this->check_dependencies()) {
            // Include files
            $this->includes();
            
            // Initialize components
            $this->init_components();
        }
    }

    /**
     * Check if WooCommerce is active
     * 
     * @return bool
     */
    private function check_dependencies() {
        // Check for WooCommerce
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return false;
        }
        return true;
    }

    /**
     * Show admin notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
        <?php
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('wp-woocommerce-printify-sync', false, basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * Include required files
     */
    private function includes() {
        // Autoloader
        require_once WPTFY_PLUGIN_DIR . 'includes/Autoloader.php';
        
        // Register the autoloader
        $autoloader = new \ApolloWeb\WooCommercePrintifySync\Autoloader();
        $autoloader->register();
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize admin
        $this->admin = new \ApolloWeb\WooCommercePrintifySync\Admin();
    }
}

// Initialize the plugin
function wp_woocommerce_printify_sync() {
    return WPWooCommercePrintifySync::instance();
}

// Let's go!
wp_woocommerce_printify_sync();