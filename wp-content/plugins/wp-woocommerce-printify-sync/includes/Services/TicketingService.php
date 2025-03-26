<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class TicketingService {
    private $api_key;
    private $email_service;
    private $ticket_post_type = 'wpwps_ticket';

    public function __construct() {
        $this->api_key = get_option('wpwps_openai_api_key');
        $this->email_service = new EmailService();
        
        add_action('init', [$this, 'registerPostType']);
        add_action('wpwps_fetch_emails', [$this, 'fetchEmails']);
        add_action('wpwps_process_ticket_reminders', [$this, 'processReminders']);
    }

    public function registerPostType(): void {
        register_post_type($this->ticket_post_type, [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync')
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'comments'],
            'capability_type' => 'post',
            'map_meta_cap' => true
        ]);

        register_taxonomy('ticket_status', $this->ticket_post_type, [
            'label' => __('Ticket Status', 'wp-woocommerce-printify-sync'),
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_admin_column' => true
        ]);
    }

    public function fetchEmails(): void {
        $pop3_host = get_option('wpwps_pop3_host');
        $pop3_user = get_option('wpwps_pop3_user');
        $pop3_pass = get_option('wpwps_pop3_pass');

        if (!$pop3_host || !$pop3_user || !$pop3_pass) {
            return;
        }

        $pop3 = imap_open(
            "{{$pop3_host}:995/pop3/ssl/novalidate-cert}INBOX",
            $pop3_user,
            $pop3_pass
        );

        if (!$pop3) {
            do_action('wpwps_log_error', 'POP3 connection failed', [
                'error' => imap_last_error()
            ]);
            return;
        }

        $emails = imap_search($pop3, 'ALL');

        if ($emails) {
            foreach ($emails as $email_number) {
                $header = imap_headerinfo($pop3, $email_number);
                $body = imap_fetchbody($pop3, $email_number, 1);
                
                // Create ticket from email
                $this->createTicketFromEmail($header, $body);
                
                // Mark email for deletion
                imap_delete($pop3, $email_number);
            }
        }

        imap_expunge($pop3);
        imap_close($pop3);
    }

    private function createTicketFromEmail(\stdClass $header, string $body): void {
        // Extract email data
        $from = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        $subject = $header->subject;
        
        // Use GPT-3 to analyze email content
        $analysis = $this->analyzeWithGPT($body);
        
        // Create ticket
        $ticket_data = [
            'post_type' => $this->ticket_post_type,
            'post_title' => $subject,
            'post_content' => $body,
            'post_status' => 'publish'
        ];

        $ticket_id = wp_insert_post($ticket_data);

        if ($ticket_id) {
            // Store metadata
            update_post_meta($ticket_id, '_customer_email', $from);
            update_post_meta($ticket_id, '_ai_analysis', $analysis);
            
            if (!empty($analysis['order_id'])) {
                update_post_meta($ticket_id, '_order_id', $analysis['order_id']);
            }

            // Set initial status
            wp_set_object_terms($ticket_id, 'new', 'ticket_status');

            // If no evidence provided but needed
            if ($analysis['requires_evidence'] && !$analysis['has_evidence']) {
                wp_set_object_terms($ticket_id, 'awaiting_evidence', 'ticket_status');
                $this->sendEvidenceRequest($from, $ticket_id);
            }
        }
    }

    private function analyzeWithGPT(string $content): array {
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $prompt = [
            [
                'role' => 'system',
                'content' => 'You are a customer service AI assistant. Analyze the following email and extract: 
                1. Order number if present
                2. Type of issue (refund/reprint request)
                3. Reason for request
                4. Whether evidence (photos) are provided
                5. Whether evidence is required for this type of issue
                6. Suggested response
                Return as JSON.'
            ],
            [
                'role' => 'user',
                'content' => $content
            ]
        ];

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $prompt,
                'temperature' => 0.3
            ])
        ]);

        if (is_wp_error($response)) {
            return [
                'error' => $response->get_error_message()
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return json_decode($body['choices'][0]['message']['content'], true);
    }

    private function sendEvidenceRequest(string $to, int $ticket_id): void {
        $message = sprintf(
            __('Thank you for your request. To process this, we need photos showing the issue. Please reply to this email with clear photos within 7 days. Ticket ID: %s', 'wp-woocommerce-printify-sync'),
            $ticket_id
        );

        $this->email_service->queueEmail(
            $to,
            __('Evidence Required for Your Request', 'wp-woocommerce-printify-sync'),
            $message
        );

        // Schedule reminder for 3 days if no response
        wp_schedule_single_event(
            time() + (3 * DAY_IN_SECONDS),
            'wpwps_check_evidence_reminder',
            [$ticket_id]
        );
    }

    public function processReminders(): void {
        $tickets = get_posts([
            'post_type' => $this->ticket_post_type,
            'tax_query' => [
                [
                    'taxonomy' => 'ticket_status',
                    'field' => 'slug',
                    'terms' => 'awaiting_evidence'
                ]
            ],
            'meta_query' => [
                [
                    'key' => '_evidence_requested_date',
                    'value' => date('Y-m-d H:i:s', strtotime('-7 days')),
                    'compare' => '<=',
                    'type' => 'DATETIME'
                ]
            ]
        ]);

        foreach ($tickets as $ticket) {
            $customer_email = get_post_meta($ticket->ID, '_customer_email', true);
            
            // Send final reminder
            $message = sprintf(
                __('This is a final reminder that we need photos to process your request. If we don\'t receive them within 24 hours, your ticket will be closed. Ticket ID: %s', 'wp-woocommerce-printify-sync'),
                $ticket->ID
            );

            $this->email_service->queueEmail(
                $customer_email,
                __('Final Reminder: Evidence Required', 'wp-woocommerce-printify-sync'),
                $message
            );

            // Schedule ticket closure
            wp_schedule_single_event(
                time() + DAY_IN_SECONDS,
                'wpwps_close_ticket_no_evidence',
                [$ticket->ID]
            );
        }
    }

    public function getTicketStats(): array {
        $stats = [
            'total' => 0,
            'new' => 0,
            'awaiting_evidence' => 0,
            'in_progress' => 0,
            'resolved' => 0
        ];

        $terms = get_terms([
            'taxonomy' => 'ticket_status',
            'hide_empty' => false
        ]);

        foreach ($terms as $term) {
            $count = $term->count;
            $stats[$term->slug] = $count;
            $stats['total'] += $count;
        }

        return $stats;
    }

    public function getSuggestedResponse(int $ticket_id): string {
        $ticket = get_post($ticket_id);
        
        if (!$ticket) {
            return '';
        }

        $analysis = get_post_meta($ticket_id, '_ai_analysis', true);
        
        if (!empty($analysis['suggested_response'])) {
            return $analysis['suggested_response'];
        }

        // Generate new response if none exists
        $prompt = [
            [
                'role' => 'system',
                'content' => 'You are a customer service representative. Generate a professional and empathetic response to the following customer email.'
            ],
            [
                'role' => 'user',
                'content' => $ticket->post_content
            ]
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $prompt,
                'temperature' => 0.7
            ])
        ]);

        if (is_wp_error($response)) {
            return '';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $suggested_response = $body['choices'][0]['message']['content'];

        update_post_meta($ticket_id, '_ai_analysis', array_merge(
            (array) $analysis,
            ['suggested_response' => $suggested_response]
        ));

        return $suggested_response;
    }
}