<?php
/**
 * Asset Helper Utility
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.2.1
 * @date 2025-03-03 13:26:26
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * Class AssetHelper
 * Helper functions for asset loading
 */
class AssetHelper {
    /**
     * Plugin version
     *
     * @var string
     */
    private static $version = '1.2.1';
    
    /**
     * Current datetime
     *
     * @var string
     */
    private static $datetime = '';
    
    /**
     * Plugin directory path
     *
     * @var string
     */
    private static $plugin_dir = '';
    
    /**
     * Plugin directory URL
     *
     * @var string
     */
    private static $plugin_url = '';
    
    /**
     * Initialize the helper
     *
     * @param string $version Plugin version
     * @param string $datetime Current datetime
     */
    public static function init($version, $datetime) {
        self::$version = $version;
        self::$datetime = $datetime;
        self::$plugin_dir = plugin_dir_path(dirname(__DIR__));
        self::$plugin_url = plugin_dir_url(dirname(__DIR__));
        
        // Debug info
        self::logDebug('Asset Helper initialized');
        self::logDebug('Plugin directory: ' . self::$plugin_dir);
        self::logDebug('Plugin URL: ' . self::$plugin_url);
    }
    
    /**
     * Get plugin URL for assets
     * 
     * @param string $path Relative path to asset
     * @return string Complete URL
     */
    public static function getAssetUrl($path) {
        return self::$plugin_url . $path;
    }
    
    /**
     * Check if asset file exists
     * 
     * @param string $path Relative path to asset
     * @return bool True if file exists
     */
    public static function assetExists($path) {
        return file_exists(self::$plugin_dir . $path);
    }
    
    /**
     * Log debug information
     * 
     * @param string $message Debug message
     * @param string $level Debug level
     */
    public static function logDebug($message, $level = 'info') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $prefix = 'Printify Sync';
        if ($level === 'warning') {
            $prefix = 'Printify Sync Warning';
        } elseif ($level === 'error') {
            $prefix = 'Printify Sync Error';
        }
        
        error_log("$prefix: $message");
    }
}