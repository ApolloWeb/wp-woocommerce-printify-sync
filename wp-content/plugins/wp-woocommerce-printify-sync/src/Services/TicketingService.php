<?php
/**
 * Ticketing Service
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;

/**
 * Class TicketingService
 *
 * Handles support ticket system with AI integration
 */
class TicketingService
{
    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Container
     *
     * @var \ApolloWeb\WPWooCommercePrintifySync\Services\Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param \ApolloWeb\WPWooCommercePrintifySync\Services\Container $container Service container.
     */
    public function __construct($container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
    }

    /**
     * Initialize the service
     *
     * @return void
     */
    public function init(): void
    {
        // Register hooks for cron jobs
        add_action('wpwps_fetch_email_tickets', [$this, 'fetchEmailTickets']);
        add_action('wpwps_process_tickets_with_ai', [$this, 'processTicketsWithAI']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_tickets', [$this, 'ajaxGetTickets']);
        add_action('wp_ajax_wpwps_get_ticket', [$this, 'ajaxGetTicket']);
        add_action('wp_ajax_wpwps_reply_to_ticket', [$this, 'ajaxReplyToTicket']);
        add_action('wp_ajax_wpwps_close_ticket', [$this, 'ajaxCloseTicket']);
        add_action('wp_ajax_wpwps_reopen_ticket', [$this, 'ajaxReopenTicket']);
        add_action('wp_ajax_wpwps_get_ai_response', [$this, 'ajaxGetAIResponse']);
    }

    /**
     * Fetch tickets from email inbox
     *
     * @return void
     */
    public function fetchEmailTickets(): void
    {
        $pop3_enabled = get_option('wpwps_enable_pop3', false);
        
        if (!$pop3_enabled) {
            $this->logger->debug('POP3 email fetching disabled');
            return;
        }
        
        $pop3_server = get_option('wpwps_pop3_server', '');
        $pop3_port = intval(get_option('wpwps_pop3_port', 110));
        $pop3_username = get_option('wpwps_pop3_username', '');
        $pop3_password = $this->getDecryptedPop3Password();
        $pop3_ssl = get_option('wpwps_pop3_ssl', false);
        
        // Validate settings
        if (empty($pop3_server) || empty($pop3_username) || empty($pop3_password)) {
            $this->logger->error('POP3 settings incomplete');
            return;
        }
        
        // Connect to POP3 server
        $this->logger->debug('Connecting to POP3 server', ['server' => $pop3_server, 'port' => $pop3_port]);
        
        try {
            $connection_string = ($pop3_ssl ? 'ssl://' : '') . $pop3_server . ':' . $pop3_port;
            $socket = fsockopen($connection_string, $pop3_port, $errno, $errstr, 30);
            
            if (!$socket) {
                $this->logger->error('Failed to connect to POP3 server', [
                    'error' => $errno . ': ' . $errstr,
                ]);
                return;
            }
            
            // Skip server greeting
            fgets($socket, 1024);
            
            // USER command
            fputs($socket, "USER $pop3_username\r\n");
            $response = fgets($socket, 1024);
            if (strpos($response, '+OK') === false) {
                $this->logger->error('POP3 USER command failed', ['response' => $response]);
                fclose($socket);
                return;
            }
            
            // PASS command
            fputs($socket, "PASS $pop3_password\r\n");
            $response = fgets($socket, 1024);
            if (strpos($response, '+OK') === false) {
                $this->logger->error('POP3 PASS command failed', ['response' => $response]);
                fclose($socket);
                return;
            }
            
            // Get message count
            fputs($socket, "STAT\r\n");
            $response = fgets($socket, 1024);
            if (strpos($response, '+OK') === false) {
                $this->logger->error('POP3 STAT command failed', ['response' => $response]);
                fclose($socket);
                return;
            }
            
            $parts = explode(' ', $response);
            $message_count = intval($parts[1]);
            
            $this->logger->debug('POP3 messages found', ['count' => $message_count]);
            
            // Process messages
            for ($i = 1; $i <= $message_count; $i++) {
                // Get message
                fputs($socket, "RETR $i\r\n");
                $response = fgets($socket, 1024);
                
                if (strpos($response, '+OK') === false) {
                    $this->logger->error('POP3 RETR command failed', [
                        'message' => $i,
                        'response' => $response,
                    ]);
                    continue;
                }
                
                // Get message content
                $message = '';
                while (!feof($socket)) {
                    $line = fgets($socket, 1024);
                    if (rtrim($line) === '.') {
                        break;
                    }
                    $message .= $line;
                }
                
                // Process message
                $this->processEmail($message);
                
                // Mark as read (delete)
                fputs($socket, "DELE $i\r\n");
                fgets($socket, 1024);
            }
            
            // Quit
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            $this->logger->debug('POP3 email fetching completed');
            
        } catch (\Exception $e) {
            $this->logger->error('POP3 error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get decrypted POP3 password
     *
     * @return string
     */
    private function getDecryptedPop3Password(): string
    {
        $encrypted_password = get_option('wpwps_pop3_password', '');
        
        if (empty($encrypted_password)) {
            return '';
        }
        
        $api_service = new ApiService($this->logger);
        return $api_service->decrypt($encrypted_password);
    }

    /**
     * Process an email message
     *
     * @param string $message Email message content
     * @return bool
     */
    private function processEmail(string $message): bool
    {
        // Parse email headers and content
        $parsed_email = $this->parseEmail($message);
        
        if (empty($parsed_email['from']) || empty($parsed_email['subject']) || empty($parsed_email['body'])) {
            $this->logger->error('Failed to parse email');
            return false;
        }
        
        // Extract sender email
        $sender_email = '';
        if (preg_match('/<([^>]+)>/', $parsed_email['from'], $matches)) {
            $sender_email = $matches[1];
        } else {
            $sender_email = $parsed_email['from'];
        }
        
        // Validate email
        if (!is_email($sender_email)) {
            $this->logger->error('Invalid sender email', ['email' => $sender_email]);
            return false;
        }
        
        // Create ticket
        $ticket_id = $this->createTicket($sender_email, $parsed_email['subject'], $parsed_email['body']);
        
        if (!$ticket_id) {
            $this->logger->error('Failed to create ticket');
            return false;
        }
        
        // Process attachments
        if (!empty($parsed_email['attachments'])) {
            foreach ($parsed_email['attachments'] as $attachment) {
                $this->saveAttachment($ticket_id, null, $attachment['filename'], $attachment['content'], $attachment['mime_type']);
            }
        }
        
        // Schedule AI processing
        $scheduler = $this->container->get('action_scheduler');
        $scheduler->scheduleTask('wpwps_process_ticket_with_ai', ['ticket_id' => $ticket_id], time() + 60);
        
        $this->logger->debug('Ticket created from email', [
            'ticket_id' => $ticket_id,
            'email' => $sender_email,
        ]);
        
        return true;
    }

    /**
     * Parse email message
     *
     * @param string $message Raw email message
     * @return array Parsed email parts
     */
    private function parseEmail(string $message): array
    {
        $result = [
            'from' => '',
            'subject' => '',
            'body' => '',
            'attachments' => [],
        ];
        
        // Split headers and body
        $parts = explode("\r\n\r\n", $message, 2);
        $headers = $parts[0];
        $body = isset($parts[1]) ? $parts[1] : '';
        
        // Parse headers
        $header_lines = explode("\r\n", $headers);
        $current_header = '';
        $headers_parsed = [];
        
        foreach ($header_lines as $line) {
            if (preg_match('/^([A-Za-z-]+):\s*(.+)$/', $line, $matches)) {
                $current_header = strtolower($matches[1]);
                $headers_parsed[$current_header] = $matches[2];
            } elseif (!empty($current_header) && preg_match('/^\s+(.+)$/', $line, $matches)) {
                // Continuation of previous header
                $headers_parsed[$current_header] .= ' ' . $matches[1];
            }
        }
        
        // Get essential headers
        $result['from'] = isset($headers_parsed['from']) ? $headers_parsed['from'] : '';
        $result['subject'] = isset($headers_parsed['subject']) ? $this->decodeHeader($headers_parsed['subject']) : '';
        
        // Process content type and encoding
        $content_type = isset($headers_parsed['content-type']) ? $headers_parsed['content-type'] : 'text/plain';
        $transfer_encoding = isset($headers_parsed['content-transfer-encoding']) ? $headers_parsed['content-transfer-encoding'] : '7bit';
        
        // Handle multipart messages
        if (strpos($content_type, 'multipart/') === 0) {
            // Extract boundary
            if (preg_match('/boundary="?([^";]+)"?/', $content_type, $matches)) {
                $boundary = $matches[1];
                $parts = explode('--' . $boundary, $body);
                
                // First part is usually empty
                array_shift($parts);
                
                // Last part is boundary end marker
                array_pop($parts);
                
                $text_body = '';
                
                foreach ($parts as $part) {
                    $part_headers = '';
                    $part_body = '';
                    
                    // Split part headers and body
                    $part_segments = explode("\r\n\r\n", $part, 2);
                    if (count($part_segments) === 2) {
                        $part_headers = $part_segments[0];
                        $part_body = $part_segments[1];
                    }
                    
                    // Parse part content type
                    $part_content_type = 'text/plain';
                    if (preg_match('/Content-Type:\s*([^;]+)/i', $part_headers, $matches)) {
                        $part_content_type = trim($matches[1]);
                    }
                    
                    // Parse part encoding
                    $part_encoding = '7bit';
                    if (preg_match('/Content-Transfer-Encoding:\s*([^;]+)/i', $part_headers, $matches)) {
                        $part_encoding = trim($matches[1]);
                    }
                    
                    // Handle attachment
                    if (preg_match('/Content-Disposition:\s*attachment/i', $part_headers)) {
                        $filename = 'attachment';
                        if (preg_match('/filename="?([^";]+)"?/i', $part_headers, $matches)) {
                            $filename = $matches[1];
                        }
                        
                        // Decode attachment content
                        $content = $this->decodeContent($part_body, $part_encoding);
                        
                        $result['attachments'][] = [
                            'filename' => $filename,
                            'content' => $content,
                            'mime_type' => $part_content_type,
                        ];
                        
                    } elseif (strpos($part_content_type, 'text/plain') === 0) {
                        // Plain text body
                        $decoded_body = $this->decodeContent($part_body, $part_encoding);
                        $text_body .= $decoded_body;
                    }
                }
                
                $result['body'] = $text_body;
                
            } else {
                // Couldn't extract boundary
                $result['body'] = $body;
            }
        } else {
            // Simple message
            $result['body'] = $this->decodeContent($body, $transfer_encoding);
        }
        
        return $result;
    }

    /**
     * Decode email header
     *
     * @param string $header Header value
     * @return string Decoded header
     */
    private function decodeHeader(string $header): string
    {
        if (strpos($header, '=?') === false) {
            return $header;
        }
        
        $parts = imap_mime_header_decode($header);
        $result = '';
        
        foreach ($parts as $part) {
            $result .= $part->text;
        }
        
        return $result;
    }

    /**
     * Decode content based on transfer encoding
     *
     * @param string $content Content to decode
     * @param string $encoding Content transfer encoding
     * @return string Decoded content
     */
    private function decodeContent(string $content, string $encoding): string
    {
        switch (strtolower($encoding)) {
            case 'base64':
                return base64_decode($content);
            case 'quoted-printable':
                return quoted_printable_decode($content);
            default:
                return $content;
        }
    }

    /**
     * Create a new ticket
     *
     * @param string $email Customer email
     * @param string $subject Ticket subject
     * @param string $content Ticket content
     * @param int|null $order_id Related order ID (optional)
     * @return int|false Ticket ID or false on failure
     */
    public function createTicket(string $email, string $subject, string $content, ?int $order_id = null)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        // Validate email
        if (!is_email($email)) {
            $this->logger->error('Invalid email address', ['email' => $email]);
            return false;
        }
        
        // Insert ticket
        $result = $wpdb->insert(
            $table_name,
            [
                'email' => $email,
                'subject' => $subject,
                'content' => $content,
                'status' => 'open',
                'order_id' => $order_id,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
            ]
        );
        
        if (false === $result) {
            $this->logger->error('Failed to create ticket', [
                'error' => $wpdb->last_error,
                'email' => $email,
                'subject' => $subject,
            ]);
            return false;
        }
        
        $ticket_id = $wpdb->insert_id;
        
        return $ticket_id;
    }

    /**
     * Save an attachment for a ticket
     *
     * @param int $ticket_id Ticket ID
     * @param int|null $reply_id Reply ID (null for ticket attachments)
     * @param string $filename Original filename
     * @param string $content File content
     * @param string $mime_type File MIME type
     * @return int|false Attachment ID or false on failure
     */
    public function saveAttachment(int $ticket_id, ?int $reply_id, string $filename, string $content, string $mime_type)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_ticket_attachments';
        
        // Create directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $attachments_dir = $upload_dir['basedir'] . '/wpwps-attachments/' . $ticket_id;
        
        if (!file_exists($attachments_dir)) {
            wp_mkdir_p($attachments_dir);
            
            // Create .htaccess to prevent direct access
            file_put_contents($attachments_dir . '/.htaccess', "Deny from all\n");
            
            // Create index.php to prevent directory listing
            file_put_contents($attachments_dir . '/index.php', '<?php // Silence is golden');
        }
        
        // Sanitize filename
        $safe_filename = sanitize_file_name($filename);
        
        // Ensure unique filename
        $path_parts = pathinfo($safe_filename);
        $base_name = $path_parts['filename'];
        $extension = isset($path_parts['extension']) ? '.' . $path_parts['extension'] : '';
        
        $counter = 1;
        $file_path = $attachments_dir . '/' . $safe_filename;
        
        while (file_exists($file_path)) {
            $safe_filename = $base_name . '-' . $counter . $extension;
            $file_path = $attachments_dir . '/' . $safe_filename;
            $counter++;
        }
        
        // Save file
        $result = file_put_contents($file_path, $content);
        
        if (false === $result) {
            $this->logger->error('Failed to save attachment', [
                'ticket_id' => $ticket_id,
                'filename' => $filename,
            ]);
            return false;
        }
        
        // Insert attachment record
        $file_size = filesize($file_path);
        
        $result = $wpdb->insert(
            $table_name,
            [
                'ticket_id' => $ticket_id,
                'reply_id' => $reply_id,
                'file_name' => $safe_filename,
                'file_path' => str_replace($upload_dir['basedir'], '', $file_path),
                'file_type' => $mime_type,
                'file_size' => $file_size,
                'created_at' => current_time('mysql', true),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
            ]
        );
        
        if (false === $result) {
            $this->logger->error('Failed to save attachment record', [
                'error' => $wpdb->last_error,
                'ticket_id' => $ticket_id,
                'filename' => $filename,
            ]);
            
            // Delete file
            unlink($file_path);
            
            return false;
        }
        
        $attachment_id = $wpdb->insert_id;
        
        return $attachment_id;
    }

    /**
     * Process tickets with AI
     *
     * @param int $ticket_id Ticket ID
     * @return void
     */
    public function processTicketsWithAI(int $ticket_id): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        // Get ticket
        $ticket = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $ticket_id
            )
        );
        
