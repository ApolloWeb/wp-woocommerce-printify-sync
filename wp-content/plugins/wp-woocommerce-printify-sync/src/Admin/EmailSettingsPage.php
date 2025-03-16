<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class EmailSettingsPage
{
    public function register(): void
    {
        add_filter('woocommerce_get_sections_email', [$this, 'addEmailSection']);
    }

    public function addEmailSection(array $sections): array
    {
        $sections['wpwps_tickets'] = __('Support Tickets', 'wp-woocommerce-printify-sync');
        return $sections;
    }
}