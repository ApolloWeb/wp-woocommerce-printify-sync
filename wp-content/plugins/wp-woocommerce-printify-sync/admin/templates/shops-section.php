<?php
/**
 * Shops section template
 *
 * @package WP_WooCommerce_Printify_Sync
 */

defined('ABSPATH') || exit;
?>
<div class="printify-action-section">
    <h2><?php _e('Printify Shops', 'wp-woocommerce-printify-sync'); ?></h2>
    <p><?php _e('Available Printify shops for your account. Click "Select" to choose a shop for product synchronization:', 'wp-woocommerce-printify-sync'); ?></p>
    <div id="printify-shops-results">
        <div class="printify-loading"><?php _e('Loading shops...', 'wp-woocommerce-printify-sync'); ?></div>
    </div>
    
    <?php
    // Display the currently selected shop ID (hidden field)
    $shop_id = get_option('printify_selected_shop', '');
    echo '<input type="hidden" id="printify_selected_shop" name="printify_selected_shop" value="' . esc_attr($shop_id) . '">';
    ?>
</div>