<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Admin Assets Manager
 * 
 * Handles loading of all admin-specific assets with modern UI libraries
 */
class AdminAssets {
    /**
     * Register and enqueue admin assets
     */
    public function enqueue_assets() {
        // Register Font Awesome 6 Free
        wp_register_style(
            WPWPS_ASSET_PREFIX . 'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // Register Bootstrap 5
        wp_register_style(
            WPWPS_ASSET_PREFIX . 'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        wp_register_script(
            WPWPS_ASSET_PREFIX . 'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.0',
            true
        );

        // Register Chart.js
        wp_register_script(
            WPWPS_ASSET_PREFIX . 'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js',
            [],
            '4.3.0',
            true
        );

        // Register and enqueue the plugin admin CSS
        wp_enqueue_style(
            WPWPS_ASSET_PREFIX . 'admin-styles',
            WPWPS_ASSETS_URL . 'css/' . WPWPS_ASSET_PREFIX . 'admin.css',
            [WPWPS_ASSET_PREFIX . 'bootstrap', WPWPS_ASSET_PREFIX . 'fontawesome'],
            WPWPS_VERSION
        );

        // Register and enqueue the plugin admin JS
        wp_enqueue_script(
            WPWPS_ASSET_PREFIX . 'admin-scripts',
            WPWPS_ASSETS_URL . 'js/' . WPWPS_ASSET_PREFIX . 'admin.js',
            ['jquery', WPWPS_ASSET_PREFIX . 'bootstrap', WPWPS_ASSET_PREFIX . 'chartjs'],
            WPWPS_VERSION,
            true
        );
        
        // Register settings page script
        wp_register_script(
            WPWPS_ASSET_PREFIX . 'settings',
            WPWPS_ASSETS_URL . 'js/' . WPWPS_ASSET_PREFIX . 'settings.js',
            ['jquery', WPWPS_ASSET_PREFIX . 'admin-scripts'],
            WPWPS_VERSION,
            true
        );
        
        // Determine current admin page
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'wpwps-settings') !== false) {
            wp_enqueue_script(WPWPS_ASSET_PREFIX . 'settings');
            
            // Pass localized data to settings script
            wp_localize_script(
                WPWPS_ASSET_PREFIX . 'settings',
                'wpwpsSettings',
                [
                    'selectShop' => __('Select a shop', 'wp-woocommerce-printify-sync'),
                    'confirmDeleteWebhook' => __('Are you sure you want to delete this webhook?', 'wp-woocommerce-printify-sync'),
                    'connectionError' => __('Unable to connect to the server. Please try again.', 'wp-woocommerce-printify-sync'),
                ]
            );
        }
        
        // Pass localized data to script
        wp_localize_script(
            WPWPS_ASSET_PREFIX . 'admin-scripts',
            'wpwps_admin',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps_admin_nonce'),
                'colors' => [
                    'primary' => '#96588a',
                    'secondary' => '#0077b6',
                    'tertiary' => '#00b4d8',
                    'dark' => '#0f1a20',
                    'light' => '#ffffff',
                ],
            ]
        );
        
        // Add Inter font from Google Fonts
        wp_enqueue_style(
            WPWPS_ASSET_PREFIX . 'google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            [],
            WPWPS_VERSION
        );
    }
}
