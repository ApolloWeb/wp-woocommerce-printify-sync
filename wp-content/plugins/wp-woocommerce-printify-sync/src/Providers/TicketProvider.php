<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Tickets\TicketManager;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\TicketsPage;
use ApolloWeb\WPWooCommercePrintifySync\Services\EmailService;

class TicketProvider implements ServiceProvider
{
    private TicketManager $ticketManager;
    private EmailService $emailService;

    public function register(): void
    {
        $this->ticketManager = new TicketManager();
        $this->emailService = new EmailService();

        // Register the tickets page
        $ticketsPage = new TicketsPage();
        $ticketsPage->register();

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_create_ticket', [$this, 'handleCreateTicket']);
        add_action('wp_ajax_wpwps_update_ticket', [$this, 'handleUpdateTicket']);
        add_action('wp_ajax_wpwps_generate_response', [$this, 'handleGenerateResponse']);

        // Add ticket table on plugin activation
        register_activation_hook(WPWPS_FILE, [TicketManager::class, 'createTable']);
    }

    public function handleCreateTicket(): void
    {
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $data = [
            'subject' => sanitize_text_field($_POST['subject'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'order_id' => (int)($_POST['order_id'] ?? 0),
            'user_id' => get_current_user_id()
        ];

        $ticket = $this->ticketManager->createTicket($data);
        if ($ticket) {
            // Send notification
            $this->emailService->sendTicketCreatedNotification($ticket);
            wp_send_json_success(['ticket' => $ticket->toArray()]);
        }

        wp_send_json_error('Failed to create ticket');
    }

    public function handleUpdateTicket(): void
    {
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $response = sanitize_textarea_field($_POST['response'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');

        $ticket = $this->ticketManager->getTicket($ticketId);
        if (!$ticket) {
            wp_send_json_error('Ticket not found');
        }

        if ($response) {
            $responseData = [
                'message' => $response,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ];
            $ticket->addResponse($response, get_current_user_id());
            
            // Send notification
            $this->emailService->sendTicketResponseNotification($ticket, $responseData);
        }

        if ($status) {
            $ticket->setStatus($status);
        }

        if ($this->ticketManager->updateTicket($ticket)) {
            wp_send_json_success(['ticket' => $ticket->toArray()]);
        }

        wp_send_json_error('Failed to update ticket');
    }

    public function handleGenerateResponse(): void
    {
        check_ajax_referer('wpwps-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $ticket = $this->ticketManager->getTicket($ticketId);
        
        if (!$ticket) {
            wp_send_json_error('Ticket not found');
        }

        $response = $this->ticketManager->generateAIResponse($ticket);
        if ($response) {
            wp_send_json_success(['response' => $response]);
        }

        wp_send_json_error('Failed to generate response');
    }
}