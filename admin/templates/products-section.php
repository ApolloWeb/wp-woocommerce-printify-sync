/**
 * products-section class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 * Time: 02:20:39
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

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
