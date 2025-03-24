<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Settings
{
    private const OPTION_KEY = 'wpwps_settings';
    private $settings;

    public function __construct()
    {
        $this->settings = get_option(self::OPTION_KEY, []);
    }

    public function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->settings[$key] = $value;
        update_option(self::OPTION_KEY, $this->settings);
    }
    
    public function getEncryptedKey(string $key)
    {
        $value = $this->get($key);
        return $value ? $this->decrypt($value) : null;
    }

    public function setEncryptedKey(string $key, string $value): void
    {
        $this->set($key, $this->encrypt($value));
    }

    private function encrypt(string $value): string
    {
        return base64_encode($value);  // TODO: Implement proper encryption
    }

    private function decrypt(string $value): string
    {
        return base64_decode($value);  // TODO: Implement proper decryption
    }
}
