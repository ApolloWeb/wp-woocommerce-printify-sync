<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Cron;

class StockSyncCron {
    public function __construct() {
        add_action('wp', [$this, 'scheduleCron']);
        add_action('stock_sync_update', [$this, 'updateStockLevels']);
    }

    public function scheduleCron() {
        if (!wp_next_scheduled('stock_sync_update')) {
            wp_schedule_event(time(), 'hourly', 'stock_sync_update');
        }
    }

    public function updateStockLevels() {
        // Code to update stock levels
    }
}