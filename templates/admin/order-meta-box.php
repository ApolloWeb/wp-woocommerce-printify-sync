<?php
/**
 * Order Meta Box Template
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

<div class="printify-order-data">
    <?php if (!empty($printify_id)): ?>
        <div class="printify-order-info">
            <p>
                <strong><?php _e('Printify Order ID:', 'wp-woocommerce-printify-sync'); ?></strong>
                <span><?php echo esc_html($printify_id); ?></span>
            </p>
            
            <p>
                <strong><?php _e('Printify Status:', 'wp-woocommerce-printify-sync'); ?></strong>
                <span class="printify-status printify-status-<?php echo esc_attr(strtolower($printify_status)); ?>">
                    <?php echo esc_html(ucfirst($printify_status)); ?>
                </span>
            </p>
            
            <?php if (!empty($last_synced)): ?>
                <p>
                    <strong><?php _e('Last Synced:', 'wp-woocommerce-printify-sync'); ?></strong>
                    <span><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_synced)); ?></span>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($tracking_info) && is_array($tracking_info)): ?>
                <div class="printify-tracking-info">
                    <h4><?php _e('Tracking Information', 'wp-woocommerce-printify-sync'); ?></h4>
                    
                    <?php foreach ($tracking_info as $tracking): ?>
                        <div class="tracking-item">
                            <p>
                                <strong><?php _e('Carrier:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span><?php echo esc_html($tracking['carrier'] ?? ''); ?></span>
                            </p>
                            
                            <p>
                                <strong><?php _e('Number:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span><?php echo esc_html($tracking['number'] ?? ''); ?></span>
                            </p>
                            
                            <?php if (!empty($tracking['url'])): ?>
                                <p>
                                    <a href="<?php echo esc_url($tracking['url']); ?>" target="_blank" class="button button-small">
                                        <?php _e('Track Package', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p><?php _e('This order has not been sent to Printify yet.', 'wp-woocommerce-printify-sync'); ?></p>
        
        <?php
        // Check if order contains Printify products
        $has_printify_products = false;
        $order = wc_get_order($post->ID);
        
        if ($order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $printify_id = get_post_meta($product_id, '_printify_product_id', true);
                
                if ($printify_id) {
                    $has_printify_products = true;
                    break;
                }
            }
        }
        
        if ($has_printify_products): ?>
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=wpwprintifysync_send_to_printify&order_id=' . $post->ID), 'wpwprintifysync_send_to_printify')); ?>" class="button button-primary">
                    <?php _e('Send to Printify', 'wp-woocommerce-printify-sync'); ?>
                </a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style type="text/css">
    .printify-status {
        display: inline-block;
        padding: 3px 6px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .printify-status-pending {
        background-color: #f8d7da;
        color: #842029;
    }
    
    .printify-status-onhold,
    .printify-status-on-hold {
        background-color: #fff3cd;
        color: #664d03;
    }
    
    .printify-status-fulfilled {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    
    .printify-status-cancelled,
    .printify-status-canceled {
        background-color: #e2e3e5;
        color: #41464b;
    }
    
    .printify-status-inprogress,
    .printify-status-in-progress {
        background-color: #cff4fc;
        color: #055160;
    }
    
    .printify-tracking-info {
        margin-top: 10px;
        border-top: 1px solid #eee;
        padding-top: 10px;
    }
    
    .tracking-item {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px dotted #eee;
    }
    
    .tracking-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
</style>