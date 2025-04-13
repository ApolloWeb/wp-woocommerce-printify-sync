<?php
/**
 * Encryption Utility
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utils
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utils;

class Encryption {
    /**
     * Encryption method
     *
     * @var string
     */
    private $cipher = 'aes-256-cbc';
    
    /**
     * Encrypt a string
     *
     * @param string $value String to encrypt
     * @return string Encrypted string
     */
    public function encrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        
        $key = $this->getEncryptionKey();
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt(
            $value,
            $this->cipher,
            $key,
            0,
            $iv
        );
        
        // Prepend IV for use in decryption
        $result = base64_encode($iv . $encrypted);
        
        return $result;
    }
    
    /**
     * Decrypt a string
     *
     * @param string $value Encrypted string
     * @return string Decrypted string
     */
    public function decrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        
        $key = $this->getEncryptionKey();
        $decoded = base64_decode($value);
        
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $key,
            0,
            $iv
        );
        
        return $decrypted;
    }
    
    /**
     * Get or generate an encryption key
     *
     * @return string Encryption key
     */
    private function getEncryptionKey(): string {
        $key = get_option('wpwps_encryption_key', '');
        
        if (empty($key)) {
            $key = wp_generate_password(32, true, true);
            update_option('wpwps_encryption_key', $key);
        }
        
        return $key;
    }
}
