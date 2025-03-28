<?php
/**
 * Request Helper Class
 *
 * @package WP_WooCommerce_Printify_Sync
 */

namespace WPWPS\Helpers;

/**
 * Request Helper Class
 */
class RequestHelper {

    /**
     * Get a request parameter
     *
     * @param string|null $key     Parameter key.
     * @param mixed       $default Default value.
     * 
     * @return mixed
     */
    public static function get($key = null, $default = null) {
        // If no key is provided, return all parameters.
        if (null === $key) {
            return array_merge($_GET, $_POST);
        }

        // Check if key exists in GET or POST.
        if (isset($_GET[$key])) {
            return self::sanitize_input($_GET[$key]);
        }

        if (isset($_POST[$key])) {
            return self::sanitize_input($_POST[$key]);
        }

        // Return default if key is not found.
        return $default;
    }

    /**
     * Get all request parameters
     *
     * @return array
     */
    public static function all() {
        return array_merge($_GET, $_POST);
    }

    /**
     * Check if a request parameter exists
     *
     * @param string $key Parameter key.
     * 
     * @return bool
     */
    public static function has($key) {
        return isset($_GET[$key]) || isset($_POST[$key]);
    }

    /**
     * Get only specified parameters from request
     *
     * @param array $keys Keys to get.
     * 
     * @return array
     */
    public static function only(array $keys) {
        $result = [];
        $all_params = self::all();

        foreach ($keys as $key) {
            if (isset($all_params[$key])) {
                $result[$key] = self::sanitize_input($all_params[$key]);
            }
        }

        return $result;
    }

    /**
     * Get all parameters except specified ones
     *
     * @param array $keys Keys to exclude.
     * 
     * @return array
     */
    public static function except(array $keys) {
        $result = self::all();

        foreach ($keys as $key) {
            unset($result[$key]);
        }

        return array_map([self::class, 'sanitize_input'], $result);
    }

    /**
     * Get a value from JSON input
     *
     * @return array
     */
    public static function json() {
        $json_str = file_get_contents('php://input');
        $data = json_decode($json_str, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return array_map([self::class, 'sanitize_input'], $data);
        }
        
        return [];
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public static function is_ajax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get request method
     *
     * @return string
     */
    public static function method() {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    /**
     * Check if request method is GET
     *
     * @return bool
     */
    public static function is_get() {
        return self::method() === 'GET';
    }

    /**
     * Check if request method is POST
     *
     * @return bool
     */
    public static function is_post() {
        return self::method() === 'POST';
    }

    /**
     * Sanitize input data recursively
     *
     * @param mixed $input Input data to sanitize.
     * 
     * @return mixed
     */
    private static function sanitize_input($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize_input'], $input);
        }
        
        if (is_string($input)) {
            // Basic sanitization for strings
            return sanitize_text_field($input);
        }
        
        // Return as is for other types (int, bool, etc.)
        return $input;
    }
}