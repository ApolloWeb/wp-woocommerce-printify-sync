<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class EmailPreviewHandler
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'handlePreviewRequest']);
    }

    public function handlePreviewRequest(): void
    {
        if (!isset($_GET['wpwps_preview_email'])) {
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $emailType = sanitize_key($_GET['wpwps_preview_email']);
        $nonce = $_GET['_wpnonce'] ?? '';

        if (!wp_verify_nonce($nonce, 'preview-mail')) {
            wp_die(__('Invalid request', 'wp-woocommerce-printify-sync'));
        }

        // Get the latest order for preview
        $orders = wc_get_orders([
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => ['processing', 'completed'],
        ]);

        if (empty($orders)) {
            wp_die(__('No orders found for preview', 'wp-woocommerce-printify-sync'));
        }

        $order = $orders[0];

        // Add sample tracking data for preview
        $order->update_meta_data('_printify_tracking_number', 'TRACK123456789');
        $order->update_meta_data('_printify_tracking_carrier', 'Sample Carrier');
        $order->update_meta_data('_printify_tracking_url', 'https://example.com/track');
        $order->update_meta_data('_printify_delivery_date', current_time('mysql'));

        // Load mailer
        $mailer = WC()->mailer();
        $email = null;

        switch ($emailType) {
            case 'shipped':
                $email = $mailer->emails['WC_Email_Printify_Shipped'];
                break;
            case 'delivered':
                $email = $mailer->emails['WC_Email_Printify_Delivered'];
                break;
        }

        if (!$email) {
            wp_die(__('Invalid email type', 'wp-woocommerce-printify-sync'));
        }

        // Generate preview
        $email->object = $order;
        $content = $email->get_content();
        $content = $email->style_inline($content);
        echo $content;
        exit;
    }
}