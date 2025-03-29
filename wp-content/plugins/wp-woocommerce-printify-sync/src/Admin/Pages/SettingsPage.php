<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;

class SettingsPage
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register(): void
    {
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'render']
        );

        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerSettings(): void
    {
        register_setting('wpwps_settings', 'wpwps_settings', [
            'sanitize_callback' => [$this, 'sanitizeSettings']
        ]);

        // API Settings
        add_settings_section(
            'wpwps_api_settings',
            __('API Settings', 'wp-woocommerce-printify-sync'),
            null,
            'wpwps-settings'
        );

        add_settings_field(
            'printify_api_key',
            __('Printify API Key', 'wp-woocommerce-printify-sync'),
            [$this, 'renderField'],
            'wpwps-settings',
            'wpwps_api_settings',
            [
                'type' => 'password',
                'name' => 'printify_api_key',
                'description' => __('Enter your Printify API key', 'wp-woocommerce-printify-sync')
            ]
        );

        add_settings_field(
            'openai_api_key',
            __('OpenAI API Key', 'wp-woocommerce-printify-sync'),
            [$this, 'renderField'],
            'wpwps-settings',
            'wpwps_api_settings',
            [
                'type' => 'password',
                'name' => 'openai_api_key',
                'description' => __('Required for AI-powered support tickets', 'wp-woocommerce-printify-sync')
            ]
        );

        // Sync Settings
        add_settings_section(
            'wpwps_sync_settings',
            __('Synchronization Settings', 'wp-woocommerce-printify-sync'),
            null,
            'wpwps-settings'
        );

        add_settings_field(
            'sync_interval',
            __('Sync Interval', 'wp-woocommerce-printify-sync'),
            [$this, 'renderSelect'],
            'wpwps-settings',
            'wpwps_sync_settings',
            [
                'name' => 'sync_interval',
                'options' => $this->settings->getValidIntervals()
            ]
        );

        add_settings_field(
            'sync_products',
            __('Sync Products', 'wp-woocommerce-printify-sync'),
            [$this, 'renderCheckbox'],
            'wpwps-settings',
            'wpwps_sync_settings',
            [
                'name' => 'sync_products',
                'description' => __('Enable product synchronization', 'wp-woocommerce-printify-sync')
            ]
        );

        add_settings_field(
            'sync_orders',
            __('Sync Orders', 'wp-woocommerce-printify-sync'),
            [$this, 'renderCheckbox'],
            'wpwps-settings',
            'wpwps_sync_settings',
            [
                'name' => 'sync_orders',
                'description' => __('Enable order synchronization', 'wp-woocommerce-printify-sync')
            ]
        );

        // System Settings
        add_settings_section(
            'wpwps_system_settings',
            __('System Settings', 'wp-woocommerce-printify-sync'),
            null,
            'wpwps-settings'
        );

        add_settings_field(
            'logging_enabled',
            __('Enable Logging', 'wp-woocommerce-printify-sync'),
            [$this, 'renderCheckbox'],
            'wpwps-settings',
            'wpwps_system_settings',
            [
                'name' => 'logging_enabled',
                'description' => __('Enable system logging', 'wp-woocommerce-printify-sync')
            ]
        );

        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'wp-woocommerce-printify-sync'),
            [$this, 'renderCheckbox'],
            'wpwps-settings',
            'wpwps_system_settings',
            [
                'name' => 'debug_mode',
                'description' => __('Enable debug mode', 'wp-woocommerce-printify-sync')
            ]
        );

        add_settings_field(
            'notification_email',
            __('Notification Email', 'wp-woocommerce-printify-sync'),
            [$this, 'renderField'],
            'wpwps-settings',
            'wpwps_system_settings',
            [
                'type' => 'email',
                'name' => 'notification_email',
                'description' => __('Email address for system notifications', 'wp-woocommerce-printify-sync')
            ]
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $errors = $this->settings->validate();
        
        echo View::render('wpwps-settings', [
            'title' => __('Settings', 'wp-woocommerce-printify-sync'),
            'settings' => $this->settings->all(),
            'errors' => $errors
        ]);
    }

    public function sanitizeSettings(array $input): array
    {
        $sanitized = [];

        foreach ($this->settings->all() as $key => $value) {
            switch ($key) {
                case 'printify_api_key':
                case 'openai_api_key':
                    $sanitized[$key] = sanitize_text_field($input[$key] ?? '');
                    break;
                case 'notification_email':
                    $sanitized[$key] = sanitize_email($input[$key] ?? '');
                    break;
                case 'sync_interval':
                    $sanitized[$key] = in_array($input[$key], array_keys($this->settings->getValidIntervals()))
                        ? $input[$key]
                        : 'hourly';
                    break;
                case 'sync_products':
                case 'sync_orders':
                case 'logging_enabled':
                case 'debug_mode':
                case 'support_ticket_enabled':
                    $sanitized[$key] = isset($input[$key]);
                    break;
                case 'max_log_files':
                    $sanitized[$key] = max(1, min(365, (int)($input[$key] ?? 30)));
                    break;
                case 'max_log_size':
                    $sanitized[$key] = max(1048576, min(104857600, (int)($input[$key] ?? 10485760)));
                    break;
                default:
                    $sanitized[$key] = $input[$key] ?? $value;
            }
        }

        return $sanitized;
    }

    public function renderField(array $args): void
    {
        $type = $args['type'] ?? 'text';
        $name = $args['name'] ?? '';
        $value = $this->settings->get($name, '');
        $description = $args['description'] ?? '';

        printf(
            '<input type="%s" name="wpwps_settings[%s]" value="%s" class="regular-text">',
            esc_attr($type),
            esc_attr($name),
            esc_attr($value)
        );

        if ($description) {
            printf('<p class="description">%s</p>', esc_html($description));
        }
    }

    public function renderSelect(array $args): void
    {
        $name = $args['name'] ?? '';
        $options = $args['options'] ?? [];
        $value = $this->settings->get($name, '');

        printf('<select name="wpwps_settings[%s]">', esc_attr($name));
        
        foreach ($options as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($value, $key, false),
                esc_html($label)
            );
        }

        echo '</select>';
    }

    public function renderCheckbox(array $args): void
    {
        $name = $args['name'] ?? '';
        $description = $args['description'] ?? '';
        $checked = $this->settings->get($name, false);

        printf(
            '<label><input type="checkbox" name="wpwps_settings[%s]" %s> %s</label>',
            esc_attr($name),
            checked($checked, true, false),
            esc_html($description)
        );
    }

    public function enqueueAssets(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpwps-settings') {
            return;
        }

        wp_enqueue_style('wpwps-settings', WPWPS_URL . 'assets/css/wpwps-settings.css', [], WPWPS_VERSION);
        wp_enqueue_script('wpwps-settings', WPWPS_URL . 'assets/js/wpwps-settings.js', ['jquery'], WPWPS_VERSION, true);
    }
}