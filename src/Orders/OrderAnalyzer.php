<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

class OrderAnalyzer {
    private $chatgpt;
    private $logger;
    private $response_generator;

    public function __construct(ChatGPTClient $chatgpt, Logger $logger, ResponseGenerator $response_generator) {
        $this->chatgpt = $chatgpt;
        $this->logger = $logger;
        $this->response_generator = $response_generator;
    }

    public function analyzeIssue($order_id, $description, $images = []) {
        try {
            $analysis = $this->chatgpt->analyze([
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getAnalysisPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->formatOrderIssue($order_id, $description, $images)
                    ]
                ]
            ]);

            // Generate customer response
            $response = $this->response_generator->generateResponse([
                'analysis' => $analysis,
                'order_id' => $order_id,
                'description' => $description
            ]);

            return array_merge($analysis, ['response' => $response]);

        } catch (\Exception $e) {
            $this->logger->error('Order analysis failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getAnalysisPrompt() {
        return <<<EOT
Analyze this print-on-demand order issue and determine:
1. Issue type (quality, sizing, design, shipping, other)
2. Severity (high, medium, low)
3. Recommended action (reprint or refund)
4. Required evidence
5. Suggested response to customer
Classify based on Printify's policies and standards.
Format response as JSON.
EOT;
    }

    private function formatOrderIssue($order_id, $description, $images) {
        $order = wc_get_order($order_id);
        return json_encode([
            'order_details' => $this->getOrderDetails($order),
            'issue_description' => $description,
            'has_images' => !empty($images),
            'customer_history' => $this->getCustomerHistory($order)
        ]);
    }
}
