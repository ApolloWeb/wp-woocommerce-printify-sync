<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class EmailService
{
    private string $currentTime = '2025-03-15 19:07:08';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        // Register WooCommerce email
        add_filter('woocommerce_email_classes', [$this, 'registerEmails']);
        
        // Add custom email actions
        add_action('wpwps_ticket_notification', [$this, 'sendTicketNotification'], 10, 2);
    }

    public function registerEmails(array $emails): array
    {
        $emails['wpwps_ticket'] = new \ApolloWeb\WPWooCommercePrintifySync\Email\TicketEmailTemplate();
        return $emails;
    }

    public function sendTicketNotification(int $ticket_id, array $args = []): void
    {
        $mailer = WC()->mailer();
        $email = $mailer->emails['wpwps_ticket'];
        $email->trigger($ticket_id, $args);
    }

    public function sendTicketConfirmation(int $ticketId): void
    {
        do_action('wpwps_ticket_notification', $ticketId, [
            'email_type' => 'confirmation'
        ]);
    }

    public function sendTicketReply(int $ticketId, string $message): void
    {
        do_action('wpwps_ticket_notification', $ticketId, [
            'email_type' => 'reply',
            'message' => $message
        ]);
    }
}