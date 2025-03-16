<?php
/**
 * Order Fulfillment Complete Email
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
        /* translators: %s: Order number */
        esc_html__('Great news! Your order #%s has been fulfilled and is on its way to you.', 'wp-woocommerce-printify-sync'),
        esc_html($order->get_order_number())
    ); ?>
</p>

<?php if (!empty($tracking_info)): ?>
    <h2><?php esc_html_e('Tracking Information', 'wp-woocommerce-printify-sync'); ?></h2>
    <p>
        <?php esc_html_e('Carrier:', 'wp-woocommerce-printify-sync'); ?> 
        <strong><?php echo esc_html($tracking_info['carrier']); ?></strong>
    </p>
    <p>
        <?php esc_html_e('Tracking Number:', 'wp-woocommerce-printify-sync'); ?> 
        <strong><?php echo esc_html($tracking_info['number']); ?></strong>
    </p>
    <?php if (!empty($tracking_info['url'])): ?>
        <p>
            <a href="<?php echo esc_url($tracking_info['url']); ?>" class="button button-primary">
                <?php esc_html_e('Track Your Order', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </p>
    <?php endif; ?>
<?php endif; ?>

<h2><?php esc_html_e('Order Details', 'wp-woocommerce-printify-sync'); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">
    <thead>
        <tr>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Product', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Quantity', 'wp-woocommerce-printify-sync'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($order_data['items'] as $item): ?>
            <tr>
                <td class="td" style="text-align:left;"><?php echo esc_html($item['name']); ?></td>
                <td class="td" style="text-align:left;"><?php echo esc_html($item['quantity']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (!empty($additional_content)): ?>
    <p><?php echo wp_kses_post($additional_content); ?></p>
<?php endif; ?>

<?php
do_action('woocommerce_email_footer', $email);