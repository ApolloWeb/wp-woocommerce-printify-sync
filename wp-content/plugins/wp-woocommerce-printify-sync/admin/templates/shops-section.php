<?php
/**
 * Shops section template
 *
 * @package WP_WooCommerce_Printify_Sync
 */

defined('ABSPATH') || exit;

// Get the currently selected shop ID
$shop_id = get_option('printify_selected_shop', '');
?>
<div class="printify-action-section">
    <h2><?php _e('Printify Shops', 'wp-woocommerce-printify-sync'); ?></h2>
    <p><?php _e('Available Printify shops for your account. Click "Select" to choose a shop for product synchronization:', 'wp-woocommerce-printify-sync'); ?></p>
    
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <div class="printify-debug-info">
        <p><strong>Debug Info:</strong> Current shop ID: <?php echo esc_html($shop_id ?: 'None'); ?></p>
    </div>
    <?php endif; ?>
    
    <div id="printify-shops-results">
        <div class="printify-loading"><?php _e('Loading shops...', 'wp-woocommerce-printify-sync'); ?></div>
    </div>
    
    <input type="hidden" id="printify_selected_shop" name="printify_selected_shop" value="<?php echo esc_attr($shop_id); ?>">
</div>