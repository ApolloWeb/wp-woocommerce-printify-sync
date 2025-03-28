<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class DashboardProvider extends ServiceProvider {
    public function register(): void {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('wp_ajax_wpwps_dashboard_data', [$this, 'getDashboardData']);
    }

    public function registerMenu(): void {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-store',
            56
        );
    }

    public function renderDashboard(): void {
        echo View::render('wpwps-dashboard', [
            'title' => __('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'),
            'stats' => $this->getStats()
        ]);
    }

    private function getStats(): array {
        return [
            'products' => [
                'total' => 0,
                'synced' => 0,
                'failed' => 0
            ],
            'orders' => [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0
            ]
        ];
    }

    public function getDashboardData(): void {
        check_ajax_referer('wpwps_dashboard_nonce');
        wp_send_json_success($this->getStats());
    }
}