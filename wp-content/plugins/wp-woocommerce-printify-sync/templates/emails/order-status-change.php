<?php
/**
 * Order Status Change Email
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>
    <?php printf(
        /* translators: %s: Customer first name */
        esc_html__('Hi %s,', 'wp-woocommerce-printify-sync'),
        esc_html($order->get_billing_first_name())
    ); ?>
</p>

<p>
    <?php printf(
        /* translators: 1: Order number, 2: New status */
        esc_html__('The status of your order #%1$s has been updated to: %2$s', 'wp-woocommerce-printify-sync'),
        esc_html($order->get_order_number()),
        esc_html(ucfirst($new_status))
    ); ?>
</p>

<?php if ($status_description): ?>
    <p><?php echo wp_kses_post($status_description); ?></p>
<?php endif; ?>

<h2><?php esc_html_e('Order Details', 'wp-woocommerce-printify-sync'); ?></h2>

<?php do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email); ?>

<?php do_action('woocommerce_email_footer', $email); ?>