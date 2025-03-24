<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Autoloader {
    private string $namespace;
    private string $baseDir;

    public function __construct(string $namespace, string $baseDir) {
        $this->namespace = $namespace;
        $this->baseDir = $baseDir;
    }

    public function register(): void {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass(string $class): void {
        $prefix = $this->namespace . '\\';
        $len = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $this->baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException(sprintf(
                'Class file not found: %s (Looking in: %s)',
                $class,
                $file
            ));
        }

        require_once $file;

        if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
            throw new \RuntimeException(sprintf(
                'Class %s not found in file: %s',
                $class,
                $file
            ));
        }
    }
}
