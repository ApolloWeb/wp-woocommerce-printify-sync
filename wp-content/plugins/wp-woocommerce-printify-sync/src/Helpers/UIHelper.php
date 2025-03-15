<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class UIHelper
{
    private string $currentTime = '2025-03-15 18:29:55';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'wpwps') === false) {
            return;
        }

        // Material Icons
        wp_enqueue_style(
            'material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons'
        );

        // Custom Admin CSS
        wp_enqueue_style(
            'wpwps-admin',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/admin.css',
            [],
            WPWPS_VERSION
        );

        // Bootstrap with custom theme
        wp_enqueue_style(
            'wpwps-bootstrap',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/bootstrap-custom.css',
            [],
            '5.3.0'
        );

        // Animation library
        wp_enqueue_style(
            'animate',
            'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css'
        );

        // Charts
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '4.4.0',
            true
        );

        // Custom JS
        wp_enqueue_script(
            'wpwps-admin',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/admin.js',
            ['jquery', 'wp-element'],
            WPWPS_VERSION,
            true
        );
    }
}