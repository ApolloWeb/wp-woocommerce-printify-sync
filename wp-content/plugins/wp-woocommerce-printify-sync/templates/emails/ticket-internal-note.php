<?php
/**
 * Ticket Internal Note Email Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/ticket-internal-note.php.
 */

defined('ABSPATH') || exit;

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

<p>
    <?php echo esc_html($email->get_option('note_prefix')); ?>
</p>

<div style="margin: 20px 0; padding: 20px; background-color: #f8f9fa; border-radius: 4px;">
    <?php echo wp_kses_post(wpautop(wptexturize($ticket_data['content']))); ?>
</div>

<?php if (!empty($ticket_data['attachments'])): ?>
    <div style="margin: 20px 0;">
        <p><strong><?php esc_html_e('Attachments:', 'wp-woocommerce-printify-sync'); ?></strong></p>
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

<p>
    <?php printf(
        /* translators: %s: Ticket ID */
        esc_html__('For reference, your ticket ID is: #%s', 'wp-woocommerce-printify-sync'),
        esc_html($ticket_id)
    ); ?>
</p>

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