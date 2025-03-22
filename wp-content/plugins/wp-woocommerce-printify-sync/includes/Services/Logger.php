<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class Logger {
    private $log_dir;

    public function __construct() {
        $this->log_dir = WP_CONTENT_DIR . '/logs/wpps';
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
    }

    public function log($message, string $type = 'info'): void {
        $file = $this->log_dir . '/' . date('Y-m-d') . '.log';
        $time = date('Y-m-d H:i:s');
        $formatted = "[{$time}] [{$type}] {$message}" . PHP_EOL;
        error_log($formatted, 3, $file);
    }
}
