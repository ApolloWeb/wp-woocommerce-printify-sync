<?php
/**
 * Support Ticket email (plain text)
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

printf(esc_html__('Ticket #%s has been updated.', 'wp-woocommerce-printify-sync'), esc_html($ticket->id)) . "\n\n";

echo esc_html($ticket->subject) . "\n\n";

if (!empty($ticket->order_id)) {
    printf(esc_html__('Related Order: #%s', 'wp-woocommerce-printify-sync'), esc_html($ticket->order_id)) . "\n";
}

echo "\n----------------------------------------\n\n";

echo esc_html__('Status:', 'wp-woocommerce-printify-sync') . ' ' . esc_html(ucfirst($ticket->status)) . "\n";
echo esc_html__('Priority:', 'wp-woocommerce-printify-sync') . ' ' . esc_html(ucfirst($ticket->priority)) . "\n\n";

if (!empty($additional_content)) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));