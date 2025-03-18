<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:14:00

class AdminSettings implements ServiceProvider
{
    public function boot()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addAdminMenu()
    {
        add_options_page('Printify Settings', 'Printify Settings', 'manage_options', 'printify-settings', [$this, 'createAdminPage']);
    }

    public function createAdminPage()
    {
        include plugin_dir_path(__FILE__) . '../templates/admin-settings-template.html';
    }

    public function registerSettings()
    {
        register_setting('printify_settings', 'printify_endpoint');
        register_setting('printify_settings', 'printify_api_key', ['sanitize_callback' => [$this, 'encryptApiKey']]);
    }

    public function encryptApiKey($api_key)
    {
        if (empty($api_key)) {
            return '';
        }

        $salt = wp_salt();
        return base64_encode(openssl_encrypt($api_key, 'aes-256-cbc', $salt, 0, substr($salt, 0, 16)));
    }

    public function decryptApiKey($encrypted_api_key)
    {
        $salt = wp_salt();
        return openssl_decrypt(base64_decode($encrypted_api_key), 'aes-256-cbc', $salt, 0, substr($salt, 0, 16));
    }
}