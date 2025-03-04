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
        
        // Ensure only file extensions are removed
        $filename = str_replace(['.css', '.js', '.min'], '', $filename);
        
        // Return the handle with prefix intact
        return $handle_prefix . '-' . $filename;
    }

    /**
     * Checks if a minified version of a file exists.
     *
     * @param string $file_path
     * @return string|false
     */
    public static function get_minified_file($file_path) {
        $minified_path = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $file_path);
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
        $relative_path = str_replace($dir_path . '/', '', $file_path);
        return $dir_url . '/' . $relative_path;
    }
}

