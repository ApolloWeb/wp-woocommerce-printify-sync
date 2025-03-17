<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AITicketProcessor
{
    private const CURRENT_TIME = '2025-03-15 22:14:03';
    private AIEmailProcessor $emailProcessor;
    private LoggerInterface $logger;

    public function __construct(
        AIEmailProcessor $emailProcessor,
        LoggerInterface $logger
    ) {
        $this->emailProcessor = $emailProcessor;
        $this->logger = $logger;
        $this->initHooks();
    }

    private function initHooks(): void
    {
        add_action('postie_post_after', [$this, 'processIncomingTicket'], 10, 2);
        add_filter('wpwps_ticket_metadata', [$this, 'enrichTicketMetadata'], 10, 2);
    }

    public function processIncomingTicket(int $postId, array $emailData): void
    {
        try {
            // Process email content with AI
            $analysis = $this->emailProcessor->processEmail(
                $emailData['subject'] ?? '',
                $emailData['content'] ?? '',
                [
                    'from' => $emailData['from'] ?? '',
                    'timestamp' => $emailData['timestamp'] ?? self::CURRENT_TIME
                ]
            );

            // Store AI analysis
            $this->storeAnalysis($postId, $analysis);

            // Update ticket properties based on AI analysis
            $this->updateTicketProperties($postId, $analysis);

            // Trigger automated actions based on analysis
            $this->handleAutomatedActions($postId, $analysis);

            $this->logger->info('Ticket processed by AI', [
                'ticket_id' => $postId,
                'category' => $analysis['category'],
                'timestamp' => self::CURRENT_TIME
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process ticket with AI', [
                'ticket_id' => $postId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function storeAnalysis(int $postId, array $analysis): void
    {
        update_post_meta($postId, '_wpwps_ai_analysis', $analysis);
        update_post_meta($postId, '_wpwps_ai_analysis_timestamp', self::CURRENT_TIME);

        // Store specific metadata for quick access
        update_post_meta($postId, '_wpwps_ticket_category', $analysis['category']);
        update_post_meta($postId, '_wpwps_urgency_level', $analysis['details']['urgency']);
        
        if (!empty($analysis['details']['order_numbers'])) {
            update_post_meta($postId, '_wpwps_related_orders', $analysis['details']['order_numbers']);
        }
    }

    private function updateTicketProperties(int $postId, array $analysis): void
    {
        // Set ticket category
        wp_set_object_terms($postId, [$analysis['category']], 'ticket_category');

        // Set urgency level
        wp_set_object_terms($postId, [$analysis['details']['urgency']], 'ticket_urgency');

        // Update ticket title with more context
        $newTitle = $this->generateTicketTitle($analysis);
        wp_update_post([
            'ID' => $postId,
            'post_title' => $newTitle
        ]);
    }

    private function handleAutomatedActions(int $postId, array $analysis): void
    {
        // Handle refund requests
        if ($analysis['category'] === 'refund') {
            $this->handleRefundRequest($postId, $analysis);
        }

        // Handle order inquiries
        if ($analysis['category'] === 'order_inquiry') {
            $this->handleOrderInquiry($postId, $analysis);
        }

        // Handle urgent tickets
        if ($analysis['details']['urgency'] === 'high') {
            $this->handleUrgentTicket($postId, $analysis);
        }

        // Trigger appropriate automated responses
        $this->triggerAutomatedResponse($postId, $analysis);
    }

    private function generateTicketTitle(array $analysis): string
    {
        $category = ucfirst(str_replace('_', ' ', $analysis['category']));
        $orderInfo = '';
        
        if (!empty($analysis['details']['order_numbers'])) {
            $orderInfo = ' - Order #' . $analysis['details']['order_numbers'][0];
        }

        $urgency = '';
        if ($analysis['details']['urgency'] === 'high') {
            $urgency = '[URGENT] ';
        }

        return $urgency . $category . $orderInfo;
    }

    private function handleRefundRequest(int $postId, array $analysis): void
    {
        if (empty($analysis['details']['order_numbers'])) {
            return;
        }

        foreach ($analysis['details']['order_numbers'] as $orderNumber) {
            $order = wc_get_order($orderNumber);
            if (!$order) continue;

            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Refund request received via ticket #%d - Under review', 'wp-woocommerce-printify-sync'),
                    $postId
                ),
                false
            );

            // Flag order for review
            $order->update_meta_data('_wpwps_refund_requested', self::CURRENT_TIME);
            $order->save();
        }
    }

    private function handleOrderInquiry(int $postId, array $analysis): void
    {
        if (empty($analysis['details']['order_numbers'])) {
            return;
        }

        // Fetch order details and prepare automated response
        foreach ($analysis['details']['order_numbers'] as $orderNumber) {
            $order = wc_get_order($orderNumber);
            if (!$order) continue;

            // Link ticket to order
            $order->update_meta_data('_wpwps_support_ticket_id', $postId);
            $order->save();

            // Prepare order status update
            $orderStatus = $this->getOrderStatusUpdate($order);
            if ($orderStatus) {
                update_post_meta($postId, '_wpwps_order_status_update', $orderStatus);
            }
        }
    }

    private function handleUrgentTicket(int $postId, array $analysis): void
    {
        // Notify support team
        do_action('wpwps_urgent_ticket_notification', $postId, $analysis);

        // Add to urgent queue
        wp_set_object_terms($postId, ['urgent'], 'ticket_priority');

        // Update ticket status
        wp_set_object_terms($postId, ['needs_immediate_attention'], 'ticket_status');
    }

    private function triggerAutomatedResponse(int $postId, array $analysis): void
    {
        $templateKey = $this->getResponseTemplate($analysis);
        if (!$templateKey) {
            return;
        }

        // Get response template
        $template = $this->getEmailTemplate($templateKey, [
            'ticket_id' => $postId,
            'analysis' => $analysis
        ]);

        // Send automated response
        if ($template) {
            $this->sendAutomatedResponse($postId, $template);
        }
    }

    private function getResponseTemplate(array $analysis): ?string
    {
        $templates = [
            'refund' => 'refund_request_received',
            'order_inquiry' => 'order_status_update',
            'product_inquiry' => 'product_information',
            'technical_support' => 'technical_support_received',
            'general_inquiry' => 'general_inquiry_received'
        ];

        return $templates[$analysis['category']] ?? null;
    }
}