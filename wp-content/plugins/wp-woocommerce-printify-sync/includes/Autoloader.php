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
        
        // Create file path
        // Convert class name to file path
        // Example: Admin -> admin/class-admin.php
        $path_parts = explode('\\', $relative_class);
        $class_file = 'class-' . strtolower(array_pop($path_parts)) . '.php';
        
        if (!empty($path_parts)) {
            // If there are path parts (subdirectories), put the file in that directory
            $directory = strtolower(implode('/', $path_parts));
            $file = WPTFY_PLUGIN_DIR . $directory . '/' . $class_file;
        } else {
            // Check in includes directory first
            $file = WPTFY_PLUGIN_DIR . 'includes/' . $class_file;
            
            // If not found in includes, check in admin directory
            if (!file_exists($file)) {
                $file = WPTFY_PLUGIN_DIR . 'admin/' . $class_file;
            }
        }

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}