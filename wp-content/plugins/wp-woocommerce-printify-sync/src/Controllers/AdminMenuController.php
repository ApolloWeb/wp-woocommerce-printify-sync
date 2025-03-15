<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

class AdminMenuController
{
    private const MENU_SLUG = 'wpwps-dashboard';
    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime;
        $this->currentUser = $currentUser;
        
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (!str_contains($hook, 'wpwps')) {
            return;
        }

        wp_enqueue_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            [],
            '6.5.1'
        );

        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_URL . 'assets/css/admin.css',
            ['wpwps-fontawesome'],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-admin',
            WPWPS_URL . 'assets/js/admin.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin'),
            'current_time' => $this->currentTime,
            'current_user' => $this->currentUser
        ]);
    }

    public function registerMenus(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            self::MENU_SLUG,
            [$this, 'renderDashboard'],
            'dashicons-admin-generic', // Temporary, will be overridden by CSS
            56
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            self::MENU_SLUG,
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Import Products', 'wp-woocommerce-printify-sync'),
            __('Import Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'renderProducts']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
    }

    public function renderDashboard(): void
    {
        require_once WPWPS_PATH . 'templates/admin/dashboard.php';
    }

    public function renderProducts(): void
    {
        require_once WPWPS_PATH . 'templates/admin/products.php';
    }

    public function renderSettings(): void
    {
        require_once WPWPS_PATH . 'templates/admin/settings.php';
    }
}