<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class OpenAIService {
    private $api_key;
    private $client;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function analyzeEmail($content): array {
        $response = $this->callAPI('chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Extract customer support ticket details from this email'
                ],
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
            'temperature' => 0.7
        ]);

        return json_decode($response, true);
    }

    public function suggestResponse($ticket_content): string {
        $response = $this->callAPI('chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Suggest a professional customer service response'
                ],
                [
                    'role' => 'user',
                    'content' => $ticket_content
                ]
            ]
        ]);

        return json_decode($response, true)['choices'][0]['message']['content'];
    }
}
