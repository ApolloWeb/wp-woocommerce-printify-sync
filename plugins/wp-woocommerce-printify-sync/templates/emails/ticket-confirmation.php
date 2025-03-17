<?php
/**
 * Support Ticket Confirmation Email
 */

defined('ABSPATH') || exit;

$ticketId = $ticket->ID;
$orderId = get_post_meta($ticketId, '_wpwps_order_id', true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e('Support Ticket Confirmation', 'wp-woocommerce-printify-sync'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h1 style="color: #2c3338; margin-top: 0;">
            <?php esc_html_e('Support Ticket Received', 'wp-woocommerce-printify-sync'); ?>
        </h1>
        
        <p><?php esc_html_e('Thank you for contacting us. We have received your support request.', 'wp-woocommerce-printify-sync'); ?></p>
        
        <div style="background-color: #ffffff; padding: 15px; border-radius: 3px; margin: 20px 0;">
            <h2 style="color: #2c3338; margin-top: 0; font-size: 18px;">
                <?php esc_html_e('Ticket Details', 'wp-woocommerce-printify-sync'); ?>
            </h2>
            
            <p><strong><?php esc_html_e('Ticket ID:', 'wp-woocommerce-printify-sync'); ?></strong> #<?php echo esc_html($ticketId); ?></p>
            
            <?php if ($orderId): ?>
                <p><strong><?php esc_html_e('Related Order:', 'wp-woocommerce-printify-sync'); ?></strong> #<?php echo esc_html($orderId); ?></p>
            <?php endif; ?>
            
            <p><strong><?php esc_html_e('Status:', 'wp-woocommerce-printify-sync'); ?></strong> 
                <?php esc_html_e('New', 'wp-woocommerce-printify-sync'); ?>
            </p>
        </div>

        <p><?php esc_html_e('We will review your request and respond as soon as possible. Our typical response time is within 24 hours.', 'wp-woocommerce-printify-sync'); ?></p>

        <?php if ($orderId): ?>
            <p><?php esc_html_e('For your reference, this ticket is associated with your order. We will review the order details while processing your request.', 'wp-woocommerce-printify-sync'); ?></p>
        <?php endif; ?>
    </div>

    <div style="font-size: 12px; color: #666; margin-top: 20px; text-align: center;">
        <p><?php esc_html_e('Please do not reply to this email. This inbox is not monitored.', 'wp-woocommerce-printify-sync'); ?></p>
        <p>
            <?php printf(
                /* translators: %s: Store name */
                esc_html__('This email was sent from %s', 'wp-woocommerce-printify-sync'),
                esc_html(get_bloginfo('name'))
            ); ?>
        </p>
    </div>
</body>
</html>