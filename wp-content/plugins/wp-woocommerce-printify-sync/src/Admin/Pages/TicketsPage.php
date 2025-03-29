<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;
use ApolloWeb\WPWooCommercePrintifySync\Tickets\TicketManager;

class TicketsPage
{
    private TicketManager $ticketManager;

    public function __construct()
    {
        $this->ticketManager = new TicketManager();
    }

    public function register(): void
    {
        add_submenu_page(
            'wpwps-dashboard',
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            __('Support', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-tickets',
            [$this, 'render']
        );

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void
    {
        $action = $_GET['action'] ?? 'list';
        $ticketId = (int)($_GET['ticket_id'] ?? 0);

        switch ($action) {
            case 'view':
                $this->renderTicketView($ticketId);
                break;
            case 'new':
                $this->renderNewTicketForm();
                break;
            default:
                $this->renderTicketList();
                break;
        }
    }

    private function renderTicketList(): void
    {
        $tickets = $this->ticketManager->getTickets();
        echo View::render('wpwps-tickets', [
            'title' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
            'tickets' => $tickets
        ]);
    }

    private function renderTicketView(int $ticketId): void
    {
        $ticket = $this->ticketManager->getTicket($ticketId);
        if (!$ticket) {
            wp_redirect(admin_url('admin.php?page=wpwps-tickets'));
            exit;
        }

        echo View::render('wpwps-ticket-view', [
            'title' => __('View Ticket', 'wp-woocommerce-printify-sync'),
            'ticket' => $ticket
        ]);
    }

    private function renderNewTicketForm(): void
    {
        echo View::render('wpwps-ticket-new', [
            'title' => __('New Support Ticket', 'wp-woocommerce-printify-sync')
        ]);
    }

    public function enqueueAssets(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpwps-tickets') {
            return;
        }

        wp_enqueue_style('wpwps-tickets', WPWPS_URL . 'assets/css/wpwps-tickets.css', [], WPWPS_VERSION);
        wp_enqueue_script('wpwps-tickets', WPWPS_URL . 'assets/js/wpwps-tickets.js', ['jquery'], WPWPS_VERSION, true);
        wp_localize_script('wpwps-tickets', 'wpwpsTickets', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin-nonce')
        ]);
    }
}