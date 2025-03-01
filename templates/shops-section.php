<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) {
    exit;
}

$selected_shop = get_option('wp_woocommerce_printify_sync_selected_shop');
?>

<div class="wrap">
    <h2><?php esc_html_e('Select Printify Shop', 'wp-woocommerce-printify-sync'); ?></h2>
    <div id="shops-message"></div>
    <form method="post" action="options.php" id="shops-form">
        <?php settings_fields('printify-sync'); ?>
        <table class="form-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Shop Name', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Shop ID', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Action', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody id="shops-table-body">
                <!-- Shops will be dynamically added here by JavaScript -->
            </tbody>
        </table>
        <input type="hidden" name="wp_woocommerce_printify_sync_selected_shop" id="wp_woocommerce_printify_sync_selected_shop"