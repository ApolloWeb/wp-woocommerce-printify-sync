<?php
/**
 * Admin Menu Handler
 * 
 * Responsible for registering and managing the admin menu for the plugin
 */

namespace Printify\Admin;

use Printify\Helpers\MenuHelper;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminMenu
 * 
 * Handles the admin menu registration and structure for the plugin
 */
class AdminMenu {
    /**
     * Singleton instance
     * 
     * @var AdminMenu
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     * 
     * @return AdminMenu
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent multiple instances
     */
    private function __construct() {
        // Initialize the menu
        $this->init();
    }

    /**
     * Initialize the admin menu
     */
    public function init() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /**
     * Register the admin menus
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            'WP WooCommerce Printify Sync', 
            'Printify Sync', 
            'manage_options', 
            'wp-woocommerce-printify-sync',
            [$this, 'display_dashboard_page'],
            'dashicons-cart',
            50
        );

        // Submenu pages
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'wp-woocommerce-printify-sync',
            [$this, 'display_dashboard_page']
        );

        // Products page
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Products',
            'Products',
            'manage_options',
            'printify-products',
            [$this, 'display_products_page']
        );

        // Orders page
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Orders',
            'Orders',
            'manage_options',
            'printify-orders',
            [$this, 'display_orders_page']
        );

        // Shops page
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Shops',
            'Shops',
            'manage_options',
            'printify-shops',
            [$this, 'display_shops_page']
        );

        // Exchange Rates page
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Exchange Rates',
            'Exchange Rates',
            'manage_options',
            'printify-exchange-rates',
            [$this, 'display_exchange_rates_page']
        );

        // Postman page
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'API Postman',
            'API Postman',
            'manage_options',
            'printify-postman',
            [$this, 'display_postman_page']
        );

        // Logs page
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Logs',
            'Logs',
            'manage_options',
            'printify-logs',
            [$this, 'display_logs_page']
        );

        // Settings page
        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Settings',
            'Settings',
            'manage_options',
            'printify-settings',
            [$this, 'display_settings_page']
        );
    }

    /**
     * Display the dashboard page
     */
    public function display_dashboard_page() {
        $dashboard = new AdminDashboard();
        $dashboard->render();
    }

    /**
     * Display the products page
     */
    public function display_products_page() {
        $page = new \Printify\Admin\ProductImport();
        $page->render();
    }

    /**
     * Display the orders page
     */
    public function display_orders_page() {
        include_once PRINTIFY_PLUGIN_DIR . 'templates/admin/orders-page.php';
    }

    /**
     * Display the shops page
     */
    public function display_shops_page() {
        $page = new ShopsPage();
        $page->render();
    }

    /**
     * Display the exchange rates page
     */
    public function display_exchange_rates_page() {
        $page = new ExchangeRatesPage();
        $page->render();
    }

    /**
     * Display the postman page
     */
    public function display_postman_page() {
        $page = new PostmanPage();
        $page->render();
    }

    /**
     * Display the logs page
     */
    public function display_logs_page() {
        include_once PRINTIFY_PLUGIN_DIR . 'templates/admin/logs-page.php';
    }

    /**
     * Display the settings page
     */
    public function display_settings_page() {
        $page = new \Printify\Settings\SettingsPage();
        $page->render();
    }
}