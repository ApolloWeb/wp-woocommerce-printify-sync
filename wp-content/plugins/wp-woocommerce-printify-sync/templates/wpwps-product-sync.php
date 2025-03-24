<?php
/**
 * Product sync template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get sync stats
$action_scheduler = $this->container->get('action_scheduler');
$import_stats = $action_scheduler->getTaskStats('wpwps_import_product');
$sync_stats = $action_scheduler->getTaskStats('wpwps_sync_product');

// Get recent products
$recent_products = $this->getRecentProducts();

// Get API and shop information
$api_service = $this->container->get('api');
$shop_id = $api_service->getShopId();
$shop_name = get_option('wpwps_shop_name', '');
$last_sync = get_option('wpwps_last_full_sync', '');
?>

<div class="wrap wpwps-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Product Sync', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <hr class="wp-header-end">
    
    <?php if (!$api_service->hasCredentials() || empty($shop_id)) : ?>
        <div class="notice notice-warning">
            <p>
                <?php echo sprintf(
                    /* translators: %s: settings page URL */
                    esc_html__('Printify API is not configured. Please set up your API key and select a shop in the %s.', 'wp-woocommerce-printify-sync'),
                    '<a href="' . esc_url(admin_url('admin.php?page=wpwps-settings')) . '">' . esc_html__('settings', 'wp-woocommerce-printify-sync') . '</a>'
                ); ?>
            </p>
        </div>
    <?php else : ?>
        
        <!-- Sync Status Card -->
        <div class="wpwps-row">
            <div class="wpwps-col-12">
                <div class="wpwps-card">
                    <div class="wpwps-card-header">
                        <h2><?php echo esc_html__('Sync Status', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="wpwps-card-body">
                        <div class="wpwps-connection-info">
                            <p>
                                <strong><?php echo esc_html__('Connected Shop:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <?php echo esc_html($shop_name); ?> (<?php echo esc_html($shop_id); ?>)
                            </p>
                            <p>
                                <strong><?php echo esc_html__('Last Full Sync:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <?php echo !empty($last_sync) ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync))) : esc_html__('Never', 'wp-woocommerce-printify-sync'); ?>
                            </p>
                        </div>
                        
                        <div class="wpwps-sync-actions">
                            <button class="button button-primary" id="wpwps-import-products" data-nonce="<?php echo esc_attr(wp_create_nonce('wpwps-admin-ajax-nonce')); ?>">
                                <i class="dashicons dashicons-download"></i> <?php echo esc_html__('Import New Products', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                            
                            <button class="button button-secondary" id="wpwps-sync-all-products" data-nonce="<?php echo esc_attr(wp_create_nonce('wpwps-admin-ajax-nonce')); ?>">
                                <i class="dashicons dashicons-update"></i> <?php echo esc_html__('Sync All Products', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Queue Stats and Recent Products -->
        <div class="wpwps-row">
            <!-- Queue Stats -->
            <div class="wpwps-col-6">
                <div class="wpwps-card">
                    <div class="wpwps-card-header">
                        <h2><?php echo esc_html__('Sync Queue', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="wpwps-card-body">
                        <div class="wpwps-queue-stats">
                            <div class="wpwps-queue-stat">
                                <h3><?php echo esc_html__('Import Queue', 'wp-woocommerce-printify-sync'); ?></h3>
                                <div class="wpwps-progress">
                                    <?php
                                    $total_import = $import_stats['total'];
                                    $pending_import = $import_stats['pending'];
                                    $completed_import = $import_stats['completed'];
                                    $failed_import = $import_stats['failed'];
                                    $running_import = $import_stats['running'];
                                    $import_progress = $total_import > 0 ? round(($total_import - $pending_import) / $total_import * 100) : 0;
                                    ?>
                                    <div class="wpwps-progress-bar" style="width: <?php echo esc_attr($import_progress); ?>%">
                                        <?php echo esc_html($import_progress); ?>%
                                    </div>
                                </div>
                                <div class="wpwps-queue-details">
                                    <div class="wpwps-queue-detail">
                                        <span class="wpwps-detail-label"><?php echo esc_html__('Pending:', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="wpwps-detail-value"><?php echo esc_html($pending_import); ?></span>
                                    </div>
                                    <div class="wpwps-queue-detail">
                                        <span class="wpwps-detail-label"><?php echo esc_html__('Running:', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="wpwps-detail-value"><?php echo esc_html($running_import); ?></span>
                                    </div>
                                    <div class="wpwps-queue-detail">
                                        <span class="wpwps-detail-label"><?php echo esc_html__('Completed:', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="wpwps-detail-value"><?php echo esc_html($completed_import); ?></span>
                                    </div>
                                    <div class="wpwps-queue-detail">
                                        <span class="wpwps-detail-label"><?php echo esc_html__('Failed:', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="wpwps-detail-value"><?php echo esc_html($failed_import); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="wpwps-queue-stat">
                                <h3><?php echo esc_html__('Update Queue', 'wp-woocommerce-printify-sync'); ?></h3>
                                <div class="wpwps-progress">
                                    <?php
                                    $total_sync = $sync_stats['total'];
                                    $pending_sync = $sync_stats['pending'];
                                    $completed_sync = $sync_stats['completed'];
                                    $failed_sync = $sync_stats['failed'];
                                    $running_sync = $sync_stats['running'];
                                    $sync_progress = $total_sync > 0 ? round(($total_sync - $pending_sync) / $total_sync * 100) : 0;
                                    ?>
                                    <div class="wpwps-progress-bar" style="width: <?php echo esc_attr($sync_progress); ?>%">
                                        <?php echo esc_html($sync_progress); ?>%
                                    </div>
                                </div>
                                <div class="wpwps-queue-details">
                                    <div class="wpwps-queue-detail">
                                        <span class="wpwps-detail-label"><?php echo esc_html__('Pending:', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="wpwps-detail-value"><?php echo esc_html($pending_sync); ?></span>
                                    </div>
                                    <div class="wpwps-queue-detail">
                                        <span class="wpwps-detail-label"><?php echo esc_html__('Running:', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="wpwps-detail-value"><?php echo esc_html($running_sync); ?></span>
                                    </div>
                                    <div class="wpwps-queue-detail">
                                        <span class="wpwps-detail-label"><?php echo esc_html__('Completed:', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="wpwps-detail-value"><?php echo esc_html($completed_sync); ?></span>
                                    </div>
                                    <div class="wpwps-queue-detail">
                                        <span class="wpwps-detail-label"><?php echo esc_html__('Failed:', 'wp-woocommerce-printify-sync'); ?></span>
                                        <span class="wpwps-detail-value"><?php echo esc_html($failed_sync); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Products -->
            <div class="wpwps-col-6">
                <div class="wpwps-card">
                    <div class="wpwps-card-header">
                        <h2><?php echo esc_html__('Recently Synced Products', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="wpwps-card-body">
                        <?php if (empty($recent_products)) : ?>
                            <p class="wpwps-no-data">
                                <?php echo esc_html__('No recently synced products.', 'wp-woocommerce-printify-sync'); ?>
                            </p>
                        <?php else : ?>
                            <table class="wpwps-table">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html__('Product', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php echo esc_html__('Last Synced', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php echo esc_html__('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_products as $product) : ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($product['thumbnail'])) : ?>
                                                    <img src="<?php echo esc_url($product['thumbnail']); ?>" alt="<?php echo esc_attr($product['title']); ?>" class="wpwps-product-thumbnail" />
                                                <?php endif; ?>
                                                <a href="<?php echo esc_url($product['edit_url']); ?>" target="_blank">
                                                    <?php echo esc_html($product['title']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="wpwps-status wpwps-status-<?php echo esc_attr($product['status']); ?>">
                                                    <?php echo esc_html($product['status_label']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo esc_html($product['last_synced']); ?>
                                            </td>
                                            <td>
                                                <div class="wpwps-action-buttons">
                                                    <a href="<?php echo esc_url($product['edit_url']); ?>" class="button button-small" title="<?php echo esc_attr__('Edit', 'wp-woocommerce-printify-sync'); ?>">
                                                        <span class="dashicons dashicons-edit"></span>
                                                    </a>
                                                    <a href="<?php echo esc_url($product['view_url']); ?>" class="button button-small" title="<?php echo esc_attr__('View', 'wp-woocommerce-printify-sync'); ?>" target="_blank">
                                                        <span class="dashicons dashicons-visibility"></span>
                                                    </a>
                                                    <button class="button button-small wpwps-sync-product" 
                                                            data-product-id="<?php echo esc_attr($product['id']); ?>" 
                                                            data-printify-id="<?php echo esc_attr($product['printify_id']); ?>"
                                                            data-nonce="<?php echo esc_attr(wp_create_nonce('wpwps-admin-ajax-nonce')); ?>"
                                                            title="<?php echo esc_attr__('Sync', 'wp-woocommerce-printify-sync'); ?>">
                                                        <span class="dashicons dashicons-update"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <div class="wpwps-view-all">
                                <a href="<?php echo esc_url(admin_url('edit.php?post_type=product&wpwps_filter=printify')); ?>" class="button">
                                    <?php echo esc_html__('View All Printify Products', 'wp-woocommerce-printify-sync'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Products Data Table -->
        <div class="wpwps-row">
            <div class="wpwps-col-12">
                <div class="wpwps-card">
                    <div class="wpwps-card-header">
                        <h2><?php echo esc_html__('Printify Products', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="wpwps-card-body">
                        <div class="wpwps-table-container">
                            <div class="wpwps-table-tools">
                                <div class="wpwps-search">
                                    <input type="text" id="wpwps-product-search" placeholder="<?php echo esc_attr__('Search products...', 'wp-woocommerce-printify-sync'); ?>" />
                                </div>
                                <div class="wpwps-table-pagination">
                                    <span class="wpwps-pagination-info"></span>
                                    <div class="wpwps-pagination-controls">
                                        <button class="button button-small wpwps-pagination-prev" disabled>
                                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                                        </button>
                                        <span class="wpwps-pagination-pages"></span>
                                        <button class="button button-small wpwps-pagination-next" disabled>
                                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <table class="wpwps-table wpwps-products-table" id="wpwps-products-table">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html__('Product', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php echo esc_html__('SKU', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php echo esc_html__('Price', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php echo esc_html__('Printify ID', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php echo esc_html__('Last Synced', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php echo esc_html__('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="wpwps-loading-row">
                                        <td colspan="6" class="wpwps-loading">
                                            <span class="spinner is-active"></span> <?php echo esc_html__('Loading products...', 'wp-woocommerce-printify-sync'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<!-- Templates for JavaScript rendering -->
<script type="text/template" id="wpwps-product-row-template">
    <tr data-product-id="{{ id }}">
        <td>
            <# if (thumbnail) { #>
                <img src="{{ thumbnail }}" alt="{{ title }}" class="wpwps-product-thumbnail" />
            <# } #>
            <a href="{{ edit_url }}" target="_blank">{{ title }}</a>
        </td>
        <td>{{ sku }}</td>
        <td>{{ formatted_price }}</td>
        <td>{{ printify_id }}</td>
        <td>{{ last_synced }}</td>
        <td>
            <div class="wpwps-action-buttons">
                <a href="{{ edit_url }}" class="button button-small" title="<?php echo esc_attr__('Edit', 'wp-woocommerce-printify-sync'); ?>">
                    <span class="dashicons dashicons-edit"></span>
                </a>
                <a href="{{ view_url }}" class="button button-small" title="<?php echo esc_attr__('View', 'wp-woocommerce-printify-sync'); ?>" target="_blank">
                    <span class="dashicons dashicons-visibility"></span>
                </a>
                <button class="button button-small wpwps-sync-product" 
                        data-product-id="{{ id }}" 
                        data-printify-id="{{ printify_id }}"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('wpwps-admin-ajax-nonce')); ?>"
                        title="<?php echo esc_attr__('Sync', 'wp-woocommerce-printify-sync'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
        </td>
    </tr>
</script>

<script type="text/template" id="wpwps-no-products-template">
    <tr class="wpwps-no-products-row">
        <td colspan="6" class="wpwps-no-products">
            <?php echo esc_html__('No Printify products found.', 'wp-woocommerce-printify-sync'); ?>
        </td>
    </tr>
</script>