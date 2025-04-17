<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Class Assets
 * 
 * Handles loading of all plugin assets with proper prefixing
 */
class Assets {
    /**
     * Register and enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // CSS
        wp_enqueue_style(
            WPWPS_ASSET_PREFIX . 'frontend',
            WPWPS_ASSETS_URL . 'css/' . WPWPS_ASSET_PREFIX . 'frontend.css',
            [],
            WPWPS_VERSION
        );
        
        // JS
        wp_enqueue_script(
            WPWPS_ASSET_PREFIX . 'frontend',
            WPWPS_ASSETS_URL . 'js/' . WPWPS_ASSET_PREFIX . 'frontend.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
    }
    
    /**
     * Register and enqueue admin assets
     */
    public function enqueue_admin_assets() {
        // CSS
        wp_enqueue_style(
            WPWPS_ASSET_PREFIX . 'admin',
            WPWPS_ASSETS_URL . 'css/' . WPWPS_ASSET_PREFIX . 'admin.css',
            [],
            WPWPS_VERSION
        );
        
        // JS
        wp_enqueue_script(
            WPWPS_ASSET_PREFIX . 'admin',
            WPWPS_ASSETS_URL . 'js/' . WPWPS_ASSET_PREFIX . 'admin.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
    }
}
