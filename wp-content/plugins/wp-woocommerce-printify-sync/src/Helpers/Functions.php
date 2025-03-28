<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * Global helper functions for the plugin
 */

if (!function_exists('request')) {
    /**
     * Get a value from the request or return default
     *
     * @param string|null $key The key to get from the request
     * @param mixed $default The default value if key doesn't exist
     * @return mixed The request value or default
     */
    function request($key = null, $default = null) {
        if ($key === null) {
            return $_REQUEST;
        }
        
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        
        return $default;
    }
}

// Make the request function available in global scope
if (!function_exists('\\request')) {
    /**
     * Global request helper function
     *
     * @param string|null $key The key to get from the request
     * @param mixed $default The default value if key doesn't exist
     * @return mixed The request value or default
     */
    function request($key = null, $default = null) {
        return ApolloWeb\WPWooCommercePrintifySync\Helpers\request($key, $default);
    }
}