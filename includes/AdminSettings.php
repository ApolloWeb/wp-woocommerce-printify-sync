<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class AdminSettings {

    public static function renderSettingsPage() {
        ?>
        <div class="wrap">
            <h1><?php _e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('printify_sync_settings');
                do_settings_sections('printify_sync_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function initializeSettings() {
        register_setting('printify_sync_settings', 'printify_api_key');
        register_setting('printify_sync_settings', 'woocommerce_api_key');
        register_setting('printify_sync_settings', 'enable_auto_sync');
        register_setting('printify_sync_settings', 'cron_interval');
        register_setting('printify_sync_settings', 'enable_auto_zone_creation');
        register_setting('printify_sync_settings', 'environment_mode');

        add_settings_section(
            'printify_sync_api_settings',
            __('API Settings', 'wp-woocommerce-printify-sync'),
            null,
            'printify_sync_settings'
        );

        add_settings_field(
            'printify_api_key',
            __('Printify API Key', 'wp-woocommerce-printify-sync'),
            [__CLASS__, 'renderApiKeyField'],
            'printify_sync_settings',
            'printify_sync_api_settings',
            ['label_for' => 'printify_api_key']
        );

        add_settings_field(
            'woocommerce_api_key',
            __('WooCommerce API Key', 'wp-woocommerce-printify-sync'),
            [__CLASS__, 'renderApiKeyField'],
            'printify_sync_settings',
            'printify_sync_api_settings',
            ['label_for' => 'woocommerce_api_key']
        );

        add_settings_section(
            'printify_sync_automation_settings',
            __('Automation Settings', 'wp-woocommerce-printify-sync'),
            null,
            'printify_sync_settings'
        );

        add_settings_field(
            'enable_auto_sync',
            __('Enable Automatic Sync', 'wp-woocommerce-printify-sync'),
            [__CLASS__, 'renderCheckboxField'],
            'printify_sync_settings',
            'printify_sync_automation_settings',
            ['label_for' => 'enable_auto_sync']
        );

        add_settings_field(
            'cron_interval',
            __('Cron Job Interval (in minutes)', 'wp-woocommerce-printify-sync'),
            [__CLASS__, 'renderTextField'],
            'printify_sync_settings',
            'printify_sync_automation_settings',
            ['label_for' => 'cron_interval']
        );

        add_settings_field(
            'enable_auto_zone_creation',
            __('Enable Automatic Zone Creation', 'wp-woocommerce-printify-sync'),
            [__CLASS__, 'renderCheckboxField'],
            'printify_sync_settings',
            'printify_sync_automation_settings',
            ['label_for' => 'enable_auto_zone_creation']
        );

        add_settings_section(
            'printify_sync_environment_settings',
            __('Environment Settings', 'wp-woocommerce-printify-sync'),
            null,
            'printify_sync_settings'
        );

        add_settings_field(
            'environment_mode',
            __('Environment Mode', 'wp-woocommerce-printify-sync'),
            [__CLASS__, 'renderSelectField'],
            'printify_sync_settings',
            'printify_sync_environment_settings',
            ['label_for' => 'environment_mode']
        );
    }

    public static function renderApiKeyField($args) {
        $option = get_option($args['label_for']);
        echo "<input type='text' id='{$args['label_for']}' name='{$args['label_for']}' value='{$option}' />";
    }

    public static function renderCheckboxField($args) {
        $option = get_option($args['label_for']);
        $checked = $option ? 'checked' : '';
        echo "<input type='checkbox' id='{$args['label_for']}' name='{$args['label_for']}' {$checked} />";
    }

    public static function renderTextField($args) {
        $option = get_option($args['label_for']);
        echo "<input type='text' id='{$args['label_for']}' name='{$args['label_for']}' value='{$option}' />";
    }

    public static function renderSelectField($args) {
        $option = get_option($args['label_for']);
        $options = ['production' => 'Production', 'development' => 'Development'];
        echo "<select id='{$args['label_for']}' name='{$args['label_for']}'>";
        foreach ($options as $value => $label) {
            $selected = $option == $value ? 'selected' : '';
            echo "<option value='{$value}' {$selected}>{$label}</option>";
        }
        echo "</select>";
    }

    public static function saveSettings() {
        // Logic to save settings via AJAX
        check_ajax_referer('save_settings_nonce', 'nonce');
        // Save settings logic here
        wp_send_json_success();
    }
}

add_action('admin_init', ['ApolloWeb\WPWooCommercePrintifySync\AdminSettings', 'initializeSettings']);