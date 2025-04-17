<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

/**
 * ChatGPT API Implementation
 */
class ChatGPTApi {
    /**
     * API key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Model
     *
     * @var string
     */
    private $model;
    
    /**
     * Temperature
     *
     * @var float
     */
    private $temperature;
    
    /**
     * Max tokens
     *
     * @var int
     */
    private $max_tokens;
    
    /**
     * Constructor
     */
    public function __construct() {
        $settings = new Settings();
        $this->api_key = $settings->get_openai_api_key();
        
        $options = get_option('wpwps_options', []);
        $this->model = isset($options['chatgpt_model']) ? $options['chatgpt_model'] : 'gpt-3.5-turbo';
        $this->temperature = isset($options['chatgpt_temperature']) ? floatval($options['chatgpt_temperature']) : 0.7;
        $this->max_tokens = isset($options['chatgpt_token_limit']) ? intval($options['chatgpt_token_limit']) : 1000;
    }
    
    /**
     * Test API connection
     *
     * @return array|WP_Error
     */
    public function testConnection() {
        if (empty($this->api_key)) {
            return new \WP_Error('api_key_missing', __('API key is not configured', 'wp-woocommerce-printify-sync'));
        }
        
        // Simple test query to verify API connection
        $result = $this->query([
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant. Respond with a simple confirmation message.'
            ],
            [
                'role' => 'user',
                'content' => 'Test connection'
            ]
        ]);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Calculate estimated cost
        $options = get_option('wpwps_options', []);
        $monthly_cap = isset($options['chatgpt_monthly_cap']) ? floatval($options['chatgpt_monthly_cap']) : 10;
        $token_limit = $this->max_tokens;
        
        // Estimate based on GPT-3.5-Turbo pricing ($0.002/1K tokens)
        $cost_per_token = ($this->model === 'gpt-3.5-turbo') ? 0.002 : 0.03; // GPT-4 is more expensive
        $cost_per_1000 = $cost_per_token;
        
        // Estimate 30 requests per day with max tokens
        $daily_tokens = 30 * ($token_limit * 1.5); // 1.5x to account for response tokens
        $monthly_tokens = $daily_tokens * 30;
        $estimated_cost = ($monthly_tokens / 1000) * $cost_per_1000;
        
        return [
            'success' => true,
            'model' => $this->model,
            'message' => isset($result['choices'][0]['message']['content']) ? $result['choices'][0]['message']['content'] : 'Connection successful',
            'estimated_monthly_tokens' => number_format($monthly_tokens),
            'estimated_monthly_cost' => '$' . number_format($estimated_cost, 2),
            'within_budget' => $estimated_cost <= $monthly_cap || $monthly_cap === 0,
        ];
    }
    
    /**
     * Query the ChatGPT API
     *
     * @param array $messages Array of messages with role and content
     * @return array|WP_Error
     */
    public function query($messages) {
        if (empty($this->api_key)) {
            return new \WP_Error('api_key_missing', __('API key is not configured', 'wp-woocommerce-printify-sync'));
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
            'body' => json_encode([
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $this->temperature,
                'max_tokens' => $this->max_tokens,
            ]),
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($code < 200 || $code >= 300) {
            return new \WP_Error(
                'chatgpt_api_error',
                isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error',
                [
                    'status' => $code,
                    'response' => $data
                ]
            );
        }
        
        return $data;
    }
}
