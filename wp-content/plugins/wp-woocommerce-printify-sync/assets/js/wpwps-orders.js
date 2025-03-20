jQuery(document).ready(function($) {
    // Maximum 10 per page for Printify Orders API according to documentation
    const perPage = 10; 
    let orderImportProgressInterval = null;

    // Event handlers
    $('#fetch-orders').on('click', function() {
        fetchOrders(true);
    });

    $('#clear-cache').on('click', function() {
        fetchOrders(true);
    });
    
    // Import all orders button handler
    $('#import-all-orders').on('click', function() {
        importAllOrders();
    });

    // Bulk import orders button handler - rename the existing function
    $('#bulk-import-orders').on('click', function() {
        bulkImportOrders();
    });

    // Initialize import button handlers
    $(document).on('click', '.import-order', function() {
        const button = $(this);
        const orderId = button.data('id');
        const originalHtml = button.html();
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'import_order_to_woo',
                nonce: wpwps_data.nonce,
                printify_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    button.html('<i class="fas fa-check"></i> Imported').addClass('btn-success').removeClass('btn-primary');
                    button.closest('tr').addClass('bg-light');
                } else {
                    button.html('<i class="fas fa-times"></i> Failed').addClass('btn-danger').removeClass('btn-primary');
                    setTimeout(function() {
                        button.prop('disabled', false).html(originalHtml).removeClass('btn-danger').addClass('btn-primary');
                    }, 3000);
                }
            },
            error: function() {
                button.html('<i class="fas fa-times"></i> Error').addClass('btn-danger').removeClass('btn-primary');
                setTimeout(function() {
                    button.prop('disabled', false).html(originalHtml).removeClass('btn-danger').addClass('btn-primary');
                }, 3000);
            }
        });
    });

    function fetchOrders(refreshCache = false) {
        const button = $('#fetch-orders');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        // Clear existing alerts
        $('#orders-alerts').empty();
        
        $('#orders-table tbody').html(
            '<tr><td colspan="8" class="text-center">' +
            '<i class="fas fa-spinner fa-spin"></i> Loading orders from Printify...' +
            '</td></tr>'
        );

        console.log('Fetching orders with params:', {
            action: 'printify_sync',
            action_type: 'fetch_printify_orders',
            nonce: wpwps_data.nonce,
            page: 1,
            per_page: perPage,
            refresh_cache: refreshCache ? 'true' : 'false'
        });

        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_orders',
                nonce: wpwps_data.nonce,
                page: 1,
                per_page: perPage,
                refresh_cache: refreshCache ? 'true' : 'false'
            },
            success: function(response) {
                console.log('Orders API response:', response);
                if (response?.success && response?.data?.orders) {
                    updateOrdersTable(response.data);
                    
                    // Show success message if cache was refreshed
                    if (refreshCache) {
                        $('#orders-alerts').html(`
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> Orders refreshed successfully
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                    }
                    
                    // Enable bulk import if we have orders
                    $('#import-all-orders').prop('disabled', !response.data.orders.length);
                } else {
                    handleError(response?.data?.message || 'Failed to fetch orders');
                    $('#import-all-orders').prop('disabled', true);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr: xhr, status: status, error: error});
                console.error('Response Text:', xhr.responseText);
                
                try {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    handleError('Failed to fetch orders: ' + (jsonResponse.message || error || 'Unknown error'));
                } catch(e) {
                    handleError('Failed to fetch orders: ' + (error || 'Server error (500)'));
                }
                
                $('#import-all-orders').prop('disabled', true);
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
            }
        });
    }
    
    // Function to import all orders from Printify
    function importAllOrders() {
        if (!confirm('Are you sure you want to import ALL orders from Printify? This may take some time for stores with many orders.')) {
            return;
        }
        
        const button = $('#import-all-orders');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        // Show progress container
        $('#order-import-progress-container').show().html(`
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-sync fa-spin me-2"></i> Starting All Orders Import...</h6>
                </div>
                <div class="card-body">
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                            role="progressbar" 
                            style="width: 0%" 
                            aria-valuenow="0" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                            0%
                        </div>
                    </div>
                    <div class="small text-muted">
                        Preparing to import all orders...
                    </div>
                </div>
            </div>
        `);
        
        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'import_all_orders',
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update any orders that were imported in the first batch
                    if (response.data.imported && response.data.imported.length > 0) {
                        response.data.imported.forEach(function(item) {
                            const button = $(`.import-order[data-id="${item.printify_id}"]`);
                            if (button.length > 0) {
                                button.prop('disabled', true)
                                    .html('<i class="fas fa-check"></i> Imported')
                                    .addClass('btn-success')
                                    .removeClass('btn-primary');
                                button.closest('tr').addClass('bg-light');
                            }
                        });
                    }
                    
                    // Show success message
                    $('#orders-alerts').html(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> ${response.data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    
                    // If more processing needed, start progress tracking
                    if (response.data.needs_more_processing) {
                        startOrderProgressTracking();
                    } else {
                        // Refresh orders list
                        setTimeout(function() {
                            fetchOrders(true);
                        }, 2000);
                        
                        $('#order-import-progress-container').html(`
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Order import completed!
                            </div>
                        `);
                        
                        // Hide progress after 5 seconds
                        setTimeout(function() {
                            $('#order-import-progress-container').fadeOut();
                        }, 5000);
                    }
                } else {
                    // Show error
                    $('#orders-alerts').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> ${response.data.message || 'Failed to import orders'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    
                    $('#order-import-progress-container').hide();
                }
            },
            error: function(xhr, status, error) {
                $('#orders-alerts').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> Failed to import orders: ${error}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                
                $('#order-import-progress-container').hide();
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
            }
        });
    }
    
    // Poll for order import progress
    function startOrderProgressTracking() {
        // Clear any existing interval
        if (orderImportProgressInterval) {
            clearInterval(orderImportProgressInterval);
        }
        
        // Start new polling interval
        orderImportProgressInterval = setInterval(checkOrderImportProgress, 3000);
    }
    
    // Check order import progress
    function checkOrderImportProgress() {
        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'get_order_import_progress',
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const percentage = data.percentage || 0;
                    
                    $('#order-import-progress-container').html(`
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-sync fa-spin me-2"></i> Import In Progress</h6>
                            </div>
                            <div class="card-body">
                                <div class="progress mb-2">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                        role="progressbar" 
                                        style="width: ${percentage}%" 
                                        aria-valuenow="${percentage}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        ${percentage}%
                                    </div>
                                </div>
                                <div class="small text-muted">
                                    Importing page ${data.current_page} of ${data.last_page}
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    // Check if import completed
                    clearInterval(orderImportProgressInterval);
                    
                    // Refresh orders list
                    fetchOrders(true);
                    
                    $('#order-import-progress-container').html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Order import completed!
                        </div>
                    `);
                    
                    // Hide progress after 5 seconds
                    setTimeout(function() {
                        $('#order-import-progress-container').fadeOut();
                    }, 5000);
                }
            },
            error: function() {
                // On error, stop polling
                clearInterval(orderImportProgressInterval);
                $('#order-import-progress-container').hide();
            }
        });
    }

    // Renamed the original bulk import to avoid confusion
    function bulkImportOrders() {
        if (!confirm('Are you sure you want to import all orders from Printify to WooCommerce?')) {
            return;
        }
        
        const button = $('#import-all-orders');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing All...');
        
        // Get all non-imported order IDs
        const orderIds = [];
        $('.import-order:not(.btn-success)').each(function() {
            orderIds.push($(this).data('id'));
        });
        
        if (orderIds.length === 0) {
            alert('No orders to import! All orders have already been imported.');
            button.prop('disabled', false).html(originalHtml);
            return;
        }
        
        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'bulk_import_orders',
                nonce: wpwps_data.nonce,
                printify_ids: orderIds
            },
            success: function(response) {
                if (response.success) {
                    // Mark imported orders in the UI
                    response.data.imported.forEach(function(item) {
                        const row = $(`.import-order[data-id="${item.printify_id}"]`).closest('tr');
                        row.find('.import-order').prop('disabled', true)
                            .html('<i class="fas fa-check"></i> Imported')
                            .addClass('btn-success')
                            .removeClass('btn-primary');
                        row.addClass('bg-light');
                    });
                    
                    // Show success message
                    const message = `Successfully imported ${response.data.imported.length} orders. ${response.data.failed.length} orders failed.`;
                    $('#orders-alerts').html(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    
                    if (response.data.failed.length > 0) {
                        console.error('Failed orders:', response.data.failed);
                    }
                } else {
                    $('#orders-alerts').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> ${response.data.message || 'Unknown error occurred'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $('#orders-alerts').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> Failed to import orders: ${error}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
                
                // If all orders imported, disable the button
                if ($('.import-order:not(.btn-success)').length === 0) {
                    button.prop('disabled', true);
                }
            }
        });
    }

    // Local formatCurrency function as fallback
    function formatCurrencyLocal(amount) {
        const num = parseFloat(amount);
        if (isNaN(num)) return '£0.00';
        
        const symbol = window.wpwps_data?.currency_symbols?.[window.wpwps_data?.currency] || '£';
        return symbol + num.toFixed(2);
    }

    function updateOrdersTable(data) {
        const orders = data.orders || [];
        const tbody = $('#orders-table tbody');
        tbody.empty();

        // Log all data for debugging
        console.log('Orders data received:', orders);

        if (orders.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center">No orders found</td></tr>');
            
            // Reset counters
            $('#showing-start').text('0');
            $('#showing-end').text('0');
            $('#total-orders').text('0');
            return;
        }

        orders.forEach(function(order) {
            const isImported = order.is_imported;
            const importButtonHtml = isImported ? 
                `<button class="btn btn-sm btn-success import-order" disabled data-id="${order.id || order.printify_id}"><i class="fas fa-check"></i> Imported</button>` : 
                `<button class="btn btn-sm btn-primary import-order" data-id="${order.id || order.printify_id}"><i class="fas fa-download"></i> Import</button>`;
                
            // Format customer name from address_to
            const firstName = order.address_to?.first_name || '';
            const lastName = order.address_to?.last_name || '';
            const customerName = order.customer_name || ((firstName || lastName) ? `${firstName} ${lastName}`.trim() : 'N/A');

            // Format status badge with appropriate color
            const statusBadgeClass = {
                'pending': 'warning',
                'fulfilled': 'success',
                'on-hold': 'info',
                'cancelled': 'danger',
                'refunded': 'secondary',
                'processing': 'primary'
            }[order.status] || 'secondary';

            // Format created date
            const orderDate = order.created_at ? new Date(order.created_at).toLocaleDateString() : 'N/A';

            // Debug log the price values for this specific order
            console.log('Order ' + order.printify_id + ' prices:', {
                total_price: order.total_price,
                total_shipping: order.total_shipping,
                total_amount: order.total_amount, 
                merchant_cost: order.merchant_cost,
                profit: order.profit,
                raw_total_price: order.raw_total_price,
                raw_total_shipping: order.raw_total_shipping,
                item_count: order.item_count,
                line_items: order.line_items
            });

            // Use simple formatting function that doesn't try any automatic division
            const formatPrice = function(value) {
                // Ensure we have a number
                const num = parseFloat(value);
                if (isNaN(num)) {
                    console.error('Invalid price value:', value);
                    return '£0.00';
                }
                
                // Get currency symbol from config or default to £
                const symbol = window.wpwps_data?.currency_symbols?.[window.wpwps_data?.currency] || '£';
                
                // Format with 2 decimal places
                return symbol + num.toFixed(2);
            };
            
            // Format the prices - using values that should already be divided by 100 from the server
            const productPrice = formatPrice(order.total_price);
            const shippingPrice = formatPrice(order.total_shipping);
            const totalPrice = formatPrice(order.total_amount);
            const merchantCost = formatPrice(order.merchant_cost);
            const profit = formatPrice(order.profit);

            // Create a more detailed shipping breakdown for multiple items
            let shippingBreakdown = shippingPrice;
            if (order.item_count > 1 && order.line_items && order.line_items.length > 0) {
                // Show a more detailed tooltip for multiple items
                shippingBreakdown = `
                    <span class="shipping-info" data-bs-toggle="tooltip" title="Order contains ${order.item_count} items">
                        ${shippingPrice}
                        <small class="text-muted d-block">
                            <i class="fas fa-info-circle"></i> ${order.item_count} items
                        </small>
                    </span>
                `;
            }

            // Format shipping status
            let shippingStatus = 'Pending';
            if (order.shipments && order.shipments.length > 0) {
                const carrier = order.shipments[0].carrier || 'N/A';
                const trackingNumber = order.shipments[0].number || '';
                shippingStatus = trackingNumber ? `${carrier} - ${trackingNumber}` : carrier;
            }

            // Simplified price display - show only total price
            const row = `
                <tr${isImported ? ' class="bg-light"' : ''}>
                    <td>${order.id || order.printify_id || 'N/A'}</td>
                    <td>${order.woo_order_id || 'N/A'}</td>
                    <td>${orderDate}</td>
                    <td>${customerName}</td>
                    <td><span class="badge bg-${statusBadgeClass}">${order.status || 'Unknown'}</span></td>
                    <td>${totalPrice}</td>
                    <td>${shippingStatus}</td>
                    <td>${importButtonHtml}</td>
                </tr>
            `;
            tbody.append(row);
        });

        // Init tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Update counters
        $('#showing-start').text('1');
        $('#showing-end').text(orders.length);
        $('#total-orders').text(data.total || orders.length);
    }

    function handleError(message) {
        $('#orders-table tbody').html(`
            <tr>
                <td colspan="8" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                </td>
            </tr>
        `);
        
        // Show alert
        $('#orders-alerts').html(`
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        // Reset counters
        $('#showing-start').text('0');
        $('#showing-end').text('0');
        $('#total-orders').text('0');
    }
});
