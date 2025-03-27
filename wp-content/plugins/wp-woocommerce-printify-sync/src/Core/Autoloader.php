<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(function ($class) {
            $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
            $base_dir = dirname(__DIR__);

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $file = $base_dir . '/' . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }
}