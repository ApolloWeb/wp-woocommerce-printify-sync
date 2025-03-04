<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class EnqueueHelper {
    /**
     * Generates a unique handle based on file path.
     *
     * @param string $file_path
     * @param string $handle_prefix
     * @return string
     */
    public static function generate_handle($file_path, $handle_prefix) {
        // Get only the filename without the directory path
        $filename = pathinfo($file_path, PATHINFO_FILENAME);
        
        // ONLY remove .min if present, don't touch the rest of the filename
        if (strpos($filename, '.min') !== false) {
            $filename = str_replace('.min', '', $filename);
        }
        
        // Return the handle with prefix and filename
        return $handle_prefix . '-' . $filename;
    }

    /**
     * Checks if a minified version of a file exists.
     *
     * @param string $file_path
     * @return string|false
     */
    public static function get_minified_file($file_path) {
        // Don't check if the file is already minified
        if (strpos($file_path, '.min.') !== false) {
            return $file_path;
        }
        
        // Use pathinfo for reliable extension handling
        $pathinfo = pathinfo($file_path);
        $dir = $pathinfo['dirname'];
        $filename = $pathinfo['filename'];
        $ext = $pathinfo['extension'];
        
        // Construct the minified file path
        $minified_path = $dir . '/' . $filename . '.min.' . $ext;
        
        return file_exists($minified_path) ? $minified_path : false;
    }

    /**
     * Converts directory paths to URLs.
     *
     * @param string $file_path
     * @param string $dir_path
     * @param string $dir_url
     * @return string
     */
    public static function convert_path_to_url($file_path, $dir_path, $dir_url) {
        $relative_path = str_replace($dir_path, '', $file_path);
        $relative_path = ltrim($relative_path, '/');
        return trailingslashit($dir_url) . $relative_path;
    }
}