<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminAssets {
    public function init(): void {
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueStyles(): void {
        // Core styles
        wp_enqueue_style(
            'wpps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
        );

        wp_enqueue_style(
            'wpps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
        );

        wp_enqueue_style(
            'wpps-inter-font',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
        );

        // Page specific styles
        $page = $_GET['page'] ?? '';
        if (strpos($page, 'wpps-') === 0) {
            $page_name = str_replace('wpps-', '', $page);
            wp_enqueue_style(
                "wpps-{$page_name}",
                WPPS_PUBLIC_URL . "css/wpwps-{$page_name}.css",
                [],
                WPPS_VERSION
            );
        }
    }

    public function enqueueScripts(): void {
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'wpps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'wpps-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );

        // Page specific scripts
        $page = $_GET['page'] ?? '';
        if (strpos($page, 'wpps-') === 0) {
            $page_name = str_replace('wpps-', '', $page);
            wp_enqueue_script(
                "wpps-{$page_name}",
                WPPS_PUBLIC_URL . "js/wpwps-{$page_name}.js",
                ['jquery'],
                WPPS_VERSION,
                true
            );
            
            wp_localize_script("wpps-{$page_name}", 'wppsAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpps_admin_nonce')
            ]);
        }
    }
}
