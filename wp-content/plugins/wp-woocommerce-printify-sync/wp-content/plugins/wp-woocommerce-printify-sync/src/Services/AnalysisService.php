<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\OpenAiApi;

class AnalysisService 
{
    private OpenAiApi $openAiApi;
    
    public function __construct(OpenAiApi $openAiApi) 
    {
        $this->openAiApi = $openAiApi;
    }
    
    public function analyzeTicket(string $subject, string $body): array
    {
        $prompt = $this->buildPrompt($subject, $body);
        $response = $this->openAiApi->generateText($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 500,
        ]);
        
        if (is_wp_error($response)) {
            return [
                'category' => 'general',
                'status' => 'new',
                'order_id' => 0,
                'urgency' => 'medium',
            ];
        }
        
        $analysis = json_decode($response, true);
        
        if (!$analysis || !is_array($analysis)) {
            return [
                'category' => 'general',
                'status' => 'new',
                'order_id' => 0,
                'urgency' => 'medium',
            ];
        }
        
        return [
            'category' => $analysis['category'] ?? 'general',
            'status' => $analysis['status'] ?? 'new',
            'order_id' => intval($analysis['order_id'] ?? 0),
            'urgency' => $analysis['urgency'] ?? 'medium',
        ];
    }
    
    private function buildPrompt(string $subject, string $body): string
    {
        return "Analyze the following customer support email and extract the following information:\n\n"
            . "1. Category (choose from: general, refund, reprint, shipping, product)\n"
            . "2. Appropriate status (choose from: new, awaiting-customer-evidence)\n"
            . "3. Order ID (if mentioned, format: digits only)\n"
            . "4. Urgency level (low, medium, high)\n\n"
            . "Email subject: " . $subject . "\n\n"
            . "Email body:\n" . $body . "\n\n"
            . "Return only a JSON object with the extracted information.";
    }
}
