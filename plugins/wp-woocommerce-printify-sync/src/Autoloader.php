<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader {
    /**
     * Register the autoloader.
     */
    public static function register(): void {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload the given class.
     *
     * @param string $class Fully-qualified class name.
     */
    public static function autoload(string $class): void {
        $prefix = __NAMESPACE__ . '\\';
        $baseDir = __DIR__ . '/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
