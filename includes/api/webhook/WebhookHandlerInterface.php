<?php
/**
 * Webhook Handler Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API\Webhook
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API\Webhook;

interface WebhookHandlerInterface {
    /**
     * Process incoming webhook request
     *
     * @param string $payload JSON payload from request body
     * @param array $headers Request headers
     * @return array Response data
     */
    public function processWebhook(string $payload, array $headers): array;
    
    /**
     * Register webhook with Printify API
     *
     * @return array Response data
     */
    public function registerWebhook(): array;
    
    /**
     * Verify webhook signature
     *
     * @param string $payload Request body
     * @param string $signature Webhook signature from header
     * @return bool Verification result
     */
    public function verifySignature(string $payload, string $signature): bool;
}