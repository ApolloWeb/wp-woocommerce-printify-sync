<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ChatGPTClient {
    private $api_key;
    private $model;
    private $logger;
    private $settings;
    private $last_request_time = 0;
    private $rate_limit_delay = 1; // seconds between requests
    
    // Response cache
    private $cache = [];
    private $cache_ttl = 3600; // 1 hour
    
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->settings = get_option('wpwps_chatgpt_settings', []);
        $this->api_key = $this->settings['api_key'] ?? '';
        $this->model = $this->settings['model'] ?? 'gpt-3.5-turbo';
    }
    
    public function analyze($params) {
        $cache_key = md5(json_encode($params));
        
        // Check cache first
        if (isset($this->cache[$cache_key])) {
            $this->logger->debug('Using cached ChatGPT response');
            return $this->cache[$cache_key];
        }
        
        $this->rateLimit();
        
        $request_data = [
            'model' => $this->model,
            'messages' => $params['messages'],
            'temperature' => $params['temperature'] ?? $this->settings['temperature'] ?? 0.7,
            'max_tokens' => $this->settings['max_tokens'] ?? 1000,
        ];
        
        try {
            $response = $this->makeRequest($request_data);
            
            if (isset($response['choices'][0]['message']['content'])) {
                $content = $response['choices'][0]['message']['content'];
                $result = $this->parseResponse($content);
                
                // Cache the response
                $this->cache[$cache_key] = $result;
                
                $this->logUsage($response);
                return $result;
            } else {
                throw new \Exception('Unexpected response format');
            }
        } catch (\Exception $e) {
            $this->logger->error('ChatGPT API error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function makeRequest($data) {
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
            'timeout' => 45
        ];
        
        $response = wp_remote_post(self::API_ENDPOINT, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            $error = $body['error']['message'] ?? 'Unknown error';
            throw new \Exception('API Error: ' . $error);
        }
        
        return $body;
    }
    
    private function parseResponse($content) {
        try {
            // Try to parse as JSON
            $decoded = json_decode($content, true);
            
            // If successfully decoded to array, return it
            if (is_array($decoded)) {
                return $decoded;
            }
            
            // Look for JSON in the response text
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $json_content = $matches[1];
                $decoded = json_decode($json_content, true);
                
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            
            // If can't parse as JSON, return as text
            return ['text' => $content];
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse ChatGPT response: ' . $e->getMessage());
            return ['text' => $content, 'parse_error' => true];
        }
    }
    
    private function rateLimit() {
        $elapsed = microtime(true) - $this->last_request_time;
        
        if ($elapsed < $this->rate_limit_delay) {
            usleep(($this->rate_limit_delay - $elapsed) * 1000000);
        }
        
        $this->last_request_time = microtime(true);
    }
    
    private function logUsage($response) {
        if (isset($response['usage'])) {
            $total_tokens = $response['usage']['total_tokens'] ?? 0;
            $prompt_tokens = $response['usage']['prompt_tokens'] ?? 0;
            $completion_tokens = $response['usage']['completion_tokens'] ?? 0;
            
            $this->logger->debug('ChatGPT API usage', [
                'total_tokens' => $total_tokens,
                'prompt_tokens' => $prompt_tokens,
                'completion_tokens' => $completion_tokens,
            ]);
            
            // Update monthly token usage
            $this->updateTokenUsage($total_tokens);
        }
    }
    
    private function updateTokenUsage($tokens) {
        $current_month = date('Y-m');
        $usage_data = get_option('wpwps_chatgpt_usage', []);
        
        if (!isset($usage_data[$current_month])) {
            $usage_data[$current_month] = 0;
        }
        
        $usage_data[$current_month] += $tokens;
        
        update_option('wpwps_chatgpt_usage', $usage_data);
    }
}
