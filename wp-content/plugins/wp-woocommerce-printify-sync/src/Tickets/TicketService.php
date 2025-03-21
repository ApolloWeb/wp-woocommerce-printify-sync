<?php
/**
 * Ticket Service.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Tickets
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Tickets;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService;
use ApolloWeb\WPWooCommercePrintifySync\API\ChatGPTClient;
use WP_Error;

/**
 * Ticket Service class for handling support tickets.
 */
class TicketService
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Activity service instance.
     *
     * @var ActivityService
     */
    private $activity_service;

    /**
     * ChatGPT client instance.
     *
     * @var ChatGPTClient
     */
    private $chatgpt_client;

    /**
     * Constructor.
     *
     * @param Logger         $logger          Logger instance.
     * @param ActivityService $activity_service Activity service instance.
     * @param ChatGPTClient  $chatgpt_client  ChatGPT client instance.
     */
    public function __construct(
        Logger $logger,
        ActivityService $activity_service,
        ChatGPTClient $chatgpt_client
    ) {
        $this->logger = $logger;
        $this->activity_service = $activity_service;
        $this->chatgpt_client = $chatgpt_client;
    }

    /**
     * Get all tickets.
     *
     * @param array $args Query arguments.
     * @return array Tickets.
     */
    public function getTickets($args = [])
    {
        global $wpdb;
        
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'status' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        $query = "SELECT * FROM {$table_name} WHERE 1=1";
        $params = [];
        
        if (!empty($args['status'])) {
            $query .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $query .= " AND (subject LIKE %s OR content LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        // Add orderby and order
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Add limit and offset
        $query .= " LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $tickets = $wpdb->get_results($query, ARRAY_A);
        
        return is_array($tickets) ? $tickets : [];
    }

    /**
     * Get ticket count.
     *
     * @param string $status Optional ticket status.
     * @return int Ticket count.
     */
    public function getTicketCount($status = '')
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        $query = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1";
        $params = [];
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return (int) $wpdb->get_var($query);
    }

    /**
     * Get a ticket by ID.
     *
     * @param int $ticket_id Ticket ID.
     * @return array|false Ticket data or false if not found.
     */
    public function getTicket($ticket_id)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $ticket_id);
        
        $ticket = $wpdb->get_row($query, ARRAY_A);
        
        if (!$ticket) {
            return false;
        }
        
        // Get ticket replies
        $ticket['replies'] = $this->getTicketReplies($ticket_id);
        
        return $ticket;
    }

    /**
     * Get ticket replies.
     *
     * @param int $ticket_id Ticket ID.
     * @return array Ticket replies.
     */
    public function getTicketReplies($ticket_id)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_ticket_replies';
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE ticket_id = %d ORDER BY created_at ASC",
            $ticket_id
        );
        
        $replies = $wpdb->get_results($query, ARRAY_A);
        
        return is_array($replies) ? $replies : [];
    }

    /**
     * Create a new ticket.
     *
     * @param array $ticket_data Ticket data.
     * @return int|WP_Error Ticket ID or error.
     */
    public function createTicket($ticket_data)
    {
        global $wpdb;
        
        $this->logger->info('Creating new support ticket');
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        // Validate required fields
        if (empty($ticket_data['customer_id'])) {
            return new WP_Error('missing_customer', __('Customer ID is required.', 'wp-woocommerce-printify-sync'));
        }
        
        if (empty($ticket_data['subject'])) {
            return new WP_Error('missing_subject', __('Subject is required.', 'wp-woocommerce-printify-sync'));
        }
        
        if (empty($ticket_data['content'])) {
            return new WP_Error('missing_content', __('Content is required.', 'wp-woocommerce-printify-sync'));
        }
        
        // Set default values
        $ticket_data = wp_parse_args($ticket_data, [
            'order_id' => null,
            'printify_order_id' => null,
            'status' => 'open',
            'category' => null,
            'urgency' => 'medium',
            'is_refund_request' => 0,
            'is_reprint_request' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        
        // Use ChatGPT to analyze the ticket content if available
        if ($this->chatgpt_client->getApiKey()) {
            try {
                $analysis = $this->chatgpt_client->analyzeTicketContent($ticket_data['content']);
                
                if (!is_wp_error($analysis) && isset($analysis['parsed_data'])) {
                    // Update ticket data with AI analysis
                    if (!empty($analysis['parsed_data']['order_number']) && empty($ticket_data['order_id'])) {
                        // Try to find order ID by number
                        $order_id = wc_get_order_id_by_order_number($analysis['parsed_data']['order_number']);
                        if ($order_id) {
                            $ticket_data['order_id'] = $order_id;
                            
                            // Try to get Printify order ID
                            $printify_order_id = get_post_meta($order_id, '_printify_order_id', true);
                            if ($printify_order_id) {
                                $ticket_data['printify_order_id'] = $printify_order_id;
                            }
                        }
                    }
                    
                    if (!empty($analysis['parsed_data']['urgency'])) {
                        $ticket_data['urgency'] = $analysis['parsed_data']['urgency'];
                    }
                    
                    if (!empty($analysis['parsed_data']['issue_type'])) {
                        $ticket_data['category'] = $analysis['parsed_data']['issue_type'];
                    }
                    
                    if (isset($analysis['parsed_data']['is_refund_request'])) {
                        $ticket_data['is_refund_request'] = $analysis['parsed_data']['is_refund_request'] ? 1 : 0;
                    }
                    
                    if (isset($analysis['parsed_data']['is_reprint_request'])) {
                        $ticket_data['is_reprint_request'] = $analysis['parsed_data']['is_reprint_request'] ? 1 : 0;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Error analyzing ticket content: ' . $e->getMessage());
            }
        }
        
        // Insert ticket into database
        $result = $wpdb->insert(
            $table_name,
            [
                'customer_id' => $ticket_data['customer_id'],
                'order_id' => $ticket_data['order_id'],
                'printify_order_id' => $ticket_data['printify_order_id'],
                'subject' => $ticket_data['subject'],
                'content' => $ticket_data['content'],
                'status' => $ticket_data['status'],
                'category' => $ticket_data['category'],
                'urgency' => $ticket_data['urgency'],
                'is_refund_request' => $ticket_data['is_refund_request'],
                'is_reprint_request' => $ticket_data['is_reprint_request'],
                'created_at' => $ticket_data['created_at'],
                'updated_at' => $ticket_data['updated_at'],
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
            ]
        );
        
        if ($result === false) {
            $this->logger->error('Failed to create ticket: ' . $wpdb->last_error);
            return new WP_Error('db_error', __('Failed to create ticket.', 'wp-woocommerce-printify-sync'));
        }
        
        $ticket_id = $wpdb->insert_id;
        
        $this->activity_service->log('ticket', sprintf(
            __('Created new ticket: "%s"', 'wp-woocommerce-printify-sync'),
            $ticket_data['subject']
        ), [
            'ticket_id' => $ticket_id,
            'customer_id' => $ticket_data['customer_id'],
            'order_id' => $ticket_data['order_id'],
            'time' => current_time('mysql')
        ]);
        
        return $ticket_id;
    }

    /**
     * Update a ticket.
     *
     * @param int   $ticket_id   Ticket ID.
     * @param array $ticket_data Ticket data.
     * @return bool|WP_Error True on success or error.
     */
    public function updateTicket($ticket_id, $ticket_data)
    {
        global $wpdb;
        
        $this->logger->info("Updating ticket {$ticket_id}");
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        // Get current ticket data
        $current_ticket = $this->getTicket($ticket_id);
        
        if (!$current_ticket) {
            return new WP_Error('ticket_not_found', __('Ticket not found.', 'wp-woocommerce-printify-sync'));
        }
        
        // Update ticket data
        $update_data = [];
        $update_format = [];
        
        if (isset($ticket_data['subject'])) {
            $update_data['subject'] = $ticket_data['subject'];
            $update_format[] = '%s';
        }
        
        if (isset($ticket_data['content'])) {
            $update_data['content'] = $ticket_data['content'];
            $update_format[] = '%s';
        }
        
        if (isset($ticket_data['status'])) {
            $update_data['status'] = $ticket_data['status'];
            $update_format[] = '%s';
        }
        
        if (isset($ticket_data['category'])) {
            $update_data['category'] = $ticket_data['category'];
            $update_format[] = '%s';
        }
        
        if (isset($ticket_data['urgency'])) {
            $update_data['urgency'] = $ticket_data['urgency'];
            $update_format[] = '%s';
        }
        
        if (isset($ticket_data['is_refund_request'])) {
            $update_data['is_refund_request'] = $ticket_data['is_refund_request'] ? 1 : 0;
            $update_format[] = '%d';
        }
        
        if (isset($ticket_data['is_reprint_request'])) {
            $update_data['is_reprint_request'] = $ticket_data['is_reprint_request'] ? 1 : 0;
            $update_format[] = '%d';
        }
        
        if (isset($ticket_data['order_id'])) {
            $update_data['order_id'] = $ticket_data['order_id'];
            $update_format[] = '%d';
        }
        
        if (isset($ticket_data['printify_order_id'])) {
            $update_data['printify_order_id'] = $ticket_data['printify_order_id'];
            $update_format[] = '%s';
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        // Update ticket
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $ticket_id],
            $update_format,
            ['%d']
        );
        
        if ($result === false) {
            $this->logger->error('Failed to update ticket: ' . $wpdb->last_error);
            return new WP_Error('db_error', __('Failed to update ticket.', 'wp-woocommerce-printify-sync'));
        }
        
        $this->activity_service->log('ticket', sprintf(
            __('Updated ticket: "%s"', 'wp-woocommerce-printify-sync'),
            $current_ticket['subject']
        ), [
            'ticket_id' => $ticket_id,
            'time' => current_time('mysql')
        ]);
        
        return true;
    }

    /**
     * Add a reply to a ticket.
     *
     * @param int   $ticket_id  Ticket ID.
     * @param array $reply_data Reply data.
     * @return int|WP_Error Reply ID or error.
     */
    public function addTicketReply($ticket_id, $reply_data)
    {
        global $wpdb;
        
        $this->logger->info("Adding reply to ticket {$ticket_id}");
        
        $table_name = $wpdb->prefix . 'wpwps_ticket_replies';
        
        // Get current ticket data
        $current_ticket = $this->getTicket($ticket_id);
        
        if (!$current_ticket) {
            return new WP_Error('ticket_not_found', __('Ticket not found.', 'wp-woocommerce-printify-sync'));
        }
        
        // Validate required fields
        if (empty($reply_data['content'])) {
            return new WP_Error('missing_content', __('Reply content is required.', 'wp-woocommerce-printify-sync'));
        }
        
        // Set default values
        $reply_data = wp_parse_args($reply_data, [
            'user_id' => get_current_user_id(),
            'is_from_customer' => 0,
            'created_at' => current_time('mysql'),
        ]);
        
        // Insert reply into database
        $result = $wpdb->insert(
            $table_name,
            [
                'ticket_id' => $ticket_id,
                'user_id' => $reply_data['user_id'],
                'content' => $reply_data['content'],
                'is_from_customer' => $reply_data['is_from_customer'],
                'created_at' => $reply_data['created_at'],
            ],
            [
                '%d',
                '%d',
                '%s',
                '%d',
                '%s',
            ]
        );
        
        if ($result === false) {
            $this->logger->error('Failed to add ticket reply: ' . $wpdb->last_error);
            return new WP_Error('db_error', __('Failed to add ticket reply.', 'wp-woocommerce-printify-sync'));
        }
        
        $reply_id = $wpdb->insert_id;
        
        // Update ticket updated_at timestamp
        $this->updateTicket($ticket_id, []);
        
        // Update ticket status if it was closed and customer replied
        if ($current_ticket['status'] === 'closed' && $reply_data['is_from_customer']) {
            $this->updateTicket($ticket_id, ['status' => 'open']);
        }
        
        $this->activity_service->log('ticket', sprintf(
            __('Added reply to ticket: "%s"', 'wp-woocommerce-printify-sync'),
            $current_ticket['subject']
        ), [
            'ticket_id' => $ticket_id,
            'reply_id' => $reply_id,
            'user_id' => $reply_data['user_id'],
            'time' => current_time('mysql')
        ]);
        
        return $reply_id;
    }

    /**
     * Generate a suggested reply for a ticket using ChatGPT.
     *
     * @param int $ticket_id Ticket ID.
     * @return string|WP_Error Suggested reply or error.
     */
    public function generateSuggestedReply($ticket_id)
    {
        $this->logger->info("Generating suggested reply for ticket {$ticket_id}");
        
        // Check if ChatGPT API is configured
        if (!$this->chatgpt_client->getApiKey()) {
            return new WP_Error('chatgpt_not_configured', __('ChatGPT API is not configured.', 'wp-woocommerce-printify-sync'));
        }
        
        // Get ticket data
        $ticket = $this->getTicket($ticket_id);
        
        if (!$ticket) {
            return new WP_Error('ticket_not_found', __('Ticket not found.', 'wp-woocommerce-printify-sync'));
        }
        
        // Get order information if available
        $order_info = '';
        if (!empty($ticket['order_id'])) {
            $order = wc_get_order($ticket['order_id']);
            if ($order) {
                $order_number = $order->get_order_number();
                $products = [];
                foreach ($order->get_items() as $item) {
                    $products[] = $item->get_name() . ' x ' . $item->get_quantity();
                }
                $order_info = "Order #{$order_number}, Products: " . implode(', ', $products);
            }
        }
        
        // Prepare ticket data for ChatGPT
        $ticket_data = [
            'content' => $ticket['content'],
            'order_id' => $order_info ? $order_number : '',
            'products' => $order_info ? implode(', ', $products) : '',
            'issue_type' => $ticket['category'] ?? '',
        ];
        
        // Generate suggested reply
        $response = $this->chatgpt_client->generateTicketResponse($ticket_data);
        
        if (is_wp_error($response)) {
            $this->logger->error("Error generating suggested reply: " . $response->get_error_message());
            return $response;
        }
        
        return $response['suggested_response'] ?? '';
    }
}
