<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class TicketCreatedEmail extends BaseTicketEmail
{
    public function __construct()
    {
        parent::__construct();

        $this->id = 'wpwps_ticket_created';
        $this->title = __('Support Ticket Created', 'wp-woocommerce-printify-sync');
        $this->description = __('This email is sent to customers when a new support ticket is created.', 'wp-woocommerce-printify-sync');
        
        $this->template_html = 'emails/ticket-created.php';
        $this->template_plain = 'emails/plain/ticket-created.php';

        $this->placeholders = [
            '{ticket_id}' => '',
            '{site_title}' => $this->get_blogname(),
            '{customer_name}' => '',
        ];

        // Default subject and heading
        $this->heading = __('Support Ticket Created #{ticket_id}', 'wp-woocommerce-printify-sync');
        $this->subject = __('[{site_title}] Support Ticket Created #{ticket_id}', 'wp-woocommerce-printify-sync');
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
                'description' => sprintf(__('Available placeholders: %s', 'wp-woocommerce-printify-sync'), '{ticket_id}, {site_title}, {customer_name}'),
                'placeholder' => $this->get_default_subject(),
                'default' => $this->get_default_subject()
            ],
            'heading' => [
                'title' => __('Email Heading', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => sprintf(__('Available placeholders: %s', 'wp-woocommerce-printify-sync'), '{ticket_id}, {site_title}, {customer_name}'),
                'placeholder' => $this->get_default_heading(),
                'default' => $this->get_default_heading()
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

    public function get_default_subject(): string
    {
        return __('[{site_title}] Support Ticket Created #{ticket_id}', 'wp-woocommerce-printify-sync');
    }

    public function get_default_heading(): string
    {
        return __('Support Ticket Created #{ticket_id}', 'wp-woocommerce-printify-sync');
    }

    protected function format_string($string): string
    {
        $this->placeholders['{ticket_id}'] = $this->ticket_id;
        $this->placeholders['{customer_name}'] = $this->get_ticket_customer_name();

        return parent::format_string($string);
    }
}