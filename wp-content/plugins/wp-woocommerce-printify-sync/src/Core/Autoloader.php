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

        // First check if the file exists in src directory
        $src_file = $this->baseDir . 'src' . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
        
        if (file_exists($src_file)) {
            require_once $src_file;
            return;
        }
        
        // If not in src, check in the plugin root directory (for PrintifyAPI and PrintifyHttpClient)
        $root_file = $this->baseDir . basename(str_replace('\\', DIRECTORY_SEPARATOR, $relative_class)) . '.php';
        
        if (file_exists($root_file)) {
            require_once $root_file;
            return;
        }
    }
}
