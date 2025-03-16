<?php
/**
 * Ticket Reply Email Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/ticket-reply.php.
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

<div style="margin: 20px 0; padding: 20px; background-color: #f8f9fa; border-radius: 4px;">
    <h2 style="color: #2c3338; margin-top: 0;">
        <?php esc_html_e('Our Response', 'wp-woocommerce-printify-sync'); ?>
    </h2>
    
    <div style="background-color: #ffffff; padding: 15px; border-radius: 3px;">
        <?php echo wp_kses_post(wpautop(wptexturize($ticket_data['message']))); ?>
    </div>

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

<?php if (!empty($ticket_data['include_original']) && !empty($ticket_data['original_message'])): ?>
    <div style="margin: 20px 0; padding: 20px; background-color: #f8f9fa; border-radius: 4px;">
        <h3 style="color: #2c3338; margin-top: 0;">
            <?php esc_html_e('Your Original Message', 'wp-woocommerce-printify-sync'); ?>
        </h3>
        <div style="color: #636363; border-left: 4px solid #dedede; padding-left: 15px;">
            <?php echo wp_kses_post(wpautop(wptexturize($ticket_data['original_message']))); ?>
        </div>
    </div>
<?php endif; ?>

<p style="margin: 20px 0; padding: 15px; background-color: #e8f5e9; border-radius: 4px; color: #2e7d32;">
    <?php printf(
        /* translators: %s: Ticket ID */
        esc_html__('For reference, your ticket ID is: #%s. You can reply to this email directly to update your ticket.', 'wp-woocommerce-printify-sync'),
        esc_html($ticket_id)
    ); ?>
</p>

<?php if ($additional_content): ?>
    <div style="margin: 20px 0;">
        <?php echo wp_kses_post(wpautop(wptexturize($additional_content))); ?>
    </div>
<?php endif; ?>

<div style="margin: 20px 0; text-align: center; color: #636363;">
    <p style="font-size: 12px;">
        <?php echo wp_kses_post(
            sprintf(
                /* translators: %s: Agent name */
                __('This response was sent by %s from our support team.', 'wp-woocommerce-printify-sync'),
                '<strong>' . esc_html($ticket_data['agent_name']) . '</strong>'
            )
        ); ?>
    </p>
</div>

<?php
do_action('woocommerce_email_footer', $email);