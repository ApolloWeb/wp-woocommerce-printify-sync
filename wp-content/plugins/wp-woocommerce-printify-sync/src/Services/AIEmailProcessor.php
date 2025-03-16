<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AIEmailProcessor
{
    private const CURRENT_TIME = '2025-03-15 22:14:03';
    private const CURRENT_USER = 'ApolloWeb';
    
    private ConfigService $config;
    private LoggerInterface $logger;
    private $openAIClient;

    public function __construct(ConfigService $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->initOpenAI();
    }

    private function initOpenAI(): void
    {
        $apiKey = $this->config->get('openai_api_key');
        $this->openAIClient = new \OpenAI\Client($apiKey);
    }

    public function processEmail(string $subject, string $content, array $metadata = []): array
    {
        try {
            $prompt = $this->buildPrompt($subject, $content);
            $response = $this->getAIResponse($prompt);
            
            $analysis = $this->parseAIResponse($response);
            
            $this->logger->info('Email processed by AI', [
                'category' => $analysis['category'],
                'timestamp' => self::CURRENT_TIME,
                'metadata' => $metadata
            ]);

            return $analysis;

        } catch (\Exception $e) {
            $this->logger->error('AI processing failed', [
                'error' => $e->getMessage(),
                'timestamp' => self::CURRENT_TIME
            ]);

            // Fall back to basic classification
            return $this->fallbackClassification($subject, $content);
        }
    }

    private function buildPrompt(string $subject, string $content): string
    {
        return <<<EOT
Analyze the following email and extract key information. Categorize it and identify any relevant order numbers, product references, or customer concerns.

Subject: {$subject}

Content: {$content}

Please provide a structured analysis including:
1. Primary Category (refund, product inquiry, order inquiry, general inquiry, technical support, complaint)
2. Specific Details:
   - Order Numbers (if any)
   - Product Names/SKUs (if any)
   - Customer Intent/Mood
   - Urgency Level (low, medium, high)
3. Key Information Points
4. Suggested Response Type
5. Required Actions

Format the response as JSON.
EOT;
    }

    private function getAIResponse(string $prompt): array
    {
        $response = $this->openAIClient->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a customer service AI assistant specializing in e-commerce support ticket analysis.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }

    private function parseAIResponse(array $aiResponse): array
    {
        return [
            'category' => $aiResponse['primary_category'] ?? 'general_inquiry',
            'details' => [
                'order_numbers' => $aiResponse['specific_details']['order_numbers'] ?? [],
                'products' => $aiResponse['specific_details']['product_names'] ?? [],
                'customer_mood' => $aiResponse['specific_details']['customer_intent'] ?? 'neutral',
                'urgency' => $aiResponse['specific_details']['urgency_level'] ?? 'medium'
            ],
            'key_points' => $aiResponse['key_information_points'] ?? [],
            'suggested_response' => $aiResponse['suggested_response_type'] ?? 'standard',
            'required_actions' => $aiResponse['required_actions'] ?? [],
            'analysis_timestamp' => self::CURRENT_TIME
        ];
    }

    private function fallbackClassification(string $subject, string $content): array
    {
        // Basic keyword-based classification
        $keywords = [
            'refund' => ['refund', 'money back', 'return'],
            'product_inquiry' => ['product', 'item', 'available', 'stock'],
            'order_inquiry' => ['order', 'shipping', 'delivery', 'tracking'],
            'technical_support' => ['error', 'website', 'login', 'account'],
            'complaint' => ['complaint', 'unhappy', 'disappointed', 'poor']
        ];

        $text = strtolower($subject . ' ' . $content);
        $category = 'general_inquiry';

        foreach ($keywords as $cat => $terms) {
            foreach ($terms as $term) {
                if (strpos($text, $term) !== false) {
                    $category = $cat;
                    break 2;
                }
            }
        }

        // Extract order numbers
        $orderNumbers = [];
        if (preg_match_all('/(?:order|#)[\s:#]*(\d{4,})/i', $text, $matches)) {
            $orderNumbers = $matches[1];
        }

        return [
            'category' => $category,
            'details' => [
                'order_numbers' => $orderNumbers,
                'products' => [],
                'customer_mood' => 'neutral',
                'urgency' => 'medium'
            ],
            'key_points' => [],
            'suggested_response' => 'standard',
            'required_actions' => [],
            'analysis_timestamp' => self::CURRENT_TIME,
            'analysis_method' => 'fallback'
        ];
    }
}