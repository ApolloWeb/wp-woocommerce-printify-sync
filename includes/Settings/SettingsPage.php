<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Settings;

class SettingsPage
{
    public static function register()
    {
        add_action('admin_menu', [__CLASS__, 'addSettingsPage']);
        add_action('admin_init', [__CLASS__, 'registerSettings']);
    }

    public static function addSettingsPage()
    {
        add_options_page(
            'Printify Sync Settings',
            'Printify Sync',
            'manage_options',
            'printify-sync-settings',
            [__CLASS__, 'renderSettingsPage']
        );
    }

    public static function registerSettings()
    {
        register_setting('printify_sync_settings', 'printify_sync_postman_api_key');

        add_settings_section(
            'printify_sync_settings_section',
            'Printify Sync Settings',
            null,
            'printify_sync_settings'
        );

        add_settings_field(
            'printify_sync_postman_api_key',
            'Postman API Key',
            [__CLASS__, 'renderPostmanApiKeyField'],
            'printify_sync_settings',
            'printify_sync_settings_section'
        );
    }

    public static function renderPostmanApiKeyField()
    {
        $apiKey = get_option('printify_sync_postman_api_key');
        echo '<input type="text" name="printify_sync_postman_api_key" value="' . esc_attr($apiKey) . '" />';
    }

    public static function renderSettingsPage()
    {
        include plugin_dir_path(__FILE__) . '../../templates/admin/settings-page.php';
    }
}