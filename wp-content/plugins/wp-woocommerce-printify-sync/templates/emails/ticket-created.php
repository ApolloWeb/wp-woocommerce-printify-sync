<?php
/**
 * Ticket Created Email Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/ticket-created.php.
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>
    <?php printf(
        /* translators: %s Customer first name */
        esc_html__('Hi %s,', 'wp-woocommerce-printify-sync'),
        esc_html($customer_name)
    ); ?>
</p>

<p>
    <?php esc_html_e('Thank you for contacting our support team. We have received your ticket and will respond as soon as possible.', 'wp-woocommerce-printify-sync'); ?>
</p>

<div style="margin: 20px 0; padding: 20px; background-color: #f8f9fa; border-radius: 4px;">
    <h2 style="color: #2c3338; margin-top: 0;">
        <?php esc_html_e('Ticket Details', 'wp-woocommerce-printify-sync'); ?>
    </h2>
    
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
        <tr>
            <th style="text-align:left; width: 30%;"><?php esc_html_e('Ticket ID:', 'wp-woocommerce-printify-sync'); ?></th>
            <td style="text-align:left;">#<?php echo esc_html($ticket_id); ?></td>
        </tr>
        <?php if (!empty($ticket_data['subject'])): ?>
            <tr>
                <th style="text-align:left;"><?php esc_html_e('Subject:', 'wp-woocommerce-printify-sync'); ?></th>
                <td style="text-align:left;"><?php echo esc_html($ticket_data['subject']); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($ticket_data['order_id'])): ?>
            <tr>
                <th style="text-align:left;"><?php esc_html_e('Related Order:', 'wp-woocommerce-printify-sync'); ?></th>
                <td style="text-align:left;">#<?php echo esc_html($ticket_data['order_id']); ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <th style="text-align:left;"><?php esc_html_e('Status:', 'wp-woocommerce-printify-sync'); ?></th>
            <td style="text-align:left;">
                <span style="background-color: #28a745; color: #fff; padding: 2px 8px; border-radius: 3px;">
                    <?php esc_html_e('Open', 'wp-woocommerce-printify-sync'); ?>
                </span>
            </td>
        </tr>
    </table>
</div>

<div style="margin: 20px 0; padding: 20px; background-color: #f8f9fa; border-radius: 4px;">
    <h2 style="color: #2c3338; margin-top: 0;">
        <?php esc_html_e('Your Message', 'wp-woocommerce-printify-sync'); ?>
    </h2>
    <?php echo wp_kses_post(wpautop(wptexturize($ticket_data['message']))); ?>
    
    <?php if (!empty($ticket_data['attachments'])): ?>
        <div style="margin-top: 20px;">
            <h3 style="color: #2c3338;"><?php esc_html_e('Attachments', 'wp-woocommerce-printify-sync'); ?></h3>
            <ul>
                <?php foreach ($ticket_data['attachments'] as $attachment): ?>
                    <li>
                        <a href="<?php echo esc_url($attachment['url']); ?>">
                            <?php echo esc_html($attachment['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php if ($additional_content): ?>
    <div style="margin: 20px 0;">
        <?php echo wp_kses_post(wpautop(wptexturize($additional_content))); ?>
    </div>
<?php endif; ?>

<?php
do_action('woocommerce_email_footer', $email);