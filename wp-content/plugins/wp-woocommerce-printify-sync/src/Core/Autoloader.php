<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Autoloader
{
    private string $namespace;
    private string $baseDir;

    public function __construct(string $namespace, string $baseDir)
    {
        $this->namespace = $namespace;
        $this->baseDir = $baseDir;
        $this->register();
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass(string $class): void
    {
        // Check if the class uses our namespace
        if (strpos($class, $this->namespace) !== 0) {
            return;
        }

        // Remove namespace from class name
        $className = str_replace($this->namespace . '\\', '', $class);

        // Convert namespace separator to directory separator
        $filePath = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        // Build the complete file path
        $file = $this->baseDir . DIRECTORY_SEPARATOR . $filePath . '.php';

        // Include the file if it exists
        if (!file_exists($file)) {
            throw new \RuntimeException(
                sprintf('Class file not found: %s. Looking in: %s', $class, $file)
            );
        }

        require_once $file;

        if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
            throw new \RuntimeException(
                sprintf('Class %s not found in file: %s', $class, $file)
            );
        }
    }
}
