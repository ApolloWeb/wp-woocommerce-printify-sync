<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use ApolloWeb\WPWooCommercePrintifySync\Tickets\TicketService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ChatGPTClient;

class OrderTicketHandler {
    private $ticket_service;
    private $chatgpt;
    private $logger;

    public function __construct(TicketService $ticket_service, ChatGPTClient $chatgpt, Logger $logger) {
        $this->ticket_service = $ticket_service;
        $this->chatgpt = $chatgpt;
        $this->logger = $logger;
    }

    public function createOrderIssueTicket($order_id, $issue_type, $description) {
        $order = wc_get_order($order_id);
        $printify_id = get_post_meta($order_id, '_printify_order_id', true);

        // Get AI analysis of the issue
        $analysis = $this->analyzeIssue($order, $issue_type, $description);

        $ticket_data = [
            'title' => sprintf('Order #%s - %s', $order->get_order_number(), $issue_type),
            'description' => $description,
            'priority' => $analysis['priority'],
            'category' => 'order-issue',
            'metadata' => [
                'order_id' => $order_id,
                'printify_id' => $printify_id,
                'issue_type' => $issue_type,
                'ai_analysis' => $analysis,
                'suggested_actions' => $analysis['suggested_actions']
            ]
        ];

        $ticket_id = $this->ticket_service->createTicket($ticket_data);

        // Add AI suggested response
        if (!empty($analysis['suggested_response'])) {
            $this->ticket_service->addResponse($ticket_id, [
                'content' => $analysis['suggested_response'],
                'type' => 'ai-suggestion',
                'metadata' => ['source' => 'chatgpt']
            ]);
        }

        return $ticket_id;
    }

    private function analyzeIssue($order, $issue_type, $description) {
        $prompt = $this->buildAnalysisPrompt($order, $issue_type, $description);
        
        try {
            $response = $this->chatgpt->analyze([
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a print-on-demand customer service expert specializing in Printify orders.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

            return [
                'priority' => $response['priority'],
                'suggested_actions' => $response['actions'],
                'suggested_response' => $response['customer_response'],
                'refund_recommended' => $response['refund_recommended'],
                'reprint_recommended' => $response['reprint_recommended']
            ];
        } catch (\Exception $e) {
            $this->logger->error('AI analysis failed: ' . $e->getMessage());
            return $this->getFallbackAnalysis($issue_type);
        }
    }

    private function buildAnalysisPrompt($order, $issue_type, $description) {
        return sprintf(
            "Analyze this Printify order issue:\n\nOrder #%s\nType: %s\nDescription: %s\n\n" .
            "Determine:\n1. Priority (high/medium/low)\n2. Suggested actions\n" .
            "3. Customer response\n4. If refund needed\n5. If reprint needed",
            $order->get_order_number(),
            $issue_type,
            $description
        );
    }
}
