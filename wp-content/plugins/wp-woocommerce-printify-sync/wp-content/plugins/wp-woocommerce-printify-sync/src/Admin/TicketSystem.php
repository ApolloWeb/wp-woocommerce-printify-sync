<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Container;
use ApolloWeb\WPWooCommercePrintifySync\View\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\EmailService;
use ApolloWeb\WPWooCommercePrintifySync\Services\TemplateService;
use ApolloWeb\WPWooCommercePrintifySync\Services\AnalysisService;
use ApolloWeb\WPWooCommercePrintifySync\Api\OpenAiApi;
use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\Security\Encryption;
use ApolloWeb\WPWooCommercePrintifySync\Assets\AssetsManager;

class TicketSystem 
{
    private BladeTemplateEngine $templateEngine;
    private EmailService $emailService;
    private OpenAiApi $openAiApi;
    private PrintifyApi $printifyApi;
    private TemplateService $templateService;
    private AnalysisService $analysisService;
    private SettingsManager $settingsManager;
    private UIManager $uiManager;
    private Encryption $encryption;
    private AssetsManager $assetsManager;

    public function __construct(Container $container) 
    {
        $this->templateEngine = $container->get(BladeTemplateEngine::class);
        $this->emailService = $container->get(EmailService::class);
        $this->openAiApi = $container->get(OpenAiApi::class);
        $this->printifyApi = $container->get(PrintifyApi::class);
        $this->templateService = $container->get(TemplateService::class);
        $this->analysisService = $container->get(AnalysisService::class);
        
        // Initialize encryption utility
        $this->encryption = new Encryption();
        
        // Initialize UI Manager
        $this->uiManager = new UIManager();
        
        // Initialize Assets Manager
        $this->assetsManager = new AssetsManager($this->uiManager);
        
        // Initialize Settings Manager
        $this->settingsManager = new SettingsManager(
            $this->templateEngine,
            $this->printifyApi,
            $this->openAiApi,
            $this->encryption
        );
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_fetch_tickets', [$this, 'fetchTickets']);
        add_action('wp_ajax_wpwps_get_ticket', [$this, 'getTicket']);
        add_action('wp_ajax_wpwps_reply_to_ticket', [$this, 'replyToTicket']);
        add_action('wp_ajax_wpwps_update_ticket_status', [$this, 'updateTicketStatus']);
        add_action('wp_ajax_wpwps_fetch_emails', [$this, 'fetchEmails']);
        add_action('wp_ajax_wpwps_get_ai_suggestion', [$this, 'getAiSuggestion']);
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_test_openai_connection', [$this, 'testOpenAiConnection']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'saveSettings']);
        
        // UI related AJAX handlers
        add_action('wp_ajax_wpwps_toggle_sidebar', [$this, 'toggleSidebar']);
        add_action('wp_ajax_wpwps_dismiss_notification', [$this, 'dismissNotification']);
        
        // Register assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Register post types and taxonomies
        add_action('init', [$this, 'registerPostType']);
        
        // Schedule email fetch
        if (!wp_next_scheduled('wpwps_fetch_pop3_emails')) {
            wp_schedule_event(time(), 'hourly', 'wpwps_fetch_pop3_emails');
        }
        
        add_action('wpwps_fetch_pop3_emails', [$this, 'scheduledEmailFetch']);
    }
    
    /**
     * Enqueue custom admin assets for our enhanced UI
     */
    public function enqueueAdminAssets($hook): void
    {
        // Only load on our plugin pages
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }
        
        // Enqueue our custom Bootstrap CSS overrides
        wp_enqueue_style(
            'wpwps-bootstrap-custom',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-bootstrap-custom.css',
            ['wpwps-bootstrap-css'],
            WPWPS_VERSION
        );
        
        // Enqueue our custom Bootstrap behaviors
        wp_enqueue_script(
            'wpwps-bootstrap-custom',
            WPWPS_PLUGIN_URL . 'assets/js/wpwps-bootstrap-custom.js',
            ['jquery', 'wpwps-bootstrap-js'],
            WPWPS_VERSION,
            true
        );
        
