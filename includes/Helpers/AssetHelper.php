<?php
/**
 * Asset Helper
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AssetHelper
 */
class AssetHelper {
    
    /**
     * Check if asset file exists
     *
     * @param string $file_path Path to check
     * @return bool
     */
    public static function asset_exists($file_path) {
        $full_path = PRINTIFY_SYNC_PATH . 'assets/' . $file_path;
        return file_exists($full_path);
    }
    
    /**
     * Get asset URL
     *
     * @param string $file_path Path relative to assets directory
     * @return string
     */
    public static function get_asset_url($file_path) {
        return PRINTIFY_SYNC_URL . 'assets/' . $file_path;
    }
    
    /**
     * Get asset version
     *
     * @param string $file_path Path relative to assets directory
     * @return string
     */
    public static function get_asset_version($file_path) {
        $full_path = PRINTIFY_SYNC_PATH . 'assets/' . $file_path;
        
        if (file_exists($full_path)) {
            // Use file modification time for better cache busting during development
            $version = defined('WP_DEBUG') && WP_DEBUG ? 
                filemtime($full_path) : 
                PRINTIFY_SYNC_VERSION;
                
            return $version;
        }
        
        return PRINTIFY_SYNC_VERSION;
    }
    
    /**
     * Minify CSS string
     *
     * @param string $css CSS content to minify
     * @return string
     */
    public static function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove space after colons
        $css = str_replace(': ', ':', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        return trim($css);
    }
    
    /**
     * Minify JS string
     *
     * @param string $js JS content to minify
     * @return string
     */
    public static function minify_js($js) {
        // For proper JS minification, a dedicated library should be used
        // This is a very basic implementation for development
        
        // Remove comments
        $js = preg_replace('/(\/\/[^\n\r]*)/', '', $js);
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        
        // Remove whitespace
        $js = str_replace(["\r\n", "\r", "\n", "\t"], '', $js);
        
        return trim($js);
    }
    
    /**
     * Print inline CSS
     *
     * @param string $css CSS content
     * @param bool $minify Whether to minify the CSS
     */
    public static function print_inline_css($css, $minify = true) {
        if ($minify) {
            $css = self::minify_css($css);
        }
        
        echo '<style type="text/css">' . $css . '</style>';
    }
    
    /**
     * Print inline JS
     *
     * @param string $js JS content
     * @param bool $minify Whether to minify the JS
     */
    public static function print_inline_js($js, $minify = true) {
        if ($minify) {
            $js = self::minify_js($js);
        }
        
        echo '<script type="text/javascript">' . $js . '</script>';
    }
}