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
     * The option name for the ChatGPT max tokens
     *
     * @var string
     */
    private const CHATGPT_MAX_TOKENS_OPTION = 'wpwps_chatgpt_max_tokens';
    
    /**
     * The option name for the ChatGPT temperature
     *
     * @var string
     */
    private const CHATGPT_TEMPERATURE_OPTION = 'wpwps_chatgpt_temperature';
    
    /**
     * The option name for enabling usage limits
     *
     * @var string
     */
    private const CHATGPT_ENABLE_USAGE_LIMIT_OPTION = 'wpwps_chatgpt_enable_usage_limit';
    
    /**
     * The option name for the monthly usage limit
     *
     * @var string
     */
    private const CHATGPT_MONTHLY_LIMIT_OPTION = 'wpwps_chatgpt_monthly_limit';
    
    /**
     * The option name for tracking current month's usage
     *
     * @var string
     */
    private const CHATGPT_CURRENT_USAGE_OPTION = 'wpwps_chatgpt_current_usage';
    
    /**
     * The option name for tracking the current usage month
     *
     * @var string
     */
    private const CHATGPT_USAGE_MONTH_OPTION = 'wpwps_chatgpt_usage_month';
    
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
     * Get the ChatGPT max tokens
     *
     * @return int
     */
    public function getChatGptMaxTokens(): int
    {
        return (int) get_option(self::CHATGPT_MAX_TOKENS_OPTION, 250);
    }
    
    /**
     * Set the ChatGPT max tokens
     *
     * @param int $maxTokens The max tokens
     * @return bool
     */
    public function setChatGptMaxTokens(int $maxTokens): bool
    {
        return update_option(self::CHATGPT_MAX_TOKENS_OPTION, $maxTokens);
    }
    
    /**
     * Get the ChatGPT temperature
     *
     * @return float
     */
    public function getChatGptTemperature(): float
    {
        return (float) get_option(self::CHATGPT_TEMPERATURE_OPTION, 0.7);
    }
    
    /**
     * Set the ChatGPT temperature
     *
     * @param float $temperature The temperature
     * @return bool
     */
    public function setChatGptTemperature(float $temperature): bool
    {
        return update_option(self::CHATGPT_TEMPERATURE_OPTION, $temperature);
    }
    
    /**
     * Check if ChatGPT usage limit is enabled
     *
     * @return bool
     */
    public function isChatGptUsageLimitEnabled(): bool
    {
        return (bool) get_option(self::CHATGPT_ENABLE_USAGE_LIMIT_OPTION, false);
    }
    
    /**
     * Enable or disable ChatGPT usage limit
     *
     * @param bool $enabled Whether to enable usage limit
     * @return bool
     */
    public function setChatGptUsageLimitEnabled(bool $enabled): bool
    {
        return update_option(self::CHATGPT_ENABLE_USAGE_LIMIT_OPTION, $enabled);
    }
    
    /**
     * Get the monthly ChatGPT usage limit
     *
     * @return float
     */
    public function getChatGptMonthlyLimit(): float
    {
        return (float) get_option(self::CHATGPT_MONTHLY_LIMIT_OPTION, 5.0);
    }
    
    /**
     * Set the monthly ChatGPT usage limit
     *
     * @param float $limit The monthly limit
     * @return bool
     */
    public function setChatGptMonthlyLimit(float $limit): bool
    {
        return update_option(self::CHATGPT_MONTHLY_LIMIT_OPTION, $limit);
    }
    
    /**
     * Get the current month's ChatGPT usage
     *
     * @return float
     */
    public function getChatGptCurrentUsage(): float
    {
        $currentMonth = date('Y-m');
        $usageMonth = get_option(self::CHATGPT_USAGE_MONTH_OPTION, '');
        
        // If it's a new month, reset the usage
        if ($currentMonth !== $usageMonth) {
            update_option(self::CHATGPT_USAGE_MONTH_OPTION, $currentMonth);
            update_option(self::CHATGPT_CURRENT_USAGE_OPTION, 0);
            return 0;
        }
        
        return (float) get_option(self::CHATGPT_CURRENT_USAGE_OPTION, 0);
    }
    
    /**
     * Record ChatGPT usage cost
     *
     * @param float $cost The cost to add
     * @return bool
     */
    public function recordChatGptUsage(float $cost): bool
    {
        $currentUsage = $this->getChatGptCurrentUsage();
        $newUsage = $currentUsage + $cost;
        return update_option(self::CHATGPT_CURRENT_USAGE_OPTION, $newUsage);
    }
    
    /**
     * Check if the usage limit is exceeded
     *
     * @return bool
     */
    public function isChatGptUsageLimitExceeded(): bool
    {
        if (!$this->isChatGptUsageLimitEnabled()) {
            return false;
        }
        
        $currentUsage = $this->getChatGptCurrentUsage();
        $monthlyLimit = $this->getChatGptMonthlyLimit();
        
        return $currentUsage >= $monthlyLimit;
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
