<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use GuzzleHttp\Client;

class ChatGPTAPI {
    private const API_ENDPOINT = 'https://api.openai.com/v1';
    private const MODEL = 'gpt-3.5-turbo';
    private const RATE_LIMIT_KEY = 'wpwps_chatgpt_rate_limit';
    private const RATE_LIMIT_WINDOW = 60; // 1 minute window
    private const MAX_REQUESTS_PER_WINDOW = 20;

    private $client;
    private $settings;

    public function __construct() {
        $this->settings = new Settings();
        $this->initClient();
    }

    private function initClient(): void {
        $this->client = new Client([
            'base_uri' => self::API_ENDPOINT,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings->getChatGPTApiKey(),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function validateApiKey(string $api_key): bool {
        if (!preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $api_key)) {
            throw new \Exception(__('Invalid API key format. Must start with "sk-" followed by at least 32 characters.', 'wp-woocommerce-printify-sync'));
        }

        try {
            $client = new Client([
                'base_uri' => self::API_ENDPOINT,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Make a minimal API call to validate the key
            $response = $client->request('GET', '/v1/models');
            $data = json_decode($response->getBody(), true);

            return !empty($data['data']);
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    __('Failed to validate ChatGPT API key: %s', 'wp-woocommerce-printify-sync'),
                    $this->sanitizeErrorMessage($e->getMessage())
                )
            );
        }
    }

    public function validateSettings(array $settings): void {
        if (!isset($settings['monthly_cap']) || $settings['monthly_cap'] < 1) {
            throw new \Exception(__('Monthly cap must be at least $1', 'wp-woocommerce-printify-sync'));
        }

        if (!isset($settings['tokens']) || $settings['tokens'] < 1 || $settings['tokens'] > 4096) {
            throw new \Exception(__('Tokens must be between 1 and 4096', 'wp-woocommerce-printify-sync'));
        }

        if (!isset($settings['temperature']) || $settings['temperature'] < 0 || $settings['temperature'] > 1) {
            throw new \Exception(__('Temperature must be between 0 and 1', 'wp-woocommerce-printify-sync'));
        }

        // Validate against monthly cap
        $cost_per_1k_tokens = 0.002;
        $monthly_cost = $settings['tokens'] * $cost_per_1k_tokens / 1000;
        $max_possible_requests = floor($settings['monthly_cap'] / $monthly_cost);

        if ($max_possible_requests < 10) {
            throw new \Exception(
                sprintf(
                    __('Current settings would allow only %d requests per month. Please increase monthly cap or decrease tokens.', 'wp-woocommerce-printify-sync'),
                    $max_possible_requests
                )
            );
        }
    }

    public function checkRateLimit(): bool {
        $current_time = time();
        $rate_limit_data = get_transient(self::RATE_LIMIT_KEY) ?: [
            'window_start' => $current_time,
            'requests' => 0,
        ];

        // Reset window if expired
        if ($current_time - $rate_limit_data['window_start'] >= self::RATE_LIMIT_WINDOW) {
            $rate_limit_data = [
                'window_start' => $current_time,
                'requests' => 0,
            ];
        }

        // Check if limit exceeded
        if ($rate_limit_data['requests'] >= self::MAX_REQUESTS_PER_WINDOW) {
            $wait_time = self::RATE_LIMIT_WINDOW - ($current_time - $rate_limit_data['window_start']);
            throw new \Exception(
                sprintf(
                    __('Rate limit exceeded. Please wait %d seconds.', 'wp-woocommerce-printify-sync'),
                    $wait_time
                )
            );
        }

        // Update counter
        $rate_limit_data['requests']++;
        set_transient(self::RATE_LIMIT_KEY, $rate_limit_data, self::RATE_LIMIT_WINDOW);

        return true;
    }

    private function sanitizeErrorMessage(string $message): string {
        // Remove any API keys that might be in the error message
        $message = preg_replace('/sk-[a-zA-Z0-9]{32,}/', '[REDACTED]', $message);
        return $message;
    }
}