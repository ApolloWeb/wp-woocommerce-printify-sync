<?php
/**
 * Tools Import/Export template
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
    <i class="fas fa-upload me-1"></i> ' . esc_html__('Import', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="import_file" class="form-label"><?php esc_html_e('Import File', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv">
            </div>
            <button type="submit" class="btn btn-primary"><?php esc_html_e('Import', 'wp-woocommerce-printify-sync'); ?></button>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <h5><?php esc_html_e('Export Data', 'wp-woocommerce-printify-sync'); ?></h5>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="wpwprintifysync_export_data">
            <button type="submit" class="btn btn-primary"><?php esc_html_e('Export CSV', 'wp-woocommerce-printify-sync'); ?></button>
        </form>
    </div>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Import/Export Tools', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-tools',
    'card_actions' => $card_actions,
));