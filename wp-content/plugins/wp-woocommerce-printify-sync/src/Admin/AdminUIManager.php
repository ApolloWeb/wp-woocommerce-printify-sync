<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;

class AdminUIManager
{
    private AppContext $context;

    public function __construct()
    {
        $this->context = AppContext::getInstance();
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (!str_contains($hook, 'printify-sync')) {
            return;
        }

        // AdminLTE Core CSS
        wp_enqueue_style(
            'adminlte',
            'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css',
            [],
            '3.2.0'
        );

        // Font Awesome
        wp_enqueue_style(
            'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            [],
            '5.15.4'
        );

        // AdminLTE JS
        wp_enqueue_script(
            'adminlte',
            'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js',
            ['jquery'],
            '3.2.0',
            true
        );

        // Custom Admin CSS
        wp_enqueue_style(
            'wpwps-admin',
            plugins_url('assets/css/admin.css', WPWPS_PLUGIN_FILE),
            ['adminlte'],
            '1.0.0'
        );

        // Custom Admin JS
        wp_enqueue_script(
            'wpwps-admin',
            plugins_url('assets/js/admin.js', WPWPS_PLUGIN_FILE),
            ['adminlte'],
            '1.0.0',
            true
        );

        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin'),
            'currentTime' => $this->context->getCurrentTime(),
            'currentUser' => $this->context->getCurrentUser(),
            'environment' => $this->context->getEnvironment()
        ]);
    }
}