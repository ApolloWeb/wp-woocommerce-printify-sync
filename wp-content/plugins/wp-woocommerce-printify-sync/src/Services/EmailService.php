<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Tickets\Ticket;

class EmailService
{
    public function sendTicketCreatedNotification(Ticket $ticket): void
    {
        $adminEmail = get_option('admin_email');
        $subject = sprintf(
            '[%s] New Support Ticket: %s',
            get_bloginfo('name'),
            $ticket->getSubject()
        );

        $message = $this->buildTicketCreatedEmail($ticket);
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        wp_mail($adminEmail, $subject, $message, $headers);
    }

    public function sendTicketResponseNotification(Ticket $ticket, array $response): void
    {
        $user = get_userdata($ticket->getUserId());
        if (!$user || !$user->user_email) {
            return;
        }

        $subject = sprintf(
            '[%s] Response to Ticket #%d: %s',
            get_bloginfo('name'),
            $ticket->getId(),
            $ticket->getSubject()
        );

        $message = $this->buildTicketResponseEmail($ticket, $response);
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        wp_mail($user->user_email, $subject, $message, $headers);
    }

    private function buildTicketCreatedEmail(Ticket $ticket): string
    {
        $user = get_userdata($ticket->getUserId());
        $template = file_get_contents(WPWPS_PATH . 'templates/emails/ticket-created.php');

        return strtr($template, [
            '{{site_name}}' => get_bloginfo('name'),
            '{{ticket_id}}' => (string)$ticket->getId(),
            '{{subject}}' => $ticket->getSubject(),
            '{{message}}' => nl2br($ticket->getMessage()),
            '{{user_name}}' => $user ? $user->display_name : 'Unknown',
            '{{admin_url}}' => admin_url("admin.php?page=wpwps-tickets&action=view&ticket_id={$ticket->getId()}")
        ]);
    }

    private function buildTicketResponseEmail(Ticket $ticket, array $response): string
    {
        $user = get_userdata($response['user_id']);
        $template = file_get_contents(WPWPS_PATH . 'templates/emails/ticket-response.php');

        return strtr($template, [
            '{{site_name}}' => get_bloginfo('name'),
            '{{ticket_id}}' => (string)$ticket->getId(),
            '{{subject}}' => $ticket->getSubject(),
            '{{response}}' => nl2br($response['message']),
            '{{responder_name}}' => $user ? $user->display_name : 'Unknown',
            '{{admin_url}}' => admin_url("admin.php?page=wpwps-tickets&action=view&ticket_id={$ticket->getId()}")
        ]);
    }
}