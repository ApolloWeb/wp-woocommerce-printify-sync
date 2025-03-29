<?php
/**
 * PSR-4 Compliant Autoloader
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Autoloader class for the plugin
 */
class Autoloader
{
    /**
     * Register the autoloader
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Load a class based on its namespace
     *
     * @param string $class The class name to load
     * @return void
     */
    public function loadClass($class)
    {
        // Check if the class is part of our namespace
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        $len = strlen($prefix);
        
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get the relative class name
        $relative_class = substr($class, $len);
        
        // Replace namespace separators with directory separators
        $file = WPWPS_PLUGIN_DIR . 'src/' . str_replace('\\', '/', $relative_class) . '.php';
        
        // If the file exists, load it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}