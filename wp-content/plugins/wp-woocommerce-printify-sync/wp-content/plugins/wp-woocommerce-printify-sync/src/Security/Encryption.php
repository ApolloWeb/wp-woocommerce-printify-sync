<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Security;

class Encryption
{
    /**
     * Get an encrypted option from database and decrypt it
     *
     * @param string $option_name
     * @param string $default
     * @return string
     */
    public function getEncryptedOption(string $option_name, string $default = ''): string
    {
        $encrypted_value = get_option($option_name, '');
        if (empty($encrypted_value)) {
            return $default;
        }

        return $this->decrypt($encrypted_value);
    }
    
    /**
     * Save an encrypted option to database
     *
     * @param string $option_name
     * @param string $value
     * @return void
     */
    public function saveEncryptedOption(string $option_name, string $value): void
    {
        if (empty($value)) {
            delete_option($option_name);
            return;
        }

        $encrypted_value = $this->encrypt($value);
        update_option($option_name, $encrypted_value);
    }

    /**
     * Encrypt a value
     *
     * @param string $value
     * @return string
     */
    public function encrypt(string $value): string
    {
        if (!function_exists('openssl_encrypt')) {
            // Fallback if OpenSSL is not available
            return base64_encode($value);
        }

        $encryption_key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $encryption_key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt a value
     *
     * @param string $encrypted_value
     * @return string
     */
    public function decrypt(string $encrypted_value): string
    {
        if (!function_exists('openssl_decrypt')) {
            // Fallback if OpenSSL is not available
            return base64_decode($encrypted_value);
        }

        $encryption_key = $this->getEncryptionKey();
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_value), 2);
        
        return openssl_decrypt($encrypted_data, 'AES-256-CBC', $encryption_key, 0, $iv);
    }

    /**
     * Get the encryption key
     *
     * @return string
     */
    private function getEncryptionKey(): string
    {
        // Use WordPress authentication keys as an encryption key
        // This is secure because it's unique to each WordPress installation
        if (defined('AUTH_KEY')) {
            return substr(hash('sha256', AUTH_KEY), 0, 32);
        }
        
        // Fallback if AUTH_KEY is not defined
        return substr(hash('sha256', DB_NAME . DB_USER . DB_PASSWORD), 0, 32);
    }
}
