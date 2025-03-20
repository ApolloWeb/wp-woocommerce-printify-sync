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
     * The option name for the shop name
     *
     * @var string
     */
    private const SHOP_NAME_OPTION = 'wpwps_printify_shop_name';
    
    /**
     * The option name for the ChatGPT API key
     *
     * @var string
     */
    private const CHATGPT_API_KEY_OPTION = 'wpwps_chatgpt_api_key';
    
    /**
     * The option name for the ChatGPT API model
     *
     * @var string
     */
    private const CHATGPT_API_MODEL_OPTION = 'wpwps_chatgpt_api_model';
    
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
     * Get the shop name
     *
     * @return string
     */
    public function getShopName(): string
    {
        return get_option(self::SHOP_NAME_OPTION, '');
    }
    
    /**
     * Set the shop name
     *
     * @param string $shopName The shop name
     * @return bool
     */
    public function setShopName(string $shopName): bool
    {
        return update_option(self::SHOP_NAME_OPTION, $shopName);
    }
    
    /**
     * Get the ChatGPT API key
     *
     * @return string
     */
    public function getChatGptApiKey(): string
    {
        $encryptedKey = get_option(self::CHATGPT_API_KEY_OPTION, '');
        
        if (empty($encryptedKey)) {
            return '';
        }
        
        return $this->decrypt($encryptedKey);
    }
    
    /**
     * Set the ChatGPT API key
     *
     * @param string $apiKey The API key
     * @return bool
     */
    public function setChatGptApiKey(string $apiKey): bool
    {
        $encryptedKey = $this->encrypt($apiKey);
        return update_option(self::CHATGPT_API_KEY_OPTION, $encryptedKey);
    }
    
    /**
     * Get the ChatGPT API model
     *
     * @return string
     */
    public function getChatGptApiModel(): string
    {
        return get_option(self::CHATGPT_API_MODEL_OPTION, 'gpt-3.5-turbo');
    }
    
    /**
     * Set the ChatGPT API model
     *
     * @param string $model The model name
     * @return bool
     */
    public function setChatGptApiModel(string $model): bool
    {
        return update_option(self::CHATGPT_API_MODEL_OPTION, $model);
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
