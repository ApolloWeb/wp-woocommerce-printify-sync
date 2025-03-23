<?php

namespace ApolloWeb\WPWooCommercePrintifySync\AI;

class AIModelManager {
    private $settings;
    private $logger;
    private $api_key;
    private $model_config;

    public function __construct(Settings $settings, Logger $logger) {
        $this->settings = $settings;
        $this->logger = $logger;
        $this->api_key = $settings->get('ai_api_key');
        $this->model_config = $this->loadModelConfig();
    }

    public function generateResponse(string $prompt, array $context = []): array {
        try {
            $response = $this->callAIAPI('generate', [
                'prompt' => $prompt,
                'context' => $context,
                'max_tokens' => 150,
                'temperature' => 0.7
            ]);

            return [
                'text' => $response['choices'][0]['text'],
                'confidence' => $response['choices'][0]['confidence'],
                'metadata' => $this->extractMetadata($response)
            ];
        } catch (\Exception $e) {
            $this->logger->log("AI response generation failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public function analyzeSentiment(string $text): array {
        return $this->callAIAPI('analyze/sentiment', [
            'text' => $text
        ]);
    }

    private function callAIAPI(string $endpoint, array $data): array {
        $response = wp_remote_post($this->settings->get('ai_api_url') . $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function loadModelConfig(): array {
        return [
            'model_version' => $this->settings->get('ai_model_version', '1.0.0'),
            'parameters' => [
                'temperature' => 0.7,
                'max_tokens' => 150,
                'top_p' => 1,
                'frequency_penalty' => 0,
                'presence_penalty' => 0
            ]
        ];
    }
}
