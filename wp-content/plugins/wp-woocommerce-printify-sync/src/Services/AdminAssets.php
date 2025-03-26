<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Handles registration and enqueuing of admin assets
 */
class AdminAssets {
    /**
     * Initialize admin assets
     */
    public function __construct() {
        // Register shared assets for all admin pages
        add_action('admin_enqueue_scripts', [$this, 'registerSharedAssets']);
    }

    /**
     * Register shared assets that will be available across all plugin admin pages
     */
    public function registerSharedAssets(): void {
        // Register Google Fonts - Inter
        wp_register_style(
            'google-fonts-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
        );

        // Register Bootstrap with utilities
        wp_register_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css'
        );
        wp_register_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            null,
            true
        );

        // Register Font Awesome 6
        wp_register_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
        );

        // Register Chart.js
        wp_register_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );

        // Register shared toast notifications
        wp_register_script(
            'wpwps-toast',
            WPWPS_URL . 'assets/js/shared/toast.js',
            ['jquery', 'bootstrap'],
            WPWPS_VERSION,
            true
        );
    }
}