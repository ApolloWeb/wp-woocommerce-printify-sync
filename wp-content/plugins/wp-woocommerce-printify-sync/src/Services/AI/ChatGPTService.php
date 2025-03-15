<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\AI;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

class ChatGPTService
{
    use LoggerAwareTrait;

    private AppContext $context;
    private string $apiKey;
    private string $currentTime = '2025-03-15 20:21:31';
    private string $currentUser = 'ApolloWeb';
    private string $model = 'gpt-3.5-turbo';
    private array $systemPrompts;

    public function __construct()
    {
        $this->context = AppContext::getInstance();
        $this->apiKey = get_option('wpwps_chatgpt_api_key');
        $this->initializePrompts();
    }

    private function initializePrompts(): void
    {
        $this->systemPrompts = [
            'email_analysis' => "You are analyzing customer service emails for a print-on-demand business. 
                Extract key information including priority, category, sentiment, and any order numbers mentioned. 
                Format response as JSON.",
            'refund_assessment' => "You are evaluating refund requests for a print-on-demand business. 
                Analyze the evidence provided and determine if it meets refund criteria. 
                Consider product quality, shipping damage, and accuracy. Format response as JSON.",
            'response_generation' => "You are drafting customer service responses for a print-on-demand business. 
                Maintain a professional, helpful tone. Include relevant order details and next steps. 
                Format response in HTML compatible with WooCommerce email templates."
        ];
    }

    public function analyzeTicket(array $ticketData): array
    {
        try {
            $response = $this->makeRequest(
                $this->systemPrompts['email_analysis'],
                json_encode($ticketData)
            );

            $this->log('info', 'Ticket analysis completed', [
                'ticket_id' => $ticketData['id'] ?? null,
                'analysis' => $response
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->log('error', 'Ticket analysis failed', [
                'error' => $e->getMessage(),
                'ticket_data' => $ticketData
            ]);
            throw $e;
        }
    }

    public function assessRefundRequest(array $refundData): array
    {
        try {
            $response = $this->makeRequest(
                $this->systemPrompts['refund_assessment'],
                json_encode($refundData)
            );

            $this->log('info', 'Refund assessment completed', [
                'order_id' => $refundData['order_id'] ?? null,
                'assessment' => $response
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->log('error', 'Refund assessment failed', [
                'error' => $e->getMessage(),
                'refund_data' => $refundData
            ]);
            throw $e;
        }
    }

    public function generateResponse(array $context): string
    {
        try {
            $response = $this->makeRequest(
                $this->systemPrompts['response_generation'],
                json_encode($context)
            );

            $this->log('info', 'Response generated', [
                'ticket_id' => $context['ticket_id'] ?? null,
                'template_type' => $context['template_type'] ?? 'general'
            ]);

            return $response['response'];

        } catch (\Exception $e) {
            $this->log('error', 'Response generation failed', [
                'error' => $e->getMessage(),
                'context' => $context
            ]);
            throw $e;
        }
    }

    private function makeRequest(string $systemPrompt, string $userContent): array
    {
        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' =>