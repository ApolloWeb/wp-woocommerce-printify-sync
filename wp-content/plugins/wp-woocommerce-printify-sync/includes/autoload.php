<?php

spl_autoload_register(function($class) {
    // Plugin namespace prefix
    $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
    $base_dir = dirname(__DIR__) . '/src/';

    // Check if class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Load file if it exists
    if (file_exists($file)) {
        require $file;
    }
});
