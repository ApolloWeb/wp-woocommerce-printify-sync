/**
 * Autoloader class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

namespace ApolloWeb\WooCommercePrintifySync;

class Autoloader {
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }

    public function autoload($class_name) {
        // Define base namespace
        $namespace = 'ApolloWeb\WooCommercePrintifySync\';

        // Ensure the class belongs to our plugin namespace
        if (strpos($class_name, $namespace) !== 0) {
            return;
        }

        // Get the relative class path
        $relative_class = str_replace($namespace, '', $class_name);
        $file_path = str_replace('\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

        // Define possible file locations
        $possible_locations = [
            WPTFY_PLUGIN_DIR . 'includes/' . $file_path, // Includes directory
            WPTFY_PLUGIN_DIR . 'admin/' . $file_path,    // Admin directory
        ];

        // Try to load the file
        foreach ($possible_locations as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }

        // Debugging: Log if the file was not found
        error_log("Autoloader could not find: " . $file_path);
    }
}
