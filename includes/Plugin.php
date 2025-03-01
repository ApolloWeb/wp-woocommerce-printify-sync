<?php
/**
 * Core plugin class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Admin;
use ApolloWeb\WPWooCommercePrintifySync\API\WebhookHandler;
use ApolloWeb\WPWooCommercePrintifySync\Sync\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Sync\OrderSync;

class Plugin {

    /**
     * Loader instance.
     *
     * @var Loader
     */
    protected $loader;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_api_hooks();
        $this->define_sync_hooks();
    }

    /**
     * Load the required dependencies.
     */
    private function load_dependencies() {
        require_once WPWPS_PLUGIN_DIR . 'includes/Loader.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/I18n.php';
        
        // Core plugin component files
        require_once WPWPS_PLUGIN_DIR . 'admin/Admin.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/API/APIClient.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/API/APIResponse.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/API/WebhookHandler.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/Sync/ProductSync.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/Sync/OrderSync.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/Sync/InventorySync.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/Utilities/Logger.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/Utilities/ImageHandler.php';
        require_once WPWPS_PLUGIN_DIR . 'includes/Utilities/Encryption.php';

        $this->loader = new Loader();
    }

    /**
     * Set up localization.
     */
    private function set_locale() {
        $plugin_i18n = new I18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Define admin hooks.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Admin();

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Ajax calls
        $this->loader->add_action('wp_ajax_wpwps_save_settings', $plugin_admin, 'save_settings');
        $this->loader->add_action('wp_ajax_wpwps_sync_products', $plugin_admin, 'sync_products');
        $this->loader->add_action('wp_ajax_wpwps_get_sync_status', $plugin_admin, 'get_sync_status');
    }

    /**
     * Define API hooks.
     */
    private function define_api_hooks() {
        $webhook_handler = new WebhookHandler();
        $this->loader->add_action('rest_api_init', $webhook_handler, 'register_webhook_endpoint');
    }

    /**
     * Define synchronization hooks.
     */
    private function define_sync_hooks() {
        $product_sync = new ProductSync();
        $order_sync = new OrderSync();
        $this->loader->add_action('wpwps_scheduled_product_sync', $product_sync, 'sync_products');
        $this->loader->add_action('wpwps_scheduled_order_sync', $order_sync, 'sync_orders');
    }

    /**
     * Run the loader.
     */
    public function run() {
        $this->loader->run();
    }
}