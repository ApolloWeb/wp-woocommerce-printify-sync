<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Assets;

use ApolloWeb\WPWooCommercePrintifySync\Admin\UIManager;

class AssetsManager
{
    private UIManager $uiManager;
    
    /**
     * Initialize the assets manager
     */
    public function __construct(UIManager $uiManager) 
    {
        $this->uiManager = $uiManager;
        
        // Register hooks for asset loading
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }
    
    /**
     * Enqueue custom admin assets for our enhanced UI
     */
    public function enqueueAdminAssets($hook): void
    {
        // Only load on our plugin pages
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }
        
        $this->registerCoreAssets();
        $this->registerCustomAssets();
        $this->enqueuePageSpecificAssets($hook);
        $this->localizeScripts();
    }
    
    /**
     * Register core third-party assets
     */
    private function registerCoreAssets(): void
    {
        // Register Bootstrap 5
        wp_register_style(
            'wpwps-bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
            [],
            '5.2.3'
        );
        
        wp_register_script(
            'wpwps-bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.2.3',
            true
        );

        // Register Font Awesome 6
        wp_register_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // Register Chart.js
        wp_register_script(
            'wpwps-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js',
            [],
            '4.3.0',
            true
        );
    }
    
    /**
     * Register custom plugin assets
     */
    private function registerCustomAssets(): void
    {
        // Register custom Bootstrap CSS overrides
        wp_register_style(
            'wpwps-bootstrap-custom',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-bootstrap-custom.css',
            ['wpwps-bootstrap-css'],
            WPWPS_VERSION
        );
        
        // Register custom Bootstrap behaviors
        wp_register_script(
            'wpwps-bootstrap-custom',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-bootstrap-custom.js',
            ['jquery', 'wpwps-bootstrap-js'],
            WPWPS_VERSION,
            true
        );
        
        // Common assets
        wp_register_style(
            'wpwps-common',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin.css',
            [],
            WPWPS_VERSION
        );
        
        wp_register_script(
            'wpwps-common',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-common.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        // Page-specific assets
        wp_register_script(
            'wpwps-dashboard',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-dashboard.js',
            ['jquery', 'wpwps-chartjs'],
            WPWPS_VERSION,
            true
        );
        
        wp_register_script(
            'wpwps-settings',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-settings.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        wp_register_script(
            'wpwps-product-sync',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-product-sync.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        wp_register_script(
            'wpwps-order-sync',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-order-sync.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        wp_register_script(
            'wpwps-tickets',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-tickets.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
    }
    
    /**
     * Enqueue page-specific assets based on the current admin page
     */
    private function enqueuePageSpecificAssets($hook): void
    {
        // Always enqueue these styles for our plugin pages
        wp_enqueue_style('wpwps-bootstrap-css');
        wp_enqueue_style('wpwps-fontawesome');
        wp_enqueue_style('wpwps-bootstrap-custom');
        wp_enqueue_style('wpwps-common');
        
        // Always enqueue these scripts for our plugin pages
        wp_enqueue_script('wpwps-bootstrap-js');
        wp_enqueue_script('wpwps-bootstrap-custom');
        wp_enqueue_script('wpwps-common');
        
        // Page-specific assets
        $current_page = str_replace('toplevel_page_', '', $hook);
        
        switch ($current_page) {
            case 'wpwps-dashboard':
                wp_enqueue_script('wpwps-dashboard');
                break;
                
            case 'wpwps-settings':
                wp_enqueue_script('wpwps-settings');
                break;
                
            case 'wpwps-product-sync':
                wp_enqueue_script('wpwps-product-sync');
                break;
                
            case 'wpwps-order-sync':
                wp_enqueue_script('wpwps-order-sync');
                break;
                
            case 'wpwps-tickets':
                wp_enqueue_script('wpwps-tickets');
                break;
        }
    }
    
    /**
     * Localize scripts with necessary data
     */
    private function localizeScripts(): void
    {
        // Localize script with AJAX URL and nonce
        wp_localize_script('wpwps-common', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-nonce'),
            'plugin_url' => WPWPS_PLUGIN_URL
        ]);
        
        // Add notification data to bootstrap custom JS
        wp_localize_script('wpwps-bootstrap-custom', 'wpwpsUI', [
            'notifications' => $this->uiManager->getNotifications(),
            'current_user' => [
                'name' => wp_get_current_user()->display_name,
                'avatar' => get_avatar_url(wp_get_current_user()->ID),
                'role' => $this->uiManager->getCurrentUserRole()
            ],
            'sidebar_state' => get_user_meta(get_current_user_id(), 'wpwps_sidebar_collapsed', true) ?: 'expanded'
        ]);
    }
}
