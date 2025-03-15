<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

class SettingsManager
{
    use LoggerAwareTrait;

    private AppContext $context;
    private string $currentTime = '2025-03-15 20:29:15';
    private string $currentUser = 'ApolloWeb';
    private array $encryptedFields = [
        'wpwps_printify_api_key',
        'wpwps_openai_api_key',
        'wpwps_exchange_rate_api_key',
        'wpwps_smtp_password',
        'wpwps_pop3_password'
    ];

    public function __construct()
    {
        $this->context = AppContext::getInstance();
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void
    {
        register_setting(
            'wpwps_settings',
            'wpwps_settings',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeSettings']
            ]
        );

        $this->addSettingsSections();
    }

    private function addSettingsSections(): void
    {
        // API Credentials Section
        add_settings_section(
            'wpwps_api_credentials',
            'API Credentials',
            [$this, 'renderApiCredentialsSection'],
            'wpwps_settings'
        );

        // Email Configuration Section
        add_settings_section(
            'wpwps_email_config',
            'Email Configuration',
            [$this, 'renderEmailConfigSection'],
            'wpwps_settings'
        );

        // Automation Settings Section
        add_settings_section(
            'wpwps_automation',
            'Automation Settings',
            [$this, 'renderAutomationSection'],
            'wpwps_settings'
        );

        $this->addSettingsFields();
    }

    private function addSettingsFields(): void
    {
        // API Credentials Fields
        $this->addField('printify_api_key', 'Printify API Key', 'api_credentials', 'password');
        $this->addField('openai_api_key', 'OpenAI API Key', 'api_credentials', 'password');
        $this->addField('exchange_rate_api_key', 'Exchange Rate API Key', 'api_credentials', 'password');

        // Email Configuration Fields
        $this->addField('smtp_host', 'SMTP Host', 'email_config', 'text');
        $this->addField('smtp_port', 'SMTP Port', 'email_config', 'number');
        $this->addField('smtp_user', 'SMTP Username', 'email_config', 'text');
        $this->addField('smtp_password', 'SMTP Password', 'email_config', 'password');
        $this->addField('pop3_host', 'POP3 Host', 'email_config', 'text');
        $this->addField('pop3_port', 'POP3 Port', 'email_config', 'number');
        $this->addField('pop3_user', 'POP3 Username', 'email_config', 'text');
        $this->addField('pop3_password', 'POP3 Password', 'email_config', 'password');

        // Automation Settings Fields
        $this->addField('auto_sync_products', 'Auto Sync Products', 'automation', 'checkbox');
        $this->addField('sync_interval', 'Sync Interval', 'automation', 'select', [
            'hourly' => 'Every Hour',
            'twicedaily' => 'Twice Daily',
            'daily' => 'Once Daily'
        ]);
        $this->addField('exchange_rate_update', 'Update Exchange Rates', 'automation', 'select', [
            '1' => 'Every Hour',
            '3' => 'Every 3 Hours',
            '6' => 'Every 6 Hours',
            '12' => 'Every 12 Hours',
            '24' => 'Every 24 Hours'
        ]);
    }

    private function addField(
        string $name,
        string $label,
        string $section,
        string $type,
        array $options = []
    ): void {
        add_settings_field(
            "wpwps_{$name}",
            $label,
            [$this, 'renderField'],
            'wpwps_settings',
            "wpwps_{$section}",
            [
                'name' => $name,
                'type' => $type,
                'options' => $options
            ]
        );
    }

    public function renderField(array $args): void
    {
        $option = "wpwps_{$args['name']}";
        $value = get_option($option);
        $type ▋