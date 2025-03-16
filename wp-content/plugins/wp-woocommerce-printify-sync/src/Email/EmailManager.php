<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class EmailManager
{
    private const EMAIL_TYPES = [
        'ticket_created' => TicketCreatedEmail::class,
        'ticket_reply' => TicketReplyEmail::class,
        'ticket_status' => TicketStatusEmail::class,
        'ticket_resolved' => TicketResolvedEmail::class,
        'internal_note' => InternalNoteEmail::class
    ];

    public function register(): void
    {
        // Register email classes with WooCommerce
        add_filter('woocommerce_email_classes', [$this, 'registerEmailClasses']);
        
        // Add email settings section
        add_filter('woocommerce_get_settings_email', [$this, 'addEmailSettings'], 10, 2);
        
        // Add custom email actions
        add_filter('woocommerce_email_actions', [$this, 'registerEmailActions']);
        
        // Add template override paths
        add_filter('woocommerce_template_directory', [$this, 'addTemplatePath'], 10, 2);
        
        // Initialize custom email hooks
        $this->initializeEmailHooks();
    }

    public function registerEmailClasses(array $emails): array
    {
        foreach (self::EMAIL_TYPES as $id => $class) {
            $emails["wpwps_{$id}"] = new $class();
        }
        return $emails;
    }

    public function addEmailSettings(array $settings, string $current_section): array
    {
        // Only add to the email section
        if ($current_section !== '') {
            return $settings;
        }

        $ticket_settings = [
            [
                'title' => __('Support Ticket Emails', 'wp-woocommerce-printify-sync'),
                'type' => 'title',
                'desc' => __('Email settings for support ticket notifications.', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_ticket_email_options'
            ],
            [
                'title' => __('Global Reply-To', 'wp-woocommerce-printify-sync'),
                'desc' => __('Email address that customers should reply to', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_ticket_reply_to',
                'type' => 'email',
                'default' => get_option('admin_email')
            ],
            [
                'title' => __('Email Threading', 'wp-woocommerce-printify-sync'),
                'desc' => __('Enable email threading support', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_enable_email_threading',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'title' => __('Auto-Response', 'wp-woocommerce-printify-sync'),
                'desc' => __('Send automatic response for new tickets', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_enable_auto_response',
                'type' => 'checkbox',
                'default' => 'yes'
            ],
            [
                'type' => 'sectionend',
                'id' => 'wpwps_ticket_email_options'
            ]
        ];

        return array_merge($settings, $ticket_settings);
    }

    public function registerEmailActions(array $actions): array
    {
        $actions[] = 'wpwps_ticket_created';
        $actions[] = 'wpwps_ticket_reply';
        $actions[] = 'wpwps_ticket_status_changed';
        $actions[] = 'wpwps_ticket_resolved';
        $actions[] = 'wpwps_internal_note_added';
        
        return $actions;
    }

    public function addTemplatePath(string $template_path, string $template): string
    {
        if (strpos($template, 'emails/ticket-') === 0) {
            return 'wp-woocommerce-printify-sync';
        }
        return $template_path;
    }

    private function initializeEmailHooks(): void
    {
        // Ticket Created
        add_action('wpwps_ticket_created', function(int $ticket_id, array $ticket_data) {
            $mailer = WC()->mailer();
            $email = $mailer->get_emails()['wpwps_ticket_created'];
            if ($email) {
                $email->trigger($ticket_id, $ticket_data);
            }
        }, 10, 2);

        // Ticket Reply
        add_action('wpwps_ticket_reply', function(int $ticket_id, array $reply_data) {
            $mailer = WC()->mailer();
            $email = $mailer->get_emails()['wpwps_ticket_reply'];
            if ($email) {
                $email->trigger($ticket_id, $reply_data);
            }
        }, 10, 2);

        // Status Change
        add_action('wpwps_ticket_status_changed', function(int $ticket_id, string $new_status, string $old_status) {
            $mailer = WC()->mailer();
            $email = $mailer->get_emails()['wpwps_ticket_status'];
            if ($email) {
                $email->trigger($ticket_id, [
                    'new_status' => $new_status,
                    'old_status' => $old_status
                ]);
            }
        }, 10, 3);

        // Internal Note
        add_action('wpwps_internal_note_added', function(int $ticket_id, array $note_data) {
            $mailer = WC()->mailer();
            $email = $mailer->get_emails()['wpwps_internal_note'];
            if ($email && !empty($note_data['notify_customer'])) {
                $email->trigger($ticket_id, $note_data);
            }
        }, 10, 2);
    }

    public function getEmailPreviewContent(string $email_type, array $sample_data = []): string
    {
        $mailer = WC()->mailer();
        $email = $mailer->get_emails()["wpwps_{$email_type}"] ?? null;

        if (!$email) {
            return '';
        }

        // Set preview mode
        $email->is_preview = true;

        // Generate sample data if not provided
        if (empty($sample_data)) {
            $sample_data = $this->generateSampleData($email_type);
        }

        ob_start();
        $email->style_inline(
            $email->get_content_html()
        );
        return ob_get_clean();
    }

    private function generateSampleData(string $email_type): array
    {
        $base_data = [
            'ticket_id' => 12345,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'subject' => 'Sample Support Ticket',
            'status' => 'open',
            'created_at' => current_time('mysql'),
            'order_id' => 789
        ];

        switch ($email_type) {
            case 'ticket_reply':
                return array_merge($base_data, [
                    'response' => 'This is a sample response to your ticket.',
                    'agent_name' => 'Support Agent'
                ]);
            case 'ticket_status':
                return array_merge($base_data, [
                    'new_status' => 'in_progress',
                    'old_status' => 'open',
                    'status_note' => 'Your ticket is now being processed.'
                ]);
            case 'internal_note':
                return array_merge($base_data, [
                    'note' => 'Internal note for staff reference.',
                    'notify_customer' => true
                ]);
            default:
                return $base_data;
        }
    }
}