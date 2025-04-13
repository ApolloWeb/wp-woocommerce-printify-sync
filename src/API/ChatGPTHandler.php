<?php
/**
 * ChatGPT API Handler
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

/**
 * Handles all ChatGPT API interactions
 */
class ChatGPTHandler {
    /**
     * API endpoint for OpenAI
     *
     * @var string
     */
    private $endpoint = 'https://api.openai.com/v1';

    /**
     * API Key for ChatGPT
     *
     * @var string
     */
    private $apiKey;

    /**
     * Temperature setting for the model
     *
     * @var float
     */
    private $temperature;

    /**
     * Constructor
     *
     * @param string $apiKey API key
     * @param float $temperature Temperature setting (0-1)
     */
    public function __construct(string $apiKey, float $temperature = 0.7) {
        $this->apiKey = $apiKey;
        $this->temperature = $temperature;
    }

    /**
     * Test connection to ChatGPT API
     *
     * @return array|\WP_Error Response data or error
     */
    public function testConnection() {
        if (empty($this->apiKey)) {
            return new \WP_Error(
                'api_error',
                __('API key is required', 'wp-woocommerce-printify-sync')
            );
        }

        try {
            // Make a small request to test the connection
            $response = $this->makeRequest('/models', 'GET');

            // If we got here, the connection works
            return [
                'estimated_cost' => $this->calculateEstimatedCost(),
                'tokens_per_month' => $this->estimateTokensPerMonth(),
                'available_models' => $this->extractModelNames($response),
            ];
        } catch (\Exception $e) {
            return new \WP_Error('api_error', $e->getMessage());
        }
    }

    /**
     * Make an API request to ChatGPT
     *
     * @param string $endpoint Endpoint path
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array Response data
     * @throws \Exception On request error
     */
    private function makeRequest(string $endpoint, string $method = 'GET', array $data = []): array {
        $url = $this->endpoint . $endpoint;

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
            'method' => $method,
        ];

        if (!empty($data) && $method !== 'GET') {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            throw new \Exception(
                sprintf(
                    __('API returned error code: %d', 'wp-woocommerce-printify-sync'),
                    $code
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(
                __('Invalid JSON response from API', 'wp-woocommerce-printify-sync')
            );
        }

        return $data;
    }

    /**
     * Calculate estimated cost based on temperature and usage
     *
     * @return string Estimated cost
     */
    private function calculateEstimatedCost(): string {
        // Higher temperature generally means more token usage
        $baseCost = 5.00;
        $tempFactor = $this->temperature * 1.5;
        
        return number_format($baseCost * $tempFactor, 2);
    }

    /**
     * Estimate tokens per month based on temperature
     *
     * @return string Estimated tokens
     */
    private function estimateTokensPerMonth(): string {
        // Base token amount with a temperature multiplier
        $baseTokens = 100000;
        $tempFactor = 1 + ($this->temperature * 0.5);
        
        return number_format($baseTokens * $tempFactor, 0);
    }

    /**
     * Extract available model names from API response
     * 
     * @param array $response API response
     * @return array Model names
     */
    private function extractModelNames(array $response): array {
        $models = [];
        
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $model) {
                if (isset($model['id'])) {
                    $models[] = $model['id'];
                }
            }
        }
        
        // Return a subset of models if there are many
        return array_slice($models, 0, 5);
    }
}
