<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class EncryptionService {
    private const CIPHER = 'aes-256-cbc';
    private const SALT = 'wp_woocommerce_printify_sync';

    public function encrypt(string $value): string {
        $key = $this->generateKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));
        
        $encrypted = openssl_encrypt(
            $value,
            self::CIPHER,
            $key,
            0,
            $iv
        );

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $value): string {
        $key = $this->generateKey();
        $decoded = base64_decode($value);
        
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);

        return openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $key,
            0,
            $iv
        );
    }

    private function generateKey(): string {
        return hash('sha256', AUTH_SALT . self::SALT, true);
    }
}
