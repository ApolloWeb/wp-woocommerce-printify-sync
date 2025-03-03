<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader
{
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload($class)
    {
        if (strpos($class, __NAMESPACE__) === 0) {
            $class_name = str_replace(__NAMESPACE__ . '\\', '', $class);
            $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
            $file = plugin_dir_path(__FILE__) . $class_name . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
}