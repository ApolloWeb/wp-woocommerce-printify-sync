<?php
/**
 * Credential Manager class for secure credential storage
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utility
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utility;

class CredentialManager {
    /**
     * Encryption method
     *
     * @var string
     */
    private $encryptionMethod = 'AES-256-CBC';
    
    /**
     * Get encryption key
     *
     * @return string
     */
    private function getEncryptionKey() {
        // Get authentication key from WordPress
        $auth_key = defined('AUTH_KEY') ? AUTH_KEY : 'default-key';
        return substr(hash('sha256', $auth_key), 0, 32);
    }
    
    /**
     * Encrypt a value
     *
     * @param string $value Value to encrypt
     * @return string|bool Encrypted value or false on failure
     */
    public function encrypt($value) {
        if (empty($value)) {
            return '';
        }
        
        $key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encryptionMethod));
        $encrypted = openssl_encrypt($value, $this->encryptionMethod, $key, 0, $iv);
        
        if ($encrypted === false) {
            return false;
        }
        
        // Store the IV with the encrypted data
        $result = base64_encode($iv . $encrypted);
        return $result;
    }
    
    /**
     * Decrypt a value
     *
     * @param string $encryptedValue Value to decrypt
     * @return string|bool Decrypted value or false on failure
     */
    public function decrypt($encryptedValue) {
        if (empty($encryptedValue)) {
            return '';
        }
        
        $key = $this->getEncryptionKey();
        $data = base64_decode($encryptedValue);
        
        // Extract the IV from the encrypted data
        $ivLength = openssl_cipher_iv_length($this->encryptionMethod);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, $this->encryptionMethod, $key, 0, $iv);
    }
    
    /**
     * Store an encrypted value
     *
     * @param string $optionName Option name
     * @param string $value Value to store
     * @return bool Whether the option was updated successfully
     */
    public function storeEncryptedValue($optionName, $value) {
        $encryptedValue = $this->encrypt($value);
        
        if ($encryptedValue === false) {
            return false;
        }
        
        return update_option($optionName, $encryptedValue);
    }
    
    /**
     * Get a decrypted value
     *
     * @param string $optionName Option name
     * @return string Decrypted value or empty string if not found
     */
    public function getDecryptedValue($optionName) {
        $encryptedValue = get_option($optionName, '');
        
        if (empty($encryptedValue)) {
            return '';
        }
        
        return $this->decrypt($encryptedValue);
    }
    
    /**
     * Mask a value for display (show only last 4 characters)
     *
     * @param string $value Value to mask
     * @return string Masked value
     */
    public function maskValue($value) {
        if (empty($value)) {
            return '';
        }
        
        $length = strlen($value);
        
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        
        return str_repeat('*', $length - 4) . substr($value, -4);
    }
}