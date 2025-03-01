<?php
/**
 * Product Sync template for WooCommerce Printify Sync Plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */
?>
<div class="wrap">
    <h1><?php esc_html_e('Product Sync', 'wp-woocommerce-printify-sync'); ?></h1>
    <div class="card">
        <button id="sync-products-btn" class="button button-primary">
            <?php esc_html_e('Sync Products Now', 'wp-woocommerce-printify-sync'); ?>
        </button>
        <div id="sync-status"></div>
    </div>
</div>