<?php


/**
 * Autoloader
 *
 * This class provides a PSR-4 compliant autoloader for the plugin.
 * It ensures that all classes under the ApolloWeb\WPWooCommercePrintifySync namespace
 * are automatically loaded from the 'includes/' directory.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader {
    /**
     * Registers the autoloader function.
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoloads classes based on their namespace and directory structure.
     *
     * @param string $class The fully qualified class name.
     */
    private static function autoload($class) {
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        $base_dir = __DIR__ . '/includes/'; // Adjusted to load from 'includes/'

        // Ensure the class uses the correct namespace prefix
        if (strpos($class, $prefix) !== 0) {
            return;
        }

        // Get the relative class name
        $relative_class = substr($class, strlen($prefix));
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // Check if the file exists, then require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Register the autoloader
Autoloader::register();