        if (!$ticket) {
            $this->logger->error('Ticket not found', ['ticket_id' => $ticket_id]);
            return;
        }
        
        // Extract order number and product info using AI
        $ai_service = new OpenAIService($this->logger);
        
        $prompt = "Extract information from this customer message. If an order number is mentioned, identify it. If specific products are mentioned, list them. If there's a complaint or issue, summarize it briefly. Here's the message:\n\n";
        $prompt .= $ticket->content;
        
        $response = $ai_service->generateCompletion($prompt);
        
        if (empty($response)) {
            $this->logger->error('Failed to process ticket with AI', ['ticket_id' => $ticket_id]);
            return;
        }
        
        // Extract order number
        $order_id = null;
        if (preg_match('/order(?:\s+number)?(?:\s*:)?\s*#?(\d+)/i', $response, $matches)) {
            $order_id = intval($matches[1]);
            
            // Verify order exists
            $order = wc_get_order($order_id);
            if ($order) {
                // Update ticket with order ID
                $wpdb->update(
                    $table_name,
                    ['order_id' => $order_id],
                    ['id' => $ticket_id],
                    ['%d'],
                    ['%d']
                );
                
                $this->logger->debug('Order ID extracted from ticket', [
                    'ticket_id' => $ticket_id,
                    'order_id' => $order_id,
                ]);
            }
        }
        
