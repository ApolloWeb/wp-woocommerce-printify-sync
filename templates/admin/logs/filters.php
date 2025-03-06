<?php
/**
 * Log filters template part
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Format date to default date input format
$formatted_date_from = $date_from ? date('Y-m-d', strtotime($date_from)) : '';
$formatted_date_to = $date_to ? date('Y-m-d', strtotime($date_to)) : '';
?>

<div class="wpwprintifysync-filters">
    <form method="get" action="">
        <input type="hidden" name="page" value="wpwprintifysync-logs">
        
        <select name="level">
            <option value=""><?php _e('All Levels', 'wp-woocommerce-printify-sync'); ?></option>
            <?php foreach ($log_levels as $level => $label): ?>
                <option value="<?php echo esc_attr($level); ?>" <?php selected($log_level, $level); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="date_from"><?php _e('From:', 'wp-woocommerce-printify-sync'); ?></label>
        <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($formatted_date_from); ?>">
        
        <label for="date_to"><?php _e('To:', 'wp-woocommerce-printify-sync'); ?></label>
        <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($formatted_date_to); ?>">
        
        <select name="context">
            <option value=""><?php _e('All Contexts', 'wp-woocommerce-printify-sync'); ?></option>
            <?php foreach ($contexts as $ctx): ?>
                <option value="<?php echo esc_attr($ctx); ?>" <?php selected($context, $ctx); ?>>
                    <?php echo esc_html($ctx); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search logs', 'wp-woocommerce-printify-sync'); ?>">
        
        <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'wp-woocommerce-printify-sync'); ?>">
    </form>
</div>