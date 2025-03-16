<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class EmailManager
{
    public function register(): void
    {
        add_filter('woocommerce_email_classes', [$this, 'registerEmails']);
        add_filter('woocommerce_email_actions', [$this, 'registerEmailActions']);
        
        // Add email settings section
        add_filter('woocommerce_get_settings_email', [$this, 'addEmailSettings'], 10, 2);
        
        // Add preview handler
        add_filter('woocommerce_email_preview_template_paths', [$this, 'addPreviewPaths']);
    }

    public function registerEmails(array $emails): array
    {
        // These will appear in WooCommerce > Settings > Emails
        $emails['wpwps_ticket_created'] = new TicketCreatedEmail();
        $emails['wpwps_ticket_updated'] = new TicketUpdatedEmail();
        $emails['wpwps_ticket_reply'] = new TicketReplyEmail();
        $emails['wpwps_ticket_resolved'] = new TicketResolvedEmail();

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
                'title' => __('Email Template Style', 'wp-woocommerce-printify-sync'),
                'desc' => __('Choose how ticket emails should look', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_ticket_email_template_style',
                'type' => 'select',
                'options' => [
                    'woocommerce' => __('WooCommerce Style', 'wp-woocommerce-printify-sync'),
                    'custom' => __('Custom Style', 'wp-woocommerce-printify-sync')
                ],
                'default' => 'woocommerce'
            ],
            [
                'type' => 'sectionend',
                'id' => 'wpwps_ticket_email_options'
            ]
        ];

        return array_merge($settings, $ticket_settings);
    }

    public function addPreviewPaths(array $paths): array
    {
        // Add our template directory to the preview paths
        $paths[] = WPWPS_PLUGIN_DIR . 'templates/';
        return $paths;
    }
}