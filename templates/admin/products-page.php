<?php
/**
 * Admin Product Management Page Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiHelper;

// Get shop ID
$shop_id = get_option('wpwprintifysync_shop_id', 0);

// Handle import request
$import_message = '';
if (isset($_POST['wpwprintifysync_import']) && isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
    check_admin_referer('wpwprintifysync_import_products', 'wpwprintifysync_nonce');
    
    $product_ids = $_POST['product_ids'];
    $batch_size = get_option('wpwprintifysync_batch_size', 10);
    
    // Split into batches
    $batches = array_chunk($product_ids, $batch_size);
    $total_batches = count($batches);
    
    // Schedule import jobs
    foreach ($batches as $batch_index => $batch) {
        wp_schedule_single_event(
            time() + ($batch_index * 30),
            'wpwprintifysync_import_products_batch',
            [
                'shop_id' => $shop_id,
                'product_ids' => $batch,
                'batch_number' => $batch_index + 1,
                'total_batches' => $total_batches,
                'user' => 'ApolloWeb',
                'timestamp' => '2025-03-05 19:02:30'
            ]
        );
    }
    
    $import_message = sprintf(
        __('Import scheduled for %d products in %d batches.', 'wp-woocommerce-printify-sync'),
        count($product_ids),
        $total_batches
    );
}

// Get current page
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Handle shop selection
if (isset($_POST['wpwprintifysync_shop_select']) && isset($_POST['shop_id'])) {
    check_admin_referer('wpwprintifysync_select_shop', 'wpwprintifysync_shop_nonce');
    $shop_id = intval($_POST['shop_id']);
    update_option('wpwprintifysync_shop_id', $shop_id);
}
?>

<div class="wrap wpwprintifysync-products-page">
    <h1 class="wp-heading-inline"><?php _e('Printify Products', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <?php if ($import_message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($import_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="wpwprintifysync-shop-selector">
        <?php
        // Get available shops
        $shops = [];
        $shops_response = ApiHelper::getInstance()->sendPrintifyRequest('shops.json');
        
        if ($shops_response['success'] && !empty($shops_response['body'])) {
            $shops = $shops_response['body'];
        }
        
        if (!empty($shops)):
        ?>
            <form method="post" action="">
                <?php wp_nonce_field('wpwprintifysync_select_shop', 'wpwprintifysync_shop_nonce'); ?>
                <label for="shop_id"><?php _e('Select Shop:', 'wp-woocommerce-printify-sync'); ?></label>
                <select name="shop_id" id="shop_id">
                    <?php foreach ($shops as $shop): ?>
                        <option value="<?php echo esc_attr($shop['id']); ?>" <?php selected($shop_id, $shop['id']); ?>>
                            <?php echo esc_html($shop['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="wpwprintifysync_shop_select" class="button" value="<?php esc_attr_e('Select Shop', 'wp-woocommerce-printify-sync'); ?>">
            </form>
        <?php endif; ?>
    </div>
    
    <form method="post" action="" id="wpwprintifysync-products-form">
        <?php wp_nonce_field('wpwprintifysync_import_products', 'wpwprintifysync_nonce'); ?>
        
        <div id="wpwprintifysync-products-container">
            <div class="wpwprintifysync-loader">
                <span class="spinner is-active"></span>
                <?php _e('Loading products...', 'wp-woocommerce-printify-sync'); ?>
            </div>
            
            <div class="wpwprintifysync-products-list" style="display: none;">
                <div class="wpwprintifysync-products-header">
                    <div class="wpwprintifysync-bulk-actions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'wp-woocommerce-printify-sync'); ?></label>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Bulk actions', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="import"><?php _e('Import selected', 'wp-woocommerce-printify-sync'); ?></option>
                        </select>
                        <input type="submit" name="wpwprintifysync_import" class="button action" value="<?php esc_attr_e('Apply', 'wp-woocommerce-printify-sync'); ?>">
                    </div>
                    
                    <div class="wpwprintifysync-pagination">
                        <span class="pagination-links">
                            <a class="first-page button" href="#"><span class="screen-reader-text"><?php _e('First page', 'wp-woocommerce-printify-sync'); ?></span><span aria-hidden="true">&laquo;</span></a>
                            <a class="prev-page button" href="#"><span class="screen-reader-text"><?php _e('Previous page', 'wp-woocommerce-printify-sync'); ?></span><span aria-hidden="true">&lsaquo;</span></a>
                            <span class="paging-input">
                                <label for="current-page-selector" class="screen-reader-text"><?php _e('Current page', 'wp-woocommerce-printify-sync'); ?></label>
                                <span class="current-page">1</span>
                                <span class="total-pages">/ <span class="total">1</span></span>
                            </span>
                            <a class="next-page button" href="#"><span class="screen-reader-text"><?php _e('Next page', 'wp-woocommerce-printify-sync'); ?></span><span aria-hidden="true">&rsaquo;</span></a>
                            <a class="last-page button" href="#"><span class="screen-reader-text"><?php _e('Last page', 'wp-woocommerce-printify-sync'); ?></span><span aria-hidden="true">&raquo;</span></a>
                        </span>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all">
                            </td>
                            <th class="manage-column column-image"><?php _e('Image', 'wp-woocommerce-printify-sync'); ?></th>
                            <th class="manage-column column-title"><?php _e('Title', 'wp-woocommerce-printify-sync'); ?></th>
                            <th class="manage-column column-price"><?php _e('Price', 'wp-woocommerce-printify-sync'); ?></th>
                            <th class="manage-column column-status"><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                            <th class="manage-column column-actions"><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="wpwprintifysync-products-tbody">
                        <!-- Products will be loaded here via AJAX -->
                    </tbody>
                </table>
                
                <div class="wpwprintifysync-bulk-actions bottom">
                    <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action', 'wp-woocommerce-printify-sync'); ?></label>
                    <select name="action2" id="bulk-action-selector-bottom">
                        <option value="-1"><?php _e('Bulk actions', 'wp-woocommerce-printify-sync'); ?></option>
                        <option value="import"><?php _e('Import selected', 'wp-woocommerce-printify-sync'); ?></option>
                    </select>
                    <input type="submit" name="wpwprintifysync_import" class="button action" value="<?php esc_attr_e('Apply', 'wp-woocommerce-printify-sync'); ?>">
                </div>
            </div>
        </div>
    </form>
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Function to load products
            function loadProducts(page) {
                $('.wpwprintifysync-loader').show();
                $('.wpwprintifysync-products-list').hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpwprintifysync_get_printify_products',
                        nonce: '<?php echo wp_create_nonce('wpwprintifysync-admin'); ?>',
                        shop_id: <?php echo intval($shop_id); ?>,
                        page: page,
                        limit: 10
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update products
                            renderProducts(response.data.products);
                            
                            // Update pagination
                            updatePagination(page, response.data.total_pages);
                            
                            $('.wpwprintifysync-products-list').show();
                        } else {
                            alert(response.data.message || 'Error loading products.');
                        }
                        
                        $('.wpwprintifysync-loader').hide();
                    },
                    error: function() {
                        $('.wpwprintifysync-loader').hide();
                        alert('Failed to load products. Please try again.');
                    }
                });
            }
            
            // Function to render products
            function renderProducts(products) {
                var tbody = $('#wpwprintifysync-products-tbody');
                tbody.empty();
                
                $.each(products, function(index, product) {
                    var row = $('<tr></tr>');
                    
                    // Checkbox
                    row.append(
                        '<td class="check-column"><input type="checkbox" name="product_ids[]" value="' + 
                        product.id + '"></td>'
                    );
                    
                    // Image
                    var image = product.images && product.images.length > 0 ? 
                        '<img src="' + product.images[0].src + '" width="50" height="50">' : 
                        '<span class="no-image">No Image</span>';
                    row.append('<td class="column-image">' + image + '</td>');
                    
                    // Title
                    row.append('<td class="column-title">' + product.title + '</td>');
                    
                    // Price - use retail_price
                    var price = '';
                    if (product.variants && product.variants.length > 0) {
                        price = product.variants[0].retail_price || '';
                    }
                    row.append('<td class="column-price">' + price + '</td>');
                    
                    // Status
                    row.append('<td class="column-status">' + (product.status || '') + '</td>');
                    
                    // Actions
                    var actions = '<div class="row-actions">';
                    actions += '<span class="import"><a href="#" class="wpwprintifysync-import-single" data-id="' + product.id + '">' + 
                        '<?php _e('Import', 'wp-woocommerce-printify-sync'); ?></a></span>';
                    actions += '</div>';
                    row.append('<td class="column-actions">' + actions + '</td>');
                    
                    tbody.append(row);
                });
                
                // Re-bind events after rendering
                bindEvents();
            }
            
            // Function to update pagination
            function updatePagination(currentPage, totalPages) {
                $('.wpwprintifysync-pagination .current-page').text(currentPage);
                $('.wpwprintifysync-pagination .total').text(totalPages);
                
                // First page button
                if (currentPage <= 1) {
                    $('.wpwprintifysync-pagination .first-page, .wpwprintifysync-pagination .prev-page')
                        .addClass('disabled')
                        .attr('aria-disabled', 'true');
                } else {
                    $('.wpwprintifysync-pagination .first-page, .wpwprintifysync-pagination .prev-page')
                        .removeClass('disabled')
                        .attr('aria-disabled', 'false');
                }
                
                // Last page button
                if (currentPage >= totalPages) {
                    $('.wpwprintifysync-pagination .last-page, .wpwprintifysync-pagination .next-page')
                        .addClass('disabled')
                        .attr('aria-disabled', 'true');
                } else {
                    $('.wpwprintifysync-pagination .last-page, .wpwprintifysync-pagination .next-page')
                        .removeClass('disabled')
                        .attr('aria-disabled', 'false');
                }
                
                // Store current page and total pages
                $('.wpwprintifysync-pagination').data('current-page', currentPage);
                $('.wpwprintifysync-pagination').data('total-pages', totalPages);
            }
            
            // Function to bind events
            function bindEvents() {
                // Select all checkbox
                $('#cb-select-all').off('change').on('change', function() {
                    $('input[name="product_ids[]"]').prop('checked', $(this).prop('checked'));
                });
                
                // Import single product
                $('.wpwprintifysync-import-single').off('click').on('click', function(e) {
                    e.preventDefault();
                    var productId = $(this).data('id');
                    
                    $('input[name="product_ids[]"]').prop('checked', false);
                    $('input[name="product_ids[]"][value="' + productId + '"]').prop('checked', true);
                    
                    $('#wpwprintifysync-products-form').submit();
                });
            }
            
            // Pagination events
            $('.wpwprintifysync-pagination .first-page').on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;
                
                loadProducts(1);
            });
            
            $('.wpwprintifysync-pagination .prev-page').on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;
                
                var currentPage = parseInt($('.wpwprintifysync-pagination').data('current-page') || 1);
                loadProducts(Math.max(1, currentPage - 1));
            });
            
            $('.wpwprintifysync-pagination .next-page').on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;
                
                var currentPage = parseInt($('.wpwprintifysync-pagination').data('current-page') || 1);
                var totalPages = parseInt($('.wpwprintifysync-pagination').data('total-pages') || 1);
                loadProducts(Math.min(totalPages, currentPage + 1));
            });
            
            $('.wpwprintifysync-pagination .last-page').on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;
                
                var totalPages = parseInt($('.wpwprintifysync-pagination').data('total-pages') || 1);
                loadProducts(totalPages);
            });
            
            // Load initial products
            loadProducts(1);
        });
    </script>