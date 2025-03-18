<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\ServiceProvider;

class AdminSettings extends ServiceProvider
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
        register_setting('printify_settings', 'printify_api_base_url'); // Add API base URL setting
    }

    public function createAdminPage()
    {
        include plugin_dir_path(__FILE__) . '../templates/admin-settings-template.html';
    }
}