<?php
/**
 * Autoloader class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Class Autoloader
 */
class Autoloader
{
    /**
     * Register the autoloader
     *
     * @return void
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * Load a class
     *
     * @param string $class The fully-qualified class name
     * @return void
     */
    public static function loadClass(string $class): void
    {
        // Check if the class is in our namespace
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        $baseDir = WPPS_PLUGIN_DIR . 'includes/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relativeClass = substr($class, $len);

        // Replace namespace separator with directory separator
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}