<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;

class Cron {
    private $logger_service;

    public function __construct() {
        $this->logger_service = new LoggerService();
        add_action('wpwps_cleanup_logs', [$this, 'cleanupLogs']);
    }

    public function cleanupLogs(): void {
        $this->logger_service->cleanupLogs(30);
    }
}