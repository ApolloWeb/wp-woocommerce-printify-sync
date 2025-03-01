<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Assets Management Class
 *
 * @package WP WooCommerce Printify Sync
 * @version 1.0.0
 * @date 2025-03-01 09:19:06
 * @user ApolloWeb
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Class SyncAssets
 * 
 * Handles all assets (scripts and styles) for the plugin
 */
class SyncAssets {
    /**
     * Initialize the class
     */
    public static function init() {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (!self::is_plugin_page($hook)) {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'wpwps-admin-styles',
            WPWPS_URL . 'admin/assets/css/admin.css',
            [],
            WPWPS_VERSION
        );
        
        // Enqueue main admin script
        wp_enqueue_script(
            'wpwps-admin-scripts',
            WPWPS_URL . 'admin/assets/js/admin.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        // Get current page
        $current_page = self::get_current_plugin_page();
        
        // Enqueue page-specific scripts
        self::enqueue_page_scripts($current_page);
        
        // Localize script with common variables
        wp_localize_script('wpwps-admin-scripts', 'wpwps_ajax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_nonce'),
        ]);
        
        // Localize script with internationalization strings
        wp_localize_script('wpwps-admin-scripts', 'wpwps_i18n', [
            'confirm_import' => __('Are you sure you want to import products from Printify? This may take several minutes.', 'wp-woocommerce-printify-sync'),
            'confirm_clear' => __('Are you sure you want to clear all imported Printify products? This action cannot be undone.', 'wp-woocommerce-printify-sync'),
            'importing' => __('Importing...', 'wp-woocommerce-printify-sync'),
            'clearing' => __('Clearing...', 'wp-woocommerce-printify-sync'),
            'loading' => __('Loading...', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Check if current page is a plugin page
     *
     * @param string $hook Current admin page hook
     * @return bool
     */
    private static function is_plugin_page($hook) {
        if ($hook === 'toplevel_page_wp-woocommerce-printify-sync') {
            return true;
        }
        
        $plugin_pages = [
            'wp-woocommerce-printify-sync_page_wp-woocommerce-printify-sync-shops',
            'wp-woocommerce-printify-sync_page_wp-woocommerce-printify-sync-products'
        ];
        
        return in_array($hook, $plugin_pages);
    }
    
    /**
     * Get current plugin page
     *
     * @return string
     */
    private static function get_current_plugin_page() {
        $current_page = '';
        
        if (isset($_GET['page'])) {
            $page = sanitize_text_field($_GET['page']);
            if ($page === 'wp-woocommerce-printify-sync') {
                $current_page = 'settings';
            } elseif (strpos($page, 'wp-woocommerce-printify-sync-') !== false) {
                $current_page = str_replace('wp-woocommerce-printify-sync-', '', $page);
            }
        }
        
        return $current_page;
    }
    
    /**
     * Enqueue page-specific scripts
     *
     * @param string $page Current plugin page
     */
    private static function enqueue_page_scripts($page) {
        // Base scripts directory
        $scripts_dir = WPWPS_DIR . 'admin/assets/js/';
        $scripts_url = WPWPS_URL . 'admin/assets/js/';
        
        // Always check if the file exists before enqueueing
        if ($page) {
            // Try specific page script (e.g. products.js for products page)
            $page_script = $scripts_dir . $page . '.js';
            if (file_exists($page_script)) {
                wp_enqueue_script(
                    'wpwps-' . $page,
                    $scripts_url . $page . '.js',
                    ['jquery', 'wpwps-admin-scripts'],
                    WPWPS_VERSION,
                    true
                );
            }
            
            // Check for additional page-specific scripts
            self::enqueue_scripts_from_directory($scripts_dir . $page . '/', $scripts_url . $page . '/', $page);
        }
        
        // Check for feature-specific scripts that might apply to multiple pages
        $features = self::get_features_for_page($page);
        foreach ($features as $feature) {
            $feature_script = $scripts_dir . 'features/' . $feature . '.js';
            if (file_exists($feature_script)) {
                wp_enqueue_script(
                    'wpwps-feature-' . $feature,
                    $scripts_url . 'features/' . $feature . '.js',
                    ['jquery', 'wpwps-admin-scripts'],
                    WPWPS_VERSION,
                    true
                );
            }
        }
    }
    
    /**
     * Enqueue all JavaScript files from a directory
     *
     * @param string $dir_path Directory path
     * @param string $dir_url Directory URL
     * @param string $prefix Script handle prefix
     */
    private static function enqueue_scripts_from_directory($dir_path, $dir_url, $prefix = '') {
        // Check if directory exists
        if (!is_dir($dir_path)) {
            return;
        }
        
        // Get all JavaScript files
        $files = glob($dir_path . '*.js');
        if (!is_array($files)) {
            return;
        }
        
        // Enqueue each file
        foreach ($files as $file) {
            $filename = basename($file);
            $handle = 'wpwps-' . ($prefix ? $prefix . '-' : '') . str_replace('.js', '', $filename);
            
            wp_enqueue_script(
                $handle,
                $dir_url . $filename,
                ['jquery', 'wpwps-admin-scripts'],
                WPWPS_VERSION,
                true
            );
        }
    }
    
    /**
     * Get features that should be loaded for a specific page
     *
     * @param string $page Current plugin page
     * @return array
     */
    private static function get_features_for_page($page) {
        // Map pages to features
        $page_features = [
            'settings' => [],
            'shops'    => ['api-connection'],
            'products' => ['products-import']
        ];
        
        // Return features for current page or empty array if page not found
        return isset($page_features[$page]) ? $page_features[$page] : [];
    }
}

// Initialize the class
SyncAssets::init();