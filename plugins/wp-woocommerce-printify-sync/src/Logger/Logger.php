<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Logger;

class Logger {
    public function debug(string $message): void {
        error_log('[DEBUG] ' . $message);
    }
    public function info(string $message): void {
        error_log('[INFO] ' . $message);
    }
    public function warning(string $message): void {
        error_log('[WARNING] ' . $message);
    }
    public function error(string $message): void {
        error_log('[ERROR] ' . $message);
    }
}
