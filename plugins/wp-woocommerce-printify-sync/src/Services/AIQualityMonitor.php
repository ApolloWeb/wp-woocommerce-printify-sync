<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AIQualityMonitor
{
    private const CURRENT_TIME = '2025-03-15 22:18:46';
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

    public function evaluateResponse(int $ticketId, array $response): array
    {
        try {
            $metrics = [
                'relevance' => $this->evaluateRelevance($ticketId, $response),
                'sentiment_match' => $this->evaluateSentimentMatch($ticketId, $response),
                'completeness' => $this->evaluateCompleteness($response),
                'tone' => $this->evaluateTone($response),
                'action_clarity' => $this->evaluateActionClarity($response),
                'evaluated_at' => self::CURRENT_TIME
            ];

            $this->storeMetrics($ticketId, $metrics);
            
            return $metrics;

        } catch (\Exception $e) {
            $this->logger->error('Response evaluation failed', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
                'timestamp' => self::CURRENT_TIME
            ]);

            return $this->getDefaultMetrics();
        }
    }

    private function evaluateRelevance(int $ticketId, array $response): float
    {
        $ticket = get_post($ticketId);
        if (!$ticket) {
            return 0.0;
        }

        $analysis = $this->openAIClient->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Evaluate the relevance of this customer service response to the original query. Score from 0 to 1.'
                ],
                [
                    'role' => 'user',
                    'content' => "Query:\n{$ticket->post_content}\n\nResponse:\n{$response['response']}"
                ]
            ],
            'temperature' => 0.3
        ]);

        return (float) $analysis->choices[0]->message->content;
    }

    private function evaluateSentimentMatch(int $ticketId, array $response): float
    {
        $originalSentiment = get_post_meta($ticketId, '_wpwps_ai_analysis', true)['details']['customer_mood'] ?? 'neutral';
        
        $analysis = $this->openAIClient->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Evaluate if the response tone matches the required sentiment: {$originalSentiment}. Score from 0 to 1."
                ],
                [
                    'role' => 'user',
                    'content' => $response['response']
                ]
            ],
            'temperature' => 0.3
        ]);

        return (float) $analysis->choices[0]->message->content;
    }

    private function evaluateCompleteness(array $response): float
    {
        $requiredElements = [
            'greeting' => 0.2,
            'issue_acknowledgment' => 0.2,
            'solution_provided' => 0.3,
            'next_steps' => 0.2,
            'closing' => 0.1
        ];

        $score = 0.0;
        foreach ($requiredElements as $element => $weight) {
            if ($this->containsElement($response['response'], $element)) {
                $score += $weight;
            }
        }

        return $score;
    }

    private function containsElement(string $response, string $element): bool
    {
        $patterns = [
            'greeting' => '/^(Hi|Hello|Dear)/i',
            'issue_acknowledgment' => '/(understand|appreciate|acknowledge)/i',
            'solution_provided' => '/(here\'s what|we can|I will|I\'ve)/i',
            'next_steps' => '/(next steps|please|you can|if you need)/i',
            'closing' => '/(thank|regards|best|sincerely)/i'
        ];

        return preg_match($patterns[$element], $response) === 1;
    }

    private function evaluateTone(array $response): float
    {
        $analysis = $this->openAIClient->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Evaluate the professionalism and appropriateness of the tone in this customer service response. Score from 0 to 1.'
                ],
                [
                    'role' => 'user',
                    'content' => $response['response']
                ]
            ],
            'temperature' => 0.3
        ]);

        return (float) $analysis->choices[0]->message->content;
    }

    private function evaluateActionClarity(array $response): float
    {
        if (empty($response['suggested_actions'])) {
            return 0.0;
        }

        $score = 0.0;
        $totalActions = count($response['suggested_actions']);

        foreach ($response['suggested_actions'] as $action) {
            if (isset($action['what']) && isset($action['how'])) {
                $score += 1.0;
            } elseif (isset($action['what']) || isset($action['how'])) {
                $score += 0.5;
            }
        }

        return $score / $totalActions;
    }

    private function storeMetrics(int $ticketId, array $metrics): void
    {
        update_post_meta($ticketId, '_wpwps_response_quality', $metrics);

        global $wpdb;
        $table = $wpdb->prefix . 'wpwps_ai_metrics';
        
        $wpdb->insert(
            $table,
            [
                'ticket_id' => $ticketId,
                'metrics' => json_encode($metrics),
                'created_at' => self::CURRENT_TIME,
                'created_by' => self::CURRENT_USER
            ],
            ['%d', '%s', '%s', '%s']
        );
    }

    private function getDefaultMetrics(): array
    {
        return [
            'relevance' => 0.5,
            'sentiment_match' => 0.5,
            'completeness' => 0.5,
            'tone' => 0.5,
            'action_clarity' => 0.5,
            'evaluated_at' => self::CURRENT_TIME,
            'is_default' => true
        ];
    }

    public function generateQualityReport(string $startDate = '', string $endDate = ''): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wpwps_ai_metrics';
        
        $query = "SELECT AVG(JSON_EXTRACT(metrics, '$.relevance')) as avg_relevance,
                         AVG(JSON_EXTRACT(metrics, '$.sentiment_match')) as avg_sentiment,
                         AVG(JSON_EXTRACT(metrics, '$.completeness')) as avg_completeness,
                         AVG(JSON_EXTRACT(metrics, '$.tone')) as avg_tone,
                         AVG(JSON_EXTRACT(metrics, '$.action_clarity')) as avg_clarity
                  FROM {$table}";

        if ($startDate && $endDate) {
            $query .= $wpdb->prepare(
                " WHERE created_at BETWEEN %s AND %s",
                $startDate,
                $endDate
            );
        }

        $results = $wpdb->get_row($query, ARRAY_A);

        return [
            'metrics' => $results,
            'total_responses' => $this->getTotalResponses($startDate, $endDate),
            'generated_at' => self::CURRENT_TIME
        ];
    }

    private function getTotalResponses(string $startDate = '', string $endDate = ''): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wpwps_ai_metrics';
        
        $query = "SELECT COUNT(*) FROM {$table}";

        if ($startDate && $endDate) {
            $query .= $wpdb->prepare(
                " WHERE created_at BETWEEN %s AND %s",
                $startDate,
                $endDate
            );
        }

        return (int) $wpdb->get_var($query);
    }
}