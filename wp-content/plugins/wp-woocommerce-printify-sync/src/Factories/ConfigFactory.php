<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Factories;

class ConfigFactory {
    private static ?self $instance = null;
    private array $config = [];
    private array $defaults = [
        'api' => [
            'printify_key' => '',
            'rate_limit' => 60,
            'timeout' => 30,
        ],
        'sync' => [
            'products_per_page' => 20,
            'auto_sync' => true,
            'sync_interval' => 'hourly',
        ],
        'email' => [
            'notifications_enabled' => true,
            'from_name' => '',
            'from_email' => '',
        ],
        'display' => [
            'items_per_page' => 10,
            'enable_ajax' => true,
        ]
    ];

    public static function create(): self 
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    public static function getInstance(): self 
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() 
    {
        if (!is_null(self::$instance)) {
            throw new \RuntimeException('Use getInstance() to get ConfigFactory instance');
        }
        $this->loadConfig();
    }

    private function __clone() {}

    private function loadConfig(): void 
    {
        foreach ($this->defaults as $section => $values) {
            $stored = get_option("wpwps_{$section}_settings", []);
            $this->config[$section] = wp_parse_args($stored, $values);
        }
    }

    public function get(string $key, $default = null) 
    {
        $parts = explode('.', $key);
        $config = $this->config;

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    public function set(string $key, $value): void 
    {
        $parts = explode('.', $key);
        $section = array_shift($parts);

        if (!isset($this->config[$section])) {
            $this->config[$section] = [];
        }

        $current = &$this->config[$section];
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        $current = $value;
        update_option("wpwps_{$section}_settings", $this->config[$section]);
    }
}
