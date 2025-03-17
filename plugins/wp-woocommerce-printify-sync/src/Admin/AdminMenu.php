<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminMenu
{
    private const CAPABILITY = 'manage_woocommerce';
    private const MENU_SLUG = 'wp-woocommerce-printify-sync';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuItems']);
    }

    public function addMenuItems(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderDashboard'],
            'dashicons-synchronization',
            56  // Position after WooCommerce
        );

        $this->addSubMenuPages();
    }

    private function addSubMenuPages(): void
    {
        $subPages = [
            'dashboard' => [
                'title' => __('Dashboard', 'wp-woocommerce-printify-sync'),
                'callback' => 'renderDashboard'
            ],
            'products' => [
                'title' => __('Products', 'wp-woocommerce-printify-sync'),
                'callback' => 'renderProducts'
            ],
            'webhooks' => [
                'title' => __('Webhooks', 'wp-woocommerce-printify-sync'),
                'callback' => 'renderWebhooks'
            ],
            'settings' => [
                'title' => __('Settings', 'wp-woocommerce-printify-sync'),
                'callback' => 'renderSettings'
            ]
        ];

        foreach ($subPages as $slug => $page) {
            add_submenu_page(
                self::MENU_SLUG,
                $page['title'],
                $page['title'],
                self::CAPABILITY,
                self::MENU_SLUG . '-' . $slug,
                [$this, $page['callback']]
            );
        }
    }

    public function renderDashboard(): void
    {
        require_once WPWPS_PLUGIN_DIR . '/templates/admin/dashboard.php';
    }

    public function renderProducts(): void
    {
        require_once WPWPS_PLUGIN_DIR . '/templates/admin/products.php';
    }

    public function renderWebhooks(): void
    {
        require_once WPWPS_PLUGIN_DIR . '/templates/admin/webhooks.php';
    }

    public function renderSettings(): void
    {
        require_once WPWPS_PLUGIN_DIR . '/templates/admin/settings.php';
    }
}