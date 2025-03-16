<?php
/**
 * Base Ticket Email Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/ticket-base.php.
 *
 * @package WP-WooCommerce-Printify-Sync
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>
    <?php printf(
        /* translators: %s Customer first name */
        esc_html__('Hi %s,', 'wp-woocommerce-printify-sync'),
        esc_html($customer_name)
    ); ?>
</p>

<?php /* translators: %s: Site title */ ?>
<p><?php printf(esc_html__('Here are the details of your support ticket on %s:', 'wp-woocommerce-printify-sync'), esc_html(wp_specialchars_decode(get_option('blogname'), ENT_QUOTES))); ?></p>

<div style="margin-bottom: 40px;">
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">
        <tbody>
            <tr>
                <th class="td" scope="row" style="text-align:left;">
                    <?php esc_html_e('Ticket ID:', 'wp-woocommerce-printify-sync'); ?>
                </th>
                <td class="td" style="text-align:left;">
                    <?php echo esc_html($ticket_id); ?>
                </td>
            </tr>
            <?php if (!empty($ticket_data['status'])): ?>
                <tr>
                    <th class="td" scope="row" style="text-align:left;">
                        <?php esc_html_e('Status:', 'wp-woocommerce-printify-sync'); ?>
                    </th>
                    <td class="td" style="text-align:left;">
                        <?php echo esc_html($ticket_data['status']); ?>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if (!empty($order)): ?>
                <tr>
                    <th class="td" scope="row" style="text-align:left;">
                        <?php esc_html_e('Related Order:', 'wp-woocommerce-printify-sync'); ?>
                    </th>
                    <td class="td" style="text-align:left;">
                        <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                            <?php echo esc_html($order->get_order_number()); ?>
                        </a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($ticket_data['message'])): ?>
        <div style="margin-bottom: 40px;">
            <h2><?php esc_html_e('Message:', 'wp-woocommerce-printify-sync'); ?></h2>
            <div style="color: #636363; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
                <?php echo wp_kses_post(wpautop(wptexturize($ticket_data['message']))); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
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