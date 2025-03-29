<?php
namespace WPWPS\Helpers;

use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;

/**
 * Helper class for encrypting and decrypting sensitive data
 */
class EncryptionHelper {
    /**
     * Key for encryption/decryption
     * 
     * @var string
     */
    private static $encryptionKey;
    
    /**
     * Initialize the encryption key
     * 
     * @return string The encryption key
     */
    private static function getEncryptionKey(): string {
        if (!isset(self::$encryptionKey)) {
            // Use WordPress auth key as encryption key or generate a new one
            $key = defined('AUTH_KEY') ? AUTH_KEY : '';
            
            // If AUTH_KEY isn't defined or is empty, generate a new key and store it
            if (empty($key)) {
                $key = self::generateKey();
                update_option('wpwps_encryption_key', $key);
            } else {
                // If we have AUTH_KEY, check if we already have a stored key
                $storedKey = get_option('wpwps_encryption_key', '');
                if (!empty($storedKey)) {
                    $key = $storedKey;
                } else {
                    // Store the AUTH_KEY derivative as our encryption key
                    update_option('wpwps_encryption_key', $key);
                }
            }
            
            self::$encryptionKey = hash('sha256', $key);
        }
        
        return self::$encryptionKey;
    }
    
    /**
     * Generate a new encryption key
     * 
     * @return string New encryption key
     */
    private static function generateKey(): string {
        return bin2hex(Random::string(32));
    }
    
    /**
     * Encrypt a string
     * 
     * @param string $plaintext The string to encrypt
     * @return string The encrypted string (base64 encoded)
     */
    public static function encrypt(string $plaintext): string {
        if (empty($plaintext)) {
            return '';
        }
        
        try {
            // Create an initialization vector
            $iv = Random::string(16);
            
            // Set up the AES cipher in CBC mode
            $cipher = new AES('cbc');
            $cipher->setKey(self::getEncryptionKey());
            $cipher->setIV($iv);
            
            // Encrypt the data
            $ciphertext = $cipher->encrypt($plaintext);
            
            // Combine IV and ciphertext and base64 encode
            $encrypted = base64_encode($iv . $ciphertext);
            
            return $encrypted;
        } catch (\Exception $e) {
            error_log('Encryption error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Decrypt a string
     * 
     * @param string $encrypted The encrypted string (base64 encoded)
     * @return string The decrypted string
     */
    public static function decrypt(string $encrypted): string {
        if (empty($encrypted)) {
            return '';
        }
        
        try {
            // Decode the base64 string
            $decoded = base64_decode($encrypted);
            
            // Extract the IV (first 16 bytes) and ciphertext
            $iv = substr($decoded, 0, 16);
            $ciphertext = substr($decoded, 16);
            
            // Set up the AES cipher in CBC mode
            $cipher = new AES('cbc');
            $cipher->setKey(self::getEncryptionKey());
            $cipher->setIV($iv);
            
            // Decrypt the data
            $plaintext = $cipher->decrypt($ciphertext);
            
            return $plaintext;
        } catch (\Exception $e) {
            error_log('Decryption error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Check if a string is encrypted
     * 
     * @param string $string The string to check
     * @return bool True if encrypted, false otherwise
     */
    public static function isEncrypted(string $string): bool {
        if (empty($string)) {
            return false;
        }
        
        // Try to decode as base64
        $decoded = base64_decode($string, true);
        if ($decoded === false || strlen($decoded) <= 16) {
            return false;
        }
        
        // Check if the string can be successfully decrypted
        try {
            $iv = substr($decoded, 0, 16);
            $cipher = new AES('cbc');
            $cipher->setKey(self::getEncryptionKey());
            $cipher->setIV($iv);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Safely encrypt or re-encrypt a string
     * 
     * This method checks if the string is already encrypted before encrypting
     * 
     * @param string $value The string to encrypt
     * @return string The encrypted string
     */
    public static function secureEncrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        
        // Don't double-encrypt
        if (self::isEncrypted($value)) {
            return $value;
        }
        
        return self::encrypt($value);
    }
    
    /**
     * Safely decrypt a string
     * 
     * This method checks if the string is actually encrypted before decrypting
     * 
     * @param string $value The string to decrypt
     * @return string The decrypted string
     */
    public static function secureDecrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        
        // Only decrypt if it's encrypted
        if (self::isEncrypted($value)) {
            return self::decrypt($value);
        }
        
        return $value;
    }
}