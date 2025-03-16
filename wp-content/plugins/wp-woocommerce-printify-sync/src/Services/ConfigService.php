<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ConfigService
{
    private const PREFIX = 'wpwps_';
    private array $config;
    private static ?ConfigService $instance = null;

    private function __construct()
    {
        $this->loadConfig();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
        update_option(self::PREFIX . $key, $value);
    }

    private function loadConfig(): void
    {
        $this->config = [
            'api_key' => get_option(self::PREFIX . 'api_key'),
            'shop_id' => get_option(self::PREFIX . 'shop_id'),
            'ipgeolocation_api_key' => get_option(self::PREFIX . 'ipgeolocation_api_key'),
            'currency_api_key' => get_option(self::PREFIX . 'currency_api_key'),
            'enable_auto_currency' => get_option(self::PREFIX . 'enable_auto_currency', true),
            'default_currency' => get_option(self::PREFIX . 'default_currency', 'USD'),
            'cache_duration' => get_option(self::PREFIX . 'cache_duration', 3600),
            'enable_geolocation' => get_option(self::PREFIX . 'enable_geolocation', true),
        ];
    }
}