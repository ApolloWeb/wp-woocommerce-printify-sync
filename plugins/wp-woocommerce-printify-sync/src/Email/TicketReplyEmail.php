<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class TicketReplyEmail extends BaseTicketEmail
{
    protected $reply_id;
    protected $agent_data;
    protected $original_message;

    public function __construct()
    {
        parent::__construct();

        $this->id = 'wpwps_ticket_reply';
        $this->title = __('Support Ticket Reply', 'wp-woocommerce-printify-sync');
        $this->description = __('This email is sent to customers when a support agent replies to their ticket.', 'wp-woocommerce-printify-sync');

        $this->template_html = 'emails/ticket-reply.php';
        $this->template_plain = 'emails/plain/ticket-reply.php';

        $this->placeholders = [
            '{ticket_id}' => '',
            '{site_title}' => $this->get_blogname(),
            '{customer_name}' => '',
            '{agent_name}' => '',
            '{reply_snippet}' => '',
        ];

        // Default subject and heading
        $this->heading = __('New Reply to Ticket #{ticket_id}', 'wp-woocommerce-printify-sync');
        $this->subject = __('[{site_title}] Re: Ticket #{ticket_id} - {reply_snippet}', 'wp-woocommerce-printify-sync');
    }

    public function trigger($ticket_id = 0, $reply_data = []): void
    {
        if (!$ticket_id || !is_numeric($ticket_id)) {
            return;
        }

        $this->ticket_id = $ticket_id;
        $this->reply_id = $reply_data['reply_id'] ?? 0;
        $this->ticket_data = $reply_data;
        
        // Get agent data
        $this->agent_data = $this->get_agent_data($reply_data['agent_id'] ?? 0);
        
        // Get original message for threading context
        $this->original_message = $this->get_original_message();

        // Set recipient
        $this->recipient = $this->get_ticket_customer_email();

        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        // Add email threading headers
        add_filter('woocommerce_email_headers', [$this, 'add_reply_threading_headers'], 10, 2);

        // Send the email
        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
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
                    '{ticket_id}, {site_title}, {customer_name}, {agent_name}, {reply_snippet}'
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
            'include_original' => [
                'title' => __('Include Original Message', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'label' => __('Include the original ticket message in replies', 'wp-woocommerce-printify-sync'),
                'default' => 'yes'
            ],
            'agent_signature' => [
                'title' => __('Agent Signature', 'wp-woocommerce-printify-sync'),
                'type' => 'textarea',
                'description' => __('Add a signature to be included in agent replies. Use {agent_name} placeholder.', 'wp-woocommerce-printify-sync'),
                'placeholder' => __("Best regards,\n{agent_name}", 'wp-woocommerce-printify-sync'),
                'default' => __("Best regards,\n{agent_name}", 'wp-woocommerce-printify-sync'),
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

    protected function get_agent_data(int $agent_id): array
    {
        $agent = get_user_by('id', $agent_id);
        if (!$agent) {
            return [
                'name' => __('Support Agent', 'wp-woocommerce-printify-sync'),
                'email' => get_option('wpwps_ticket_reply_to', get_option('admin_email')),
            ];
        }

        return [
            'name' => $agent->display_name,
            'email' => $agent->user_email,
        ];
    }

    protected function get_original_message(): string
    {
        if ($this->get_option('include_original') !== 'yes') {
            return '';
        }

        $ticket = get_post($this->ticket_id);
        return $ticket ? $ticket->post_content : '';
    }

    public function add_reply_threading_headers($headers, $email_id): array
    {
        if ($email_id !== $this->id) {
            return $headers;
        }

        // Get ticket thread ID
        $thread_id = get_post_meta($this->ticket_id, '_email_thread_id', true);
        if (!$thread_id) {
            $thread_id = sprintf('<%s.ticket-%d@%s>', uniqid(), $this->ticket_id, parse_url(home_url(), PHP_URL_HOST));
            update_post_meta($this->ticket_id, '_email_thread_id', $thread_id);
        }

        // Add threading headers
        $headers[] = 'Message-ID: <' . $this->reply_id . '.' . $thread_id;
        $headers[] = 'References: ' . $thread_id;
        $headers[] = 'In-Reply-To: ' . $thread_id;

        // Set reply-to as the agent's email
        $headers[] = 'Reply-To: ' . $this->agent_data['name'] . ' <' . $this->agent_data['email'] . '>';

        return $headers;
    }

    protected function format_string($string): string
    {
        $this->placeholders['{ticket_id}'] = $this->ticket_id;
        $this->placeholders['{customer_name}'] = $this->get_ticket_customer_name();
        $this->placeholders['{agent_name}'] = $this->agent_data['name'];
        $this->placeholders['{reply_snippet}'] = $this->get_reply_snippet();

        return parent::format_string($string);
    }

    protected function get_reply_snippet(int $length = 50): string
    {
        $content = $this->ticket_data['message'] ?? '';
        if (empty($content)) {
            return '';
        }

        $snippet = wp_strip_all_tags($content);
        $snippet = wp_trim_words($snippet, 10, '...');
        
        return $snippet;
    }

    protected function get_default_additional_content(): string
    {
        return __(
            'You can reply to this email directly to update your ticket. Your ticket reference number is #{ticket_id}.',
            'wp-woocommerce-printify-sync'
        );
    }
}