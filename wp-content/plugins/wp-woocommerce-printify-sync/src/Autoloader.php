<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Class Autoloader
 * 
 * Custom autoloader for PSR-12 compliant class loading
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */
class Autoloader
{
    /**
     * Namespace prefix for all plugin classes.
     * 
     * @var string
     */
    private $namespacePrefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';

    /**
     * Base directory for the namespace prefix.
     * 
     * @var string
     */
    private $baseDir;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->baseDir = WPWPS_PLUGIN_DIR . 'src/';
    }

    /**
     * Register the autoloader.
     * 
     * @return void
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Load a class by its fully qualified name.
     *
     * @param string $class The fully qualified class name.
     * @return void
     */
    public function loadClass(string $class): void
    {
        // Check if the class uses our namespace prefix
        $len = strlen($this->namespacePrefix);
        if (strncmp($this->namespacePrefix, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relativeClass = substr($class, $len);
        
        // Replace namespace separators with directory separators
        $file = $this->baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        // Load the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
