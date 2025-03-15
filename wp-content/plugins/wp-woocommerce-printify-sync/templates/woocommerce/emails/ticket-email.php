<?php
/**
 * Support Ticket email
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email); ?>

<p><?php printf(esc_html__('Ticket #%s has been updated.', 'wp-woocommerce-printify-sync'), esc_html($ticket->id)); ?></p>

<h2><?php echo esc_html($ticket->subject); ?></h2>

<?php if (!empty($ticket->order_id)): ?>
    <p>
        <?php 
        printf(
            esc_html__('Related Order: #%s', 'wp-woocommerce-printify-sync'),
            '<a href="' . esc_url(wc_get_order_admin_url($ticket->order_id)) . '">' . esc_html($ticket->order_id) . '</a>'
        );
        ?>
    </p>
<?php endif; ?>

<div style="margin-bottom: 40px;">
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5;">
        <tbody>
            <tr>
                <th class="td" scope="row" style="text-align: left; width: 30%;">
                    <?php esc_html_e('Status:', 'wp-woocommerce-printify-sync'); ?>
                </th>
                <td class="td">
                    <?php echo esc_html(ucfirst($ticket->status)); ?>
                </td>
            </tr>
            <tr>
                <th class="td" scope="row" style="text-align: left;">
                    <?php esc_html_e('Priority:', 'wp-woocommerce-printify-sync'); ?>
                </th>
                <td class="td">
                    <?php echo esc_html(ucfirst($ticket->priority)); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php if (!empty($additional_content)): ?>
    <p><?php echo wp_kses_post(wpautop(wptexturize($additional_content))); ?></p>
<?php endif; ?>

<?php do_action('woocommerce_email_footer', $email); ?>