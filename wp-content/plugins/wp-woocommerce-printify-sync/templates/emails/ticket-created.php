<?php
/**
 * Ticket Created email
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p><?php printf(
    __('Hello, we have received your message and created ticket #%d for you.', 'wp-woocommerce-printify-sync'),
    $ticket_id
); ?></p>

<h2><?php _e('Ticket Details', 'wp-woocommerce-printify-sync'); ?></h2>

<ul>
    <li><strong><?php _e('Ticket ID:', 'wp-woocommerce-printify-sync'); ?></strong> #<?php echo $ticket_id; ?></li>
    <li><strong><?php _e('Subject:', 'wp-woocommerce-printify-sync'); ?></strong> <?php echo esc_html($ticket->post_title); ?></li>
    <li><strong><?php _e('Status:', 'wp-woocommerce-printify-sync'); ?></strong> <?php echo esc_html(get_post_meta($ticket_id, '_ticket_status', true)); ?></li>
    <?php if ($order_id = get_post_meta($ticket_id, '_order_id', true)): ?>
        <li><strong><?php _e('Related Order:', 'wp-woocommerce-printify-sync'); ?></strong> #<?php echo $order_id; ?></li>
    <?php endif; ?>
</ul>

<p><?php _e('We will review your message and get back to you as soon as possible.', 'wp-woocommerce-printify-sync'); ?></p>

<p><?php _e('You can reply to this email directly to update your ticket.', 'wp-woocommerce-printify-sync'); ?></p>

<?php
do_action('woocommerce_email_footer', $email);