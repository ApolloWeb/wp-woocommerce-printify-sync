<?php
/**
 * Main Plugin class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;
use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Core\ProductSync;

/**
 * Plugin class.
 */
class Plugin {
    /**
     * The singleton instance.
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * The settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * The API instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * The product sync instance.
     *
     * @var ProductSync
     */
    private $product_sync;

    /**
     * Get the singleton instance.
     *
     * @return Plugin
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->settings = new Settings();
        $this->api = new PrintifyAPI();
        $this->product_sync = new ProductSync($this->api);
    }

    /**
     * Boot the plugin.
     *
     * @return void
     */
    public function boot() {
        // Check if WooCommerce is active
        if (!$this->checkDependencies()) {
            add_action('admin_notices', [$this, 'dependencyError']);
            return;
        }

        // Initialize components
        add_action('init', [$this, 'init']);
        
        // Initialize stock sync
        $stock_sync = new StockSync(
            $this->container->get('api_service'),
            $this->container->get('rate_limiter'),
            $this->container->get('logger')
        );
        $stock_sync->scheduleCron();
        
        // Register dashboard widgets
        if (is_admin()) {
            new Dashboard\StockSyncWidget($stock_sync);
        }
        
        // Admin hooks
        if (is_admin()) {
            $this->settings->init();
            add_action('admin_menu', [$this, 'registerAdminMenu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
            add_action('admin_notices', [$this, 'checkApiKeyNotice']);
        }
    }

    /**
     * Initialize plugin components.
     *
     * @return void
     */
    public function init() {
        load_plugin_textdomain('wp-woocommerce-printify-sync', false, dirname(WPWPS_PLUGIN_BASENAME) . '/languages');
        
        // Register AJAX actions
        add_action('wp_ajax_wpwps_sync_products', [$this->product_sync, 'syncProducts']);
        add_action('wp_ajax_wpwps_test_api_connection', [$this->api, 'testConnection']);
    }

    /**
     * Register admin menu pages.
     *
     * @return void
     */
    public function registerAdminMenu() {
        add_menu_page(
            __('WC Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wp-woocommerce-printify-sync',
            [$this, 'renderDashboardPage'],
            'dashicons-update',
            56
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wp-woocommerce-printify-sync',
            [$this, 'renderDashboardPage']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wp-woocommerce-printify-sync-products',
            [$this->product_sync, 'renderPage']
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wp-woocommerce-printify-sync-settings',
            [$this->settings, 'renderPage']
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'wp-woocommerce-printify-sync') === false) {
            return;
        }

        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin.css',
            [],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-admin.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script(
            'wpwps-admin',
            'wpwpsData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps-nonce'),
                'i18n' => [
                    'syncing' => __('Syncing products...', 'wp-woocommerce-printify-sync'),
                    'syncSuccess' => __('Products synced successfully!', 'wp-woocommerce-printify-sync'),
                    'syncError' => __('Error syncing products. Please try again.', 'wp-woocommerce-printify-sync'),
                    'testingConnection' => __('Testing API connection...', 'wp-woocommerce-printify-sync'),
                    'connectionSuccess' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
                    'connectionError' => __('Connection failed. Please check your API key.', 'wp-woocommerce-printify-sync'),
                ],
            ]
        );
    }

    /**
     * Render the dashboard page.
     *
     * @return void
     */
    public function renderDashboardPage() {
        include WPWPS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Check if API key is set and display notice if not.
     *
     * @return void
     */
    public function checkApiKeyNotice() {
        $api_key = $this->settings->getOption('api_key');
        
        if (empty($api_key) && (isset($_GET['page']) && strpos($_GET['page'], 'wp-woocommerce-printify-sync') !== false)) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo sprintf(
                /* translators: %s: Settings page URL */
                __('Printify API key is not set. <a href="%s">Set it now</a> to start syncing products.', 'wp-woocommerce-printify-sync'),
                admin_url('admin.php?page=wp-woocommerce-printify-sync-settings')
            );
            echo '</p></div>';
        }
    }

    /**
     * Check if WooCommerce is active.
     *
     * @return bool
     */
    private function checkDependencies() {
        return class_exists('WooCommerce');
    }

    /**
     * Display dependency error.
     *
     * @return void
     */
    public function dependencyError() {
        echo '<div class="notice notice-error"><p>';
        echo __('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync');
        echo '</p></div>';
    }

    /**
     * Plugin activation hook.
     *
     * @return void
     */
    public function activate() {
        // Create necessary directories
        wp_mkdir_p(WPWPS_PLUGIN_DIR . 'logs');
        
        // Add default options
        $this->settings->addDefaultOptions();
        
        // Create capabilities
        $this->createCapabilities();
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook.
     *
     * @return void
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wpwps_daily_sync');
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall hook.
     *
     * @return void
     */
    public static function uninstall() {
        // Remove plugin options
        delete_option('wpwps_settings');
        
        // Remove plugin capabilities
        self::removeCapabilities();
    }

    /**
     * Create plugin capabilities.
     *
     * @return void
     */
    private function createCapabilities() {
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_printify_sync');
        }
    }

    /**
     * Remove plugin capabilities.
     *
     * @return void
     */
    private static function removeCapabilities() {
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('manage_printify_sync');
        }
    }
}
