<?php
namespace ApolloWeb\WPWooCommercePrintifySync\AI;

class ResponseGenerator {
    private $ai_manager;
    private $sentiment_analyzer;
    private $context_window = 5;

    public function __construct(
        AIModelManager $ai_manager,
        SentimentAnalyzer $sentiment_analyzer
    ) {
        $this->ai_manager = $ai_manager;
        $this->sentiment_analyzer = $sentiment_analyzer;
    }

    public function generateResponse(string $query, array $conversation_history = []): array {
        $context = $this->buildContext($conversation_history);
        $sentiment = $this->sentiment_analyzer->analyzeSentiment($query);

        $response = $this->ai_manager->generateResponse($query, [
            'context' => $context,
            'sentiment' => $sentiment,
            'tone' => $this->determineTone($sentiment)
        ]);

        return $this->validateResponse($response);
    }

    private function buildContext(array $history): array {
        return array_slice($history, -$this->context_window);
    }

    private function determineTone(array $sentiment): string {
        return match ($sentiment['dominant_emotion']) {
            'angry' => 'empathetic',
            'frustrated' => 'helpful',
            'satisfied' => 'enthusiastic',
            default => 'neutral'
        };
    }
}
