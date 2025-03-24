<?php
/**
 * Autoloader class for the plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Class Autoloader
 *
 * Handles PSR-4 autoloading for the plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */
class Autoloader
{
    /**
     * Registers the autoloader
     *
     * @return void
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload callback
     *
     * @param string $class The fully-qualified class name.
     * @return void
     */
    public static function autoload(string $class): void
    {
        // Check if the class is in our namespace.
        if (0 !== strpos($class, 'ApolloWeb\\WPWooCommercePrintifySync\\')) {
            return;
        }

        // Convert namespace to file path.
        $relative_class_name = substr($class, strlen('ApolloWeb\\WPWooCommercePrintifySync\\'));
        $file_path = WPWPS_PLUGIN_DIR . 'src/' . str_replace('\\', '/', $relative_class_name) . '.php';

        // If the file exists, require it.
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}
