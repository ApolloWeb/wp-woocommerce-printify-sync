<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Security;

class Encryption {
    private string $key;
    private string $salt;

    public function __construct() 
    {
        if (!defined('SECURE_AUTH_KEY') || !defined('SECURE_AUTH_SALT')) {
            throw new \RuntimeException('WordPress security keys are not properly configured');
        }
        
        $this->key = SECURE_AUTH_KEY;
        $this->salt = SECURE_AUTH_SALT;
    }

    public function encrypt(string $value): string 
    {
        if (empty($value)) {
            return '';
        }

        $method = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        $raw = openssl_encrypt(
            $value,
            $method,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($raw === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $raw);
    }

    public function decrypt(string $value): string 
    {
        if (empty($value)) {
            return '';
        }

        $method = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($method);
        
        $decoded = base64_decode($value);
        $iv = substr($decoded, 0, $ivlen);
        $raw = substr($decoded, $ivlen);

        $decrypted = openssl_decrypt(
            $raw,
            $method,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }
}
