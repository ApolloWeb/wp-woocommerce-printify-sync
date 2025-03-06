<?php
/**
 * Email Templates Configuration template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Action buttons for the card
$card_actions = '
<a href="#" class="btn btn-sm btn-primary">
    <i class="fas fa-save me-1"></i> ' . esc_html__('Save Changes', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <form method="post" action="options.php">
            <?php settings_fields('wpwprintifysync_email_settings'); ?>
            <?php do_settings_sections('wpwprintifysync_email_settings'); ?>
            
            <div class="mb-3">
                <label for="wpwprintifysync_email_from_name" class="form-label"><?php esc_html_e('From Name', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="text" class="form-control" id="wpwprintifysync_email_from_name" name="wpwprintifysync_email_from_name" value="<?php echo esc_attr(get_option('wpwprintifysync_email_from_name')); ?>">
            </div>

            <div class="mb-3">
                <label for="wpwprintifysync_email_from_address" class="form-label"><?php esc_html_e('From Address', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="email" class="form-control" id="wpwprintifysync_email_from_address" name="wpwprintifysync_email_from_address" value="<?php echo esc_attr(get_option('wpwprintifysync_email_from_address')); ?>">
            </div>

            <div class="mb-3">
                <label for="wpwprintifysync_email_subject" class="form-label"><?php esc_html_e('Email Subject', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="text" class="form-control" id="wpwprintifysync_email_subject" name="wpwprintifysync_email_subject" value="<?php echo esc_attr(get_option('wpwprintifysync_email_subject')); ?>">
            </div>

            <div class="mb-3">
                <label for="wpwprintifysync_email_content" class="form-label"><?php esc_html_e('Email Content', 'wp-woocommerce-printify-sync'); ?></label>
                <textarea class="form-control" id="wpwprintifysync_email_content" name="wpwprintifysync_email_content" rows="10"><?php echo esc_textarea(get_option('wpwprintifysync_email_content')); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?php esc_html_e('Save Changes', 'wp-woocommerce-printify-sync'); ?></button>
        </form>
    </div>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Email Templates Configuration', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-envelope',
    'card_actions' => $card_actions,
));