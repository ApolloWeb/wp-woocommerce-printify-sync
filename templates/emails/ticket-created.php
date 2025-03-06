<?php
/**
 * Ticket Created Email Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php printf(esc_html__('Hello %s,', 'wp-woocommerce-printify-sync'), esc_html($ticket['customer_name'])); ?></p>
<p><?php esc_html_e('Thank you for contacting our support team. Your ticket has been created and assigned the following reference number:', 'wp-woocommerce-printify-sync'); ?></p>

<h2 style="text-align:center;"><?php echo esc_html($ticket['ticket_number']); ?></h2>

<div style="margin-bottom: 40px;">
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5;">
        <tbody>
            <tr>
                <th class="td" scope="row" style="text-align:left; border-top-width: 1px;"><?php esc_html_e('Subject:', 'wp-woocommerce-printify-sync'); ?></th>
                <td class="td" style="text-align:left; border-top-width: 1px;"><?php echo esc_html($ticket['subject']); ?></td>
            </tr>
            <tr>
                <th class="td" scope="row" style="text-align:left;"><?php esc_html_e('Type:', 'wp-woocommerce-printify-sync'); ?></th>
                <td class="td" style="text-align:left;"><?php echo esc_html(ucfirst($ticket['type'])); ?></td>
            </tr>
            <tr>
                <th class="td" scope="row" style="text-align:left;"><?php esc_html_e('Status:', 'wp-woocommerce-printify-sync'); ?></th>
                <td class="td" style="text-align:left;"><?php echo esc_html(ucfirst($ticket['status'])); ?></td>
            </tr>
            <tr>
                <th class="td" scope="row" style="text-align:left;"><?php esc_html_e('Created On:', 'wp-woocommerce-printify-sync'); ?></th>
                <td class="td" style="text-align:left;"><?php echo esc_html(date_i18n(get