<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class Autoloader
 */
class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'autoload']);
        self::loadThirdPartyLibraries();
    }

    protected static function autoload(string $class): void
    {
        // Handle plugin classes
        $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
        $base_dir = __DIR__ . '/../../';

        if (strpos($class, $prefix) === 0) {
            $relative_class = substr($class, strlen($prefix));
            $file = $base_dir . 'src/' . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        // Handle phpseclib3 namespace
        if (strpos($class, 'phpseclib3\\') === 0) {
            $phpseclibPath = $base_dir . 'lib/phpseclib/';
            $relative_class = substr($class, strlen('phpseclib3\\'));
            $file = $phpseclibPath . 'phpseclib/' . str_replace('\\', '/', $relative_class) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
    
    protected static function loadThirdPartyLibraries(): void
    {
        // Load BladeOne
        if (file_exists(WPWPS_PLUGIN_DIR . 'lib/BladeOne/BladeOne.php')) {
            require_once WPWPS_PLUGIN_DIR . 'lib/BladeOne/BladeOne.php';
        }
        
        // Load GuzzleHttp
        $guzzleFiles = [
            'lib/GuzzleHttp/functions_include.php',
            'lib/GuzzleHttp/Psr7/functions_include.php',
            'lib/GuzzleHttp/Promise/functions_include.php'
        ];
        
        foreach ($guzzleFiles as $file) {
            if (file_exists(WPWPS_PLUGIN_DIR . $file)) {
                require_once WPWPS_PLUGIN_DIR . $file;
            }
        }
        
        // Load phpseclib autoloader
        $phpseclibAutoloader = WPWPS_PLUGIN_DIR . 'lib/phpseclib/autoload.php';
        if (file_exists($phpseclibAutoloader)) {
            require_once $phpseclibAutoloader;
        }
        
        // Check if composer autoload exists and load it
        $composer_autoload = WPWPS_PLUGIN_DIR . 'vendor/autoload.php';
        if (file_exists($composer_autoload)) {
            require_once $composer_autoload;
        }
    }
}
