<?php
/**
 * Asset Helper Class
 *
 * Provides utilities for working with asset paths and URLs
 *
 * @package WP_WooCommerce_Printify_Sync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

defined('ABSPATH') || exit;

/**
 * Asset Helper class.
 */
class AssetHelper {
    /**
     * Plugin root path
     *
     * @var string
     */
    private $plugin_path;

    /**
     * Plugin root URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Constructor
     *
     * @param string $plugin_path Plugin root path.
     * @param string $plugin_url  Plugin root URL.
     * @param string $version     Plugin version.
     */
    public function __construct($plugin_path, $plugin_url, $version) {
        $this->plugin_path = $plugin_path;
        $this->plugin_url = $plugin_url;
        $this->version = $version;
    }

    /**
     * Get the URL for an asset
     *
     * @param string $relative_path Path relative to the assets directory.
     * @return string Full URL to the asset
     */
    public function get_asset_url($relative_path) {
        return $this->plugin_url . 'assets/' . $relative_path;
    }

    /**
     * Get the file path for an asset
     *
     * @param string $relative_path Path relative to the assets directory.
     * @return string Full path to the asset
     */
    public function get_asset_path($relative_path) {
        return $this->plugin_path . 'assets/' . $relative_path;
    }

    /**
     * Get current plugin version
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get script suffix based on debug mode
     *
     * @return string
     */
    public function get_script_suffix() {
        return (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
    }
    
    /**
     * Check if current page is a plugin admin page
     *
     * @param string $hook Current admin page hook.
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
    }

    /**
     * Determine if we should load frontend assets
     *
     * @return bool
     */
    public function should_load_frontend_assets() {
        $load = false;
        
        if (is_product() || is_cart() || is_checkout() || is_shop()) {
            $load = true;
        }
        
        return apply_filters('wc_printify_sync_load_frontend_assets', $load);
    }
}