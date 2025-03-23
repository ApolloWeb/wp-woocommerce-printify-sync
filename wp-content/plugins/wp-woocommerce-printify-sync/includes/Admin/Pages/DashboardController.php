<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Admin\PageController;

class DashboardController extends PageController {
    public function __construct($template) {
        parent::__construct($template);
        $this->title = 'Dashboard';
        $this->addAction('Sync Now', 'sync-all', ['confirm' => 'Start full sync?'], 'btn-primary', 'fas fa-sync');
        $this->data['stats'] = $this->getStats();
    }

    protected function getTemplate(): string {
        return 'admin/dashboard';
    }

    private function getStats(): array {
        return [
            'api_calls' => get_option('wpwps_api_calls_today', 0),
            'products' => get_option('wpwps_product_count', 0),
            'orders' => get_option('wpwps_orders_today', 0),
            'last_sync' => get_option('wpwps_last_sync', '')
        ];
    }
}
