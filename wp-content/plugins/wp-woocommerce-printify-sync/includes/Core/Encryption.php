<?php
/**
 * Encryption utility class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class Encryption
 */
class Encryption {
    /**
     * Encryption method
     *
     * @var string
     */
    private $method = 'aes-256-cbc';
    
    /**
     * Get encryption key
     *
     * @return string Encryption key
     */
    private function getKey() {
        $key = get_option('wpwps_encryption_key');
        
        if (!$key) {
            $key = wp_generate_password(32, true, true);
            update_option('wpwps_encryption_key', $key);
        }
        
        return hash('sha256', $key);
    }
    
    /**
     * Encrypt data
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data.
     */
    public function encrypt($data) {
        $key = $this->getKey();
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        $encrypted = openssl_encrypt($data, $this->method, $key, 0, $iv);
        
        if ($encrypted === false) {
            return '';
        }
        
        // Return IV + encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     *
     * @param string $data Data to decrypt.
     * @return string|false Decrypted data or false on failure.
     */
    public function decrypt($data) {
        $key = $this->getKey();
        $data = base64_decode($data);
        
        if ($data === false) {
            return false;
        }
        
        $ivlen = openssl_cipher_iv_length($this->method);
        
        if (strlen($data) <= $ivlen) {
            return false;
        }
        
        $iv = substr($data, 0, $ivlen);
        $encrypted = substr($data, $ivlen);
        
        return openssl_decrypt($encrypted, $this->method, $key, 0, $iv);
    }
}
