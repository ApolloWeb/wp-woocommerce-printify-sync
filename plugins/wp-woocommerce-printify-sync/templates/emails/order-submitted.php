<?php
/**
 * Order Submitted to Printify Email
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>
    <?php printf(
        /* translators: %s: Order number */
        esc_html__('Order #%s has been successfully submitted to Printify for fulfillment.', 'wp-woocommerce-printify-sync'),
        esc_html($order_data['order_number'])
    ); ?>
</p>

<h2><?php esc_html_e('Order Details', 'wp-woocommerce-printify-sync'); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">
    <thead>
        <tr>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Product', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Quantity', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Printify ID', 'wp-woocommerce-printify-sync'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($order_data['items'] as $item): ?>
            <tr>
                <td class="td" style="text-align:left;"><?php echo esc_html($item['name']); ?></td>
                <td class="td" style="text-align:left;"><?php echo esc_html($item['quantity']); ?></td>
                <td class="td" style="text-align:left;"><?php echo esc_html($item['printify_id']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p>
    <?php esc_html_e('Printify Order ID:', 'wp-woocommerce-printify-sync'); ?> 
    <strong><?php echo esc_html($order_data['printify_id']); ?></strong>
</p>

<?php
do_action('woocommerce_email_footer', $email);