<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OpenAI
{
    private Client $client;
    private string $apiKey;
    private int $tokenLimit;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey = get_option('wpwps_openai_key', '');
        $this->tokenLimit = (int)get_option('wpwps_openai_token_limit', 2000);
        $this->temperature = (float)get_option('wpwps_openai_temperature', 0.7);
        
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function generateSupportResponse(string $query): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful customer support assistant for a WooCommerce store using Printify for print-on-demand products.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $query
                        ]
                    ],
                    'max_tokens' => $this->tokenLimit,
                    'temperature' => $this->temperature
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['choices'][0]['message']['content'] ?? null;
        } catch (GuzzleException $e) {
            error_log("OpenAI API Error: " . $e->getMessage());
            return null;
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->client->get('models');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    public function estimateSpendCap(): float
    {
        // Rough estimate based on token limit and average query length
        $averageQueriesPerDay = 100;
        $averageTokensPerQuery = $this->tokenLimit;
        $costPer1kTokens = 0.002; // Current rate for GPT-3.5-turbo
        
        $tokensPerDay = $averageQueriesPerDay * $averageTokensPerQuery;
        $costPerDay = ($tokensPerDay / 1000) * $costPer1kTokens;
        
        return $costPerDay * 30; // Monthly estimate
    }
}