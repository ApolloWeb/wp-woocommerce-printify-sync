<?php
/**
 * Base admin template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap wpwprintifysync-admin-page">
    <div class="row">
        <div class="col-12">
            <?php do_action('wpwprintifysync_admin_notices'); ?>
            <?php do_action('wpwprintifysync_before_admin_content'); ?>
            
            <div class="wpwprintifysync-admin-content">
                <?php
                // Template content will be inserted here
                if (isset($template_content)) {
                    echo wp_kses_post($template_content);
                }
                ?>
            </div>
            
            <?php do_action('wpwprintifysync_after_admin_content'); ?>
        </div>
    </div>
</div>