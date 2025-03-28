<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * Helper class for handling request data
 */
class RequestHelper {
    /**
     * Get a value from the request
     * 
     * @param string $key The request key to retrieve
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public static function get($key, $default = null) {
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        return $default;
    }
    
    /**
     * Check if a key exists in the request
     * 
     * @param string $key The key to check
     * @return bool
     */
    public static function has($key) {
        return isset($_REQUEST[$key]) || isset($_GET[$key]) || isset($_POST[$key]);
    }
    
    /**
     * Get all request data
     * 
     * @return array
     */
    public static function all() {
        return array_merge($_GET, $_POST, $_REQUEST);
    }
}