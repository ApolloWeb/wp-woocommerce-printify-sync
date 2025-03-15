<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class CompatibilityChecker
{
    private string $currentTime = '2025-03-15 19:05:35';
    private string $currentUser = 'ApolloWeb';

    public function checkCompatibility(): void
    {
        add_action('admin_notices', [$this, 'displayCompatibilityNotices']);
    }

    public function displayCompatibilityNotices(): void
    {
        // Check for required plugins
        if (!class_exists('Postie')) {
            $this->displayMissingPluginNotice('Postie');
        }

        if (!class_exists('SMTP_MAILER')) {
            $this->displayMissingPluginNotice('SMTP Mailer');
        }

        // Check Postie configuration
        if (class_exists('Postie')) {
            $this->checkPostieConfiguration();
        }
    }

    private function displayMissingPluginNotice(string $pluginName): void
    {
        $class = 'notice notice-error';
        $message = sprintf(
            __('WP WooCommerce Printify Sync requires %s plugin to be installed and activated.', 'wp-woocommerce-printify-sync'),
            $pluginName
        );

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    private function checkPostieConfiguration(): void
    {
        // Check if Postie is configured for ticket email
        $ticketEmail = get_option('wpwps_ticket_email');
        if (empty($ticketEmail)) {
            $class = 'notice notice-warning is-dismissible';
            $message = __('Please configure the ticket email address in Postie settings.', 'wp-woocommerce-printify-sync');
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        }
    }
}