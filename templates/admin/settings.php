<?php
/**
 * Admin Settings Template
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

<div class="wrap wpwprintifysync-admin">
    <h1><?php _e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('wpwprintifysync_settings');
        do_settings_sections('printify-sync-settings');
        submit_button();
        ?>
    </form>
    
    <div class="postbox">
        <h2 class="hndle"><?php _e('Help & Documentation', 'wp-woocommerce-printify-sync'); ?></h2>
        
        <div class="inside">
            <p><?php _e('For more detailed information on how to use this plugin, please refer to the documentation:', 'wp-woocommerce-printify-sync'); ?></p>
            
            <ul style="list-style-type: disc; padding-left: 20px;">
                <li><a href="https://example.com/docs/getting-started" target="_blank"><?php _e('Getting Started Guide', 'wp-woocommerce-printify-sync'); ?></a></li>
                <li><a href="https://example.com/docs/api-setup" target="_blank"><?php _e('API Setup Instructions', 'wp-woocommerce-printify-sync'); ?></a></li>
                <li><a href="https://example.com/docs/troubleshooting" target="_blank"><?php _e('Troubleshooting Common Issues', 'wp-woocommerce-printify-sync'); ?></a></li>
            </ul>
            
            <p><?php _e('If you need further assistance, please contact our support team.', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
    </div>
</div>