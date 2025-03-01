<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Asset loader for WP WooCommerce Printify Sync
 *
 * Handles loading JavaScript and CSS files
 */
class AssetLoader {
    /**
     * Plugin version for cache busting
     */
    const VERSION = '1.0.0';
    
    /**
     * Register hooks
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }
    
    /**
     * Get plugin url
     *
     * @return string Plugin URL
     */
    private function getPluginUrl() {
        return plugin_dir_url(dirname(__FILE__));
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueueAdminScripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'printify-sync') === false) {
            return;
        }
        
        // Enqueue common CSS
        wp_enqueue_style(
            'wpwps-admin-styles',
            $this->getPluginUrl() . 'admin/assets/css/admin-styles.css',
            [],
            self::VERSION
        );
        
        // Enqueue common JS
        wp_enqueue_script(
            'wpwps-admin',
            $this->getPluginUrl() . 'admin/assets/js/admin.js',
            ['jquery'],
            self::VERSION,
            true
        );
        
        // Page-specific scripts
        if (strpos($hook, 'shops') !== false) {
            wp_enqueue_script(
                'wpwps-shops',
                $this->getPluginUrl() . 'admin/assets/js/shops.js',
                ['jquery', 'wpwps-admin'],
                self::VERSION,
                true
            );
        }
        
        if (strpos($hook, 'products') !== false) {
            wp_enqueue_script(
                'wpwps-products',
                $this->getPluginUrl() . 'admin/assets/js/products.js',
                ['jquery', 'wpwps-admin'],
                self::VERSION,
                true
            );
        }
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-nonce'),
        ]);
    }
}