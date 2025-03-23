<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class Encryption {
    private $key;
    private $method = 'aes-256-cbc';

    public function __construct() {
        $this->key = $this->getKey();
    }

    public function encrypt(string $value): string {
        if (empty($value)) return '';
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $value,
            $this->method,
            $this->key,
            0,
            $iv
        );

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $value): string {
        if (empty($value)) return '';
        
        $decoded = base64_decode($value);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);

        return openssl_decrypt(
            $encrypted,
            $this->method,
            $this->key,
            0,
            $iv
        );
    }

    private function getKey(): string {
        $key = get_option('wpps_encryption_key');
        if (!$key) {
            $key = wp_generate_password(32, true, true);
            update_option('wpps_encryption_key', $key);
        }
        return $key;
    }
}
