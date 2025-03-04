<?php
/**
<<<<<<< HEAD
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
=======
 * Asset Helper Class * Provides utilities for working with asset paths and URLs * @package WP_WooCommerce_Printify_Sync
 * @since 1.0.0
 */namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;defined('ABSPATH') || exit;/**
 * Asset Helper class.
 */
class AssetHelper {
    /**
     * Plugin root path     * @var string
     */
    private $plugin_path;    /**
     * Plugin root URL     * @var string
     */
    private $plugin_url;    /**
     * Plugin version     * @var string
     */
    private $version;    /**
     * Constructor     * @param string $plugin_path Plugin root path.
     * @param string $plugin_url  Plugin root URL.
     * @param string $version     Plugin version.
     */
    public function __construct($plugin_path, $plugin_url, $version) {
        $this->plugin_path = $plugin_path;
        $this->plugin_url = $plugin_url;
        $this->version = $version;
    }    /**
     * Get the URL for an asset     * @param string $relative_path Path relative to the assets directory.
     * @return string Full URL to the asset
     */
    public function get_asset_url($relative_path) {
        return $this->plugin_url . 'assets/' . $relative_path;
    }    /**
     * Get the file path for an asset     * @param string $relative_path Path relative to the assets directory.
     * @return string Full path to the asset
     */
    public function get_asset_path($relative_path) {
        return $this->plugin_path . 'assets/' . $relative_path;
    }    /**
     * Get current plugin version     * @return string
     */
    public function get_version() {
        return $this->version;
    }    /**
     * Get script suffix based on debug mode     * @return string
     */
    public function get_script_suffix() {
        return (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
    }
    
    /**
     * Check if current page is a plugin admin page     * @param string $hook Current admin page hook.
     * @return bool
     */
    public function is_plugin_admin_page($hook) {
        $plugin_admin_pages = [
            'toplevel_page_wc-printify-sync',
            'printify-sync_page_wc-printify-sync-settings',
            'woocommerce_page_wc-settings'
        ];
        
        if (in_array($hook, $plugin_admin_pages, true)) {
            return true;
        }
        
        // Also check settings tab in WooCommerce if applicable
        if ($hook === 'woocommerce_page_wc-settings' && 
            isset($_GET['tab']) && 
            $_GET['tab'] === 'printify_sync') {
            return true;
        }
        
        return false;
    }    /**
     * Determine if we should load frontend assets     * @return bool
     */
    public function should_load_frontend_assets() {
        $load = false;
        
        if (is_product() || is_cart() || is_checkout() || is_shop()) {
            $load = true;
        }
        
        return apply_filters('wc_printify_sync_load_frontend_assets', $load);
    }
} Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: } Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------
#
#
# Commit Hash 16c804f
#
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
