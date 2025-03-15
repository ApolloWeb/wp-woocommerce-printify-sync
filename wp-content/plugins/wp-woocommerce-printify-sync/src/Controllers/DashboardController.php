<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\DashboardDataProviderInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\SystemTimeInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\UserContext;

class DashboardController
{
    private DashboardDataProviderInterface $dataProvider;
    private SystemTimeInterface $systemTime;
    private UserContext $userContext;

    public function __construct(
        DashboardDataProviderInterface $dataProvider,
        SystemTimeInterface $systemTime,
        UserContext $userContext
    ) {
        $this->dataProvider = $dataProvider;
        $this->systemTime = $systemTime;
        $this->userContext = $userContext;

        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'registerMenuPages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenuPages(): void
    {
        if (!$this->userContext->hasPermission('manage_woocommerce')) {
            return;
        }

        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'data:image/svg+xml;base64,' . base64_encode($this->getTshirtIcon()),
            56
        );
    }

    public function renderDashboard(): void
    {
        if (!$this->userContext->hasPermission('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $data = [
            'total_products' => $this->dataProvider->getTotalProducts(),
            'synced_products' => $this->dataProvider->getSyncedProducts(),
            'pending_sync' => $this->dataProvider->getPendingSync(),
            'sync_errors' => $this->dataProvider->getSyncErrors(),
            'recent_activity' => $this->dataProvider->getRecentActivity(),
            'sync_history' => $this->dataProvider->getSyncHistory(),
            'product_categories' => $this->dataProvider->getProductCategories(),
            'last_sync' => $this->dataProvider->getLastSyncTime(),
            'current_user' => $this->userContext->getCurrentUserLogin(),
            'current_time' => $this->systemTime->formatDateTime(
                $this->systemTime->getCurrentUTCDateTime()
            )
        ];

        require_once WPWPS_PATH . 'templates/admin/dashboard.php';
    }

    private function getTshirtIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
            <!-- Font Awesome Free 6.5.1 t-shirt icon -->
            <path d="M211.8 0c7.8 0 14.3 5.7 16.7 13.2C234.1 30.1 256 46.7 256 64c0 17.3-21.9 33.9-27.5 50.8c-2.4 7.5-8.9 13.2-16.7 13.2H211c-7.9 0-14.4-5.7-16.7-13.2C188.7 97.9 167 81.3 167 64c0-17.3 21.7-33.9 27.3-50.8c2.4-7.5 8.9-13.2 16.7-13.2h.8zM107.2 128h.8c7.8 0 14.3 5.7 16.7 13.2c5.6 16.9 27.5 33.5 27.5 50.8c0 17.3-21.9 33.9-27.5 50.8c-2.4 7.5-8.9 13.2-16.7 13.2h-.8c-7.8 0-14.3-5.7-16.7-13.2C85.9 225.9 64 209.3 64 192c0-17.3 21.9-33.9 27.5-50.8c2.4-7.5 8.9-13.2 16.7-13.2zm297.3 0h.8c7.8 0 14.3 5.7 16.7 13.2c5.6 16.9 27.5 33.5 27.5 50.8c0 17.3-21.9 33.9-27.5 50.8c-2.4 7.5-8.9 13.2-16.7 13.2h-.8c-7.8 0-14.3-5.7-16.7-13.2C382.1 225.9 360 209.3 360 192c0-17.3 21.9-33.9 27.5-50.8c2.4-7.5 8.9-13.2 16.7-13.2z"/></svg>';
    }
}