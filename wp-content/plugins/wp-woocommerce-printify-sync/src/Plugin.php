<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync;

class Plugin
{
    private const VERSION = '1.0.0';
    private const CURRENT_TIME = '2025-03-15 22:08:00';
    private const CURRENT_USER = 'ApolloWeb';

    public function init(): void
    {
        // Register email templates
        add_filter('woocommerce_email_classes', [$this, 'registerEmailTemplates']);
        
        // Add custom email actions
        add_action('wpwps_shipped_notification', [$this, 'triggerShippedEmail'], 10, 1);
        add_action('wpwps_delivered_notification', [$this, 'triggerDeliveredEmail'], 10, 1);
        
        // Initialize email styles
        new Emails\PrintifyEmailStyles();
    }

    public function registerEmailTemplates(array $emails): array
    {
        $emails['WC_Email_Printify_Shipped'] = include(
            WPWPS_PLUGIN_PATH . 'src/Emails/OrderShippedEmail.php'
        );
        
        $emails['WC_Email_Printify_Delivered'] = include(
            WPWPS_PLUGIN_PATH . 'src/Emails/OrderDeliveredEmail.php'
        );

        return $emails;
    }

    public function triggerShippedEmail(int $orderId): void
    {
        $mailer = WC()->mailer();
        $email = $mailer->emails['WC_Email_Printify_Shipped'];
        $email->trigger($orderId);
    }

    public function triggerDeliveredEmail(int $orderId): void
    {
        $mailer = WC()->mailer();
        $email = $mailer->emails['WC_Email_Printify_Delivered'];
        $email->trigger($orderId);
    }
}