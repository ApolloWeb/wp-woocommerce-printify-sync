<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class TicketingService
{
    private string $currentTime = '2025-03-15 19:01:40';
    private string $currentUser = 'ApolloWeb';
    private EmailService $emailService;
    private AIService $aiService;

    public function __construct()
    {
        $this->emailService = new EmailService();
        $this->aiService = new AIService();
        
        add_action('wp_ajax_wpwps_create_ticket', [$this, 'createTicket']);
        add_action('wp_ajax_wpwps_update_ticket', [$this, 'updateTicket']);
        add_action('wpwps_process_email_queue', [$this, 'processEmailQueue']);
    }

    public function createTicket(array $data): int
    {
        global $wpdb;

        $ticketData = [
            'subject' => sanitize_text_field($data['subject']),
            'customer_email' => sanitize_email($data['customer_email']),
            'status' => 'open',
            'priority' => $this->determinePriority($data),
            'order_id' => $data['order_id'] ?? null,
            'created_at' => $this->currentTime,
            'created_by' => $this->currentUser
        ];

        $wpdb->insert($wpdb->prefix . 'wpwps_tickets', $ticketData);
        $ticketId = $wpdb->insert_id;

        // Create initial message
        $this->addMessage($ticketId, $data['message'], $data['attachments'] ?? []);

        // Send confirmation email
        $this->emailService->sendTicketConfirmation($ticketId);

        return $ticketId;
    }

    private function determinePriority(array $data): string
    {
        // Use AI to analyze message content and determine priority
        $content = $data['message'] . ' ' . ($data['subject'] ?? '');
        $analysis = $this->aiService->analyzeTicketContent($content);

        if (str_contains(strtolower($content), 'refund')) {
            return 'high';
        }

        return $analysis['priority'] ?? 'normal';
    }

    public function addMessage(int $ticketId, string $message, array $attachments = []): int
    {
        global $wpdb;

        $messageData = [
            'ticket_id' => $ticketId,
            'message' => wp_kses_post($message),
            'created_at' => $this->currentTime,
            'created_by' => $this->currentUser
        ];

        $wpdb->insert($wpdb->prefix . 'wpwps_ticket_messages', $messageData);
        $messageId = $wpdb->insert_id;

        // Handle attachments
        if (!empty($attachments)) {
            $this->processAttachments($messageId, $attachments);
        }

        return $messageId;
    }

    private function processAttachments(int $messageId, array $attachments): void
    {
        global $wpdb;

        foreach ($attachments as $attachment) {
            $attachmentData = [
                'message_id' => $messageId,
                'file_name' => sanitize_file_name($attachment['name']),
                'file_path' => $attachment['path'],
                'file_type' => $attachment['type'],
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ];

            $wpdb->insert($wpdb->prefix . 'wpwps_ticket_attachments', $attachmentData);
        }
    }
}