<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Class Autoloader
 * 
 * PSR-4 compliant autoloader
 */
class Autoloader {
    /**
     * Register the autoloader
     */
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }
    
    /**
     * Autoload callback
     *
     * @param string $class_name Fully qualified class name
     */
    public function autoload($class_name) {
        // Only handle classes in our namespace
        $namespace = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        if (strpos($class_name, $namespace) !== 0) {
            return;
        }
        
        // Remove the namespace from the class name
        $class_name = str_replace($namespace, '', $class_name);
        
        // Convert class name to file path
        $file_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
        $file = WPWPS_PATH . 'includes/' . $file_path;
        
        // Include the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
