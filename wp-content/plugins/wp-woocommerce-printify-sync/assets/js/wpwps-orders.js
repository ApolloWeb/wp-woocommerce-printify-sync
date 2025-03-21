/**
 * Orders page JavaScript.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    // Current page and filters state
    let currentPage = 1;
    let currentStatus = '';
    let currentSearch = '';
    let orderDetailsId = '';
    
    // Load orders on page load
    loadOrders();
    
    // Trigger orders sync
    $('#wpwps-sync-orders').on('click', function() {
        const button = $(this);
        const statusContainer = $('#sync-status');
        
        // Disable button and show loading state
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> ' + wpwps_orders.syncing);
        statusContainer.html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> ' + wpwps_orders.sync_in_progress + '</div>');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_sync_all_orders',
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    statusContainer.html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + response.data.message + '</div>');
                    
                    // Reload orders after sync
                    setTimeout(function() {
                        loadOrders();
                    }, 2000);
                } else {
                    statusContainer.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + response.data.message + '</div>');
                }
            },
            error: function() {
                statusContainer.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + wpwps_orders.sync_error + '</div>');
            },
            complete: function() {
                // Reset button state
                button.prop('disabled', false);
                button.html('<i class="fas fa-sync-alt"></i> ' + wpwps_orders.sync_all_orders);
            }
        });
    });
    
    // Sync single order
    $('#sync-single-order').on('click', function() {
        if (!orderDetailsId) return;
        
        const button = $(this);
        const modalContent = $('.order-details-content');
        
        // Disable button and show loading state
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> ' + wpwps_orders.syncing);
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_sync_single_order',
                nonce: wpwps_data.nonce,
                order_id: orderDetailsId
            },
            success: function(response) {
                if (response.success) {
                    modalContent.prepend('<div class="alert alert-success mb-3"><i class="fas fa-check-circle"></i> ' + response.data.message + '</div>');
                    
                    // Reload orders after sync
                    loadOrders();
                } else {
                    modalContent.prepend('<div class="alert alert-danger mb-3"><i class="fas fa-times-circle"></i> ' + response.data.message + '</div>');
                }
            },
            error: function() {
                modalContent.prepend('<div class="alert alert-danger mb-3"><i class="fas fa-times-circle"></i> ' + wpwps_orders.sync_error + '</div>');
            },
            complete: function() {
                // Reset button state
                button.prop('disabled', false);
                button.html(wpwps_orders.sync_this_order);
            }
        });
    });
    
    // Handle search input
    $('#search-orders').on('keyup', function(e) {
        if (e.keyCode === 13) {
            currentSearch = $(this).val();
            currentPage = 1;
            loadOrders();
        }
    });
    
    // Handle status filter change
    $('#filter-status').on('change', function() {
        currentStatus = $(this).val();
        currentPage = 1;
        loadOrders();
    });
    
    // Show order details modal
    $(document).on('click', '.view-order', function(e) {
        e.preventDefault();
        
        const orderId = $(this).data('order-id');
        orderDetailsId = orderId;
        
        // Reset modal content
        $('.order-details-content').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">' + wpwps_orders.loading + '</span></div><p>' + wpwps_orders.loading_details + '</p></div>');
        
        // Show modal
        $('#order-details-modal').modal('show');
        
        // Load order details
        loadOrderDetails(orderId);
    });
    
    /**
     * Load orders via AJAX.
     */
    function loadOrders() {
        const tableBody = $('#printify-orders-table tbody');
        
        // Show loading indicator
        tableBody.html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">' + wpwps_orders.loading + '</span></div><p>' + wpwps_orders.loading_orders + '</p></td></tr>');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'GET',
            data: {
                action: 'wpwps_get_orders',
                nonce: wpwps_data.nonce,
                page: currentPage,
                status: currentStatus,
                search: currentSearch
            },
            success: function(response) {
                if (response.success) {
                    const orders = response.data.orders;
                    const pagination = response.data.pagination;
                    
                    // Update pagination info
                    $('.pagination-info').text(pagination.showing_text);
                    
                    // Build pagination HTML
                    let paginationHtml = '';
                    
                    // Previous button
                    paginationHtml += '<li class="page-item' + (pagination.current_page <= 1 ? ' disabled' : '') + '">';
                    paginationHtml += '<a class="page-link" href="#" data-page="' + (pagination.current_page - 1) + '" aria-label="Previous">';
                    paginationHtml += '<span aria-hidden="true">&laquo;</span></a></li>';
                    
                    // Page numbers
                    for (let i = 1; i <= pagination.total_pages; i++) {
                        if (
                            i === 1 || 
                            i === pagination.total_pages ||
                            (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)
                        ) {
                            paginationHtml += '<li class="page-item' + (i === pagination.current_page ? ' active' : '') + '">';
                            paginationHtml += '<a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
                        } else if (
                            i === 2 || 
                            i === pagination.total_pages - 1
                        ) {
                            paginationHtml += '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            // Skip ahead to avoid multiple ellipses
                            if (i === 2) i = pagination.current_page - 3;
                            if (i === pagination.total_pages - 1) i = pagination.total_pages - 1;
                        }
                    }
                    
                    // Next button
                    paginationHtml += '<li class="page-item' + (pagination.current_page >= pagination.total_pages ? ' disabled' : '') + '">';
                    paginationHtml += '<a class="page-link" href="#" data-page="' + (pagination.current_page + 1) + '" aria-label="Next">';
                    paginationHtml += '<span aria-hidden="true">&raquo;</span></a></li>';
                    
                    // Update pagination
                    $('.pagination').html(paginationHtml);
                    
                    // Build table rows
                    let tableHtml = '';
                    
                    if (orders.length === 0) {
                        tableHtml = '<tr><td colspan="8" class="text-center">' + wpwps_orders.no_orders + '</td></tr>';
                    } else {
                        for (let i = 0; i < orders.length; i++) {
                            const order = orders[i];
                            
                            tableHtml += '<tr>';
                            tableHtml += '<td><a href="#" class="view-order" data-order-id="' + order.id + '">#' + order.number + '</a>';
                            
                            if (order.printify_id) {
                                tableHtml += ' <small class="text-muted">(' + order.printify_id + ')</small>';
                            }
                            
                            tableHtml += '</td>';
                            tableHtml += '<td>' + order.date + '</td>';
                            tableHtml += '<td>' + getStatusBadge(order.status) + '</td>';
                            tableHtml += '<td>' + order.customer + '</td>';
                            tableHtml += '<td>' + order.products + '</td>';
                            tableHtml += '<td>' + order.total + '</td>';
                            tableHtml += '<td>' + (order.last_synced ? order.last_synced : '-') + '</td>';
                            tableHtml += '<td>';
                            tableHtml += '<div class="btn-group btn-group-sm" role="group">';
                            tableHtml += '<a href="#" class="btn btn-primary view-order" data-order-id="' + order.id + '"><i class="fas fa-eye"></i></a>';
                            tableHtml += '<a href="' + order.edit_url + '" class="btn btn-secondary" target="_blank"><i class="fas fa-edit"></i></a>';
                            tableHtml += '<button type="button" class="btn btn-info sync-order" data-order-id="' + order.id + '"><i class="fas fa-sync-alt"></i></button>';
                            tableHtml += '</div>';
                            tableHtml += '</td>';
                            tableHtml += '</tr>';
                        }
                    }
                    
                    // Update table
                    tableBody.html(tableHtml);
                } else {
                    tableBody.html('<tr><td colspan="8" class="text-center text-danger">' + response.data.message + '</td></tr>');
                }
            },
            error: function() {
                tableBody.html('<tr><td colspan="8" class="text-center text-danger">' + wpwps_orders.load_error + '</td></tr>');
            }
        });
    }
    
    /**
     * Load order details via AJAX.
     *
     * @param {string} orderId Order ID.
     */
    function loadOrderDetails(orderId) {
        const modalContent = $('.order-details-content');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'GET',
            data: {
                action: 'wpwps_get_order_details',
                nonce: wpwps_data.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    const order = response.data.order;
                    
                    // Update modal title
                    $('#order-details-title').text(wpwps_orders.order_details + ' #' + order.number);
                    
                    // Build order details HTML
                    let detailsHtml = '';
                    
                    // Order info
                    detailsHtml += '<div class="row mb-4">';
                    detailsHtml += '<div class="col-md-6">';
                    detailsHtml += '<h6>' + wpwps_orders.order_info + '</h6>';
                    detailsHtml += '<p><strong>' + wpwps_orders.order_number + ':</strong> #' + order.number + '</p>';
                    detailsHtml += '<p><strong>' + wpwps_orders.order_date + ':</strong> ' + order.date + '</p>';
                    detailsHtml += '<p><strong>' + wpwps_orders.order_status + ':</strong> ' + getStatusBadge(order.status) + '</p>';
                    detailsHtml += '<p><strong>' + wpwps_orders.printify_id + ':</strong> ' + (order.printify_id ? order.printify_id : '-') + '</p>';
                    detailsHtml += '<p><strong>' + wpwps_orders.last_synced + ':</strong> ' + (order.last_synced ? order.last_synced : '-') + '</p>';
                    detailsHtml += '</div>';
                    
                    // Customer info
                    detailsHtml += '<div class="col-md-6">';
                    detailsHtml += '<h6>' + wpwps_orders.customer_info + '</h6>';
                    detailsHtml += '<p><strong>' + wpwps_orders.customer_name + ':</strong> ' + order.customer.name + '</p>';
                    detailsHtml += '<p><strong>' + wpwps_orders.customer_email + ':</strong> ' + order.customer.email + '</p>';
                    detailsHtml += '<p><strong>' + wpwps_orders.customer_phone + ':</strong> ' + (order.customer.phone ? order.customer.phone : '-') + '</p>';
                    detailsHtml += '</div>';
                    detailsHtml += '</div>';
                    
                    // Line items
                    detailsHtml += '<div class="row mb-4">';
                    detailsHtml += '<div class="col-md-12">';
                    detailsHtml += '<h6>' + wpwps_orders.items + '</h6>';
                    detailsHtml += '<div class="table-responsive">';
                    detailsHtml += '<table class="table table-bordered table-sm">';
                    detailsHtml += '<thead><tr>';
                    detailsHtml += '<th>' + wpwps_orders.product + '</th>';
                    detailsHtml += '<th>' + wpwps_orders.sku + '</th>';
                    detailsHtml += '<th>' + wpwps_orders.quantity + '</th>';
                    detailsHtml += '<th>' + wpwps_orders.price + '</th>';
                    detailsHtml += '<th>' + wpwps_orders.total + '</th>';
                    detailsHtml += '</tr></thead>';
                    detailsHtml += '<tbody>';
                    
                    for (let i = 0; i < order.line_items.length; i++) {
                        const item = order.line_items[i];
                        
                        detailsHtml += '<tr>';
                        detailsHtml += '<td>' + item.name;
                        
                        if (item.meta.length > 0) {
                            detailsHtml += '<br><small>';
                            for (let j = 0; j < item.meta.length; j++) {
                                detailsHtml += '<strong>' + item.meta[j].key + ':</strong> ' + item.meta[j].value + '<br>';
                            }
                            detailsHtml += '</small>';
                        }
                        
                        detailsHtml += '</td>';
                        detailsHtml += '<td>' + (item.sku ? item.sku : '-') + '</td>';
                        detailsHtml += '<td>' + item.quantity + '</td>';
                        detailsHtml += '<td>' + item.price + '</td>';
                        detailsHtml += '<td>' + item.total + '</td>';
                        detailsHtml += '</tr>';
                    }
                    
                    detailsHtml += '</tbody>';
                    detailsHtml += '</table>';
                    detailsHtml += '</div>';
                    detailsHtml += '</div>';
                    detailsHtml += '</div>';
                    
                    // Addresses
                    detailsHtml += '<div class="row mb-4">';
                    
                    // Billing address
                    detailsHtml += '<div class="col-md-6">';
                    detailsHtml += '<h6>' + wpwps_orders.billing_address + '</h6>';
                    detailsHtml += '<address>';
                    detailsHtml += order.billing.name + '<br>';
                    if (order.billing.company) detailsHtml += order.billing.company + '<br>';
                    detailsHtml += order.billing.address_1 + '<br>';
                    if (order.billing.address_2) detailsHtml += order.billing.address_2 + '<br>';
                    detailsHtml += order.billing.city + ', ' + order.billing.state + ' ' + order.billing.postcode + '<br>';
                    detailsHtml += order.billing.country;
                    detailsHtml += '</address>';
                    detailsHtml += '</div>';
                    
                    // Shipping address
                    detailsHtml += '<div class="col-md-6">';
                    detailsHtml += '<h6>' + wpwps_orders.shipping_address + '</h6>';
                    detailsHtml += '<address>';
                    detailsHtml += order.shipping.name + '<br>';
                    if (order.shipping.company) detailsHtml += order.shipping.company + '<br>';
                    detailsHtml += order.shipping.address_1 + '<br>';
                    if (order.shipping.address_2) detailsHtml += order.shipping.address_2 + '<br>';
                    detailsHtml += order.shipping.city + ', ' + order.shipping.state + ' ' + order.shipping.postcode + '<br>';
                    detailsHtml += order.shipping.country;
                    detailsHtml += '</address>';
                    detailsHtml += '</div>';
                    detailsHtml += '</div>';
                    
                    // Tracking information
                    if (order.tracking_info && order.tracking_info.length > 0) {
                        detailsHtml += '<div class="row mb-4">';
                        detailsHtml += '<div class="col-md-12">';
                        detailsHtml += '<h6>' + wpwps_orders.tracking_info + '</h6>';
                        detailsHtml += '<div class="table-responsive">';
                        detailsHtml += '<table class="table table-bordered table-sm">';
                        detailsHtml += '<thead><tr>';
                        detailsHtml += '<th>' + wpwps_orders.carrier + '</th>';
                        detailsHtml += '<th>' + wpwps_orders.tracking_number + '</th>';
                        detailsHtml += '<th>' + wpwps_orders.shipped_date + '</th>';
                        detailsHtml += '</tr></thead>';
                        detailsHtml += '<tbody>';
                        
                        for (let i = 0; i < order.tracking_info.length; i++) {
                            const tracking = order.tracking_info[i];
                            
                            detailsHtml += '<tr>';
                            detailsHtml += '<td>' + tracking.carrier + '</td>';
                            
                            if (tracking.tracking_url) {
                                detailsHtml += '<td><a href="' + tracking.tracking_url + '" target="_blank">' + tracking.tracking_number + '</a></td>';
                            } else {
                                detailsHtml += '<td>' + tracking.tracking_number + '</td>';
                            }
                            
                            detailsHtml += '<td>' + tracking.shipped_at + '</td>';
                            detailsHtml += '</tr>';
                        }
                        
                        detailsHtml += '</tbody>';
                        detailsHtml += '</table>';
                        detailsHtml += '</div>';
                        detailsHtml += '</div>';
                        detailsHtml += '</div>';
                    }
                    
                    // Totals
                    detailsHtml += '<div class="row">';
                    detailsHtml += '<div class="col-md-6 offset-md-6">';
                    detailsHtml += '<table class="table table-borderless table-sm">';
                    detailsHtml += '<tbody>';
                    detailsHtml += '<tr>';
                    detailsHtml += '<th>' + wpwps_orders.subtotal + ':</th>';
                    detailsHtml += '<td class="text-end">' + order.totals.subtotal + '</td>';
                    detailsHtml += '</tr>';
                    
                    if (order.totals.shipping > 0) {
                        detailsHtml += '<tr>';
                        detailsHtml += '<th>' + wpwps_orders.shipping + ':</th>';
                        detailsHtml += '<td class="text-end">' + order.totals.shipping + '</td>';
                        detailsHtml += '</tr>';
                    }
                    
                    if (order.totals.tax > 0) {
                        detailsHtml += '<tr>';
                        detailsHtml += '<th>' + wpwps_orders.tax + ':</th>';
                        detailsHtml += '<td class="text-end">' + order.totals.tax + '</td>';
                        detailsHtml += '</tr>';
                    }
                    
                    if (order.totals.discount > 0) {
                        detailsHtml += '<tr>';
                        detailsHtml += '<th>' + wpwps_orders.discount + ':</th>';
                        detailsHtml += '<td class="text-end">-' + order.totals.discount + '</td>';
                        detailsHtml += '</tr>';
                    }
                    
                    detailsHtml += '<tr>';
                    detailsHtml += '<th>' + wpwps_orders.total + ':</th>';
                    detailsHtml += '<td class="text-end"><strong>' + order.totals.total + '</strong></td>';
                    detailsHtml += '</tr>';
                    detailsHtml += '</tbody>';
                    detailsHtml += '</table>';
                    detailsHtml += '</div>';
                    detailsHtml += '</div>';
                    
                    // Update modal content
                    modalContent.html(detailsHtml);
                } else {
                    modalContent.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + response.data.message + '</div>');
                }
            },
            error: function() {
                modalContent.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + wpwps_orders.load_error + '</div>');
            }
        });
    }
    
    /**
     * Handle pagination clicks.
     */
    $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        
        const page = $(this).data('page');
        
        if (page && !$(this).parent().hasClass('disabled')) {
            currentPage = page;
            loadOrders();
        }
    });
    
    /**
     * Sync individual order from the list.
     */
    $(document).on('click', '.sync-order', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const orderId = button.data('order-id');
        const row = button.closest('tr');
        
        // Disable button and show loading state
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i>');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_sync_single_order',
                nonce: wpwps_data.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    // Show success feedback
                    row.addClass('table-success');
                    setTimeout(function() {
                        row.removeClass('table-success');
                    }, 2000);
                    
                    // Reload orders after sync
                    setTimeout(function() {
                        loadOrders();
                    }, 1000);
                } else {
                    // Show error feedback
                    row.addClass('table-danger');
                    setTimeout(function() {
                        row.removeClass('table-danger');
                    }, 2000);
                    
                    // Reset button state
                    button.prop('disabled', false);
                    button.html('<i class="fas fa-sync-alt"></i>');
                }
            },
            error: function() {
                // Show error feedback
                row.addClass('table-danger');
                setTimeout(function() {
                    row.removeClass('table-danger');
                }, 2000);
                
                // Reset button state
                button.prop('disabled', false);
                button.html('<i class="fas fa-sync-alt"></i>');
            }
        });
    });
    
    /**
     * Get status badge HTML.
     *
     * @param {string} status Order status.
     * @return {string} Badge HTML.
     */
    function getStatusBadge(status) {
        let badge = '';
        
        switch (status) {
            case 'pending':
                badge = '<span class="badge bg-warning text-dark">' + wpwps_orders.status_pending + '</span>';
                break;
            case 'processing':
                badge = '<span class="badge bg-info text-dark">' + wpwps_orders.status_processing + '</span>';
                break;
            case 'on-hold':
                badge = '<span class="badge bg-secondary">' + wpwps_orders.status_on_hold + '</span>';
                break;
            case 'completed':
                badge = '<span class="badge bg-success">' + wpwps_orders.status_completed + '</span>';
                break;
            case 'cancelled':
                badge = '<span class="badge bg-danger">' + wpwps_orders.status_cancelled + '</span>';
                break;
            case 'refunded':
                badge = '<span class="badge bg-dark">' + wpwps_orders.status_refunded + '</span>';
                break;
            default:
                badge = '<span class="badge bg-secondary">' + status + '</span>';
        }
        
        return badge;
    }
});
