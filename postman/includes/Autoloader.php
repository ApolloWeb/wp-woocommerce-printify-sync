<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader {

    /**
     * Registers the autoloader.
     */
    public static function register() {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoloads the classes.
     *
     * @param string $class The class name.
     */
    private static function autoload($class) {
        // Project-specific namespace prefix
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';

        // Base directory for the namespace prefix
        $base_dir = WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'includes/';

        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // No, move to the next registered autoloader
            return;
        }

        // Get the relative class name
        $relative_class = substr($class, $len);

        // Replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
}