<?php defined('ABSPATH') || exit; ?>

<?php do_action('woocommerce_email_header', $subject, $email); ?>

<div class="wpwps-email-content">
    <?php if (!empty($ticket_id)): ?>
        <p class="ticket-reference">
            <?php printf(__('Ticket Reference: #%s', 'wp-woocommerce-printify-sync'), $ticket_id); ?>
        </p>
    <?php endif; ?>

    <div class="ticket-response">
        <?php echo wp_kses_post($response_content); ?>
    </div>

    <?php if (!empty($signature)): ?>
        <div class="wpwps-signature">
            <?php echo wp_kses_post($signature); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($order)): ?>
        <?php do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email); ?>
    <?php endif; ?>
</div>

<?php do_action('woocommerce_email_footer', $email); ?>
