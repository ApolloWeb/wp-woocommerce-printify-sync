<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AssetManager {
    private $plugin_url;
    private $version;
    
    public function __construct() {
        $this->plugin_url = WPWPS_PLUGIN_URL;
        $this->version = WPWPS_VERSION;
    }

    public function init() {
        add_action('admin_enqueue_scripts', [$this, 'registerAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueuePageAssets']);
    }

    public function registerAssets() {
        // Register vendor scripts
        wp_register_script('wpwps-bootstrap', $this->plugin_url . 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js', ['jquery'], '5.1.3', true);
        wp_register_script('wpwps-chartjs', $this->plugin_url . 'assets/vendor/chart.js/chart.min.js', [], '3.7.0', true);
        
        // Register plugin scripts
        wp_register_script('wpwps-dashboard', $this->plugin_url . 'assets/js/wpwps-dashboard.js', ['jquery', 'wpwps-bootstrap', 'wpwps-chartjs'], $this->version, true);
        wp_register_script('wpwps-orders', $this->plugin_url . 'assets/js/wpwps-orders.js', ['jquery', 'wpwps-bootstrap'], $this->version, true);
        
        // Register styles
        wp_register_style('wpwps-bootstrap', $this->plugin_url . 'assets/vendor/bootstrap/css/bootstrap.min.css', [], '5.1.3');
        wp_register_style('wpwps-main', $this->plugin_url . 'assets/css/wpwps-admin.css', ['wpwps-bootstrap'], $this->version);
    }

    public function enqueuePageAssets($hook) {
        $page_scripts = [
            'wpwps-dashboard' => ['wpwps-dashboard'],
            'wpwps-orders' => ['wpwps-orders'],
            'wpwps-settings' => ['wpwps-settings']
        ];

        foreach ($page_scripts as $page => $scripts) {
            if (strpos($hook, $page) !== false) {
                foreach ($scripts as $script) {
                    wp_enqueue_script($script);
                    wp_localize_script($script, 'wpwps_data', $this->getLocalizationData($page));
                }
                wp_enqueue_style('wpwps-main');
            }
        }
    }

    private function getLocalizationData($page) {
        $data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_ajax_nonce')
        ];

        return apply_filters('wpwps_localize_script_' . $page, $data);
    }
}
