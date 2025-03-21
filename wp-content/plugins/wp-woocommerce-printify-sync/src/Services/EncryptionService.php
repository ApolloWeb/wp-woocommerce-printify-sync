<?php
/**
 * Encryption Service for handling sensitive data.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Encryption service for handling sensitive data.
 */
class EncryptionService
{
    /**
     * Encryption method.
     *
     * @var string
     */
    private $method = 'aes-256-cbc';
    
    /**
     * The encryption/decryption key.
     *
     * @var string
     */
    private $key;
    
    /**
     * Constructor.
     */
    public function __construct()
    {
        // Get or generate the encryption key
        $this->key = $this->getEncryptionKey();
    }
    
    /**
     * Get the encryption key.
     *
     * @return string The encryption key.
     */
    private function getEncryptionKey()
    {
        $key = get_option('wpwps_encryption_key');
        
        if (!$key) {
            // Generate a new key if not exists
            $key = wp_generate_password(32, true, true);
            update_option('wpwps_encryption_key', $key);
        }
        
        return $key;
    }
    
    /**
     * Encrypt data.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data.
     */
    public function encrypt($data)
    {
        if (empty($data)) {
            return '';
        }
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        $encrypted = openssl_encrypt($data, $this->method, $this->key, 0, $iv);
        
        if ($encrypted === false) {
            return '';
        }
        
        // Combine IV and encrypted data
        $combined = base64_encode($iv . $encrypted);
        
        return $combined;
    }
    
    /**
     * Decrypt data.
     *
     * @param string $data Encrypted data.
     * @return string Decrypted data.
     */
    public function decrypt($data)
    {
        if (empty($data)) {
            return '';
        }
        
        $combined = base64_decode($data);
        
        if ($combined === false) {
            return '';
        }
        
        $iv_length = openssl_cipher_iv_length($this->method);
        $iv = substr($combined, 0, $iv_length);
        $encrypted = substr($combined, $iv_length);
        
        $decrypted = openssl_decrypt($encrypted, $this->method, $this->key, 0, $iv);
        
        if ($decrypted === false) {
            return '';
        }
        
        return $decrypted;
    }
    
    /**
     * Securely store an API key.
     *
     * @param string $key_name The option name.
     * @param string $key_value The API key value.
     * @return bool Whether the key was saved successfully.
     */
    public function storeKey($key_name, $key_value)
    {
        if (empty($key_value)) {
            return false;
        }
        
        $encrypted_key = $this->encrypt($key_value);
        
        if (empty($encrypted_key)) {
            return false;
        }
        
        return update_option($key_name, $encrypted_key);
    }
    
    /**
     * Retrieve a stored API key.
     *
     * @param string $key_name The option name.
     * @return string The decrypted API key or empty string if not found/decryption failed.
     */
    public function getKey($key_name)
    {
        $encrypted_key = get_option($key_name, '');
        
        if (empty($encrypted_key)) {
            return '';
        }
        
        return $this->decrypt($encrypted_key);
    }
}
