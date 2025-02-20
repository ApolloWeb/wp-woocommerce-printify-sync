<?php

namespace ApolloWeb\WooCommercePrintifySync;

class Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'createAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function createAdminMenu()
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync',
            [$this, 'settingsPage'],
            'dashicons-update'
        );
    }

    public function registerSettings()
    {
        register_setting('printify_sync_settings', 'printify_api_key');
        add_settings_section('printify_sync_section', null, null, 'printify-sync');
        add_settings_field(
            'printify_api_key',
            __('Printify API Key', 'wp-woocommerce-printify-sync'),
            [$this, 'apiKeyFieldHtml'],
            'printify-sync',
            'printify_sync_section'
        );
    }

    public function apiKeyFieldHtml()
    {
        $apiKey = get_option('printify_api_key');
        echo '<input type="text" name="printify_api_key" value="' . esc_attr($apiKey) . '" />';
    }

    public function settingsPage()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('printify_sync_settings');
                do_settings_sections('printify-sync');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}