<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

class EmailCustomizationTab
{
    public function register(): void
    {
        add_filter('woocommerce_get_sections_email', [$this, 'addEmailSection']);
        add_filter('woocommerce_get_settings_email', [$this, 'addEmailSettings'], 10, 2);
    }

    public function addEmailSection(array $sections): array
    {
        $sections['printify'] = __('Printify Notifications', 'wp-woocommerce-printify-sync');
        return $sections;
    }

    public function addEmailSettings(array $settings, string $currentSection): array
    {
        if ($currentSection !== 'printify') {
            return $settings;
        }

        return [
            [
                'title' => __('Printify Email Settings', 'wp-woocommerce-printify-sync'),
                'type' => 'title',
                'desc' => __('Configure how Printify order notifications are handled.', 'wp-woocommerce-printify-sync'),
                'id' => 'printify_email_options',
            ],
            [
                'title' => __('Enable/Disable', 'wp-woocommerce-printify-sync'),
                'desc' => __('Enable Printify email notifications', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_enable_emails',
                'default' => 'yes',
                'type' => 'checkbox',
            ],
            [
                'title' => __('Shipping Notifications', 'wp-woocommerce-printify-sync'),
                'desc' => __('Send email when order is shipped', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_enable_shipping_emails',
                'default' => 'yes',
                'type' => 'checkbox',
            ],
            [
                'title' => __('Delivery Notifications', 'wp-woocommerce-printify-sync'),
                'desc' => __('Send email when order is delivered', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_enable_delivery_emails',
                'default' => 'yes',
                'type' => 'checkbox',
            ],
            [
                'title' => __('Production Updates', 'wp-woocommerce-printify-sync'),
                'desc' => __('Send email when production starts', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_enable_production_emails',
                'default' => 'no',
                'type' => 'checkbox',
            ],
            [
                'title' => __('Email Sender Name', 'wp-woocommerce-printify-sync'),
                'desc' => __('How the sender name appears in Printify notification emails.', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_email_from_name',
                'type' => 'text',
                'default' => get_bloginfo('name'),
                'desc_tip' => true,
            ],
            [
                'title' => __('Email Sender Address', 'wp-woocommerce-printify-sync'),
                'desc' => __('How the sender email appears in Printify notification emails.', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwps_email_from_address',
                'type' => 'email',
                'default' => get_option('admin_email'),
                'desc_tip' => true,
            ],
            [
                'type' => 'sectionend',
                'id' => 'printify_email_options',
            ],
        ];
    }
}