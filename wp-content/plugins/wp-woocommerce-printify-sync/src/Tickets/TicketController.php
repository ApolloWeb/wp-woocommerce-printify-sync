<?php
/**
 * Ticket Controller.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Tickets
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Tickets;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\TemplateService;

/**
 * Ticket Controller class for handling ticket-related Ajax calls and admin pages.
 */
class TicketController
{
    /**
     * Ticket service instance.
     *
     * @var TicketService
     */
    private $ticket_service;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Template service instance.
     *
     * @var TemplateService
     */
    private $template;

    /**
     * Constructor.
     *
     * @param TicketService    $ticket_service Ticket service instance.
     * @param Logger           $logger         Logger instance.
     * @param TemplateService  $template       Template service instance.
     */
    public function __construct(
        TicketService $ticket_service,
        Logger $logger,
        TemplateService $template
    ) {
        $this->ticket_service = $ticket_service;
        $this->logger = $logger;
        $this->template = $template;
    }

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function init()
    {
        // No initialization needed at this time
    }

    /**
     * Get tickets via AJAX.
     *
     * @return void
     */
    public function getTickets()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $per_page = 10;

        // Get tickets
        $tickets = $this->ticket_service->getTickets([
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'status' => $status,
            'search' => $search,
        ]);

        // Count total tickets
        $total_tickets = $this->ticket_service->getTicketCount($status);
        $total_pages = ceil($total_tickets / $per_page);

        // Format pagination
        $pagination = [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_tickets' => $total_tickets,
            'showing_text' => sprintf(
                /* translators: %1$d: first item, %2$d: last item, %3$d: total items */
                __('Showing %1$d to %2$d of %3$d tickets', 'wp-woocommerce-printify-sync'),
                ($page - 1) * $per_page + 1,
                min($page * $per_page, $total_tickets),
                $total_tickets
            ),
        ];

        // Format tickets for display
        $formatted_tickets = [];
        foreach ($tickets as $ticket) {
            $formatted_tickets[] = $this->formatTicketForDisplay($ticket);
        }

