<?php
/**
 * View Helper Class
 *
 * @package WP_WooCommerce_Printify_Sync
 */

namespace WPWPS\Helpers;

use eftec\bladeone\BladeOne;

/**
 * View Helper Class for template rendering
 */
class View {
    /**
     * BladeOne instance
     *
     * @var BladeOne
     */
    private static $blade;

    /**
     * Initialize the BladeOne template engine
     *
     * @param string $views_path Path to the views directory.
     * @param string $cache_path Path to the cache directory.
     *
     * @return BladeOne
     */
    public static function init($views_path, $cache_path) {
        if (!isset(self::$blade)) {
            self::$blade = new BladeOne($views_path, $cache_path, BladeOne::MODE_DEBUG);
            
            // Register custom directives and functions
            self::register_custom_directives();
            self::register_custom_functions();
        }

        return self::$blade;
    }

    /**
     * Register custom directives for BladeOne
     */
    private static function register_custom_directives() {
        // Add custom directives here if needed
    }

    /**
     * Register custom functions for BladeOne
     */
    private static function register_custom_functions() {
        // Register the request helper function
        self::$blade->directiveRT('request', function($expression) {
            $params = self::parseParams($expression);
            $key = isset($params[0]) ? $params[0] : 'null';
            $default = isset($params[1]) ? $params[1] : 'null';
            return "<?php echo RequestHelper::get($key, $default); ?>";
        });
        
        // Register WordPress functions
        self::$blade->directiveRT('get_option', function($expression) {
            $params = self::parseParams($expression);
            $option = isset($params[0]) ? $params[0] : "''";
            $default = isset($params[1]) ? $params[1] : 'false';
            return "<?php echo get_option($option, $default); ?>";
        });
        
        self::$blade->directiveRT('plugin_url', function($expression) {
            $params = self::parseParams($expression);
            $path = isset($params[0]) ? $params[0] : "''";
            return "<?php echo plugins_url($path, WPWPS_PLUGIN_FILE); ?>";
        });
        
        self::$blade->directiveRT('wp_nonce_field', function($expression) {
            $params = self::parseParams($expression);
            $action = isset($params[0]) ? $params[0] : "-1";
            $name = isset($params[1]) ? $params[1] : "'_wpnonce'";
            $referrer = isset($params[2]) ? $params[2] : 'true';
            return "<?php wp_nonce_field($action, $name, $referrer); ?>";
        });
    }

    /**
     * Helper function to parse parameters from directive expressions
     * 
     * @param string $expression The expression to parse
     * @return array The parsed parameters
     */
    private static function parseParams($expression) {
        $expression = trim($expression, '()');
        if (empty($expression)) {
            return [];
        }
        
        // Handle quoted strings and commas within them
        $result = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            if (($char === '"' || $char === "'") && ($i === 0 || $expression[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                } else {
                    $current .= $char;
                }
                continue;
            }
            
            if ($char === ',' && !$inQuote) {
                $result[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        // Add the last parameter
        if (!empty($current)) {
            $result[] = trim($current);
        }
        
        return $result;
    }

    /**
     * Render a view
     *
     * @param string $view  The view name.
     * @param array  $data  The data to pass to the view.
     * 
     * @return string
     */
    public static function render($view, $data = []) {
        if (!isset(self::$blade)) {
            self::init(
                WPWPS_PLUGIN_DIR . 'templates',
                WPWPS_PLUGIN_DIR . 'cache/views'
            );
        }
        
        return self::$blade->run($view, $data);
    }
}