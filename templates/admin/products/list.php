<?php
/**
 * Products list template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Action buttons for the card
$card_actions = '
<a href="' . esc_url(admin_url('admin.php?page=wpwprintifysync-products&action=new')) . '" class="btn btn-sm btn-primary">
    <i class="fas fa-plus me-1"></i> ' . esc_html__('Add New', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <form class="row g-2" method="get">
            <input type="hidden" name="page" value="wpwprintifysync-products">
            
            <div class="col-12 col-md-3 col-lg-2">
                <select name="product_status" class="form-select form-select-sm">
                    <option value=""><?php esc_html_e('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="published" <?php selected(isset($_GET['product_status']) ? $_GET['product_status'] : '', 'published'); ?>><?php esc_html_e('Published', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="draft" <?php selected(isset($_GET['product_status']) ? $_GET['product_status'] : '', 'draft'); ?>><?php esc_html_e('Draft', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
            </div>
            
            <div class="col-12 col-md-3 col-lg-2">
                <select name="product_type" class="form-select form-select-sm">
                    <option value=""><?php esc_html_e('All Types', 'wp-woocommerce-printify-sync'); ?></option>
                    <!-- Options -->
                </select>
            </div>
            
            <div class="col-12 col-md-4 col-lg-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search products...', 'wp-woocommerce-printify-sync'); ?>" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
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
                <th style="width: 60px;"><?php esc_html_e('Image', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Name', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('SKU', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Price', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                <th><?php esc_html_e('Last Synced', 'wp-woocommerce-printify-sync'); ?></th>
                <th style="width: 130px;"><?php esc_html_e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)) : ?>
                <?php foreach ($products as $product) : ?>
                    <tr>
                        <td>
                            <img src="<?php echo esc_url($product['image']); ?>" class="img-thumbnail" alt="<?php echo esc_attr($product['name']); ?>" width="50" height="50">
                        </td>
                        <td>
                            <a href="<?php echo esc_url($product['edit_link']); ?>">
                                <?php echo esc_html($product['name']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($product['sku']); ?></td>
                        <td><?php echo wp_kses_post($product['price_formatted']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo esc_attr($product['status_color']); ?>">
                                <?php echo esc_html($product['status_text']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($product['last_synced']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo esc_url($product['edit_link']); ?>" class="btn btn-outline-secondary" title="<?php esc_attr_e('Edit', 'wp-woocommerce-printify-sync'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" class="btn btn-outline-danger" title="<?php esc_attr_e('Delete', 'wp-woocommerce-printify-sync'); ?>" data-product-id="<?php echo esc_attr($product['id']); ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7" class="text-center"><?php esc_html_e('No products found.', 'wp-woocommerce-printify-sync'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Products', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-box',
    'card_actions' => $card_actions,
));