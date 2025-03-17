<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class TicketEmailTemplate extends \WC_Email
{
    protected $template_base;
    protected $ticket_id;
    protected $ticket_data;

    public function __construct()
    {
        // Define base properties
        $this->template_base = WPWPS_PLUGIN_DIR . 'templates/';
        
        // Call parent constructor
        parent::__construct();

        // Add customizer support
        add_action('woocommerce_email_settings_before', [$this, 'add_customizer_settings']);
    }

    public function add_customizer_settings(): void
    {
        $this->form_fields = array_merge($this->form_fields, [
            'ticket_email_header_color' => [
                'title' => __('Ticket Header Color', 'wp-woocommerce-printify-sync'),
                'type' => 'color',
                'desc_tip' => true,
                'description' => __('The header color for ticket emails.', 'wp-woocommerce-printify-sync'),
                'default' => '#96588a'
            ],
            'ticket_email_background_color' => [
                'title' => __('Background Color', 'wp-woocommerce-printify-sync'),
                'type' => 'color',
                'desc_tip' => true,
                'description' => __('The main background color for ticket emails.', 'wp-woocommerce-printify-sync'),
                'default' => '#f7f7f7'
            ]
        ]);
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
                'description' => sprintf(__('Available placeholders: %s', 'wp-woocommerce-printify-sync'), '{ticket_id}, {site_title}'),
                'placeholder' => $this->get_default_subject(),
                'default' => $this->get_default_subject()
            ],
            'heading' => [
                'title' => __('Email Heading', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => sprintf(__('Available placeholders: %s', 'wp-woocommerce-printify-sync'), '{ticket_id}, {site_title}'),
                'placeholder' => $this->get_default_heading(),
                'default' => $this->get_default_heading()
            ],
            'email_type' => [
                'title' => __('Email type', 'wp-woocommerce-printify-sync'),
                'type' => 'select',
                'description' => __('Choose which format of email to send.', 'wp-woocommerce-printify-sync'),
                'default' => 'html',
                'class' => 'email_type wc-enhanced-select',
                'options' => $this->get_email_type_options()
            ]
        ];
    }

    public function get_content_html(): string
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'ticket_id' => $this->ticket_id,
                'ticket_data' => $this->ticket_data,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this
            ],
            'wp-woocommerce-printify-sync/',
            $this->template_base
        );
    }

    public function style_inline(string $content): string
    {
        // Add custom CSS to WooCommerce email styles
        add_filter('woocommerce_email_styles', [$this, 'add_custom_styles']);
        
        // Call parent style_inline
        $styled_content = parent::style_inline($content);
        
        // Remove our filter
        remove_filter('woocommerce_email_styles', [$this, 'add_custom_styles']);
        
        return $styled_content;
    }

    public function add_custom_styles(string $css): string
    {
        $custom_css = "
            .ticket-header {
                background-color: {$this->get_option('ticket_email_header_color', '#96588a')};
                padding: 15px;
                border-radius: 3px 3px 0 0;
            }
            .ticket-content {
                background-color: {$this->get_option('ticket_email_background_color', '#f7f7f7')};
                padding: 20px;
            }
            .ticket-meta {
                margin-bottom: 20px;
                border-bottom: 1px solid #dedede;
                padding-bottom: 15px;
            }
        ";

        return $css . $custom_css;
    }
}