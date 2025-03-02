<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Settings
{
    public static function init()
    {
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('wp_ajax_save_api_key', [__CLASS__, 'save_api_key']);
    }

    public static function register_settings()
    {
        register_setting('wpwcs_settings', 'wpwcs_api_key');
    }

    public static function save_api_key()
    {
        if (isset($_POST['api_key'])) {
            $api_key = sanitize_text_field($_POST['api_key']);
            update_option('wpwcs_api_key', $api_key);
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}

Settings::init();