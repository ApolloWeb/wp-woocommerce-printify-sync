<?php
/**
 * Asset Enqueuing Utility
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utilities
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

/**
 * Class EnqueueAssets
 * Handles all asset enqueuing for the plugin
 */
class EnqueueAssets {
    /**
     * Plugin version for asset versioning
     *
     * @var string
     */
    private static $version = '1.0.4';
    
    /**
     * Current date and time for script data
     *
     * @var string
     */
    private static $current_datetime = '2025-03-03 11:34:10';
    
    /**
     * Register the class hooks
     *
     * @return void
     */
    public static function register() {
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets']);
    }
    
    /**
     * Enqueue assets for the admin area
     *
     * @param string $hook The current admin page
     * @return void
     */
    public static function enqueueAdminAssets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'printify-sync') !== false) {
            // Common styles must be loaded before specific page styles
            self::enqueueCommonStyles();
            
            // Admin dashboard styles 
            wp_enqueue_style('printify-sync-admin-css', plugin_dir_url(dirname(__DIR__)) . 'assets/css/admin-dashboard.css', [], self::$version);
            
            // Common scripts must be loaded before page scripts
            self::enqueueCommonScripts();
            
            // Admin dashboard scripts with explicit dependency order
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js', [], '3.7.0', true);
            wp_enqueue_script('progressbar-js', 'https://cdn.jsdelivr.net/npm/progressbar.js@1.1.0/dist/progressbar.min.js', [], '1.1.0', true);
            wp_enqueue_script('printify-sync-admin-js', plugin_dir_url(dirname(__DIR__)) . 'assets/js/admin-dashboard.js', ['jquery', 'chart-js', 'progressbar-js'], self::$version, true);
            
            // Settings page specific scripts
            if ($hook === 'printify-sync_page_printify-sync-settings') {
                wp_enqueue_style('printify-sync-settings-css', plugin_dir_url(dirname(__DIR__)) . 'assets/css/settings.css', [], self::$version);
                wp_enqueue_script('printify-sync-settings-js', plugin_dir_url(dirname(__DIR__)) . 'assets/js/settings.js', ['jquery'], self::$version, true);
                
                // Add data to the script
                wp_localize_script('printify-sync-settings-js', 'printifySyncData', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('printify_sync_settings'),
                    'currentDateTime' => self::$current_datetime,
                    'currentUser' => self::getCurrentUser()
                ]);
            }
            
            // Log viewer specific styles
            if ($hook === 'printify-sync_page_printify-sync-logs') {
                wp_enqueue_style('printify-sync-logs-css', plugin_dir_url(dirname(__DIR__)) . 'assets/css/logs.css', [], self::$version);
            }
            
            // Add global data
            wp_localize_script('jquery', 'printifySyncGlobal', [
                'currentDateTime' => self::$current_datetime,
                'currentUser' => self::getCurrentUser(),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'pluginUrl' => plugin_dir_url(dirname(__DIR__))
            ]);
        }
    }
    
    /**
     * Enqueue assets for the frontend
     *
     * @return void
     */
    public static function enqueueFrontendAssets() {
        // Frontend assets would be enqueued here if needed
        // Currently the plugin is admin-only
    }
    
    /**
     * Enqueue common styles used across the plugin
     *
     * @return void
     */
    private static function enqueueCommonStyles() {
        // Google Fonts - Poppins
        wp_enqueue_style('google-fonts-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', [], null);
        
        // Font Awesome - Using Free version
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
    }
    
    /**
     * Enqueue common scripts used across the plugin
     *
     * @return void
     */
    private static function enqueueCommonScripts() {
        // jQuery - ensure it's loaded
        wp_enqueue_script('jquery');
    }
    
    /**
     * Get the current WordPress user's display name
     *
     * @return string
     */
    private static function getCurrentUser() {
        $current_user = wp_get_current_user();
        return $current_user->exists() ? $current_user->display_name : 'ApolloWeb';
    }
}