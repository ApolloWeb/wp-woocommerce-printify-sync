<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    protected static function autoload(string $class): void
    {
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        $base_dir = __DIR__ . '/../../';

        if (strpos($class, $prefix) !== 0) return;

        $relative_class = substr($class, strlen($prefix));
        $file = $base_dir . 'src/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
