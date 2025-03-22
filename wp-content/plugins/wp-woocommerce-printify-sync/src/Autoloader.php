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
     * Classes that have been already loaded or attempted to load
     * 
     * @var array
     */
    private $loadedClasses = [];

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
        // Skip if we've already tried to load this class
        if (isset($this->loadedClasses[$class])) {
            return;
        }
        
        // Mark this class as processed to avoid infinite recursion
        $this->loadedClasses[$class] = false;
        
        // Only handle classes in our namespace
        if (strpos($class, $this->namespacePrefix) !== 0) {
            return;
        }

        try {
            // Get the relative class name
            $relativeClass = substr($class, strlen($this->namespacePrefix));

            // Replace namespace separators with directory separators
            $filePath = str_replace('\\', '/', $relativeClass) . '.php';

            // Get the full file path
            $file = $this->baseDir . $filePath;

            // If the file exists, require it
            if (file_exists($file)) {
                // Use include instead of require_once during diagnostics to prevent fatal errors
                if (defined('WPWPS_RUNNING_DIAGNOSTICS') && WPWPS_RUNNING_DIAGNOSTICS) {
                    // Try to suppress parse errors during diagnostics
                    $content = file_get_contents($file);
                    if ($content !== false && $this->validatePhpSyntax($content)) {
                        include $file;
                        $this->loadedClasses[$class] = true;
                    } else {
                        // File exists but has syntax errors
                        error_log("Syntax error in file: $file");
                    }
                } else {
                    // Normal operation - use require_once for better performance
                    require_once $file;
                    $this->loadedClasses[$class] = true;
                }
            }
        } catch (\Throwable $e) {
            // Log the error but don't crash
            if (function_exists('error_log')) {
                error_log('Error loading class ' . $class . ': ' . $e->getMessage());
            }
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
    
    /**
     * Basic validation of PHP syntax without actually executing the code
     *
     * @param string $code PHP code to validate
     * @return bool Whether the code appears to be valid PHP
     */
    private function validatePhpSyntax(string $code): bool
    {
        // Extremely basic validation to catch common syntax errors
        // This isn't foolproof but can catch obvious issues
        
        // Check for mismatched brackets/braces
        $openBraces = substr_count($code, '{');
        $closeBraces = substr_count($code, '}');
        if ($openBraces !== $closeBraces) {
            return false;
        }
        
        $openParens = substr_count($code, '(');
        $closeParens = substr_count($code, ')');
        if ($openParens !== $closeParens) {
            return false;
        }
        
        // Check for PHP 8 attributes which could cause issues during scanning
        if (preg_match('/#\[(.*?)\]/s', $code)) {
            // If we detect PHP 8 attributes, we should be cautious
            // but still return true and let PHP handle it
            return true;
        }
        
        return true;
    }
}
