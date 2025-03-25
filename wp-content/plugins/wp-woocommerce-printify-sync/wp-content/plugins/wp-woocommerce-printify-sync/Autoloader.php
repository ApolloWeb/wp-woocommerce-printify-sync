<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(function ($class) {
            // Define the namespace prefix for our plugin
            $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
            
            // Base directory for the namespace prefix
            $base_dir = plugin_dir_path(__FILE__);
            
            // Get the relative class name
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                // Class doesn't belong to this namespace, skip
                return;
            }
            
            // Get the relative class name
            $relative_class = substr($class, $len);
            
            // Check if the class is in the src directory
            // For classes like 'Plugin', 'Admin\Pages\DashboardPage', etc.
            $file = $base_dir . 'src/' . str_replace('\\', '/', $relative_class) . '.php';
            
            // If file exists, require it
            if (file_exists($file)) {
                require_once $file;
                return;
            }
            
            // If not in src, try the base directory
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
            
            // Log failure if debug mode is on
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("WPWPS Autoloader: Failed to load class {$class}. File {$file} not found.");
            }
        });
    }
}

// Register autoloader
Autoloader::register();