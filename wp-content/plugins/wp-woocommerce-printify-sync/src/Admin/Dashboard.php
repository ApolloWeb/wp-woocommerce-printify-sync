<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Dashboard {
    public function init(): void {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function renderPage(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        require PRINTIFY_SYNC_PATH . 'templates/admin/dashboard.php';
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'toplevel_page_printify-sync') {
            return;
        }

        // AdminLTE CSS
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

        // Plugin CSS
        wp_enqueue_style(
            'printify-sync-admin',
            PRINTIFY_SYNC_URL . 'assets/css/admin.css',
            ['adminlte', 'fontawesome'],
            PRINTIFY_SYNC_VERSION
        );
    }
}
