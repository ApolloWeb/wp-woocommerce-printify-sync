<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Autoloader for WP WooCommerce Printify Sync
 */
class Autoloader {
    /**
     * Register the autoloader
     *
     * @return void
     */
    public static function register() {
        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * Autoload function for class loading
     *
     * @param string $class The class name to load
     * @return void
     */
    public static function loadClass($class) {
        // Check if class is in our namespace
        $namespace = 'ApolloWeb\\WooCommercePrintifySync\\';
        if (strpos($class, $namespace) !== 0) {
            return;
        }

        // Remove namespace from class name
        $relative_class = substr($class, strlen($namespace));
        
        // Handle different directories based on class naming
        if ($relative_class === 'Admin') {
            // Admin main class is in the admin directory
            $file = WPWPS_PLUGIN_DIR . 'admin/Admin.php';
        } elseif (strpos($relative_class, 'Admin\\') === 0) {
            // Admin subnamespace classes
            $file = WPWPS_PLUGIN_DIR . 'admin/' . substr($relative_class, strlen('Admin\\')) . '.php';
        } elseif (strpos($relative_class, 'Helpers\\') === 0) {
            // Helper classes
            $file = WPWPS_PLUGIN_DIR . 'includes/Helpers/' . substr($relative_class, strlen('Helpers\\')) . '.php';
        } else {
            // Default - in the includes directory
            $file = WPWPS_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';
        }

        // Include the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}