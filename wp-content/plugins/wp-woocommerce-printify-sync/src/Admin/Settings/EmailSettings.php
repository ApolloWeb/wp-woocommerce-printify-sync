<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

class EmailSettings
{
    private string $currentTime = '2025-03-15 19:07:08';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_filter('woocommerce_email_settings', [$this, 'addEmailSettings']);
    }

    public function addEmailSettings(array $settings): array
    {
        $settings[] = [
            'title' => __('Support Ticket Emails', 'wp-woocommerce-printify-sync'),
            'type' => 'title',
            'desc' => __('Email settings for support tickets.', 'wp-woocommerce-printify-sync'),
            'id' => 'wpwps_ticket_email_settings'
        ];

        $settings[] = [
            'title' => __('Ticket Email Template', 'wp-woocommerce-printify-sync'),
            'desc' => __('This template is used for all ticket-related emails.', 'wp-woocommerce-printify-sync'),
            'id' => 'wpwps_ticket_email_template',
            'type' => 'select',
            'options' => [
                'default' => __('Default Template', 'wp-woocommerce-printify-sync'),
                'plain' => __('Plain Text', 'wp-woocommerce-printify-sync'),
                'custom' => __('Custom Template', 'wp-woocommerce-printify-sync')
            ],
            'default' => 'default'
        ];

        $settings[] = [
            'type' => 'sectionend',
            'id' => 'wpwps_ticket_email_settings'
        ];

        return $settings;
    }
}