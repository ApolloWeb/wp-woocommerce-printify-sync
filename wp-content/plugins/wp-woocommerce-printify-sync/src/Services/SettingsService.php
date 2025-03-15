<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SettingsService
{
    private const ENCRYPTION_METHOD = 'aes-256-cbc';
    private const SALT_LENGTH = 22;
    private string $encryptionKey;

    public function __construct()
    {
        $this->encryptionKey = $this->getEncryptionKey();
    }

    public function encryptApiKey(string $apiKey): string
    {
        $salt = $this->generateSalt();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));
        
        $encrypted = openssl_encrypt(
            $apiKey,
            self::ENCRYPTION_METHOD,
            $this->encryptionKey,
            0,
            $iv
        );

        return base64_encode($salt . '::' . base64_encode($iv) . '::' . $encrypted);
    }

    public function decryptApiKey(string $encryptedData): ?string
    {
        try {
            $parts = explode('::', base64_decode($encryptedData), 3);
            if (count($parts) !== 3) {
                return null;
            }

            [$salt, $iv, $encrypted] = $parts;
            
            return openssl_decrypt(
                $encrypted,
                self::ENCRYPTION_METHOD,
                $this->encryptionKey,
                0,
                base64_decode($iv)
            );
        } catch (\Exception $e) {
            error_log('Decryption error: ' . $e->getMessage());
            return null;
        }
    }

    private function generateSalt(): string
    {
        return bin2hex(random_bytes(self::SALT_LENGTH));
    }

    private function getEncryptionKey(): string
    {
        $key = get_option('wpwps_encryption_key');
        if (!$key) {
            $key = wp_generate_password(64, true, true);
            update_option('wpwps_encryption_key', $key);
        }
        return $key;
    }

    public function saveSettings(array $settings): bool
    {
        if (!empty($settings['api_key'])) {
            $settings['api_key'] = $this->encryptApiKey($settings['api_key']);
        }

        foreach ($settings as $key => $value) {
            update_option("wpwps_{$key}", $value);
        }

        return true;
    }

    public function getSettings(): array
    {
        $apiKey = get_option('wpwps_api_key', '');
        return [
            'api_key' => $apiKey ? str_repeat('•', 32) : '',
            'api_endpoint' => get_option('wpwps_api_endpoint', 'https://api.printify.com/v1/'),
            'default_shop_id' => get_option('wpwps_default_shop_id', ''),
            'current_time' => '2025-03-15 18:00:42',
            'current_user' => 'ApolloWeb'
        ];
    }
}