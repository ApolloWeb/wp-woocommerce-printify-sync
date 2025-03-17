<?php
/**
 * Ticket Internal Note Email (plain text)
 */

defined('ABSPATH') || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s Customer first name */
echo sprintf(esc_html__('Hi %s,', 'wp-woocommerce-printify-sync'), esc_html($customer_name)) . "\n\n";

echo esc_html($email->get_option('note_prefix')) . "\n\n";

echo "-------\n\n";

echo wp_strip_all_tags(wptexturize($ticket_data['content'])) . "\n\n";

if (!empty($ticket_data['attachments'])) {
    echo esc_html__('Attachments:', 'wp-woocommerce-printify-sync') . "\n";
    foreach ($ticket_data['attachments'] as $attachment) {
        echo esc_html($attachment['name']) . ': ' . esc_url($attachment['url']) . "\n";
    }
    echo "\n";
}

/* translators: %s: Ticket ID */
echo sprintf(esc_html__('For reference, your ticket ID is: #%s', 'wp-woocommerce-printify-sync'), esc_html($ticket_id)) . "\n\n";

echo "----------\n\n";

echo wp_strip_all_tags($additional_content) . "\n\n";

echo wp_strip_all_tags(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));