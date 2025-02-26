<?php
/**
 * Products section template
 *
 * @package WP_WooCommerce_Printify_Sync
 */

defined('ABSPATH') || exit;
?>
<div class="printify-action-section">
    <h2><?php _e('Printify Products', 'wp-woocommerce-printify-sync'); ?></h2>
    <p><?php _e('View available products from your selected Printify shop.', 'wp-woocommerce-printify-sync'); ?></p>
    <button id="fetch-printify-products" class="button button-secondary">
        <?php _e('Fetch Printify Products', 'wp-woocommerce-printify-sync'); ?>
    </button>
    <div id="printify-products-results"></div>
</div>