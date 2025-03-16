<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Page\{
    DashboardPage,
    SettingsPage,
    ProductsPage,
    OrdersPage,
    TicketsPage,
    LogViewerPage,
    ExchangeRatesPage
};

class AdminMenu
{
    private array $pages;

    public function __construct(
        DashboardPage $dashboard,
        SettingsPage $settings,
        ProductsPage $products,
        OrdersPage $orders,
        TicketsPage $tickets,
        LogViewerPage $logViewer,
        ExchangeRatesPage $exchangeRates
    ) {
        $this->pages = [
            $dashboard,
            $products,
            $orders,
            $tickets,
            $exchangeRates,
            $logViewer,
            $settings
        ];
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_head', [$this, 'addFontAwesomeStyle']);
    }

    public function addMenuPages(): void
    {
        // Add main menu with Font Awesome t-shirt icon
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this->pages[0], 'render'],
            'data:image/svg+xml;base64,' . base64_encode($this->getTshirtIcon()),
            58 // After WooCommerce
        );

        // Add submenu pages
        foreach ($this->pages as $page) {
            $page->register();
        }
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }

        // Enqueue Bootstrap
        wp_enqueue_style(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
            [],
            '5.1.3'
        );

        wp_enqueue_script(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.1.3',
            true
        );

        // Enqueue Font Awesome
        wp_enqueue_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            [],
            '6.0.0'
        );

        // Core admin assets
        wp_enqueue_style(
            'wpwps-admin-core',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/admin-core.css',
            ['wpwps-bootstrap', 'wpwps-fontawesome'],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-admin-core',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/admin-core.js',
            ['jquery', 'wpwps-bootstrap'],
            WPWPS_VERSION,
            true
        );

        // Page specific assets
        foreach ($this->pages as $page) {
            if (strpos($hook, $page->getMenuSlug()) !== false) {
                $page->enqueueAssets();
            }
        }
    }

    private function getTshirtIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
            <path fill="currentColor" d="M631.2 96.5L436.5 0C416.4 27.8 371.9 47.2 320 47.2S223.6 27.8 203.5 0L8.8 96.5c-7.9 4-11.1 13.6-7.2 21.5l57.2 114.5c4 7.9 13.6 11.1 21.5 7.2l56.6-27.7c10.6-5.2 23 2.5 23 14.4V480c0 17.7 14.3 32 32 32h256c17.7 0 32-14.3 32-32V226.3c0-11.8 12.4-19.6 23-14.4l56.6 27.7c7.9 4 17.5.8 21.5-7.2L638.3 118c4-7.9.8-17.6-7.1-21.5z"/>
        </svg>';
    }

    public function addFontAwesomeStyle(): void
    {
        echo '<style>
            #adminmenu .toplevel_page_wpwps-dashboard .wp-menu-image img {
                width: 20px;
                height: 20px;
                padding: 7px 0;
                opacity: 0.6;
            }
            
            #adminmenu .toplevel_page_wpwps-dashboard:hover .wp-menu-image img,
            #adminmenu .toplevel_page_wpwps-dashboard.current .wp-menu-image img {
                opacity: 1;
            }
        </style>';
    }
}