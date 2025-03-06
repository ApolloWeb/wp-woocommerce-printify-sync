<?php
/**
 * Settings General template
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
            <?php settings_fields('wpwprintifysync_general_settings'); ?>
            <?php do_settings_sections('wpwprintifysync_general_settings'); ?>
            
            <div class="mb-3">
                <label for="wpwprintifysync_api_key" class="form-label"><?php esc_html_e('API Key', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="text" class="form-control" id="wpwprintifysync_api_key" name="wpwprintifysync_api_key" value="<?php echo esc_attr(get_option('wpwprintifysync_api_key')); ?>">
            </div>

            <div class="mb-3">
                <label for="wpwprintifysync_store_id" class="form-label"><?php esc_html_e('Store ID', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="text" class="form-control" id="wpwprintifysync_store_id" name="wpwprintifysync_store_id" value="<?php echo esc_attr(get_option('wpwprintifysync_store_id')); ?>">
            </div>

            <div class="mb-3">
                <label for="wpwprintifysync_sync_interval" class="form-label"><?php esc_html_e('Sync Interval', 'wp-woocommerce-printify-sync'); ?></label>
                <select class="form-select" id="wpwprintifysync_sync_interval" name="wpwprintifysync_sync_interval">
                    <option value="hourly" <?php selected(get_option('wpwprintifysync_sync_interval'), 'hourly'); ?>><?php esc_html_e('Hourly', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="twicedaily" <?php selected(get_option('wpwprintifysync_sync_interval'), 'twicedaily'); ?>><?php esc_html_e('Twice Daily', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="daily" <?php selected(get_option('wpwprintifysync_sync_interval'), 'daily'); ?>><?php esc_html_e('Daily', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary"><?php esc_html_e('Save Changes', 'wp-woocommerce-printify-sync'); ?></button>
        </form>
    </div>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('General Settings', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-cog',
    'card_actions' => $card_actions,
));