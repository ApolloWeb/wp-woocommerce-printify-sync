<?php
/**
 * Autoloader.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Autoloader class.
 */
class Autoloader {
    /**
     * Register autoloader.
     *
     * @return void
     */
    public static function register() {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload callback.
     *
     * @param string $class_name Class name to autoload.
     * @return void
     */
    public static function autoload($class_name) {
        // Check if the class is in our namespace.
        if (0 !== strpos($class_name, 'ApolloWeb\\WPWooCommercePrintifySync\\')) {
            return;
        }

        // Remove namespace from class name.
        $class_file = str_replace('ApolloWeb\\WPWooCommercePrintifySync\\', '', $class_name);
        // Convert class name format to file name format.
        $class_file = str_replace('\\', DIRECTORY_SEPARATOR, $class_file);
        // Get the file path.
        $file_path = WPWPS_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $class_file . '.php';

        // Include the file if it exists.
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}
