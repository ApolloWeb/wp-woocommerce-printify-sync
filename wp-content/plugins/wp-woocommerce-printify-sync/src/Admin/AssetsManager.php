<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AssetsManager {
    private const MENU_SLUG = 'wp-woocommerce-printify-sync';

    public function enqueueAssets(string $hook): void {
        // Log all hooks in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WPWPS: Hook called: ' . $hook);
        }

        // Check for any of our plugin pages in a more comprehensive way
        if (strpos($hook, self::MENU_SLUG) === false && 
            strpos($hook, 'printify') === false && 
            strpos($hook, 'woocommerce-printify') === false) {
            
            // Skip if not our page, but log it in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WPWPS: Skipping asset loading for hook: ' . $hook);
            }
            return;
        }

        // We reached here, so we're on one of our plugin pages
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WPWPS: Loading assets for hook: ' . $hook);
        }

        $this->enqueueStyles();
        $this->enqueueScripts();
    }

    private function enqueueStyles(): void {
        // Inter font from Google Fonts
        wp_enqueue_style(
            'google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            [],
            PLUGIN_VERSION
        );

        // Bootstrap with custom theme
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
            [],
            '5.1.3'
        );

        // Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            [],
            '6.0.0'
        );

        // Custom admin styles
        wp_enqueue_style(
            'wpwps-admin',
            plugin_dir_url(PLUGIN_FILE) . 'assets/css/wpwps-admin.css',
            ['bootstrap', 'font-awesome'],
            PLUGIN_VERSION
        );
    }

    private function enqueueScripts(): void {
        // Bootstrap JS
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.1.3',
            true
        );

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '3.7.0',
            true
        );

        // Custom admin scripts
        wp_enqueue_script(
            'wpwps-settings',
            plugin_dir_url(PLUGIN_FILE) . 'assets/js/wpwps-settings.js',
            ['jquery', 'bootstrap', 'chartjs'],
            PLUGIN_VERSION,
            true
        );

        wp_localize_script('wpwps-settings', 'wpPrintifySync', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-woocommerce-printify-sync-settings')
        ]);

        // Force display script for debugging (load last)
        wp_enqueue_script(
            'wpwps-force-display',
            plugin_dir_url(PLUGIN_FILE) . 'assets/js/wpwps-force-display.js',
            ['jquery', 'wpwps-settings'],
            PLUGIN_VERSION,
            true
        );
    }
}
