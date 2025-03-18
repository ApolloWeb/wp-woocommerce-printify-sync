<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:58:33

class AdminSettings implements ServiceProvider
{
    public function boot()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addAdminMenu()
    {
        add_options_page(
            'Printify Sync Settings',
            'Printify Sync',
            'manage_options',
            'printify-settings',
            [$this, 'createAdminPage']
        );
    }

    public function registerSettings()
    {
        register_setting('printify_settings', 'printify_endpoint');
        register_setting('printify_settings', 'printify_api_key');
    }

    public function createAdminPage()
    {
        include plugin_dir_path(__FILE__) . '../templates/admin-settings-template.html';
    }
}