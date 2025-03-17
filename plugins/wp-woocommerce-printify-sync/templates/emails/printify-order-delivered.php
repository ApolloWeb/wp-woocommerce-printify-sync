<?php
/**
 * Printify Order Delivered email
 *
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php printf(
    /* translators: %s: Customer first name */
    esc_html__('Hi %s,', 'wp-woocommerce-printify-sync'),
    esc_html($order->get_billing_first_name())
); ?></p>

<p><?php printf(
    /* translators: %s: Order number */
    esc_html__('Great news! Your order #%s has been delivered.', 'wp-woocommerce-printify-sync'),
    esc_html($order->get_order_number())
); ?></p>

<div class="printify-delivery-info">
    <h3><?php esc_html_e('Delivery Details', 'wp-woocommerce-printify-sync'); ?></h3>
    <?php
    $delivery_date = $order->get_meta('_printify_delivery_date');
    $tracking_number = $order->get_meta('_printify_tracking_number');
    $carrier = $order->get_meta('_printify_tracking_carrier');
    ?>
    
    <?php if ($delivery_date): ?>
        <p><?php printf(
            /* translators: %s: Delivery date */
            esc_html__('Delivered on: %s', 'wp-woocommerce-printify-sync'),
            esc_html(wc_format_datetime(wc_string_to_datetime($delivery_date)))
        ); ?></p>
    <?php endif; ?>

    <?php if ($tracking_number && $carrier): ?>
        <p><?php printf(
            /* translators: %1$s: Carrier name, %2$s: Tracking number */
            esc_html__('Delivered by %1$s (Tracking: %2$s)', 'wp-woocommerce-printify-sync'),
            esc_html($carrier),
            esc_html($tracking_number)
        ); ?></p>
    <?php endif; ?>
</div>

<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

do_action('woocommerce_email_footer', $email);