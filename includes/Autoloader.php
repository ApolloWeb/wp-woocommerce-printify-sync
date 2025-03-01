<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader {
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload($class) {
        if (strpos($class, __NAMESPACE__) === 0) {
            $class_name = str_replace(__NAMESPACE__ . '\\', '', $class);
            $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
            $paths = [
                plugin_dir_path(__FILE__) . $class_name . '.php',
                plugin_dir_path(__DIR__) . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . $class_name . '.php',
                plugin_dir_path(__DIR__) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $class_name . '.php',
            ];

            foreach ($paths as $file) {
                if (file_exists($file)) {
                    require $file;
                    break;
                }
            }
        }
    }
}