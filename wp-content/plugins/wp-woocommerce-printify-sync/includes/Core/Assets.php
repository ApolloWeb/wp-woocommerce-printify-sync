<?php
/**
 * Asset manager class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class Assets
 */
class Assets {
    /**
     * Initialize assets
     *
     * @return void
     */
    public function init() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Register and enqueue admin assets
     *
     * @param string $hook Current admin page.
     * @return void
     */
    public function enqueueAdminAssets($hook) {
        $admin_pages = [
            'toplevel_page_wpwps-dashboard',
            'printify-sync_page_wpwps-settings',
            'printify-sync_page_wpwps-products',
            'printify-sync_page_wpwps-orders',
            'printify-sync_page_wpwps-tickets'
        ];

        // Only load on plugin pages
        if (!in_array($hook, $admin_pages, true)) {
            return;
        }

        // Bootstrap 5
        wp_enqueue_style(
            'wpwps-bootstrap',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        wp_enqueue_script(
            'wpwps-bootstrap',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-bootstrap.bundle.min.js',
            ['jquery'],
            '5.3.0',
            true
        );

        // Font Awesome
        wp_enqueue_style(
            'wpwps-fontawesome',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-fontawesome.min.css',
            [],
            '6.4.0'
        );

        // Chart.js
        wp_enqueue_script(
            'wpwps-chartjs',
            WPWPS_PLUGIN_URL . 'assets/js/chart.min.js',
            [],
            '4.3.0',
            true
        );

        // Common plugin styles and scripts
        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin.css',
            ['wpwps-bootstrap', 'wpwps-fontawesome'],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wpwps-bootstrap', 'wpwps-chartjs'],
            WPWPS_VERSION,
            true
        );

        // Page-specific scripts
        $page_script = $this->getPageScript($hook);
        if ($page_script) {
            wp_enqueue_script(
                'wpwps-' . $page_script,
                WPWPS_PLUGIN_URL . 'assets/js/' . $page_script . '.js',
                ['jquery', 'wpwps-admin'],
                WPWPS_VERSION,
                true
            );
            
            // Add localized data
            wp_localize_script('wpwps-' . $page_script, 'wpwps', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps-ajax-nonce'),
                'plugin_url' => WPWPS_PLUGIN_URL,
                'i18n' => [
                    'success' => __('Success!', 'wp-woocommerce-printify-sync'),
                    'error' => __('Error', 'wp-woocommerce-printify-sync'),
                    'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
                    'processing' => __('Processing...', 'wp-woocommerce-printify-sync'),
                ]
            ]);
        }
    }
    
    /**
     * Get the script name for the current admin page
     *
     * @param string $hook Current admin page.
     * @return string|false Script name or false if not found
     */
    private function getPageScript($hook) {
        $page_scripts = [
            'toplevel_page_wpwps-dashboard' => 'dashboard',
            'printify-sync_page_wpwps-settings' => 'settings',
            'printify-sync_page_wpwps-products' => 'products',
            'printify-sync_page_wpwps-orders' => 'orders',
            'printify-sync_page_wpwps-tickets' => 'tickets',
        ];
        
        return isset($page_scripts[$hook]) ? $page_scripts[$hook] : false;
    }
}
