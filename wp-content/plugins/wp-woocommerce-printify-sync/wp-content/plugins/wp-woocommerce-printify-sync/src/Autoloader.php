<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader
{
    private string $namespace;
    private string $basePath;

    public function __construct(string $namespace, string $basePath)
    {
        $this->namespace = $namespace;
        $this->basePath = $basePath;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    private function loadClass(string $class): void
    {
        // Check if the class uses our namespace
        if (strpos($class, $this->namespace) !== 0) {
            return;
        }

        // Remove namespace from class name
        $relativeClass = substr($class, strlen($this->namespace));

        // Convert namespace separators to directory separators
        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        // Build full file path
        $filePath = $this->basePath . DIRECTORY_SEPARATOR . $classPath;

        // Load the file if it exists
        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
}
