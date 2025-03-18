<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:14:00

class AdminProductImport implements ServiceProvider
{
    public function boot()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
    }

    public function addAdminMenu()
    {
        add_menu_page(
            'Printify Product Import',
            'Printify Sync <i class="fas fa-tshirt"></i>',
            'manage_options',
            'printify-product-import',
            [$this, 'createAdminPage'],
            'dashicons-tshirt',
            56
        );
    }

    public function createAdminPage()
    {
        include plugin_dir_path(__FILE__) . '../templates/admin-product-import-template.html';
    }
}