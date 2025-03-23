<?php
namespace ApolloWeb\WPWooCommercePrintifySync\AI;

class SentimentAnalyzer {
    private $ai_manager;
    private $cache;

    public function __construct(AIModelManager $ai_manager) {
        $this->ai_manager = $ai_manager;
        $this->cache = new \WP_Object_Cache();
    }

    public function analyzeSentiment(string $text): array {
        $cache_key = 'sentiment_' . md5($text);
        $result = $this->cache->get($cache_key);

        if (false === $result) {
            $result = $this->ai_manager->analyzeSentiment($text);
            $this->cache->set($cache_key, $result, '', 3600);
        }

        return $this->enrichSentimentData($result);
    }

    private function enrichSentimentData(array $data): array {
        return array_merge($data, [
            'confidence_score' => $this->calculateConfidence($data),
            'emotion_breakdown' => $this->detectEmotions($data),
            'key_phrases' => $this->extractKeyPhrases($data)
        ]);
    }

    private function calculateConfidence(array $data): float {
        return $data['scores']['dominant_probability'] ?? 0.0;
    }
}
