<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class MenuManager
{
    private string $currentTime;
    private string $currentUser;
    private UIHelper $uiHelper;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:41:59';
        $this->currentUser = 'ApolloWeb';
        
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_filter('woocommerce_screen_ids', [$this, 'addScreenIds']);
    }

    public function addMenuPages(): void
    {
        // Add main menu under WooCommerce
        add_submenu_page(
            'woocommerce',
            'Printify Sync',
            'Printify Sync',
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard']
        );

        // Add submenus
        add_submenu_page(
            'wpwps-dashboard',
            'Products',
            'Products',
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'renderProducts']
        );

        add_submenu_page(
            'wpwps-dashboard',
            'Settings',
            'Settings',
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettings']
        );

        add_submenu_page(
            'wpwps-dashboard',
            'Logs',
            'Logs',
            'manage_woocommerce',
            'wpwps-logs',
            [$this, 'renderLogs']
        );

        add_submenu_page(
            'wpwps-dashboard',
            'Data Cleanup',
            'Data Cleanup',
            'manage_woocommerce',
            'wpwps-cleanup',
            [$this, 'renderCleanup']
        );
    }

    public function renderDashboard(): void
    {
        // Dashboard template
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function renderProducts(): void
    {
        // Products template
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/products.php';
    }

    public function renderSettings(): void
    {
        // Settings template
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    public function renderLogs(): void
    {
        // Logs template
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/logs.php';
    }

    public function renderCleanup(): void
    {
        // Cleanup template
        require_once WPWPS_PLUGIN_DIR . 'templates/admin/cleanup.php';
    }

    public function addScreenIds(array $screenIds): array
    {
        $screenIds[] = 'woocommerce_page_wpwps-dashboard';
        $screenIds[] = 'woocommerce_page_wpwps-products';
        $screenIds[] = 'woocommerce_page_wpwps-settings';
        $screenIds[] = 'woocommerce_page_wpwps-logs';
        $screenIds[] = 'woocommerce_page_wpwps-cleanup';
        
        return $screenIds;
    }
}