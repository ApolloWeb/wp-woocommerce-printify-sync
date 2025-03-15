<?php
/**
 * PSR-4 Compliant Autoloader
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @author  ApolloWeb
 * @version 1.0.0
 * @since   2025-03-15
 */

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync;

class Autoloader
{
    /**
     * Namespace prefix for plugin classes
     */
    private const NAMESPACE_PREFIX = 'ApolloWeb\\WPWooCommercePrintifySync\\';

    /**
     * Base directory for plugin classes
     */
    private const BASE_DIR = __DIR__;

    /**
     * Register the autoloader
     *
     * @return void
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * Load the class file
     *
     * @param string $class The fully-qualified class name
     * @return void
     */
    public static function loadClass(string $class): void
    {
        // Check if the class uses our namespace prefix
        $len = strlen(self::NAMESPACE_PREFIX);
        if (strncmp(self::NAMESPACE_PREFIX, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relativeClass = substr($class, $len);

        // Convert the relative class name to a file path
        $file = self::BASE_DIR . DIRECTORY_SEPARATOR . 
                str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        // Load the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}