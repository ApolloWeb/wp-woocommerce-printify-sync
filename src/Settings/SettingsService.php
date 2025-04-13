<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Settings;

/**
 * Settings Service
 */
class SettingsService implements SettingsServiceInterface {
    /**
     * Option group prefix
     * 
     * @var string
     */
    private $optionPrefix = 'wpwps_';
    
    /**
     * Test Printify API connection
     * 
     * @param string $api_key
     * @param string $api_endpoint
     * @return array|\WP_Error
     */
    public function testPrintifyConnection($api_key, $api_endpoint) {
        // Ensure endpoint has trailing slash
        $api_endpoint = trailingslashit($api_endpoint);
        
        // Prepare request
        $response = wp_remote_get($api_endpoint . 'shops.json', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $message = isset($data['message']) ? $data['message'] : __('Unknown error', 'wp-woocommerce-printify-sync');
            return new \WP_Error('api_error', sprintf(__('API Error: %s', 'wp-woocommerce-printify-sync'), $message));
        }
        
        return $data;
    }
    
    /**
     * Save Printify settings
     * 
     * @param string $api_key
     * @param string $api_endpoint
     * @param string $shop_id
     * @return bool|\WP_Error
     */
    public function savePrintifySettings($api_key, $api_endpoint, $shop_id) {
        // Encrypt API key
        $encrypted_key = $this->encryptString($api_key);
        
        if (is_wp_error($encrypted_key)) {
            return $encrypted_key;
        }
        
        // Save settings
        update_option($this->optionPrefix . 'printify_api_key', $encrypted_key);
        update_option($this->optionPrefix . 'printify_api_endpoint', $api_endpoint);
        
        if (!empty($shop_id)) {
            update_option($this->optionPrefix . 'printify_shop_id', $shop_id);
        }
        
        return true;
    }
    
    /**
     * Get Printify settings
     * 
     * @return array
     */
    public function getPrintifySettings() {
        $encrypted_key = get_option($this->optionPrefix . 'printify_api_key', '');
        $api_key = '';
        
        if (!empty($encrypted_key)) {
            $api_key = $this->decryptString($encrypted_key);
            
            if (is_wp_error($api_key)) {
                $api_key = '';
            }
        }
        
        return [
            'api_key' => $api_key,
            'api_endpoint' => get_option($this->optionPrefix . 'printify_api_endpoint', 'https://api.printify.com/v1/'),
            'shop_id' => get_option($this->optionPrefix . 'printify_shop_id', '')
        ];
    }
    
