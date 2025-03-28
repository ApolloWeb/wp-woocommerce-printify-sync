<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Autoloader {
    public function register(): void {
        spl_autoload_register([$this, 'loadClass']);
    }

    private function loadClass(string $class): void {
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        $baseDir = WPWPS_PATH . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
}