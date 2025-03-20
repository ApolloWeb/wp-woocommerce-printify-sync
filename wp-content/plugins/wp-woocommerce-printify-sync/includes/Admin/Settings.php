<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Settings
{
    /**
     * The option name for the API key
     *
     * @var string
     */
    private const API_KEY_OPTION = 'wpwps_printify_api_key';
    
    /**
     * The option name for the API endpoint
     *
     * @var string
     */
    private const API_ENDPOINT_OPTION = 'wpwps_printify_api_endpoint';
    
    /**
     * The option name for the shop ID
     *
     * @var string
     */
    private const SHOP_ID_OPTION = 'wpwps_printify_shop_id';
    
    /**
     * Get the API key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        $encryptedKey = get_option(self::API_KEY_OPTION, '');
        
        if (empty($encryptedKey)) {
            return '';
        }
        
        return $this->decrypt($encryptedKey);
    }
    
    /**
     * Set the API key
     *
     * @param string $apiKey The API key
     * @return bool
     */
    public function setApiKey(string $apiKey): bool
    {
        $encryptedKey = $this->encrypt($apiKey);
        return update_option(self::API_KEY_OPTION, $encryptedKey);
    }
    
    /**
     * Get the API endpoint
     *
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return get_option(self::API_ENDPOINT_OPTION, 'https://api.printify.com/v1/');
    }
    
    /**
     * Set the API endpoint
     *
     * @param string $endpoint The API endpoint
     * @return bool
     */
    public function setApiEndpoint(string $endpoint): bool
    {
        return update_option(self::API_ENDPOINT_OPTION, $endpoint);
    }
    
    /**
     * Get the shop ID
     *
     * @return string
     */
    public function getShopId(): string
    {
        return get_option(self::SHOP_ID_OPTION, '');
    }
    
    /**
     * Set the shop ID
     *
     * @param string $shopId The shop ID
     * @return bool
     */
    public function setShopId(string $shopId): bool
    {
        return update_option(self::SHOP_ID_OPTION, $shopId);
    }
    
    /**
     * Encrypt a string using WordPress salt
     *
     * @param string $data The data to encrypt
     * @return string
     */
    private function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $salt = wp_salt('auth');
        $method = 'aes-256-ctr';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        
        $encrypted = openssl_encrypt($data, $method, $salt, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt a string using WordPress salt
     *
     * @param string $data The data to decrypt
     * @return string
     */
    private function decrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $salt = wp_salt('auth');
        $method = 'aes-256-ctr';
        
        $parts = explode('::', base64_decode($data), 2);
        
        // Check if we have both parts (encrypted data and IV)
        if (count($parts) !== 2) {
            return '';
        }
        
        list($encrypted_data, $iv) = $parts;
        
        // Ensure IV is not null
        if (empty($iv)) {
            return '';
        }
        
        return openssl_decrypt($encrypted_data, $method, $salt, 0, $iv);
    }
}
