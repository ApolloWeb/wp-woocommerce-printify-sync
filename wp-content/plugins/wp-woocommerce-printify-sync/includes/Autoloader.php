<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * PSR-4 Autoloader
 */
class Autoloader {
    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload classes
     *
     * @param string $class_name Full class name including namespace
     */
    public static function autoload($class_name) {
        // Only handle our namespace
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        $prefix_length = strlen($prefix);

        if (strncmp($prefix, $class_name, $prefix_length) !== 0) {
            return;
        }

        // Get the relative class name
        $relative_class = substr($class_name, $prefix_length);

        // Convert namespace to path
        $file = WWPS_PATH . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';

        // Require the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
