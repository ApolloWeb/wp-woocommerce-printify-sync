<?php
/**
 * Admin Products Template
 *
 * @var array $products
 * @var array $printifyProducts
 * @var array $stats
 * @var array $syncStatus
 */
?>

<div class="wrap wpwps-products">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <button type="button" class="page-title-action sync-all-products">
        <?php _e('Sync All Products', 'wp-woocommerce-printify-sync'); ?>
    </button>

    <!-- Stats Overview -->
    <div class="wpwps-stats-grid">
        <div class="wpwps-stat-card">
            <div class="stat-content">
                <h4><?php _e('Total Products', 'wp-woocommerce-printify-sync'); ?></h4>
                <span class="stat-value"><?php echo esc_html($stats['total']); ?></span>
            </div>
        </div>

        <div class="wpwps-stat-card">
            <div class="stat-content">
                <h4><?php _e('Synced Today', 'wp-woocommerce-printify-sync'); ?></h4>
                <span class="stat-value"><?php echo esc_html($stats['synced_today']); ?></span>
            </div>
        </div>

        <div class="wpwps-stat-card">
            <div class="stat-content">
                <h4><?php _e('Pending Sync', 'wp-woocommerce-printify-sync'); ?></h4>
                <span class="stat-value warning"><?php echo esc_html($stats['pending_sync']); ?></span>
            </div>
        </div>

        <div class="wpwps-stat-card">
            <div class="stat-content">
                <h4><?php _e('Sync Errors', 'wp-woocommerce-printify-sync'); ?></h4>
                <span class="stat-value error"><?php echo esc_html($stats['sync_errors']); ?></span>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="wpwps-card">
        <div class="card-header">
            <div class="card-title">
                <h2><?php _e('Products', 'wp-woocommerce-printify-sync'); ?></h2>
            </div>
            <div class="card-actions">
                <input type="text" id="product-search" class="search-box" placeholder="<?php esc_attr_e('Search products...', 'wp-woocommerce-printify-sync'); ?>">
                <select id="sync-status-filter">
                    <option value=""><?php esc_html_e('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="synced"><?php esc_html_e('Synced', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="pending"><?php esc_html_e('Pending', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="error"><?php esc_html_e('Error', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="column-thumb"><?php esc_html_e('Image', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Product', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('SKU', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Price', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Stock', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Last Synced', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr data-product-id="<?php echo esc_attr($product['id']); ?>">
                            <td class="column-thumb">
                                <?php echo $product['thumbnail']; ?>
                            </td>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url($product['edit_url']); ?>">
                                        <?php echo esc_html($product['title']); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html($product['sku']); ?></td>
                            <td><?php echo wc_price($product['price']); ?></td>
                            <td>
                                <span class="stock-status <?php echo esc_attr($product['stock_status']); ?>">
                                    <?php echo esc_html($product['stock_quantity']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="sync-status <?php echo esc_attr($product['sync_status']); ?>">
                                    <?php echo esc_html($product['sync_status_label']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['last_synced']): ?>
                                    <span title="<?php echo esc_attr($product['last_synced']); ?>">
                                        <?php echo esc_html(human_time_diff(strtotime($product['last_synced']))); ?> ago
                                    </span>
                                <?php else: ?>
                                    <?php esc_html_e('Never', 'wp-woocommerce-printify-sync'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button type="button" class="button sync-product" data-product-id="<?php echo esc_attr($product['id']); ?>">
                                    <span class="dashicons dashicons-update"></span>
                                </button>
                                <a href="<?php echo esc_url($product['edit_url']); ?>" class="button">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <?php if ($product['printify_url']): ?>
                                    <a href="<?php echo esc_url($product['printify_url']); ?>" class="button" target="_blank">
                                        <span class="dashicons dashicons-external"></span>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Import Products Modal -->
    <div id="import-products-modal" class="wpwps-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php _e('Import Products from Printify', 'wp-woocommerce-printify-sync'); ?></h2>
                <button type="button" class="close-modal">×</button>
            </div>
            <div class="modal-body">
                <div class="printify-products-grid">
                    <?php foreach ($printifyProducts as $product): ?>
                        <div class="product-card" data-product-id="<?php echo esc_attr($product['id']); ?>">
                            <div class="product-image">
                                <img src="<?php echo esc_url($product['thumbnail']); ?>" alt="">
                            </div>
                            <div class="product-details">
                                <h3><?php echo esc_html($product['title']); ?></h3>
                                <p class="price"><?php echo wc_price($product['price']); ?></p>
                                <button type="button" class="button import-product" <?php echo $product['imported'] ? 'disabled' : ''; ?>>
                                    <?php echo $product['imported'] 
                                        ? esc_html__('Imported', 'wp-woocommerce-printify-sync')
                                        : esc_html__('Import', 'wp-woocommerce-printify-sync'); 
                                    ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>