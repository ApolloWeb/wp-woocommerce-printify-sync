<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Settings handler for the plugin
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
        
        // Decrypt sensitive values if needed
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
        // Encrypt sensitive values
        if (in_array($key, ['printify_api_key', 'chatgpt_api_key'])) {
            $value = $this->maybeEncrypt($value);
        }
        
        return update_option("wpwps_{$key}", $value);
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
     * Maybe encrypt a value if it's not already encrypted
     *
     * @param string $value Value to encrypt
     * @return string Encrypted value
     */
    private function maybeEncrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        
        // Basic encryption - for production, consider using a more secure method
        return base64_encode($value);
    }
    
    /**
     * Maybe decrypt a value if it's encrypted
     *
     * @param string $value Value to decrypt
     * @return string Decrypted value
     */
    private function maybeDecrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        
        // Basic decryption - for production, consider using a more secure method
        return base64_decode($value);
    }
}
