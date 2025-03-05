<?php
/**
 * Log export actions template part
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;
?>

<div class="wpwprintifysync-logs-actions">
    <div class="alignleft">
        <form method="post" action="">
            <?php wp_nonce_field('wpwprintifysync_export_logs', 'wpwprintifysync_export_nonce'); ?>
            <input type="submit" name="wpwprintifysync_export_logs" class="button button-primary" value="<?php esc_attr_e('Export Logs', 'wp-woocommerce-printify-sync'); ?>">
        </form>
    </div>
    
    <div class="alignright">
        <form method="post" action="" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to clean logs older than 7 days?', 'wp-woocommerce-printify-sync'); ?>');">
            <?php wp_nonce_field('wpwprintifysync_clean_logs', 'wpwprintifysync_clean_nonce'); ?>
            <input type="submit" name="wpwprintifysync_clean_logs" class="button button-secondary" value="<?php esc_attr_e('Clean Old Logs', 'wp-woocommerce-printify-sync'); ?>">
        </form>
    </div>
    
    <div class="clear"></div>
</div>