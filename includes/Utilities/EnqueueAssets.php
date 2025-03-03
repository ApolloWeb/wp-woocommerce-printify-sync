<?php
/**
 * Asset Enqueuing Utility
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utilities
 * @version 1.2.4
 * @date 2025-03-03 13:53:06
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\AssetHelper;

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
    private static $version = '1.2.4';
    
    /**
     * Register the class hooks
     *
     * @return void
     */
    public static function register() {
        // Debug path information
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Plugin Dir Path: ' . plugin_dir_path(dirname(__DIR__)));
            error_log('Plugin URL: ' . plugin_dir_url(dirname(__DIR__)));
        }
        
        // Register the asset helper
        AssetHelper::init(self::$version, '2025-03-03 13:53:06');
        
        // Register admin scripts and styles
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
        
        // Output the global data for JavaScript
        add_action('admin_footer', [self::class, 'outputGlobalData']);
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook The current admin page
     * @return void
     */
    public static function enqueueAdminAssets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'printify-sync') === false) {
            return;
        }
        
        // Debug info
        self::debugLog("Loading assets for hook: {$hook}");
        
        // 1. External dependencies
        // -----------------------
        
        // Load Font Awesome
        wp_enqueue_style(
            'printify-sync-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            [],
            '5.15.4'
        );
        
        // Load Google Fonts
        wp_enqueue_style(
            'printify-sync-google-fonts',
            'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
            [],
            null
        );
        
        // 2. Plugin CSS files - explicitly check for existence
        // -----------------
        $plugin_url = plugin_dir_url(dirname(__DIR__));
        $plugin_dir = plugin_dir_path(dirname(__DIR__));
        $css_version = self::$version;
        
        // Main dashboard styles - first priority
        $dashboard_css_path = 'assets/css/admin-dashboard.css';
        if (file_exists($plugin_dir . $dashboard_css_path)) {
            wp_enqueue_style(
                'printify-sync-admin-dashboard',
                $plugin_url . $dashboard_css_path,
                [],
                $css_version
            );
            self::debugLog("Loaded: {$dashboard_css_path}");
        } else {
            self::debugLog("File not found: {$plugin_dir}{$dashboard_css_path}", 'warning');
        }
        
        // Table styles for dashboard
        $tables_css_path = 'assets/css/admin-dashboard-tables.css';
        if (file_exists($plugin_dir . $tables_css_path)) {
            wp_enqueue_style(
                'printify-sync-admin-tables',
                $plugin_url . $tables_css_path,
                ['printify-sync-admin-dashboard'],
                $css_version
            );
            self::debugLog("Loaded: {$tables_css_path}");
        } else {
            self::debugLog("File not found: {$plugin_dir}{$tables_css_path}", 'warning');
        }
        
        // Admin widgets styles
        $widgets_css_path = 'assets/css/admin-widgets.css';
        if (file_exists($plugin_dir . $widgets_css_path)) {
            wp_enqueue_style(
                'printify-sync-admin-widgets',
                $plugin_url . $widgets_css_path,
                ['printify-sync-admin-dashboard'],
                $css_version
            );
            self::debugLog("Loaded: {$widgets_css_path}");
        }
        
        // Page-specific styles based on current hook
        if (strpos($hook, 'settings') !== false) {
            $settings_css_path = 'assets/css/settings.css';
            if (file_exists($plugin_dir . $settings_css_path)) {
                wp_enqueue_style(
                    'printify-sync-settings',
                    $plugin_url . $settings_css_path,
                    ['printify-sync-admin-dashboard'],
                    $css_version
                );
                self::debugLog("Loaded: {$settings_css_path}");
            }
        }
        
        // 3. JavaScript dependencies
        // ------------------------
        
        // jQuery is already included by WordPress
        wp_enqueue_script('jquery');
        
        // Chart.js - ensure we deregister any existing versions first
        wp_deregister_script('chart-js'); 
        wp_deregister_script('chartjs');
        
        wp_enqueue_script(
            'printify-sync-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
            ['jquery'],
            '3.7.0',
            true
        );
        
        // ProgressBar.js
        wp_enqueue_script(
            'printify-sync-progressbar',
            'https://cdn.jsdelivr.net/npm/progressbar.js@1.1.0/dist/progressbar.min.js',
            ['jquery'],
            '1.1.0',
            true
        );
        
        // 4. Plugin JavaScript files
        // ------------------------
        $js_version = self::$version;
        
        // Main dashboard script - only on dashboard page
        if (strpos($hook, 'dashboard') !== false) {
            $dashboard_js_path = 'assets/js/admin-dashboard.js';
            if (file_exists($plugin_dir . $dashboard_js_path)) {
                wp_enqueue_script(
                    'printify-sync-admin-dashboard',
                    $plugin_url . $dashboard_js_path,
                    ['jquery', 'printify-sync-chartjs', 'printify-sync-progressbar'],
                    $js_version,
                    true
                );
                self::debugLog("Loaded: {$dashboard_js_path}");
            } else {
                self::debugLog("File not found: {$plugin_dir}{$dashboard_js_path}", 'warning');
            }
            
            // Check for charts.js in admin subdirectory
            $charts_js_path = 'assets/js/admin/charts.js';
            if (file_exists($plugin_dir . $charts_js_path)) {
                wp_enqueue_script(
                    'printify-sync-charts',
                    $plugin_url . $charts_js_path,
                    ['jquery', 'printify-sync-chartjs', 'printify-sync-progressbar', 'printify-sync-admin-dashboard'],
                    $js_version,
                    true
                );
                self::debugLog("Loaded: {$charts_js_path}");
            }
        }
        
        // Admin widgets script - load on all admin pages
        $admin_widgets_path = 'assets/js/admin-widgets.js';
        if (file_exists($plugin_dir . $admin_widgets_path)) {
            wp_enqueue_script(
                'printify-sync-admin-widgets',
                $plugin_url . $admin_widgets_path,
                ['jquery'],
                $js_version,
                true
            );
            self::debugLog("Loaded: {$admin_widgets_path}");
        }
        
        // Settings script - only on settings page
        if (strpos($hook, 'settings') !== false) {
            $settings_js_path = 'assets/js/settings.js';
            if (file_exists($plugin_dir . $settings_js_path)) {
                wp_enqueue_script(
                    'printify-sync-settings',
                    $plugin_url . $settings_js_path,
                    ['jquery'],
                    $js_version,
                    true
                );
                self::debugLog("Loaded: {$settings_js_path}");
            }
        }
    }
    
    /**
     * Output global data for JavaScript
     *
     * @return void
     */
    public static function outputGlobalData() {
        if (!is_admin()) return;
        
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'printify-sync') === false) return;
        
        // Current user and time
        $current_datetime = '2025-03-03 13:53:06';
        $user_login = 'ApolloWeb';
        
        echo "<script>
            var printifySyncData = {
                currentDateTime: '$current_datetime',
                currentUser: '$user_login',
                ajaxUrl: '" . admin_url('admin-ajax.php') . "',
                nonce: '" . wp_create_nonce('printify_sync_nonce') . "',
                debug: " . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . ",
                version: '" . self::$version . "'
            };
            console.log('Printify Sync: Global data initialized', printifySyncData);
        </script>";
    }
    
    /**
     * Output debug log
     *
     * @param string $message Debug message
     * @param string $level Debug level (info, warning, error)
     * @return void
     */
    private static function debugLog($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $prefix = 'PrintifySync';
            if ($level === 'warning') {
                $prefix = 'PrintifySync WARNING';
            } elseif ($level === 'error') {
                $prefix = 'PrintifySync ERROR';
            }
            error_log("$prefix: $message");
        }
    }
}