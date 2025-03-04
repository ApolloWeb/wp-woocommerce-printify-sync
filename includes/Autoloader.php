<?php
/**
 * Autoloader for WP WooCommerce Printify Sync
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Autoloader
 */
class Autoloader {
    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload function for registration with spl_autoload_register
     *
     * @param string $class Class name to load.
     */
    public static function autoload($class) {
        // Only handle classes in our namespace
        $namespace = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        if (strpos($class, $namespace) !== 0) {
            return;
        }

        // Remove the namespace prefix
        $class_name = substr($class, strlen($namespace));
        
        // Convert namespace separators to directory separators
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        
        // Build the file path
        $file_path = PRINTIFY_SYNC_PATH . 'includes' . DIRECTORY_SEPARATOR . $class_path . '.php';

        // If the file exists, load it
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            // Debug message for missing files
            if (defined('PRINTIFY_SYNC_DEBUG') && PRINTIFY_SYNC_DEBUG) {
                error_log("Autoloader could not find: {$file_path} for class: {$class}");
            }
        }
    }
}