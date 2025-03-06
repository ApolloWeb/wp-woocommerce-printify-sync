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
    }

    public static function displayDashboard() {
        echo '<h1>' . __('Printify Sync Dashboard', 'wp-woocommerce-printify-sync') . '</h1>';
    }

    public static function displayProductsPage() {
        echo '<h1>' . __('Products', 'wp-woocommerce-printify-sync') . '</h1>';
    }

    public static function displayOrdersPage() {
        echo '<h1>' . __('Orders', 'wp-woocommerce-printify-sync') . '</h1>';
    }

    public static function displayShippingPage() {
        echo '<h1>' . __('Shipping', 'wp-woocommerce-printify-sync') . '</h1>';
    }

    public static function displayCurrencyPage() {
        echo '<h1>' . __('Currency', 'wp-woocommerce-printify-sync') . '</h1>';
    }
}