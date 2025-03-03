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
    private static $version = '1.0.7';
    
    /**
     * Current date and time for script data
     *
     * @var string
     */
    private static $current_datetime = '2025-03-03 11:45:42';
    
    /**
     * Register the class hooks
     *
     * @return void
     */
    public static function register() {
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets']);
        add_action('admin_head', [self::class, 'outputCriticalStyles']);
        add_action('admin_footer', [self::class, 'outputGlobalData']);
    }
    
    /**
     * Output critical CSS styles for immediate layout rendering
     */
    public static function outputCriticalStyles() {
        if (!is_admin()) return;
        
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'printify-sync') === false) return;
        
        echo '<style id="printify-sync-critical-css">
            /* Basic layout styles */
            .dashboard.no-sidebar { display: block; }
            .main-content.full-width { width: 100%; }
            .header-left { display: flex; align-items: center; flex: 1; }
            .logo-container {
                padding: 0 20px; height: 70px; display: flex; align-items: center;
                background: linear-gradient(90deg, #674399 0%, #7f54b3 100%);
            }
            .site-logo { color: white; font-weight: 600; font-size: 20px; }
            .site-logo span { font-weight: 300; }
            
            /* Horizontal menu */
            .main-nav { height: 70px; flex: 1; }
            .main-nav ul {
                display: flex; flex-direction: row !important;
                list-style: none; margin: 0; padding: 0;
                height: 100%;
            }
            .main-nav li { height: 100%; display: flex !important; }
            .main-nav a { 
                display: flex; align-items: center; height: 100%; 
                padding: 0 15px; text-decoration: none; 
                color: #2D3748; font-weight: 500; 
            }
            .main-nav a i { margin-right: 8px; }
            .main-nav li.active a {
                color: #7f54b3; position: relative;
            }
            .main-nav li.active a::after {
                content: ""; position: absolute; bottom: 0;
                left: 0; width: 100%; height: 3px; background-color: #7f54b3;
            }
        </style>';
    }
    
    /**
     * Output global data in admin footer - centralized to avoid duplication
     */
    public static function outputGlobalData() {
        if (!is_admin()) return;
        
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'printify-sync') === false) return;
        
        // Output global data - NOW ONLY DONE HERE to avoid duplication
        echo '<script>
            var printifySyncData = {
                currentDateTime: "' . self::$current_datetime . '",
                currentUser: "ApolloWeb",
                debug: true
            };
            
            console.log("Global Data Loaded", printifySyncData);
        </script>';
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
            // First deregister any potentially conflicting scripts
            wp_deregister_script('printify-sync-admin-dashboard');
            
            // Font Awesome
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
            
            // Google Fonts
            wp_enqueue_style('google-fonts-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', [], null);
            
            // Main admin CSS
            wp_enqueue_style(
                'printify-sync-admin-dashboard', 
                plugin_dir_url(dirname(__DIR__)) . 'assets/css/admin-dashboard.css',
                [],
                self::$version
            );
            
            // Enqueue jQuery first
            wp_enqueue_script('jquery');
            
            // Enqueue Chart.js before our admin script
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
                ['jquery'],
                '3.7.0',
                true
            );
            
            // Enqueue ProgressBar.js before our admin script
            wp_enqueue_script(
                'progressbar-js',
                'https://cdn.jsdelivr.net/npm/progressbar.js@1.1.0/dist/progressbar.min.js',
                ['jquery'],
                '1.1.0',
                true
            );
            
            // Finally enqueue our admin script
            wp_enqueue_script(
                'printify-sync-admin-dashboard',
                plugin_dir_url(dirname(__DIR__)) . 'assets/js/admin-dashboard.js',
                ['jquery', 'chart-js', 'progressbar-js'],
                self::$version,
                true
            );
            
            // Add additional page-specific resources
            if (strpos($hook, 'printify-sync-settings') !== false) {
                wp_enqueue_style(
                    'printify-sync-settings',
                    plugin_dir_url(dirname(__DIR__)) . 'assets/css/settings.css',
                    [],
                    self::$version
                );
                
                wp_enqueue_script(
                    'printify-sync-settings',
                    plugin_dir_url(dirname(__DIR__)) . 'assets/js/settings.js',
                    ['jquery'],
                    self::$version,
                    true
                );
            }
        }
    }
    
    /**
     * Enqueue assets for the frontend
     *
     * @return void
     */
    public static function enqueueFrontendAssets() {
        // Frontend assets would go here
    }
}