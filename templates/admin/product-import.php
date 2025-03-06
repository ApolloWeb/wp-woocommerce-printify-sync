<?php
/**
 * Admin product import page template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

defined( 'ABSPATH' ) || exit;

// Current time and user info
$current_time = '2025-03-06 01:34:35';
$current_user = 'ApolloWeb';
?>

<div class="wrap printify-sync-products">
    <h1><?php esc_html_e( 'Printify Product Import', 'wp-woocommerce-printify-sync' ); ?></h1>
    
    <div class="printify-sync-meta">
        <p>
            <span class="dashicons dashicons-clock"></span> 
            <?php esc_html_e( 'Current Time (UTC):', 'wp-woocommerce-printify-sync' ); ?> 
            <strong><?php echo esc_html( $current_time ); ?></strong>
        </p>
        <p>
            <span class="dashicons dashicons-admin-users"></span> 
            <?php esc_html_e( 'Logged in as:', 'wp-woocommerce-printify-sync' ); ?> 
            <strong><?php echo esc_html( $current_user ); ?></strong>
        </p>
    </div>
    
    <?php if ( ! isset( $connected_shop ) || empty( $connected_shop ) ) : ?>
        <div class="notice notice-error">
            <p>
                <?php esc_html_e( 'No Printify shop connected. Please connect a shop first.', 'wp-woocommerce-printify-sync' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-shops' ) ); ?>" class="button button-small">
                    <?php esc_html_e( 'Connect Shop', 'wp-woocommerce-printify-sync' ); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <div class="printify-sync-product-stats">
            <div class="printify-sync-stat-card">
                <div class="printify-sync-stat-value"><?php echo isset( $product_stats['total'] ) ? esc_html( $product_stats['total'] ) : '0'; ?></div>
                <div class="printify-sync-stat-label"><?php esc_html_e( 'Total Products', 'wp-woocommerce-printify-sync' ); ?></div>
            </div>
            <div class="printify-sync-stat-card">
                <div class="printify-sync-stat-value"><?php echo isset( $product_stats['published'] ) ? esc_html( $product_stats['published'] ) : '0'; ?></div>
                <div class="printify-sync-stat-label"><?php esc_html_e( 'Published', 'wp-woocommerce-printify-sync' ); ?></div>
            </div>
            <div class="printify-sync-stat-card">
                <div class="printify-sync-stat-value"><?php echo isset( $product_stats['unpublished'] ) ? esc_html( $product_stats['unpublished'] ) : '0'; ?></div>
                <div class="printify-sync-stat-label"><?php esc_html_e( 'Unpublished', 'wp-woocommerce-printify-sync' ); ?></div>
            </div>
            <div class="printify-sync-stat-card">
                <div class="printify-sync-stat-value"><?php echo isset( $product_stats['imported'] ) ? esc_html( $product_stats['imported'] ) : '0'; ?></div>
                <div class="printify-sync-stat-label"><?php esc_html_e( 'Imported to WC', 'wp-woocommerce-printify-sync' ); ?></div>
            </div>
            <div class="printify-sync-stat-card">
                <div class="printify-sync-stat-value"><?php echo isset( $product_stats['pending_import'] ) ? esc_html( $product_stats['pending_import'] ) : '0'; ?></div>
                <div class="printify-sync-stat-label"><?php esc_html_e( 'Pending Import', 'wp-woocommerce-printify-sync' ); ?></div>
            </div>
        </div>
        
        <div class="printify-sync-actions-row">
            <button id="get-products" class="button button-primary">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e( 'Get Products', 'wp-woocommerce-printify-sync' ); ?>
            </button>
            
            <button id="import-products" class="button button-primary" <?php echo isset( $product_stats['pending_import'] ) && $product_stats['pending_import'] <= 0 ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Import to WooCommerce', 'wp-woocommerce-printify-sync' ); ?>
            </button>
            
            <button id="sync-existing" class="button">
                <span class="dashicons dashicons-image-rotate"></span>
                <?php esc_html_e( 'Sync Existing Products', 'wp-woocommerce-printify-sync' ); ?>
            </button>
        </div>
        
        <div class="printify-sync-import-progress" style="display: none;">
            <div class="printify-sync-progress-bar-container">
                <div class="printify-sync-progress-bar"></div>
            </div>
            <div class="printify-sync-progress-text">
                <span class="printify-sync-progress-percentage">0%</span>
                <span class="printify-sync-progress-details"><?php esc_html_e( 'Processing...', 'wp-woocommerce-printify-sync' ); ?></span>
            </div>
        </div>
        
        <div class="printify-sync-section">
            <h2><?php esc_html_e( 'Product List', 'wp-woocommerce-printify-sync' ); ?></h2>
            
            <div class="printify-sync-filter-row">
                <div class="printify-sync-search">
                    <input type="text" id="product-search" placeholder="<?php esc_attr_e( 'Search products...', 'wp-woocommerce-printify-sync' ); ?>">
                    <button class="button" id="search-button">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
                
                <div class="printify-sync-filters">
                    <select id="status-filter">
                        <option value=""><?php esc_html_e( 'All Statuses', 'wp-woocommerce-printify-sync' ); ?></option>
                        <option value="published"><?php esc_html_e( 'Published', 'wp-woocommerce-printify-sync' ); ?></option>
                        <option value="unpublished"><?php esc_html_e( 'Unpublished', 'wp-woocommerce-printify-sync' ); ?></option>
                    </select>
                    
                    <select id="import-filter">
                        <option value=""><?php esc_html_e( 'All Import Statuses', 'wp-woocommerce-printify-sync' ); ?></option>
                        <option value="imported"><?php esc_html_e( 'Imported', 'wp-woocommerce-printify-sync' ); ?></option>
                        <option value="not-imported"><?php esc_html_e( 'Not Imported', 'wp-woocommerce-printify-sync' ); ?></option>
                    </select>
                    
                    <button id="apply-filters" class="button">
                        <?php esc_html_e( 'Apply', 'wp-woocommerce-printify-sync' ); ?>
                    </button>
                </div>
            </div>
            
            <div id="product-list-container">
                <?php if ( isset( $products ) && ! empty( $products ) ) : ?>
                    <table class="widefat printify-sync-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-products"></th>
                                <th><?php esc_html_e( 'Thumbnail', 'wp-woocommerce-printify-sync' ); ?></th>
                                <th><?php esc_html_e( 'Product Title', 'wp-woocommerce-printify-sync' ); ?></th>
                                <th><?php esc_html_e( 'Printify ID', 'wp-woocommerce-printify-sync' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'wp-woocommerce-printify-sync' ); ?></th>
                                <th><?php esc_html_e( 'Price', 'wp-woocommerce-printify-sync' ); ?></th>
                                <th><?php esc_html_e( 'Import Status', 'wp-woocommerce-printify-sync' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'wp-woocommerce-printify-sync' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $products as $product ) : 
                                $is_imported = isset( $product['wc_product_id'] ) && ! empty( $product['wc_product_id'] );
                            ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="product-checkbox" value="<?php echo esc_attr( $product['id'] ); ?>">
                                    </td>
                                    <td>
                                        <?php if ( isset( $product['thumbnail'] ) && ! empty( $product['thumbnail'] ) ) : ?>
                                            <img src="<?php echo esc_url( $product['thumbnail'] ); ?>" width="50" height="50" alt="<?php echo esc_attr( $product['title'] ); ?>">
                                        <?php else : ?>
                                            <div class="no-image"><?php esc_html_e( 'No image', 'wp-woocommerce-printify-sync' ); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html( $product['title'] ); ?></td>
                                    <td><?php echo esc_html( $product['id'] ); ?></td>
                                    <td>
                                        <span class="product-status status-<?php echo esc_attr( $product['status'] ); ?>">
                                            <?php echo esc_html( ucfirst( $product['status'] ) ); ?>
                                        </span>
                                    </td>
                                    <td><?php echo isset( $product['price'] ) ? esc_html( wc_price( $product['price'] ) ) : '-'; ?></td>
                                    <td>
                                        <?php if ( $is_imported ) : ?>
                                            <span class="import-status imported">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                <?php esc_html_e( 'Imported', 'wp-woocommerce-printify-sync' ); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="import-status not-imported">
                                                <span class="dashicons dashicons-no-alt"></span>
                                                <?php esc_html_e( 'Not Imported', 'wp-woocommerce-printify-sync' ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $is_imported ) : ?>
                                            <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $product['wc_product_id'] . '&action=edit' ) ); ?>" class="button button-small" target="_blank">
                                                <?php esc_html_e( 'Edit in WC', 'wp-woocommerce-printify-sync' ); ?>
                                            </a>
                                            <button class="button button-small sync-product" data-product-id="<?php echo esc_attr( $product['id'] ); ?>">
                                                <?php esc_html_e( 'Sync', 'wp-woocommerce-printify-sync' ); ?>
                                            </button>
                                        <?php else : ?>
                                            <button class="button button-small import-product" data-product-id="<?php echo esc_attr( $product['id'] ); ?>">
                                                <?php esc_html_e( 'Import', 'wp-woocommerce-printify-sync' ); ?>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url( 'https://printify.com/app/editor/' . $product['id'] ); ?>" class="button button-small" target="_blank">
                                            <?php esc_html_e( 'View in Printify', 'wp-woocommerce-printify-sync' ); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ( isset( $pagination ) && $pagination['total_pages'] > 1 ) : ?>
                        <div class="printify-sync-pagination">
                            <?php for ( $i = 1; $i <= $pagination['total_pages']; $i++ ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'page', $i ) ); ?>" class="page-number <?php echo $pagination['current_page'] == $i ? 'current' : ''; ?>">
                                    <?php echo esc_html( $i ); ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else : ?>
                    <div class="printify-sync-notice notice-info">
                        <p><?php esc_html_e( 'No products found. Click "Get Products" to retrieve products from Printify.', 'wp-woocommerce-printify-sync' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="printify-sync-section">
            <h2><?php esc_html_e( 'Bulk Actions', 'wp-woocommerce-printify-sync' ); ?></h2>
            
            <div class="printify-sync-bulk-actions">
                <select id="bulk-action-select">
                    <option value=""><?php esc_html_e( 'Select Action', 'wp-woocommerce-printify-sync' ); ?></option>
                    <option value="import"><?php esc_html_e( 'Import Selected', 'wp-woocommerce-printify-sync' ); ?></option>
                    <option value="sync"><?php esc_html_e( 'Sync Selected', 'wp-woocommerce-printify-sync' ); ?></option>
                </select>
                
                <button id="apply-bulk-action" class="button">
                    <?php esc_html_e( 'Apply', 'wp-woocommerce-printify-sync' ); ?>
                </button>
                
                <span class="selected-count">
                    <?php esc_html_e( '0 items selected', 'wp-woocommerce-printify-sync' ); ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Get products button
        $('#get-products').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true);
            $button.html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Retrieving...', 'wp-woocommerce-printify-sync' ); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_sync_products',
                    action_type: 'get_products',
                    nonce: PrintifySyncAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php esc_html_e( 'Error retrieving products.', 'wp-woocommerce-printify-sync' ); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e( 'Error connecting to server.', 'wp-woocommerce-printify-sync' ); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.html('<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Get Products', 'wp-woocommerce-printify-sync' ); ?>');
                }
            });
        });
        
        // Import products button
        $('#import-products').on('click', function() {
            if (!confirm('<?php esc_html_e( 'This will import all pending products to WooCommerce. Continue?', 'wp-woocommerce-printify-sync' ); ?>')) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true);
            $button.html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Starting import...', 'wp-woocommerce-printify-sync' ); ?>');
            
            // Show progress bar
            $('.printify-sync-import-progress').show();
            
            importProductBatch(1);
        });
        
        // Function to import products in batches
        function importProductBatch(batch) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_sync_products',
                    action_type: 'import_batch',
                    batch: batch,
                    nonce: PrintifySyncAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update progress bar
                        var progress = response.data.progress;
                        $('.printify-sync-progress-bar').css('width', progress + '%');
                        $('.printify-sync-progress-