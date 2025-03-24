<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class StockSync {
    private $api_service;
    private $rate_limiter;
    private $logger;

    public function __construct($api_service, $rate_limiter, $logger) {
        $this->api_service = $api_service;
        $this->rate_limiter = $rate_limiter;
        $this->logger = $logger;

        // Register cron hook
        add_action('wpwps_stock_sync', [$this, 'syncStockLevels']);
    }

    public function scheduleCron(): void {
        if (!wp_next_scheduled('wpwps_stock_sync')) {
            wp_schedule_event(time(), 'wpwps_stock_sync', 'wpwps_stock_sync');
        }
    }

    public function syncStockLevels(): void {
        $this->logger->info('Starting stock level sync');
        // Implement stock sync logic here
    }
}
