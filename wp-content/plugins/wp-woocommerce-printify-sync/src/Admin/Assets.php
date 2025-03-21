<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Assets
{
    public function register(): void
    {
        if (!$this->isPluginPage()) {
            return;
        }

        // Register global admin assets
        wp_enqueue_style(
            'wpwps-admin',
            WPWS_PLUGIN_URL . 'assets/css/wpwps-admin.css',
            [],
            WPWS_VERSION
        );

        // Register Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // Register Bootstrap
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );

        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.0',
            true
        );

        // Register Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '4.3.0',
            true
        );
    }

    private function isPluginPage(): bool
    {
        return strpos(get_current_screen()->id, 'wpwps') !== false;
    }
}
