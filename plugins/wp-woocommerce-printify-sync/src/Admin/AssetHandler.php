<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AssetHandler {
    public function enqueueAssets(string $hook): void {
        if (false === strpos($hook, 'wpwps')) {
            return;
        }
        // Enqueue plugin assets using the new paths.
        wp_enqueue_style(
            'wpwps-settings',
            plugins_url('assets/css/wpwps-settings.css', dirname(__DIR__, 2)),
            [],
            WPWPS_VERSION
        );
        wp_enqueue_script(
            'wpwps-settings',
            plugins_url('assets/js/wpwps-settings.js', dirname(__DIR__, 2)),
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        wp_enqueue_script(
            'wpwps-admin',
            plugins_url('assets/js/wpwps-admin.js', dirname(__DIR__, 2)),
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        // Enqueue external libraries.
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        wp_enqueue_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.2.1/chart.min.js',
            [],
            '4.2.1',
            true
        );
        wp_enqueue_style(
            'adminlte',
            'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css',
            [],
            '3.2.0'
        );
        wp_enqueue_script(
            'adminlte',
            'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js',
            ['jquery'],
            '3.2.0',
            true
        );
        
        wp_localize_script(
            'wpwps-settings',
            'wpwpsAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('wpwps_nonce'),
            ]
        );
    }
}
