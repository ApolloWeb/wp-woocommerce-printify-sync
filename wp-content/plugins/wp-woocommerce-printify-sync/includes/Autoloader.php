<?php
/**
 * Autoloader for the plugin.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Class Autoloader
 */
class Autoloader {
    /**
     * Register the autoloader.
     *
     * @return void
     */
    public static function register() {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload classes based on namespace and class name.
     *
     * @param string $class_name The fully qualified class name.
     * @return void
     */
    public static function autoload($class_name) {
        // Only handle classes in our namespace
        $namespace = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        if (strpos($class_name, $namespace) !== 0) {
            return;
        }

        // Remove namespace from class name
        $class_name = str_replace($namespace, '', $class_name);
        
        // Convert class name to file path
        $file_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        $file = WPWPS_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . $file_path . '.php';

        // Include the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