        wp_send_json_success([
            'tickets' => $formatted_tickets,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Get a single ticket via AJAX.
     *
     * @return void
     */
    public function getTicket()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;

        if (!$ticket_id) {
            wp_send_json_error([
                'message' => __('Ticket ID is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $ticket = $this->ticket_service->getTicket($ticket_id);

        if (!$ticket) {
            wp_send_json_error([
                'message' => __('Ticket not found.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Format ticket for display
        $formatted_ticket = $this->formatTicketDetailsForDisplay($ticket);

        wp_send_json_success([
            'ticket' => $formatted_ticket,
        ]);
    }

    /**
     * Create a ticket via AJAX.
     *
     * @return void
     */
    public function createTicket()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
        $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';
        $urgency = isset($_POST['urgency']) ? sanitize_text_field(wp_unslash($_POST['urgency'])) : 'medium';

        // Create ticket
        $result = $this->ticket_service->createTicket([
            'customer_id' => $customer_id,
            'subject' => $subject,
            'content' => $content,
            'order_id' => $order_id,
            'category' => $category,
            'urgency' => $urgency,
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
            return;
        }

        wp_send_json_success([
            'message' => __('Ticket created successfully.', 'wp-woocommerce-printify-sync'),
            'ticket_id' => $result,
        ]);
    }

    /**
     * Update a ticket via AJAX.
     *
     * @return void
     */
    public function updateTicket()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;

        if (!$ticket_id) {
            wp_send_json_error([
                'message' => __('Ticket ID is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : null;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : null;
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : null;
        $urgency = isset($_POST['urgency']) ? sanitize_text_field(wp_unslash($_POST['urgency'])) : null;

        // Update ticket
        $result = $this->ticket_service->updateTicket($ticket_id, [
            'subject' => $subject,
            'status' => $status,
            'category' => $category,
            'urgency' => $urgency,
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
            return;
        }

        wp_send_json_success([
            'message' => __('Ticket updated successfully.', 'wp-woocommerce-printify-sync'),
        ]);
    }

    /**
     * Add a reply to a ticket via AJAX.
     *
     * @return void
     */
    public function addTicketReply()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $is_from_customer = isset($_POST['is_from_customer']) ? (bool) $_POST['is_from_customer'] : false;

        if (!$ticket_id) {
            wp_send_json_error([
                'message' => __('Ticket ID is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        if (empty($content)) {
            wp_send_json_error([
                'message' => __('Reply content is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Add reply
        $result = $this->ticket_service->addTicketReply($ticket_id, [
            'content' => $content,
            'is_from_customer' => $is_from_customer,
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
            return;
        }

        // Get updated ticket
        $ticket = $this->ticket_service->getTicket($ticket_id);
        $formatted_ticket = $this->formatTicketDetailsForDisplay($ticket);

        wp_send_json_success([
            'message' => __('Reply added successfully.', 'wp-woocommerce-printify-sync'),
            'ticket' => $formatted_ticket,
        ]);
    }

    /**
     * Format a ticket for display in the admin.
     *
     * @param array $ticket Ticket data.
     * @return array Formatted ticket data.
     */
    private function formatTicketForDisplay($ticket)
    {
        $ticket_id = $ticket['id'];
        $customer = get_user_by('id', $ticket['customer_id']);
        $order = !empty($ticket['order_id']) ? wc_get_order($ticket['order_id']) : null;

        return [
            'id' => $ticket_id,
            'subject' => $ticket['subject'],
            'status' => $ticket['status'],
            'category' => $ticket['category'],
            'urgency' => $ticket['urgency'],
            'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['created_at'])),
            'updated_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['updated_at'])),
            'customer' => $customer ? $customer->display_name : __('Unknown', 'wp-woocommerce-printify-sync'),
            'order_number' => $order ? $order->get_order_number() : '',
            'order_link' => $order ? admin_url('post.php?post=' . $order->get_id() . '&action=edit') : '',
            'is_refund_request' => (bool) $ticket['is_refund_request'],
            'is_reprint_request' => (bool) $ticket['is_reprint_request'],
        ];
    }

    /**
     * Format detailed ticket data for display in the admin.
     *
     * @param array $ticket Ticket data.
     * @return array Formatted ticket data.
     */
    private function formatTicketDetailsForDisplay($ticket)
    {
        $ticket_id = $ticket['id'];
        $customer = get_user_by('id', $ticket['customer_id']);
        $order = !empty($ticket['order_id']) ? wc_get_order($ticket['order_id']) : null;

        // Format replies
        $replies = [];
        foreach ($ticket['replies'] as $reply) {
            $user = get_user_by('id', $reply['user_id']);
            $replies[] = [
                'id' => $reply['id'],
                'content' => $reply['content'],
                'user' => $user ? $user->display_name : __('Unknown', 'wp-woocommerce-printify-sync'),
                'is_from_customer' => (bool) $reply['is_from_customer'],
                'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($reply['created_at'])),
            ];
        }

        return [
            'id' => $ticket_id,
            'subject' => $ticket['subject'],
            'content' => $ticket['content'],
            'status' => $ticket['status'],
            'category' => $ticket['category'],
            'urgency' => $ticket['urgency'],
            'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['created_at'])),
            'updated_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['updated_at'])),
            'customer' => [
                'id' => $ticket['customer_id'],
                'name' => $customer ? $customer->display_name : __('Unknown', 'wp-woocommerce-printify-sync'),
                'email' => $customer ? $customer->user_email : '',
            ],
            'order' => $order ? [
                'id' => $order->get_id(),
                'number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'link' => admin_url('post.php?post=' . $order->get_id() . '&action=edit'),
                'printify_id' => get_post_meta($order->get_id(), '_printify_order_id', true),
            ] : null,
            'is_refund_request' => (bool) $ticket['is_refund_request'],
            'is_reprint_request' => (bool) $ticket['is_reprint_request'],
            'replies' => $replies,
        ];
    }
}
