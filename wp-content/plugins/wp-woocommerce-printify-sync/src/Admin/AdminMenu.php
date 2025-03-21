<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\DashboardPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\SettingsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\ProductsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\OrdersPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\ShippingPage;

class AdminMenu
{
    public const MENU_SLUG = 'wpwps-dashboard';
    private array $pages = [];

    private array $pageClasses = [
        'dashboard' => DashboardPage::class,
        'settings' => SettingsPage::class,
        'products' => ProductsPage::class,
        'orders' => OrdersPage::class,
        'shipping' => ShippingPage::class,
    ];

    public function register(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderPage'],
            'dashicons-store',
            56
        );

        $this->registerSubMenus();
    }

    private function registerSubMenus(): void
    {
        $submenus = [
            'dashboard' => [
                'title' => __('Dashboard', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderPage']
            ],
            'settings' => [
                'title' => __('Settings', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderPage']
            ],
            'products' => [
                'title' => __('Products', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderPage']
            ],
            'orders' => [
                'title' => __('Orders', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderPage']
            ],
            'shipping' => [
                'title' => __('Shipping', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderPage']
            ],
        ];

        foreach ($submenus as $slug => $menu) {
            add_submenu_page(
                self::MENU_SLUG,
                $menu['title'],
                $menu['title'],
                'manage_options',
                'wpwps-' . $slug,
                function() use ($slug) {
                    $this->renderPage($slug);
                }
            );
        }
    }

    public function renderPage(string $slug = 'dashboard'): void
    {
        if (!isset($this->pageClasses[$slug])) {
            wp_die(sprintf('Page %s not found', $slug));
        }

        $className = $this->pageClasses[$slug];
        $page = new $className();
        $page->render();
    }
}
