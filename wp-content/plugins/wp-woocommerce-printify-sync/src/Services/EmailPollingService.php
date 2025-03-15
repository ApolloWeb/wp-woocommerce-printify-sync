<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class EmailPollingService
{
    private string $currentTime = '2025-03-15 19:05:35';
    private string $currentUser = 'ApolloWeb';
    private TicketingService $ticketingService;

    public function __construct()
    {
        $this->ticketingService = new TicketingService();

        // Postie specific hooks
        add_filter('postie_filter_email', [$this, 'filterEmail'], 10, 1);
        add_action('postie_post_after', [$this, 'processPostieEmail'], 10, 2);
        
        // Add Postie settings
        add_filter('postie_pages', [$this, 'addPostieSettings']);
    }

    /**
     * Add custom settings to Postie configuration
     */
    public function addPostieSettings(array $pages): array
    {
        $pages['wpwps_tickets'] = [
            'page_title' => __('Ticket Settings', 'wp-woocommerce-printify-sync'),
            'menu_title' => __('Ticket Settings', 'wp-woocommerce-printify-sync'),
            'settings' => [
                'wpwps_ticket_email' => [
                    'title' => __('Ticket Email Address', 'wp-woocommerce-printify-sync'),
                    'description' => __('Emails sent to this address will create tickets', 'wp-woocommerce-printify-sync'),
                    'type' => 'text',
                    'default' => 'support@yourdomain.com'
                ],
                'wpwps_auto_categorize' => [
                    'title' => __('Auto Categorize', 'wp-woocommerce-printify-sync'),
                    'description' => __('Automatically categorize tickets based on content', 'wp-woocommerce-printify-sync'),
                    'type' => 'checkbox',
                    'default' => 'yes'
                ]
            ]
        ];

        return $pages;
    }

    /**
     * Filter incoming emails before Postie processes them
     */
    public function filterEmail(array $email): array
    {
        // Only process emails sent to the ticket email address
        $ticketEmail = get_option('wpwps_ticket_email');
        if (!empty($ticketEmail) && !$this->isEmailForTicketing($email, $ticketEmail)) {
            return $email;
        }

        // Add custom header to identify ticket emails
        $email['headers']['X-WPWPS-Ticket'] = 'true';
        
        return $email;
    }

    /**
     * Process emails after Postie has handled them
     */
    public function processPostieEmail(int $post_ID, array $email): void
    {
        // Check if this is a ticket email
        if (empty($email['headers']['X-WPWPS-Ticket'])) {
            return;
        }

        try {
            $ticketData = $this->extractTicketData($email, $post_ID);
            
            // Handle existing ticket replies
            if ($ticketId = $this->extractTicketId($email['subject'])) {
                $this->ticketingService->addMessage(
                    $ticketId,
                    $ticketData['message'],
                    $ticketData['attachments']
                );
            } else {
                // Create new ticket
                $this->ticketingService->createTicket($ticketData);
            }

            // Delete the post created by Postie as we don't need it
            wp_delete_post($post_ID, true);

        } catch (\Exception $e) {
            error_log("WPWPS Ticket Processing Error: " . $e->getMessage());
        }
    }

    private function extractTicketData(array $email, int $post_ID): array
    {
        $post = get_post($post_ID);
        $attachments = [];

        // Get attachments that Postie has already processed
        if ($post) {
            $attachment_ids = get_posts([
                'post_parent' => $post_ID,
                'post_type' => 'attachment',
                'fields' => 'ids'
            ]);

            foreach ($attachment_ids as $attach_id) {
                $file = get_attached_file($attach_id);
                if ($file) {
                    $attachments[] = [
                        'name' => basename($file),
                        'path' => $file,
                        'type' => get_post_mime_type($attach_id)
                    ];
                }
            }
        }

        return [
            'subject' => $email['subject'] ?? '',
            'customer_email' => $this->extractEmailAddress($email['from']),
            'message' => $post ? $post->post_content : '',
            'attachments' => $attachments,
            'order_id' => $this->extractOrderId($post ? $post->post_content : ''),
            'priority' => $this->determinePriority($email['subject'], $post ? $post->post_content : '')
        ];
    }

    private function isEmailForTicketing(array $email, string $ticketEmail): bool
    {
        return (
            stripos($email['to'] ?? '', $ticketEmail) !== false ||
            stripos($email['cc'] ?? '', $ticketEmail) !== false
        );
    }

    // ... rest of the helper methods remain the same
}