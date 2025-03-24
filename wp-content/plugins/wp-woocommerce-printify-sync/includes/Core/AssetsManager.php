<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class AssetsManager
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function enqueueAdminAssets(): void
    {
        // Bootstrap 5 CSS and JS
        wp_enqueue_style(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
            [],
            '5.1.3'
        );
        
        wp_enqueue_script(
            'wpwps-bootstrap-bundle',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.1.3',
            true
        );
        
        // Inter Font
        wp_enqueue_style(
            'wpwps-inter-font',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            [],
            WPWPS_VERSION
        );
        
        // Font Awesome
        wp_enqueue_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            [],
            '6.0.0'
        );
        
        // Chart.js
        wp_enqueue_script(
            'wpwps-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            ['wpwps-bootstrap-bundle'],
            '3.7.0',
            true
        );
        
        // Add GSAP
        wp_enqueue_script(
            'wpwps-gsap',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
            [],
            '3.12.2',
            true
        );

        // Add Particles.js
        wp_enqueue_script(
            'wpwps-particles',
            'https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js',
            [],
            '2.0.0',
            true
        );
        
        // Custom Admin Assets
        wp_enqueue_style(
            'wpwps-admin-styles',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin.css',
            ['wpwps-bootstrap'],
            WPWPS_VERSION
        );
        
        wp_enqueue_script(
            'wpwps-admin-scripts',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-admin.js',
            ['jquery', 'wpwps-bootstrap-bundle', 'wpwps-chartjs', 'wpwps-gsap', 'wpwps-particles'],
            WPWPS_VERSION,
            true
        );
        
        // Localized Data
        wp_localize_script('wpwps-admin-scripts', 'wpwpsAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin'),
            'i18n' => [
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                'error' => __('Error', 'wp-woocommerce-printify-sync'),
                'success' => __('Success', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }
}
