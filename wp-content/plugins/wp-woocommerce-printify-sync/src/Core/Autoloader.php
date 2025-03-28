<?php
/**
 * PSR-4 Autoloader implementation
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * PSR-4 compliant autoloader for plugin classes
 */
class Autoloader
{
    /**
     * Base namespace for the plugin
     *
     * @var string
     */
    protected $baseNamespace = 'ApolloWeb\\WPWooCommercePrintifySync\\';

    /**
     * Base directory for the plugin's classes
     *
     * @var string
     */
    protected $baseDir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseDir = WPWPS_PLUGIN_DIR . 'src/';
    }

    /**
     * Register the autoloader
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Autoload function for class files
     *
     * @param string $class Full class name
     * @return void
     */
    public function autoload($class)
    {
        // Only load classes from our namespace
        if (strpos($class, $this->baseNamespace) !== 0) {
            return;
        }

        // Remove base namespace
        $relativeClass = substr($class, strlen($this->baseNamespace));

        // Replace namespace separators with directory separators
        $filePath = $this->baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        // Load the file if it exists
        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
}