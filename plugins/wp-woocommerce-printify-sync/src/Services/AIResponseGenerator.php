<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AIResponseGenerator
{
    private const CURRENT_TIME = '2025-03-15 22:15:54';
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

    public function generateResponse(int $ticketId): array
    {
        try {
            $ticket = get_post($ticketId);
            if (!$ticket) {
                throw new \Exception('Ticket not found');
            }

            $analysis = get_post_meta($ticketId, '_wpwps_ai_analysis', true);
            $context = $this->getTicketContext($ticketId);
            
            $response = $this->generateAIResponse($ticket, $analysis, $context);
            
            $this->logResponse($ticketId, $response);
            
            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate AI response', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
                'timestamp' => self::CURRENT_TIME
            ]);

            return $this->getFallbackResponse($ticketId);
        }
    }

    private function getTicketContext(int $ticketId): array
    {
        $context = [
            'order_info' => $this->getOrderContext($ticketId),
            'customer_info' => $this->getCustomerContext($ticketId),
            'previous_interactions' => $this->getPreviousInteractions($ticketId),
            'sentiment_analysis' => $this->getSentimentAnalysis($ticketId)
        ];

        return array_filter($context);
    }

    private function generateAIResponse(\WP_Post $ticket, array $analysis, array $context): array
    {
        $prompt = $this->buildResponsePrompt($ticket, $analysis, $context);

        $completion = $this->openAIClient->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt()
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ]);

        return [
            'response' => $completion->choices[0]->message->content,
            'sentiment' => $analysis['details']['customer_mood'],
            'suggested_actions' => $this->parseSuggestedActions($completion->choices[0]->message->content),
            'generated_at' => self::CURRENT_TIME
        ];
    }

    private function getSystemPrompt(): string
    {
        return <<<EOT
You are an empathetic and professional customer service AI assistant for an e-commerce store.
Your responses should be:
1. Professional and courteous
2. Empathetic to customer concerns
3. Clear and concise
4. Solution-oriented
5. In line with e-commerce best practices

When responding:
- Address customer concerns directly
- Provide clear next steps
- Use appropriate tone based on customer sentiment
- Include relevant order/product information when available
- Suggest additional helpful resources when appropriate
EOT;
    }

    private function buildResponsePrompt(\WP_Post $ticket, array $analysis, array $context): string
    {
        $prompt = <<<EOT
Customer Inquiry:
{$ticket->post_content}

Ticket Analysis:
Category: {$analysis['category']}
Urgency: {$analysis['details']['urgency']}
Customer Mood: {$analysis['details']['customer_mood']}

Context:
EOT;

        if (!empty($context['order_info'])) {
            $prompt .= "\nOrder Information:\n" . json_encode($context['order_info'], JSON_PRETTY_PRINT);
        }

        if (!empty($context['customer_info'])) {
            $prompt .= "\nCustomer History:\n" . json_encode($context['customer_info'], JSON_PRETTY_PRINT);
        }

        $prompt .= "\n\nPlease generate a professional response that:
1. Addresses the customer's specific concerns
2. Provides clear next steps or solutions
3. Maintains appropriate tone and empathy
4. Includes relevant order/tracking information if available
5. Suggests additional resources if helpful

Format the response with:
- Main response body
- Suggested follow-up actions (JSON)
- Internal notes (if any)";

        return $prompt;
    }

    private function getSentimentAnalysis(int $ticketId): array
    {
        try {
            $ticket = get_post($ticketId);
            $response = $this->openAIClient->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Analyze the sentiment and emotion in the following customer message. Provide detailed analysis of tone, urgency, and emotional state.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $ticket->post_content
                    ]
                ],
                'temperature' => 0.3
            ]);

            $sentiment = json_decode($response->choices[0]->message->content, true);
            
            update_post_meta($ticketId, '_wpwps_sentiment_analysis', $sentiment);
            
            return $sentiment;

        } catch (\Exception $e) {
            $this->logger->error('Sentiment analysis failed', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);

            return [
                'sentiment' => 'neutral',
                'urgency' => 'medium',
                'confidence' => 0.5
            ];
        }
    }

    private function parseSuggestedActions(string $response): array
    {
        try {
            if (preg_match('/Suggested Actions:(.*?)(?=Internal Notes:|$)/s', $response, $matches)) {
                $actionsText = $matches[1];
                return json_decode($actionsText, true) ?: [];
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function logResponse(int $ticketId, array $response): void
    {
        update_post_meta($ticketId, '_wpwps_ai_response', [
            'response' => $response['response'],
            'sentiment' => $response['sentiment'],
            'suggested_actions' => $response['suggested_actions'],
            'generated_at' => self::CURRENT_TIME,
            'generated_by' => self::CURRENT_USER
        ]);

        $this->logger->info('AI response generated', [
            'ticket_id' => $ticketId,
            'sentiment' => $response['sentiment'],
            'timestamp' => self::CURRENT_TIME
        ]);
    }

    private function getFallbackResponse(int $ticketId): array
    {
        $templates = [
            'refund' => $this->getRefundTemplate(),
            'order_inquiry' => $this->getOrderInquiryTemplate(),
            'product_inquiry' => $this->getProductInquiryTemplate(),
            'general_inquiry' => $this->getGeneralInquiryTemplate()
        ];

        $analysis = get_post_meta($ticketId, '_wpwps_ai_analysis', true);
        $category = $analysis['category'] ?? 'general_inquiry';

        return [
            'response' => $templates[$category] ?? $templates['general_inquiry'],
            'sentiment' => 'neutral',
            'suggested_actions' => ['review_manually' => true],
            'generated_at' => self::CURRENT_TIME,
            'is_fallback' => true
        ];
    }

    private function getOrderContext(int $ticketId): array
    {
        $orderIds = get_post_meta($ticketId, '_wpwps_related_orders', true);
        if (!$orderIds) {
            return [];
        }

        $context = [];
        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);
            if (!$order) continue;

            $context[] = [
                'order_id' => $orderId,
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'date_created' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'shipping_method' => $order->get_shipping_method(),
                'tracking_number' => $order->get_meta('_printify_tracking_number'),
                'printify_status' => $order->get_meta('_printify_status')
            ];
        }

        return $context;
    }
}