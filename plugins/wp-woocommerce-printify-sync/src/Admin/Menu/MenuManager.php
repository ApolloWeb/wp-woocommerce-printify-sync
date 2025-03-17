<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Menu;

class MenuManager
{
    private const CAPABILITY = 'manage_options';
    private const MENU_SLUG = 'wpwps-dashboard';

    public function initialize(): void
    {
        add_action('admin_menu', [$this, 'registerMenuItems']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenuItems(): void
    {
        // Add main menu item
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderDashboard'],
            'fa fa-tshirt', // Changed to Font Awesome icon
            30
        );

        // Add submenu items
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            self::CAPABILITY,
            self::MENU_SLUG . '-products',
            [$this, 'renderProducts']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            self::CAPABILITY,
            self::MENU_SLUG . '-orders',
            [$this, 'renderOrders']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            self::CAPABILITY,
            self::MENU_SLUG . '-settings',
            [$this, 'renderSettings']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        // Only load assets on our plugin pages
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'wpwps-admin',
            plugins_url('assets/css/admin/style.css', WPWPS_PLUGIN_FILE),
            [],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-admin',
            plugins_url('assets/js/admin/admin.js', WPWPS_PLUGIN_FILE),
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-admin', 'wpwpsAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_admin'),
            'i18n' => [
                'error' => __('Error', 'wp-woocommerce-printify-sync'),
                'success' => __('Success', 'wp-woocommerce-printify-sync'),
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
            ]
        ]);
    }

    public function renderDashboard(): void
    {
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function renderProducts(): void
    {
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/products.php';
    }

    public function renderOrders(): void
    {
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/orders.php';
    }

    public function renderSettings(): void
    {
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}