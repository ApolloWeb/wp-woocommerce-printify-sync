<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Dashboard;

use ApolloWeb\WPWooCommercePrintifySync\Services\StockSync;

class StockSyncWidget {
    private $stock_sync;
    
    public function __construct(StockSync $stock_sync) {
        $this->stock_sync = $stock_sync;
        add_action('wp_dashboard_setup', [$this, 'register']);
    }

    public function register(): void {
        wp_add_dashboard_widget(
            'wpwps_stock_sync',
            __('Stock Sync Status', 'wp-woocommerce-printify-sync'),
            [$this, 'render']
        );
    }

    public function render(): void {
        $next_sync = wp_next_scheduled('wpwps_stock_sync');
        $last_sync = get_option('wpwps_last_stock_sync');
        $sync_stats = get_option('wpwps_stock_sync_stats', [
            'updated' => 0,
            'failed' => 0,
            'total' => 0
        ]);
        
        include WPWPS_PLUGIN_DIR . 'templates/admin/widgets/stock-sync.php';
    }
}
