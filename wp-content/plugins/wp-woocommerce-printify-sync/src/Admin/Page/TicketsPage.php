<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Page;

use ApolloWeb\WPWooCommercePrintifySync\Service\{
    TicketService,
    EmailService,
    OrderService
};

class TicketsPage extends AbstractAdminPage
{
    private TicketService $ticketService;
    private EmailService $emailService;
    private OrderService $orderService;

    public function __construct(
        TicketService $ticketService,
        EmailService $emailService,
        OrderService $orderService
    ) {
        $this->ticketService = $ticketService;
        $this->emailService = $emailService;
        $this->orderService = $orderService;
    }

    public function getTitle(): string
    {
        return __('Support Tickets', 'wp-woocommerce-printify-sync');
    }

    public function getMenuTitle(): string
    {
        $pendingCount = $this->ticketService->getPendingCount();
        return sprintf(
            __('Tickets %s', 'wp-woocommerce-printify-sync'),
            $pendingCount ? "<span class='awaiting-mod'>{$pendingCount}</span>" : ''
        );
    }

    public function getCapability(): string
    {
        return 'manage_woocommerce';
    }

    public function getMenuSlug(): string
    {
        return 'wpwps-tickets';
    }

    public function register(): void
    {
        parent::register();
        add_action('wp_ajax_wpwps_reply_ticket', [$this, 'handleTicketReply']);
        add_action('wp_ajax_wpwps_close_ticket', [$this, 'handleTicketClose']);
        add_action('wp_ajax_wpwps_reopen_ticket', [$this, 'handleTicketReopen']);
    }

    public function render(): void
    {
        if (!current_user_can($this->getCapability())) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $currentStatus = sanitize_text_field($_GET['status'] ?? 'open');
        $currentPage = max(1, (int)($_GET['paged'] ?? 1));
        $search = sanitize_text_field($_GET['s'] ?? '');

        $ticketsList = $this->ticketService->getTickets([
            'status' => $currentStatus,
            'page' => $currentPage,
            'search' => $search,
            'per_page' => 20
        ]);

        $this->renderTemplate('tickets', [
            'tickets' => $ticketsList['tickets'],
            'pagination' => $ticketsList['pagination'],
            'stats' => $this->ticketService->getStats(),
            'statuses' => $this->ticketService->getStatuses(),
            'currentStatus' => $currentStatus,
            'search' => $search,
            'templates' => $this->emailService->getTemplates()
        ]);
    }

    public function handleTicketReply(): void
    {
        check_ajax_referer('wpwps_tickets', 'nonce');

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = wp_kses_post($_POST['message'] ?? '');
        $templateId = sanitize_text_field($_POST['template_id'] ?? '');

        try {
            $result = $this->ticketService->replyToTicket($ticketId, $message, $templateId);
            wp_send_json_success([
                'message' => __('Reply sent successfully!', 'wp-woocommerce-printify-sync'),
                'ticket' => $result
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // Similar handlers for close and reopen...
}