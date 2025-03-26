<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Autoloader {
    /**
     * Register the autoloader
     */
    public static function register(): void {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload callback
     */
    public static function autoload(string $class): void {
        // Only handle our namespace
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }

        // Convert namespace to file path
        $relative_class = substr($class, strlen($prefix));
        $file = WPWPS_PATH . 'src/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}