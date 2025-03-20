<?php
/**
 * PSR-4 Autoloader
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}

/**
 * Simple PSR-4 autoloader for the plugin
 */
spl_autoload_register(function ($class) {
    // Plugin namespace prefix
    $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Base directory for the namespace prefix
    $base_dir = plugin_dir_path(__FILE__);
    
    // Replace namespace separators with directory separators in the relative class name
    // Replace "\" with "/"
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});