        // Store AI analysis as ticket meta
        $this->setTicketMeta($ticket_id, 'ai_analysis', $response);
        
        // Generate suggested response
        $this->generateAISuggestedResponse($ticket_id);
    }

    /**
     * Generate AI suggested response
     *
     * @param int $ticket_id Ticket ID
     * @return string|null
     */
    public function generateAISuggestedResponse(int $ticket_id): ?string
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        // Get ticket
        $ticket = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $ticket_id
            )
        );
        
        if (!$ticket) {
            $this->logger->error('Ticket not found', ['ticket_id' => $ticket_id]);
            return null;
        }
        
        // Get AI analysis
        $ai_analysis = $this->getTicketMeta($ticket_id, 'ai_analysis');
        
        // Get order details if available
        $order_details = '';
        if ($ticket->order_id) {
            $order = wc_get_order($ticket->order_id);
            if ($order) {
                $order_details = "\nOrder #{$ticket->order_id}";
                $order_details .= "\nStatus: " . $order->get_status();
                $order_details .= "\nDate: " . $order->get_date_created()->date('Y-m-d H:i:s');
                $order_details .= "\nItems:";
                
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    $order_details .= "\n- " . $item->get_name() . " (Qty: " . $item->get_quantity() . ")";
                }
            }
        }
        
        // Build the prompt
        $ai_service = new OpenAIService($this->logger);
        
        $prompt = "You are a customer service representative for an online store that sells print-on-demand products. ";
        $prompt .= "Write a helpful, empathetic, and professional response to this customer inquiry. ";
        $prompt .= "Be specific based on the customer's question but keep the tone warm and friendly. ";
        $prompt .= "Avoid making promises you can't keep.\n\n";
        
        $prompt .= "Customer message:\n" . $ticket->content . "\n\n";
        
        if ($ai_analysis) {
            $prompt .= "Analysis of the message: " . $ai_analysis . "\n\n";
        }
        
        if ($order_details) {
            $prompt .= "Order information: " . $order_details . "\n\n";
        }
        
        $prompt .= "Write your response now:";
        
        $response = $ai_service->generateCompletion($prompt);
        
        if (empty($response)) {
            $this->logger->error('Failed to generate AI response', ['ticket_id' => $ticket_id]);
            return null;
        }
        
        // Store the suggested response
        $this->setTicketMeta($ticket_id, 'ai_suggested_response', $response);
        
        return $response;
    }

    /**
     * Set ticket meta
     *
     * @param int $ticket_id Ticket ID
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @return bool
     */
    public function setTicketMeta(int $ticket_id, string $meta_key, $meta_value): bool
    {
        return update_metadata('wpwps_ticket', $ticket_id, $meta_key, $meta_value);
    }

    /**
     * Get ticket meta
     *
     * @param int $ticket_id Ticket ID
     * @param string $meta_key Meta key
     * @param bool $single Whether to return a single value
     * @return mixed
     */
    public function getTicketMeta(int $ticket_id, string $meta_key, bool $single = true)
    {
        return get_metadata('wpwps_ticket', $ticket_id, $meta_key, $single);
    }

    /**
     * AJAX handler: Get tickets
     *
     * @return void
     */
    public function ajaxGetTickets(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        // Get parameters
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $per_page = isset($_REQUEST['per_page']) ? intval($_REQUEST['per_page']) : 20;
        $status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        $search = isset($_REQUEST['search']) ? sanitize_text_field($_REQUEST['search']) : '';
        
        // Build query
        $where = "1=1";
        $where_args = [];
        
        if ($status) {
            $where .= " AND status = %s";
            $where_args[] = $status;
        }
        
        if ($search) {
            $where .= " AND (email LIKE %s OR subject LIKE %s OR content LIKE %s";
            $where_args[] = "%{$search}%";
            $where_args[] = "%{$search}%";
            $where_args[] = "%{$search}%";
            
            if (is_numeric($search)) {
                $where .= " OR id = %d OR order_id = %d";
                $where_args[] = intval($search);
                $where_args[] = intval($search);
            }
            
            $where .= ")";
        }
        
        // Prepare where clause
        $where = $wpdb->prepare($where, $where_args);
        
        // Count total tickets
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE {$where}");
        
        // Calculate offset
        $offset = ($page - 1) * $per_page;
        
        // Get tickets
        $tickets = $wpdb->get_results(
            "SELECT id, email, subject, status, order_id, created_at, updated_at 
            FROM {$table_name} 
            WHERE {$where} 
            ORDER BY updated_at DESC 
            LIMIT {$offset}, {$per_page}"
        );
        
        // Get unread count
        $unread_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE status = 'open'"
        );
        
        wp_send_json_success([
            'tickets' => $tickets,
            'total' => intval($total),
            'unread' => intval($unread_count),
            'pages' => ceil($total / $per_page),
            'page' => $page,
        ]);
    }

    /**
     * AJAX handler: Get ticket details
     *
     * @return void
     */
    public function ajaxGetTicket(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        global $wpdb;
        
        $ticket_id = isset($_REQUEST['ticket_id']) ? intval($_REQUEST['ticket_id']) : 0;
        
        if (!$ticket_id) {
            wp_send_json_error(['message' => 'Invalid ticket ID']);
            return;
        }
        
        $tickets_table = $wpdb->prefix . 'wpwps_tickets';
        $replies_table = $wpdb->prefix . 'wpwps_ticket_replies';
        $attachments_table = $wpdb->prefix . 'wpwps_ticket_attachments';
        
        // Get ticket
        $ticket = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tickets_table} WHERE id = %d",
                $ticket_id
            )
        );
        
        if (!$ticket) {
            wp_send_json_error(['message' => 'Ticket not found']);
            return;
        }
        
        // Get ticket replies
        $replies = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$replies_table} WHERE ticket_id = %d ORDER BY created_at ASC",
                $ticket_id
            )
        );
        
        // Get attachments
        $attachments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$attachments_table} WHERE ticket_id = %d ORDER BY created_at ASC",
                $ticket_id
            )
        );
        
        // Group attachments by reply ID
        $grouped_attachments = [];
        foreach ($attachments as $attachment) {
            $key = $attachment->reply_id ? $attachment->reply_id : 'ticket';
            if (!isset($grouped_attachments[$key])) {
                $grouped_attachments[$key] = [];
            }
            $grouped_attachments[$key][] = $attachment;
        }
        
        // Get order details if available
        $order = null;
        if ($ticket->order_id) {
            $order = wc_get_order($ticket->order_id);
            if ($order) {
                $order = [
                    'id' => $order->get_id(),
                    'status' => $order->get_status(),
                    'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
                    'total' => $order->get_total(),
                    'items' => [],
                ];
                
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    $order['items'][] = [
                        'name' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'total' => $item->get_total(),
                        'product_id' => $product ? $product->get_id() : null,
                    ];
                }
            }
        }
        
        // Get AI suggested response
        $ai_suggested_response = $this->getTicketMeta($ticket_id, 'ai_suggested_response');
        
        wp_send_json_success([
            'ticket' => $ticket,
            'replies' => $replies,
            'attachments' => $grouped_attachments,
            'order' => $order,
            'ai_suggested_response' => $ai_suggested_response,
        ]);
    }

    /**
     * AJAX handler: Reply to ticket
     *
     * @return void
     */
    public function ajaxReplyToTicket(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        global $wpdb;
        
        $ticket_id = isset($_REQUEST['ticket_id']) ? intval($_REQUEST['ticket_id']) : 0;
        $content = isset($_REQUEST['content']) ? sanitize_textarea_field($_REQUEST['content']) : '';
        
        if (!$ticket_id || empty($content)) {
            wp_send_json_error(['message' => 'Invalid ticket ID or empty content']);
            return;
        }
        
        $tickets_table = $wpdb->prefix . 'wpwps_tickets';
        $replies_table = $wpdb->prefix . 'wpwps_ticket_replies';
        
        // Check if ticket exists
        $ticket = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tickets_table} WHERE id = %d",
                $ticket_id
            )
        );
        
        if (!$ticket) {
            wp_send_json_error(['message' => 'Ticket not found']);
            return;
        }
        
        // Insert reply
        $result = $wpdb->insert(
            $replies_table,
            [
                'ticket_id' => $ticket_id,
                'is_customer' => 0, // Admin reply
                'content' => $content,
                'created_at' => current_time('mysql', true),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
            ]
        );
        
        if (false === $result) {
            wp_send_json_error([
                'message' => 'Failed to save reply',
                'error' => $wpdb->last_error,
            ]);
            return;
        }
        
        $reply_id = $wpdb->insert_id;
        
        // Update ticket status and updated_at timestamp
        $wpdb->update(
            $tickets_table,
            [
                'status' => 'waiting',
                'updated_at' => current_time('mysql', true),
            ],
            ['id' => $ticket_id],
            ['%s', '%s'],
            ['%d']
        );
        
        // Send email to customer
        $email = $ticket->email;
        $subject = 'Re: ' . $ticket->subject;
        
        // Include signature in email
        $signature = get_option('wpwps_email_signature', '');
        $email_content = $content;
        
        if ($signature) {
            $email_content .= "\n\n" . $signature;
        }
        
        // Queue email for sending
        $email_queue = $this->container->get('email_queue');
        $email_id = $email_queue->queueEmail($email, $subject, $email_content);
        
        wp_send_json_success([
            'reply_id' => $reply_id,
            'email_id' => $email_id,
            'message' => 'Reply sent successfully',
        ]);
    }

    /**
     * AJAX handler: Close ticket
     *
     * @return void
     */
    public function ajaxCloseTicket(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        global $wpdb;
        
        $ticket_id = isset($_REQUEST['ticket_id']) ? intval($_REQUEST['ticket_id']) : 0;
        
        if (!$ticket_id) {
            wp_send_json_error(['message' => 'Invalid ticket ID']);
            return;
        }
        
        $tickets_table = $wpdb->prefix . 'wpwps_tickets';
        
        // Update ticket status
        $result = $wpdb->update(
            $tickets_table,
            [
                'status' => 'closed',
                'updated_at' => current_time('mysql', true),
            ],
            ['id' => $ticket_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if (false === $result) {
            wp_send_json_error([
                'message' => 'Failed to close ticket',
                'error' => $wpdb->last_error,
            ]);
            return;
        }
        
        wp_send_json_success([
            'message' => 'Ticket closed successfully',
        ]);
    }

    /**
     * AJAX handler: Reopen ticket
     *
     * @return void
     */
    public function ajaxReopenTicket(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        global $wpdb;
        
        $ticket_id = isset($_REQUEST['ticket_id']) ? intval($_REQUEST['ticket_id']) : 0;
        
        if (!$ticket_id) {
            wp_send_json_error(['message' => 'Invalid ticket ID']);
            return;
        }
        
        $tickets_table = $wpdb->prefix . 'wpwps_tickets';
        
        // Update ticket status
        $result = $wpdb->update(
            $tickets_table,
            [
                'status' => 'open',
                'updated_at' => current_time('mysql', true),
            ],
            ['id' => $ticket_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if (false === $result) {
            wp_send_json_error([
                'message' => 'Failed to reopen ticket',
                'error' => $wpdb->last_error,
            ]);
            return;
        }
        
        wp_send_json_success([
            'message' => 'Ticket reopened successfully',
        ]);
    }

    /**
     * AJAX handler: Get AI response
     *
     * @return void
     */
    public function ajaxGetAIResponse(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        $ticket_id = isset($_REQUEST['ticket_id']) ? intval($_REQUEST['ticket_id']) : 0;
        
        if (!$ticket_id) {
            wp_send_json_error(['message' => 'Invalid ticket ID']);
            return;
        }
        
        // Generate AI response
        $response = $this->generateAISuggestedResponse($ticket_id);
        
        if (empty($response)) {
            wp_send_json_error([
                'message' => 'Failed to generate AI response',
            ]);
            return;
        }
        
        wp_send_json_success([
            'response' => $response,
        ]);
    }
}
