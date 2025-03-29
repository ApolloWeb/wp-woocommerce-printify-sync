<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AISupportService
{
    private string $apiKey;
    private int $tokenLimit;
    private float $temperature;
    private float $spendCap;
    
    public function __construct()
    {
        $this->apiKey = get_option('wpwps_openai_key', '');
        $this->tokenLimit = (int)get_option('wpwps_token_limit', 500);
        $this->temperature = (float)get_option('wpwps_temperature', 0.7);
        $this->spendCap = (float)get_option('wpwps_spend_cap', 50.0);
    }
    
    public function generateSupportResponse(string $query): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => __('OpenAI API key is not configured', 'wp-woocommerce-printify-sync')
            ];
        }
        
        try {
            $response = $this->callOpenAI($query);
            
            return [
                'success' => true,
                'response' => $response['choices'][0]['message']['content'] ?? '',
                'tokens' => $response['usage']['total_tokens'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function estimateMonthlyCost(int $averageQueries = 100): array
    {
        $averageTokens = $this->tokenLimit * 1.5; // Estimate input + output tokens
        $costPerToken = 0.000002; // $0.002 per 1K tokens for GPT-3.5-Turbo
        
        $dailyCost = $averageQueries * $averageTokens * $costPerToken;
        $monthlyCost = $dailyCost * 30;
        
        return [
            'daily' => $dailyCost,
            'monthly' => $monthlyCost,
            'within_cap' => ($monthlyCost <= $this->spendCap)
        ];
    }
    
    private function callOpenAI(string $query): array
    {
        $client = new \GuzzleHttp\Client();
        
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant providing support for WooCommerce and Printify integration issues.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $query
                    ]
                ],
                'max_tokens' => $this->tokenLimit,
                'temperature' => $this->temperature,
            ],
        ]);
        
        return json_decode($response->getBody()->getContents(), true);
    }
}