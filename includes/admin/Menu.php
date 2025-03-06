<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Menu {
    
    public static function registerMenus() {
        add_action('admin_menu', [self::class, 'addAdminMenu']);
    }

    public static function addAdminMenu() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync',
            [self::class, 'displayDashboard'],
            'dashicons-store', // This will be replaced by Font Awesome icon
            56
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync',
            [self::class, 'displayDashboard']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Shops', 'wp-woocommerce-printify-sync'),
            __('Shops', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-shops',
            [self::class, 'displayShopsPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-products',
            [self::class, 'displayProductsPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-orders',
            [self::class, 'displayOrdersPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Shipping', 'wp-woocommerce-printify-sync'),
            __('Shipping', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-shipping',
            [self::class, 'displayShippingPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Currency', 'wp-woocommerce-printify-sync'),
            __('Currency', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-currency',
            [self::class, 'displayCurrencyPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-settings',
            [self::class, 'displaySettingsPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-logs',
            [self::class, 'displayLogsPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Reports', 'wp-woocommerce-printify-sync'),
            __('Reports', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-reports',
            [self::class, 'displayReportsPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Tools', 'wp-woocommerce-printify-sync'),
            __('Tools', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-tools',
            [self::class, 'displayToolsPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Tickets', 'wp-woocommerce-printify-sync'),
            __('Tickets', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-tickets',
            [self::class, 'displayTicketsPage']
        );

        add_submenu_page(
            'wpwprintifysync',
            __('Postman', 'wp-woocommerce-printify-sync'),
            __('Postman', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwprintifysync-postman',
            [self::class, 'displayPostmanPage']
        );
    }

    public static function displayDashboard() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public static function displayProductsPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/products-page.php';
    }

    public static function displayShopsPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/shops-page.php'; // Placeholder template
    }

    public static function displayOrdersPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/orders-page.php';
    }

    public static function displayShippingPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/shipping-page.php'; // Placeholder template
    }

    public static function displayCurrencyPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/currency-page.php'; // Placeholder template
    }

    public static function displaySettingsPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    public static function displayLogsPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/logs-page.php'; // Placeholder template
    }

    public static function displayReportsPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/reports-page.php'; // Placeholder template
    }

    public static function displayToolsPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/tools-page.php'; // Placeholder template
    }

    public static function displayTicketsPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/tickets-page.php'; // Placeholder template
    }

    public static function displayPostmanPage() {
        include WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/postman-page.php'; // Placeholder template
    }
}