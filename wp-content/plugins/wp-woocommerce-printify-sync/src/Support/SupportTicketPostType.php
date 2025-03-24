<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

use ApolloWeb\WPWooCommercePrintifySync\Support\Interfaces\{
    TicketRepositoryInterface,
    ThreadManagerInterface,
    TicketMailerInterface
};
use ApolloWeb\WPWooCommercePrintifySync\Core\EventDispatcherInterface;

class SupportTicketPostType {
    private $settings;
    private $ticketRepository;
    private $threadManager;
    private $ticketMailer;
    private $eventDispatcher;

    public function __construct(
        Settings $settings,
        TicketRepositoryInterface $ticketRepository,
        ThreadManagerInterface $threadManager, 
        TicketMailerInterface $ticketMailer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->settings = $settings;
        $this->ticketRepository = $ticketRepository;
        $this->threadManager = $threadManager;
        $this->ticketMailer = $ticketMailer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function register(): void {
        register_post_type('support_ticket', [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync')
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-tickets-alt'
        ]);

        register_taxonomy('ticket_category', 'support_ticket', [
            'label' => __('Categories', 'wp-woocommerce-printify-sync'),
            'hierarchical' => true
        ]);

        register_taxonomy('ticket_status', 'support_ticket', [
            'label' => __('Status', 'wp-woocommerce-printify-sync'),
            'hierarchical' => false
        ]);
    }

    public function addMetaBoxes(): void {
        add_meta_box(
            'ticket_details',
            __('Ticket Details', 'wp-woocommerce-printify-sync'),
            [$this, 'renderTicketDetails'],
            'support_ticket',
            'normal',
            'high'
        );

        add_meta_box(
            'ticket_thread',
            __('Conversation', 'wp-woocommerce-printify-sync'),
            [$this, 'renderTicketThread'],
            'support_ticket',
            'normal',
            'high'
        );
    }

    public function renderTicketDetails($post): void {
        $ticket = $this->ticketRepository->find($post->ID);
        include WPWPS_PLUGIN_DIR . 'templates/admin/ticket-details.php';
    }

    public function renderTicketThread($post): void {
        $threads = $this->threadManager->getThreadsForTicket($post->ID);
        include WPWPS_PLUGIN_DIR . 'templates/admin/ticket-thread.php';
    }

    public function sendResponse(int $ticket_id, string $content, array $attachments = []): void {
        $ticket = $this->ticketRepository->find($ticket_id);
        
        // Save thread
        $thread = $this->threadManager->addThread($ticket_id, [
            'author_id' => get_current_user_id(),
            'content' => $content,
            'attachments' => $attachments
        ]);

        // Send email
        $this->ticketMailer->sendResponse($ticket, $content, $attachments);

        // Dispatch event
        $this->eventDispatcher->dispatch('ticket.replied', [
            'ticket_id' => $ticket_id,ticket_id,
            'author_id' => get_current_user_id(),
            'thread' => $thread
        ]);
    }
}
