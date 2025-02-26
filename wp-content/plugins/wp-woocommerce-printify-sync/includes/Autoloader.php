<?php
/**
 * Autoloader class for Printify Sync plugin
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */

namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Autoloader class
 */
class Autoloader {
    /**
     * Register the autoloader
     */
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Autoload classes based on namespace
     *
     * @param string $class_name Full class name including namespace
     */
    public function autoload($class_name) {
        // Check if the class is in our namespace
        $namespace = 'ApolloWeb\\WooCommercePrintifySync\\';
        if (strpos($class_name, $namespace) !== 0) {
            return;
        }

        // Get the relative class name
        $relative_class = substr($class_name, strlen($namespace));
        
        // Convert namespace separator to directory separator
        $file_path = str_replace('\\', '/', $relative_class);
        
        // Extract class name from path
        $path_parts = explode('/', $file_path);
        $class_file = array_pop($path_parts) . '.php'; // Use class name directly as file name
        
        // Build directory path
        $directory = '';
        if (!empty($path_parts)) {
            $directory = implode('/', $path_parts) . '/';
        }
        
        // Check in multiple possible locations
        $possible_locations = [
            WPTFY_PLUGIN_DIR . $directory . $class_file,            // Direct path from namespace
            WPTFY_PLUGIN_DIR . 'includes/' . $directory . $class_file, // In includes directory
            WPTFY_PLUGIN_DIR . 'admin/' . $class_file,              // In admin directory
        ];
        
        // Try to load the file from each possible location
        foreach ($possible_locations as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
}