<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Support\EmailProcessor;
use ApolloWeb\WPWooCommercePrintifySync\Support\AIAnalyzer;
use ApolloWeb\WPWooCommercePrintifySync\Support\EmailQueue;

/**
 * Manages the support ticketing system
 */
class TicketManager {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var Settings
     */
    private $settings;
    
    /**
     * @var EmailProcessor
     */
    private $email_processor;
    
    /**
     * @var AIAnalyzer
     */
    private $ai_analyzer;
    
    /**
     * @var EmailQueue
     */
    private $email_queue;
    
    /**
     * Constructor
     */
    public function __construct(
        Logger $logger,
        Settings $settings,
        EmailProcessor $email_processor,
        AIAnalyzer $ai_analyzer,
        EmailQueue $email_queue
    ) {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->email_processor = $email_processor;
        $this->ai_analyzer = $ai_analyzer;
        $this->email_queue = $email_queue;
    }
    
    /**
     * Initialize the ticketing system
     */
    public function init(): void {
        // Register custom post type for tickets
        add_action('init', [$this, 'registerTicketPostType']);
        
        // Schedule email fetch
        if (!wp_next_scheduled('wpwps_fetch_support_emails')) {
            wp_schedule_event(time(), 'every_fifteen_minutes', 'wpwps_fetch_support_emails');
        }
        
        // Hook into the scheduled event
        add_action('wpwps_fetch_support_emails', [$this, 'fetchAndProcessEmails']);
        
        // Schedule email queue processing
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'five_minutes', 'wpwps_process_email_queue');
        }
        
        // Hook into the scheduled event
        add_action('wpwps_process_email_queue', [$this->email_queue, 'processQueue']);
        
        // Register custom interval for queue processing
        add_filter('cron_schedules', [$this, 'addCronIntervals']);
        
        // Register admin notification for new tickets
        add_action('wpwps_new_support_ticket', [$this, 'notifyAdminOfNewTicket']);
        
        // AJAX endpoints for admin actions
        add_action('wp_ajax_wpwps_get_ai_response', [$this, 'getAIResponseAjax']);
        add_action('wp_ajax_wpwps_send_ticket_reply', [$this, 'sendTicketReplyAjax']);
    }
    
    /**
     * Register the custom post type for support tickets
     */
    public function registerTicketPostType(): void {
        $labels = [
            'name'                  => _x('Support Tickets', 'Post type general name', 'wp-woocommerce-printify-sync'),
            'singular_name'         => _x('Support Ticket', 'Post type singular name', 'wp-woocommerce-printify-sync'),
            'menu_name'             => _x('Support Tickets', 'Admin Menu text', 'wp-woocommerce-printify-sync'),
            'name_admin_bar'        => _x('Support Ticket', 'Add New on Toolbar', 'wp-woocommerce-printify-sync'),
            'add_new'               => __('Add New', 'wp-woocommerce-printify-sync'),
            'add_new_item'          => __('Add New Ticket', 'wp-woocommerce-printify-sync'),
            'new_item'              => __('New Ticket', 'wp-woocommerce-printify-sync'),
            'edit_item'             => __('Edit Ticket', 'wp-woocommerce-printify-sync'),
            'view_item'             => __('View Ticket', 'wp-woocommerce-printify-sync'),
            'all_items'             => __('All Tickets', 'wp-woocommerce-printify-sync'),
            'search_items'          => __('Search Tickets', 'wp-woocommerce-printify-sync'),
            'parent_item_colon'     => __('Parent Tickets:', 'wp-woocommerce-printify-sync'),
            'not_found'             => __('No tickets found.', 'wp-woocommerce-printify-sync'),
            'not_found_in_trash'    => __('No tickets found in Trash.', 'wp-woocommerce-printify-sync'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'wpwps-dashboard',
            'query_var'          => true,
            'rewrite'            => ['slug' => 'support-ticket'],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => ['title', 'editor', 'author', 'comments'],
            'menu_icon'          => 'dashicons-email-alt',
        ];

        register_post_type('support_ticket', $args);
        
        // Register custom status taxonomy for tickets
        register_taxonomy(
            'ticket_status',
            ['support_ticket'],
            [
                'label' => __('Ticket Status', 'wp-woocommerce-printify-sync'),
                'hierarchical' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'ticket-status'],
            ]
        );
        
        // Register custom category taxonomy for tickets
        register_taxonomy(
            'ticket_category',
            ['support_ticket'],
            [
                'label' => __('Ticket Category', 'wp-woocommerce-printify-sync'),
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'ticket-category'],
            ]
        );
        
        // Add default terms if they don't exist
        $this->addDefaultTerms();
    }
    
    /**
     * Add default terms for ticket taxonomies
     */
    private function addDefaultTerms(): void {
        // Default statuses
        $statuses = [
            'new' => __('New', 'wp-woocommerce-printify-sync'),
            'open' => __('Open', 'wp-woocommerce-printify-sync'),
            'pending' => __('Pending Customer', 'wp-woocommerce-printify-sync'),
            'resolved' => __('Resolved', 'wp-woocommerce-printify-sync'),
            'closed' => __('Closed', 'wp-woocommerce-printify-sync')
        ];
        
        foreach ($statuses as $slug => $name) {
            if (!term_exists($slug, 'ticket_status')) {
                wp_insert_term($name, 'ticket_status', ['slug' => $slug]);
            }
        }
        
        // Default categories
        $categories = [
            'general' => __('General', 'wp-woocommerce-printify-sync'),
            'order' => __('Order Related', 'wp-woocommerce-printify-sync'),
            'product' => __('Product Related', 'wp-woocommerce-printify-sync'),
            'shipping' => __('Shipping', 'wp-woocommerce-printify-sync'),
            'returns' => __('Returns', 'wp-woocommerce-printify-sync'),
            'technical' => __('Technical', 'wp-woocommerce-printify-sync')
        ];
        
        foreach ($categories as $slug => $name) {
            if (!term_exists($slug, 'ticket_category')) {
                wp_insert_term($name, 'ticket_category', ['slug' => $slug]);
            }
        }
    }
    
    /**
     * Add custom cron intervals
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function addCronIntervals(array $schedules): array {
        $schedules['five_minutes'] = [
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'wp-woocommerce-printify-sync')
        ];
        
        $schedules['every_fifteen_minutes'] = [
            'interval' => 900,
            'display' => __('Every 15 Minutes', 'wp-woocommerce-printify-sync')
        ];
        
        return $schedules;
    }
    
    /**
     * Fetch and process emails
     */
    public function fetchAndProcessEmails(): void {
        try {
            $this->logger->log('Starting email fetch for tickets', 'info');
            
            // Fetch emails from the server
            $emails = $this->email_processor->fetchEmails();
            
            if (empty($emails)) {
                $this->logger->log('No new emails found', 'info');
                return;
            }
            
            $this->logger->log(sprintf('Found %d new emails to process', count($emails)), 'info');
            
            foreach ($emails as $email) {
                try {
                    // Analyze email content with AI
                    $analysis = $this->ai_analyzer->analyzeEmailContent($email);
                    
                    // Create ticket based on the email and AI analysis
                    $ticket_id = $this->createTicketFromEmail($email, $analysis);
                    
                    if ($ticket_id) {
                        $this->logger->log(sprintf('Created new ticket #%d from email', $ticket_id), 'info');
                        
                        // Trigger admin notification
                        do_action('wpwps_new_support_ticket', $ticket_id, $email, $analysis);
                    }
                } catch (\Exception $e) {
                    $this->logger->log('Error processing email: ' . $e->getMessage(), 'error');
                }
            }
            
            $this->logger->log('Completed email processing for tickets', 'info');
        } catch (\Exception $e) {
            $this->logger->log('Error fetching emails: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Create a new ticket from an email
     *
     * @param array $email Email data
     * @param array $analysis AI analysis results
     * @return int|false The ticket ID or false on failure
     */
    public function createTicketFromEmail(array $email, array $analysis) {
        // Extract relevant information
        $subject = $email['subject'] ?? 'No Subject';
        $content = $email['body'] ?? '';
        $from_email = $email['from_email'] ?? '';
        $from_name = $email['from_name'] ?? '';
        $date = $email['date'] ?? current_time('mysql');
        $attachments = $email['attachments'] ?? [];
        
        // Check if this is a reply to an existing ticket
        $existing_ticket_id = $this->findExistingTicket($email);
        
        if ($existing_ticket_id) {
            // This is a reply to an existing ticket
            return $this->addReplyToTicket($existing_ticket_id, $email, $analysis);
        }
        
        // Create a new ticket
        $ticket_data = [
            'post_title'    => $subject,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'support_ticket',
            'post_author'   => 1, // Default to admin
            'post_date'     => $date,
            'meta_input'    => [
                '_wpwps_ticket_email' => $from_email,
                '_wpwps_ticket_name' => $from_name,
                '_wpwps_ticket_urgency' => $analysis['urgency'] ?? 'normal',
                '_wpwps_message_id' => $email['message_id'] ?? '',
                '_wpwps_references' => $email['references'] ?? '',
                '_wpwps_in_reply_to' => $email['in_reply_to'] ?? '',
            ]
        ];
        
        // Link to WooCommerce customer if found
        if (!empty($analysis['customer_id'])) {
            $ticket_data['meta_input']['_wpwps_customer_id'] = $analysis['customer_id'];
        }
        
        // Link to WooCommerce order if found
        if (!empty($analysis['order_id'])) {
            $ticket_data['meta_input']['_wpwps_order_id'] = $analysis['order_id'];
        }
        
        // Insert the ticket
        $ticket_id = wp_insert_post($ticket_data);
        
        if (!$ticket_id || is_wp_error($ticket_id)) {
            $this->logger->log('Failed to create ticket: ' . ($ticket_id->get_error_message() ?? 'Unknown error'), 'error');
            return false;
        }
        
        // Set the initial status to "new"
        wp_set_object_terms($ticket_id, 'new', 'ticket_status');
        
        // Set the category based on AI analysis
        if (!empty($analysis['category'])) {
            wp_set_object_terms($ticket_id, $analysis['category'], 'ticket_category');
        } else {
            wp_set_object_terms($ticket_id, 'general', 'ticket_category');
        }
        
        // Process attachments
        if (!empty($attachments)) {
            $this->saveTicketAttachments($ticket_id, $attachments);
        }
        
        return $ticket_id;
    }
    
    /**
     * Add a reply to an existing ticket
     *
     * @param int $ticket_id Ticket ID
     * @param array $email Email data
     * @param array $analysis AI analysis results
     * @return int The ticket ID
     */
    private function addReplyToTicket(int $ticket_id, array $email, array $analysis) {
        $content = $email['body'] ?? '';
        $from_email = $email['from_email'] ?? '';
        $from_name = $email['from_name'] ?? '';
        $date = $email['date'] ?? current_time('mysql');
        $attachments = $email['attachments'] ?? [];
        
        // Add the reply as a comment
        $comment_data = [
            'comment_post_ID' => $ticket_id,
            'comment_author' => $from_name,
            'comment_author_email' => $from_email,
            'comment_content' => $content,
            'comment_date' => $date,
            'comment_approved' => 1,
            'comment_type' => 'comment',
            'comment_meta' => [
                'message_id' => $email['message_id'] ?? '',
                'references' => $email['references'] ?? '',
                'in_reply_to' => $email['in_reply_to'] ?? '',
            ]
        ];
        
        $comment_id = wp_insert_comment($comment_data);
        
        if (!$comment_id || is_wp_error($comment_id)) {
            $this->logger->log('Failed to add reply: ' . ($comment_id->get_error_message() ?? 'Unknown error'), 'error');
            return $ticket_id;
        }
        
        // Update ticket status to "open" if it was "resolved" or "closed"
        $current_status = wp_get_object_terms($ticket_id, 'ticket_status', ['fields' => 'slugs']);
        
        if (in_array('resolved', $current_status) || in_array('closed', $current_status)) {
            wp_set_object_terms($ticket_id, 'open', 'ticket_status');
            
            // Add a note about reopening
            wp_insert_comment([
                'comment_post_ID' => $ticket_id,
                'comment_author' => 'System',
                'comment_content' => __('Ticket reopened due to new customer reply', 'wp-woocommerce-printify-sync'),
                'comment_type' => 'ticket_note',
                'comment_approved' => 1,
            ]);
        }
        
        // Process attachments
        if (!empty($attachments)) {
            $this->saveTicketAttachments($ticket_id, $attachments, $comment_id);
        }
        
        // Update the last activity date
        update_post_meta($ticket_id, '_wpwps_last_activity', current_time('mysql'));
        
        // Trigger admin notification
        do_action('wpwps_ticket_reply_received', $ticket_id, $comment_id, $email);
        
        return $ticket_id;
    }
    
    /**
     * Find an existing ticket that this email might be replying to
     *
     * @param array $email Email data
     * @return int|false Ticket ID or false if not found
     */
    private function findExistingTicket(array $email) {
        // Check if this is a reply to a specific message ID
        if (!empty($email['in_reply_to'])) {
            $args = [
                'post_type' => 'support_ticket',
                'meta_query' => [
                    [
                        'key' => '_wpwps_message_id',
                        'value' => $email['in_reply_to'],
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1
            ];
            
            $query = new \WP_Query($args);
            
            if ($query->have_posts()) {
                return $query->posts[0]->ID;
            }
            
            // Check comments for message ID
            global $wpdb;
            $comment = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT comment_post_ID FROM {$wpdb->commentmeta} 
                     JOIN {$wpdb->comments} ON {$wpdb->commentmeta}.comment_id = {$wpdb->comments}.comment_ID
                     WHERE meta_key = 'message_id' AND meta_value = %s 
                     LIMIT 1",
                    $email['in_reply_to']
                )
            );
            
            if ($comment) {
                return (int) $comment->comment_post_ID;
            }
        }
        
        // Check references
        if (!empty($email['references'])) {
            $references = explode(' ', $email['references']);
            
            foreach ($references as $reference) {
                $reference = trim($reference);
                if (empty($reference)) continue;
                
                $args = [
                    'post_type' => 'support_ticket',
                    'meta_query' => [
                        [
                            'key' => '_wpwps_message_id',
                            'value' => $reference,
                            'compare' => '='
                        ]
                    ],
                    'posts_per_page' => 1
                ];
                
                $query = new \WP_Query($args);
                
                if ($query->have_posts()) {
                    return $query->posts[0]->ID;
                }
                
                // Check comments for message ID
                global $wpdb;
                $comment = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT comment_post_ID FROM {$wpdb->commentmeta} 
                         JOIN {$wpdb->comments} ON {$wpdb->commentmeta}.comment_id = {$wpdb->comments}.comment_ID
                         WHERE meta_key = 'message_id' AND meta_value = %s 
                         LIMIT 1",
                        $reference
                    )
                );
                
                if ($comment) {
                    return (int) $comment->comment_post_ID;
                }
            }
        }
        
        // Check by email address to see if there's an ongoing conversation
        $from_email = $email['from_email'] ?? '';
        
        if (!empty($from_email)) {
            $args = [
                'post_type' => 'support_ticket',
                'meta_query' => [
                    [
                        'key' => '_wpwps_ticket_email',
                        'value' => $from_email,
                        'compare' => '='
                    ]
                ],
                'tax_query' => [
                    [
                        'taxonomy' => 'ticket_status',
                        'field' => 'slug',
                        'terms' => ['new', 'open', 'pending'],
                        'operator' => 'IN'
                    ]
                ],
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC'
            ];
            
            $query = new \WP_Query($args);
            
            if ($query->have_posts()) {
                return $query->posts[0]->ID;
            }
        }
        
        return false;
    }
    
    /**
     * Save attachments for a ticket
     *
     * @param int $ticket_id Ticket ID
     * @param array $attachments Array of attachment data
     * @param int|null $comment_id Comment ID if this is a reply
     */
    private function saveTicketAttachments(int $ticket_id, array $attachments, int $comment_id = null): void {
        $upload_dir = wp_upload_dir();
        $ticket_dir = $upload_dir['basedir'] . '/wpwps-attachments/' . $ticket_id;
        
        // Create directory if it doesn't exist
        if (!file_exists($ticket_dir)) {
            wp_mkdir_p($ticket_dir);
            
            // Add an index.php file to prevent directory listing
            file_put_contents($ticket_dir . '/index.php', '<?php // Silence is golden');
        }
        
        $saved_attachments = [];
        
        foreach ($attachments as $attachment) {
            // Sanitize filename
            $filename = sanitize_file_name($attachment['filename']);
            
            // Generate a unique filename to avoid overwrites
            $unique_filename = wp_unique_filename($ticket_dir, $filename);
            $filepath = $ticket_dir . '/' . $unique_filename;
            
            // Save the file
            if (file_put_contents($filepath, $attachment['content'])) {
                $saved_attachments[] = [
                    'filename' => $unique_filename,
                    'original_filename' => $filename,
                    'filepath' => $filepath,
                    'filesize' => filesize($filepath),
                    'mime_type' => $attachment['mime_type'] ?? 'application/octet-stream'
                ];
            }
        }
        
        // Save attachment metadata
        if (!empty($saved_attachments)) {
            if ($comment_id) {
                // Attachments for a reply
                add_comment_meta($comment_id, '_wpwps_attachments', $saved_attachments);
            } else {
                // Attachments for the original ticket
                update_post_meta($ticket_id, '_wpwps_attachments', $saved_attachments);
            }
        }
    }
    
    /**
     * Notify admin of a new ticket
     *
     * @param int $ticket_id Ticket ID
     * @param array $email Email data
     * @param array $analysis AI analysis data
     */
    public function notifyAdminOfNewTicket(int $ticket_id, array $email, array $analysis): void {
        $subject = sprintf(__('New Support Ticket: %s', 'wp-woocommerce-printify-sync'), get_the_title($ticket_id));
        
        $urgency = $analysis['urgency'] ?? 'normal';
        $category = $analysis['category'] ?? 'general';
        
        $message = sprintf(
            __('A new support ticket has been created:
            
Ticket ID: #%1$d
Subject: %2$s
From: %3$s <%4$s>
Category: %5$s
Urgency: %6$s

View Ticket: %7$s', 'wp-woocommerce-printify-sync'),
            $ticket_id,
            get_the_title($ticket_id),
            $email['from_name'] ?? '',
            $email['from_email'] ?? '',
            $category,
            $urgency,
            admin_url('post.php?post=' . $ticket_id . '&action=edit')
        );
        
        $admin_email = get_option('admin_email');
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        // Add to email queue instead of sending directly
        $this->email_queue->addToQueue($admin_email, $subject, $message, $headers);
        
        // Also send browser notification if enabled
        $notify_setting = $this->settings->get('ticket_browser_notifications', 'yes');
        
        if ($notify_setting === 'yes') {
            $this->sendBrowserNotification($ticket_id, $subject);
        }
    }
    
    /**
     * Get AI response suggestion for a ticket
     */
    public function getAIResponseAjax(): void {
        check_ajax_referer('wpwps_admin');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        
        if (empty($ticket_id)) {
            wp_send_json_error(['message' => __('Invalid ticket ID', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            // Generate AI response suggestion
            $response = $this->ai_analyzer->generateResponseSuggestion($ticket_id);
            
            wp_send_json_success([
                'response' => $response,
                'signature' => $this->generateEmailSignature()
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Send a reply to a ticket
     */
    public function sendTicketReplyAjax(): void {
        check_ajax_referer('wpwps_admin');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $response = isset($_POST['response']) ? wp_kses_post($_POST['response']) : '';
        
        if (empty($ticket_id)) {
            wp_send_json_error(['message' => __('Invalid ticket ID', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        if (empty($response)) {
            wp_send_json_error(['message' => __('Response cannot be empty', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            // Get the ticket
            $ticket = get_post($ticket_id);
            
            if (!$ticket || $ticket->post_type !== 'support_ticket') {
                throw new \Exception(__('Ticket not found', 'wp-woocommerce-printify-sync'));
            }
            
            // Get customer email
            $customer_email = get_post_meta($ticket_id, '_wpwps_ticket_email', true);
            $customer_name = get_post_meta($ticket_id, '_wpwps_ticket_name', true);
            
            if (empty($customer_email)) {
                throw new \Exception(__('Customer email not found', 'wp-woocommerce-printify-sync'));
            }
            
            // Add signature to response
            $response .= $this->generateEmailSignature();
            
            // Prepare email
            $subject = 'Re: ' . $ticket->post_title;
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->getFromHeader(),
                'Reply-To: ' . $this->settings->get('support_email', get_option('admin_email'))
            ];
            
            // Get original message ID if available for threading
            $message_id = get_post_meta($ticket_id, '_wpwps_message_id', true);
            
            if (!empty($message_id)) {
                $headers[] = 'References: ' . $message_id;
                $headers[] = 'In-Reply-To: ' . $message_id;
            }
            
            // Generate a unique message ID for this reply
            $reply_message_id = sprintf('<%s@%s>', uniqid('wpwps_reply_'), $_SERVER['HTTP_HOST']);
            $headers[] = 'Message-ID: ' . $reply_message_id;
            
            // Add to email queue
            $this->email_queue->addToQueue($customer_email, $subject, $response, $headers);
            
            // Add the reply as a comment in the ticket
            $comment_data = [
                'comment_post_ID' => $ticket_id,
                'comment_author' => wp_get_current_user()->display_name,
                'comment_author_email' => wp_get_current_user()->user_email,
                'comment_content' => $response,
                'comment_type' => 'ticket_reply',
                'comment_approved' => 1,
                'comment_meta' => [
                    'message_id' => $reply_message_id
                ]
            ];
            
            $comment_id = wp_insert_comment($comment_data);
            
            // Process any attachments
            if (!empty($_FILES)) {
                $attachments = [];
                
                foreach ($_FILES as $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $attachments[] = [
                            'filename' => sanitize_file_name($file['name']),
                            'content' => file_get_contents($file['tmp_name']),
                            'mime_type' => $file['type']
                        ];
                    }
                }
                
                if (!empty($attachments)) {
                    $this->saveTicketAttachments($ticket_id, $attachments, $comment_id);
                }
            }
            
            // Update ticket status to "pending"
            wp_set_object_terms($ticket_id, 'pending', 'ticket_status');
            
            // Update the last activity date
            update_post_meta($ticket_id, '_wpwps_last_activity', current_time('mysql'));
            
            wp_send_json_success([
                'message' => __('Reply sent successfully and added to queue', 'wp-woocommerce-printify-sync'),
                'comment_id' => $comment_id
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Generate email signature
     * 
     * @return string HTML signature
     */
    private function generateEmailSignature(): string {
        $company_name = $this->settings->get('company_name', get_bloginfo('name'));
        $website_url = home_url();
        $logo_url = $this->settings->get('email_logo_url', '');
        
        $signature = '<br><br>--<br>';
        $signature .= '<div style="font-family: Arial, sans-serif; color: #333;">';
        
        if (!empty($logo_url)) {
            $signature .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($company_name) . '" style="max-height: 50px; max-width: 200px; margin-bottom: 10px;"><br>';
        }
        
        $signature .= '<strong>' . esc_html($company_name) . '</strong><br>';
        $signature .= '<a href="' . esc_url($website_url) . '">' . esc_html($website_url) . '</a><br>';
        
        // Add support email and phone if available
        $support_email = $this->settings->get('support_email', '');
        $support_phone = $this->settings->get('support_phone', '');
        
        if (!empty($support_email)) {
            $signature .= 'Email: <a href="mailto:' . esc_attr($support_email) . '">' . esc_html($support_email) . '</a><br>';
        }
        
        if (!empty($support_phone)) {
            $signature .= 'Phone: ' . esc_html($support_phone) . '<br>';
        }
        
        // Add social media links if available
        $social_links = $this->getSocialMediaLinks();
        
        if (!empty($social_links)) {
            $signature .= '<div style="margin-top: 10px;">';
            
            foreach ($social_links as $platform => $url) {
                $signature .= '<a href="' . esc_url($url) . '" style="display: inline-block; margin-right: 10px;">';
                $signature .= '<img src="' . esc_url($this->getSocialMediaIcon($platform)) . '" alt="' . esc_attr($platform) . '" style="height: 20px; width: 20px;">';
                $signature .= '</a>';
            }
            
            $signature .= '</div>';
        }
        
        $signature .= '</div>';
        
        return $signature;
    }
    
    /**
     * Get social media links from settings
     * 
     * @return array Social media links
     */
    private function getSocialMediaLinks(): array {
        $links = [];
        
        $platforms = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube'];
        
        foreach ($platforms as $platform) {
            $url = $this->settings->get("social_{$platform}", '');
            
            if (!empty($url)) {
                $links[$platform] = $url;
            }
        }
        
        return $links;
    }
    
    /**
     * Get social media icon URL
     * 
     * @param string $platform Social media platform
     * @return string Icon URL
     */
    private function getSocialMediaIcon(string $platform): string {
        // You could store these in the plugin assets folder
        $default_icons = [
            'facebook' => WPPS_URL . 'assets/images/facebook.png',
            'twitter' => WPPS_URL . 'assets/images/twitter.png',
            'instagram' => WPPS_URL . 'assets/images/instagram.png',
            'linkedin' => WPPS_URL . 'assets/images/linkedin.png',
            'youtube' => WPPS_URL . 'assets/images/youtube.png',
        ];
        
        return $default_icons[$platform] ?? '';
    }
    
    /**
     * Get From header for emails
     * 
     * @return string From header
     */
    private function getFromHeader(): string {
        $support_name = $this->settings->get('support_name', 'Customer Support');
        $support_email = $this->settings->get('support_email', get_option('admin_email'));
        
        return sprintf('%s <%s>', $support_name, $support_email);
    }
    
    /**
     * Send browser notification
     * 
     * @param int $ticket_id Ticket ID
     * @param string $subject Notification subject
     */
    private function sendBrowserNotification(int $ticket_id, string $subject): void {
        // Store notification for admin dashboard
        $notifications = get_option('wpwps_ticket_notifications', []);
        
        $notifications[] = [
            'id' => uniqid('notification_'),
            'ticket_id' => $ticket_id,
            'subject' => $subject,
            'time' => current_time('mysql'),
            'read' => false
        ];
        
        // Keep only the most recent 100 notifications
        if (count($notifications) > 100) {
            $notifications = array_slice($notifications, -100);
        }
        
        update_option('wpwps_ticket_notifications', $notifications);
    }
}
