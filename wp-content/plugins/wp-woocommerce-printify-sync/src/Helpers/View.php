<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use eftec\bladeone\BladeOne;

class View {
    /**
     * @var BladeOne
     */
    private static $blade;

    /**
     * Get the Blade instance
     *
     * @return BladeOne
     */
    public static function getBlade(): BladeOne {
        if (!isset(self::$blade)) {
            $views = WPWPS_PATH . 'templates';
            $cache = WPWPS_PATH . 'templates/cache';
            
            // Create cache directory if it doesn't exist
            if (!file_exists($cache)) {
                mkdir($cache, 0755, true);
            }
            
            // Initialize BladeOne
            self::$blade = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
            
            // Add custom directives
            self::registerCustomDirectives();
            
            // Register helper functions
            self::registerHelperFunctions();
        }
        
        return self::$blade;
    }
    
    /**
     * Register custom directives for Blade templates
     * 
     * @return void
     */
    private static function registerCustomDirectives(): void {
        $blade = self::getBlade();
        
        // WordPress nonce field for forms
        $blade->directiveRT('wpnonce', function($expression) {
            return '<?php wp_nonce_field(' . $expression . '); ?>';
        });
        
        // Add other custom directives as needed
        $blade->directiveRT('settings_fields', function($expression) {
            return '<?php settings_fields(' . $expression . '); ?>';
        });
        
        $blade->directiveRT('do_settings_sections', function($expression) {
            return '<?php do_settings_sections(' . $expression . '); ?>';
        });
        
        $blade->directiveRT('submit_button', function($expression) {
            return '<?php submit_button(' . $expression . '); ?>';
        });
        
        $blade->directiveRT('wp_head', function() {
            return '<?php wp_head(); ?>';
        });
    }
    
    /**
     * Register helper functions for use in templates
     * 
     * @return void
     */
    private static function registerHelperFunctions(): void {
        $blade = self::getBlade();
        
        // Define the request() function that works like Laravel's
        // This is the key fix for the undefined function error
        $blade->setCallbackFunction('request', function($key = null, $default = null) {
            if (is_null($key)) {
                return array_merge($_GET, $_POST);
            }
            
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
            
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }
            
            return $default;
        });
        
        // Make the request object available to all views
        $blade->share('request', new class {
            public function get($key = null, $default = null) {
                if (is_null($key)) {
                    return array_merge($_GET, $_POST);
                }
                
                if (isset($_GET[$key])) {
                    return $_GET[$key];
                }
                
                if (isset($_POST[$key])) {
                    return $_POST[$key];
                }
                
                return $default;
            }
            
            public function has($key) {
                return isset($_GET[$key]) || isset($_POST[$key]);
            }
            
            public function is($page) {
                $current = $_GET['page'] ?? '';
                return $current === $page;
            }
            
            public function __call($method, $args) {
                if ($method === 'is' && count($args) === 1) {
                    return $this->is($args[0]);
                }
                
                return null;
            }
        });
    }
    
    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    public static function render(string $view, array $data = []): string {
        return self::getBlade()->run($view, $data);
    }
    
    /**
     * Clear the BladeOne template cache
     *
     * @return bool True if cache was cleared, false otherwise
     */
    public static function clearCache(): bool {
        $cache_dir = WPWPS_PATH . 'templates/cache';
        if (!is_dir($cache_dir)) {
            return false;
        }
        
        $files = glob($cache_dir . '/*');
        if ($files === false) {
            return false;
        }
        
        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!unlink($file)) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
}