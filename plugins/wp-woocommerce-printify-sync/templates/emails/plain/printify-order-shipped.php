<?php
/**
 * Printify Order Shipped email (plain text)
 *
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf(esc_html__('Hi %s,', 'wp-woocommerce-printify-sync'), esc_html($order->get_billing_first_name())) . "\n\n";

/* translators: %s: Order number */
echo sprintf(esc_html__('Great news! Your order #%s has been shipped and is on its way to you.', 'wp-woocommerce-printify-sync'), esc_html($order->get_order_number())) . "\n\n";

$tracking_number = $order->get_meta('_printify_tracking_number');
$tracking_url = $order->get_meta('_printify_tracking_url');
$carrier = $order->get_meta('_printify_tracking_carrier');

if ($tracking_number) {
    echo esc_html__('Tracking Information:', 'wp-woocommerce-printify-sync') . "\n";
    echo esc_html__('Carrier:', 'wp-woocommerce-printify-sync') . ' ' . esc_html($carrier) . "\n";
    echo esc_html__('Tracking Number:', 'wp-woocommerce-printify-sync') . ' ' . esc_html($tracking_number) . "\n";
    if ($tracking_url) {
        echo esc_html__('Tracking URL:', 'wp-woocommerce-printify-sync') . ' ' . esc_html($tracking_url) . "\n";
    }
    echo "\n";
}

echo "----------------------------------------\n\n";

echo esc_html__('Order Details', 'wp-woocommerce-printify-sync') . "\n\n";

do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n----------------------------------------\n\n";

do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

echo "\n----------------------------------------\n\n";

do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n\n----------------------------------------\n\n";

if ($additional_content) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));