<?php
spl_autoload_register(function ($class) {
    // Plugin namespace prefix
    $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
    $prefix_len = strlen($prefix);

    // Does the class use the namespace prefix?
    if (strncmp($prefix, $class, $prefix_len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $prefix_len);

    // Debug logging for development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Attempting to load class: {$class}");
    }

    // Convert class name to file path
    $file = WPPS_INCLUDES_PATH . str_replace('\\', '/', $relative_class) . '.php';

    if (!file_exists($file)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Class file not found: {$file}");
        }
        return;
    }

    require_once $file;

    if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
        throw new \Exception("Failed loading {$class} from {$file}");
    }
});
