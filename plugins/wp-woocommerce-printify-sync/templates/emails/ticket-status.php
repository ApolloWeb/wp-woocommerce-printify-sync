<?php
/**
 * Ticket Status Change Email Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/ticket-status.php.
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email);

// Get status colors
$status_colors = [
    'open' => '#28a745',
    'pending' => '#ffc107',
    'in_progress' => '#17a2b8',
    'on_hold' => '#6c757d',
    'resolved' => '#28a745',
    'closed' => '#dc3545'
];

$new_status_color = $status_colors[$ticket_data['new_status']] ?? '#6c757d';
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
        <?php esc_html_e('Ticket Status Update', 'wp-woocommerce-printify-sync'); ?>
    </h2>
    
    <div style="margin: 20px 0; text-align: center;">
        <?php if (!empty($ticket_data['old_status'])): ?>
            <span style="display: inline-block; padding: 8px 16px; background-color: <?php echo esc_attr($status_colors[$ticket_data['old_status']] ?? '#6c757d'); ?>; color: #fff; border-radius: 3px;">
                <?php echo esc_html(ucfirst($ticket_data['old_status'])); ?>
            </span>
            <span style="display: inline-block; margin: 0 15px; color: #6c757d;">
                <i class="fas fa-arrow-right"></i>
            </span>
        <?php endif; ?>
        <span style="display: inline-block; padding: 8px 16px; background-color: <?php echo esc_attr($new_status_color); ?>; color: #fff; border-radius: 3px;">
            <?php echo esc_html(ucfirst($ticket_data['new_status'])); ?>
        </span>
    </div>

    <?php if (!empty($ticket_data['status_note'])): ?>
        <div style="margin-top: 20px; padding: 15px; background-color: #ffffff; border-radius: 3px;">
            <?php echo wp_kses_post(wpautop(wptexturize($ticket_data['status_note']))); ?>
        </div>
    <?php endif; ?>
</div>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;" border="1">
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
</table>

<?php if ($ticket_data['new_status'] === 'resolved'): ?>
    <div style="margin: 20px 0; padding: 15px; background-color: #e8f5e9; border-radius: 4px; color: #2e7d32;">
        <p style="margin: 0;">
            <?php esc_html_e('If you\'re satisfied with the resolution, no further action is needed. If you need additional assistance, simply reply to this email to reopen the ticket.', 'wp-woocommerce-printify-sync'); ?>
        </p>
    </div>
<?php endif; ?>

<?php if ($additional_content): ?>
    <div style="margin: 20px 0;">
        <?php echo wp_kses_post(wpautop(wptexturize($additional_content))); ?>
    </div>
<?php endif; ?>

<?php
do_action('woocommerce_email_footer', $email);