<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class TicketStatusEmail extends BaseTicketEmail
{
    protected $old_status;
    protected $new_status;
    protected $status_note;
    protected $status_colors = [
        'open' => '#28a745',
        'pending' => '#ffc107',
        'in_progress' => '#17a2b8',
        'on_hold' => '#6c757d',
        'resolved' => '#28a745',
        'closed' => '#dc3545'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->id = 'wpwps_ticket_status';
        $this->title = __('Support Ticket Status Update', 'wp-woocommerce-printify-sync');
        $this->description = __('This email is sent to customers when their ticket status changes.', 'wp-woocommerce-printify-sync');

        $this->template_html = 'emails/ticket-status.php';
        $this->template_plain = 'emails/plain/ticket-status.php';

        $this->placeholders = [
            '{ticket_id}' => '',
            '{site_title}' => $this->get_blogname(),
            '{customer_name}' => '',
            '{old_status}' => '',
            '{new_status}' => '',
            '{status_note}' => '',
        ];

        // Default subject and heading
        $this->heading = __('Ticket #{ticket_id} Status Update', 'wp-woocommerce-printify-sync');
        $this->subject = __('[{site_title}] Ticket #{ticket_id} - Status Changed to {new_status}', 'wp-woocommerce-printify-sync');
    }

    public function trigger($ticket_id = 0, $status_data = []): void
    {
        if (!$ticket_id || !is_numeric($ticket_id)) {
            return;
        }

        $this->ticket_id = $ticket_id;
        $this->ticket_data = $status_data;
        
        // Set status information
        $this->old_status = $status_data['old_status'] ?? '';
        $this->new_status = $status_data['new_status'] ?? '';
        $this->status_note = $status_data['status_note'] ?? '';

        // Check if we should send this email based on the status change
        if (!$this->should_send_status_email()) {
            return;
        }

        // Set recipient
        $this->recipient = $this->get_ticket_customer_email();

        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        // Send the email
        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );

        // Log status change
        $this->log_status_change();
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'label' => __('Enable this email notification', 'wp-woocommerce-printify-sync'),
                'default' => 'yes'
            ],
            'status_triggers' => [
                'title' => __('Status Change Triggers', 'wp-woocommerce-printify-sync'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'description' => __('Select which status changes should trigger this email', 'wp-woocommerce-printify-sync'),
                'default' => ['resolved', 'on_hold'],
                'options' => [
                    'open' => __('Opened', 'wp-woocommerce-printify-sync'),
                    'pending' => __('Pending', 'wp-woocommerce-printify-sync'),
                    'in_progress' => __('In Progress', 'wp-woocommerce-printify-sync'),
                    'on_hold' => __('On Hold', 'wp-woocommerce-printify-sync'),
                    'resolved' => __('Resolved', 'wp-woocommerce-printify-sync'),
                    'closed' => __('Closed', 'wp-woocommerce-printify-sync'),
                ],
            ],
            'subject' => [
                'title' => __('Subject', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => sprintf(
                    __('Available placeholders: %s', 'wp-woocommerce-printify-sync'),
                    '{ticket_id}, {site_title}, {customer_name}, {old_status}, {new_status}'
                ),
                'placeholder' => $this->get_default_subject(),
                'default' => $this->get_default_subject()
            ],
            'heading' => [
                'title' => __('Email Heading', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => sprintf(
                    __('Available placeholders: %s', 'wp-woocommerce-printify-sync'),
                    '{ticket_id}, {site_title}, {customer_name}, {new_status}'
                ),
                'placeholder' => $this->get_default_heading(),
                'default' => $this->get_default_heading()
            ],
            'status_messages' => [
                'title' => __('Status Messages', 'wp-woocommerce-printify-sync'),
                'type' => 'title',
                'description' => __('Customize messages for each status change.', 'wp-woocommerce-printify-sync')
            ],
            'message_resolved' => [
                'title' => __('Resolved Message', 'wp-woocommerce-printify-sync'),
                'type' => 'textarea',
                'description' => __('Message to send when ticket is resolved', 'wp-woocommerce-printify-sync'),
                'default' => __('Your ticket has been resolved. If you need further assistance, please feel free to reply to this email.', 'wp-woocommerce-printify-sync'),
                'css' => 'width:400px; height: 75px;'
            ],
            'message_on_hold' => [
                'title' => __('On Hold Message', 'wp-woocommerce-printify-sync'),
                'type' => 'textarea',
                'description' => __('Message to send when ticket is put on hold', 'wp-woocommerce-printify-sync'),
                'default' => __('Your ticket has been placed on hold while we gather additional information. We\'ll update you as soon as possible.', 'wp-woocommerce-printify-sync'),
                'css' => 'width:400px; height: 75px;'
            ],
            'additional_content' => [
                'title' => __('Additional Content', 'wp-woocommerce-printify-sync'),
                'description' => __('Text to appear below the main email content.', 'wp-woocommerce-printify-sync'),
                'css' => 'width:400px; height: 75px;',
                'placeholder' => __('Enter any additional content here.', 'wp-woocommerce-printify-sync'),
                'type' => 'textarea',
                'default' => $this->get_default_additional_content(),
                'desc_tip' => true,
            ],
            'email_type' => [
                'title' => __('Email type', 'wp-woocommerce-printify-sync'),
                'type' => 'select',
                'description' => __('Choose which format of email to send.', 'wp-woocommerce-printify-sync'),
                'default' => 'html',
                'class' => 'email_type wc-enhanced-select',
                'options' => $this->get_email_type_options(),
                'desc_tip' => true,
            ],
        ];
    }

    protected function should_send_status_email(): bool
    {
        // Always send if there's a custom note
        if (!empty($this->status_note)) {
            return true;
        }

        // Get status triggers from settings
        $triggers = $this->get_option('status_triggers', ['resolved', 'on_hold']);
        
        // Check if new status is in triggers
        return in_array($this->new_status, $triggers);
    }

    protected function get_status_message(): string
    {
        // If there's a custom note, use it
        if (!empty($this->status_note)) {
            return $this->status_note;
        }

        // Get predefined message for the status
        $message_option = 'message_' . $this->new_status;
        return $this->get_option($message_option, $this->get_default_status_message());
    }

    protected function get_default_status_message(): string
    {
        return sprintf(
            __('Your ticket status has been updated from %1$s to %2$s.', 'wp-woocommerce-printify-sync'),
            $this->get_status_label($this->old_status),
            $this->get_status_label($this->new_status)
        );
    }

    protected function get_status_label(string $status): string
    {
        $labels = [
            'open' => __('Open', 'wp-woocommerce-printify-sync'),
            'pending' => __('Pending', 'wp-woocommerce-printify-sync'),
            'in_progress' => __('In Progress', 'wp-woocommerce-printify-sync'),
            'on_hold' => __('On Hold', 'wp-woocommerce-printify-sync'),
            'resolved' => __('Resolved', 'wp-woocommerce-printify-sync'),
            'closed' => __('Closed', 'wp-woocommerce-printify-sync'),
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    protected function get_status_color(string $status): string
    {
        return $this->status_colors[$status] ?? '#6c757d';
    }

    protected function format_string($string): string
    {
        $this->placeholders['{ticket_id}'] = $this->ticket_id;
        $this->placeholders['{customer_name}'] = $this->get_ticket_customer_name();
        $this->placeholders['{old_status}'] = $this->get_status_label($this->old_status);
        $this->placeholders['{new_status}'] = $this->get_status_label($this->new_status);
        $this->placeholders['{status_note}'] = $this->status_note;

        return parent::format_string($string);
    }

    protected function log_status_change(): void
    {
        $log_entry = sprintf(
            /* translators: 1: old status 2: new status */
            __('Ticket status changed from %1$s to %2$s', 'wp-woocommerce-printify-sync'),
            $this->get_status_label($this->old_status),
            $this->get_status_label($this->new_status)
        );

        if (!empty($this->status_note)) {
            $log_entry .= ' - ' . $this->status_note;
        }

        // Add to ticket history
        add_post_meta($this->ticket_id, '_ticket_history', [
            'type' => 'status_change',
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'note' => $this->status_note,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ]);
    }

    protected function get_default_additional_content(): string
    {
        return __(
            'If you have any questions about this status change, please reply to this email.',
            'wp-woocommerce-printify-sync'
        );
    }
}