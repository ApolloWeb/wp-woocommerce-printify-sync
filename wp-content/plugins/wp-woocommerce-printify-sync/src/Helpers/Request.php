<?php
/**
 * Request Helper Class
 *
 * Provides utility functions for working with HTTP requests in the WP WooCommerce Printify Sync plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * Request Helper Class
 */
class Request
{
    /**
     * Get a value from the request
     *
     * @param string $key     The key to get from the request
     * @param mixed  $default The default value to return if the key is not found
     * 
     * @return mixed The value from the request or the default value
     */
    public static function get($key = null, $default = null)
    {
        if (null === $key) {
            return $_REQUEST;
        }
        
        return isset($_REQUEST[$key]) ? self::sanitizeInput($_REQUEST[$key]) : $default;
    }
    
    /**
     * Check if a key exists in the request
     *
     * @param string $key The key to check for
     * 
     * @return bool Whether the key exists
     */
    public static function has($key)
    {
        return isset($_REQUEST[$key]);
    }
    
    /**
     * Get all request data
     * 
     * @return array The request data
     */
    public static function all()
    {
        return $_REQUEST;
    }
    
    /**
     * Sanitize input data
     *
     * @param mixed $input The input to sanitize
     * 
     * @return mixed The sanitized input
     */
    private static function sanitizeInput($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitizeInput($value);
            }
            return $input;
        }
        
        return is_string($input) ? sanitize_text_field($input) : $input;
    }
}

/**
 * Helper function to access the Request class methods
 *
 * @param string|null $key     The key to get from the request
 * @param mixed|null  $default The default value to return if the key is not found
 * 
 * @return mixed The Request instance or the value from the request
 */
function request($key = null, $default = null)
{
    return Request::get($key, $default);
}