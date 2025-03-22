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
        // Only handle classes in our namespace
        if (strpos($class, $this->namespacePrefix) !== 0) {
            return;
        }

        // Get the relative class name
        $relativeClass = substr($class, strlen($this->namespacePrefix));

        // Replace namespace separators with directory separators
        $filePath = str_replace('\\', '/', $relativeClass) . '.php';

        // Get the full file path
        $file = $this->baseDir . $filePath;

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
    
    /**
     * Handle missing classes gracefully.
     *
     * This method is used to prevent syntax errors during the diagnostic process
     * when scanning for class references.
     *
     * @param string $className The name of the class to check.
     * @return bool Whether the class can be safely ignored.
     */
    public function handleMissingClass(string $className): bool
    {
        // Log the missing class for debugging
        if (function_exists('error_log')) {
            error_log('Attempted to load non-existent class: ' . $className);
        }
        
        // Return true to indicate we've handled the missing class
        return true;
    }
}
