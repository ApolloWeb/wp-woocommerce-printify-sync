<?php
/**
 * Printify Order Shipped email
 *
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php printf(
    /* translators: %s: Customer first name */
    esc_html__('Hi %s,', 'wp-woocommerce-printify-sync'),
    esc_html($order->get_billing_first_name())
); ?></p>

<p><?php printf(
    /* translators: %s: Order number */
    esc_html__('Great news! Your order #%s has been shipped and is on its way to you.', 'wp-woocommerce-printify-sync'),
    esc_html($order->get_order_number())
); ?></p>

<?php
$tracking_number = $order->get_meta('_printify_tracking_number');
$tracking_url = $order->get_meta('_printify_tracking_url');
$carrier = $order->get_meta('_printify_tracking_carrier');

if ($tracking_number): ?>
    <h2><?php esc_html_e('Tracking Information', 'wp-woocommerce-printify-sync'); ?></h2>
    <ul>
        <li><?php esc_html_e('Carrier:', 'wp-woocommerce-printify-sync'); ?> <?php echo esc_html($carrier); ?></li>
        <li>
            <?php esc_html_e('Tracking Number:', 'wp-woocommerce-printify-sync'); ?> 
            <?php if ($tracking_url): ?>
                <a href="<?php echo esc_url($tracking_url); ?>" target="_blank"><?php echo esc_html($tracking_number); ?></a>
            <?php else: ?>
                <?php echo esc_html($tracking_number); ?>
            <?php endif; ?>
        </li>
    </ul>
<?php endif; ?>

<h2><?php esc_html_e('Order Details', 'wp-woocommerce-printify-sync'); ?></h2>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);