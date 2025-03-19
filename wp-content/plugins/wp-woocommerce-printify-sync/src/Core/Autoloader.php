<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Autoloader
{
    private $namespace;
    private $baseDir;

    public function __construct($namespace, $baseDir)
    {
        $this->namespace = $namespace;
        $this->baseDir = $baseDir;
    }

    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass($class)
    {
        // Check if the class uses our namespace
        $len = strlen($this->namespace);
        if (strncmp($this->namespace, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relative_class = substr($class, $len);

        // Convert namespace separators to directory separators and prefix with src
        $file = $this->baseDir . 'src/' . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
}
