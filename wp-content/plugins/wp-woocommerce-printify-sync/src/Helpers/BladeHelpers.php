<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * Helper functions for BladeOne templates
 */
class BladeHelpers {
    /**
     * Register all helper functions for BladeOne templates
     * 
     * @return void
     */
    public static function register() {
        // Define the request function
        if (!function_exists('request')) {
            /**
             * Get a value from the request ($_GET or $_POST)
             *
             * @param string|null $key The key to retrieve from the request
             * @param mixed $default Default value to return if key doesn't exist
             * @return mixed
             */
            function request($key = null, $default = null) {
                $request = array_merge($_GET, $_POST);
                
                if (is_null($key)) {
                    return $request;
                }
                
                return isset($request[$key]) ? $request[$key] : $default;
            }
        }
        
        // Add more helper functions as needed
        if (!function_exists('old')) {
            /**
             * Get old input value for form fields after validation
             *
             * @param string $key
             * @param mixed $default
             * @return mixed
             */
            function old($key, $default = null) {
                // You might want to implement session-based old input
                // For now, just return from the request
                return request($key, $default);
            }
        }
    }
}