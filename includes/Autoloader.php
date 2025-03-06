<?php
/**
 * Autoloader Class
 * 
 * Handles automatic class loading with PSR-4 style namespaces.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader {
    /**
     * Namespace prefix for the plugin
     *
     * @var string
     */
    private $namespace_prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
    
    /**
     * Base directory for the namespace prefix
     *
     * @var string
     */
    private $base_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Base directory for the namespace prefix
        $this->base_dir = WPWPRINTIFYSYNC_PLUGIN_DIR;
    }
    
    /**
     * Register the autoloader
     */
    public function register() {
        spl_autoload_register([$this, 'load_class']);
    }
    
    /**
     * Load class
     *
     * @param string $class The fully-qualified class name
     * @return bool True if loaded, false otherwise
     */
    public function load_class($class) {
        // Does the class use the namespace prefix?
        $len = strlen($this->namespace_prefix);
        if (strncmp($this->namespace_prefix, $class, $len) !== 0) {
            // No, move to the next registered autoloader
            return false;
        }
        
        // Get the relative class name
        $relative_class = substr($class, $len);
        
        // Replace namespace separators with directory separators,
        // append with .php and prepend with base directory
        $file = $this->base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        
        return false;
    }
}