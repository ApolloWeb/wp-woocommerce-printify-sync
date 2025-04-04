<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AiSupport {
    private $api_key;
    private $model;
    
    public function __construct() 
    {
        $this->api_key = get_option('wpwps_ai_api_key');
        $this->model = get_option('wpwps_ai_model', 'gpt-3.5-turbo');
    }

    public function generateResponse(string $query): string 
    {
        if (empty($this->api_key)) {
            return 'AI support is not configured. Please set up your API key.';
        }

        try {
            // Basic response for now - implement actual AI API integration later
            return $this->getDefaultResponse($query);
        } catch (\Exception $e) {
            return 'Error generating AI response: ' . $e->getMessage();
        }
    }

    public function isConfigured(): bool 
    {
        return !empty($this->api_key);
    }

    private function getDefaultResponse(string $query): string 
    {
        $responses = [
            'product' => 'Our products are synchronized automatically with Printify.',
            'sync' => 'Synchronization occurs hourly by default.',
            'error' => 'Please check your API settings and logs for details.',
            'default' => 'How can I help you with WooCommerce Printify integration?'
        ];

        foreach ($responses as $key => $response) {
            if (stripos($query, $key) !== false) {
                return $response;
            }
        }

        return $responses['default'];
    }

    public function analyzeTicket(array $ticket): array 
    {
        return [
            'priority' => $this->calculatePriority($ticket),
            'category' => $this->determineCategory($ticket),
            'suggested_response' => $this->generateResponse($ticket['description'])
        ];
    }

    private function calculatePriority(array $ticket): string 
    {
        // Basic priority calculation
        return 'medium';
    }

    private function determineCategory(array $ticket): string 
    {
        // Basic category determination
        return 'general';
    }
}
