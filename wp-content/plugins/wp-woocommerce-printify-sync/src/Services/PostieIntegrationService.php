<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class PostieIntegrationService
{
    use TimeStampTrait;

    private const EMAIL_PATTERNS = [
        'order_confirmation' => '/Order\s+#(\d+)\s+Confirmation/',
        'shipping_update' => '/Shipping\s+Update\s+for\s+Order\s+#(\d+)/',
        'product_query' => '/Product\s+Query:\s+(.+)/',
        'error_report' => '/Error\s+Report:\s+(.+)/'
    ];

    private LoggerInterface $logger;
    private ConfigService $config;
    private TicketingService $ticketing;
    private PrintifyAPIHandler $apiHandler;

    public function __construct(
        LoggerInterface $logger,
        ConfigService $config,
        TicketingService $ticketing,
        PrintifyAPIHandler $apiHandler
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->ticketing = $ticketing;
        $this->apiHandler = $apiHandler;
    }

    public function processIncomingEmail(array $email): void
    {
        try {
            // Extract email metadata
            $metadata = $this->extractEmailMetadata($email);
            
            // Log incoming email
            $this->logIncomingEmail($metadata);

            // Process based on email type
            switch ($this->determineEmailType($email['subject'])) {
                case 'order_confirmation':
                    $this->handleOrderConfirmation($email, $metadata);
                    break;
                    
                case 'shipping_update':
                    $this->handleShippingUpdate($email, $metadata);
                    break;
                    
                case 'product_query':
                    $this->handleProductQuery($email, $metadata);
                    break;
                    
                case 'error_report':
                    $this->handleErrorReport($email, $metadata);
                    break;
                    
                default:
                    $this->createSupportTicket($email, $metadata);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to process incoming email', $this->addTimeStampData([
                'subject' => $email['subject'],
                'error' => $e->getMessage()
            ]));
            
            // Create error ticket
            $this->createErrorTicket($email, $e);
        }
    }

    private function extractEmailMetadata(array $email): array
    {
        return $this->addTimeStampData([
            'message_id' => $email['message_id'] ?? null,
            'from' => $email['from'],
            'subject' => $email['subject'],
            'date' => $email['date'],
            'has_attachments' => !empty($email['attachments']),
            'size' => strlen($email['body']),
            'references' => $email['references'] ?? null,
            'in_reply_to' => $email['in_reply_to'] ?? null
        ]);
    }

    private function determineEmailType(string $subject): ?string
    {
        foreach (self::EMAIL_PATTERNS as $type => $pattern) {
            if (preg_match($pattern, $subject)) {
                return $type;
            }
        }
        return null;
    }

    private function handleOrderConfirmation(array $email, array $metadata): void
    {
        preg_match(self::EMAIL_PATTERNS['order_confirmation'], $email['subject'], $matches);
        $orderId = $matches[1];

        try {
            // Extract order details from email
            $orderDetails = $this->parseOrderConfirmationEmail($email['body']);
            
            // Update order status in WooCommerce
            $order = wc_get_order($orderId);
            if ($order) {
                $order->update_status(
                    'processing',
                    __('Order confirmed via email', 'wp-woocommerce-printify-sync')
                );

                // Add order note with confirmation details
                $order->add_order_note(
                    sprintf(
                        __('Order confirmation received via email. Reference: %s', 'wp-woocommerce-printify-sync'),
                        $metadata['message_id']
                    )
                );
            }

            $this->logger->info('Processed order confirmation email', $this->addTimeStampData([
                'order_id' => $orderId,
                'message_id' => $metadata['message_id']
            ]));

        } catch (\Exception $e) {
            $this->createErrorTicket($email, $e);
        }
    }

    private function handleShippingUpdate(array $email, array $metadata): void
    {
        preg_match(self::EMAIL_PATTERNS['shipping_update'], $email['subject'], $matches);
        $orderId = $matches[1];

        try {
            // Extract shipping details
            $shippingDetails = $this->parseShippingUpdateEmail($email['body']);
            
            // Update order shipping information
            $order = wc_get_order($orderId);
            if ($order) {
                // Update tracking information
                update_post_meta($order->get_id(), '_tracking_number', $shippingDetails['tracking_number']);
                update_post_meta($order->get_id(), '_tracking_provider', $shippingDetails['carrier']);

                // Add order note
                $order->add_order_note(
                    sprintf(
                        __('Shipping updated via email. Tracking: %s (%s)', 'wp-woocommerce-printify-sync'),
                        $shippingDetails['tracking_number'],
                        $shippingDetails['carrier']
                    )
                );

                // Update Printify
                $this->apiHandler->updateOrderShipping($orderId, $shippingDetails);
            }

            $this->logger->info('Processed shipping update email', $this->addTimeStampData([
                'order_id' => $orderId,
                'tracking' => $shippingDetails['tracking_number']
            ]));

        } catch (\Exception $e) {
            $this->createErrorTicket($email, $e);
        }
    }

    private function handleProductQuery(array $email, array $metadata): void
    {
        try {
            // Create support ticket for product query
            $ticketId = $this->ticketing->createTicket([
                'title' => 'Product Query: ' . $email['subject'],
                'description' => $email['body'],
                'priority' => 'medium',
                'status' => 'open',
                'related_entity_type' => 'email',
                'related_entity_id' => $metadata['message_id'],
                'metadata' => $metadata
            ]);

            // Send auto-response
            $this->sendAutoResponse($email['from'], 'product_query', [
                'ticket_id' => $ticketId
            ]);

            $this->logger->info('Created ticket for product query', $this->addTimeStampData([
                'ticket_id' => $ticketId,
                'message_id' => $metadata['message_id']
            ]));

        } catch (\Exception $e) {
            $this->createErrorTicket($email, $e);
        }
    }

    private function handleErrorReport(array $email, array $metadata): void
    {
        try {
            // Create high-priority ticket for error report
            $ticketId = $this->ticketing->createTicket([
                'title' => 'Error Report: ' . $email['subject'],
                'description' => $email['body'],
                'priority' => 'high',
                'status' => 'open',
                'related_entity_type' => 'email',
                'related_entity_id' => $metadata['message_id'],
                'metadata' => $metadata
            ]);

            // Process attachments if any
            if (!empty($email['attachments'])) {
                $this->processErrorReportAttachments($ticketId, $email['attachments']);
            }

            $this->logger->warning('Received error report', $this->addTimeStampData([
                'ticket_id' => $ticketId,
                'message_id' => $metadata['message_id']
            ]));

        } catch (\Exception $e) {
            $this->createErrorTicket($email, $e);
        }
    }

    private function createSupportTicket(array $email, array $metadata): int
    {
        return $this->ticketing->createTicket([
            'title' => $email['subject'],
            'description' => $email['body'],
            'priority' => 'normal',
            'status' => 'open',
            'related_entity_type' => 'email',
            'related_entity_id' => $metadata['message_id'],
            'metadata' => $metadata
        ]);
    }

    private function createErrorTicket(array $email, \Exception $error): int
    {
        return $this->ticketing->createTicket([
            'title' => 'Email Processing Error: ' . $email['subject'],
            'description' => "Error processing email:\n\n" . $error->getMessage(),
            'priority' => 'high',
            'status' => 'open',
            'related_entity_type' => 'email',
            'related_entity_id' => $email['message_id'] ?? null,
            'metadata' => [
                'error_message' => $error->getMessage(),
                'error_trace' => $error->getTraceAsString(),
                'original_email' => $email
            ]
        ]);
    }

    private function logIncomingEmail(array $metadata): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_email_log',
            $this->addTimeStampData($metadata),
            ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
        );
    }

    private function parseOrderConfirmationEmail(string $body): array
    {
        // Implementation of order confirmation email parsing
        return [];
    }

    private function parseShippingUpdateEmail(string $body): array
    {
        // Implementation of shipping update email parsing
        return [];
    }

    private function processErrorReportAttachments(int $ticketId, array $attachments): void
    {
        // Implementation of attachment processing
    }

    private function sendAutoResponse(string $to, string $type, array $data): void
    {
        // Implementation of auto-response system
    }
}