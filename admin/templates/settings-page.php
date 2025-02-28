/**
 * settings-page class for Printify Sync plugin
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
<div class="wrap printify-sync-settings">
    <h1><?php _e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <form method="post" action="options.php">
        <?php
            settings_fields('printify_sync_settings');
            do_settings_sections('printify-sync');
            submit_button();
        ?>
    </form>
    
    <?php include __DIR__ . '/shops-section.php'; ?>
    
    <?php include __DIR__ . '/products-section.php'; ?>
</div>
