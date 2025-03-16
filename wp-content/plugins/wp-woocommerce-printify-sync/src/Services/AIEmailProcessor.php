<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;
use ApolloWeb\WPWooCommercePrintifySync\PostTypes\SupportTicketPostType;

class AIEmailProcessor
{
    use TimeStampTrait;

    private const GPT_MODEL = 'gpt-3';
    private const ORDER_PATTERN = '#Order\s*(?:Number|#|No\.?:?)?\s*(\d+)#i';

    private LoggerInterface $logger;
    private ConfigService $config;
    private OpenAIClient $openai;

    public function __construct(
        LoggerInterface $logger,
        ConfigService $config,
        OpenAIClient $openai
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->openai = $openai;
    }

    public function processEmail(array $email): array
    {
        try {
            // Extract metadata
            $metadata = $this->extractMetadata($email);

            // Process with GPT-3
            $analysis = $this->analyzeWithGPT3($email);

            // Match customer
            $customer = $this->matchCustomer($email['from']);

            // Extract order references
            $orders = $this->extractOrderReferences($email['body']);

            // Determine thread
            $threadId = $this->determineThreadId($email);

            return $this->addTimeStampData([
                'post_type' => SupportTicketPostType::POST_TYPE,
                'post_title' => $email['subject'],
                'post_content' => $email['body'],
                'meta_input' => [
                    '_wpwps_email_metadata' => $metadata,
                    '_wpwps_ai_analysis' => $analysis,
                    '_wpwps_customer_id' => $customer ? $customer->get_id() : null,
                    '_wpwps_order_ids' => $orders,
                    '_wpwps_thread_id' => $threadId,
                    '_wpwps_priority' => $analysis['priority'],
                    '_wpwps_category' => $analysis['category'],
                ],
                'tax_input' => [
                    SupportTicketPostType::TAXONOMY_CATEGORY => [$analysis['category']],
                    SupportTicketPostType::TAXONOMY_STATUS => ['new'],
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process email', $this->addTimeStampData([
                'subject' => $email['subject'],
                'error' => $e->getMessage()
            ]));
            throw $e;
        }
    }

    private function analyzeWithGPT3(array $email): array
    {
        $prompt = $this->buildAnalysisPrompt($email);
        
        $response = $this->openai->complete([
            'model' => self::GPT_MODEL,
            'prompt' => $prompt,
            'temperature' => 0.3,
            'max_tokens' => 150
        ]);

        return $this->parseGPT3Response($response);
    }

    private function buildAnalysisPrompt(array $email): string
    {
        return <<<PROMPT
        Analyze this support email and extract the following information:
        1. Category (order, shipping, product, technical, billing, other)
        2. Priority (low, medium, high, urgent)
        3. Key entities (order numbers, product names, etc.)
        4. Sentiment (positive, neutral, negative)
        5. Required actions

        Email Subject: {$email['subject']}
        Email Body:
        {$email['body']}
        PROMPT;
    }

    private function parseGPT3Response($response): array
    {
        // Parse structured response from GPT-3
        $analysis = json_decode($response, true);
        
        return [
            'category' => $analysis['category'],
            'priority' => $analysis['priority'],
            'entities' => $analysis['entities'],
            'sentiment' => $analysis['sentiment'],
            'actions' => $analysis['actions']
        ];
    }

    private function matchCustomer(string $email): ?\WC_Customer
    {
        $customer_id = wc_get_customer_id_by_email($email);
        
        if ($customer_id) {
            return new \WC_Customer($customer_id);
        }

        return null;
    }

    private function extractOrderReferences(string $content): array
    {
        preg_match_all(self::ORDER_PATTERN, $content, $matches);
        
        $orderIds = array_map('intval', $matches[1]);
        $validOrders = [];

        foreach ($orderIds as $orderId) {
            if (wc_get_order($orderId)) {
                $validOrders[] = $orderId;
            }
        }

        return array_unique($validOrders);
    }

    private function determineThreadId(array $email): ?string
    {
        // Check for References or In-Reply-To headers
        if (!empty($email['references'])) {
            return md5($email['references']);
        }

        if (!empty($email['in_reply_to'])) {
            return md5($email['in_reply_to']);
        }

        // Generate new thread ID for fresh conversations
        return md5($email['message_id']);
    }

    private function extractMetadata(array $email): array
    {
        return [
            'from' => $email['from'],
            'to' => $email['to'],
            'date' => $email['date'],
            'message_id' => $email['message_id'],
            'references' => $email['references'] ?? null,
            'in_reply_to' => $email['in_reply_to'] ?? null,
            'has_attachments' => !empty($email['attachments']),
        ];
    }
}