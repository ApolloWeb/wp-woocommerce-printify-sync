<?php
/**
 * Product Meta Box Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="printify-product-data">
    <?php if (!empty($printify_id)): ?>
        <div class="printify-product-info">
            <p>
                <strong><?php _e('Printify Product ID:', 'wp-woocommerce-printify-sync'); ?></strong>
                <span><?php echo esc_html($printify_id); ?></span>
            </p>
            
            <?php if (!empty($provider_name)): ?>
                <p>
                    <strong><?php _e('Print Provider:', 'wp-woocommerce-printify-sync'); ?></strong>
                    <span><?php echo esc_html($provider_name); ?></span>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($blueprint_id)): ?>
                <p>
                    <strong><?php _e('Blueprint ID:', 'wp-woocommerce-printify-sync'); ?></strong>
                    <span><?php echo esc_html($blueprint_id); ?></span>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($last_synced)): ?>
                <p>
                    <strong><?php _e('Last Synced:', 'wp-woocommerce-printify-sync'); ?></strong>
                    <span><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_synced)); ?></span>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="printify-product-actions">
            <p>
                <label>
                    <input type="checkbox" name="wpwprintifysync_manual_sync" id="wpwprintifysync_manual_sync" value="1" />
                    <?php _e('Sync from Printify on save', 'wp-woocommerce-printify-sync'); ?>
                </label>
            </p>
            
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=wpwprintifysync_sync_product&product_id=' . $post->ID), 'wpwprintifysync_sync_product')); ?>" class="button">
                    <?php _e('Sync Now', 'wp-woocommerce-printify-sync'); ?>
                </a>
                
                <a href="<?php echo esc_url('https://printify.com/app/shop/products/' . $printify_id . '/edit'); ?>" target="_blank" class="button">
                    <?php _e('View in Printify', 'wp-woocommerce-printify-sync'); ?>
                </a>
            </p>
        </div>
        
    <?php else: ?>
        <p><?php _e('This product is not linked to Printify.', 'wp-woocommerce-printify-sync'); ?></p>
        
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=printify-sync-products')); ?>" class="button">
                <?php _e('Manage Printify Products', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </p>
    <?php endif; ?>
</div>