    /**
     * Test ChatGPT API connection
     * 
     * @param string $api_key
     * @return bool|\WP_Error
     */
    public function testChatGptConnection($api_key) {
        // Prepare request
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Say "Connection test successful"'
                    ]
                ],
                'max_tokens' => 10
            ]),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $message = isset($data['error']['message']) ? $data['error']['message'] : __('Unknown error', 'wp-woocommerce-printify-sync');
            return new \WP_Error('api_error', sprintf(__('API Error: %s', 'wp-woocommerce-printify-sync'), $message));
        }
        
        return true;
    }
    
    /**
     * Save ChatGPT settings
     * 
     * @param string $api_key
     * @param int $monthly_cap
     * @param int $token_limit
     * @param float $temperature
     * @return bool|\WP_Error
     */
    public function saveChatGptSettings($api_key, $monthly_cap, $token_limit, $temperature) {
        // Encrypt API key
        $encrypted_key = $this->encryptString($api_key);
        
        if (is_wp_error($encrypted_key)) {
            return $encrypted_key;
        }
        
        // Save settings
        update_option($this->optionPrefix . 'chatgpt_api_key', $encrypted_key);
        update_option($this->optionPrefix . 'chatgpt_monthly_cap', $monthly_cap);
        update_option($this->optionPrefix . 'chatgpt_token_limit', $token_limit);
        update_option($this->optionPrefix . 'chatgpt_temperature', $temperature);
        
        return true;
    }
    
    /**
     * Get ChatGPT settings
     * 
     * @return array
     */
    public function getChatGptSettings() {
        $encrypted_key = get_option($this->optionPrefix . 'chatgpt_api_key', '');
        $api_key = '';
        
        if (!empty($encrypted_key)) {
            $api_key = $this->decryptString($encrypted_key);
            
            if (is_wp_error($api_key)) {
                $api_key = '';
            }
        }
        
        return [
            'api_key' => $api_key,
            'monthly_cap' => get_option($this->optionPrefix . 'chatgpt_monthly_cap', 0),
            'token_limit' => get_option($this->optionPrefix . 'chatgpt_token_limit', 0),
            'temperature' => get_option($this->optionPrefix . 'chatgpt_temperature', 0.7)
        ];
    }
    
    /**
     * Estimate ChatGPT cost
     * 
     * @param int $token_limit
     * @param int $estimated_tickets
     * @return float
     */
    public function estimateChatGptCost($token_limit, $estimated_tickets) {
        // GPT-3.5-Turbo costs approximately $0.002 per 1K tokens
        $cost_per_1k_tokens = 0.002;
        
        // Calculate total tokens per month
        $total_tokens = $token_limit * $estimated_tickets;
        
        // Calculate cost
        $cost = ($total_tokens / 1000) * $cost_per_1k_tokens;
        
        return round($cost, 2);
    }
    
    /**
     * Encrypt a string
     * 
     * @param string $string
     * @return string|\WP_Error
     */
    private function encryptString($string) {
        if (!function_exists('openssl_encrypt')) {
            return new \WP_Error('encryption_error', __('OpenSSL not available.', 'wp-woocommerce-printify-sync'));
        }
        
        // Get or generate encryption key
        $encryption_key = $this->getEncryptionKey();
        
        // Generate an initialization vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        
        // Encrypt the data
        $encrypted = openssl_encrypt($string, 'aes-256-cbc', $encryption_key, 0, $iv);
        
        if ($encrypted === false) {
            return new \WP_Error('encryption_error', __('Could not encrypt the data.', 'wp-woocommerce-printify-sync'));
        }
        
        // Combine the IV and encrypted data
        $encrypted_with_iv = base64_encode($iv . $encrypted);
        
        return $encrypted_with_iv;
    }
    
    /**
     * Decrypt a string
     * 
     * @param string $encrypted_string
     * @return string|\WP_Error
     */
    private function decryptString($encrypted_string) {
        if (!function_exists('openssl_decrypt')) {
            return new \WP_Error('decryption_error', __('OpenSSL not available.', 'wp-woocommerce-printify-sync'));
        }
        
        // Get encryption key
        $encryption_key = $this->getEncryptionKey();
        
        // Decode the encrypted data
        $decoded = base64_decode($encrypted_string);
        
        if ($decoded === false) {
            return new \WP_Error('decryption_error', __('Could not decode the encrypted data.', 'wp-woocommerce-printify-sync'));
        }
        
        // Extract the initialization vector and encrypted data
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($decoded, 0, $iv_length);
        $encrypted = substr($decoded, $iv_length);
        
        // Decrypt the data
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $encryption_key, 0, $iv);
        
        if ($decrypted === false) {
            return new \WP_Error('decryption_error', __('Could not decrypt the data.', 'wp-woocommerce-printify-sync'));
        }
        
        return $decrypted;
    }
    
    /**
     * Get or generate encryption key
     * 
     * @return string
     */
    private function getEncryptionKey() {
        $key = get_option($this->optionPrefix . 'encryption_key', '');
        
        if (empty($key)) {
            // Generate a new key
            $key = bin2hex(openssl_random_pseudo_bytes(32));
            update_option($this->optionPrefix . 'encryption_key', $key);
        }
        
        return $key;
    }
}
