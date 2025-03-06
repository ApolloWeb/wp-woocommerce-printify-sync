<?php
/**
 * Tickets list template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Action buttons for the card
$card_actions = '
<a href="' . esc_url(admin_url('admin.php?page=wpwprintifysync-tickets&action=new')) . '" class="btn btn-sm btn-primary">
    <i class="fas fa-plus me-1"></i> ' . esc_html__('Add New Ticket', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <form class="row g-2" method="get">
            <input type="hidden" name="page" value="wpwprintifysync-tickets">
            
            <div class="col-12 col-md-3 col-lg-2">
                <select name="ticket_status" class="form-select form-select-sm">
                    <option value=""><?php esc_html_e('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="open" <?php selected(isset($_GET['ticket_status']) ? $_GET['ticket_status'] : '', 'open'); ?>><?php esc_html_e('Open', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="closed" <?php selected(isset($_GET['ticket_status']) ? $_GET['ticket_status'] : '', 'closed'); ?>><?php esc_html_e('Closed', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
            </div>
            
            <div class="col-12 col-md-3 col-lg-2">
                <select name="ticket_category" class="form-select form-select-sm">
                    <option value=""><?php esc_html_e('All Categories', 'wp-woocommerce-printify-sync'); ?></option>
                    <!-- Options -->
                </select>
            </div>
            
            <div class="col-12 col-md-4 col-lg-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search tickets...', 'wp-woocommerce-printify-sync'); ?>" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
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
                <th style="width: 60px;"><?php esc_html_e('Ticket', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Subject', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Customer', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Last Update', 'wp-woocommerce-printify-sync'); ?></th>
                <th style="width: 130px;"><?php esc_html_e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tickets)) : ?>
                <?php foreach ($tickets as $ticket) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url($ticket['edit_link']); ?>">
                                <?php echo esc_html($ticket['ticket_number']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($ticket['subject']); ?></td>
                        <td><?php echo esc_html($ticket['customer']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo esc_attr($ticket['status_color']); ?>">
                                <?php echo esc_html($ticket['status_text']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($ticket['last_update_formatted']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo esc_url($ticket['edit_link']); ?>" class="btn btn-outline-secondary" title="<?php esc_attr_e('Edit', 'wp-woocommerce-printify-sync'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" class="btn btn-outline-danger" title="<?php esc_attr_e('Delete', 'wp-woocommerce-printify-sync'); ?>" data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" class="text-center"><?php esc_html_e('No tickets found.', 'wp-woocommerce-printify-sync'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Tickets', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-ticket-alt',
    'card_actions' => $card_actions,
));