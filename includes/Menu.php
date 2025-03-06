<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Menu {
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Menu
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_menus']);
    }

    /**
     * Add menus and submenus
     */
    public function add_menus() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync',
            [$this, 'display_dashboard'],
            'dashicons-store',
            56
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync',
            [$this, 'display_dashboard']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-products',
            [ProductHelper::get_instance(), 'display_products_page']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-orders',
            [OrderHelper::get_instance(), 'display_orders_page']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-shipping',
            [$this, 'display_shipping_page']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Currency', 'wp-woocommerce-printify-sync'),
            __('Currency', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-currency',
            [CurrencyHelper::get_instance(), 'display_currency_page']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-logs',
            [LogHelper::get_instance(), 'display_logs_page']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-settings',
            [SettingsHelper::get_instance(), 'display_settings_page']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('API Tester', 'wp-woocommerce-printify-sync'),
            __('API Tester', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwprintifysync-api-tester',
            [PostmanManager::get_instance(), 'render_page']
        );
    }

    /**
     * Display dashboard page
     */
    public function display_dashboard() {
        echo '<div class="wrap"><h1>' . esc_html(get_admin_page_title()) . '</h1></div>';
    }

    /**
     * Display shipping page
     */
    public function display_shipping_page() {
        echo '<div class="wrap"><h1>' . esc_html(get_admin_page_title()) . '</h1></div>';
    }
}

// Initialize the menu
Menu::get_instance();