        // Add notification data to JS
        wp_localize_script('wpwps-bootstrap-custom', 'wpwpsUI', [
            'notifications' => $this->getNotifications(),
            'current_user' => [
                'name' => wp_get_current_user()->display_name,
                'avatar' => get_avatar_url(wp_get_current_user()->ID),
                'role' => $this->getCurrentUserRole()
            ],
            'sidebar_state' => get_user_meta(get_current_user_id(), 'wpwps_sidebar_collapsed', true) ?: 'expanded'
        ]);
    }
    
    /**
     * Get current user's primary role
     */
    private function getCurrentUserRole(): string
    {
        $user = wp_get_current_user();
        return !empty($user->roles) ? ucfirst($user->roles[0]) : 'Administrator';
    }
    
    /**
     * Get notifications for the current user
     */
    private function getNotifications(): array
    {
        // Here we would typically fetch real notifications from the database
        // For now, we'll return sample data
        return [
            [
                'id' => 1,
                'title' => 'New support ticket',
                'message' => 'A new support ticket has been created',
                'time' => '5 min ago',
                'read' => false,
                'icon' => 'fas fa-ticket-alt',
                'icon_color' => 'text-warning'
            ],
            [
                'id' => 2,
                'title' => 'Printify API rate limit',
                'message' => 'Approaching API rate limit (80%)',
                'time' => '1 hour ago',
                'read' => true,
                'icon' => 'fas fa-exclamation-triangle',
                'icon_color' => 'text-danger'
            ],
            [
                'id' => 3,
                'title' => 'Sync completed',
                'message' => '24 products successfully synced',
                'time' => 'Yesterday',
                'read' => true,
                'icon' => 'fas fa-sync',
                'icon_color' => 'text-success'
            ]
        ];
    }
    
    /**
     * Toggle sidebar collapsed state
     */
    public function toggleSidebar(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $current_state = get_user_meta(get_current_user_id(), 'wpwps_sidebar_collapsed', true) ?: 'expanded';
        $new_state = $current_state === 'expanded' ? 'collapsed' : 'expanded';
        
        update_user_meta(get_current_user_id(), 'wpwps_sidebar_collapsed', $new_state);
        
        wp_send_json_success(['state' => $new_state]);
    }
    
    /**
     * Dismiss a notification
     */
    public function dismissNotification(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $notification_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$notification_id) {
            wp_send_json_error(['message' => 'Invalid notification ID']);
            return;
        }
        
        // In a real implementation, you would mark the notification as read in the database
        // For now, we'll just return success
        wp_send_json_success(['message' => 'Notification dismissed']);
    }
    
    /**
     * Fetch tickets 
     */
    public function fetchTickets(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'any';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        
        $args = [
            'status' => $status,
            'category' => $category,
            'search' => $search,
            'page' => $page,
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        
        $tickets = $this->getTickets($args);
        $total = $this->countTickets($args);
        
        wp_send_json_success([
            'tickets' => $tickets,
            'total' => $total,
            'pages' => ceil($total / $limit),
        ]);
    }
    
    public function getTicket(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        
        if (!$ticket_id) {
            wp_send_json_error(['message' => 'Invalid ticket ID']);
            return;
        }
        
        $ticket = $this->getTicketDetails($ticket_id);
        
        if (!$ticket) {
            wp_send_json_error(['message' => 'Ticket not found']);
            return;
        }
        
        wp_send_json_success(['ticket' => $ticket]);
    }
    
    public function replyToTicket(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $message = isset($_POST['message']) ? wp_kses_post($_POST['message']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$ticket_id || empty($message)) {
            wp_send_json_error(['message' => 'Invalid ticket ID or empty message']);
            return;
        }
        
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'wpwps_ticket') {
            wp_send_json_error(['message' => 'Ticket not found']);
            return;
        }
        
        // Add reply to ticket
        $reply_id = wp_insert_comment([
            'comment_post_ID' => $ticket_id,
            'comment_content' => $message,
            'comment_type' => 'wpwps_ticket_reply',
            'comment_author' => wp_get_current_user()->display_name,
            'comment_author_email' => wp_get_current_user()->user_email,
            'comment_author_url' => '',
            'comment_approved' => 1,
        ]);
        
        if (!$reply_id) {
            wp_send_json_error(['message' => 'Failed to add reply']);
            return;
        }
        
        // Update ticket status if provided
        if (!empty($status)) {
            wp_set_object_terms($ticket_id, $status, 'wpwps_ticket_status');
        }
        
        // Send email to customer
        $customer_email = get_post_meta($ticket_id, '_wpwps_customer_email', true);
        
        if (!empty($customer_email)) {
            $subject = sprintf(__('Re: [Ticket #%d] %s', 'wp-woocommerce-printify-sync'), $ticket_id, $ticket->post_title);
            $headers = $this->getEmailHeaders();
            
            // Get email signature
            $signature = $this->getEmailSignature();
            
            // Prepare email content
            $email_content = $message . "\n\n" . $signature;
            
            // Queue email
            $this->emailService->queueEmail($customer_email, $subject, $email_content, $headers);
        }
        
        wp_send_json_success([
            'message' => 'Reply added successfully',
            'reply_id' => $reply_id,
        ]);
    }
    
    public function updateTicketStatus(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$ticket_id || empty($status)) {
            wp_send_json_error(['message' => 'Invalid ticket ID or status']);
            return;
        }
        
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'wpwps_ticket') {
            wp_send_json_error(['message' => 'Ticket not found']);
            return;
        }
        
        // Update ticket status
        $result = wp_set_object_terms($ticket_id, $status, 'wpwps_ticket_status');
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }
        
        wp_send_json_success(['message' => 'Ticket status updated successfully']);
    }
    
    public function fetchEmails(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $result = $this->processIncomingEmails();
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }
        
        wp_send_json_success([
            'message' => sprintf(
                _n(
                    'Successfully processed %d email and created %d ticket.',
                    'Successfully processed %d emails and created %d tickets.',
                    $result['processed_count'],
                    'wp-woocommerce-printify-sync'
                ),
                $result['processed_count'],
                $result['ticket_count']
            ),
            'processed_count' => $result['processed_count'],
            'ticket_count' => $result['ticket_count'],
        ]);
    }
    
    public function getAiSuggestion(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        
        if (!$ticket_id) {
            wp_send_json_error(['message' => 'Invalid ticket ID']);
            return;
        }
        
        $ticket = $this->getTicketDetails($ticket_id);
        
        if (!$ticket) {
            wp_send_json_error(['message' => 'Ticket not found']);
            return;
        }
        
        // Get OpenAI API key
        $api_key = $this->getEncryptedOption('wpwps_openai_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'OpenAI API key not configured']);
            return;
        }
        
        // Prepare conversation context
        $context = "You are a helpful customer support agent for an e-commerce store using Printify for print-on-demand products. ";
        $context .= "Please generate a polite, helpful response to the customer query below, addressing all their concerns. ";
        $context .= "The response should be professional but warm in tone.\n\n";
        
        // Add ticket details
        $context .= "Ticket title: " . $ticket['title'] . "\n";
        $context .= "Customer: " . $ticket['customer_name'] . "\n";
        $context .= "Category: " . $ticket['category'] . "\n";
        $context .= "Status: " . $ticket['status'] . "\n";
        $context .= "Related Order: " . ($ticket['order_id'] ? '#' . $ticket['order_id'] : 'None') . "\n\n";
        
        // Add ticket content and replies
        $context .= "--- Original message ---\n" . $ticket['content'] . "\n\n";
        
        if (!empty($ticket['replies'])) {
            $context .= "--- Previous replies ---\n";
            foreach ($ticket['replies'] as $reply) {
                $context .= $reply['author'] . ": " . $reply['content'] . "\n\n";
            }
        }
        
        // Get AI suggestion
        $response = $this->openAiApi->generateText($context, [
            'temperature' => get_option('wpwps_openai_temperature', 0.7),
            'max_tokens' => get_option('wpwps_openai_token_limit', 1000),
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }
        
        wp_send_json_success([
            'suggestion' => $response,
        ]);
    }
    
    public function scheduledEmailFetch(): void
    {
        $this->processIncomingEmails();
    }
    
    private function processIncomingEmails()
    {
        // Get POP3 settings
        $pop3_server = get_option('wpwps_pop3_server', '');
        $pop3_port = get_option('wpwps_pop3_port', 110);
        $pop3_username = get_option('wpwps_pop3_username', '');
        $pop3_password = $this->getEncryptedOption('wpwps_pop3_password');
        $pop3_ssl = get_option('wpwps_pop3_ssl', 'yes') === 'yes';
        
        if (empty($pop3_server) || empty($pop3_username) || empty($pop3_password)) {
            return new \WP_Error('pop3_not_configured', 'POP3 server not configured');
        }
        
        // Connect to POP3 server
        $pop3 = new \POP3();
        
        if (!$pop3->connect($pop3_server, $pop3_port)) {
            return new \WP_Error('pop3_connection_failed', 'Failed to connect to POP3 server');
        }
        
        if (!$pop3->user($pop3_username)) {
            return new \WP_Error('pop3_auth_failed', 'Failed to authenticate with POP3 server');
        }
        
        $emails = $pop3->popstat();
        
        if (!$emails || !is_array($emails) || $emails[0] === 0) {
            $pop3->quit();
            return [
                'processed_count' => 0,
                'ticket_count' => 0,
            ];
        }
        
        $processed_count = 0;
        $ticket_count = 0;
        
        for ($i = 1; $i <= $emails[0]; $i++) {
            $message = $pop3->get($i);
            
            if (!$message) {
                continue;
            }
            
            $ticket_id = $this->processEmail($message);
            
            if ($ticket_id) {
                $ticket_count++;
            }
            
            // Mark email as deleted
            $pop3->delete($i);
            $processed_count++;
        }
        
        $pop3->quit();
        
        return [
            'processed_count' => $processed_count,
            'ticket_count' => $ticket_count,
        ];
    }
    
    private function processEmail($raw_email)
    {
        // Parse email
        $parser = new \Mail_RFC822();
        $decoded = $parser->parse_mime_message($raw_email);
        
        if (!$decoded) {
            return false;
        }
        
        $from = $decoded['headers']['from'];
        $to = $decoded['headers']['to'];
        $subject = $decoded['headers']['subject'];
        $body = $decoded['body'];
        $attachments = isset($decoded['attachments']) ? $decoded['attachments'] : [];
        
        // Check if this is a reply to an existing ticket
        $ticket_id = $this->findExistingTicket($subject, $from);
        
        if ($ticket_id) {
            // Add reply to existing ticket
            $this->addReplyToTicket($ticket_id, $body, $from, $attachments);
            return $ticket_id;
        }
        
        // Create new ticket
        // Get OpenAI API key
        $api_key = $this->getEncryptedOption('wpwps_openai_api_key');
        
        if (!empty($api_key)) {
            // Use OpenAI to extract ticket details
            $analysis = $this->analyzeTicketWithAI($subject, $body);
            
            if (!is_wp_error($analysis)) {
                return $this->createTicket([
                    'title' => $subject,
                    'content' => $body,
                    'customer_email' => $from,
                    'category' => $analysis['category'],
                    'status' => $analysis['status'],
                    'order_id' => $analysis['order_id'],
                    'urgency' => $analysis['urgency'],
                    'attachments' => $attachments,
                ]);
            }
        }
        
        // Fallback without AI analysis
        return $this->createTicket([
            'title' => $subject,
            'content' => $body,
            'customer_email' => $from,
            'category' => 'general',
            'status' => 'new',
            'attachments' => $attachments,
        ]);
    }
    
    private function analyzeTicketWithAI(string $subject, string $body): array
    {
        return $this->analysisService->analyzeTicket($subject, $body);
    }
    
    private function findExistingTicket($subject, $email)
    {
        // Check if this is a reply to an existing ticket
        if (preg_match('/\[Ticket #(\d+)\]/', $subject, $matches)) {
            $ticket_id = intval($matches[1]);
            $ticket = get_post($ticket_id);
            
            if ($ticket && $ticket->post_type === 'wpwps_ticket') {
                $ticket_email = get_post_meta($ticket_id, '_wpwps_customer_email', true);
                
                if ($ticket_email === $email) {
                    return $ticket_id;
                }
            }
        }
        
        return false;
    }
    
    private function addReplyToTicket($ticket_id, $content, $email, $attachments = [])
    {
        // Add reply as comment
        $comment_id = wp_insert_comment([
            'comment_post_ID' => $ticket_id,
            'comment_content' => $content,
            'comment_type' => 'wpwps_ticket_reply',
            'comment_author' => $email,
            'comment_author_email' => $email,
            'comment_approved' => 1,
        ]);
        
        if (!$comment_id) {
            return false;
        }
        
        // Store attachments
        if (!empty($attachments)) {
            $this->processAttachments($ticket_id, $comment_id, $attachments);
        }
        
        // Update ticket status to in-progress
        wp_set_object_terms($ticket_id, 'in-progress', 'wpwps_ticket_status');
        
        // Send notification to admin
        $this->notifyAdmin($ticket_id, 'reply');
        
        return $comment_id;
    }
    
    private function createTicket($args)
    {
        $title = isset($args['title']) ? sanitize_text_field($args['title']) : '';
        $content = isset($args['content']) ? wp_kses_post($args['content']) : '';
        $customer_email = isset($args['customer_email']) ? sanitize_email($args['customer_email']) : '';
        $category = isset($args['category']) ? sanitize_text_field($args['category']) : 'general';
        $status = isset($args['status']) ? sanitize_text_field($args['status']) : 'new';
        $order_id = isset($args['order_id']) ? intval($args['order_id']) : 0;
        $urgency = isset($args['urgency']) ? sanitize_text_field($args['urgency']) : 'medium';
        $attachments = isset($args['attachments']) ? $args['attachments'] : [];
        
        // Create ticket post
        $ticket_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'wpwps_ticket',
            'comment_status' => 'open',
        ]);
        
        if (!$ticket_id || is_wp_error($ticket_id)) {
            return false;
        }
        
        // Store customer details
        update_post_meta($ticket_id, '_wpwps_customer_email', $customer_email);
        
        // Try to find customer in WooCommerce
        $customer = $this->findWooCommerceCustomer($customer_email);
        
        if ($customer) {
            update_post_meta($ticket_id, '_wpwps_customer_id', $customer->ID);
            update_post_meta($ticket_id, '_wpwps_customer_name', $customer->display_name);
        }
        
        // Store order ID if provided
        if ($order_id) {
            update_post_meta($ticket_id, '_wpwps_order_id', $order_id);
        } else {
            // Try to find latest order for this customer
            $latest_order = $this->findLatestOrder($customer_email);
            
            if ($latest_order) {
                update_post_meta($ticket_id, '_wpwps_order_id', $latest_order->get_id());
            }
        }
        
        // Store urgency
        update_post_meta($ticket_id, '_wpwps_urgency', $urgency);
        
        // Set category and status
        wp_set_object_terms($ticket_id, $category, 'wpwps_ticket_category');
        wp_set_object_terms($ticket_id, $status, 'wpwps_ticket_status');
        
        // Process attachments
        if (!empty($attachments)) {
            $this->processAttachments($ticket_id, 0, $attachments);
        }
        
        // Send notification to admin
        $this->notifyAdmin($ticket_id, 'new');
        
        return $ticket_id;
    }
    
    private function processAttachments($ticket_id, $comment_id, $attachments)
    {
        // Make sure uploads directory exists and is writable
        $upload_dir = wp_upload_dir();
        $attachments_dir = $upload_dir['basedir'] . '/wpwps-ticket-attachments/' . $ticket_id;
        
        if (!file_exists($attachments_dir)) {
            wp_mkdir_p($attachments_dir);
        }
        
        $stored_attachments = [];
        
        foreach ($attachments as $index => $attachment) {
            $filename = sanitize_file_name($attachment['filename']);
            $content = $attachment['content'];
            $mime_type = $attachment['mime_type'];
            
            // Generate safe filename
            $unique_filename = $index . '-' . time() . '-' . $filename;
            $file_path = $attachments_dir . '/' . $unique_filename;
            
            // Store file
            file_put_contents($file_path, $content);
            
            // Store attachment metadata
            $stored_attachments[] = [
                'file' => $unique_filename,
                'url' => $upload_dir['baseurl'] . '/wpwps-ticket-attachments/' . $ticket_id . '/' . $unique_filename,
                'mime_type' => $mime_type,
            ];
        }
        
        // Save attachments metadata
        if ($comment_id > 0) {
            update_comment_meta($comment_id, '_wpwps_attachments', $stored_attachments);
        } else {
            update_post_meta($ticket_id, '_wpwps_attachments', $stored_attachments);
        }
        
        return $stored_attachments;
    }
    
    private function notifyAdmin($ticket_id, $type = 'new')
    {
        $admin_email = get_option('admin_email');
        $ticket = get_post($ticket_id);
        
        if (!$ticket) {
            return false;
        }
        
        $customer_email = get_post_meta($ticket_id, '_wpwps_customer_email', true);
        $order_id = get_post_meta($ticket_id, '_wpwps_order_id', true);
        
        if ($type === 'new') {
            $subject = sprintf(__('New Support Ticket: #%d - %s', 'wp-woocommerce-printify-sync'), $ticket_id, $ticket->post_title);
            $message = sprintf(__("A new support ticket has been created:\n\nTicket ID: #%d\nSubject: %s\nCustomer: %s\nOrder: %s\n\nTo view and respond to this ticket, please go to: %s", 'wp-woocommerce-printify-sync'),
                $ticket_id,
                $ticket->post_title,
                $customer_email,
                $order_id ? '#' . $order_id : 'N/A',
                admin_url('admin.php?page=wpwps-tickets&ticket=' . $ticket_id)
            );
        } else {
            $subject = sprintf(__('New Reply to Support Ticket: #%d - %s', 'wp-woocommerce-printify-sync'), $ticket_id, $ticket->post_title);
            $message = sprintf(__("A customer has replied to a support ticket:\n\nTicket ID: #%d\nSubject: %s\nCustomer: %s\nOrder: %s\n\nTo view and respond to this ticket, please go to: %s", 'wp-woocommerce-printify-sync'),
                $ticket_id,
                $ticket->post_title,
                $customer_email,
                $order_id ? '#' . $order_id : 'N/A',
                admin_url('admin.php?page=wpwps-tickets&ticket=' . $ticket_id)
            );
        }
        
        // Send email notification
        $headers = $this->getEmailHeaders();
        wp_mail($admin_email, $subject, $message, $headers);
        
        return true;
    }
    
    private function getTickets($args = [])
    {
        $status = isset($args['status']) ? $args['status'] : 'any';
        $category = isset($args['category']) ? $args['category'] : '';
        $search = isset($args['search']) ? $args['search'] : '';
        $page = isset($args['page']) ? absint($args['page']) : 1;
        $limit = isset($args['limit']) ? absint($args['limit']) : 10;
        $orderby = isset($args['orderby']) ? $args['orderby'] : 'date';
        $order = isset($args['order']) ? $args['order'] : 'DESC';
        
        $query_args = [
            'post_type' => 'wpwps_ticket',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];
        
        // Add status filter
        if ($status !== 'any') {
            $query_args['tax_query'][] = [
                'taxonomy' => 'wpwps_ticket_status',
                'field' => 'slug',
                'terms' => $status,
            ];
        }
        
        // Add category filter
        if (!empty($category)) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'wpwps_ticket_category',
                'field' => 'slug',
                'terms' => $category,
            ];
        }
        
        // Add search query
        if (!empty($search)) {
            $query_args['s'] = $search;
        }
        
        $query = new \WP_Query($query_args);
        $tickets = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $ticket_id = get_the_ID();
                
                // Get customer details
                $customer_email = get_post_meta($ticket_id, '_wpwps_customer_email', true);
                $customer_name = get_post_meta($ticket_id, '_wpwps_customer_name', true);
                
                if (empty($customer_name)) {
                    $customer_name = $customer_email;
                }
                
                // Get order ID
                $order_id = get_post_meta($ticket_id, '_wpwps_order_id', true);
                
                // Get status and category
                $status_terms = wp_get_object_terms($ticket_id, 'wpwps_ticket_status');
                $category_terms = wp_get_object_terms($ticket_id, 'wpwps_ticket_category');
                
                $status = !empty($status_terms) ? $status_terms[0]->name : '';
                $status_slug = !empty($status_terms) ? $status_terms[0]->slug : '';
                $category = !empty($category_terms) ? $category_terms[0]->name : '';
                $category_slug = !empty($category_terms) ? $category_terms[0]->slug : '';
                
                // Get urgency
                $urgency = get_post_meta($ticket_id, '_wpwps_urgency', true);
                
                // Get reply count
                $reply_count = get_comments([
                    'post_id' => $ticket_id,
                    'type' => 'wpwps_ticket_reply',
                    'count' => true,
                ]);
                
                // Get latest reply
                $latest_reply = get_comments([
                    'post_id' => $ticket_id,
                    'type' => 'wpwps_ticket_reply',
                    'number' => 1,
                    'orderby' => 'comment_date',
                    'order' => 'DESC',
                ]);
                
                $latest_reply_date = !empty($latest_reply) ? $latest_reply[0]->comment_date : '';
                
                $tickets[] = [
                    'id' => $ticket_id,
                    'title' => get_the_title(),
                    'date' => get_the_date('Y-m-d H:i:s'),
                    'customer_name' => $customer_name,
                    'customer_email' => $customer_email,
                    'status' => $status,
                    'status_slug' => $status_slug,
                    'category' => $category,
                    'category_slug' => $category_slug,
                    'order_id' => $order_id,
                    'urgency' => $urgency,
                    'reply_count' => $reply_count,
                    'latest_reply_date' => $latest_reply_date,
                ];
            }
        }
        
        wp_reset_postdata();
        
        return $tickets;
    }
    
    private function countTickets($args = [])
    {
        $status = isset($args['status']) ? $args['status'] : 'any';
        $category = isset($args['category']) ? $args['category'] : '';
        $search = isset($args['search']) ? $args['search'] : '';
        
        $query_args = [
            'post_type' => 'wpwps_ticket',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];
        
        // Add status filter
        if ($status !== 'any') {
            $query_args['tax_query'][] = [
                'taxonomy' => 'wpwps_ticket_status',
                'field' => 'slug',
                'terms' => $status,
            ];
        }
        
        // Add category filter
        if (!empty($category)) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'wpwps_ticket_category',
                'field' => 'slug',
                'terms' => $category,
            ];
        }
        
        // Add search query
        if (!empty($search)) {
            $query_args['s'] = $search;
        }
        
        $query = new \WP_Query($query_args);
        return $query->found_posts;
    }
    
    private function getTicketDetails($ticket_id)
    {
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'wpwps_ticket') {
            return false;
        }
        
        // Get customer details
        $customer_email = get_post_meta($ticket_id, '_wpwps_customer_email', true);
        $customer_name = get_post_meta($ticket_id, '_wpwps_customer_name', true);
        
        if (empty($customer_name)) {
            $customer_name = $customer_email;
        }
        
        // Get order ID
        $order_id = get_post_meta($ticket_id, '_wpwps_order_id', true);
        
        // Get status and category
        $status_terms = wp_get_object_terms($ticket_id, 'wpwps_ticket_status');
        $category_terms = wp_get_object_terms($ticket_id, 'wpwps_ticket_category');
        
        $status = !empty($status_terms) ? $status_terms[0]->name : '';
        $status_slug = !empty($status_terms) ? $status_terms[0]->slug : '';
        $category = !empty($category_terms) ? $category_terms[0]->name : '';
        $category_slug = !empty($category_terms) ? $category_terms[0]->slug : '';
        
        // Get urgency
        $urgency = get_post_meta($ticket_id, '_wpwps_urgency', true);
        
        // Get attachments
        $attachments = get_post_meta($ticket_id, '_wpwps_attachments', true);
        if (!$attachments) {
            $attachments = [];
        }
        
        // Get replies
        $replies = get_comments([
            'post_id' => $ticket_id,
            'type' => 'wpwps_ticket_reply',
            'orderby' => 'comment_date',
            'order' => 'ASC',
        ]);
        
        $formatted_replies = [];
        
        foreach ($replies as $reply) {
            $reply_attachments = get_comment_meta($reply->comment_ID, '_wpwps_attachments', true);
            
            if (!$reply_attachments) {
                $reply_attachments = [];
            }
            
            $formatted_replies[] = [
                'id' => $reply->comment_ID,
                'content' => $reply->comment_content,
                'date' => $reply->comment_date,
                'author' => $reply->comment_author,
                'author_email' => $reply->comment_author_email,
                'is_admin' => $reply->user_id > 0,
                'attachments' => $reply_attachments,
            ];
        }
        
        // Get order details if available
        $order_details = null;
        
        if ($order_id && function_exists('wc_get_order')) {
            $order = wc_get_order($order_id);
            
            if ($order) {
                $order_details = [
                    'id' => $order->get_id(),
                    'status' => $order->get_status(),
                    'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                    'total' => $order->get_total(),
                    'currency' => $order->get_currency(),
                    'items' => [],
                ];
                
                foreach ($order->get_items() as $item) {
                    $order_details['items'][] = [
                        'name' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'total' => $item->get_total(),
                    ];
                }
            }
        }
        
        return [
            'id' => $ticket_id,
            'title' => $ticket->post_title,
            'content' => $ticket->post_content,
            'date' => $ticket->post_date,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'status' => $status,
            'status_slug' => $status_slug,
            'category' => $category,
            'category_slug' => $category_slug,
            'order_id' => $order_id,
            'urgency' => $urgency,
            'attachments' => $attachments,
            'replies' => $formatted_replies,
            'order' => $order_details,
        ];
    }
    
    private function findWooCommerceCustomer($email)
    {
        if (!function_exists('wc_get_customer_id_by_email')) {
            return false;
        }
        
        $customer_id = wc_get_customer_id_by_email($email);
        
        if (!$customer_id) {
            return false;
        }
        
        return get_user_by('id', $customer_id);
    }
    
    private function findLatestOrder($email)
    {
        if (!function_exists('wc_get_orders')) {
            return false;
        }
        
        $orders = wc_get_orders([
            'customer' => $email,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        if (empty($orders)) {
            return false;
        }
        
        return $orders[0];
    }
    
    private function getEmailHeaders(): array
    {
        return $this->templateService->getEmailHeaders();
    }
    
    private function getEmailSignature(): string
    {
        return $this->templateService->getEmailSignature();
    }
    
    private function getEncryptedOption(string $option_name): string
    {
        $encrypted_value = get_option($option_name, '');
        if (empty($encrypted_value)) {
            return '';
        }

        return $this->decrypt($encrypted_value);
    }
    
    private function decrypt(string $encrypted_value): string
    {
        if (!function_exists('openssl_decrypt')) {
            // Fallback if OpenSSL is not available
            return base64_decode($encrypted_value);
        }

        $encryption_key = $this->getEncryptionKey();
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_value), 2);
        
        return openssl_decrypt($encrypted_data, 'AES-256-CBC', $encryption_key, 0, $iv);
    }
    
    private function getEncryptionKey(): string
    {
        // Use WordPress authentication keys as an encryption key
        // This is secure because it's unique to each WordPress installation
        if (defined('AUTH_KEY')) {
            return substr(hash('sha256', AUTH_KEY), 0, 32);
        }
        
        // Fallback if AUTH_KEY is not defined
        return substr(hash('sha256', DB_NAME . DB_USER . DB_PASSWORD), 0, 32);
    }

    public function testPrintifyConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : 'https://api.printify.com/v1';

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key is required']);
            return;
        }

        $response = $this->printifyApi->testConnection($api_key, $api_endpoint);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'message' => 'Connection successful!',
            'shops' => $response['data']
        ]);
    }

    public function saveSettings(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        // Printify settings
        if (isset($_POST['printify_api_key'])) {
            $this->saveEncryptedOption('wpwps_printify_api_key', sanitize_text_field($_POST['printify_api_key']));
        }
        
        if (isset($_POST['printify_api_endpoint'])) {
            update_option('wpwps_printify_api_endpoint', esc_url_raw($_POST['printify_api_endpoint']));
        }
        
        if (isset($_POST['printify_shop_id'])) {
            update_option('wpwps_printify_shop_id', sanitize_text_field($_POST['printify_shop_id']));
        }

        // OpenAI settings
        if (isset($_POST['openai_api_key'])) {
            $this->saveEncryptedOption('wpwps_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        }
        
        if (isset($_POST['openai_token_limit'])) {
            update_option('wpwps_openai_token_limit', intval($_POST['openai_token_limit']));
        }
        
        if (isset($_POST['openai_spend_cap'])) {
            update_option('wpwps_openai_spend_cap', floatval($_POST['openai_spend_cap']));
        }
        
        if (isset($_POST['openai_temperature'])) {
            update_option('wpwps_openai_temperature', floatval($_POST['openai_temperature']));
        }

        wp_send_json_success(['message' => 'Settings saved successfully']);
    }

    private function saveEncryptedOption(string $option_name, string $value): void
    {
        if (empty($value)) {
            delete_option($option_name);
            return;
        }

        $encrypted_value = $this->encrypt($value);
        update_option($option_name, $encrypted_value);
    }

    private function encrypt(string $value): string
    {
        if (!function_exists('openssl_encrypt')) {
            // Fallback if OpenSSL is not available
            return base64_encode($value);
        }

        $encryption_key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $encryption_key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Render settings page
     */
    public function renderSettings(): void
    {
        $data = [
            'printify_api_key' => $this->getEncryptedOption('wpwps_printify_api_key'),
            'printify_api_endpoint' => get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1'),
            'printify_shop_id' => get_option('wpwps_printify_shop_id', ''),
            'shops' => $this->getShopsList(),
            'openai_api_key' => $this->getEncryptedOption('wpwps_openai_api_key'),
            'openai_token_limit' => get_option('wpwps_openai_token_limit', 1000),
            'openai_spend_cap' => get_option('wpwps_openai_spend_cap', 10),
            'openai_temperature' => get_option('wpwps_openai_temperature', 0.7),
        ];
        
        // Use blade template
        echo $this->templateEngine->render('admin.settings', $data);
    }
    
    /**
     * Get the list of shops from Printify
     * 
     * @return array
     */
    private function getShopsList(): array
    {
        $api_key = $this->getEncryptedOption('wpwps_printify_api_key');
        $api_endpoint = get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1');
        
        if (empty($api_key)) {
            return [];
        }
        
        $response = $this->printifyApi->getShops($api_key, $api_endpoint);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * Test Printify API connection
     */
    public function testPrintifyConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : 'https://api.printify.com/v1';

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key is required']);
            return;
        }

        $response = $this->printifyApi->testConnection($api_key, $api_endpoint);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'message' => 'Connection successful!',
            'shops' => $response['data']
        ]);
    }
    
    /**
     * Test OpenAI API connection and calculate estimated cost
     */
    public function testOpenAiConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $token_limit = isset($_POST['token_limit']) ? intval($_POST['token_limit']) : 1000;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
        $spend_cap = isset($_POST['spend_cap']) ? floatval($_POST['spend_cap']) : 10;

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'OpenAI API key is required']);
            return;
        }

        $response = $this->openAiApi->testConnection($api_key);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        // Calculate estimated monthly cost
        $cost_per_token = 0.000002; // Approximate cost per token for GPT-3.5-Turbo
        $estimated_daily_tickets = 10; // Assumed average
        $avg_tokens_per_request = $token_limit * 0.75; // Average usage
        $daily_cost = $estimated_daily_tickets * $avg_tokens_per_request * $cost_per_token;
        $monthly_cost = $daily_cost * 30;

        wp_send_json_success([
            'message' => 'OpenAI API connection successful!',
            'estimated_monthly_cost' => '$' . number_format($monthly_cost, 2),
            'model_info' => $response['data']
        ]);
    }

    /**
     * Save plugin settings
     */
    public function saveSettings(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        // Printify settings
        if (isset($_POST['printify_api_key'])) {
            $this->saveEncryptedOption('wpwps_printify_api_key', sanitize_text_field($_POST['printify_api_key']));
        }
        
        if (isset($_POST['printify_api_endpoint'])) {
            update_option('wpwps_printify_api_endpoint', esc_url_raw($_POST['printify_api_endpoint']));
        }
        
        if (isset($_POST['printify_shop_id'])) {
            update_option('wpwps_printify_shop_id', sanitize_text_field($_POST['printify_shop_id']));
        }

        // OpenAI settings
        if (isset($_POST['openai_api_key'])) {
            $this->saveEncryptedOption('wpwps_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        }
        
        if (isset($_POST['openai_token_limit'])) {
            update_option('wpwps_openai_token_limit', intval($_POST['openai_token_limit']));
        }
        
        if (isset($_POST['openai_spend_cap'])) {
            update_option('wpwps_openai_spend_cap', floatval($_POST['openai_spend_cap']));
        }
        
        if (isset($_POST['openai_temperature'])) {
            update_option('wpwps_openai_temperature', floatval($_POST['openai_temperature']));
        }

        wp_send_json_success(['message' => 'Settings saved successfully']);
    }

    private function saveEncryptedOption(string $option_name, string $value): void
    {
        if (empty($value)) {
            delete_option($option_name);
            return;
        }

        $encrypted_value = $this->encrypt($value);
        update_option($option_name, $encrypted_value);
    }

    private function encrypt(string $value): string
    {
        if (!function_exists('openssl_encrypt')) {
            // Fallback if OpenSSL is not available
            return base64_encode($value);
        }

        $encryption_key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $encryption_key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Render settings page
     */
    public function renderSettings(): void
    {
        $data = [
            'printify_api_key' => $this->getEncryptedOption('wpwps_printify_api_key'),
            'printify_api_endpoint' => get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1'),
            'printify_shop_id' => get_option('wpwps_printify_shop_id', ''),
            'shops' => $this->getShopsList(),
            'openai_api_key' => $this->getEncryptedOption('wpwps_openai_api_key'),
            'openai_token_limit' => get_option('wpwps_openai_token_limit', 1000),
            'openai_spend_cap' => get_option('wpwps_openai_spend_cap', 10),
            'openai_temperature' => get_option('wpwps_openai_temperature', 0.7),
        ];
        
        // Use blade template
        echo $this->templateEngine->render('admin.settings', $data);
    }
    
    /**
     * Get the list of shops from Printify
     * 
     * @return array
     */
    private function getShopsList(): array
    {
        $api_key = $this->getEncryptedOption('wpwps_printify_api_key');
        $api_endpoint = get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1');
        
        if (empty($api_key)) {
            return [];
        }
        
        $response = $this->printifyApi->getShops($api_key, $api_endpoint);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * Test Printify API connection
     */
    public function testPrintifyConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : 'https://api.printify.com/v1';

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key is required']);
            return;
        }

        $response = $this->printifyApi->testConnection($api_key, $api_endpoint);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'message' => 'Connection successful!',
            'shops' => $response['data']
        ]);
    }
    
    /**
     * Test OpenAI API connection and calculate estimated cost
     */
    public function testOpenAiConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $token_limit = isset($_POST['token_limit']) ? intval($_POST['token_limit']) : 1000;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
        $spend_cap = isset($_POST['spend_cap']) ? floatval($_POST['spend_cap']) : 10;

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'OpenAI API key is required']);
            return;
        }

        $response = $this->openAiApi->testConnection($api_key);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        // Calculate estimated monthly cost
        $cost_per_token = 0.000002; // Approximate cost per token for GPT-3.5-Turbo
        $estimated_daily_tickets = 10; // Assumed average
        $avg_tokens_per_request = $token_limit * 0.75; // Average usage
        $daily_cost = $estimated_daily_tickets * $avg_tokens_per_request * $cost_per_token;
        $monthly_cost = $daily_cost * 30;

        wp_send_json_success([
            'message' => 'OpenAI API connection successful!',
            'estimated_monthly_cost' => '$' . number_format($monthly_cost, 2),
            'model_info' => $response['data']
        ]);
    }

    /**
     * Save plugin settings
     */
    public function saveSettings(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        // Printify settings
        if (isset($_POST['printify_api_key'])) {
            $this->saveEncryptedOption('wpwps_printify_api_key', sanitize_text_field($_POST['printify_api_key']));
        }
        
        if (isset($_POST['printify_api_endpoint'])) {
            update_option('wpwps_printify_api_endpoint', esc_url_raw($_POST['printify_api_endpoint']));
        }
        
        if (isset($_POST['printify_shop_id'])) {
            update_option('wpwps_printify_shop_id', sanitize_text_field($_POST['printify_shop_id']));
        }

        // OpenAI settings
        if (isset($_POST['openai_api_key'])) {
            $this->saveEncryptedOption('wpwps_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        }
        
        if (isset($_POST['openai_token_limit'])) {
            update_option('wpwps_openai_token_limit', intval($_POST['openai_token_limit']));
        }
        
        if (isset($_POST['openai_spend_cap'])) {
            update_option('wpwps_openai_spend_cap', floatval($_POST['openai_spend_cap']));
        }
        
        if (isset($_POST['openai_temperature'])) {
            update_option('wpwps_openai_temperature', floatval($_POST['openai_temperature']));
        }

        wp_send_json_success(['message' => 'Settings saved successfully']);
    }

    private function saveEncryptedOption(string $option_name, string $value): void
    {
        if (empty($value)) {
            delete_option($option_name);
            return;
        }

        $encrypted_value = $this->encrypt($value);
        update_option($option_name, $encrypted_value);
    }

    private function encrypt(string $value): string
    {
        if (!function_exists('openssl_encrypt')) {
            // Fallback if OpenSSL is not available
            return base64_encode($value);
        }

        $encryption_key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $encryption_key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Render settings page - delegate to SettingsManager
     */
    public function renderSettings(): void
    {
        $this->settingsManager->render();
    }
}
