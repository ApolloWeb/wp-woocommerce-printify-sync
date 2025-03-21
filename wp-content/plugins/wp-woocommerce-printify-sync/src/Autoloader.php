<?php
/**
 * Autoloader class for WP WooCommerce Printify Sync plugin.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Autoloader class for loading classes automatically.
 */
class Autoloader
{
    /**
     * Register the autoloader.
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload classes from the ApolloWeb\WPWooCommercePrintifySync namespace.
     *
     * @param string $class The fully-qualified class name.
     * @return void
     */
    public static function autoload($class)
    {
        // Check if the class belongs to our namespace
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        $base_dir = WPWPS_PLUGIN_DIR . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relative_class = substr($class, $len);
        
        // Replace namespace separators with directory separators
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
