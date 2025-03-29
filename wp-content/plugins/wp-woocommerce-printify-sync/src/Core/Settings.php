<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Settings
{
    private const OPTION_KEY = 'wpwps_settings';
    private array $defaults = [
        'printify_api_key' => '',
        'sync_interval' => 'hourly',
        'sync_products' => true,
        'sync_orders' => true,
        'logging_enabled' => true,
        'debug_mode' => false,
        'notification_email' => '',
        'openai_api_key' => '',
        'support_ticket_enabled' => true,
        'max_log_files' => 30,
        'max_log_size' => 10485760, // 10MB
    ];

    private array $settings;

    public function __construct()
    {
        $this->settings = get_option(self::OPTION_KEY, []);
        $this->settings = array_merge($this->defaults, $this->settings);
    }

    public function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        if (!array_key_exists($key, $this->defaults)) {
            throw new \InvalidArgumentException("Invalid setting key: {$key}");
        }

        $this->settings[$key] = $value;
    }

    public function save(): bool
    {
        return update_option(self::OPTION_KEY, $this->settings);
    }

    public function reset(): bool
    {
        $this->settings = $this->defaults;
        return $this->save();
    }

    public function all(): array
    {
        return $this->settings;
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->settings['printify_api_key'])) {
            $errors[] = 'Printify API key is required';
        }

        if ($this->settings['support_ticket_enabled'] && empty($this->settings['openai_api_key'])) {
            $errors[] = 'OpenAI API key is required when support tickets are enabled';
        }

        if (!empty($this->settings['notification_email']) && !is_email($this->settings['notification_email'])) {
            $errors[] = 'Invalid notification email address';
        }

        if (!in_array($this->settings['sync_interval'], ['hourly', 'twicedaily', 'daily'])) {
            $errors[] = 'Invalid sync interval';
        }

        return $errors;
    }

    public function getValidIntervals(): array
    {
        return [
            'hourly' => __('Hourly', 'wp-woocommerce-printify-sync'),
            'twicedaily' => __('Twice Daily', 'wp-woocommerce-printify-sync'),
            'daily' => __('Daily', 'wp-woocommerce-printify-sync')
        ];
    }
}