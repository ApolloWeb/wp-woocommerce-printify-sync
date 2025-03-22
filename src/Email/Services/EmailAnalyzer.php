<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class EmailAnalyzer {
    private $chatgpt;
    private $logger;

    public function __construct(ChatGPTClient $chatgpt, Logger $logger) {
        $this->chatgpt = $chatgpt;
        $this->logger = $logger;
    }

    public function analyzeEmail(array $email) {
        try {
            $analysis = $this->chatgpt->analyze([
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getAnalysisPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->formatEmailForAnalysis($email)
                    ]
                ]
            ]);

            return [
                'category' => $analysis['category'],
                'urgency' => $analysis['urgency'],
                'customer_id' => $this->findCustomer($analysis['customer_details']),
                'order_id' => $this->findOrder($analysis['order_details']),
                'suggested_response' => $analysis['suggested_response'],
                'metadata' => $analysis['extracted_data']
            ];
        } catch (\Exception $e) {
            $this->logger->error('Email analysis failed: ' . $e->getMessage());
            return null;
        }
    }

    private function getAnalysisPrompt() {
        return <<<EOT
Analyze this customer service email and extract:
1. Category (order, product, technical, billing, other)
2. Urgency (high, medium, low)
3. Customer details (name, email, identifiers)
4. Order references
5. Key issues or requests
6. Suggested response approach
Format as JSON with clear categorizations.
EOT;
    }
}
