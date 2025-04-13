<?php
/**
 * Asset Manager
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Manages assets for the plugin
 */
class AssetManager {
    /**
     * Enqueue plugin assets
     *
     * @return void
     */
    public function enqueueAssets(): void {
        // Only load assets on plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'wpwps') === false) {
            return;
        }

        $this->enqueueStyles($screen);
        $this->enqueueScripts($screen);
        $this->localizeScripts($screen);
    }
    
    /**
     * Enqueue styles
     *
     * @param \WP_Screen $screen Current screen
     * @return void
     */
    private function enqueueStyles(\WP_Screen $screen): void {
        // Core styles
        wp_enqueue_style(
            'wpwps-bootstrap', 
            WPWPS_URL . 'assets/core/css/bootstrap.min.css',
            [],
            WPWPS_VERSION
        );
        
        wp_enqueue_style(
            'wpwps-fontawesome',
            WPWPS_URL . 'assets/core/css/fontawesome.min.css',
            [],
            WPWPS_VERSION
        );

        // UI Components CSS
        wp_enqueue_style(
            'wpwps-ui-components',
            WPWPS_URL . 'assets/css/wpwps-ui-components.css',
            [],
            WPWPS_VERSION
        );
        
        wp_enqueue_style(
            'wpwps-enhanced-ui',
            WPWPS_URL . 'assets/css/wpwps-enhanced-ui.css',
            ['wpwps-ui-components'],
            WPWPS_VERSION
        );
        
        // Animations CSS
        wp_enqueue_style(
            'wpwps-animations',
            WPWPS_URL . 'assets/css/wpwps-animations.css',
            [],
            WPWPS_VERSION
        );
        
        // Premium UI styles
        wp_enqueue_style(
            'wpwps-premium-ui',
            WPWPS_URL . 'assets/css/wpwps-premium-ui.css',
            [],
            WPWPS_VERSION
        );
        
        // Loader styles
        wp_enqueue_style(
            'wpwps-loader',
            WPWPS_URL . 'assets/css/wpwps-loader.css',
            [],
            WPWPS_VERSION
        );

        // Page specific styles
        $this->enqueuePageSpecificStyles($screen);
    }
    
    /**
     * Enqueue scripts
     *
     * @param \WP_Screen $screen Current screen
     * @return void
     */
    private function enqueueScripts(\WP_Screen $screen): void {
        // Core scripts
        wp_enqueue_script(
            'wpwps-bootstrap',
            WPWPS_URL . 'assets/core/js/bootstrap.bundle.min.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        wp_enqueue_script(
            'wpwps-chartjs',
            WPWPS_URL . 'assets/core/js/chart.min.js',
            [],
            WPWPS_VERSION,
            true
        );
        
        // UI Components JS
        wp_enqueue_script(
            'wpwps-ui-components',
            WPWPS_URL . 'assets/js/wpwps-ui-components.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        wp_enqueue_script(
            'wpwps-enhanced-ui',
            WPWPS_URL . 'assets/js/wpwps-enhanced-ui.js',
            ['jquery', 'wpwps-ui-components'],
            WPWPS_VERSION,
            true
        );
        
        // Premium visual effects
        wp_enqueue_script(
            'wpwps-premium-effects',
            WPWPS_URL . 'assets/js/wpwps-premium-effects.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        // Splash screen script
        wp_enqueue_script(
            'wpwps-splash',
            WPWPS_URL . 'assets/js/wpwps-splash.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        // API service script
        wp_enqueue_script(
            'wpwps-api-service',
            WPWPS_URL . 'assets/js/wpwps-api-service.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        // Page specific scripts
        $this->enqueuePageSpecificScripts($screen);
    }
    
    /**
     * Localize scripts
     *
     * @param \WP_Screen $screen Current screen
     * @return void
     */
    private function localizeScripts(\WP_Screen $screen): void {
        // Pass data to JS
        wp_localize_script('wpwps-ui-components', 'wpwpsUIComponents', [
            'quickActionNonce' => wp_create_nonce('wpwps-quick-action-nonce'),
            'pluginUrl' => WPWPS_URL
        ]);
        
        // Pass API configuration to JavaScript
        wp_localize_script('wpwps-api-service', 'wpwpsApi', [
            'apiUrl' => rest_url('wpwps/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'printifyEndpoint' => get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1'),
            'wooEndpoint' => rest_url('wc/v3'),
            'isDebug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
        
        // Page specific localizations
        $this->localizePageSpecificScripts($screen);
    }
    
    /**
     * Enqueue page specific styles
     *
     * @param \WP_Screen $screen Current screen
     * @return void
     */
    private function enqueuePageSpecificStyles(\WP_Screen $screen): void {
        if ($screen->id === 'toplevel_page_wpwps-dashboard') {
            wp_enqueue_style(
                'wpwps-dashboard',
                WPWPS_URL . 'assets/css/wpwps-dashboard.css',
                ['wpwps-bootstrap'],
                WPWPS_VERSION
            );
        } else if ($screen->id === 'printify-sync_page_wpwps-products') {
            wp_enqueue_style(
                'wpwps-products',
                WPWPS_URL . 'assets/css/wpwps-products.css',
                ['wpwps-bootstrap'],
                WPWPS_VERSION
            );
        } else if ($screen->id === 'printify-sync_page_wpwps-settings') {
            wp_enqueue_style(
                'wpwps-settings',
                WPWPS_URL . 'assets/css/wpwps-settings.css',
                ['wpwps-bootstrap'],
                WPWPS_VERSION
            );
        }
    }
    
    /**
     * Enqueue page specific scripts
     *
     * @param \WP_Screen $screen Current screen
     * @return void
     */
    private function enqueuePageSpecificScripts(\WP_Screen $screen): void {
        if ($screen->id === 'toplevel_page_wpwps-dashboard') {
            wp_enqueue_script(
                'wpwps-dashboard',
                WPWPS_URL . 'assets/js/wpwps-dashboard.js',
                ['jquery', 'wpwps-chartjs', 'wpwps-bootstrap'],
                WPWPS_VERSION,
                true
            );
        } else if ($screen->id === 'printify-sync_page_wpwps-products') {
            wp_enqueue_script(
                'wpwps-products',
                WPWPS_URL . 'assets/js/wpwps-products.js',
                ['jquery', 'wpwps-bootstrap'],
                WPWPS_VERSION,
                true
            );
        } else if ($screen->id === 'printify-sync_page_wpwps-settings') {
            wp_enqueue_script(
                'wpwps-settings',
                WPWPS_URL . 'assets/js/wpwps-settings.js',
                ['jquery', 'wpwps-bootstrap'],
                WPWPS_VERSION,
                true
            );
        }
    }
    
    /**
     * Localize page specific scripts
     *
     * @param \WP_Screen $screen Current screen
     * @return void
     */
    private function localizePageSpecificScripts(\WP_Screen $screen): void {
        if ($screen->id === 'printify-sync_page_wpwps-settings') {
            wp_localize_script('wpwps-settings', 'wpwpsSettings', [
                'nonce' => wp_create_nonce('wpwps-settings-nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php')
            ]);
        }
    }
}
