<?php
/**
 * Fulfillment Failure Email
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>
    <?php esc_html_e('A fulfillment issue has occurred with the following order:', 'wp-woocommerce-printify-sync'); ?>
</p>

<p>
    <strong><?php esc_html_e('Order:', 'wp-woocommerce-printify-sync'); ?></strong> 
    #<?php echo esc_html($order->get_order_number()); ?>
</p>

<p>
    <strong><?php esc_html_e('Error:', 'wp-woocommerce-printify-sync'); ?></strong> 
    <?php echo esc_html($error_message); ?>
</p>

<p>
    <strong><?php esc_html_e('Time:', 'wp-woocommerce-printify-sync'); ?></strong> 
    <?php echo esc_html(wp_date('Y-m-d H:i:s')); ?>
</p>

<h2><?php esc_html_e('Order Details', 'wp-woocommerce-printify-sync'); ?></h2>

<?php do_action('woocommerce_email_order_details', $order, true, false, $email); ?>

<p