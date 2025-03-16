<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AITrainingManager
{
    private const CURRENT_TIME = '2025-03-15 22:17:37';
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

    public function collectTrainingData(int $ticketId): void
    {
        try {
            $ticket = get_post($ticketId);
            if (!$ticket) {
                throw new \Exception('Ticket not found');
            }

            $trainingData = [
                'ticket_id' => $ticketId,
                'content' => $ticket->post_content,
                'ai_analysis' => get_post_meta($ticketId, '_wpwps_ai_analysis', true),
                'ai_response' => get_post_meta($ticketId, '_wpwps_ai_response', true),
                'human_response' => get_post_meta($ticketId, '_wpwps_human_response', true),
                'customer_feedback' => get_post_meta($ticketId, '_wpwps_customer_feedback', true),
                'resolution_time' => $this->calculateResolutionTime($ticketId),
                'successful_resolution' => get_post_meta($ticketId, '_wpwps_resolved_successfully', true),
                'collected_at' => self::CURRENT_TIME,
                'collected_by' => self::CURRENT_USER
            ];

            $this->storeTrainingData($trainingData);

        } catch (\Exception $e) {
            $this->logger->error('Failed to collect training data', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
                'timestamp' => self::CURRENT_TIME
            ]);
        }
    }

    public function improveAIModel(): void
    {
        $trainingData = $this->getRecentTrainingData();
        
        foreach ($trainingData as $data) {
            try {
                $this->submitTrainingExample($data);
            } catch (\Exception $e) {
                $this->logger->error('Failed to submit training example', [
                    'ticket_id' => $data['ticket_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function storeTrainingData(array $data): void
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwps_ai_training';
        
        $wpdb->insert(
            $table,
            [
                'ticket_id' => $data['ticket_id'],
                'training_data' => json_encode($data),
                'created_at' => self::CURRENT_TIME,
                'created_by' => self::CURRENT_USER
            ],
            ['%d', '%s', '%s', '%s']
        );
    }

    private function getRecentTrainingData(): array
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwps_ai_training';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            ),
            ARRAY_A
        );
    }

    private function submitTrainingExample(array $data): void
    {
        // Format training example for GPT fine-tuning
        $example = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a customer service AI assistant for an e-commerce store.'
                ],
                [
                    'role' => 'user',
                    'content' => $data['content']
                ],
                [
                    'role' => 'assistant',
                    'content' => $data['human_response'] ?? $data['ai_response']['response']
                ]
            ]
        ];

        // Submit to OpenAI for fine-tuning
        $this->openAIClient->files()->create([
            'purpose' => 'fine-tune',
            'file' => json_encode([$example])
        ]);
    }

    private function calculateResolutionTime(int $ticketId): int
    {
        $createdAt = get_post_time('U', true, $ticketId);
        $resolvedAt = get_post_meta($ticketId, '_wpwps_resolved_at', true);
        
        if (!$resolvedAt) {
            return 0;
        }

        return strtotime($resolvedAt) - $createdAt;
    }
}