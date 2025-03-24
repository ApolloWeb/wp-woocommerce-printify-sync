<?php
/**
 * OpenAI Service for AI-powered features
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Class OpenAIService
 */
class OpenAIService
{
    /**
     * API endpoint
     *
     * @var string
     */
    private string $api_endpoint = 'https://api.openai.com/v1/';

    /**
     * API key
     *
     * @var string
     */
    private string $api_key;

    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Model to use
     *
     * @var string
     */
    private string $model;

    /**
     * Temperature (randomness)
     *
     * @var float
     */
    private float $temperature;

    /**
     * Monthly token cap
     *
     * @var int
     */
    private int $monthly_token_cap;

    /**
     * Constructor
     *
     * @param LoggerService $logger Logger service.
     */
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        
        // Get API key (decrypt if needed)
        $api_service = new ApiService($logger);
        $encrypted_key = get_option('wpwps_openai_api_key', '');
        $this->api_key = !empty($encrypted_key) ? $api_service->decrypt($encrypted_key) : '';
        
        // Get other settings
        $this->model = get_option('wpwps_openai_model', 'gpt-3.5-turbo');
        $this->temperature = (float) get_option('wpwps_openai_temperature', 0.7);
        $this->monthly_token_cap = (int) get_option('wpwps_openai_monthly_token_cap', 100000);
    }

    /**
     * Test the API connection
     *
     * @return array Success message and data or error
     */
    public function testConnection(): array
    {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => __('OpenAI API key is not set', 'wp-woocommerce-printify-sync'),
            ];
        }

        $prompt = "Hello! Please respond with a simple 'Hello, I am working correctly.' to confirm API connectivity.";
        $response = $this->makeRequest('chat/completions', [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => 50,
        ]);

        if (isset($response['error'])) {
            $this->logger->error('OpenAI API connection failed', [
                'error' => $response['error'],
            ]);
            
            return [
                'success' => false,
                'message' => __('Failed to connect to OpenAI API: ', 'wp-woocommerce-printify-sync') . ($response['error']['message'] ?? 'Unknown error'),
            ];
        }

        // Get usage stats for current month
        $usage = $this->getMonthlyTokenUsage();
        $current_month = date('Y-m');
        $current_month_usage = $usage[$current_month] ?? 0;
        
        return [
            'success' => true,
            'message' => __('Successfully connected to OpenAI API', 'wp-woocommerce-printify-sync'),
            'data' => [
                'response' => $response['choices'][0]['message']['content'] ?? '',
                'model' => $this->model,
                'monthly_cap' => $this->monthly_token_cap,
                'current_month_usage' => $current_month_usage,
                'estimated_cost' => $this->calculateEstimatedCost($current_month_usage),
            ],
        ];
    }

    /**
     * Generate completion using the API
     *
     * @param string $prompt Text prompt for completion.
     * @param array  $options Additional options.
     * @return string Completion text or empty on error
     */
    public function generateCompletion(string $prompt, array $options = []): string
    {
        // Check if API key is set
        if (empty($this->api_key)) {
            $this->logger->error('OpenAI API key is not set');
            return '';
        }
        
        // Check if we're over the monthly token cap
        $usage = $this->getMonthlyTokenUsage();
        $current_month = date('Y-m');
        $current_month_usage = $usage[$current_month] ?? 0;
        
        if ($current_month_usage >= $this->monthly_token_cap) {
            $this->logger->error('Monthly token cap reached', [
                'usage' => $current_month_usage,
                'cap' => $this->monthly_token_cap,
            ]);
            return '';
        }
        
        // Default options
        $default_options = [
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => 500,
        ];
        
        // Merge options
        $options = array_merge($default_options, $options);
        
        // Prepare request data
        $data = [
            'model' => $options['model'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $options['temperature'],
            'max_tokens' => $options['max_tokens'],
        ];
        
        // Make request
        $response = $this->makeRequest('chat/completions', $data);
        
        // Check for error
        if (isset($response['error'])) {
            $this->logger->error('OpenAI API request failed', [
                'error' => $response['error'],
            ]);
            return '';
        }
        
        // Return completion
        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Make a request to the OpenAI API
     *
     * @param string $endpoint API endpoint.
     * @param array  $data Request data.
     * @return array Response data
     */
    private function makeRequest(string $endpoint, array $data): array
    {
        $url = $this->api_endpoint . $endpoint;
        
        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
            'timeout' => 30,
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'error' => [
                    'message' => $response->get_error_message(),
                ],
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code < 200 || $code >= 300) {
            return [
                'error' => [
                    'code' => $code,
                    'message' => $body,
                ],
            ];
        }
        
        return json_decode($body, true);
    }

    /**
     * Get monthly token usage
     *
     * @return array Monthly token usage
     */
    public function getMonthlyTokenUsage(): array
    {
        $usage = get_option('wpwps_openai_token_usage', []);
        
        if (!is_array($usage)) {
            $usage = [];
        }
        
        return $usage;
    }

    /**
     * Calculate estimated cost based on token usage
     *
     * @param int $tokens Number of tokens.
     * @return float Estimated cost in USD
     */
    public function calculateEstimatedCost(int $tokens): float
    {
        // Cost per 1000 tokens (approximate, depends on model)
        $rate = $this->model === 'gpt-4' ? 0.03 : 0.002;
        
        return $tokens / 1000 * $rate;
    }

    /**
     * Track token usage
     *
     * @param int $tokens Number of tokens used.
     * @return void
     */
    public function trackTokenUsage(int $tokens): void
    {
        $usage = $this->getMonthlyTokenUsage();
        $current_month = date('Y-m');
        
        if (!isset($usage[$current_month])) {
            $usage[$current_month] = 0;
        }
        
        $usage[$current_month] += $tokens;
        
        update_option('wpwps_openai_token_usage', $usage);
    }
}
