<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:36:07

class AdminProductImport implements ServiceProvider
{
    public function boot()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
    }

    public function addAdminMenu()
    {
        add_menu_page(
            'Printify Sync Dashboard',
            'Printify Sync <i class="fas fa-tshirt" style="float: right;"></i>',
            'manage_options',
            'printify-sync-dashboard',
            [$this, 'createAdminDashboardPage'],
            'dashicons-admin-generic',
            56
        );

        add_submenu_page(
            'printify-sync-dashboard',
            'Printify Product Import',
            'Product Import',
            'manage_options',
            'printify-product-import',
            [$this, 'createAdminPage']
        );

        add_submenu_page(
            'printify-sync-dashboard',
            'Printify Settings',
            'Settings',
            'manage_options',
            'printify-settings',
            [$this, 'createSettingsPage']
        );
    }

    public function createAdminDashboardPage()
    {
        include plugin_dir_path(__FILE__) . '../templates/admin-dashboard-template.html';
    }

    public function createAdminPage()
    {
        include plugin_dir_path(__FILE__) . '../templates/admin-product-import-template.html';
    }

    public function createSettingsPage()
    {
        include plugin_dir_path(__FILE__) . '../templates/admin-settings-template.html';
    }
}