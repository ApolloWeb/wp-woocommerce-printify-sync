<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\AI;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

class AIService
{
    use LoggerAwareTrait;

    private AppContext $context;
    private string $currentTime = '2025-03-15 20:22:58';
    private string $currentUser = 'ApolloWeb';
    private string $apiKey;
    private string $model = 'gpt-4'; // Using GPT-4 for better accuracy

    private const TICKET_CATEGORIES = [
        'REFUND_REQUEST',
        'REPRINT_REQUEST',
        'SHIPPING_QUERY',
        'QUALITY_ISSUE',
        'ORDER_STATUS',
        'DESIGN_HELP',
        'GENERAL_INQUIRY',
        'COMPLAINT',
        'TECHNICAL_ISSUE',
        'SIZE_EXCHANGE'
    ];

    public function __construct()
    {
        $this->context = AppContext::getInstance();
        $this->apiKey = get_option('wpwps_openai_api_key');
    }

    public function analyzeEmail(array $email): array
    {
        try {
            $combinedContent = $this->prepareEmailContent($email);
            
            $response = $this->makeAIRequest([
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $combinedContent
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 500
            ]);

            $analysis = $this->parseAIResponse($response);
            
            $this->log('info', 'Email analyzed successfully', [
                'category' => $analysis['category'],
                'priority' => $analysis['priority'],
                'has_order' => !empty($analysis['order_details'])
            ]);

            return $analysis;

        } catch (\Exception $e) {
            $this->log('error', 'AI analysis failed', [
                'error' => $e->getMessage(),
                'email_subject' => $email['subject']
            ]);

            // Return basic analysis if AI fails
            return $this->getFallbackAnalysis($email);
        }
    }

    private function prepareEmailContent(array $email): string
    {
        return "Subject: {$email['subject']}\n\n" .
               "From: {$email['from']}\n" .
               "Date: {$email['date']}\n\n" .
               "Body:\n{$email['body']}\n\n" .
               "Attachments: " . implode(', ', array_keys($email['attachments'] ?? []));
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
        You are an AI assistant analyzing customer service emails for a print-on-demand business.
        
        Analyze the email content and extract the following information in JSON format:
        
        1. category: Must be one of: REFUND_REQUEST, REPRINT_REQUEST, SHIPPING_QUERY, QUALITY_ISSUE, 
           ORDER_STATUS, DESIGN_HELP, GENERAL_INQUIRY, COMPLAINT, TECHNICAL_ISSUE, SIZE_EXCHANGE
        
        2. priority: Must be one of: urgent, high, normal, low
           - urgent: Issues affecting multiple orders or serious quality issues
           - high: Refund requests, quality issues with evidence
           - normal: General inquiries, shipping questions
           - low: Feature requests, general feedback
        
        3. order_details: {
           "order_numbers": [], // All order numbers mentioned
           "products": [], // Product names or SKUs mentioned
           "shipping_info": {} // Any shipping or tracking numbers
        }
        
        4. customer_intent: {
           "primary_intent": "", // Main purpose of the email
           "secondary_intent": "", // Secondary purpose if any
           "requires_refund": boolean,
           "requires_reprint": boolean,
           "requires_shipping_update": boolean
        }
        
        5. evidence_provided: {
           "has_photos": boolean,
           "has_videos": boolean,
           "description_quality": "detailed|partial|none"
        }
        
        6. sentiment_analysis: {
           "sentiment": "positive|neutral|negative",
           "urgency": "high|medium|low",
           "satisfaction": "satisfied|neutral|dissatisfied"
        }
        
        7. auto_response: {
           "should_send": boolean,
           "response_type": "refund_evidence|shipping_update|general",
           "suggested_template": "template_name_here"
        }

        8. extracted_data: {
           "email": "", // Customer email if different from sender
           "phone": "", // Phone number if mentioned
           "country": "", // Shipping country if mentioned
           "size_info": {} // Size information for exchanges
        }

        Ensure all order numbers, tracking numbers, and product SKUs are correctly identified.
        PROMPT;
    }

    private function parseAIResponse(array $response): array
    {
        $analysis = json_decode(
            $response['choices'][0]['message']['content'],
            true
        );

        // Validate category
        if (!in_array($analysis['category'], self::TICKET_CATEGORIES)) {
            $analysis['category'] = 'GENERAL_INQUIRY';
        }

        // Validate and normalize priority
        $analysis['priority'] = $this->normalizePriority($analysis['priority']);

        // Ensure required structure
        return array_merge([
            'category' => 'GENERAL_INQUIRY',
            'priority' => 'normal',
            'order_details' => [
                'order_numbers' => [],
                'products' => [],
                'shipping_info' => []
            ],
            'customer_intent' => [
                'primary_intent' => '',
                'secondary_intent' => '',
                'requires_refund' => false,
                'requires_reprint' => false,
                'requires_shipping_update' => false
            ],
            'evidence_provided' => [
                'has_photos' => false,
                'has_videos' => false,
                'description_quality' => 'none'
            ],
            'sentiment_analysis' => [
                'sentiment' => 'neutral',
                'urgency' => 'low',
                'satisfaction' => 'neutral'
            ],
            'auto_response' => [
                'should_send' => false,
                'response_type' => 'general',
                'suggested_template' => null
            ],
            'extracted_data' => [
                'email' => '',
                'phone' => '',
                'country' => '',
                'size_info' => []
            ],
            'analyzed_at' => $this->currentTime,
            'analyzed_by' => 'AI-GPT4'
        ], $analysis);
    }

    private function normalizePriority(string $priority): string
    {
        return match (strtolower($priority)) {
            'urgent' => 'urgent',
            'high' => 'high',
            'low' => 'low',
            default => 'normal'
        };
    }

    private function getFallbackAnalysis(array $email): array
    {
        // Basic analysis based on subject keywords
        $subject = strtolower($email['subject']);
        
        $category = match(true) {
            str_contains($subject, 'refund') => 'REFUND_REQUEST',
            str_contains($subject, 'shipping') => 'SHIPPING_QUERY',
            str_contains($subject, 'order') => 'ORDER_STATUS',
            default => 'GENERAL_INQUIRY'
        };

        return [
            'category' => $category,
            'priority' => 'normal',
            'order_details' => [
                'order_numbers' => $this->extractOrderNumbers($email['body']),
                'products' => [],
                'shipping_info' => []
            ],
            'customer_intent' => [
                'primary_intent' => $category,
                'secondary_intent' => '',
                'requires_refund' => $category === 'REFUND_REQUEST',
                'requires_reprint' => false,
                'requires_shipping_update' => false
            ],
            'evidence_provided' => [
                'has_photos' => !empty($email['attachments']),
                'has_videos' => false,
                'description_quality' => 'none'
            ],
            'sentiment_analysis' => [
                'sentiment' => 'neutral',
                'urgency' => 'low',
                'satisfaction' => 'neutral'
            ],
            'auto_response' => [
                'should_send' => true,
                'response_type' => 'general',
                'suggested_template' => 'general_acknowledgment'
            ],
            'extracted_data' => [
                'email' => $email['from'],
                'phone' => '',
                'country' => '',
                'size_info' => []
            ],
            'analyzed_at' => $this->currentTime,
            'analyzed_by' => 'FALLBACK'
        ];
    }

    private function extractOrderNumbers(string $content): array
    {
        // Match common order number patterns
        preg_match_all('/\b(?:order|#)[\s:]?(\d{6,})\b/i', $content, $matches);
        return array_unique($matches[1]);
    }
}