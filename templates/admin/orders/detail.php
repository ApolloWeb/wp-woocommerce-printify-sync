<?php
/**
 * Orders detail template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Fetch order details using the order ID
$order_id = isset($args['order_id']) ? intval($args['order_id']) : 0;
$order = wc_get_order($order_id);

if (!$order) {
    echo '<div class="alert alert-danger">' . esc_html__('Order not found.', 'wp-woocommerce-printify-sync') . '</div>';
    return;
}

$card_actions = '
<a href="' . esc_url(admin_url('admin.php?page=wpwprintifysync-orders')) . '" class="btn btn-sm btn-secondary">
    <i class="fas fa-arrow-left me-1"></i> ' . esc_html__('Back to Orders', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <h5><?php echo esc_html__('Order Details', 'wp-woocommerce-printify-sync'); ?></h5>
        <table class="table table-striped">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Order Number:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html($order->get_order_number()); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Order Date:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Order Status:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><span class="badge bg-<?php echo esc_attr(wc_get_order_status_class($order->get_status())); ?>"><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Customer:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Total:', 'wp-woocommerce-printify-sync'); ?></th>
                    <td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <h5><?php esc_html_e('Order Items', 'wp-woocommerce-printify-sync'); ?></h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Product', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Quantity', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Total', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item->get_name()); ?></td>
                        <td><?php echo esc_html($item->get_quantity()); ?></td>
                        <td><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <h5><?php esc_html_e('Order Notes', 'wp-woocommerce-printify-sync'); ?></h5>
        <ul class="list-group">
            <?php foreach ($order->get_notes() as $note) : ?>
                <li class="list-group-item">
                    <span class="badge bg-secondary"><?php echo esc_html($note->date_created->date_i18n(get_option('date_format') . ' ' . get_option('time_format'))); ?></span>
                    <?php echo wp_kses_post($note->content); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Order Details', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-shopping-cart',
    'card_actions' => $card_actions,
));