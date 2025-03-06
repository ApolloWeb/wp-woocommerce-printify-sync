<?php
/**
 * Email Templates list template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Action buttons for the card
$card_actions = '
<a href="' . esc_url(admin_url('admin.php?page=wpwprintifysync-emails&action=new')) . '" class="btn btn-sm btn-primary">
    <i class="fas fa-plus me-1"></i> ' . esc_html__('Add New Template', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <form class="row g-2" method="get">
            <input type="hidden" name="page" value="wpwprintifysync-emails">
            
            <div class="col-12 col-md-4 col-lg-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search templates...', 'wp-woocommerce-printify-sync'); ?>" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><?php esc_html_e('Search', 'wp-woocommerce-printify-sync'); ?></button>
                </div>
            </div>

            <div class="col-12 col-md-2 col-lg-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100"><?php esc_html_e('Filter', 'wp-woocommerce-printify-sync'); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Subject', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Last Modified', 'wp-woocommerce-printify-sync'); ?></th>
                <th style="width: 130px;"><?php esc_html_e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($email_templates)) : ?>
                <?php foreach ($email_templates as $template) : ?>
                    <tr>
                        <td><?php echo esc_html($template['name']); ?></td>
                        <td><?php echo esc_html($template['subject']); ?></td>
                        <td><?php echo esc_html($template['last_modified']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo esc_url($template['edit_link']); ?>" class="btn btn-outline-secondary" title="<?php esc_attr_e('Edit', 'wp-woocommerce-printify-sync'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" class="btn btn-outline-danger" title="<?php esc_attr_e('Delete', 'wp-woocommerce-printify-sync'); ?>" data-template-id="<?php echo esc_attr($template['id']); ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4" class="text-center"><?php esc_html_e('No email templates found.', 'wp-woocommerce-printify-sync'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Email Templates', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-envelope',
    'card_actions' => $card_actions,
));