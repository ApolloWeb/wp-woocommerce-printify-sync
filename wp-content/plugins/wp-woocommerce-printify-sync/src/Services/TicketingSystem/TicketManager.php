<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\TicketingSystem;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;
use ApolloWeb\WPWooCommercePrintifySync\Services\AI\AIService;

class TicketManager
{
    use LoggerAwareTrait;

    private AppContext $context;
    private EmailPoller $emailPoller;
    private AIService $aiService;
    private string $currentTime = '2025-03-15 20:19:41';
    private string $currentUser = 'ApolloWeb';

    public function __construct(
        EmailPoller $emailPoller,
        AIService $aiService
    ) {
        $this->context = AppContext::getInstance();
        $this->emailPoller = $emailPoller;
        $this->aiService = $aiService;
    }

    public function processIncomingEmails(): void
    {
        try {
            $emails = $this->emailPoller->fetchNewEmails();
            
            foreach ($emails as $email) {
                $this->processEmail($email);
            }

            $this->log('info', 'Processed incoming emails', [
                'count' => count($emails)
            ]);

        } catch (\Exception $e) {
            $this->log('error', 'Failed to process incoming emails', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function processEmail(array $email): void
    {
        // Extract information using AI
        $analysis = $this->aiService->analyzeEmail($email['body']);

        // Determine if this is a reply to existing ticket
        $ticketId = $this->findExistingTicket($email);

        if ($ticketId) {
            $this->addReplyToTicket($ticketId, $email, $analysis);
        } else {
            $this->createNewTicket($email, $analysis);
        }
    }

    private function findExistingTicket(array $email): ?int
    {
        global $wpdb;

        // Check references in email headers
        if (!empty($email['references'])) {
            return $this->findTicketByReference($email['references']);
        }

        // Check by email thread and subject
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpwps_tickets 
            WHERE customer_email = %s 
            AND (
                subject = %s 
                OR ticket_number IN (
                    SELECT ticket_number 
                    FROM {$wpdb->prefix}wpwps_ticket_messages 
                    WHERE content LIKE %s
                )
            )
            AND created_at > DATE_SUB(%s, INTERVAL 30 DAY)
            ORDER BY created_at DESC 
            LIMIT 1",
            $email['from'],
            $email['subject'],
            '%' . $email['subject'] . '%',
            $this->currentTime
        ));
    }

    private function createNewTicket(array $email, array $analysis): int
    {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Generate ticket number
            $ticketNumber = $this->generateTicketNumber();

            // Insert ticket
            $wpdb->insert(
                $wpdb->prefix . 'wpwps_tickets',
                [
                    'ticket_number' => $ticketNumber,
                    'customer_email' => $email['from'],
                    'subject' => $email['subject'],
                    'content' => $email['body'],
                    'status' => 'new',
                    'priority' => $analysis['priority'],
                    'order_id' => $analysis['order_id'],
                    'created_at' => $this->currentTime,
                    'updated_at' => $this->currentTime
                ]
            );

            $ticketId = $wpdb->insert_id;

            // Handle attachments
            if (!empty($email['attachments'])) {
                $this->processAttachments($ticketId, $email['attachments']);
            }

            // Send auto-response if needed
            if ($analysis['requires_auto_response']) {
                $this->sendAutoResponse($ticketId, $analysis);
            }

            $wpdb->query('COMMIT');

            $this->log('info', 'New ticket created', [
                'ticket_number' => $ticketNumber,
                'analysis' => $analysis
            ]);

            return $ticketId;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    private function addReplyToTicket(int $ticketId, array $email, array $analysis): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_ticket_messages',
            [
                'ticket_id' => $ticketId,
                'message_type' => 'customer_reply',
                'content' => $email['body'],
                'created_by' => $email['from'],
                'created_at' => $this->currentTime
            ]
        );

        // Update ticket status
        $wpdb->update(
            $wpdb->prefix . 'wpwps_tickets',
            [
                'status' => 'customer_reply',
                'updated_at' => $this->currentTime
            ],
            ['id' => $ticketId]
        );

        // Handle attachments
        if (!empty($email['attachments'])) {
            $this->processAttachments($ticketId, $email['attachments']);
        }

        $this->log('info', 'Reply added to ticket', [
            'ticket_id' => $ticketId,
            'analysis' => $analysis
        ]);
    }

    private function sendAutoResponse(int $ticketId, array $analysis): void
    {
        $ticket = $this->getTicket($ticketId);

        // Get appropriate template based on analysis
        $template = $this->getAutoResponseTemplate($analysis);

        // Send email
        wp_mail(
            $ticket['customer_email'],
            "Re: {$ticket['subject']} [#{$ticket['ticket_number']}]",
            $template['body'],
            $template['headers']
        );

        // Log auto-response
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_ticket_messages',
            [
                'ticket_id' => $ticketId,
                'message_type' => 'auto_response',
                'content' => $template['body'],
                'created_by' => 'system',
                'created_at' => $this->currentTime
            ]
        );
    }

    private function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $date = date('Ymd', strtotime($this->currentTime));
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$date}-{$random}";
    }

    // ... continue with more methods
}