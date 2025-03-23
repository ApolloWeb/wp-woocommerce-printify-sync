<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Handles plugin settings
 */
class Settings {
    /**
     * Get a setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value
     */
    public function get(string $key, $default = null) {
        $value = get_option("wpwps_{$key}", $default);
        
        // Decrypt sensitive data if needed
        if (in_array($key, ['printify_api_key', 'chatgpt_api_key'])) {
            $value = $this->maybeDecrypt($value);
        }
        
        return $value;
    }
    
    /**
     * Set a setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success
     */
    public function set(string $key, $value): bool {
        // Encrypt sensitive data
        if (in_array($key, ['printify_api_key', 'chatgpt_api_key'])) {
            $value = $this->maybeEncrypt($value);
        }
        
        return update_option("wpwps_{$key}", $value);
    }
    
    /**
     * Check if a setting exists
     *
     * @param string $key Setting key
     * @return bool Whether setting exists
     */
    public function exists(string $key): bool {
        return get_option("wpwps_{$key}") !== false;
    }
    
    /**
     * Delete a setting
     *
     * @param string $key Setting key
     * @return bool Success
     */
    public function delete(string $key): bool {
        return delete_option("wpwps_{$key}");
    }
    
    /**
     * Maybe encrypt a value
     *
     * @param string $value Value to encrypt
     * @return string Encrypted value
     */
    private function maybeEncrypt(string $value): string {
        // Simple encryption - in a real plugin you'd want better encryption
        if (empty($value)) {
            return '';
        }
        
        return base64_encode($value);
    }
    
    /**
     * Maybe decrypt a value
     *
     * @param string $value Value to decrypt
     * @return string Decrypted value
     */
    private function maybeDecrypt(string $value): string {
        // Simple decryption - in a real plugin you'd want better decryption
        if (empty($value)) {
            return '';
        }
        
        return base64_decode($value);
    }
}
