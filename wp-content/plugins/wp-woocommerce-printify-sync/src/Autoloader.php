<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader {
    /**
     * Plugin namespace
     */
    private const NAMESPACE = 'ApolloWeb\\WPWooCommercePrintifySync\\';

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
        // Check if class uses our namespace
        if (strpos($class, self::NAMESPACE) !== 0) {
            return;
        }

        // Remove namespace from class name
        $class_path = str_replace(self::NAMESPACE, '', $class);
        
        // Convert namespace separators to directory separators
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_path);
        
        // Build full path to class file
        $file = PRINTIFY_SYNC_PATH . 'src' . DIRECTORY_SEPARATOR . $class_path . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
