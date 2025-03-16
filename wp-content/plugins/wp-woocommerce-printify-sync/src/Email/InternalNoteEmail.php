<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class InternalNoteEmail extends BaseTicketEmail
{
    protected $note_id;
    protected $note_data;
    protected $is_internal;

    public function __construct()
    {
        parent::__construct();

        $this->id = 'wpwps_internal_note';
        $this->title = __('Support Ticket Internal Note', 'wp-woocommerce-printify-sync');
        $this->description = __('This email is sent when an internal note is added to a ticket and marked for customer notification.', 'wp-woocommerce-printify-sync');

        $this->template_html = 'emails/ticket-internal-note.php';
        $this->template_plain = 'emails/plain/ticket-internal-note.php';

        $this->placeholders = [
            '{ticket_id}' => '',
            '{site_title}' => $this->get_blogname(),
            '{customer_name}' => '',
            '{agent_name}' => '',
            '{note_preview}' => '',
        ];

        // Default subject and heading
        $this->heading = __('Update on Ticket #{ticket_id}', 'wp-woocommerce-printify-sync');
        $this->subject = __('[{site_title}] Update on Ticket #{ticket_id}: {note_preview}', 'wp-woocommerce-printify-sync');
    }

    public function trigger($ticket_id = 0, $note_data = []): void
    {
        if (!$ticket_id || !is_numeric($ticket_id)) {
            return;
        }

        $this->ticket_id = $ticket_id;
        $this->note_id = $note_data['note_id'] ?? 0;
        $this->note_data = $note_data;
        $this->is_internal = !empty($note_data['is_internal']);

        // Don't send if it's marked as internal only
        if ($this->is_internal) {
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

        // Log note sent
        $this->log_note_sent();
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
            'subject' => [
                'title' => __('Subject', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => sprintf(
                    __('Available placeholders: %s', 'wp-woocommerce-printify-sync'),
                    '{ticket_id}, {site_title}, {customer_name}, {agent_name}, {note_preview}'
                ),
                'placeholder' => $this->get_default_subject(),
                'default' => $this->get_default_subject()
            ],
            'heading' => [
                'title' => __('Email Heading', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => sprintf(
                    __('Available placeholders: %s', 'wp-woocommerce-printify-sync'),
                    '{ticket_id}, {site_title}, {customer_name}, {agent_name}'
                ),
                'placeholder' => $this->get_default_heading(),
                'default' => $this->get_default_heading()
            ],
            'note_prefix' => [
                'title' => __('Note Prefix', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('Text to display before the note content', 'wp-woocommerce-printify-sync'),
                'default' => __('Additional information about your ticket:', 'wp-woocommerce-printify-sync'),
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

    protected function get_note_preview(int $length = 50): string
    {
        $content = $this->note_data['content'] ?? '';
        if (empty($content)) {
            return '';
        }

        $preview = wp_strip_all_tags($content);
        $preview = wp_trim_words($preview, 10, '...');
        
        return $preview;
    }

    protected function format_string($string): string
    {
        $this->placeholders['{ticket_id}'] = $this->ticket_id;
        $this->placeholders['{customer_name}'] = $this->get_ticket_customer_name();
        $this->placeholders['{agent_name}'] = $this->note_data['agent_name'] ?? __('Support Team', 'wp-woocommerce-printify-sync');
        $this->placeholders['{note_preview}'] = $this->get_note_preview();

        return parent::format_string($string);
    }

    protected function log_note_sent(): void
    {
        add_post_meta($this->ticket_id, '_ticket_history', [
            'type' => 'note_sent',
            'note_id' => $this->note_id,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ]);
    }

    protected function get_default_additional_content(): string
    {
        return __(
            'You can reply to this email if you need any clarification about this update.',
            'wp-woocommerce-printify-sync'
        );
    }
}