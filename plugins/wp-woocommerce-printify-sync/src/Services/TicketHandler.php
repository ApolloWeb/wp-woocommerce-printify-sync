<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Utilities\OrderUtil;

class TicketHandler
{
    private const TICKET_POST_TYPE = 'wpwps_ticket';
    private const CURRENT_TIME = '2025-03-15 22:11:36';
    private const CURRENT_USER = 'ApolloWeb';

    private ConfigService $config;
    private LoggerInterface $logger;

    public function __construct(ConfigService $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->initHooks();
    }

    private function initHooks(): void
    {
        // Register ticket post type
        add_action('init', [$this, 'registerTicketPostType']);
        
        // Postie integration
        add_filter('postie_post_type', [$this, 'setPostiePostType']);
        add_filter('postie_category_default', [$this, 'setPostieCategory']);
        add_action('postie_post_after', [$this, 'handlePostieTicket']);

        // SMTP Mailer integration
        add_filter('wp_mail_smtp_options', [$this, 'configureSMTP']);
        
        // Ticket processing
        add_action('wpwps_process_ticket', [$this, 'processTicket']);
        add_action('transition_post_status', [$this, 'handleTicketStatusChange'], 10, 3);
    }

    public function registerTicketPostType(): void
    {
        register_post_type(self::TICKET_POST_TYPE, [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'woocommerce',
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
            'hierarchical' => false,
            'has_archive' => false,
            'menu_icon' => 'dashicons-tickets-alt',
            'rewrite' => false
        ]);

        register_taxonomy('ticket_status', self::TICKET_POST_TYPE, [
            'hierarchical' => true,
            'labels' => [
                'name' => __('Ticket Status', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Ticket Status', 'wp-woocommerce-printify-sync'),
            ],
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
        ]);
    }

    public function setPostiePostType(): string
    {
        return self::TICKET_POST_TYPE;
    }

    public function setPostieCategory(): string
    {
        return 'new';
    }

    public function handlePostieTicket(int $postId): void
    {
        $post = get_post($postId);
        if (!$post || $post->post_type !== self::TICKET_POST_TYPE) {
            return;
        }

        // Extract order information from email
        $orderId = $this->extractOrderId($post->post_content);
        if ($orderId) {
            update_post_meta($postId, '_wpwps_order_id', $orderId);
            
            // Link ticket to order
            $order = wc_get_order($orderId);
            if ($order) {
                $order->add_meta_data('_wpwps_ticket_id', $postId);
                $order->save();
            }
        }

        // Set initial ticket status
        wp_set_object_terms($postId, ['new'], 'ticket_status');

        // Trigger ticket processing
        do_action('wpwps_process_ticket', $postId);

        $this->logger->info('New ticket created from email', [
            'ticket_id' => $postId,
            'order_id' => $orderId,
            'timestamp' => self::CURRENT_TIME
        ]);
    }

    public function configureSMTP(array $options): array
    {
        // Configure SMTP settings if not already set
        if (!isset($options['mail']['from_email'])) {
            $options['mail']['from_email'] = $this->config->get('support_email');
        }
        if (!isset($options['mail']['from_name'])) {
            $options['mail']['from_name'] = get_bloginfo('name') . ' Support';
        }

        return $options;
    }

    public function processTicket(int $ticketId): void
    {
        try {
            $ticket = get_post($ticketId);
            if (!$ticket || $ticket->post_type !== self::TICKET_POST_TYPE) {
                throw new \Exception('Invalid ticket');
            }

            // Auto-categorize ticket
            $this->categorizeTicket($ticket);

            // Send confirmation email
            $this->sendTicketConfirmation($ticket);

            // Assign to appropriate team member
            $this->assignTicket($ticket);

            $this->logger->info('Ticket processed', [
                'ticket_id' => $ticketId,
                'timestamp' => self::CURRENT_TIME
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleTicketStatusChange(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        if ($post->post_type !== self::TICKET_POST_TYPE) {
            return;
        }

        if ($newStatus === $oldStatus) {
            return;
        }

        // Update ticket metadata
        update_post_meta($post->ID, '_wpwps_status_changed', self::CURRENT_TIME);
        update_post_meta($post->ID, '_wpwps_status_changed_by', self::CURRENT_USER);

        // Notify customer if resolved
        if ($newStatus === 'resolved') {
            $this->sendTicketResolutionEmail($post);
        }

        $this->logger->info('Ticket status changed', [
            'ticket_id' => $post->ID,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => self::CURRENT_USER,
            'timestamp' => self::CURRENT_TIME
        ]);
    }

    private function extractOrderId(string $content): ?int
    {
        // Look for order number in various formats
        $patterns = [
            '/Order\s*#?(\d+)/i',
            '/Order\s*ID:\s*(\d+)/i',
            '/Reference:\s*#?(\d+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                return (int)$matches[1];
            }
        }

        return null;
    }

    private function categorizeTicket(\WP_Post $ticket): void
    {
        $content = strtolower($ticket->post_content);
        
        // Basic categorization rules
        $categories = [
            'shipping' => ['shipping', 'delivery', 'tracking'],
            'product' => ['product', 'quality', 'design'],
            'order' => ['order', 'payment', 'refund'],
            'technical' => ['error', 'website', 'login']
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    wp_set_object_terms($ticket->ID, [$category], 'ticket_category', true);
                    break;
                }
            }
        }
    }

    private function assignTicket(\WP_Post $ticket): void
    {
        // Get ticket category
        $terms = wp_get_object_terms($ticket->ID, 'ticket_category');
        if (empty($terms)) {
            return;
        }

        $category = $terms[0]->slug;
        
        // Get available agents
        $agents = $this->getAvailableAgents($category);
        if (empty($agents)) {
            return;
        }

        // Assign to least loaded agent
        $assignedAgent = $this->getLeastLoadedAgent($agents);
        if ($assignedAgent) {
            update_post_meta($ticket->ID, '_wpwps_assigned_to', $assignedAgent);
            
            // Notify agent
            $this->notifyAgent($ticket, $assignedAgent);
        }
    }

    private function sendTicketConfirmation(\WP_Post $ticket): void
    {
        $to = get_post_meta($ticket->ID, '_wpwps_customer_email', true);
        if (!$to) {
            return;
        }

        $subject = sprintf(
            __('[%s] Your support ticket has been received (#%s)', 'wp-woocommerce-printify-sync'),
            get_bloginfo('name'),
            $ticket->ID
        );

        $message = $this->getTicketConfirmationTemplate($ticket);

        wp_mail($to, $subject, $message, [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' Support <' . $this->config->get('support_email') . '>'
        ]);
    }

    private function getTicketConfirmationTemplate(\WP_Post $ticket): string
    {
        ob_start();
        include WPWPS_PLUGIN_PATH . 'templates/emails/ticket-confirmation.php';
        return ob_get_clean();
    }
}